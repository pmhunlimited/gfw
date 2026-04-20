<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../includes/functions.php';

// Accurate Path Detection for Admin (Handles root or subfolder installs)
$request_uri = $_SERVER['REQUEST_URI'];
$base_path = strtok($request_uri, '?');

if (preg_match('/^(.*\/admin)(\/.*|$)/', $base_path, $matches)) {
    $admin_base = $matches[1];
    $path = $matches[2];
} else {
    // Fallback if regex fails (should not happen if routed correctly)
    $admin_base = (defined('SITE_URL') ? parse_url(SITE_URL, PHP_URL_PATH) : '') ?: '';
    $admin_base = rtrim($admin_base, '/') . '/admin';
    $path = str_replace($admin_base, '', $base_path);
}

$path = '/' . trim($path, '/');

if (!is_admin() && $path !== '/login') {
    redirect($admin_base . '/login');
    exit;
}

$conn = get_db_connection();
$settings = get_settings();

// Security PIN enforcement - ONLY for authenticated admins
if (is_admin() && !empty($settings['pin_enabled'])) {
    if (!isset($_SESSION['pin_verified']) || $_SESSION['pin_verified'] !== true ||
        (time() - ($_SESSION['pin_verified_at'] ?? 0)) > 86400) {

        // Don't redirect if already on pin_verify page
        if ($path !== '/pin_verify') {
            redirect($admin_base . '/pin_verify');
            exit;
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
} elseif ($path == '/social_connect') {
    include __DIR__ . '/social_connect.php';
} elseif ($path == '/social_callback') {
    include __DIR__ . '/social_callback.php';
} elseif ($path == '/logout') {
    session_destroy();
    redirect($admin_base . '/login');
    exit;
} else {
    redirect($admin_base . '/');
    exit;
}
?>
