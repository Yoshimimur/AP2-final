<?php
declare(strict_types=1);

// Die Einrichtung darf nur im Terminal gestartet werden
if (PHP_SAPI !== 'cli') {
    exit('Nur im Terminal ausführen');
}

$dbFile = __DIR__ . '/database.sqlite';

if (file_exists($dbFile)) {
    exit("Die Datenbank existiert bereits. Setup wurde abgebrochen.\n");
}

$pdo = new PDO('sqlite:' . $dbFile);

$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$pdo->exec('PRAGMA foreign_keys = ON');

$pdo->exec(file_get_contents(__DIR__ . '/schema.sql'));

// Zufälliges vorläufiges Passwort
$temp = implode('-', str_split(bin2hex(random_bytes(6)), 4));
// Ersten Systemadministrator anlegen
$stmt = $pdo->prepare(
    'INSERT INTO users (username, password_hash, role, first_login)
     VALUES (:u, :h, :r, 1)'
);
$stmt->execute([
    'u' => 'Systemadministrator',
    'h' => password_hash($temp, PASSWORD_DEFAULT),
    'r' => 'system_admin',
]);

echo "Datenbank angelegt.\n";
echo "Benutzername: Systemadministrator\n";
echo "Vorläufiges Passwort: $temp\n";
echo "Das Passwort muss bei der ersten Anmeldung geändert werden.\n";
