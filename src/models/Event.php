<?php
declare(strict_types=1);

class Event
{
    private int $id;
    private string $name;

    // Baut aus einer Datenbankzeile ein Event-Objekt
    private static function fromRow(array $row): Event
    {
        $event = new Event();
        $event->id   = (int) $row['id'];
        $event->name = $row['name'];
        return $event;
    }

    // Alle Events (alphabetisch sortiert)
    public static function findAll(): array
    {
        $rows = Database::getConnection()
            ->query('SELECT * FROM events ORDER BY name')
            ->fetchAll();

        return array_map(fn(array $row) => self::fromRow($row), $rows);
    }

    // Sucht ein Event über seine ID - Gibt null zurück, wenn es keins gibt
    public static function findById(int $id): ?Event
    {
        $stmt = Database::getConnection()->prepare('SELECT * FROM events WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row ? self::fromRow($row) : null;
    }

    // Legt ein neues Event an
    public static function create(string $name): Event
    {
        $name = trim($name);

        if ($name === '') {
            throw new InvalidArgumentException('Der Name darf nicht leer sein.');
        }

        $pdo  = Database::getConnection();
        $stmt = $pdo->prepare('INSERT INTO events (name) VALUES (:n)');
        $stmt->execute(['n' => $name]);

        return self::findById((int) $pdo->lastInsertId());
    }

    // Benennt das Event um
    public function rename(string $name): void
    {
        $name = trim($name);

        if ($name === '') {
            throw new InvalidArgumentException('Der Name darf nicht leer sein.');
        }

        $stmt = Database::getConnection()->prepare('UPDATE events SET name = :n WHERE id = :id');
        $stmt->execute(['n' => $name, 'id' => $this->id]);

        $this->name = $name;
    }

    // Löscht das Event mit allen QR-Zieladressen. Deren URL's und Dokumente verschwinden automatisch mit (ON DELETE CASCADE)
    public function delete(): void
    {
        $pdo = Database::getConnection();
        $pdo->beginTransaction();

        try {
            $stmt = $pdo->prepare('DELETE FROM qr_destinations WHERE event_id = :id');
            $stmt->execute(['id' => $this->id]);

            $stmt = $pdo->prepare('DELETE FROM events WHERE id = :id');
            $stmt->execute(['id' => $this->id]);

            $pdo->commit();
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    // Getter: Lesezugriff auf die privaten Werte
    public function getId(): int { return $this->id; }
    public function getName(): string { return $this->name; }
}