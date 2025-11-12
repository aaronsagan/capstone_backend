<?php

/**
 * Laravel - A PHP Framework For Web Artisans
 *
 * Custom router script for PHP's built-in server.
 * This handles CORS headers for storage files before serving them.
 */

$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));

// CORS Configuration
$allowedOrigins = [
    'http://localhost:8080',
    'http://127.0.0.1:8080',
    'http://localhost:8081',
    'http://127.0.0.1:8081',
    'http://localhost:3000',
    'http://127.0.0.1:3000',
    'http://localhost:5173',
    'http://127.0.0.1:5173',
];

$origin = $_SERVER['HTTP_ORIGIN'] ?? '';

// Add CORS headers for storage files
if (strpos($uri, '/storage/') === 0) {
    if (in_array($origin, $allowedOrigins, true)) {
        header("Access-Control-Allow-Origin: $origin");
        header('Access-Control-Allow-Credentials: true');
    }
    header('Access-Control-Allow-Methods: GET, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, Accept, Origin');
    header('Cross-Origin-Resource-Policy: cross-origin');
    header('Vary: Origin');

    // Handle preflight OPTIONS request
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        header('Access-Control-Max-Age: 86400');
        http_response_code(204);
        exit(0);
    }

    // Serve the file if it exists
    $filePath = __DIR__ . '/public' . $uri;
    if (file_exists($filePath) && is_file($filePath)) {
        $mimeType = mime_content_type($filePath);
        header('Content-Type: ' . $mimeType);
        readfile($filePath);
        exit(0);
    }
}

// For all other requests, pass to Laravel
if ($uri !== '/' && file_exists(__DIR__ . '/public' . $uri)) {
    return false; // Serve the static file
}

require_once __DIR__ . '/public/index.php';
