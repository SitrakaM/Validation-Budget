<?php

$requestedPath = rawurldecode(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/');
$publicPath = realpath(__DIR__);
$filePath = realpath(__DIR__ . $requestedPath);

if (
    $filePath !== false
    && $publicPath !== false
    && str_starts_with($filePath, $publicPath . DIRECTORY_SEPARATOR)
    && is_file($filePath)
) {
    return false;
}

require __DIR__ . '/index.php';