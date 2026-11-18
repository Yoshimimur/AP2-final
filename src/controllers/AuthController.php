<?php
declare(strict_types=1);

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class AuthController
{
    // Zeigt die Anmeldung oder leitet zur Übersicht weiter
    public function showLogin(Request $request, Response $response): Response
    {
        if ((new AuthService())->currentUser() !== null) {
            return $response->withHeader('Location', '/events')->withStatus(302);
        }
        $error = null;
        $title = 'Anmelden';
        require __DIR__ . '/../../views/login.php';
        return $response;
    }

    // Prüft Kürzel und Passwort, ohne zu verreten, was falsch war
    public function login(Request $request, Response $response): Response
    {
        try {
            $data = (array) $request->getParsedBody();
            $username = trim(AuthService::text($data, 'username'));
            $password = AuthService::text($data, 'password');
            if (strlen($username) <= 50 && strlen($password) <= 72 && (new AuthService())->login($username, $password)) {
                return $response->withHeader('Location', '/events')->withStatus(303);
            }
        } catch (InvalidArgumentException $error) {
            // Manipulierte Felder erhalten dieselbe Meldung wie falsche Zugangsdaten
        }
        $error = 'Benutzername oder Passwort ist falsch.';
        $title = 'Anmelden';
        require __DIR__ . '/../../views/login.php';
        return $response->withStatus(401);
    }

    // Zeigt den ersten Passwortwechsel und verarbeitet das Formular
    public function changePassword(Request $request, Response $response): Response
    {
        $user = (new AuthService())->currentUser();
        if (!$user->isFirstLogin()) {
            return $response->withHeader('Location', '/events')->withStatus(303);
        }
        $error = null;
        if ($request->getMethod() === 'POST') {
            try {
                $data = (array) $request->getParsedBody();
                $new = AuthService::text($data, 'new_password');
                $repeat = AuthService::text($data, 'repeat_password');
                if (strlen($new) < 8 || strlen($new) > 72 || preg_match('/[a-z]/', $new) !== 1
                    || preg_match('/[A-Z]/', $new) !== 1 || preg_match('/[0-9]/', $new) !== 1
                    || preg_match('/[^a-zA-Z0-9]/', $new) !== 1) {
                    throw new InvalidArgumentException('Das Passwort benötigt mindestens 8 Zeichen, Groß- und Kleinbuchstaben, eine Ziffer und ein Sonderzeichen. Höchstens 72 Bytes sind erlaubt.');
                }
                if ($new !== $repeat) {
                    throw new InvalidArgumentException('Die beiden Passwörter stimmen nicht überein.');
                }
                if (password_verify($new, $user->getPasswordHash())) {
                    throw new InvalidArgumentException('Das neue Passwort muss sich vom bisherigen unterscheiden.');
                }
                $user->changePassword($new);
                session_regenerate_id(true);
                unset($_SESSION['csrf']);
                return $response->withHeader('Location', '/events')->withStatus(303);
            } catch (InvalidArgumentException $exception) {
                $error = $exception->getMessage();
            }
        }
        $title = 'Passwort ändern';
        require __DIR__ . '/../../views/change_password.php';
        return $response->withStatus($error === null ? 200 : 422);
    }

    // Beendet die Sitzung nach einem geschützten Formularaufruf
    public function logout(Request $request, Response $response): Response
    {
        (new AuthService())->logout();
        return $response->withHeader('Location', '/login')->withStatus(303);
    }
}
