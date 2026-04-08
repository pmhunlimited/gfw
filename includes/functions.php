<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/social_poster.php';

function get_settings() {
    static $settings = null;
    if ($settings !== null) return $settings;

    $conn = get_db_connection();
    if (!$conn) return [
        'name' => 'GLOBAL FOOTBALL WATCH',
        'logo' => '',
        'favicon' => ''
    ];
    $stmt = $conn->query("SELECT * FROM site_settings WHERE id = 1");
    $settings = $stmt->fetch();

    if ($settings && !array_key_exists('header_code', $settings)) {
        try {
            $conn->exec("ALTER TABLE site_settings ADD COLUMN header_code TEXT");
            $conn->exec("ALTER TABLE site_settings ADD COLUMN footer_code TEXT");
            // Refetch settings after migration
            $stmt = $conn->query("SELECT * FROM site_settings WHERE id = 1");
            $settings = $stmt->fetch();
        } catch (Exception $e) {
            error_log("Code injection migration failed: " . $e->getMessage());
        }
    }


    // Auto-migration for posts source_url and video_url
    try {
        $conn->query("SELECT source_url FROM posts LIMIT 1");
    } catch (Exception $e) {
        try {
            $conn->exec("ALTER TABLE posts ADD COLUMN source_url VARCHAR(255)");
        } catch (Exception $ex) {}
    }

    try {
        $conn->query("SELECT video_url FROM posts LIMIT 1");
    } catch (Exception $e) {
        try {
            $conn->exec("ALTER TABLE posts ADD COLUMN video_url VARCHAR(255)");
        } catch (Exception $ex) {}
    }

    // Auto-migration for categories slug
    try {
        $conn->query("SELECT slug FROM categories LIMIT 1");
    } catch (Exception $e) {
        try {
            $conn->exec("ALTER TABLE categories ADD COLUMN slug VARCHAR(100)");
            // Populate slugs for existing categories
            $all_cats = $conn->query("SELECT id, name FROM categories")->fetchAll();
            foreach ($all_cats as $c) {
                $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $c['name'])));
                $conn->prepare("UPDATE categories SET slug = ? WHERE id = ?")->execute([$slug, $c['id']]);
            }
        } catch (Exception $ex) {}
    }

    $settings = $settings ?: [
        'name' => 'GLOBAL FOOTBALL WATCH',
        'logo' => '',
        'favicon' => ''
    ];
    return $settings;
}

