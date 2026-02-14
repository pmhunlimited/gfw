<?php
$path = strtok($_SERVER["REQUEST_URI"], '?');
if (file_exists(__DIR__ . $path)) {
    if (is_dir(__DIR__ . $path)) {
        if (file_exists(__DIR__ . $path . '/index.php')) {
            require __DIR__ . $path . '/index.php';
            return;
        }
    } else {
        return false;
    }
}
require 'index.php';
