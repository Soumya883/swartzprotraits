<?php

declare(strict_types=1);

use App\Auth\AuthService;
use App\Auth\AuthorizationService;
use App\Auth\JwtService;
use App\Auth\PasswordService;
use App\Controllers\AdminController;
use App\Controllers\AuthController;
use App\Controllers\ContactController;
use App\Controllers\GalleryController;
use App\Controllers\UserController;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Core\Router;
use App\Middleware\AuthMiddleware;
use App\Middleware\RoleMiddleware;
use App\Repositories\GalleryRepository;
use App\Repositories\UserRepository;

require_once __DIR__ . '/../src/bootstrap.php';

// DEBUG LOGGING
file_put_contents(__DIR__ . '/../../debug_log.txt', date('[Y-m-d H:i:s] ') . ($_SERVER['REQUEST_METHOD'] ?? 'UNKNOWN') . ' ' . ($_SERVER['REQUEST_URI'] ?? 'UNKNOWN') . PHP_EOL, FILE_APPEND);


set_exception_handler(static function (Throwable $e): void {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Internal Server Error: ' . $e->getMessage()
    ]);
    exit;
});

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS');

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'OPTIONS') {
    http_response_code(204);
    exit;
}

$request = new Request();
$router = new Router();

$db = Database::connection();
$users = new UserRepository($db);
$passwords = new PasswordService();
$jwt = new JwtService($config['auth']['jwt_secret'], $config['auth']['jwt_issuer']);
$authService = new AuthService(
    $users,
    $passwords,
    $jwt,
    $config['auth']['jwt_access_ttl'],
    $config['auth']['jwt_refresh_ttl']
);

$authorization = new AuthorizationService();
$authMiddleware = new AuthMiddleware($jwt);
$roleMiddleware = new RoleMiddleware($authorization);

$authController = new AuthController($authService);
$userController = new UserController();
$adminController = new AdminController();
$contactController = new ContactController();
$galleryRepo = new GalleryRepository($db);
$galleryController = new GalleryController($galleryRepo, $passwords);

