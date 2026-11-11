<?php
declare(strict_types=1);

class AdminController
{
    private AuthService $auth;

    public function __construct()
    {
        $this->auth = new AuthService();
    }

    // Eventübersicht: alle angemeldeten Benutzer sehen die Liste, nur Administratoren ändern
    public function manageEvents(): void
    {
        $user = $this->auth->currentUser();

        if ($user === null) {
            header('Location: /login');
            exit;
        }

        $error = null;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Änderungen sind nur für Systemadministratoren erlaubt
            $this->auth->requireAdmin();

            $action = $_POST['action'] ?? '';
            $id     = (int) ($_POST['id'] ?? 0);
            $name   = trim($_POST['name'] ?? '');

            if ($action === 'delete') {
                $event = Event::findById($id);

                if ($event === null) {
                    $error = 'Das Event wurde nicht gefunden.';
                } else {
                    $event->delete();
                    header('Location: /events');
                    exit;
                }
            } elseif ($name === '') {
                $error = 'Bitte geben Sie einen Namen ein.';
            } elseif (strlen($name) > 100) {
                $error = 'Der Name darf höchstens 100 Zeichen lang sein.';
            } elseif ($action === 'create') {
                Event::create($name);
                header('Location: /events');
                exit;
            } elseif ($action === 'rename') {
                $event = Event::findById($id);

                if ($event === null) {
                    $error = 'Das Event wurde nicht gefunden.';
                } else {
                    $event->rename($name);
                    header('Location: /events');
                    exit;
                }
            }
        }

        $events  = Event::findAll();
        $isAdmin = $user->isAdmin();

        require __DIR__ . '/../../views/events.php';
    }
}