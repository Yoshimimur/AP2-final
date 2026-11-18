<?php
declare(strict_types=1);

class AuthService
{
    public function login(string $username, string $password): bool
    {
        $user = User::findByUsername($username);
        if ($user === null || !password_verify($password, $user->getPasswordHash())) {
            return false;
        }

        session_regenerate_id(true);
        $_SESSION = [];
        $_SESSION['username'] = $user->getUsername();
        return true;
    }

    public function logout(): void
    {
        $_SESSION = [];
        if (session_status() === PHP_SESSION_ACTIVE) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', [
                'expires' => time() - 3600, 'path' => $params['path'],
                'secure' => $params['secure'], 'httponly' => true, 'samesite' => 'Lax',
            ]);
            session_destroy();
        }
    }

    public function currentUser(): ?User
    {
        $username = $_SESSION['username'] ?? null;
        return is_string($username) ? User::findByUsername($username) : null;
    }

    // Erstellt einen geheimen Prüfwert für Formulare dieser Sitzung
    public static function token(): string
    {
        if (!isset($_SESSION['csrf'])) {
            $_SESSION['csrf'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf'];
    }

    // Vergleicht den Formularwert mit dem Wert der Sitzung
    public static function validToken(mixed $token): bool
    {
        return is_string($token) && isset($_SESSION['csrf']) && hash_equals($_SESSION['csrf'], $token);
    }

    // Nimmt nur einzelne Texte an und weist manipulierte Formularfelder zurück
    public static function text(array $data, string $key): string
    {
        $value = $data[$key] ?? '';
        if (!is_string($value)) {
            throw new InvalidArgumentException('Bitte gültige Formulardaten eingeben.');
        }
        return $value;
    }
}
