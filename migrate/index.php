<?php
if (!file_exists(__DIR__ . '/includes/config.php')) {
    // Determine the base path to redirect to installer
    $base = strpos($_SERVER['REQUEST_URI'], '/migrate') === 0 ? '/migrate' : '';
    header("Location: $base/install/");
    exit;
}

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';

// Simple Router
$request = $_SERVER['REQUEST_URI'];
$path = strtok($request, '?');

// Handle optional /migrate prefix in path for routing
$path = preg_replace('/^\/migrate/', '', $path);

if ($path == '/' || $path == '' || empty($path)) {
    include __DIR__ . '/pages/home.php';
} elseif (preg_match('/^\/post\/([^\/]+)$/', $path, $matches)) {
    $_GET['slug'] = $matches[1];
    include __DIR__ . '/pages/post_detail.php';
} elseif ($path == '/watch') {
    include __DIR__ . '/pages/watch.php';
} elseif ($path == '/betting') {
    include __DIR__ . '/pages/betting.php';
} elseif ($path == '/tables' || $path == '/standings') {
    include __DIR__ . '/pages/tables.php';
} elseif (preg_match('/^\/category\/([^\/]+)$/', $path, $matches)) {
    $_GET['category'] = $matches[1];
    include __DIR__ . '/pages/home.php';
} elseif ($path == '/subscribe' && $_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $conn = get_db_connection();
        $stmt = $conn->prepare("INSERT IGNORE INTO subscribers (email) VALUES (?)");
        $stmt->execute([$email]);
        header('Location: /?subscribed=true');
        exit;
    } else {
        header('Location: /?error=invalid_email');
        exit;
    }
} elseif ($path == '/admin/login') {
    include __DIR__ . '/admin/login.php';
} elseif (strpos($path, '/admin') === 0) {
    include __DIR__ . '/admin/index.php';
} elseif ($path == '/install' || strpos($path, '/install/') === 0) {
    include __DIR__ . '/install/index.php';
} else {
    // Check if it's a CMS page slug
    $conn = get_db_connection();
    if ($conn) {
        $stmt = $conn->prepare("SELECT * FROM pages WHERE slug = ? AND is_visible = 1");
        $stmt->execute([trim($path, '/')]);
        $page = $stmt->fetch();
        if ($page) {
            $_GET['page_id'] = $page['id'];
            include __DIR__ . '/pages/cms_page.php';
        } else {
            http_response_code(404);
            include __DIR__ . '/pages/404.php';
        }
    } else {
        http_response_code(404);
        include __DIR__ . '/pages/404.php';
    }
}
?>
