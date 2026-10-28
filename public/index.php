<?php
declare(strict_types=1);

require __DIR__ . '/../src/services/Database.php';

$pdo  = Database::getConnection();
$user = $pdo->query('SELECT username, role FROM users')->fetch();

echo 'Verbindung OK. Benutzer: '
    . htmlspecialchars($user['username']) . ' (' . htmlspecialchars($user['role']) . ')';