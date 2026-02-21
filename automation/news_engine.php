<?php
// GFW News Automation Engine - AI ONLY (NO NewsAPI)
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

$conn = get_db_connection();
$settings = get_settings();

$apiKey = (strpos($settings['selected_model'], 'gemini') !== false) ? $settings['gemini_api_key'] : $settings['deepseek_api_key'];

if (empty($apiKey)) {
    die("Error: AI API Key missing. Configure it in Admin -> Parameters -> AI Core.\n");
}

echo "Starting AI-Powered News Discovery...\n";

// Fetch current categories from DB
$available_categories = $conn->query("SELECT name FROM categories")->fetchAll(PDO::FETCH_COLUMN);
if (empty($available_categories)) {
    // Seed default categories if missing
    $defaults = ['PREMIER LEAGUE', 'CHAMPIONS LEAGUE', 'TRANSFER NEWS', 'LA LIGA', 'SERIE A', 'BUNDESLIGA', 'MATCH ANALYSIS'];
    foreach ($defaults as $d) {
        $conn->prepare("INSERT IGNORE INTO categories (name) VALUES (?)")->execute([$d]);
    }
    $available_categories = $defaults;
}
$cat_list = implode(', ', $available_categories);

// 1. Discovery Stage: Ask AI for trending story headlines
$today = date('D d M Y H:i');
echo "Stage 1: Discovering trending football stories for $today...\n";

$discovery_prompt = "Identify exactly 10 of the LATEST and MOST ACCURATE major football news headlines that happened WITHIN THE LAST 24 HOURS (Current time: $today).

You MUST ONLY use information from the following official sources:
- skysports.com
- sky-sport.ch
- espn.com
- supersport.com
- Sport - Scores, Fixtures, News - Live Sport

Focus on: Latest match results, breaking transfers, and major team news.
Ensure coverage of Premier League, La Liga, Serie A, Bundesliga, and Ligue 1.

Return ONLY a valid JSON array of objects with these keys:
- 'title': Catchy sports headline.
- 'category': Must be ONE of: ($cat_list). Match the story to the most appropriate category.
- 'image_keyword': Specific search query for a photo of the event/player.
Return ONLY the JSON array. No other text.";

$discovered_items = [];
$discovery_source = $settings['discovery_source'] ?? 'ai';
$tavily_results = ($discovery_source === 'tavily') ? get_tavily_news("latest major football news headlines from skysports.com, espn.com, supersport.com, sky-sport.ch last 24 hours") : null;

if ($discovery_source === 'tavily' && $tavily_results && count($tavily_results) > 0) {
    echo "Using Tavily for high-precision news discovery...\n";
    foreach ($tavily_results as $res) {
        $discovered_items[] = [
            'title' => $res['title'],
            'category' => 'MATCH ANALYSIS', // Default, will be refined by AI
            'image_keyword' => $res['title']
        ];
    }
} else {
    echo "Using AI-powered news discovery...\n";
    $raw_discovery = get_ai_insight($discovery_prompt);
    if ($raw_discovery && strpos($raw_discovery, 'AI Error:') !== 0) {
        $discovered_items = extract_json($raw_discovery, true);
    }
}

if (empty($discovered_items)) {
    die("Error: News discovery failed. Please check your API keys.\n");
}

$date_path = date('Y/m/d');
$upload_dir = __DIR__ . "/../assets/uploads/news/" . $date_path . "/";
$web_dir = "/assets/uploads/news/" . $date_path . "/";
if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);

