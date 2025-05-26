<?php

/**
 * Static Asset Server
 *
 * This script serves static assets directly from the filesystem.
 * It's designed to work with the Nginx configuration that routes
 * asset requests to this script with a path query parameter.
 */

// Get the requested asset path from the query string
$assetPath = $_GET['path'] ?? '';
$fullPath = __DIR__ . $assetPath;

// Basic security check to prevent directory traversal
if (strpos($assetPath, '..') !== false || !file_exists($fullPath) || is_dir($fullPath)) {
    header('HTTP/1.1 404 Not Found');
    echo 'File not found: ' . htmlspecialchars($assetPath);
    exit;
}

// Get the file extension to determine content type
$extension = strtolower(pathinfo($fullPath, PATHINFO_EXTENSION));

// Map file extensions to content types
$contentTypes = [
    'css' => 'text/css',
    'js' => 'application/javascript',
    'json' => 'application/json',
    'jpg' => 'image/jpeg',
    'jpeg' => 'image/jpeg',
    'png' => 'image/png',
    'gif' => 'image/gif',
    'ico' => 'image/x-icon',
    'svg' => 'image/svg+xml',
    'woff' => 'font/woff',
    'woff2' => 'font/woff2',
    'ttf' => 'font/ttf',
    'eot' => 'application/vnd.ms-fontobject',
    'pdf' => 'application/pdf',
    'zip' => 'application/zip',
    'txt' => 'text/plain',
    'html' => 'text/html',
    'xml' => 'application/xml'
];

$contentType = $contentTypes[$extension] ?? 'application/octet-stream';

// Set appropriate headers
header("Content-Type: $contentType");
header('Content-Length: ' . filesize($fullPath));
header('Cache-Control: public, max-age=604800');
header('Expires: ' . gmdate('D, d M Y H:i:s', time() + 604800) . ' GMT');
header('Access-Control-Allow-Origin: *');

// Output the file
readfile($fullPath);
exit;
