<?php
// Gibt alle angezeigten Texte als Text statt als HTML-Code aus
$e = function ($value): string {
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
};
$layout = isset($user) && $user instanceof User && !$user->isFirstLogin();
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= $e($title ?? 'QR-DocManager') ?> | QR-DocManager</title>
    <link rel="stylesheet" href="/assets/app.css?v=<?= filemtime(__DIR__ . '/../public/assets/app.css') ?>">
    <script src="/assets/app.js?v=<?= filemtime(__DIR__ . '/../public/assets/app.js') ?>" defer></script>
</head>
<body class="bg-gray-100 text-neutral-950 antialiased">
<?php if ($layout): ?>
    <div class="app-shell">
        <aside class="sidebar">
            <a class="brand" href="/events">QR-DocManager</a>
            <nav aria-label="Hauptnavigation">
                <a class="nav-link <?= ($title ?? '') === 'Events' ? 'active' : '' ?>" href="/events"><img class="icon" src="/assets/icons/folder.svg" alt="">Events</a>
                <?php if ($user->isAdmin()): ?>
                    <a class="nav-link <?= ($title ?? '') === 'Benutzerverwaltung' ? 'active' : '' ?>" href="/users"><img class="icon" src="/assets/icons/users.svg" alt="">Benutzerverwaltung</a>
                <?php endif; ?>
            </nav>
            <form class="logout" method="post" action="/logout">
                <input type="hidden" name="csrf" value="<?= $e(AuthService::token()) ?>">
                <button class="nav-link" type="submit"><img class="icon" src="/assets/icons/log-out.svg" alt="">Abmelden</button>
            </form>
        </aside>
        <div class="workspace">
            <header class="topbar">
                <h1 class="text-lg font-semibold"><?= $e($title) ?></h1>
                <div class="user-info"><img class="icon" src="/assets/icons/user-round.svg" alt=""><div><strong><?= $e($user->getUsername()) ?></strong><small><?= $user->isAdmin() ? 'Systemadministrator' : 'Standardbenutzer' ?></small></div></div>
            </header>
            <main class="content">
<?php else: ?>
    <main class="auth-shell">
        <div class="auth-wrap">
            <h1 class="mb-8 text-center text-2xl font-bold">QR-DocManager</h1>
<?php endif; ?>
