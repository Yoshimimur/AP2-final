<?php
declare(strict_types=1);

class ChangeLog
{
    // Speichert Benutzerkürzel und Aktion unabhängig vom späteren Löschen des Benutzers
    public static function create(User $user, QrDestination $destination, string $action): void
    {
        $stmt = Database::getConnection()->prepare(
            'INSERT INTO change_logs (user_id, qr_destination_id, user_shortcut, action)
             VALUES (:user, :destination, :shortcut, :action)'
        );
        $stmt->execute([
            'user' => $user->getId(), 'destination' => $destination->getId(),
            'shortcut' => $user->getUsername(), 'action' => $action,
        ]);
    }

    // Holt die letzte Änderung einer Zieladresse
    public static function findLastByDestination(int $id): ?array
    {
        $stmt = Database::getConnection()->prepare('SELECT * FROM change_logs WHERE qr_destination_id = :id ORDER BY id DESC LIMIT 1');
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }
}
