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
CRITICAL: Identify exactly 10 of the LATEST and MOST ACCURATE major news stories that happened WITHIN THE LAST 24 HOURS (today, $today).
Focus on: Latest match results from today, breaking transfers from today, and major team news from today.
Cover various leagues: Premier League, La Liga, Serie A, Bundesliga, Ligue 1, and global transfer news.

For each story, provide:
1. 'title': Engaging, accurate and descriptive sports headline for $today.
2. 'category': Must be ONE of these exactly: ($cat_list). Choose the most appropriate one.
3. 'content': A comprehensive sports report (approx 400 words) in an engaging fan-blogger tone. Structure it with 3 to 4 detailed paragraphs. Use Markdown.
4. 'image_keyword': 4-6 highly specific and accurate keywords for an exact image matching this specific news story (e.g., 'Erling Haaland scoring vs Arsenal today' instead of just 'Haaland').
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
foreach ($news_items as $item) {
    if ($count >= 10) break;
    echo "Processing: " . $item['title'] . "\n";

    // 2. Fetch Image - Robust Discovery from Multiple Sources
    $specific_keyword = urlencode($item['image_keyword']);
    $general_keyword = urlencode($item['image_keyword'] . " football soccer");
    $img_data = null;

    // Source 1: LoremFlickr with specific lock
    $img_url = "https://loremflickr.com/1200/800/" . urlencode(str_replace(' ', ',', $item['image_keyword'])) . "/all?lock=" . rand(1, 9999);
    $img_data = fetch_image($img_url);

    // Source 2: Bing Thumbnail (if source 1 failed)
    if (!$img_data) {
        $bing_url = "https://tse1.mm.bing.net/th?q=" . $specific_keyword . "&w=1200&h=800&c=7&rs=1&p=0&dpr=1&pid=Api";
        $img_data = fetch_image($bing_url);
    }

    // Source 3: Unsplash Source (Fallback)
    if (!$img_data) {
        $unsplash_url = "https://source.unsplash.com/featured/1200x800/?" . $specific_keyword . ",football";
        $img_data = fetch_image($unsplash_url);
    }

    $safe_title = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $item['title'])));
    $filename = $safe_title . "-" . time() . ".jpg";
    $local_img_path = $upload_dir . $filename;
    $db_img_path = $web_dir . $filename;

    if ($img_data && strlen($img_data) > 1000) { // Ensure it's not a tiny placeholder
        file_put_contents($local_img_path, $img_data);
        echo "Successfully fetched image for: " . $item['title'] . "\n";
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
