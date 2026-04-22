<?php
// GFW News Automation Engine - AI ONLY (NO NewsAPI)
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

$conn = get_db_connection();
$settings = get_settings();

$apiKey = $settings['deepseek_api_key'] ?? '';

if (empty($apiKey)) {
    die("Error: DeepSeek API Key missing. Configure it in Admin -> Settings.\n");
}

echo "Starting AI-Powered News Discovery...\n";

// Fetch current categories from DB - Strictly restricted
$available_categories = ['Football News', 'Transfer News'];
foreach ($available_categories as $d) {
    $conn->prepare("INSERT IGNORE INTO categories (name) VALUES (?)")->execute([$d]);
}
$cat_list = implode(', ', $available_categories);

// 1. Discovery Stage: Fetch factual news from RSS Feeds
$today = date('D d M Y H:i');
echo "Stage 1: Discovering factual sports stories from RSS for $today...\n";

$rss_urls = get_rss_feed_urls();

$discovered_items = [];
$rss_results = get_rss_news($rss_urls);

if (!empty($rss_results)) {
    echo "Using RSS Feeds for 100% factual discovery...\n";
    foreach ($rss_results as $res) {
        if (count($discovered_items) >= 20) break;
        $discovered_items[] = [
            'title' => $res['title'],
            'description' => $res['description'],
            'source_link' => $res['link'],
            'category' => 'MATCH ANALYSIS', // Default, refined by AI
            'image_keyword' => $res['title']
        ];
    }
}

if (empty($discovered_items)) {
    die("Error: News discovery failed. Please check your API keys.\n");
}

// 1.5 Deduplication Stage: Use AI to identify and remove redundant stories
echo "Stage 1.5: Filtering redundant stories via AI...\n";
echo "Stage 1.1: Pre-filtering existing database content...\n";
$filtered_discovery = [];
foreach ($discovered_items as $item) {
    $safe_title = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $item['title'])));
    $title_prefix = substr($item['title'], 0, 20) . '%';

    $check = $conn->prepare("SELECT id FROM posts WHERE title = ? OR slug LIKE ? OR source_url = ? OR title LIKE ?");
    $check->execute([$item['title'], $safe_title . '%', $item['source_link'], $title_prefix]);
    if ($check->fetch()) {
        echo "Pre-filtered duplicate: " . $item['title'] . "\n";
        continue;
    }
    $filtered_discovery[] = $item;
}
$discovered_items = $filtered_discovery;

echo "Stage 1.2: Streamlined League & Cross-Run Filtering via AI...\n";
// Fetch recent headlines from DB to prevent cross-run duplication
$recent_db_posts = $conn->query("SELECT title FROM posts ORDER BY created_at DESC LIMIT 50")->fetchAll(PDO::FETCH_COLUMN);
$db_headlines_str = implode("\n", array_map(function($t) { return "- " . $t; }, $recent_db_posts));

$headlines_to_filter = "";
foreach ($discovered_items as $idx => $item) {
    $headlines_to_filter .= "$idx: {$item['title']}\n";
}

$filter_prompt = "Act as a Content Curator for a European Football Intelligence Network.
I have a list of discovered headlines from RSS feeds.
You MUST filter this list and return a JSON array of indices (integers) that I should KEEP.

STRICT FILTERING RULES:
1. KEEP only news related to European Football (Premier League, La Liga, Serie A, Ligue 1, Bundesliga, Champions League, Europa League, Conference League) and associated transfers.
2. REMOVE all American sports content (NFL, NBA, MLB, NHL, MLS).
3. REMOVE headlines that refer to the SAME event already present in our database.
4. If multiple headlines in the current list refer to the SAME event, KEEP only the most descriptive one.

RECENT DATABASE HEADLINES (ALREADY PUBLISHED):
$db_headlines_str

DISCOVERED HEADLINES TO FILTER:
$headlines_to_filter

Return ONLY a valid JSON array of integers. Example: [0, 3, 4]";

$raw_filter = get_ai_insight($filter_prompt);
$keep_indices = extract_json($raw_filter, true);

if (is_array($keep_indices)) {
    $filtered_items = [];
    foreach ($keep_indices as $idx) {
        if (isset($discovered_items[$idx])) {
            $filtered_items[] = $discovered_items[$idx];
        }
    }
    $discovered_items = $filtered_items;
    echo "AI Filtering complete. " . count($discovered_items) . " high-quality unique stories remaining.\n";
}

$date_path = date('Y/m/d');
$upload_dir = __DIR__ . "/../assets/uploads/news/" . $date_path . "/";
$web_dir = "/assets/uploads/news/" . $date_path . "/";
if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);

