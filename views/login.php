<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Anmelden | QR-DocManager</title>
</head>
<body>
    <h1>Anmelden</h1>

    <?php if (!empty($error)): ?>
        <p><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>

    <form method="post" action="/login">
        <p>
            <label>Benutzername<br>
                <input type="text" name="username" required autofocus>
            </label>
        </p>
        <p>
            <label>Passwort<br>
                <input type="password" name="password" required>
            </label>
        </p>
        <button type="submit">Anmelden</button>
    </form>
</body>
</html>