<?php

declare(strict_types=1);

$sqlitePath = getenv('DB_SQLITE_PATH') ?: __DIR__ . '/../../database/app.db';
if (!str_contains($sqlitePath, ':') && !str_starts_with($sqlitePath, DIRECTORY_SEPARATOR)) {
    $sqlitePath = __DIR__ . '/../../' . ltrim($sqlitePath, '/\\');
}

return [
    'app' => [
        'name' => getenv('APP_NAME') ?: 'PHP Auth API',
        'env' => getenv('APP_ENV') ?: 'local',
        'debug' => (bool) (getenv('APP_DEBUG') ?: true),
        'base_url' => getenv('APP_BASE_URL') ?: 'http://localhost:8000',
    ],
    'db' => [
        'driver' => getenv('DB_DRIVER') ?: 'sqlite',
        'sqlite_path' => $sqlitePath,
        'host' => getenv('DB_HOST') ?: '127.0.0.1',
        'port' => getenv('DB_PORT') ?: '3306',
        'database' => getenv('DB_DATABASE') ?: 'auth_api',
        'username' => getenv('DB_USERNAME') ?: 'root',
        'password' => getenv('DB_PASSWORD') ?: '',
    ],
    'auth' => [
        'jwt_secret' => getenv('JWT_SECRET') ?: 'change_me_to_a_random_secret',
        'jwt_issuer' => getenv('JWT_ISSUER') ?: 'php-auth-api',
        'jwt_access_ttl' => (int) (getenv('JWT_ACCESS_TTL') ?: 900),
        'jwt_refresh_ttl' => (int) (getenv('JWT_REFRESH_TTL') ?: 604800),
    ],
];
