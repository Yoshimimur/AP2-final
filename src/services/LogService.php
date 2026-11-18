<?php
declare(strict_types=1);

class LogService
{
    // Schreibt eine erfolgreiche Änderung ins Protokoll
    public function log(User $user, QrDestination $destination, string $action): void
    {
        ChangeLog::create($user, $destination, $action);
    }
}
