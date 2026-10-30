<?php
declare(strict_types=1);

class User
{
    private int $id;
    private string $username;
    private string $passwordHash;
    private string $role;
    private bool $firstLogin;
    private string $createdAt;

    // Baut aus einer Datenbankzeile ein User-Objekt
    private static function fromRow(array $row): User
    {
        $user = new User();
        $user->id           = (int) $row['id'];
        $user->username     = $row['username'];
        $user->passwordHash = $row['password_hash'];
        $user->role         = $row['role'];
        $user->firstLogin   = (bool) $row['first_login'];
        $user->createdAt    = $row['created_at'];
        return $user;
    }

    // Sucht einen Benutzer über den Benutzernamen. Gibt null zurück, wenn es kein Benutzer gibt
    public static function findByUsername(string $username): ?User
    {
        $stmt = Database::getConnection()->prepare(
            'SELECT * FROM users WHERE username = :u'
        );
        $stmt->execute(['u' => $username]);
        $row = $stmt->fetch();

        return $row ? self::fromRow($row) : null;
    }

    // Ist der Benutzer Systemadministrator?
    public function isAdmin(): bool
    {
        return $this->role === 'system_admin';
    }

    // Lesezugriff auf die Eigenschaften (private)
    public function getId(): int { return $this->id; }
    public function getUsername(): string { return $this->username; }
    public function getPasswordHash(): string { return $this->passwordHash; }
    public function getRole(): string { return $this->role; }
    public function isFirstLogin(): bool { return $this->firstLogin; }
    public function getCreatedAt(): string { return $this->createdAt; }
}