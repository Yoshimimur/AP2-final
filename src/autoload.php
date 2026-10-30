<?php
declare(strict_types=1);

// Lädt Klassen automatisch
spl_autoload_register(function (string $class): void {
    foreach (['models', 'services', 'controllers'] as $dir) {
        $file = __DIR__ . '/' . $dir . '/' . $class . '.php';
        if (is_file($file)) {
            require $file;
            return;
        }
    }
});