function get_categories_with_counts() {
    static $categories = null;
    if ($categories !== null) return $categories;

    $conn = get_db_connection();
    if (!$conn) return [
        ['name' => 'PREMIER LEAGUE', 'post_count' => 5],
        ['name' => 'TRANSFER NEWS', 'post_count' => 3],
        ['name' => 'MATCH ANALYSIS', 'post_count' => 8]
    ];
    $stmt = $conn->query("SELECT c.id, c.name, c.slug, COUNT(p.id) as post_count
                          FROM categories c
                          LEFT JOIN posts p ON c.name = p.category
                          GROUP BY c.id, c.name, c.slug
                          ORDER BY c.name ASC");
    $categories = $stmt->fetchAll();
    return $categories;
}

function sanitize($data) {
    if (is_array($data)) {
        $data = implode(', ', $data);
    }
    return htmlspecialchars(strip_tags(trim($data)));
}

function is_admin() {
    if (session_status() == PHP_SESSION_NONE) session_start();
    return isset($_SESSION['user_id']) && $_SESSION['role'] == 'admin';
}

function redirect($url) {
    header("Location: $url");
    exit;
}

function generate_csrf_token() {
    if (session_status() == PHP_SESSION_NONE) session_start();
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf_token($token) {
    if (session_status() == PHP_SESSION_NONE) session_start();
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// SMTP Mail Function
function send_mail($to, $subject, $message) {
    $settings = get_settings();
    if (empty($settings['smtp_host'])) {
        $headers = "From: " . ($settings['smtp_sender_name'] ?: 'GFW') . " <" . ($settings['smtp_sender_email'] ?: 'noreply@gfw.com') . ">\r\n";
        $headers .= "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
        return mail($to, $subject, $message, $headers);
    }

    $host = $settings['smtp_host'];
    $port = $settings['smtp_port'];
    $user = $settings['smtp_user'];
    $pass = $settings['smtp_pass'];
    $from = $settings['smtp_sender_email'];
    $name = $settings['smtp_sender_name'];

    // Prepend ssl:// for port 465 if no scheme is provided
    if ($port == 465 && strpos($host, '://') === false) {
        $host = "ssl://" . $host;
    }

    try {
        $socket = @fsockopen($host, $port, $errno, $errstr, 10);
        if (!$socket) throw new Exception("Could not connect to SMTP host: $errstr ($errno)");

        $getResponse = function($socket) {
            $response = "";
            stream_set_timeout($socket, 5);
            while ($line = @fgets($socket, 515)) {
                $response .= $line;
                if (substr($line, 3, 1) == " ") break;
                $info = stream_get_meta_data($socket);
                if ($info['timed_out']) throw new Exception("SMTP Response Timeout");
            }
            return $response;
        };

        $write = function($socket, $cmd) {
            if (@fwrite($socket, $cmd) === false) throw new Exception("Failed to write to SMTP socket");
        };

        $getResponse($socket);
        $write($socket, "EHLO " . ($_SERVER['HTTP_HOST'] ?? 'localhost') . "\r\n");
        $ehlo_resp = $getResponse($socket);

        // Try STARTTLS if on 587
        if ($port == 587 && strpos($ehlo_resp, 'STARTTLS') !== false) {
            $write($socket, "STARTTLS\r\n");
            $getResponse($socket);
            if (!@stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                throw new Exception("STARTTLS failed");
            }
            $write($socket, "EHLO " . ($_SERVER['HTTP_HOST'] ?? 'localhost') . "\r\n");
            $getResponse($socket);
        }

        if (!empty($user) && !empty($pass)) {
            $write($socket, "AUTH LOGIN\r\n");
            $getResponse($socket);
            $write($socket, base64_encode($user) . "\r\n");
            $getResponse($socket);
            $write($socket, base64_encode($pass) . "\r\n");
            $getResponse($socket);
        }

        $write($socket, "MAIL FROM: <$from>\r\n");
        $getResponse($socket);
        $write($socket, "RCPT TO: <$to>\r\n");
        $getResponse($socket);
        $write($socket, "DATA\r\n");
        $getResponse($socket);

        $headers = "To: $to\r\n";
        $headers .= "From: $name <$from>\r\n";
        $headers .= "Subject: $subject\r\n";
        $headers .= "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
        $headers .= "\r\n";

        $write($socket, $headers . $message . "\r\n.\r\n");
        $getResponse($socket);
        $write($socket, "QUIT\r\n");
        @fclose($socket);
        return true;
    } catch (Exception $e) {
        error_log("SMTP Error: " . $e->getMessage());
        return false;
    }
}

function render_email_template($content, $subtitle = 'Intelligence Protocol Active') {
    $settings = get_settings();
    $site_name = $settings['name'] ?? 'GLOBAL FOOTBALL WATCH';
    $year = date('Y');

    return "
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset='utf-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <style>
            body { margin: 0; padding: 0; background-color: #05070a; font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; }
            .wrapper { width: 100%; table-layout: fixed; background-color: #05070a; padding-bottom: 40px; }
            .main { background-color: #0a0e17; margin: 0 auto; width: 100%; max-width: 600px; border-spacing: 0; color: #ffffff; }
            .header { background-color: #000000; padding: 40px; text-align: center; border-bottom: 2px solid #ff3e3e; }
            .content { padding: 40px; }
            .news-item { margin-bottom: 40px; border-bottom: 1px solid rgba(255,255,255,0.05); padding-bottom: 30px; }
            .news-title { font-size: 24px; font-weight: bold; color: #ffffff; text-decoration: none; line-height: 1.3; text-transform: uppercase; font-style: italic; display: block; }
            .news-excerpt { font-size: 16px; color: #a0aec0; line-height: 1.6; margin: 15px 0; }
            .btn { display: inline-block; background-color: #ff3e3e; color: #ffffff; padding: 12px 25px; text-decoration: none; border-radius: 4px; font-weight: bold; font-size: 14px; text-transform: uppercase; letter-spacing: 1px; }
            .footer { padding: 30px; text-align: center; font-size: 12px; color: #4a5568; }
            p { margin: 0 0 20px; }
            a { color: #ff3e3e; text-decoration: none; }
        </style>
    </head>
    <body>
        <center class='wrapper'>
            <table class='main' width='100%'>
                <tr>
                    <td class='header'>
                        <h1 style='margin:0; color:#ffffff; letter-spacing:-1px; text-transform:uppercase; font-style:italic; font-size: 32px;'>$site_name</h1>
                        <p style='margin:10px 0 0; color:#ff3e3e; font-size:12px; font-weight:bold; text-transform:uppercase; letter-spacing:2px;'>$subtitle</p>
                    </td>
                </tr>
                <tr>
                    <td class='content'>
                        $content
                    </td>
                </tr>
                <tr>
                    <td class='footer'>
                        <p>&copy; $year $site_name. All Systems Secure.</p>
                        <p>This is an automated encrypted transmission from the core network.</p>
                    </td>
                </tr>
            </table>
        </center>
    </body>
    </html>";
}

function log_activity($message) {
    $settings = get_settings();
    if (!empty($settings['admin_email'])) {
        $html = render_email_template("<p>$message</p>", "Security Alert");
        send_mail($settings['admin_email'], "GFW System Alert", $html);
    }
}

function get_ai_insight($prompt) {
    $settings = get_settings();
    $model = $settings['selected_model'] ?? 'deepseek-chat';
    $apiKey = $settings['deepseek_api_key'] ?? '';

    // If model is legacy (Gemini/Groq), force to DeepSeek
    if (strpos($model, 'gemini') !== false || strpos($model, 'groq') !== false) {
        $model = 'deepseek-chat';
    }

    if (empty($apiKey)) return "DeepSeek API Key missing.";

    $url = "https://api.deepseek.com/chat/completions";
    $data = [
        "model" => $model,
        "messages" => [["role" => "user", "content" => $prompt]],
        "max_tokens" => 8192
    ];
    $headers = [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $apiKey
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_TIMEOUT, 180);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);

    $response = curl_exec($ch);
    $curl_err = curl_error($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $result = json_decode($response, true);
    curl_close($ch);

    if (isset($result['choices'][0]['message']['content'])) {
        return $result['choices'][0]['message']['content'];
    }

    if (isset($result['error'])) {
        return "AI Error: DeepSeek - " . ($result['error']['message'] ?? 'Unknown');
    }

    if (!empty($curl_err)) {
        return "AI Error: Connection failed - " . $curl_err;
    }

    return "AI Error: Intelligence gathering failed (HTTP $httpCode). Response: " . substr($response, 0, 100);
}

/**
 * Fetches and parses RSS feeds from multiple sources.
 * @param array $urls
 * @return array
 */
function get_rss_news($urls) {
    $all_items = [];
    $seen_links = [];

    foreach ($urls as $url) {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 20);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36');
        $xml_data = curl_exec($ch);
        curl_close($ch);

        if (!$xml_data) continue;

        try {
            // Suppress errors for malformed XML
            $xml = @simplexml_load_string($xml_data);
            if ($xml === false) continue;

            $items = $xml->xpath('//item');
            if (!$items) continue;

            foreach ($items as $item) {
                $link = (string)$item->link;
                if (in_array($link, $seen_links)) continue;

                $pubDate = (string)$item->pubDate;
                $timestamp = strtotime($pubDate);

                // Only within last 24 hours
                if ($timestamp > (time() - 86400)) {
                    $all_items[] = [
                        'title' => (string)$item->title,
                        'description' => strip_tags((string)$item->description),
                        'link' => $link,
                        'pubDate' => $pubDate,
                        'timestamp' => $timestamp,
                        'source' => parse_url($url, PHP_URL_HOST)
                    ];
                    $seen_links[] = $link;
                }
            }
        } catch (Exception $e) {
            error_log("RSS Parse Error ($url): " . $e->getMessage());
        }
    }

    // Sort by newest first
    usort($all_items, function($a, $b) {
        return $b['timestamp'] - $a['timestamp'];
    });

    return $all_items;
}


function get_suggested_topics() {
    $today = date('D d M Y');
    $prompt = "Suggest 5 trending football news subjects/headlines for today, $today. Return them as a JSON array of strings ONLY. Example format: [\"Subject 1\", \"Subject 2\"]. Be very specific about current teams, transfers and players.";
    $raw = get_ai_insight($prompt);

    $topics = extract_json($raw, true);
    if (is_array($topics)) return array_slice($topics, 0, 5);

    // Fallback: If no JSON array found, try to split by lines if it looks like a list
    $lines = explode("\n", $raw);
    $topics = [];
    foreach ($lines as $line) {
        $line = trim(preg_replace('/^\d+\.\s*/', '', $line)); // Remove numbering
        if (!empty($line) && strlen($line) > 10 && count($topics) < 5) {
            $topics[] = $line;
        }
    }

    return $topics;
}

/**
 * Robustly extracts JSON from a string that may contain markdown or other text.
 * @param string $raw
 * @param bool $as_array If true, expects a JSON array. If false, expects a JSON object.
 * @return mixed|null
 */
function extract_json($raw, $as_array = false) {
    $start_char = $as_array ? '[' : '{';
    $end_char = $as_array ? ']' : '}';

    $start_pos = strpos($raw, $start_char);
    $end_pos = strrpos($raw, $end_char);

    if ($start_pos === false || $end_pos === false || $end_pos < $start_pos) {
        return null;
    }

    $json_str = substr($raw, $start_pos, $end_pos - $start_pos + 1);

    // 1. Direct attempt
    $json = json_decode($json_str, true);
    if ($json !== null) return $json;

    // 2. Try to escape literal newlines inside strings
    $escaped = preg_replace_callback('/"([^"\\\\]|\\\\.)*"/', function($matches) {
        return str_replace(["\n", "\r"], ["\\n", "\\r"], $matches[0]);
    }, $json_str);
    $json = json_decode($escaped, true);
    if ($json !== null) return $json;

    // 3. Last resort: Clean all literal control characters
    $cleaned = preg_replace('/[\x00-\x1F\x7F]/', '', $json_str);
    $json = json_decode($cleaned, true);
    return $json;
}

/**
 * Robustly fetches an image using cURL and returns the data only if it is a valid image.
 * @param string $url
 * @return string|false
 */
function fetch_image($url) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36');

    $data = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
    curl_close($ch);

    if ($httpCode == 200 && strpos($contentType, 'image/') !== false) {
        return $data;
    }

    return false;
}

/**
 * Safely handles image uploads with extension validation and unique renaming.
 * @param array $file The $_FILES element
 * @param string $target_subpath Subdirectory in assets/
 * @return string|false Path to uploaded file relative to root, or false on failure.
 */
function upload_image($file, $target_subpath = 'uploads/') {
    if (empty($file['name']) || $file['error'] !== UPLOAD_ERR_OK) return false;

    $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'ico'];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

    if (!in_array($ext, $allowed)) return false;

    $target_dir = __DIR__ . "/../assets/" . $target_subpath;
    if (!is_dir($target_dir)) mkdir($target_dir, 0755, true);

    // Unique filename to prevent overwrites and hide original name
    $filename = bin2hex(random_bytes(8)) . "_" . time() . '.' . $ext;
    $target_file = $target_dir . $filename;

    if (move_uploaded_file($file['tmp_name'], $target_file)) {
        return "/assets/" . $target_subpath . $filename;
    }

    return false;
}

// Basic Markdown to HTML
function parse_markdown($text) {
    $text = htmlspecialchars($text);
    $text = preg_replace('/^# (.*$)/m', '<h2 class="h3 font-condensed fw-black text-electric-red mt-4 mb-3 uppercase italic">$1</h2>', $text);
    $text = preg_replace('/^## (.*$)/m', '<h3 class="h4 font-condensed fw-black text-white mt-4 mb-2 uppercase italic">$1</h3>', $text);
    $text = preg_replace('/\*\*(.*?)\*\*/', '<strong>$1</strong>', $text);

    // Simple table parser
    if (strpos($text, '|') !== false) {
        $lines = explode("\n", $text);
        $html = '';
        $inTable = false;
        foreach ($lines as $line) {
            if (trim($line) && strpos($line, '|') !== false) {
                $cells = array_filter(array_map('trim', explode('|', $line)));
                if (!$inTable) {
                    $html .= '<div class="table-responsive my-4"><table class="table table-dark table-hover mb-0">';
                    $html .= '<thead><tr>';
                    foreach ($cells as $c) $html .= "<th>$c</th>";
                    $html .= '</tr></thead><tbody>';
                    $inTable = true;
                } else {
                    if (strpos($line, '---') === false) {
                        $html .= '<tr>';
                        foreach ($cells as $c) $html .= "<td>$c</td>";
                        $html .= '</tr>';
                    }
                }
            } else {
                if ($inTable) { $html .= '</tbody></table></div>'; $inTable = false; }
                if (trim($line)) $html .= "<p>$line</p>";
            }
        }
        if ($inTable) $html .= '</tbody></table></div>';
        return $html;
    }
    return nl2br($text);
}

/**
 * Notifies all subscribers about new posts.
 * @param array $post_ids Array of post IDs
 */
function notify_subscribers($post_ids) {
    if (empty($post_ids)) return;

    $conn = get_db_connection();
    if (!$conn) return;

    $subscribers = $conn->query("SELECT email FROM subscribers")->fetchAll(PDO::FETCH_COLUMN);
    if (empty($subscribers)) return;

    $placeholders = implode(',', array_fill(0, count($post_ids), '?'));
    $stmt = $conn->prepare("SELECT title, slug, excerpt FROM posts WHERE id IN ($placeholders)");
    $stmt->execute($post_ids);
    $posts = $stmt->fetchAll();

    if (empty($posts)) return;

    $settings = get_settings();
    $site_url = defined('SITE_URL') ? rtrim(SITE_URL, '/') : '';

    $subject = "Intelligence Alert: New Reports Published - " . date('D d M Y');
    $content = "<p style='font-size:18px; color:#ff3e3e; font-weight:bold; margin-bottom:30px; text-transform:uppercase;'>New Intelligence Reports: ".date('d M Y')."</p>";

    foreach ($posts as $post) {
        $post_url = $site_url . "/post/" . $post['slug'];
        $content .= "
            <div class='news-item'>
                <a href='$post_url' class='news-title'>{$post['title']}</a>
                <p class='news-excerpt'>{$post['excerpt']}</p>
                <a href='$post_url' class='btn'>Decrypt Full Report</a>
            </div>
        ";
    }

    $message = render_email_template($content, "Intelligence Alert");

    foreach ($subscribers as $email) {
        send_mail($email, $subject, $message);
    }
}

/**
 * Returns the list of active RSS feed URLs used for news discovery.
 * @return array
 */
function get_rss_feed_urls() {
    return [
        'https://www.skysports.com/rss/12433', // Sky Sports Home
        'https://www.espn.com/espn/rss/news', // ESPN Top Headlines
        'https://supersport.com/rss/news', // SuperSport All News
        'https://sport.sky.ch/feed', // Sky Sport CH
        'https://feeds.bbci.co.uk/sport/rss.xml' // BBC Sport Home
    ];
}
?>
