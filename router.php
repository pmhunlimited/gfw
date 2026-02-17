<?php
// GFW Development Router
$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));

if ($uri !== '/' && file_exists(__DIR__ . $uri)) {
    return false;
}

// Admin Routing
if (strpos($uri, '/admin') === 0) {
    include __DIR__ . '/admin/index.php';
    return;
}

// Category Routing
if (preg_match('/^\/category\/(.+)$/', $uri, $matches)) {
    $_GET['category'] = $matches[1];
    include __DIR__ . '/pages/home.php';
    return;
}

// Post Routing
if (preg_match('/^\/post\/(.+)$/', $uri, $matches)) {
    $_GET['slug'] = $matches[1];
    include __DIR__ . '/pages/post_detail.php';
    return;
}

// Static Pages Routing (watch, tables, standings, etc.)
$static_pages = ['watch', 'tables', 'standings', 'privacy-policy'];
foreach ($static_pages as $page) {
    if ($uri === '/' . $page) {
        include __DIR__ . '/pages/home.php'; // These are often handled within home.php or similar
        // Actually, let's check if the file exists in pages/
        if (file_exists(__DIR__ . '/pages/' . $page . '.php')) {
            include __DIR__ . '/pages/' . $page . '.php';
            return;
        }
    }
}

// Check for dynamic pages in database
if ($uri !== '/') {
    $slug = ltrim($uri, '/');
    $conn = get_db_connection();
    $stmt = $conn->prepare("SELECT id FROM pages WHERE slug = ? AND is_visible = 1 LIMIT 1");
    $stmt->execute([$slug]);
    if ($stmt->fetch()) {
        $_GET['page'] = $slug;
        include __DIR__ . '/pages/home.php'; // or a dedicated page handler
        return;
    }
}

// Default to Home
include __DIR__ . '/pages/home.php';
