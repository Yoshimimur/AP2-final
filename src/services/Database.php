<?php
declare(strict_types=1);

class Database
{
    private static ?PDO $pdo = null;

    // Verbindung
    public static function getConnection(): PDO
    {
        if (self::$pdo === null) {
            self::$pdo = new PDO('sqlite:' . __DIR__ . '/../../database/database.sqlite');

            // Fehler als Ausnahme melden
            self::$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            // Ergebnisse als Array mit Spaltennamen liefern
            self::$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

            // Fremdschlüssel einschalten (pro Verbindung)
            self::$pdo->exec('PRAGMA foreign_keys = ON');
        }

        return self::$pdo;
    }
}