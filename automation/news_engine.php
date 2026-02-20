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

// 1. Ask AI for trending stories
$today = date('D d M Y');
$prompt = "Act as a leading football news aggregator. Today's date is $today.
CRITICAL: Identify exactly 10 of the LATEST and MOST ACCURATE major news stories that happened WITHIN THE LAST 24 HOURS (specifically on $today).
Focus EXCLUSIVELY on: Latest match results from today, breaking transfers announced today, and major team news/press conferences from today.
DO NOT include old news or general historical facts. Every story MUST be a 'featured news' item from the last 24 hours.
Ensure you cover a variety of leagues: Premier League, La Liga, Serie A, Bundesliga, and Ligue 1. Each of the 10 stories must be distinct and relate to a different match or event.

For each story, provide:
1. 'title': Engaging, accurate and descriptive sports headline for $today.
2. 'category': Must be ONE of these exactly: ($cat_list). Choose the most appropriate one.
3. 'content': A comprehensive sports report (approx 400 words) in an engaging fan-blogger tone. Structure it with 3 to 4 detailed paragraphs. Use Markdown.
4. 'image_keyword': EXTREMELY IMPORTANT: Provide a highly specific, UNIQUE and VISUALLY DESCRIPTIVE search query for a photo related ONLY to this specific news story.
   Include specific player names, team colors, or stadium names (e.g. 'Kylian Mbappe celebrating goal for Real Madrid vs Atletico in action shot photography').
   Ensure each of the 10 stories has a COMPLETELY DIFFERENT and HIGHLY ACCURATE image_keyword.
   STRICTLY PROHIBITED: Do not return generic images like a lone football, a generic grass field, or an empty stadium if the news is about a specific person or team.
5. 'tags': 6-10 relevant and high-ranking SEO tags (comma separated).
6. 'meta_title': High level SEO optimized title (max 60 chars) for maximum site ranking.
7. 'meta_description': Compelling and high-level SEO description (max 160 chars).
8. 'meta_keywords': High ranking, specific keywords for this news event.
Return ONLY a valid JSON array of 10 objects. No other text, no markdown code blocks.";

$raw_ai = get_ai_insight($prompt);
if (!$raw_ai || strpos($raw_ai, 'AI Error:') === 0) {
    die("Error: AI discovery failed. Raw: " . $raw_ai . "\n");
}

// Extract JSON
$news_items = extract_json($raw_ai, true);

if (!$news_items) die("Error: Could not parse news data. Raw: " . substr($raw_ai, 0, 100) . "...\n");

$date_path = date('Y/m/d');
$upload_dir = __DIR__ . "/../assets/uploads/news/" . $date_path . "/";
$web_dir = "/assets/uploads/news/" . $date_path . "/";
if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);

$count = 0;
$fetched_hashes = [];
foreach ($news_items as $item) {
    if ($count >= 10) break;

    // Skip if already exists
    $check_stmt = $conn->prepare("SELECT id FROM posts WHERE title = ?");
    $check_stmt->execute([$item['title']]);
    if ($check_stmt->fetch()) {
        echo "Skipping existing post: " . $item['title'] . "\n";
        continue;
    }

    echo "Processing: " . $item['title'] . "\n";

    // 2. Fetch Image - Multi-Source Unique Discovery
    $specific_keyword = urlencode($item['image_keyword'] . " " . rand(100, 999)); // Added entropy for unique results
    $category_keyword = urlencode($item['category'] . " " . $item['title']);

    $image_sources = [
        "https://tse1.mm.bing.net/th?q=" . $specific_keyword . "&w=1200&h=800&c=7&rs=1&p=0&dpr=1&pid=Api",
        "https://tse1.mm.bing.net/th?q=" . urlencode($item['title'] . " sports photography") . "&w=1200&h=800&c=7&rs=1&p=0&dpr=1&pid=Api",
        "https://loremflickr.com/1200/800/" . urlencode(str_replace(' ', ',', $item['image_keyword'])) . "/all?lock=" . rand(1, 99999),
        "https://tse1.mm.bing.net/th?q=" . $category_keyword . "&w=1200&h=800&c=7&rs=1&p=0&dpr=1&pid=Api"
    ];

    $img_data = null;
    foreach ($image_sources as $source_url) {
        echo "Attempting fetch from: " . substr($source_url, 0, 50) . "...\n";
        $temp_data = fetch_image($source_url);
        if ($temp_data && strlen($temp_data) > 8000) { // Increased threshold to avoid small generic thumbnails
            $temp_hash = md5($temp_data);
            if (!in_array($temp_hash, $fetched_hashes)) {
                $img_data = $temp_data;
                $fetched_hashes[] = $temp_hash;
                echo "Match found! Unique binary hash acquired.\n";
                break;
            } else {
                echo "Duplicate binary detected, skipping source...\n";
            }
        }
    }

    // Safety delay to prevent provider throttling and duplicate responses
    sleep(1);

    $safe_title = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $item['title'])));
    $filename = $safe_title . "-" . time() . ".jpg";
    $local_img_path = $upload_dir . $filename;
    $db_img_path = $web_dir . $filename;

    if ($img_data) {
        file_put_contents($local_img_path, $img_data);
        echo "Successfully fetched unique image for: " . $item['title'] . "\n";
    } else {
        echo "Failed to get relevant image for: " . $item['title'] . ". Using default.\n";
        $db_img_path = "/assets/img/default-news.jpg";
    }

    // 3. Save to Database
    $title = sanitize($item['title']);
    $slug = $safe_title . '-' . time();
    $content = $item['content']; // Markdown supported
    $excerpt = sanitize(substr(strip_tags($content), 0, 150)) . '...';
    $category = $item['category'];
    $author = 'GFW';

    $tags = sanitize($item['tags'] ?? '');
    $meta_title = sanitize($item['meta_title'] ?? $title);
    $meta_desc = sanitize($item['meta_description'] ?? $excerpt);
    $meta_keys = sanitize($item['meta_keywords'] ?? '');
    $publish_date = date('Y-m-d H:i:s');

    $stmt = $conn->prepare("INSERT INTO posts (title, slug, excerpt, content, category, author, image, is_top_story, publish_date, tags, meta_title, meta_description, meta_keywords) VALUES (?, ?, ?, ?, ?, ?, ?, 1, ?, ?, ?, ?, ?)");
    if ($stmt->execute([$title, $slug, $excerpt, $content, $category, $author, $db_img_path, $publish_date, $tags, $meta_title, $meta_desc, $meta_keys])) {
        $post_id = $conn->lastInsertId();
        echo "Successfully published: $title\n";

        echo "Broadcasting to social media...\n";
        broadcast_to_social($post_id);

        $count++;
    } else {
        echo "Database error.\n";
    }
}

echo "\nAI Automation complete. $count posts published.\n";
?>
