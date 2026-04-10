<?php

declare(strict_types=1);

namespace App\Core;

final class Request
{
    public function method(): string
    {
        return strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
    }

    public function path(): string
    {
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        $path = parse_url($uri, PHP_URL_PATH);
        if (!is_string($path) || $path === '') {
            $path = '/';
        }
        $path = rawurldecode($path);
        
        // Remove trailing slash for consistency
        if ($path !== '/' && str_ends_with($path, '/')) {
            $path = rtrim($path, '/');
        }

        // Support subdirectories by finding where /api/ starts in the path
        if (($pos = strpos($path, '/api')) !== false) {
            $path = substr($path, $pos);
        }

        return $path;
    }

    public function body(): array
    {
        $contentType = $this->header('Content-Type') ?? '';
        
        // Handle multipart/form-data
        if (str_contains($contentType, 'multipart/form-data')) {
            return $_POST;
        }

        $raw = file_get_contents('php://input');
        if (!$raw) {
            return $_POST; // Fallback to $_POST if php://input is empty (standard forms)
        }

        $decoded = json_decode($raw, true);
        return is_array($decoded) ? $decoded : $_POST;
    }

    public function files(): array
    {
        return $_FILES;
    }

    public function header(string $name): ?string
    {
        $normalized = 'HTTP_' . strtoupper(str_replace('-', '_', $name));
        return $_SERVER[$normalized] ?? null;
    }

    public function bearerToken(): ?string
    {
        $auth = $this->header('Authorization');
        if (!$auth) {
            return null;
        }

        if (preg_match('/Bearer\s+(.+)/i', $auth, $matches) === 1) {
            return trim($matches[1]);
        }

        return null;
    }
}
