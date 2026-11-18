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

        $events = [];
        foreach ($rows as $row) {
            $events[] = self::fromRow($row);
        }
        return $events;
    }

    // Sucht ein Event über seine ID;  Gibt null zurück, wenn es keins gibt
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

        if ($name === '' || preg_match('/^.{1,100}$/us', $name) !== 1) {
            throw new InvalidArgumentException('Bitte einen Namen mit 1 bis 100 Zeichen eingeben.');
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

        if ($name === '' || preg_match('/^.{1,100}$/us', $name) !== 1) {
            throw new InvalidArgumentException('Bitte einen Namen mit 1 bis 100 Zeichen eingeben.');
        }

        $stmt = Database::getConnection()->prepare('UPDATE events SET name = :n WHERE id = :id');
        $stmt->execute(['n' => $name, 'id' => $this->id]);

        $this->name = $name;
    }
    // Der Controller speichert die gesamte Löschung gemeinsam
    public function delete(): void
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('DELETE FROM qr_destinations WHERE event_id = :id');
        $stmt->execute(['id' => $this->id]);
        $stmt = $pdo->prepare('DELETE FROM events WHERE id = :id');
        $stmt->execute(['id' => $this->id]);
    }

    // Getter: Lesezugriff auf die privaten Werte
    public function getId(): int { return $this->id; }
    public function getName(): string { return $this->name; }
}
