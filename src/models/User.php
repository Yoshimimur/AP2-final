<?php
declare(strict_types=1);

class User
{
    private const ROLES = ['system_admin', 'standard_user'];
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

    // Alle Benutzer, alphabetisch sortiert (für die Benutzerverwaltung)
    public static function findAll(): array
    {
        $rows = Database::getConnection()
            ->query('SELECT * FROM users ORDER BY username')
            ->fetchAll();

        $users = [];
        foreach ($rows as $row) {
            $users[] = self::fromRow($row);
        }
        return $users;
    }

    // Legt einen Benutzer mit vorläufigem Passwort an 
    public static function create(string $username, string $password, string $role): User
    {
        if (preg_match('/^[a-zA-Z0-9._-]{1,50}$/D', $username) !== 1) {
            throw new InvalidArgumentException('Das Kürzel darf 1 bis 50 Buchstaben, Ziffern, Punkte, Bindestriche oder Unterstriche enthalten.');
        }
        if (self::findByUsername($username) !== null) {
            throw new InvalidArgumentException('Dieses Benutzerkürzel ist bereits vergeben.');
        }
        if ($password === '' || strlen($password) > 72) {
            throw new InvalidArgumentException('Bitte ein vorläufiges Passwort mit höchstens 72 Bytes eingeben.');
        }
        if (!in_array($role, self::ROLES, true)) {
            throw new InvalidArgumentException('Ungültige Rolle.');
        }

        $stmt = Database::getConnection()->prepare(
            'INSERT INTO users (username, password_hash, role, first_login)
             VALUES (:u, :h, :r, 1)'
        );
        $stmt->execute([
            'u' => $username,
            'h' => password_hash($password, PASSWORD_DEFAULT),
            'r' => $role,
        ]);

        return self::findByUsername($username);
    }

    // Sucht einen Benutzer für die Benutzerverwaltung
    public static function findById(int $id): ?User
    {
        $stmt = Database::getConnection()->prepare('SELECT * FROM users WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ? self::fromRow($row) : null;
    }

    // Sucht einen Benutzer über den Benutzernamen; gibt null zurück, wenn es kein Benutzer gibt
    public static function findByUsername(string $username): ?User
    {
        $stmt = Database::getConnection()->prepare(
            'SELECT * FROM users WHERE username = :u'
        );
        $stmt->execute(['u' => $username]);
        $row = $stmt->fetch();

        return $row ? self::fromRow($row) : null;
    }


        // Setzt ein neues Passwort; danach ist es kein vorläufiges mehr (first_login = 0)
    public function changePassword(string $password): void
    {
        $hash = password_hash($password, PASSWORD_DEFAULT);

        $stmt = Database::getConnection()->prepare(
            'UPDATE users SET password_hash = :h, first_login = 0 WHERE id = :id'
        );
        $stmt->execute(['h' => $hash, 'id' => $this->id]);

        $this->passwordHash = $hash;
        $this->firstLogin   = false;
    }

    // Ändert die Rolle des Benutzers
    public function changeRole(string $role): void
    {
        if ($this->isProtected()) {
            throw new InvalidArgumentException('Die Rolle des festen Systemadministrators kann nicht geändert werden.');
        }
        if (!in_array($role, self::ROLES, true)) {
            throw new InvalidArgumentException('Ungültige Rolle.');
        }

        $stmt = Database::getConnection()->prepare(
            'UPDATE users SET role = :r WHERE id = :id'
        );
        $stmt->execute(['r' => $role, 'id' => $this->id]);

        $this->role = $role;
    }

        // Löscht den Benutzer; seine Protokolleinträge bleiben erhalten
    public function delete(): void
    {
        if ($this->isProtected()) {
            throw new InvalidArgumentException('Der feste Systemadministrator kann nicht gelöscht werden.');
        }
        $stmt = Database::getConnection()->prepare('DELETE FROM users WHERE id = :id');
        $stmt->execute(['id' => $this->id]);
    }

    // Ist der Benutzer Systemadministrator?
    public function isAdmin(): bool
    {
        return $this->role === 'system_admin';
    }

    // Das erste Einrichtungskonto bleibt als Administrator erhalten
    public function isProtected(): bool
    {
        return $this->username === 'Systemadministrator';
    }

    // Lesezugriff auf die Eigenschaften (private)
    public function getId(): int { return $this->id; }
    public function getUsername(): string { return $this->username; }
    public function getPasswordHash(): string { return $this->passwordHash; }
    public function getRole(): string { return $this->role; }
    public function isFirstLogin(): bool { return $this->firstLogin; }
    public function getCreatedAt(): string { return $this->createdAt; }
}
