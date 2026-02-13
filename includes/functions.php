<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/social_poster.php';

function get_settings() {
    $conn = get_db_connection();
    if (!$conn) return [];
    $stmt = $conn->query("SELECT * FROM site_settings WHERE id = 1");
    return $stmt->fetch() ?: [];
}

function get_categories_with_counts() {
    $conn = get_db_connection();
    if (!$conn) return [];
    $stmt = $conn->query("SELECT c.id, c.name, COUNT(p.id) as post_count
                          FROM categories c
                          LEFT JOIN posts p ON c.name = p.category
                          GROUP BY c.id, c.name
                          ORDER BY c.name ASC");
    return $stmt->fetchAll();
}

function sanitize($data) {
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

    try {
        $socket = fsockopen($host, $port, $errno, $errstr, 30);
        if (!$socket) throw new Exception("Could not connect to SMTP host: $errstr ($errno)");

        $getResponse = function($socket) {
            $response = "";
            while ($line = fgets($socket, 515)) {
                $response .= $line;
                if (substr($line, 3, 1) == " ") break;
            }
            return $response;
        };

        $getResponse($socket);
        fwrite($socket, "EHLO " . $_SERVER['HTTP_HOST'] . "\r\n");
        $getResponse($socket);

        // Try STARTTLS if on 587
        if ($port == 587) {
            fwrite($socket, "STARTTLS\r\n");
            $getResponse($socket);
            stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
            fwrite($socket, "EHLO " . $_SERVER['HTTP_HOST'] . "\r\n");
            $getResponse($socket);
        }

        if (!empty($user) && !empty($pass)) {
            fwrite($socket, "AUTH LOGIN\r\n");
            $getResponse($socket);
            fwrite($socket, base64_encode($user) . "\r\n");
            $getResponse($socket);
            fwrite($socket, base64_encode($pass) . "\r\n");
            $getResponse($socket);
        }

        fwrite($socket, "MAIL FROM: <$from>\r\n");
        $getResponse($socket);
        fwrite($socket, "RCPT TO: <$to>\r\n");
        $getResponse($socket);
        fwrite($socket, "DATA\r\n");
        $getResponse($socket);

        $headers = "To: $to\r\n";
        $headers .= "From: $name <$from>\r\n";
        $headers .= "Subject: $subject\r\n";
        $headers .= "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
        $headers .= "\r\n";

        fwrite($socket, $headers . $message . "\r\n.\r\n");
        $getResponse($socket);
        fwrite($socket, "QUIT\r\n");
        fclose($socket);
        return true;
    } catch (Exception $e) {
        error_log("SMTP Error: " . $e->getMessage());
        return false;
    }
}

function log_activity($message) {
    $settings = get_settings();
    if (!empty($settings['admin_email'])) {
        send_mail($settings['admin_email'], "GFW System Alert", $message);
    }
}

function get_ai_insight($prompt) {
    $settings = get_settings();
    $model = $settings['selected_model'];

    if (strpos($model, 'gemini') !== false) {
        $apiKey = $settings['gemini_api_key'];
        if (empty($apiKey)) return "Gemini API Key missing.";
        $url = "https://generativelanguage.googleapis.com/v1beta/models/$model:generateContent?key=$apiKey";
        $data = ["contents" => [["parts" => [["text" => $prompt]]]]];
        $headers = ['Content-Type: application/json'];
    } else {
        $apiKey = $settings['deepseek_api_key'];
        if (empty($apiKey)) return "DeepSeek API Key missing.";
        $url = "https://api.deepseek.com/chat/completions";
        $data = [
            "model" => $model,
            "messages" => [["role" => "user", "content" => $prompt]]
        ];
        $headers = [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $apiKey
        ];
    }

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, JSON_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    $response = curl_exec($ch);
    $result = JSON_decode($response, true);
    curl_close($ch);

    if (strpos($model, 'gemini') !== false) {
        if (isset($result['candidates'][0]['content']['parts'][0]['text'])) {
            return $result['candidates'][0]['content']['parts'][0]['text'];
        }
        if (isset($result['error'])) {
            return "Gemini Error: " . ($result['error']['message'] ?? 'Unknown');
        }
    } else {
        if (isset($result['choices'][0]['message']['content'])) {
            return $result['choices'][0]['message']['content'];
        }
        if (isset($result['error'])) {
            return "DeepSeek Error: " . ($result['error']['message'] ?? 'Unknown');
        }
    }

    return "Intelligence gathering failed. Response: " . substr($response, 0, 100);
}

function get_suggested_topics() {
    $today = date('D d M Y');
    $prompt = "Suggest 5 trending football news subjects/headlines for today, $today. Return them as a JSON array of strings ONLY. Example format: [\"Subject 1\", \"Subject 2\"]. Be very specific about current teams, transfers and players.";
    $raw = get_ai_insight($prompt);

    // Attempt to extract JSON array
    if (preg_match('/\[.*\]/s', $raw, $matches)) {
        $topics = json_decode($matches[0], true);
        if (is_array($topics)) return array_slice($topics, 0, 5);
    }

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
?>
