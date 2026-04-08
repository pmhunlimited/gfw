<?php
session_start();
require_once __DIR__ . '/../includes/functions.php';

$request_uri = $_SERVER['REQUEST_URI'];
$base_path = strtok($request_uri, '?');
$path = '/';
if (strpos($base_path, '/admin') === 0) {
    $path = substr($base_path, 6);
}
$path = '/' . trim($path, '/');

if (!is_admin() && $path !== '/login') {
    $admin_base = (defined('SITE_URL') ? rtrim(SITE_URL, '/') : '') . '/admin';
    redirect($admin_base . '/login');
}

$conn = get_db_connection();
$settings = get_settings();

$admin_base = (defined('SITE_URL') ? rtrim(SITE_URL, '/') : '') . '/admin';

// Security PIN enforcement
if (!empty($settings['pin_enabled'])) {
    if (!isset($_SESSION['pin_verified']) || $_SESSION['pin_verified'] !== true ||
        (time() - ($_SESSION['pin_verified_at'] ?? 0)) > 86400) {

        // Don't redirect if already on pin_verify page
        if ($path !== '/pin_verify') {
            redirect($admin_base . '/pin_verify');
        }
    }
}

// Layout helper
function admin_header($title = "Dashboard") {
    global $settings, $path, $admin_base;
    include __DIR__ . '/header.php';
}

function admin_footer() {
    include __DIR__ . '/footer.php';
}

if ($path == '/' || $path == '') {
    include __DIR__ . '/posts.php';
} elseif ($path == '/settings') {
    include __DIR__ . '/settings.php';
} elseif ($path == '/comments') {
    include __DIR__ . '/comments.php';
} elseif ($path == '/subscribers') {
    include __DIR__ . '/subscribers.php';
} elseif ($path == '/categories') {
    include __DIR__ . '/categories.php';
} elseif ($path == '/pages') {
    include __DIR__ . '/pages.php';
} elseif ($path == '/profile') {
    include __DIR__ . '/profile.php';
} elseif ($path == '/pin_verify') {
    include __DIR__ . '/pin_verify.php';
} elseif ($path == '/login') {
    include __DIR__ . '/login.php';
} elseif ($path == '/ajax_suggest.php' || $path == '/ajax_suggest') {
    include __DIR__ . '/ajax_suggest.php';
} elseif ($path == '/logout') {
    session_destroy();
    redirect($admin_base . '/login');
} else {
    redirect($admin_base . '/');
}
?>
