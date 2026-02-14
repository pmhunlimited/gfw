<?php
/**
 * Root Redirector
 * Ensures that accessing the root directory starts the installer or redirects to the app.
 */

if (!file_exists(__DIR__ . '/migrate/includes/config.php')) {
    header('Location: /migrate/install/');
    exit;
}

header('Location: /migrate/');
exit;
?>