$router->register('GET', '/', static function (): void {
    header('Content-Type: text/html; charset=UTF-8');
    echo <<<'HTML'
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>PHP Auth API</title>
  <style>
    :root{
      --bg:#070b1a;--bg2:#0f172a;--card:#111a2f;--line:#2a3554;--text:#e8ecff;--muted:#98a3c7;
      --accent:#7c8cff;--accent2:#00d4ff;--ok:#22c55e;
    }
    *{box-sizing:border-box}
    body{
      margin:0;font-family:Inter,Segoe UI,Arial,sans-serif;color:var(--text);
      background: radial-gradient(1200px 600px at 10% -10%, #1b2b6a 0%, transparent 60%),
                  radial-gradient(900px 500px at 100% 0%, #0f4c77 0%, transparent 55%),
                  linear-gradient(140deg,var(--bg),var(--bg2));
      min-height:100vh;
    }
    .wrap{max-width:1100px;margin:0 auto;padding:44px 22px 60px}
    .badge{display:inline-block;padding:6px 12px;border:1px solid var(--line);border-radius:999px;color:var(--muted);font-size:12px}
    h1{font-size:40px;line-height:1.08;margin:16px 0 8px}
    p.lead{color:var(--muted);max-width:760px}
    .grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:14px;margin-top:26px}
    .card{
      background:linear-gradient(180deg, rgba(255,255,255,.03), rgba(255,255,255,.01));
      border:1px solid var(--line);border-radius:16px;padding:16px
    }
    .method{display:inline-block;padding:3px 9px;border-radius:999px;font-size:11px;font-weight:700;letter-spacing:.04em}
    .get{background:#153923;color:#89f0b0}.post{background:#1f2e66;color:#a9b5ff}
    code{display:block;margin-top:10px;padding:10px;border-radius:10px;background:#0a1022;color:#d9e0ff;overflow:auto}
    .two{display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-top:14px}
    .k{color:var(--accent2)} .v{color:#d7e3ff}
    .ok{color:var(--ok)}
    @media (max-width:700px){h1{font-size:30px}.two{grid-template-columns:1fr}}
  </style>
</head>
<body>
  <main class="wrap">
    <span class="badge">Secure • Balanced • Scalable Backend</span>
    <h1>PHP Authentication & Authorization API</h1>
    <p class="lead">Production-style backend with JWT access tokens, refresh token rotation, role-based access control, and full relational database design.</p>

    <section class="grid">
      <article class="card"><span class="method post">POST</span><code>/api/auth/register</code><p>Create user account securely.</p></article>
      <article class="card"><span class="method post">POST</span><code>/api/auth/login</code><p>Login and receive access/refresh tokens.</p></article>
      <article class="card"><span class="method post">POST</span><code>/api/auth/refresh</code><p>Rotate refresh token and mint new access token.</p></article>
      <article class="card"><span class="method get">GET</span><code>/api/me</code><p>Protected profile endpoint (Bearer token required).</p></article>
      <article class="card"><span class="method get">GET</span><code>/api/admin/dashboard</code><p>Admin-only endpoint with role guard.</p></article>
    </section>

    <section class="two">
      <article class="card">
        <h3>Quick Start</h3>
        <code>php scripts/migrate.php --fresh
php -S localhost:8000 -t public</code>
      </article>
      <article class="card">
        <h3>Default Admin</h3>
        <code><span class="k">email:</span> <span class="v">admin@example.com</span>
<span class="k">password:</span> <span class="v">Admin@12345</span></code>
        <p class="ok">Change password immediately in production.</p>
      </article>
    </section>
  </main>
</body>
</html>
HTML;
});

$router->register('POST', '/api/auth/register', static function (Request $req) use ($authController): void {
    $authController->register($req);
});
$router->register('POST', '/api/auth/login', static function (Request $req) use ($authController): void {
    $authController->login($req);
});
$router->register('POST', '/api/auth/refresh', static function (Request $req) use ($authController): void {
    $authController->refresh($req);
});

$router->register('POST', '/api/contact', static function (Request $req) use ($contactController): void {
    $contactController->submit($req);
});

$router->register('GET', '/api', static function (): void {
    Response::success('API online', [
        'version' => '1.1.0',
        'routes' => [
            'POST /api/auth/register',
            'POST /api/auth/login',
            'POST /api/auth/refresh',
            'POST /api/contact',
            'GET /api/me',
            'GET /api/admin/dashboard',
            'GET /api/admin/contacts',
        ],
    ]);
});

$router->register('GET', '/api/me', static function (Request $req) use ($authMiddleware, $userController): void {
    $claims = $authMiddleware->handle($req);
    if (!$claims) {
        return;
    }
    $userController->profile($claims);
});

$router->register('GET', '/api/admin/dashboard', static function (Request $req) use ($authMiddleware, $roleMiddleware, $adminController): void {
    $claims = $authMiddleware->handle($req);
    if (!$claims) {
        return;
    }

    if (!$roleMiddleware->handle($claims, 'admin')) {
        return;
    }

    $adminController->info();
});

$router->register('GET', '/api/admin/contacts', static function (Request $req) use ($authMiddleware, $roleMiddleware, $contactController): void {
    $claims = $authMiddleware->handle($req);
    if (!$claims) {
        return;
    }

    if (!$roleMiddleware->handle($claims, 'admin')) {
        return;
    }

    $contactController->listSubmissions();
});

// ── Admin Gallery Routes ───────────────────────────────────────────────
$router->register('GET', '/api/admin/gallery/clients', static function (Request $req) use ($authMiddleware, $roleMiddleware, $galleryController): void {
    $claims = $authMiddleware->handle($req);
    if (!$claims) return;
    if (!$roleMiddleware->handle($claims, 'admin')) return;
    $galleryController->listClients();
});

$router->register('GET', '/api/admin/gallery/photos', static function (Request $req) use ($authMiddleware, $roleMiddleware, $galleryController): void {
    $claims = $authMiddleware->handle($req);
    if (!$claims) return;
    if (!$roleMiddleware->handle($claims, 'admin')) return;
    $galleryController->listPhotos($req);
});

$router->register('POST', '/api/admin/gallery/password', static function (Request $req) use ($authMiddleware, $roleMiddleware, $galleryController): void {
    $claims = $authMiddleware->handle($req);
    if (!$claims) return;
    if (!$roleMiddleware->handle($claims, 'admin')) return;
    $galleryController->setPassword($req);
});

$router->register('POST', '/api/admin/gallery/photo', static function (Request $req) use ($authMiddleware, $roleMiddleware, $galleryController): void {
    $claims = $authMiddleware->handle($req);
    if (!$claims) return;
    if (!$roleMiddleware->handle($claims, 'admin')) return;
    $galleryController->addPhoto($req, $claims);
});

$router->register('POST', '/api/admin/gallery/upload-folder', static function (Request $req) use ($authMiddleware, $roleMiddleware, $galleryController): void {
    $claims = $authMiddleware->handle($req);
    if (!$claims) return;
    if (!$roleMiddleware->handle($claims, 'admin')) return;
    $galleryController->uploadPhotosBatch($req, $claims);
});

$router->register('DELETE', '/api/admin/gallery/photo', static function (Request $req) use ($authMiddleware, $roleMiddleware, $galleryController): void {
    $claims = $authMiddleware->handle($req);
    if (!$claims) return;
    if (!$roleMiddleware->handle($claims, 'admin')) return;
    $galleryController->deletePhoto($req);
});

// ── Client Gallery Routes ─────────────────────────────────────────────
$router->register('GET', '/api/gallery/meta', static function (Request $req) use ($authMiddleware, $galleryController): void {
    $claims = $authMiddleware->handle($req);
    if (!$claims) return;
    $galleryController->getMeta($claims);
});

$router->register('POST', '/api/gallery/unlock', static function (Request $req) use ($authMiddleware, $galleryController): void {
    $claims = $authMiddleware->handle($req);
    if (!$claims) return;
    $galleryController->unlock($req, $claims);
});

$router->dispatch($request);
