<?php
declare(strict_types=1);

session_start();
require __DIR__ . '/../src/autoload.php';

$path   = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');
$method = $_SERVER['REQUEST_METHOD'];

// Solange ein vorläufiges Passwort gilt, ist nur "Passwort ändern" (und Abmelden) erreichbar
$user = (new AuthService())->currentUser();
if ($user !== null && $user->isFirstLogin() && !in_array($path, ['password', 'logout'], true)) {
    header('Location: /password');
    exit;
}

if ($path === '' || $path === 'login') {
    $controller = new AuthController();
    if ($method === 'POST') {
        $controller->login();
    } else {
        $controller->showLogin();
    }
} elseif ($path === 'password') {
    $controller = new AuthController();
    if ($method === 'POST') {
        $controller->changePassword();
    } else {
        $controller->showChangePassword();
    }
} elseif ($path === 'logout') {
    (new AuthController())->logout();
} elseif ($path === 'admin') {
    // Vorübergehend, nur zum Testen der Anmeldung
    if ($user === null) {
        header('Location: /login');
        exit;
    }
    echo 'Angemeldet als ' . htmlspecialchars($user->getUsername())
        . ' – <a href="/logout">Abmelden</a>';
} elseif (preg_match('#^[a-z0-9-]+/[a-f0-9]{8}$#', $path)) {
    // Öffentliche QR-Adresse: Namensteil + zufälliger Code
    (new PublicController())->show($path);
} else {
    http_response_code(404);
    echo 'Seite nicht gefunden.';
}