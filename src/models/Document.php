<?php
declare(strict_types=1);

class Document
{
    private int $id;
    private int $qrDestinationId;
    private string $name;
    private string $fileName;
    private string $filePath;
    private int $fileSize;

    // Sucht das aktuelle PDF zu einer Zieladresse
    public static function findByDestination(int $id): ?Document
    {
        $stmt = Database::getConnection()->prepare('SELECT * FROM documents WHERE qr_destination_id = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        if (!$row) {
            return null;
        }
        $document = new Document();
        $document->id = (int) $row['id'];
        $document->qrDestinationId = (int) $row['qr_destination_id'];
        $document->name = $row['name'];
        $document->fileName = $row['file_name'];
        $document->filePath = $row['file_path'];
        $document->fileSize = (int) $row['file_size'];
        return $document;
    }

    // Speichert die Dateiinformationen und ersetzt eine vorhandene Zuordnung
    public static function save(QrDestination $destination, array $file): void
    {
        $stmt = Database::getConnection()->prepare(
            'INSERT INTO documents (qr_destination_id, name, file_name, file_path, file_size)
             VALUES (:destination, :name, :file_name, :file_path, :file_size)
             ON CONFLICT(qr_destination_id) DO UPDATE SET
             name = excluded.name, file_name = excluded.file_name,
             file_path = excluded.file_path, file_size = excluded.file_size'
        );
        $stmt->execute([
            'destination' => $destination->getId(), 'name' => $destination->getName(),
            'file_name' => $file['file_name'], 'file_path' => $file['file_path'], 'file_size' => $file['file_size'],
        ]);
    }

    // Gibt Dateiinformationen zum Lesen frei
    public function getId(): int { return $this->id; }
    public function getQrDestinationId(): int { return $this->qrDestinationId; }
    public function getName(): string { return $this->name; }
    public function getFileName(): string { return $this->fileName; }
    public function getFilePath(): string { return $this->filePath; }
    public function getFileSize(): int { return $this->fileSize; }
}
