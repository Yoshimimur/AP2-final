<?php
declare(strict_types=1);

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class AdminController
{
    public function manageEvents(Request $request, Response $response): Response
    {
        $user = (new AuthService())->currentUser();
        if ($request->getMethod() === 'POST') {
            $data = (array) $request->getParsedBody();
            $action = AuthService::text($data, 'action');
            $name = trim(AuthService::text($data, 'name'));
            if (!in_array($action, ['create', 'rename', 'delete'], true)) {
                throw new InvalidArgumentException('Diese Aktion ist nicht erlaubt.');
            }
            if ($action !== 'create') {
                $id = $this->id($data, 'id');
                $event = Event::findById($id);
                if ($event === null) {
                    throw new InvalidArgumentException('Das Event wurde nicht gefunden.');
                }
            }
            if ($action === 'create') {
                Event::create($name);
            } elseif ($action === 'rename') {
                $event->rename($name);
            } else {
                $this->deleteEvent($event, $user);
            }
            $_SESSION['notice'] = 'Die Änderung wurde gespeichert.';
            return $response->withHeader('Location', '/events')->withStatus(303);
        }
        $events = Event::findAll();
        $eventRows = [];
        foreach ($events as $event) {
            $destinations = [];
            $eventDestinations = QrDestination::findByEvent($event->getId());
            foreach ($eventDestinations as $destination) {
                $destinations[] = [
                    'destination' => $destination,
                    'document' => Document::findByDestination($destination->getId()),
                    'lastChange' => ChangeLog::findLastByDestination($destination->getId()),
                ];
            }
            $eventRows[] = ['event' => $event, 'destinations' => $destinations];
        }
        $baseUrl = $request->getUri()->getScheme() . '://' . $request->getUri()->getAuthority();
        $isAdmin = $user->isAdmin();
        $title = 'Events';
        $error = $_SESSION['error'] ?? null;
        $notice = $_SESSION['notice'] ?? null;
        unset($_SESSION['error'], $_SESSION['notice']);
        require __DIR__ . '/../../views/events.php';
        return $response;
    }

    // Löscht zusammengehörige Einträge gemeinsam und danach ihre PDF-Dateien
    private function deleteEvent(Event $event, User $user): void
    {
        $pdo = Database::getConnection();
        $fileService = new FileService();
        $logService = new LogService();
        $files = [];
        $pdo->beginTransaction();
        try {
            $destinations = QrDestination::findByEvent($event->getId());
            foreach ($destinations as $destination) {
                $document = Document::findByDestination($destination->getId());
                if ($document !== null) {
                    $files[] = $document->getFilePath();
                }
                $logService->log($user, $destination, 'Zieladresse mit Event gelöscht: ' . $destination->getName());
            }
            $event->delete();
            $pdo->commit();
        } catch (Throwable $error) {
            $pdo->rollBack();
            throw $error;
        }
        foreach ($files as $path) {
            $fileService->delete($path);
        }
    }

    public function manageDestinations(Request $request, Response $response): Response
    {
        $user = (new AuthService())->currentUser();
        $pdo = Database::getConnection();
        $fileService = new FileService();
        $newFile = null;
        $oldPath = null;
        try {
            $data = (array) $request->getParsedBody();
            $action = AuthService::text($data, 'action');
            if (!in_array($action, ['create', 'status', 'upload', 'delete'], true)) {
                throw new InvalidArgumentException('Diese Aktion ist nicht erlaubt.');
            }
            if ($action === 'upload') {
                $uploadedFiles = $request->getUploadedFiles();
                $file = $uploadedFiles['pdf'] ?? null;
                if (!$file instanceof \Psr\Http\Message\UploadedFileInterface) {
                    throw new InvalidArgumentException('Bitte eine PDF-Datei bis 20 MB auswählen.');
                }
                $newFile = $fileService->store($file);
            }
            $pdo->beginTransaction();
            if ($action === 'create') {
                $eventId = $this->id($data, 'event_id');
                $name = AuthService::text($data, 'name');
                $destination = QrDestination::create($eventId, $name);
                $log = 'Zieladresse erstellt';
            } else {
                $id = $this->id($data, 'id');
                $destination = QrDestination::findById($id);
                if ($destination === null) {
                    throw new InvalidArgumentException('Die Zieladresse wurde nicht gefunden.');
                }
                $oldDocument = Document::findByDestination($destination->getId());
                if ($oldDocument !== null && ($action === 'upload' || $action === 'delete')) {
                    $oldPath = $oldDocument->getFilePath();
                }
            }
            if ($action === 'status') {
                $status = AuthService::text($data, 'status');
                $destination->setStatus($status);
                $log = 'Zieladresse pausiert';
                if ($destination->isActive()) {
                    $log = 'Zieladresse aktiviert';
                }
            }
            if ($action === 'upload') {
                Document::save($destination, $newFile);
                $log = 'PDF hochgeladen';
                if ($oldDocument !== null) {
                    $log = 'PDF ausgetauscht';
                }
            }
            if ($action === 'delete') {
                $log = 'Zieladresse gelöscht: ' . $destination->getName();
            }
            $logService = new LogService();
            $logService->log($user, $destination, $log);
            if ($action === 'delete') {
                $destination->delete();
            }
            $pdo->commit();
        } catch (Throwable $error) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            if ($newFile !== null) {
                $fileService->delete($newFile['file_path']);
            }
            throw $error;
        }
        // Erst nach dem Speichern wird das alte PDF entfernt
        if ($oldPath !== null) {
            $fileService->delete($oldPath);
        }
        $_SESSION['notice'] = 'Die Änderung wurde gespeichert.';
        return $response->withHeader('Location', '/events')->withStatus(303);
    }

    public function manageUsers(Request $request, Response $response): Response
    {
        $user = (new AuthService())->currentUser();
        $credentialsNotice = null;
        if ($request->getMethod() === 'POST') {
            $data = (array) $request->getParsedBody();
            $action = AuthService::text($data, 'action');
            if (!in_array($action, ['create', 'role', 'delete'], true)) {
                throw new InvalidArgumentException('Diese Aktion ist nicht erlaubt.');
            }
            if ($action !== 'create') {
                $id = $this->id($data, 'id');
                $target = User::findById($id);
                if ($target === null) {
                    throw new InvalidArgumentException('Der Benutzer wurde nicht gefunden.');
                }
            }
            if ($action === 'create') {
                $username = trim(AuthService::text($data, 'username'));
                $password = bin2hex(random_bytes(6));
                $role = AuthService::text($data, 'role');
                User::create($username, $password, $role);
                // Zeigt das vorläufige Passwort direkt und speichert es nicht in der Sitzung
                $credentialsNotice = 'Benutzer ' . $username . ' angelegt. Vorläufiges Passwort: ' . $password . ' – bitte jetzt sicher weitergeben.';
            } elseif ($action === 'role') {
                $role = AuthService::text($data, 'role');
                $target->changeRole($role);
            } else {
                $target->delete();
            }
            if ($action !== 'create') {
                $_SESSION['notice'] = 'Die Änderung wurde gespeichert.';
                if ($target->getId() === $user->getId()) {
                    return $response->withHeader('Location', '/events')->withStatus(303);
                }
            }
            if ($credentialsNotice === null) {
                return $response->withHeader('Location', '/users')->withStatus(303);
            }
        }
        $users = User::findAll();
        $title = 'Benutzerverwaltung';
        $isAdmin = true;
        $error = $_SESSION['error'] ?? null;
        $notice = $credentialsNotice ?? ($_SESSION['notice'] ?? null);
        unset($_SESSION['error'], $_SESSION['notice']);
        require __DIR__ . '/../../views/users.php';
        return $response;
    }

    // Prüft, ob die ID eine ganze Zahl größer als 0 ist
    private function id(array $data, string $key): int
    {
        $value = AuthService::text($data, $key);
        $id = filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if ($id === false) {
            throw new InvalidArgumentException('Bitte einen gültigen Eintrag auswählen.');
        }
        return $id;
    }
}
