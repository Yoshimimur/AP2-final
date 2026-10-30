<?php
declare(strict_types=1);

class PublicController
{
    public function show(string $slug): void
    {
        echo 'Öffentliche Seite für: ' . htmlspecialchars($slug);
    }
}