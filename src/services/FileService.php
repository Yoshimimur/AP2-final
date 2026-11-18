<?php
declare(strict_types=1);

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UploadedFileInterface;
use Slim\Psr7\Factory\StreamFactory;

class FileService
{
    public const MAX_SIZE = 20 * 1024 * 1024;

    // Legt den privaten Dateiordner fest
    public function directory(): string
    {
        return getenv('QR_UPLOAD_PATH') ?: __DIR__ . '/../../uploads';
    }

    // Prüft den vom Server vergebenen Speichernamen
    public function path(string $name): string
    {
        if (preg_match('/^[a-f0-9]{32}\.pdf$/', $name) !== 1) {
            throw new RuntimeException('Ungültiger Speichername');
        }
        return $this->directory() . '/' . $name;
    }

    // Prüft und speichert nur echte PDF-Dateien bis 20 MB
    public function store(UploadedFileInterface $file): array
    {
        if ($file->getError() !== UPLOAD_ERR_OK) {
            throw new InvalidArgumentException('Der Upload ist fehlgeschlagen. Bitte eine PDF-Datei bis 20 MB auswählen.');
        }
        if ($file->getSize() === null || $file->getSize() <= 0 || $file->getSize() > self::MAX_SIZE) {
            throw new InvalidArgumentException('Die PDF-Datei darf höchstens 20 MB groß und nicht leer sein.');
        }
        $originalName = basename(str_replace('\\', '/', $file->getClientFilename() ?? ''));
        if (strtolower(pathinfo($originalName, PATHINFO_EXTENSION)) !== 'pdf'
            || preg_match('/^[^\x00-\x1F\x7F]{1,200}$/u', $originalName) !== 1) {
            throw new InvalidArgumentException('Bitte eine PDF-Datei mit einem gültigen Dateinamen auswählen.');
        }
        if (!is_dir($this->directory()) && !mkdir($this->directory(), 0700, true)) {
            throw new RuntimeException('Der Dateiordner konnte nicht angelegt werden');
        }
        $name = bin2hex(random_bytes(16)) . '.pdf';
        $path = $this->path($name);
        $file->moveTo($path);
        try {
            $size = filesize($path);
            $handle = fopen($path, 'rb');
            if ($handle === false) {
                throw new RuntimeException('Die hochgeladene Datei konnte nicht gelesen werden');
            }
            $start = fread($handle, 5);
            fclose($handle);
            $type = (new finfo(FILEINFO_MIME_TYPE))->file($path);
            if ($size === false || $size <= 0 || $size > self::MAX_SIZE || $start !== '%PDF-' || $type !== 'application/pdf') {
                throw new InvalidArgumentException('Erlaubt sind nur PDF-Dateien bis 20 MB.');
            }
            return ['file_name' => $originalName, 'file_path' => $name, 'file_size' => $size];
        } catch (Throwable $error) {
            $this->delete($name);
            throw $error;
        }
    }

    // Entfernt eine nicht mehr benötigte Datei aus dem privaten Ordner
    public function delete(string $name): void
    {
        $path = $this->path($name);
        if (is_file($path) && !unlink($path)) {
            throw new RuntimeException('Eine alte Datei konnte nicht entfernt werden');
        }
    }

    // Liefert das geprüfte PDF zum Anzeigen und Speichern im Browser
    public function output(Document $document, ResponseInterface $response): ResponseInterface
    {
        $path = $this->path($document->getFilePath());
        if (!is_file($path) || is_link($path)) {
            throw new RuntimeException('Die PDF-Datei wurde nicht gefunden');
        }
        $stream = (new StreamFactory())->createStreamFromFile($path, 'rb');
        return $response->withBody($stream)
            ->withHeader('Content-Type', 'application/pdf')
            ->withHeader('Content-Length', (string) filesize($path))
            ->withHeader('Content-Disposition', "inline; filename=\"document.pdf\"; filename*=UTF-8''" . rawurlencode($document->getFileName()))
            ->withHeader('Cache-Control', 'private, no-store')
            ->withHeader('X-Content-Type-Options', 'nosniff');
    }
}