$count = 0;
$loop_idx = 0;
$fetched_hashes = [];
$published_post_ids = [];
foreach ($discovered_items as $item) {
    $loop_idx++;
    if ($count >= 10) break;

    // Skip if already exists or similar slug found (prevent duplicates)
    $safe_title = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $item['title'])));
    $source_url = $item['source_link'] ?? '';

    // Robust check: Exact title, Slug prefix, Source URL, or Title prefix (first 25 chars)
    $title_prefix = substr($item['title'], 0, 25) . '%';

    $check_stmt = $conn->prepare("SELECT id FROM posts WHERE title = ? OR slug LIKE ? OR (source_url != '' AND source_url = ?) OR title LIKE ?");
    $check_stmt->execute([$item['title'], $safe_title . '%', $source_url, $title_prefix]);
    if ($check_stmt->fetch()) {
        echo "Skipping existing or duplicate post: " . $item['title'] . "\n";
        continue;
    }

    echo "\n--- Processing Story $loop_idx: " . $item['title'] . " ---\n";

    // Stage 2: Content Generation for this specific story
    echo "Stage 2: Factual Rewriting and SEO metadata generation...\n";
    $target_cat = $item['category'];
    $content_prompt = "Act as an Expert Football Columnist. You are writing for a high-end football intelligence network.

    SOURCE DATA:
    Headline: '{$item['title']}'
    Factual Summary: '{$item['description']}'

    STRICT LINGUISTIC GUIDELINES FOR 100% HUMAN SCORE:
    - Rewrite the 'Factual Summary' into a unique, sophisticated, and engaging report (minimum 400 words).
    - DO NOT mention news sources (Sky, BBC, etc).
    - Use a mix of short, punchy sentences and long, complex analytical ones (High Perplexity & Burstiness).
    - Use colloquialisms common in football fan culture but keep a professional tone.
    - AVOID typical AI vocabulary: 'delve', 'tapestry', 'testament', 'unleash', 'overall', 'landscape', 'in summary', 'furthermore'.
    - DO NOT use an 'Introduction' or 'Conclusion' header. Start right with the analysis.
    - Focus strictly on European Football: Premier League, La Liga, Serie A, Ligue 1, Bundesliga, Champions League, Europa League, Conference League and their transfers.
    - ABSOLUTELY EXCLUDE American sports (NFL, NBA, MLB, NHL) or MLS.
    - ABSOLUTELY NO HALLUCINATIONS.

    CATEGORY SELECTION:
    - Categorize strictly into one of: ($cat_list).

    Return ONLY a valid JSON object with:
    - 'category': The chosen category.
    - 'content': The rewritten report (Markdown).
    - 'tags': 6-10 SEO tags.
    - 'meta_title': SEO title (max 60 chars).
    - 'meta_description': SEO description (max 160 chars).
    - 'meta_keywords': keywords.

    No other text.";

    $raw_content = get_ai_insight($content_prompt);
    if (!$raw_content || strpos($raw_content, 'AI Error:') === 0) {
        echo "Error: Content generation failed for this item. AI Response: " . ($raw_content ?: 'Empty') . ". Skipping.\n";
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

    $image_sources = [];
    if (!empty($item['image_url'])) $image_sources[] = $item['image_url'];

    $image_sources[] = "https://tse1.mm.bing.net/th?q=" . $specific_keyword . "&w=1200&h=800&c=7&rs=1&p=0&dpr=1&pid=Api";
    $image_sources[] = "https://tse1.mm.bing.net/th?q=" . urlencode($item['title'] . " sports photography") . "&w=1200&h=800&c=7&rs=1&p=0&dpr=1&pid=Api";
    $image_sources[] = "https://loremflickr.com/1200/800/" . urlencode(str_replace(' ', ',', $item['image_keyword'])) . "/all?lock=" . rand(1, 99999);
    $image_sources[] = "https://tse1.mm.bing.net/th?q=" . $category_keyword . "&w=1200&h=800&c=7&rs=1&p=0&dpr=1&pid=Api";

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

    $stmt = $conn->prepare("INSERT INTO posts (title, slug, excerpt, content, category, author, image, source_url, is_top_story, publish_date, tags, meta_title, meta_description, meta_keywords) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1, ?, ?, ?, ?, ?)");
    if ($stmt->execute([$title, $slug, $excerpt, $content, $category, $author, $db_img_path, $source_url, $publish_date, $tags, $meta_title, $meta_desc, $meta_keys])) {
        $post_id = $conn->lastInsertId();
        echo "Successfully published: $title\n";

        echo "Broadcasting to social media...\n";
        broadcast_to_social($post_id);

        $published_post_ids[] = $post_id;

        $count++;
    } else {
        echo "Database error.\n";
    }
}

echo "\nAI Automation complete. $count posts published.\n";

// 5. Notify Subscribers
if ($count > 0) {
    echo "Notifying subscribers via centralized system...\n";
    notify_subscribers($published_post_ids);
}