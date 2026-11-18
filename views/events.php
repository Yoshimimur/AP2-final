<?php require __DIR__ . '/header.php'; ?>
<div class="toolbar">
    <?php if ($isAdmin): ?><button class="btn" type="button" data-dialog="create-event"><img class="icon" src="/assets/icons/plus.svg" alt="">Event anlegen</button><?php endif; ?>
</div>
<?php if ($error): ?><p class="alert error" role="alert"><?= $e($error) ?></p><?php endif; ?>
<?php if ($notice): ?><p class="alert" role="status"><?= $e($notice) ?></p><?php endif; ?>
<?php if (!$eventRows): ?><section class="panel"><p>Noch keine Events vorhanden.</p></section><?php endif; ?>
<?php foreach ($eventRows as $eventRow): ?>
    <?php $event = $eventRow['event']; ?>
    <details class="event-card" open>
        <summary>
            <span class="event-title"><img class="icon" src="/assets/icons/folder.svg" alt=""><?= $e($event->getName()) ?></span>
            <span class="event-tools">
                <span class="text-xs text-gray-500"><?= count($eventRow['destinations']) ?> <?= count($eventRow['destinations']) === 1 ? 'Zieladresse' : 'Zieladressen' ?></span>
                <?php if ($isAdmin): ?>
                    <button class="icon-button" type="button" aria-label="Event umbenennen" data-dialog="rename-event" data-id="<?= $event->getId() ?>" data-name="<?= $e($event->getName()) ?>"><img class="icon" src="/assets/icons/pencil.svg" alt=""></button>
                    <button class="icon-button" type="button" aria-label="Event löschen" data-dialog="delete-event" data-id="<?= $event->getId() ?>" data-name="<?= $e($event->getName()) ?>"><img class="icon" src="/assets/icons/trash-2.svg" alt=""></button>
                <?php endif; ?>
                <img class="icon chevron" src="/assets/icons/chevron-down.svg" alt="">
            </span>
        </summary>
        <div class="table-scroll">
            <table class="destination-table">
                <thead><tr><th>Dokumentname</th><th>Feste URL</th><th>PDF</th><th>Status</th><th>Letzte Änderung</th><?php if ($isAdmin): ?><th>Aktionen</th><?php endif; ?></tr></thead>
                <tbody>
                <?php foreach ($eventRow['destinations'] as $row): ?>
                    <?php $destination = $row['destination']; $document = $row['document']; $lastChange = $row['lastChange']; ?>
                    <tr class="<?= $destination->isActive() ? '' : 'paused-row' ?>">
                        <td class="font-medium"><?= $e($destination->getName()) ?></td>
                        <td><a class="url-link" href="/<?= $e($destination->getSlug()) ?>" target="_blank" rel="noopener"><?= $e($baseUrl . '/' . $destination->getSlug()) ?></a></td>
                        <td>
                            <?php if ($document): ?>
                                <a class="pdf-link" href="/documents/<?= $destination->getId() ?>" target="_blank" rel="noopener"><?= $e($document->getFileName()) ?></a>
                                <small class="block mt-1 text-gray-500"><?= $e(number_format(ceil($document->getFileSize() / 1024), 0, ',', '.')) ?> KB · PDF öffnen</small>
                            <?php else: ?><span class="missing-pdf">Kein PDF hochgeladen</span><?php endif; ?>
                        </td>
                        <td><span class="badge <?= $destination->isActive() ? '' : 'paused' ?>"><?= $destination->isActive() ? 'Aktiv' : 'Pausiert' ?></span></td>
                        <td>
                            <?php if ($lastChange): ?>
                                <span class="block"><?= $e((new DateTimeImmutable($lastChange['changed_at'], new DateTimeZone('UTC')))->setTimezone(new DateTimeZone('Europe/Berlin'))->format('d.m.Y H:i')) ?></span>
                                <small class="block mt-1 text-gray-500"><?= $e($lastChange['user_shortcut']) ?></small>
                            <?php else: ?><span class="text-gray-500">Keine Änderung erfasst</span><?php endif; ?>
                        </td>
                        <?php if ($isAdmin): ?>
                            <td><div class="actions">
                                <button class="icon-button" type="button" aria-label="PDF hochladen oder austauschen" data-dialog="upload-pdf" data-id="<?= $destination->getId() ?>" data-name="<?= $e($destination->getName()) ?>"><img class="icon" src="/assets/icons/upload.svg" alt=""></button>
                                <form method="post" action="/destinations">
                                    <input type="hidden" name="csrf" value="<?= $e(AuthService::token()) ?>">
                                    <input type="hidden" name="action" value="status"><input type="hidden" name="id" value="<?= $destination->getId() ?>"><input type="hidden" name="status" value="<?= $destination->isActive() ? 'paused' : 'active' ?>">
                                    <button class="icon-button" type="submit" aria-label="<?= $destination->isActive() ? 'Zieladresse pausieren' : 'Zieladresse aktivieren' ?>"><img class="icon" src="/assets/icons/<?= $destination->isActive() ? 'pause' : 'play' ?>.svg" alt=""></button>
                                </form>
                                <button class="icon-button" type="button" aria-label="Zieladresse löschen" data-dialog="delete-destination" data-id="<?= $destination->getId() ?>" data-name="<?= $e($destination->getName()) ?>"><img class="icon" src="/assets/icons/trash-2.svg" alt=""></button>
                            </div></td>
                        <?php endif; ?>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$eventRow['destinations']): ?><tr><td colspan="<?= $isAdmin ? 6 : 5 ?>" class="text-gray-500">Noch keine Zieladressen vorhanden.</td></tr><?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php if ($isAdmin): ?>
            <div class="event-footer">
                <details class="destination-create">
                    <summary class="text-button">+ Dokument hinzufügen</summary>
                <form class="destination-form" method="post" action="/destinations">
                    <input type="hidden" name="csrf" value="<?= $e(AuthService::token()) ?>">
                    <input type="hidden" name="action" value="create">
                    <input type="hidden" name="event_id" value="<?= $event->getId() ?>">
                    <label class="sr-only" for="destination-name-<?= $event->getId() ?>">Dokumentname</label>
                    <input id="destination-name-<?= $event->getId() ?>" name="name" maxlength="100" placeholder="Dokumentname, z. B. Menükarte Winter" required>
                    <button class="btn" type="submit">Hinzufügen</button>
                    <button class="btn secondary" type="button" data-cancel-destination>Abbrechen</button>
                </form>
                </details>
            </div>
        <?php endif; ?>
    </details>
