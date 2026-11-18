<?php require __DIR__ . '/header.php'; ?>
<section class="panel">
    <h2 class="mb-6 text-lg font-semibold">Anmelden</h2>
    <?php if ($error): ?><p class="alert error" role="alert"><?= $e($error) ?></p><?php endif; ?>
    <form method="post" action="/login">
        <input type="hidden" name="csrf" value="<?= $e(AuthService::token()) ?>">
        <label>Benutzername<input name="username" maxlength="50" autocomplete="username" required autofocus></label>
        <label>Passwort<input type="password" name="password" autocomplete="current-password" required></label>
        <button class="btn w-full mt-6" type="submit">Anmelden</button>
    </form>
</section>
<?php require __DIR__ . '/footer.php'; ?>