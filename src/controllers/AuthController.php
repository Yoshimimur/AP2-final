<?php
declare(strict_types=1);

class AuthController
{
    private AuthService $auth;

    public function __construct()
    {
        $this->auth = new AuthService();
    }

    // Zeigt die Login-Seite (optional mit Fehlermeldung)
    public function showLogin(?string $error = null): void
    {
        require __DIR__ . '/../../views/login.php';
    }

    // Verarbeitet das abgeschickte Login-Formular
    public function login(): void
    {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        if ($this->auth->login($username, $password)) {
            header('Location: /admin');
            exit;
        }

        http_response_code(401);
        $this->showLogin('Benutzername oder Passwort ist falsch.');
    }

    // Meldet ab und leitet zur Login-Seite
    public function logout(): void
    {
        $this->auth->logout();
        header('Location: /login');
        exit;
    }
}