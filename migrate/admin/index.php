<?php
session_start();
require_once __DIR__ . '/../includes/functions.php';

if (!is_admin()) {
    redirect('/migrate/admin/login');
}

$conn = get_db_connection();
$settings = get_settings();

// Admin Routing
$request = $_SERVER['REQUEST_URI'];
$base_path = '/migrate/admin';
$path = str_replace($base_path, '', $request);
$path = strtok($path, '?');

// Layout helper
function admin_header($title = "Dashboard") {
    global $settings, $path;
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
} elseif ($path == '/profile') {
    include __DIR__ . '/profile.php';
} elseif ($path == '/logout') {
    session_destroy();
    redirect('/migrate/admin/login');
} else {
    redirect('/migrate/admin/');
}
?>
