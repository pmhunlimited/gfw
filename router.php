<?php
$path = strtok($_SERVER["REQUEST_URI"], '?');

// If file exists, serve it
if (file_exists(__DIR__ . $path) && !is_dir(__DIR__ . $path)) {
    return false;
}

// Handle directories with index.php
if (is_dir(__DIR__ . $path)) {
    $index = rtrim(__DIR__ . $path, '/') . '/index.php';
    if (file_exists($index)) {
        require $index;
        return;
    }
}

// Otherwise, use the root index.php
require 'index.php';
