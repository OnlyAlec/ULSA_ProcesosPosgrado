<?php

define('BASE_DIR', $_SERVER['DOCUMENT_ROOT'] . '/..');
define('PUBLIC_DIR', BASE_DIR . '/public');
define('INCLUDES_DIR', BASE_DIR . '/includes');
define('VENDOR_DIR', BASE_DIR . '/vendor');
define('MODULES_DIR', BASE_DIR . '/public/modules');
define('XLSX_DIR', BASE_DIR . '/public/assets/xlsx');
define('PDF_DIR', BASE_DIR . '/public/assets/pdf');
define('GRAPHS_DIR', BASE_DIR . '/public/assets/graphs');
define('EMAIL_TEMPLATES_DIR', INCLUDES_DIR . '/templates/emails');

define('ASSETS_PATH', '/assets');
define('CONFIG_PATH', INCLUDES_DIR . '/config');

define('BASE_URL', (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" . $_SERVER['HTTP_HOST']);

const EMAIL_NAME_SENDER = 'Development OA';
const LIMIT_LISTS = 50;
const LIMIT_CONTACTS = 500;
