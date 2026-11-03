<?php
declare(strict_types=1);

class AuthService
{
    // Prüft Benutzername und Passwort (bei Erfolg wird der Benutzer in der Session gemerkt)
    public function login(string $username, string $password): bool
    {
        $user = User::findByUsername($username);

        if ($user === null || !password_verify($password, $user->getPasswordHash())) {
            return false;
        }

        // Neue Session-ID vergeben
        session_regenerate_id(true);
        $_SESSION['username'] = $user->getUsername();

        return true;
    }

        // Meldet den Benutzer ab und beendet die Session
    public function logout(): void
    {
        $_SESSION = [];

        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
    }

    // Liefert den angemeldeten Benutzer (oder null, wenn niemand angemeldet ist)
    public function currentUser(): ?User
    {
        $username = $_SESSION['username'] ?? null;

        if ($username === null) {
            return null;
        }

        return User::findByUsername($username);
    }

        // Lässt nur Systemadministratoren durch
    public function requireAdmin(): void
    {
        $user = $this->currentUser();

        if ($user === null) {
            header('Location: /login');
            exit;
        }

        if (!$user->isAdmin()) {
            http_response_code(403);
            echo 'Zugriff verweigert.';
            exit;
        }
    }
}