$count = 0;
$fetched_hashes = [];
$published_posts = [];
foreach ($discovered_items as $item) {
    if ($count >= 10) break;

    // Skip if already exists or similar slug found (prevent duplicates)
    $safe_title = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $item['title'])));
    $check_stmt = $conn->prepare("SELECT id FROM posts WHERE title = ? OR slug LIKE ?");
    $check_stmt->execute([$item['title'], $safe_title . '%']);
    if ($check_stmt->fetch()) {
        echo "Skipping existing or duplicate post: " . $item['title'] . "\n";
        continue;
    }

    echo "\n--- Processing Story " . ($count + 1) . ": " . $item['title'] . " ---\n";

    // Stage 2: Content Generation for this specific story
    echo "Stage 2: Generating high-level content and SEO metadata...\n";
    $target_cat = $item['category'];
    $content_prompt = "Act as an expert football journalist. Write a detailed breaking news article about this story: '{$item['title']}'.

    CRITICAL: Determine the best category for this story from this list: ($cat_list).

    Requirements:
    0. 'category': The chosen category from the list.
    1. 'content': Comprehensive sports report (400-500 words) in an engaging fan-blogger tone. Use 3-4 paragraphs. Use Markdown.
    2. 'tags': 6-10 high-ranking SEO tags.
    3. 'meta_title': SEO optimized title (max 60 chars).
    4. 'meta_description': Compelling SEO description (max 160 chars).
    5. 'meta_keywords': High ranking specific keywords.

    Return ONLY a valid JSON object. No extra text.";

    $raw_content = get_ai_insight($content_prompt);
    if (!$raw_content || strpos($raw_content, 'AI Error:') === 0) {
        echo "Error: Content generation failed for this item. Skipping.\n";
        continue;
    }

    $content_data = extract_json($raw_content, false);
    if (!$content_data) {
        echo "Error: Could not parse content data. Skipping.\n";
        continue;
    }

    // 3. Fetch Image - Multi-Source Unique Discovery
    $specific_keyword = urlencode($item['image_keyword'] . " " . rand(100, 999));
    $category_keyword = urlencode($item['category'] . " " . $item['title']);

    $image_sources = [
        "https://tse1.mm.bing.net/th?q=" . $specific_keyword . "&w=1200&h=800&c=7&rs=1&p=0&dpr=1&pid=Api",
        "https://tse1.mm.bing.net/th?q=" . urlencode($item['title'] . " sports photography") . "&w=1200&h=800&c=7&rs=1&p=0&dpr=1&pid=Api",
        "https://loremflickr.com/1200/800/" . urlencode(str_replace(' ', ',', $item['image_keyword'])) . "/all?lock=" . rand(1, 99999),
        "https://tse1.mm.bing.net/th?q=" . $category_keyword . "&w=1200&h=800&c=7&rs=1&p=0&dpr=1&pid=Api"
    ];

    $img_data = null;
    foreach ($image_sources as $source_url) {
        echo "Attempting image fetch from source...\n";
        $temp_data = fetch_image($source_url);
        if ($temp_data && strlen($temp_data) > 8000) {
            $temp_hash = md5($temp_data);
            if (!in_array($temp_hash, $fetched_hashes)) {
                $img_data = $temp_data;
                $fetched_hashes[] = $temp_hash;
                echo "Unique image binary acquired.\n";
                break;
            }
        }
    }

    sleep(1);

    $filename = $safe_title . "-" . time() . ".jpg";
    $local_img_path = $upload_dir . $filename;
    $db_img_path = $web_dir . $filename;

    if ($img_data) {
        file_put_contents($local_img_path, $img_data);
        echo "Saved unique image for: " . $item['title'] . "\n";
    } else {
        echo "Failed to get unique image. Using default.\n";
        $db_img_path = "/assets/img/default-news.jpg";
    }

    // 4. Save to Database
    $title = sanitize($item['title']);
    $slug = $safe_title . '-' . time();
    $content = $content_data['content'];
    $excerpt = sanitize(substr(strip_tags($content), 0, 150)) . '...';
    $category = $content_data['category'] ?? $item['category'];
    $author = 'GFW';

    $tags = sanitize($content_data['tags'] ?? '');
    $meta_title = sanitize($content_data['meta_title'] ?? $title);
    $meta_desc = sanitize($content_data['meta_description'] ?? $excerpt);
    $meta_keys = sanitize($content_data['meta_keywords'] ?? '');
    $publish_date = date('Y-m-d H:i:s');

    $stmt = $conn->prepare("INSERT INTO posts (title, slug, excerpt, content, category, author, image, is_top_story, publish_date, tags, meta_title, meta_description, meta_keywords) VALUES (?, ?, ?, ?, ?, ?, ?, 1, ?, ?, ?, ?, ?)");
    if ($stmt->execute([$title, $slug, $excerpt, $content, $category, $author, $db_img_path, $publish_date, $tags, $meta_title, $meta_desc, $meta_keys])) {
        $post_id = $conn->lastInsertId();
        echo "Successfully published: $title\n";

        echo "Broadcasting to social media...\n";
        broadcast_to_social($post_id);

        $published_posts[] = [
            'title' => $title,
            'slug' => $slug,
            'excerpt' => $excerpt
        ];

        $count++;
    } else {
        echo "Database error.\n";
    }
}

echo "\nAI Automation complete. $count posts published.\n";

// 5. Notify Subscribers
if ($count > 0) {
    echo "Notifying subscribers...\n";
    $subscribers = $conn->query("SELECT email FROM subscribers")->fetchAll(PDO::FETCH_COLUMN);

    if (!empty($subscribers)) {
        $subject = "Daily Sports Intelligence Digest - " . date('D d M Y');

        $content = "<p style='font-size:18px; color:#ff3e3e; font-weight:bold; margin-bottom:30px; text-transform:uppercase;'>Daily Intelligence Digest: ".date('d M Y')."</p>";

        foreach ($published_posts as $post) {
            $post_url = SITE_URL . "/post/" . $post['slug'];
            $content .= "
                <div class='news-item'>
                    <a href='$post_url' class='news-title'>{$post['title']}</a>
                    <p class='news-excerpt'>{$post['excerpt']}</p>
                    <a href='$post_url' class='btn'>Decrypt Full Report</a>
                </div>
            ";
        }

        $message = render_email_template($content, "Daily Intelligence Digest");

        foreach ($subscribers as $email) {
            send_mail($email, $subject, $message);
        }
        echo "Notification sent to " . count($subscribers) . " subscribers.\n";
    }
}
?>
