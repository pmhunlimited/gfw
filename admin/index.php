<?php
session_start();
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

// Admin Routing
$request = $_SERVER['REQUEST_URI'];
$path = strtok($request, '?');
if (strpos($path, '/admin') === 0) {
    $path = substr($path, 6);
}

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
} elseif ($path == '/categories') {
    include __DIR__ . '/categories.php';
} elseif ($path == '/profile') {
    include __DIR__ . '/profile.php';
} elseif ($path == '/pin_verify') {
    include __DIR__ . '/pin_verify.php';
} elseif ($path == '/logout') {
    session_destroy();
    redirect('/admin/login');
} else {
    redirect('/admin/');
}
?>
