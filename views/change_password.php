<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Passwort ändern – QR-DocManager</title>
</head>
<body>
    <h1>Passwort ändern</h1>
    <p>Bitte vergeben Sie ein neues Passwort, bevor Sie fortfahren.<br>
       Es muss mindestens 8 Zeichen lang sein und Groß- und Kleinbuchstaben,
       eine Ziffer und ein Sonderzeichen enthalten.</p>

    <?php if (!empty($error)): ?>
        <p><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>

    <form method="post" action="/password">
        <p>
            <label>Neues Passwort (mindestens 8 Zeichen)<br>
                <input type="password" name="new_password" minlength="8" required autofocus autocomplete="new-password">
            </label>
        </p>
        <p>
            <label>Neues Passwort wiederholen<br>
                <input type="password" name="repeat_password" minlength="8" required autocomplete="new-password">
            </label>
        </p>
        <button type="submit">Passwort speichern</button>
    </form>
</body>
</html>