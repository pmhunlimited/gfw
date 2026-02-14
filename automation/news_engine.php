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
$cat_list = implode(', ', $available_categories);

// 1. Ask AI for trending stories
$today = date('D d M Y');
$prompt = "Act as a leading football news aggregator. Based on current global football trends around $today, identify 5 major news stories.
For each story, provide:
1. 'title': Engaging headline.
2. 'category': Must be ONE of these exactly: ($cat_list). Choose the most appropriate one.
3. 'content': A comprehensive 500-word sports report in an engaging fan-blogger tone. Structure it with 4 to 5 long, detailed paragraphs. Use Markdown.
4. 'image_keyword': 3-5 highly specific keywords for an exact image matching this story (e.g., 'Erling Haaland Manchester City' instead of just 'football').
Return the results as a JSON array of objects ONLY.";

$raw_ai = get_ai_insight($prompt);
if (!$raw_ai || strpos($raw_ai, '[') === false) {
    die("Error: AI failed to discover news.\n");
}

// Extract JSON
$json_start = strpos($raw_ai, '[');
$json_end = strrpos($raw_ai, ']');
$json_str = substr($raw_ai, $json_start, $json_end - $json_start + 1);
$news_items = json_decode($json_str, true);

if (!$news_items) die("Error: Could not parse news data.\n");

$date_path = date('Y/m/d');
$upload_dir = __DIR__ . "/../assets/uploads/news/" . $date_path . "/";
$web_dir = "/assets/uploads/news/" . $date_path . "/";
if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);

$count = 0;
foreach ($news_items as $item) {
    if ($count >= 5) break;
    echo "Processing: " . $item['title'] . "\n";

    // 2. Fetch Image - Using highly specific keywords for exact match
    $keyword = urlencode(str_replace(' ', ',', $item['image_keyword']) . ",football,soccer");
    $img_url = "https://loremflickr.com/1200/800/" . $keyword . "/all";

    $safe_title = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $item['title'])));
    $filename = $safe_title . "-" . time() . ".jpg";
    $local_img_path = $upload_dir . $filename;
    $db_img_path = $web_dir . $filename;

    $img_data = @file_get_contents($img_url);
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

    $stmt = $conn->prepare("INSERT INTO posts (title, slug, excerpt, content, category, author, image, is_top_story) VALUES (?, ?, ?, ?, ?, ?, ?, 1)");
    if ($stmt->execute([$title, $slug, $excerpt, $content, $category, $author, $db_img_path])) {
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
