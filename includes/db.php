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

        // Auto-migration for missing columns
        try {
            $conn->query("SELECT pin_enabled FROM site_settings LIMIT 1");
        } catch (Exception $e) {
            $conn->exec("ALTER TABLE site_settings ADD COLUMN pin_enabled BOOLEAN DEFAULT FALSE");
            $conn->exec("ALTER TABLE site_settings ADD COLUMN admin_pin VARCHAR(255)");
        }

        return $conn;
    } catch (PDOException $e) {
        error_log("Connection failed: " . $e->getMessage());
        return null;
    }
}
?>
