<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

$conn = get_db_connection();
if ($conn) {
    $title = "Privacy Policy";
    $slug = "privacy-policy";
    $content = "# Privacy Policy\n\nYour privacy is important to us. This privacy policy explains how we collect, use, and protect your personal information when you use our website.\n\n## 1. Information We Collect\nWe may collect personal information such as your name, email address, and IP address when you interact with our site.\n\n## 2. How We Use Your Information\nWe use your information to provide and improve our services, communicate with you, and ensure the security of our network.\n\n## 3. Cookies\nWe use cookies to enhance your experience and analyze our traffic.\n\n## 4. Security\nWe implement robust security measures to protect your data from unauthorized access.\n\n## 5. Contact Us\nIf you have any questions about this policy, please contact us at " . ($settings['admin_email'] ?? 'admin@gfw.com') . ".";

    $stmt = $conn->prepare("INSERT IGNORE INTO pages (title, slug, content, is_visible, position) VALUES (?, ?, ?, 1, 'main')");
    $stmt->execute([$title, $slug, $content]);
    echo "Privacy Policy page created successfully.";
} else {
    echo "Database connection failed.";
}
?>
