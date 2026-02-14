<?php
/**
 * GFW Root Entry Point
 * Migrated to PHP - Maintained in /migrate directory
 */

$migrate_dir = __DIR__ . '/migrate';

if (!file_exists($migrate_dir . '/includes/config.php')) {
    header("Location: /migrate/install/");
    exit;
}

require_once $migrate_dir . '/includes/config.php';

if (defined('INSTALLED') && !INSTALLED) {
    header("Location: /migrate/install/");
    exit;
}

// Load the application from the migrate directory
require_once $migrate_dir . '/index.php';
?>