<?php endforeach; ?>

<?php if ($isAdmin): ?>
<!-- Die Dialoge übernehmen Nummer und Namen aus der gewählten Tabellenzeile -->
<dialog id="create-event"><form method="post" action="/events">
    <input type="hidden" name="csrf" value="<?= $e(AuthService::token()) ?>"><input type="hidden" name="action" value="create">
    <h2>Event anlegen</h2><label>Eventname<input name="name" maxlength="100" required></label>
    <div class="dialog-actions"><button class="btn secondary" type="button" data-close>Abbrechen</button><button class="btn" type="submit">Event anlegen</button></div>
</form></dialog>
<dialog id="rename-event"><form method="post" action="/events">
    <input type="hidden" name="csrf" value="<?= $e(AuthService::token()) ?>"><input type="hidden" name="action" value="rename"><input type="hidden" name="id">
    <h2>Event umbenennen</h2><label>Eventname<input name="name" maxlength="100" required></label>
    <p class="hint">URLs, Dokumente und bestehende Slugs bleiben unverändert.</p>
    <div class="dialog-actions"><button class="btn secondary" type="button" data-close>Abbrechen</button><button class="btn" type="submit">Namen speichern</button></div>
</form></dialog>
<dialog id="delete-event"><form method="post" action="/events">
    <input type="hidden" name="csrf" value="<?= $e(AuthService::token()) ?>"><input type="hidden" name="action" value="delete"><input type="hidden" name="id">
    <h2>Event endgültig löschen?</h2><p>Das Event „<strong data-current-name></strong>“, alle zugehörigen Zieladressen und PDF-Dateien werden endgültig gelöscht. Dieser Vorgang kann nicht rückgängig gemacht werden.</p>
    <div class="dialog-actions"><button class="btn secondary" type="button" data-close>Abbrechen</button><button class="btn danger" type="submit">Endgültig löschen</button></div>
</form></dialog>
<dialog id="upload-pdf"><form method="post" action="/destinations" enctype="multipart/form-data">
    <input type="hidden" name="csrf" value="<?= $e(AuthService::token()) ?>"><input type="hidden" name="action" value="upload"><input type="hidden" name="id">
    <h2>PDF hochladen oder austauschen</h2><p>Zieladresse: <strong data-current-name></strong></p>
    <label>PDF-Datei<input type="file" name="pdf" accept=".pdf,application/pdf" required></label>
    <p class="hint">Maximal 20 MB. Ein vorhandenes PDF wird ersetzt. Die URL bleibt unverändert.</p>
    <div class="dialog-actions"><button class="btn secondary" type="button" data-close>Abbrechen</button><button class="btn" type="submit">PDF speichern</button></div>
</form></dialog>
<dialog id="delete-destination"><form method="post" action="/destinations">
    <input type="hidden" name="csrf" value="<?= $e(AuthService::token()) ?>"><input type="hidden" name="action" value="delete"><input type="hidden" name="id">
    <h2>Zieladresse endgültig löschen?</h2><p>Die Zieladresse „<strong data-current-name></strong>“ und das zugehörige PDF werden endgültig gelöscht. Gedruckte QR-Codes mit dieser Adresse funktionieren danach nicht mehr.</p>
    <div class="dialog-actions"><button class="btn secondary" type="button" data-close>Abbrechen</button><button class="btn danger" type="submit">Endgültig löschen</button></div>
</form></dialog>
<?php endif; ?>
<?php require __DIR__ . '/footer.php'; ?>
