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

// Fetch current categories from DB
$available_categories = $conn->query("SELECT name FROM categories")->fetchAll(PDO::FETCH_COLUMN);
if (empty($available_categories)) {
    // Seed default categories if missing
    $defaults = ['PREMIER LEAGUE', 'CHAMPIONS LEAGUE', 'TRANSFER NEWS', 'LA LIGA', 'SERIE A', 'BUNDESLIGA', 'MATCH ANALYSIS', 'CRICKET', 'TENNIS', 'BASKETBALL', 'GOLF', 'MOTOR SPORT', 'OTHER SPORTS'];
    foreach ($defaults as $d) {
        $conn->prepare("INSERT IGNORE INTO categories (name) VALUES (?)")->execute([$d]);
    }
    $available_categories = $defaults;
}
$cat_list = implode(', ', $available_categories);

// 1. Discovery Stage: Fetch factual news from RSS Feeds
$today = date('D d M Y H:i');
echo "Stage 1: Discovering factual sports stories from RSS for $today...\n";

$rss_urls = [
    'https://www.skysports.com/rss/12433', // Sky Sports Home
    'https://www.espn.com/espn/rss/news', // ESPN Top Headlines
    'https://supersport.com/rss/news', // SuperSport All News
    'https://sport.sky.ch/feed', // Sky Sport CH
    'https://feeds.bbci.co.uk/sport/rss.xml', // BBC Sport Home
    'https://www.goal.com/feeds/en/news' // Goal.com
];

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
} else {
    echo "RSS Discovery yielded no results. Falling back to Tavily Search...\n";
    $tavily_query = "top breaking sports news headlines from skysports.com, espn.com, supersport.com, bbc.com/sport in the last 24 hours";
    $tavily_results = get_tavily_news($tavily_query);
    if ($tavily_results) {
        foreach ($tavily_results as $res) {
            $discovered_items[] = [
                'title' => $res['title'],
                'description' => $res['content'] ?? $res['title'],
                'source_link' => $res['url'],
                'category' => 'MATCH ANALYSIS',
                'image_keyword' => $res['title'],
                'image_url' => $res['image'] ?? null
            ];
        }
    }
}

if (empty($discovered_items)) {
    die("Error: News discovery failed. Please check your API keys.\n");
}

// 1.5 Deduplication Stage: Use AI to identify and remove redundant stories
echo "Stage 1.5: Filtering redundant stories via AI...\n";
$headlines_for_dedup = "";
foreach ($discovered_items as $idx => $item) {
    $headlines_for_dedup .= "$idx: {$item['title']}\n";
}

$dedup_prompt = "I have a list of sports news headlines from different sources. Some refer to the EXACT SAME match, transfer, or event.
Identify the unique events and return a JSON array of the indices (integers) that I should KEEP.
If multiple headlines refer to the same event, only keep the ONE index that has the most descriptive or complete headline.

HEADLINES:
$headlines_for_dedup

Return ONLY a valid JSON array of integers. Example: [0, 2, 5]";

$raw_dedup = get_ai_insight($dedup_prompt);
$unique_indices = extract_json($raw_dedup, true);

if (is_array($unique_indices) && !empty($unique_indices)) {
    $filtered_items = [];
    foreach ($unique_indices as $idx) {
        if (isset($discovered_items[$idx])) {
            $filtered_items[] = $discovered_items[$idx];
        }
    }
    $discovered_items = $filtered_items;
    echo "Deduplication complete. " . count($discovered_items) . " unique stories remaining.\n";
}

$date_path = date('Y/m/d');
$upload_dir = __DIR__ . "/../assets/uploads/news/" . $date_path . "/";
$web_dir = "/assets/uploads/news/" . $date_path . "/";
if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);

$count = 0;
$loop_idx = 0;
$fetched_hashes = [];
$published_posts = [];
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
    $content_prompt = "Act as a Factual Sports News Rewriter.

    SOURCE DATA:
    Headline: '{$item['title']}'
    Factual Summary: '{$item['description']}'

    STRICT GUIDELINES:
    - Rewrite the 'Factual Summary' into a unique, detailed, and engaging sports report (minimum 300 words).
    - ABSOLUTELY NO FICTION OR HALLUCINATIONS. Use ONLY the provided information.
    - NEWS MUST BE RECENT (Last 24 hours).
    - If your internal AI knowledge contradicts the 'Factual Summary' (e.g., about managers or player locations), IGNORE your internal knowledge and trust the Summary 100%.
    - DO NOT mention any news source names (e.g., Goal.com, BBC, ESPN, Sky Sports, etc.).
    - Use an engaging fan-blogger tone with 3-4 paragraphs. Use Markdown.
    - Determine the best category for this story from this list ONLY: ($cat_list).

    Requirements:
    - 'category': The chosen category (must be from the provided list).
    - 'content': The rewritten report (Markdown).
    - 'tags': 6-10 high-ranking SEO tags.
    - 'meta_title': SEO optimized title (max 60 chars).
    - 'meta_description': Compelling SEO description (max 160 chars).
    - 'meta_keywords': High ranking keywords.

    Return ONLY a valid JSON object. No other text.";

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
