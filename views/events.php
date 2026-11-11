<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Events – QR-DocManager</title>
</head>
<body>
    <nav>
        <a href="/events">Events</a>
        <a href="/logout">Abmelden</a>
    </nav>

    <h1>Events</h1>

    <?php if (!empty($error)): ?>
        <p><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>

    <?php if ($isAdmin): ?>
        <button type="button" id="open-create">+ Event anlegen</button>
    <?php endif; ?>

    <?php if (empty($events)): ?>
        <p>Noch keine Events vorhanden.</p>
    <?php else: ?>
        <ul>
            <?php foreach ($events as $event): ?>
                <li>
                    <?= htmlspecialchars($event->getName()) ?>
                    <?php if ($isAdmin): ?>
                        <button type="button" class="js-rename"
                                data-id="<?= $event->getId() ?>"
                                data-name="<?= htmlspecialchars($event->getName()) ?>">Umbenennen</button>
                        <button type="button" class="js-delete"
                                data-id="<?= $event->getId() ?>"
                                data-name="<?= htmlspecialchars($event->getName()) ?>">Löschen</button>
                    <?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>

    <?php if ($isAdmin): ?>
        <!-- Dialog: Event anlegen -->
        <dialog id="dialog-create">
            <form method="post" action="/events">
                <h2>Event anlegen</h2>
                <input type="hidden" name="action" value="create">
                <p>
                    <label>Eventname<br>
                        <input type="text" name="name" maxlength="100" required>
                    </label>
                </p>
                <button type="button" class="js-close">Abbrechen</button>
                <button type="submit">Event anlegen</button>
            </form>
        </dialog>

        <!-- Dialog: Event umbenennen -->
        <dialog id="dialog-rename">
            <form method="post" action="/events">
                <h2>Event umbenennen</h2>
                <input type="hidden" name="action" value="rename">
                <input type="hidden" name="id" id="rename-id">
                <p>
                    <label>Eventname<br>
                        <input type="text" name="name" id="rename-name" maxlength="100" required>
                    </label>
                </p>
                <p>URLs, Dokumente und bestehende Slugs bleiben unverändert.</p>
                <button type="button" class="js-close">Abbrechen</button>
                <button type="submit">Namen speichern</button>
            </form>
        </dialog>

        <!-- Dialog: Event löschen -->
        <dialog id="dialog-delete">
            <form method="post" action="/events">
                <h2>Event endgültig löschen?</h2>
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" id="delete-id">
                <p>Das Event „<span id="delete-name"></span>“ und alle enthaltenen Dokumente
                   werden endgültig gelöscht. Dieser Vorgang kann nicht rückgängig gemacht werden.</p>
                <button type="button" class="js-close">Abbrechen</button>
                <button type="submit">Endgültig löschen</button>
            </form>
        </dialog>

        <script>
            const $ = (selector) => document.querySelector(selector);

            // Dialog "Event anlegen" öffnen
            $('#open-create').addEventListener('click', () => $('#dialog-create').showModal());

            // Dialog "Umbenennen": ID und aktuellen Namen einsetzen, dann öffnen
            document.querySelectorAll('.js-rename').forEach((button) => {
                button.addEventListener('click', () => {
                    $('#rename-id').value   = button.dataset.id;
                    $('#rename-name').value = button.dataset.name;
                    $('#dialog-rename').showModal();
                });
            });

            // Dialog "Löschen": ID und Namen einsetzen, dann öffnen
            document.querySelectorAll('.js-delete').forEach((button) => {
                button.addEventListener('click', () => {
                    $('#delete-id').value         = button.dataset.id;
                    $('#delete-name').textContent = button.dataset.name;
                    $('#dialog-delete').showModal();
                });
            });

            // "Abbrechen" schließt den jeweiligen Dialog
            document.querySelectorAll('.js-close').forEach((button) => {
                button.addEventListener('click', () => button.closest('dialog').close());
            });
        </script>
    <?php endif; ?>
</body>
</html>