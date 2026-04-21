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
$available_categories = ['Football News', 'Transfer News'];
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
        // No hard cap, we filter duplicates and then process top ones
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

// 1.1 Pre-filtering Stage: Remove stories that already exist in the database to save AI tokens
echo "Stage 1.1: Pre-filtering existing stories from database...\n";
$filtered_discovery = [];
foreach ($discovered_items as $item) {
    $title = trim($item['title']);
    $safe_title = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $title)));
    $source_url = $item['source_link'] ?? '';
    $title_prefix = substr($title, 0, 20) . '%'; // Slightly shorter prefix for better matching

    // Robust check: exact title, similar slug, same source URL, or similar title start
    $check_stmt = $conn->prepare("SELECT id FROM posts WHERE title = ? OR slug LIKE ? OR (source_url != '' AND source_url = ?) OR title LIKE ? OR excerpt LIKE ?");
    $check_stmt->execute([
        $title,
        $safe_title . '%',
        $source_url,
        $title_prefix,
        '%' . substr($title, 0, 30) . '%'
    ]);

    if ($check_stmt->fetch()) {
        echo "Pre-filtering existing or duplicate post: " . $title . "\n";
        continue;
    }
    $filtered_discovery[] = $item;
}
$discovered_items = $filtered_discovery;

if (empty($discovered_items)) {
    die("Intelligence status: All discovered news are already published. No new reports to generate.\n");
}

// 1.2 Streamlined League Filtering: Use AI to prune stories not related to target leagues
echo "Stage 1.2: Streamlining discovery to target leagues and transfers...\n";
$headlines_for_filter = "";
foreach ($discovered_items as $idx => $item) {
    $headlines_for_filter .= "$idx: {$item['title']}\n";
}

$filter_prompt = "I have a list of sports headlines. I only want news related to these specific European football leagues and their transfers:
- Premier League (England)
- Spanish League / La Liga (Spain)
- Champions League (UEFA)
- Europa League (UEFA)
- Conference League (UEFA)
- French League / Ligue 1 (France)
- Italian League / Serie A (Italy)
- Transfer News for any of the above.

STRICT RULE: There must be NO American sports news (NFL, NBA, MLB, NHL, MLS, etc.).
Identify the headlines that are STRICTLY about the European football leagues listed above or their players/managers/transfers.
Return a JSON array of the indices (integers) that I should keep.

HEADLINES:
$headlines_for_filter

Return ONLY a valid JSON array of integers. Example: [0, 1, 4]";

$raw_filter = get_ai_insight($filter_prompt);
$football_indices = extract_json($raw_filter, true);

if (is_array($football_indices)) {
    $filtered_items = [];
    foreach ($football_indices as $idx) {
        if (isset($discovered_items[$idx])) {
            $filtered_items[] = $discovered_items[$idx];
        }
    }
    $discovered_items = $filtered_items;
    echo "Football filter complete. " . count($discovered_items) . " football stories identified.\n";
}

if (empty($discovered_items)) {
    die("Intelligence status: No new football-related reports discovered in this cycle.\n");
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
$published_post_ids = [];
foreach ($discovered_items as $item) {
    $loop_idx++;
    if ($count >= 10) break;


    echo "\n--- Processing Story $loop_idx: " . $item['title'] . " ---\n";

    // Stage 2: Content Generation for this specific story
    echo "Stage 2: Factual Rewriting and SEO metadata generation...\n";
    $target_cat = $item['category'];
    $content_prompt = "Act as an Expert Football Columnist with a gritty, conversational blog style. Your absolute priority is to produce 100% HUMAN-LIKE content that passes all AI detection checks (Quillbot, Originality.ai, GPTZero) with a 0% AI score.

    SOURCE DATA:
    Headline: '{$item['title']}'
    Factual Summary: '{$item['description']}'

    STRICT HUMANIZATION PROTOCOL (FOR 0% AI DETECTION):
    1. PERPLEXITY & BURSTINESS: Vary sentence length aggressively. Mix 3-word punchy sentences with long, rambling (but grammatically correct) thoughts. AI is too consistent; humans are chaotic.
    2. IRREGULAR STRUCTURE: Avoid the 'In a surprising turn of events' AI intro. Start with a hot take: 'I've seen enough.' or 'This is getting ridiculous now.'
    3. COLLOQUIALISM: Use heavy fan-slang: 'absolute bottle jobs', 'tinpot', 'scenes', 'proper player', 'finished', 'washed', 'cooking'.
    4. EXTENDED BANNED LIST (CRITICAL): delve, tapestry, testament, unleash, elevate, pivotal, comprehensive, game-changer, multifaceted, underscores, vibrant, embarking, looking ahead, in conclusion, furthermore, moreover, additionally, notably, transform, enhance, unlock, seamless, meticulously, navigate, foster, bridge, empower, essential, vital, key, overall, finally.
    5. ACTIVE PERSONA: Use first-person ('I think', 'We all saw') and second-person ('You know how it is'). Use contractions (it's, won't, couldn't) 100% of the time.
    6. ANTI-GPT PATTERNS: Do not use bullet points or numbered lists unless absolutely necessary. Do not summarize at the end. Just stop when the point is made.
    7. NO ECHOING: Never reuse more than 3 consecutive words from the source data.

    CORE RULES:
    - Minimum 350 words of high-quality, unique commentary.
    - ABSOLUTELY NO FICTION. Keep every single fact provided, but change every single word used to describe it.
    - NEWS MUST BE RECENT (Within the last 30 minutes).
    - DO NOT mention news source names (BBC, ESPN, etc.).
    - Use 3-4 detailed paragraphs in Markdown for formatting.
    - Determine the category ONLY from: ($cat_list).
    - CATEGORIZATION: Transfers/Rumors -> 'Transfer News'. Others -> 'Football News'.

    JSON Requirements:
    - 'title': A catchy, 100% human-sounding headline (do not use source title).
    - 'category': 'Football News' or 'Transfer News'.
    - 'content': The human-style report (Markdown).
    - 'tags': 6-10 SEO tags.
    - 'meta_title': SEO title (max 60 chars).
    - 'meta_description': Compelling description (max 160 chars).
    - 'meta_keywords': High ranking keywords.

    Return ONLY a valid JSON object.";

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
    $title = sanitize($content_data['title'] ?? $item['title']);
    $safe_title = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $title)));
    $slug = $safe_title . '-' . time();
    $content = $content_data['content'];
    $excerpt = sanitize(substr(strip_tags($content), 0, 150)) . '...';
    $category = $content_data['category'] ?? $item['category'];
    $author = $settings['name'] ?? 'GFW';
    $source_url = $item['source_link'] ?? '';

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
if (!empty($published_post_ids)) {
    echo "Notifying subscribers...\n";
    notify_subscribers($published_post_ids);
    echo "Notification process complete.\n";
}
?>
