<?php
declare(strict_types=1);

class Database
{
    private static ?PDO $pdo = null;

    // Verbindung
    public static function getConnection(): PDO
    {
        if (self::$pdo === null) {
            $path = getenv('QR_DB_PATH') ?: __DIR__ . '/../../database/database.sqlite';
            if (!is_file($path)) {
                throw new RuntimeException('Die Datenbank wurde noch nicht eingerichtet');
            }
            self::$pdo = new PDO('sqlite:' . $path);

            // Fehler als Ausnahme melden
            self::$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            // Ergebnisse als Array mit Spaltennamen liefern
            self::$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

            // Fremdschlüssel einschalten (pro Verbindung)
            self::$pdo->exec('PRAGMA foreign_keys = ON');
            self::$pdo->exec('PRAGMA busy_timeout = 5000');
        }

        return self::$pdo;
    }
}
