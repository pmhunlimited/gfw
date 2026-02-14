<?php
if (!file_exists(__DIR__ . '/config.php')) {
    // If config doesn't exist, we might be in middle of installation or something is wrong
    return null;
}

require_once __DIR__ . '/config.php';

function get_db_connection() {
    try {
        if (defined('DB_TYPE') && DB_TYPE === 'sqlite') {
            $conn = new PDO("sqlite:" . __DIR__ . "/../database.sqlite");
        } else {
            try {
                $conn = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
            } catch (PDOException $e) {
                // Fallback to SQLite if MySQL fails in this environment
                $conn = new PDO("sqlite:" . __DIR__ . "/../database.sqlite");
            }
        }
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

        // SQLite doesn't support some MySQL syntax like ALTER TABLE ADD COLUMN IF NOT EXISTS easily
        // But we can try/catch
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

        try {
            $conn->query("SELECT is_top_story FROM posts LIMIT 1");
        } catch (Exception $e) {
            $conn->exec("ALTER TABLE posts ADD COLUMN is_top_story BOOLEAN DEFAULT FALSE");
        }

        // Auto-seed Privacy Policy if missing
        $check = $conn->query("SELECT id FROM pages WHERE slug = 'privacy-policy' LIMIT 1")->fetch();
        if (!$check) {
            $stmt = $conn->prepare("INSERT INTO pages (title, slug, content, is_visible, position) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute(['Privacy Policy', 'privacy-policy', "# Privacy Policy\n\nYour privacy is important to us. This privacy policy explains how we collect, use, and protect your personal information.", 1, 'main']);
        }


        return $conn;
    } catch (PDOException $e) {
        error_log("Connection failed: " . $e->getMessage());
        return null;
    }
}
?>
