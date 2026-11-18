<?php
declare(strict_types=1);

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as Handler;
use Slim\Factory\AppFactory;
use Slim\Middleware\OutputBufferingMiddleware;
use Slim\Psr7\Factory\StreamFactory;

// Richtet Routing und die Ausgabe der PHP-Views ein
$app = AppFactory::create();
$app->add(new OutputBufferingMiddleware(new StreamFactory(), 'append'));

// Diese Prüfungen gelten auch für direkt gesendete Anfragen
$app->add(function (Request $request, Handler $handler) use ($app): Response {
    $user = (new AuthService())->currentUser();
    $path = $request->getUri()->getPath();
    $pathParts = explode('/', $path);
    $section = $pathParts[1] ?? '';
    $isPost = $request->getMethod() === 'POST';
    $protected = in_array($section, ['events', 'destinations', 'users', 'documents'], true);

    // Interne PDFs dürfen nur angemeldete Benutzer öffnen
    $needsLogin = $protected || $path === '/password';
    if ($needsLogin && $user === null) {
        return $app->getResponseFactory()->createResponse(302)->withHeader('Location', '/login');
    }

    $needsNewPassword = $protected || in_array($path, ['/', '/login', '/admin'], true);
    if ($user !== null && $user->isFirstLogin() && $needsNewPassword) {
        return $app->getResponseFactory()->createResponse(302)->withHeader('Location', '/password');
    }

    $changesData = $isPost && in_array($path, ['/events', '/destinations'], true);
    $adminOnly = $path === '/users' || $changesData;
    if ($adminOnly && ($user === null || !$user->isAdmin())) {
        throw new \Slim\Exception\HttpForbiddenException($request);
    }

    // Anmeldung und Abmeldung brauchen den Prüfwert der Sitzung
    if ($isPost) {
        if ((int) $request->getHeaderLine('Content-Length') > 25 * 1024 * 1024) {
            throw new \Slim\Exception\HttpException($request, 'Die Anfrage ist zu groß. Erlaubt sind PDFs bis 20 MB.', 413);
        }
        $data = (array) $request->getParsedBody();
        if (!AuthService::validToken($data['csrf'] ?? null)) {
            throw new \Slim\Exception\HttpForbiddenException($request, 'Das Formular ist abgelaufen. Bitte die Seite neu laden.');
        }
    }

    // Zeigt fehlerhafte Verwaltungseingaben wieder auf der passenden Übersicht
    try {
        return $handler->handle($request);
    } catch (InvalidArgumentException $error) {
        if (!in_array($path, ['/events', '/destinations', '/users'], true)) {
            throw $error;
        }
        $_SESSION['error'] = $error->getMessage();
        $target = $path === '/users' ? '/users' : '/events';
        return $app->getResponseFactory()->createResponse(303)->withHeader('Location', $target);
    }
});
$app->addBodyParsingMiddleware();
$app->addRoutingMiddleware();

// Technische Details bleiben im Fehlerprotokoll und werden Gästen nicht angezeigt
$errors = $app->addErrorMiddleware(false, true, true);
$errors->setDefaultErrorHandler(function (Request $request, Throwable $exception) use ($app): Response {
    $status = 500;
    if ($exception instanceof \Slim\Exception\HttpException) {
        $code = $exception->getCode();
        if ($code >= 400 && $code <= 599) {
            $status = $code;
        }
    }
    error_log(get_class($exception) . ': ' . $exception->getMessage());
    $message = match ($status) {
        403 => 'Diese Aktion ist nicht erlaubt oder das Formular ist abgelaufen. Bitte die Seite neu laden.',
        404 => 'Das Dokument ist derzeit nicht verfügbar.',
        405 => 'Dieser Aufruf ist nicht erlaubt.',
        413 => 'Die Anfrage ist zu groß. Erlaubt sind PDF-Dateien bis 20 MB.',
        default => 'Die Anfrage konnte gerade nicht verarbeitet werden. Bitte später erneut versuchen.',
    };
    $response = $app->getResponseFactory()->createResponse($status);
    $response->getBody()->write($message);
    return $response->withHeader('Content-Type', 'text/plain; charset=UTF-8');
});

// Verhindert das Zwischenspeichern privater Seiten und fremde Skripte
$app->add(function (Request $request, Handler $handler): Response {
    $response = $handler->handle($request);
    if (!$response->hasHeader('Content-Type')) {
        $response = $response->withHeader('Content-Type', 'text/html; charset=UTF-8');
    }
    return $response
        ->withHeader('Cache-Control', 'private, no-store')
        ->withHeader('X-Content-Type-Options', 'nosniff')
        ->withHeader('Referrer-Policy', 'same-origin')
        ->withHeader('X-Frame-Options', 'SAMEORIGIN')
        ->withHeader('Content-Security-Policy', "default-src 'self'; script-src 'self'; style-src 'self'; img-src 'self'; object-src 'none'; base-uri 'none'; frame-ancestors 'self'; form-action 'self'");
});

// Ordnet URLs und HTTP-Methoden den zuständigen Controllern zu
$auth = new AuthController();
$admin = new AdminController();
$public = new PublicController();
$app->get('/', [$auth, 'showLogin']);
$app->post('/', [$auth, 'login']);
$app->get('/login', [$auth, 'showLogin']);
$app->post('/login', [$auth, 'login']);
$app->map(['GET', 'POST'], '/password', [$auth, 'changePassword']);
$app->post('/logout', [$auth, 'logout']);
$app->get('/admin', function (Request $request, Response $response): Response {
    return $response->withHeader('Location', '/events')->withStatus(302);
});
$app->map(['GET', 'POST'], '/events', [$admin, 'manageEvents']);
$app->post('/destinations', [$admin, 'manageDestinations']);
$app->map(['GET', 'POST'], '/users', [$admin, 'manageUsers']);
$app->get('/documents/{id:[0-9]+}', [$public, 'preview']);
$app->get('/{name:[a-z0-9-]+}/{code:[a-f0-9]{8}}', [$public, 'show']);

return $app;
