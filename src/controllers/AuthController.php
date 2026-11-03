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

    // Zeigt das Formular zum Ändern des Passworts (nur beim ersten Login)
    public function showChangePassword(?string $error = null): void
    {
        $user = $this->auth->currentUser();

        if ($user === null) {
            header('Location: /login');
            exit;
        }

        if (!$user->isFirstLogin()) {
            header('Location: /admin');
            exit;
        }

        require __DIR__ . '/../../views/change_password.php';
    }

    // Prüft die Eingaben und speichert das neue Passwort
    public function changePassword(): void
    {
        $user = $this->auth->currentUser();

        if ($user === null) {
            header('Location: /login');
            exit;
        }

        if (!$user->isFirstLogin()) {
            header('Location: /admin');
            exit;
        }

        $new    = $_POST['new_password'] ?? '';
        $repeat = $_POST['repeat_password'] ?? '';

        if (!$this->isStrongPassword($new)) {
            $this->showChangePassword(
                'Das Passwort muss mindestens 8 Zeichen lang sein und Groß- und Kleinbuchstaben, '
                . 'eine Ziffer und ein Sonderzeichen enthalten.'
            );
            return;
        }

        if ($new !== $repeat) {
            $this->showChangePassword('Die beiden Passwörter stimmen nicht überein.');
            return;
        }

        if (password_verify($new, $user->getPasswordHash())) {
            $this->showChangePassword('Das neue Passwort muss sich vom bisherigen unterscheiden.');
            return;
        }

        $user->changePassword($new);

        header('Location: /admin');
        exit;
    }

    // Meldet ab und leitet zur Login-Seite
    public function logout(): void
    {
        $this->auth->logout();
        header('Location: /login');
        exit;
    }

    // Prüft die Passwortregeln: mindestens 8 Zeichen, Groß- und Kleinbuchstaben, Ziffer, Sonderzeichen.
    private function isStrongPassword(string $password): bool
    {
        return strlen($password) >= 8
            && preg_match('/[a-z]/', $password) === 1
            && preg_match('/[A-Z]/', $password) === 1
            && preg_match('/[0-9]/', $password) === 1
            && preg_match('/[^a-zA-Z0-9]/', $password) === 1;
    }
}

