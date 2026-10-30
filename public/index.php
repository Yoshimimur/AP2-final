<?php
declare(strict_types=1);

session_start();
require __DIR__ . '/../src/autoload.php';

// Angefragten Pfad ermitteln, z. B. "login" oder "agenda-event1/a8f3c91d"
$path = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');

if ($path === '' || $path === 'login') {
    (new AuthController())->showLogin();
} elseif (preg_match('#^[a-z0-9-]+/[a-f0-9]{8}$#', $path)) {
    // Öffentliche QR-Adresse: Namensteil 
    (new PublicController())->show($path);
} else {
    http_response_code(404);
    echo 'Seite nicht gefunden.';
}