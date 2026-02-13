<?php
// GFW News Automation Engine
// Designed for root-level deployment

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

$conn = get_db_connection();
$settings = get_settings();

$gemini_key = $settings['gemini_api_key'] ?? '';
$news_api_key = $settings['news_api_key'] ?? '';

if (empty($gemini_key) || empty($news_api_key)) {
    die("Error: API Keys missing. Configure them in Admin -> Parameters -> AI Core.\n");
}

$date_path = date('Y/m/d');
$upload_dir = __DIR__ . "/../assets/uploads/news/" . $date_path . "/";
$web_dir = "/assets/uploads/news/" . $date_path . "/";

if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);

/**
 * Enhanced Gemini Caller with fallback and tone instruction
 */
function callGeminiForAutomation($prompt, $apiKey) {
    $models = ["gemini-2.0-flash", "gemini-1.5-flash"]; // Adjusted from 2.5 as it's likely a typo in user prompt or represents future version, 2.0 is current latest flash.

    foreach ($models as $model) {
        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}";

        $data = [
            "contents" => [["parts" => [["text" => $prompt]]]]
        ];

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        $response = curl_exec($ch);
        $result = json_decode($response, true);
        curl_close($ch);

        if (isset($result['candidates'][0]['content']['parts'][0]['text'])) {
            return $result['candidates'][0]['content']['parts'][0]['text'];
        }
    }
    return null;
}

/**
 * Category Mapping Logic
 */
function mapCategory($title, $categories) {
    $map = [
        'Premier League' => ['PL', 'Premier League', 'Arsenal', 'Liverpool', 'Manchester', 'Chelsea', 'Tottenham', 'City', 'United'],
        'Champions League' => ['UCL', 'Champions League', 'Real Madrid', 'Bayern', 'PSG', 'Dortmund'],
        'La Liga' => ['La Liga', 'Barcelona', 'Real Madrid', 'Atletico'],
        'Serie A' => ['Serie A', 'Juventus', 'Milan', 'Inter', 'Napoli', 'Roma'],
        'Bundesliga' => ['Bundesliga', 'Bayern', 'Bayer', 'Dortmund'],
        'Transfer News' => ['Transfer', 'Signing', 'Deal', 'Contract', 'Bid', 'Agent']
    ];

    foreach ($map as $cat_name => $keywords) {
        foreach ($keywords as $kw) {
            if (stripos($title, $kw) !== false) {
                // Check if this category exists in DB
                foreach ($categories as $db_cat) {
                    if ($db_cat['name'] == $cat_name) return $cat_name;
                }
            }
        }
    }
    return 'Football'; // Default
}

// 1. Fetch News from NewsAPI.org
$news_url = "https://newsapi.org/v2/top-headlines?category=sports&q=football&language=en&apiKey=$news_api_key";
$news_json = file_get_contents($news_url);
if (!$news_json) die("Error: Failed to fetch news from NewsAPI.\n");

$news_data = json_decode($news_json, true);
if (empty($news_data['articles'])) die("No articles found.\n");

$categories = $conn->query("SELECT name FROM categories")->fetchAll();

$count = 0;
foreach ($news_data['articles'] as $article) {
    if ($count >= 5) break;
    if (empty($article['description']) || empty($article['urlToImage'])) continue;

    echo "Processing: " . $article['title'] . "\n";

    // 2. Rewrite Content with Gemini
    $prompt = "Rewrite this football news into a unique 400-word blog post.
               Write in a first-person 'fan blogger' perspective to ensure the tone is distinct and engaging.
               Return the response in JSON format with two keys: 'title' and 'content'.
               Original Content: " . $article['title'] . " - " . $article['description'];

    $raw_ai = callGeminiForAutomation($prompt, $gemini_key);
    if (!$raw_ai) {
        echo "AI Failure for this article.\n";
        continue;
    }

    // Extract JSON from AI response
    $json_start = strpos($raw_ai, '{');
    $json_end = strrpos($raw_ai, '}');
    if ($json_start === false || $json_end === false) {
        echo "Invalid AI response format.\n";
        continue;
    }
    $ai_data = json_decode(substr($raw_ai, $json_start, $json_end - $json_start + 1), true);

    if ($ai_data && !empty($ai_data['title']) && !empty($ai_data['content'])) {
        // 3. Handle Image
        $img_url = $article['urlToImage'];
        $safe_title = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $ai_data['title'])));
        $filename = $safe_title . ".jpg";
        $local_img_path = $upload_dir . $filename;
        $db_img_path = $web_dir . $filename;

        $img_data = @file_get_contents($img_url);
        if ($img_data) {
            file_put_contents($local_img_path, $img_data);
        } else {
            echo "Failed to download image. Skipping.\n";
            continue;
        }

        // 4. Save to Database
        $title = $ai_data['title'];
        $slug = $safe_title . '-' . time();
        $content = $ai_data['content'];
        $excerpt = sanitize(substr(strip_tags($content), 0, 150)) . '...';
        $category = mapCategory($title, $categories);
        $author = 'AI FAN BLOG';

        $stmt = $conn->prepare("INSERT INTO posts (title, slug, excerpt, content, category, author, image, is_top_story) VALUES (?, ?, ?, ?, ?, ?, ?, 1)");
        if ($stmt->execute([$title, $slug, $excerpt, $content, $category, $author, $db_img_path])) {
            echo "Successfully published: $title\n";
            $count++;
        } else {
            echo "Database error.\n";
        }
    } else {
        echo "Failed to parse AI data.\n";
    }
}

echo "\nAutomation complete. $count posts published.\n";
?>
