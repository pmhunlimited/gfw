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


        try {
            $conn->query("SELECT favicon FROM site_settings LIMIT 1");
        } catch (Exception $e) {
            $conn->exec("ALTER TABLE site_settings ADD COLUMN favicon VARCHAR(255)");
        }

        try {
            $conn->query("SELECT fb_access_token FROM site_settings LIMIT 1");
        } catch (Exception $e) {
            $conn->exec("ALTER TABLE site_settings ADD COLUMN fb_app_id VARCHAR(255)");
            $conn->exec("ALTER TABLE site_settings ADD COLUMN fb_app_secret VARCHAR(255)");
            $conn->exec("ALTER TABLE site_settings ADD COLUMN fb_page_id VARCHAR(255)");
            $conn->exec("ALTER TABLE site_settings ADD COLUMN fb_access_token TEXT");

            $conn->exec("ALTER TABLE site_settings ADD COLUMN tw_client_id VARCHAR(255)");
            $conn->exec("ALTER TABLE site_settings ADD COLUMN tw_client_secret VARCHAR(255)");
            $conn->exec("ALTER TABLE site_settings ADD COLUMN tw_api_key VARCHAR(255)");
            $conn->exec("ALTER TABLE site_settings ADD COLUMN tw_api_secret VARCHAR(255)");
            $conn->exec("ALTER TABLE site_settings ADD COLUMN tw_access_token VARCHAR(255)");
            $conn->exec("ALTER TABLE site_settings ADD COLUMN tw_access_secret VARCHAR(255)");

            $conn->exec("ALTER TABLE site_settings ADD COLUMN ig_account_id VARCHAR(255)");
            $conn->exec("ALTER TABLE site_settings ADD COLUMN ig_access_token TEXT");

            $conn->exec("ALTER TABLE site_settings ADD COLUMN tt_client_key VARCHAR(255)");
            $conn->exec("ALTER TABLE site_settings ADD COLUMN tt_client_secret VARCHAR(255)");
            $conn->exec("ALTER TABLE site_settings ADD COLUMN tt_access_token TEXT");
        }

        try {
            $conn->query("SELECT position FROM pages LIMIT 1");
        } catch (Exception $e) {
            $conn->exec("ALTER TABLE pages ADD COLUMN position ENUM('top', 'main', 'footer') DEFAULT 'main'");
            $conn->exec("ALTER TABLE pages ADD COLUMN meta_title VARCHAR(255)");
            $conn->exec("ALTER TABLE pages ADD COLUMN meta_description TEXT");
            $conn->exec("ALTER TABLE pages ADD COLUMN meta_keywords TEXT");
        }

        try {
            $conn->query("SELECT is_scheduled FROM posts LIMIT 1");
        } catch (Exception $e) {
            $conn->exec("ALTER TABLE posts ADD COLUMN is_scheduled BOOLEAN DEFAULT FALSE");
            $conn->exec("ALTER TABLE posts ADD COLUMN publish_date DATETIME");
        }


        return $conn;
    } catch (PDOException $e) {
        error_log("Connection failed: " . $e->getMessage());
        return null;
    }
}
?>
