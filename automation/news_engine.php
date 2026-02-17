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
    die("Error: No categories found in database. Please create categories first.\n");
}
$cat_list = implode(', ', $available_categories);

// 1. Ask AI for trending stories
$today = date('D d M Y');
$prompt = "Act as a leading global football news aggregator.
CRITICAL: Only identify major news stories that happened TODAY ($today). Do not include old news.
Identify 10 major fresh stories, ensuring a balanced distribution specifically for each major league (Premier League, La Liga, Champions League, Serie A, Bundesliga, and Ligue 1).

For each story, provide:
1. 'title': Engaging, sharp headline.
2. 'category': Must be ONE of these exactly: ($cat_list). Choose the most appropriate one.
3. 'content': A comprehensive sports report in an engaging fan-blogger tone with extremely high-level SEO optimization. Structure it with 4 to 5 long, detailed paragraphs. Use Markdown.
4. 'image_keyword': 3-5 EXTREMELY specific keywords for an exact image matching this story (e.g., 'Erling Haaland goal celebration vs Arsenal 2024' or 'Kylian Mbappe Real Madrid presentation' - avoid all generic terms).
5. 'tags': 8-12 relevant, high-ranking SEO tags (comma separated).
6. 'meta_title': High-level SEO optimized title (max 60 chars, must include primary keywords and the league name).
7. 'meta_description': Compelling, high-converting SEO description (max 160 chars, must be optimized for search intent and accuracy).
8. 'meta_keywords': High ranking, specific keywords for this specific news.
Return the results as a JSON array of 10 objects ONLY.";

$raw_ai = get_ai_insight($prompt);
if (!$raw_ai || strpos($raw_ai, 'AI Error:') !== false || strpos($raw_ai, 'API Key missing') !== false) {
    error_log("AI discovery failed. Raw output: " . ($raw_ai ?? 'NULL'));
    die("Error: AI discovery failed. Raw: " . ($raw_ai ?? 'NULL') . "\n");
}

// Extract JSON
$news_items = extract_json($raw_ai, true);

if ($news_items === null) {
    error_log("JSON parsing failed. Raw output: " . substr($raw_ai, 0, 1000));
    die("Error: Could not parse news data. Raw: " . substr($raw_ai, 0, 500) . "\n");
}

if (empty($news_items)) {
    die("No news stories discovered for today. AI response was empty array.\n");
}

$date_path = date('Y/m/d');
$upload_dir = __DIR__ . "/../assets/uploads/news/" . $date_path . "/";
$web_dir = "/assets/uploads/news/" . $date_path . "/";
if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);

$count = 0;
foreach ($news_items as $item) {
    if ($count >= 10) break;
    echo "Processing: " . $item['title'] . "\n";

    // 2. Fetch Image - Using highly specific keywords for exact match
    $keyword = urlencode(str_replace(' ', ',', $item['image_keyword']) . ",football,soccer");
    $img_url = "https://loremflickr.com/1200/800/" . $keyword . "/all";

    $safe_title = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $item['title'])));
    $filename = $safe_title . "-" . time() . ".jpg";
    $local_img_path = $upload_dir . $filename;
    $db_img_path = $web_dir . $filename;

    $img_data = fetch_image($img_url);

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
