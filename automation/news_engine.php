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

// 1. Ask AI for trending stories
$today = date('D d M Y');
$prompt = "Act as a leading football news aggregator. Based on current global football trends around $today, identify 5 major news stories.
For each story, provide a unique 'title', a 'category' (choose from: Premier League, Champions League, La Liga, Serie A, Bundesliga, Transfer News),
a 'content' (400-word engaging blog post in fan-blogger tone), and an 'image_keyword' (2-3 words for a high-quality sports photo).
Return the results as a JSON array of objects.";

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

    // 2. Fetch Image (using Unsplash Source Redirect if possible, or direct URL generation)
    // We'll use a reliable keyword-based image fetching strategy
    $keyword = urlencode($item['image_keyword'] . " football");
    $img_url = "https://loremflickr.com/1200/800/" . $keyword;

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
    $author = 'GFW INTELLIGENCE';

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
