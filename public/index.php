<?php
declare(strict_types=1);

// Lädt die Bibliotheken und startet eine geschützte Sitzung
require __DIR__ . '/../vendor/autoload.php';
session_name('qr_docmanager');
session_start([
    'use_strict_mode' => true,
    'cookie_httponly' => true,
    'cookie_secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    'cookie_samesite' => 'Lax',
]);

// Slim verarbeitet die Anfrage und sendet die Antwort
$app = require __DIR__ . '/../src/app.php';
$app->run();