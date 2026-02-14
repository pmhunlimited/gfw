<?php
$path = strtok($_SERVER["REQUEST_URI"], '?');

// If file exists in root, serve it
if (file_exists(__DIR__ . $path) && !is_dir(__DIR__ . $path)) {
    return false;
}

// Check in migrate directory
$check_path = (strpos($path, '/migrate') === 0) ? $path : '/migrate' . $path;

if (is_dir(__DIR__ . $check_path)) {
    $index = rtrim(__DIR__ . $check_path, '/') . '/index.php';
    if (file_exists($index)) {
        $_SERVER['SCRIPT_NAME'] = (strpos($path, '/migrate') === 0 ? '' : '/migrate') . $path . (substr($path, -1) == '/' ? '' : '/') . 'index.php';
        require $index;
        return;
    }
}

if (file_exists(__DIR__ . $check_path) && !is_dir(__DIR__ . $check_path)) {
    $_SERVER['SCRIPT_NAME'] = (strpos($path, '/migrate') === 0 ? '' : '/migrate') . $path;
    return false;
}

// Otherwise, use the root index.php
require 'index.php';
