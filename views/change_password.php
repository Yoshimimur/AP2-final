<?php require __DIR__ . '/header.php'; ?>
<section class="panel">
    <h2 class="mb-4 text-lg font-semibold">Passwort ändern</h2>
    <p class="hint">Bitte vor der ersten Nutzung ein eigenes Passwort vergeben. Mindestens 8 Zeichen mit Groß- und Kleinbuchstaben, einer Ziffer und einem Sonderzeichen.</p>
    <?php if ($error): ?><p class="alert error" role="alert"><?= $e($error) ?></p><?php endif; ?>
    <form method="post" action="/password">
        <input type="hidden" name="csrf" value="<?= $e(AuthService::token()) ?>">
        <label>Neues Passwort<input type="password" name="new_password" minlength="8" maxlength="72" autocomplete="new-password" required autofocus></label>
        <label>Passwort wiederholen<input type="password" name="repeat_password" minlength="8" maxlength="72" autocomplete="new-password" required></label>
        <button class="btn w-full mt-6" type="submit">Passwort speichern</button>
    </form>
    <form class="mt-4" method="post" action="/logout"><input type="hidden" name="csrf" value="<?= $e(AuthService::token()) ?>"><button class="text-button" type="submit">Abmelden</button></form>
</section>
<?php require __DIR__ . '/footer.php'; ?>