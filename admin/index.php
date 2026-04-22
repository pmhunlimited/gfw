<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../includes/functions.php';

if (!is_admin()) {
    redirect('/admin/login');
}

$conn = get_db_connection();
$settings = get_settings();

// Security PIN enforcement
if (!empty($settings['pin_enabled'])) {
    if (!isset($_SESSION['pin_verified']) || $_SESSION['pin_verified'] !== true ||
        (time() - ($_SESSION['pin_verified_at'] ?? 0)) > 86400) {

        // Don't redirect if already on pin_verify page
        $request = $_SERVER['REQUEST_URI'];
        if (strpos($request, '/admin/pin_verify') === false) {
            redirect('/admin/pin_verify');
        }
    }
}

// Admin Routing & Normalization
$request = $_SERVER['REQUEST_URI'];
$base_path = rtrim(strtok($request, '?'), '/');

// Global admin base for URLs
$admin_base = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]/admin";

if (strpos($base_path, '/admin') === 0) {
    $path = substr($base_path, 6);
} else {
    $path = $base_path;
}

if (empty($path)) $path = '/';

// Layout helper
function admin_header($title = "Dashboard") {
    global $settings, $path, $admin_base;
    include __DIR__ . '/header.php';
}

function admin_footer() {
    include __DIR__ . '/footer.php';
}

// Router Logic
if ($path == '/' || $path == '' || $path == '/posts') {
    include __DIR__ . '/posts.php';
} elseif (strpos($path, '/settings') === 0) {
    include __DIR__ . '/settings.php';
} elseif (strpos($path, '/comments') === 0) {
    include __DIR__ . '/comments.php';
} elseif (strpos($path, '/subscribers') === 0) {
    include __DIR__ . '/subscribers.php';
} elseif (strpos($path, '/categories') === 0) {
    include __DIR__ . '/categories.php';
} elseif (strpos($path, '/pages') === 0) {
    include __DIR__ . '/pages.php';
} elseif (strpos($path, '/profile') === 0) {
    include __DIR__ . '/profile.php';
} elseif (strpos($path, '/pin_verify') === 0) {
    include __DIR__ . '/pin_verify.php';
} elseif (strpos($path, '/ajax_suggest') === 0) {
    include __DIR__ . '/ajax_suggest.php';
} elseif (strpos($path, '/logout') === 0) {
    session_destroy();
    redirect('/admin/login');
} else {
    // Fallback or specific file handling
    $file = __DIR__ . $path . '.php';
    if (file_exists($file)) {
        include $file;
    } else {
        redirect('/admin/');
    }
}
?>
