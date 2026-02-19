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
    // Seed with defaults if empty
    $defaults = ['PREMIER LEAGUE', 'TRANSFER NEWS', 'MATCH ANALYSIS', 'CHAMPIONS LEAGUE', 'NBA', 'GLOBAL SPORTS'];
    foreach ($defaults as $d) {
        $stmt = $conn->prepare("SELECT id FROM categories WHERE name = ?");
        $stmt->execute([$d]);
        if (!$stmt->fetch()) {
            $conn->prepare("INSERT INTO categories (name) VALUES (?)")->execute([$d]);
        }
    }
    $available_categories = $defaults;
}
$cat_list = implode(', ', $available_categories);

// 1. Ask AI for trending stories
$today = date('D d M Y');
$prompt = "Act as a leading global sports news aggregator. Today is $today.
Identify exactly 10 major sports news stories that are currently trending or occurred in the last 24 hours.
Focus on accuracy and provide latest updates for major leagues (Premier League, La Liga, Champions League, NBA, etc.).

For each of the 10 stories, provide a JSON object with:
1. 'title': Engaging, accurate headline.
2. 'category': Must be ONE of these exactly: ($cat_list).
3. 'content': A comprehensive 500-word report in an engaging fan-blogger tone. Use 4-5 long paragraphs. Use Markdown for styling.
4. 'image_keyword': A single highly specific search term for an image (e.g., 'Lionel Messi Inter Miami goal celebration').
5. 'tags': 5-8 relevant SEO tags (comma separated).
6. 'meta_title': SEO optimized title (max 60 chars).
7. 'meta_description': Compelling SEO description (max 160 chars).
8. 'meta_keywords': High-ranking keywords for this news.

Return the results as a JSON array containing exactly 10 objects. Do not include any other text, only the JSON array.";

echo "Requesting 10 stories from AI model: " . $settings['selected_model'] . "...\n";
$raw_ai = get_ai_insight($prompt);
if (!$raw_ai) {
    die("Error: AI failed to discover news. Check your API key and connection.\n");
}

// Extract JSON
$news_items = extract_json($raw_ai, true);

if (!$news_items) {
    echo "Error: Could not parse news data. Attempting secondary extraction...\n";
    // Sometimes AI adds text before/after. Try to find the first '[' and last ']'
    $start = strpos($raw_ai, '[');
    $end = strrpos($raw_ai, ']');
    if ($start !== false && $end !== false) {
        $json_str = substr($raw_ai, $start, $end - $start + 1);
        $news_items = json_decode($json_str, true);
    }
}

if (!$news_items) {
    file_put_contents(__DIR__ . '/last_failed_ai_response.txt', $raw_ai);
    die("Error: Critical failure parsing news data. Raw response saved to last_failed_ai_response.txt\n");
}

$date_path = date('Y/m/d');
$upload_dir = __DIR__ . "/../assets/uploads/news/" . $date_path . "/";
$web_dir = "/assets/uploads/news/" . $date_path . "/";
if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);

$count = 0;
foreach ($news_items as $item) {
    if ($count >= 10) break;
    echo "Processing (" . ($count + 1) . "/10): " . $item['title'] . "\n";

    // 2. Fetch Image - Using highly specific keywords
    $keyword = urlencode($item['image_keyword']);
    // Try multiple sources for better reliability
    $img_sources = [
        "https://loremflickr.com/1200/800/" . $keyword . "/all",
        "https://tse1.mm.bing.net/th?q=" . $keyword . "&w=1200&h=800&c=7&rs=1&p=0&dpr=1&pid=1.7",
        "https://source.unsplash.com/featured/1200x800/?" . $keyword
    ];

    $safe_title = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $item['title'])));
    $filename = $safe_title . "-" . time() . ".jpg";
    $local_img_path = $upload_dir . $filename;
    $db_img_path = $web_dir . $filename;

    $img_data = false;
    foreach ($img_sources as $source_url) {
        echo "Attempting to fetch image from: $source_url\n";
        $img_data = fetch_image($source_url);
        if ($img_data) break;
    }

    if ($img_data) {
        file_put_contents($local_img_path, $img_data);
    } else {
        echo "Failed to get image for: " . $item['title'] . ". Using fallback.\n";
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

    $stmt = $conn->prepare("INSERT INTO posts (title, slug, excerpt, content, category, author, image, is_top_story, tags, meta_title, meta_description, meta_keywords) VALUES (?, ?, ?, ?, ?, ?, ?, 1, ?, ?, ?, ?)");
    if ($stmt->execute([$title, $slug, $excerpt, $content, $category, $author, $db_img_path, $tags, $meta_title, $meta_desc, $meta_keys])) {
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
