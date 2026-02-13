<?php
if (file_exists(__DIR__ . '/migrate/includes/config.php')) {
    include __DIR__ . '/migrate/includes/config.php';
    if (defined('INSTALLED') && INSTALLED) {
        header('Location: /migrate/');
        exit;
    }
}

header('Location: /migrate/install/');
exit;
?>
