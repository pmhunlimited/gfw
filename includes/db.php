<?php
if (!file_exists(__DIR__ . '/config.php')) {
    // If config doesn't exist, we might be in middle of installation or something is wrong
    return null;
}

require_once __DIR__ . '/config.php';

function get_db_connection() {
    try {
        $conn = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        return $conn;
    } catch (PDOException $e) {
        error_log("Connection failed: " . $e->getMessage());
        return null;
    }
}
?>
