-- SQLite prüft Fremdschlüssel nur, wenn das ausdrücklich eingeschaltet wird
PRAGMA foreign_keys = ON;

-- EVENTS
CREATE TABLE events (
    id    INTEGER PRIMARY KEY AUTOINCREMENT,
    name  TEXT NOT NULL
);

-- QR_DESTINATIONS: die festen QR-Zieladressen eines Events
CREATE TABLE qr_destinations (
    id        INTEGER PRIMARY KEY AUTOINCREMENT,
    event_id  INTEGER NOT NULL,
    slug      TEXT NOT NULL UNIQUE,
    name      TEXT NOT NULL,
    status    TEXT NOT NULL DEFAULT 'active' CHECK (status IN ('active', 'paused')),
    FOREIGN KEY (event_id) REFERENCES events(id)
);

-- DOCUMENTS: das PDF zu einer Zieladresse 
CREATE TABLE documents (
    id                 INTEGER PRIMARY KEY AUTOINCREMENT,
    qr_destination_id  INTEGER NOT NULL UNIQUE,
    name               TEXT NOT NULL,
    file_name          TEXT NOT NULL,
    file_path          TEXT NOT NULL,
    file_size          INTEGER NOT NULL,
    FOREIGN KEY (qr_destination_id) REFERENCES qr_destinations(id) ON DELETE CASCADE
);

-- USERS
CREATE TABLE users (
    id             INTEGER PRIMARY KEY AUTOINCREMENT,
    username       TEXT NOT NULL UNIQUE,
    password_hash  TEXT NOT NULL,
    role           TEXT NOT NULL DEFAULT 'standard_user' CHECK (role IN ('system_admin', 'standard_user')),
    created_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    first_login    INTEGER NOT NULL DEFAULT 1 CHECK (first_login IN (0, 1))
);

-- CHANGE_LOGS: Änderungsprotokoll 
CREATE TABLE change_logs (
    id                 INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id            INTEGER,
    qr_destination_id  INTEGER,
    user_shortcut      TEXT NOT NULL,
    action             TEXT NOT NULL,
    changed_at         DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (qr_destination_id) REFERENCES qr_destinations(id) ON DELETE SET NULL
);


-- Der Slug einer QR-Zieladresse darf nach dem Anlegen nicht mehr geändert werden

CREATE TRIGGER qr_slug_unveraenderlich
BEFORE UPDATE OF slug ON qr_destinations
WHEN OLD.slug <> NEW.slug
BEGIN
    SELECT RAISE(ABORT, 'Der Slug einer QR-Zieladresse kann nicht geaendert werden.');
END;
