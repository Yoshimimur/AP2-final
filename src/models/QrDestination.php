<?php
declare(strict_types=1);

class QrDestination
{
    private int $id;
    private int $eventId;
    private string $slug;
    private string $name;
    private string $status;

    // Macht aus einer Datenbankzeile eine Zieladresse
    private static function fromRow(array $row): QrDestination
    {
        $destination = new QrDestination();
        $destination->id = (int) $row['id'];
        $destination->eventId = (int) $row['event_id'];
        $destination->slug = $row['slug'];
        $destination->name = $row['name'];
        $destination->status = $row['status'];
        return $destination;
    }

    // Sucht eine Zieladresse anhand ihrer Nummer
    public static function findById(int $id): ?QrDestination
    {
        $stmt = Database::getConnection()->prepare('SELECT * FROM qr_destinations WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ? self::fromRow($row) : null;
    }

    // Sucht die feste öffentliche Adresse
    public static function findBySlug(string $slug): ?QrDestination
    {
        $stmt = Database::getConnection()->prepare('SELECT * FROM qr_destinations WHERE slug = :slug');
        $stmt->execute(['slug' => $slug]);
        $row = $stmt->fetch();
        return $row ? self::fromRow($row) : null;
    }

    // Liefert die Zieladressen eines Events
    public static function findByEvent(int $eventId): array
    {
        $stmt = Database::getConnection()->prepare('SELECT * FROM qr_destinations WHERE event_id = :event ORDER BY name, id');
        $stmt->execute(['event' => $eventId]);
        $destinations = [];
        foreach ($stmt->fetchAll() as $row) {
            $destinations[] = self::fromRow($row);
        }
        return $destinations;
    }

    // Erstellt einen lesbaren Pfad mit einem zufälligen achtstelligen Code
    public static function create(int $eventId, string $name): QrDestination
    {
        $name = trim($name);
        if ($name === '' || preg_match('/^.{1,100}$/us', $name) !== 1) {
            throw new InvalidArgumentException('Bitte einen Dokumentnamen mit höchstens 100 Zeichen eingeben.');
        }
        if (Event::findById($eventId) === null) {
            throw new InvalidArgumentException('Das Event wurde nicht gefunden.');
        }
        $urlName = strtolower(strtr($name, ['Ä' => 'Ae', 'Ö' => 'Oe', 'Ü' => 'Ue', 'ä' => 'ae', 'ö' => 'oe', 'ü' => 'ue', 'ß' => 'ss']));
        $urlName = preg_replace('/[^a-z0-9]+/', '-', $urlName);
        $urlName = trim($urlName, '-');
        if ($urlName === '') {
            throw new InvalidArgumentException('Der Dokumentname muss mindestens einen lateinischen Buchstaben oder eine Ziffer enthalten.');
        }
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('INSERT INTO qr_destinations (event_id, slug, name) VALUES (:event, :slug, :name)');
        for ($attempt = 0; $attempt < 10; $attempt++) {
            $slug = $urlName . '/' . bin2hex(random_bytes(4));
            if (self::findBySlug($slug) !== null) {
                continue;
            }
            try {
                $stmt->execute(['event' => $eventId, 'slug' => $slug, 'name' => $name]);
                return self::findById((int) $pdo->lastInsertId());
            } catch (PDOException $error) {
                // Ein anderer Aufruf könnte denselben Slug inzwischen gespeichert haben
                if (self::findBySlug($slug) === null) {
                    throw $error;
                }
            }
        }
        throw new RuntimeException('Die Zieladresse konnte nicht erstellt werden');
    }

    // Ändert nur den Status und URL-Pfad bleibt unverändert
    public function setStatus(string $status): void
    {
        if (!in_array($status, ['active', 'paused'], true)) {
            throw new InvalidArgumentException('Bitte einen gültigen Status wählen.');
        }
        $stmt = Database::getConnection()->prepare('UPDATE qr_destinations SET status = :status WHERE id = :id');
        $stmt->execute(['status' => $status, 'id' => $this->id]);
        $this->status = $status;
    }

    // Löscht die Zieladresse und ihre Dokumentzuordnung
    public function delete(): void
    {
        $stmt = Database::getConnection()->prepare('DELETE FROM qr_destinations WHERE id = :id');
        $stmt->execute(['id' => $this->id]);
    }

    // Gibt die gespeicherten Werte zum Lesen frei
    public function getId(): int { return $this->id; }
    public function getEventId(): int { return $this->eventId; }
    public function getSlug(): string { return $this->slug; }
    public function getName(): string { return $this->name; }
    public function getStatus(): string { return $this->status; }
    public function isActive(): bool { return $this->status === 'active'; }
}
