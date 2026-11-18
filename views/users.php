<?php require __DIR__ . '/header.php'; ?>
<div class="toolbar">
    <button class="btn" type="button" data-dialog="create-user"><img class="icon" src="/assets/icons/plus.svg" alt="">Benutzer anlegen</button>
</div>
<?php if ($error): ?><p class="alert error" role="alert"><?= $e($error) ?></p><?php endif; ?>
<?php if ($notice): ?><p class="alert" role="status"><?= $e($notice) ?></p><?php endif; ?>
<section class="panel p-0">
    <div class="panel-heading"><h2 class="font-semibold">Benutzerverwaltung</h2><p class="mt-2 text-sm text-gray-600">Als Benutzername wird das betriebliche Kürzel verwendet.</p></div>
    <div class="table-scroll">
        <table>
            <thead><tr><th>Benutzername / Kürzel</th><th>Rolle</th><th>Erstellt am</th><th>Aktionen</th></tr></thead>
            <tbody>
            <?php foreach ($users as $entry): ?>
                <tr>
                    <td class="font-medium"><?= $e($entry->getUsername()) ?></td>
                    <td>
                    <?php if ($entry->isProtected()): ?>
                        <span class="badge">Systemadministrator | geschützt</span>
                    <?php else: ?>
                    <form method="post" action="/users">
                        <input type="hidden" name="csrf" value="<?= $e(AuthService::token()) ?>">
                        <input type="hidden" name="action" value="role"><input type="hidden" name="id" value="<?= $entry->getId() ?>">
                        <select name="role" data-role-select aria-label="Rolle für <?= $e($entry->getUsername()) ?>">
                            <option value="standard_user" <?= !$entry->isAdmin() ? 'selected' : '' ?>>Standardbenutzer</option>
                            <option value="system_admin" <?= $entry->isAdmin() ? 'selected' : '' ?>>Systemadministrator</option>
                        </select>
                        <noscript><button class="btn" type="submit">Speichern</button></noscript>
                    </form>
                    <?php endif; ?>
                    </td>
                    <td><?= $e((new DateTimeImmutable($entry->getCreatedAt(), new DateTimeZone('UTC')))->setTimezone(new DateTimeZone('Europe/Berlin'))->format('d.m.Y H:i')) ?></td>
                    <td><div class="actions">
                        <?php if (!$entry->isProtected()): ?>
                        <button class="icon-button" type="button" aria-label="<?= $e($entry->getUsername()) ?> löschen" data-dialog="delete-user" data-id="<?= $entry->getId() ?>" data-name="<?= $e($entry->getUsername()) ?>"><img class="icon" src="/assets/icons/trash-2.svg" alt=""></button>
                        <?php endif; ?>
                    </div></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>

<!-- Die Dialoge verwenden dieselbe geschützte Benutzerverwaltung -->
<dialog id="create-user">
    <form method="post" action="/users">
        <input type="hidden" name="csrf" value="<?= $e(AuthService::token()) ?>">
        <input type="hidden" name="action" value="create">
        <h2>Benutzer anlegen</h2>
        <label>Benutzername / Kürzel<input name="username" maxlength="50" pattern="[a-zA-Z0-9._\-]+" required aria-describedby="shortcut-hint"></label>
        <p id="shortcut-hint" class="hint">Bitte das betriebliche Kürzel verwenden.</p>
        <p class="hint missing-pdf">Nach dem Anlegen wird ein vorläufiges Passwort erzeugt und einmal angezeigt. Es muss beim ersten Login geändert werden.</p>
        <label>Rolle<select name="role"><option value="standard_user">Standardbenutzer</option><option value="system_admin">Systemadministrator</option></select></label>
        <div class="dialog-actions"><button class="btn secondary" type="button" data-close>Abbrechen</button><button class="btn" type="submit">Benutzer anlegen</button></div>
    </form>
</dialog>
<dialog id="delete-user">
    <form method="post" action="/users">
        <input type="hidden" name="csrf" value="<?= $e(AuthService::token()) ?>">
        <input type="hidden" name="action" value="delete"><input type="hidden" name="id">
        <h2>Benutzer endgültig löschen?</h2>
        <p>Das Konto „<strong data-current-name></strong>“ wird gelöscht. Seine Änderungsprotokolle bleiben erhalten. Dieser Vorgang kann nicht rückgängig gemacht werden.</p>
        <div class="dialog-actions"><button class="btn secondary" type="button" data-close>Abbrechen</button><button class="btn danger" type="submit">Endgültig löschen</button></div>
    </form>
</dialog>
<?php require __DIR__ . '/footer.php'; ?>
