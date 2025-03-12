<?php
define('BASE_DIR', __DIR__ . '/../..');
define('PUBLIC_DIR', BASE_DIR . '/public');
define('INCLUDES_DIR', BASE_DIR . '/includes');
define('MODULES_DIR', BASE_DIR . '/public/modules');
define('VENDOR_DIR', BASE_DIR . '/vendor');
define('ASSETS_PATH', '/assets');
define('CONFIG_PATH', INCLUDES_DIR . '/config');
define('BASE_URL', (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" . $_SERVER['HTTP_HOST']);
