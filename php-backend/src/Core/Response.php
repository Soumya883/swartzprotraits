<?php

declare(strict_types=1);

namespace App\Core;

final class Response
{
    public static function success(string $message, array $data = [], int $status = 200, array $meta = []): void
    {
        self::json([
            'success' => true,
            'message' => $message,
            'data' => $data,
            'meta' => array_merge([
                'status' => $status,
                'timestamp' => gmdate('c'),
            ], $meta),
        ], $status);
    }

    public static function error(string $message, int $status = 400, array $errors = []): void
    {
        self::json([
            'success' => false,
            'message' => $message,
            'errors' => $errors,
            'meta' => [
                'status' => $status,
                'timestamp' => gmdate('c'),
            ],
        ], $status);
    }

    public static function json(array $payload, int $status = 200): void
    {
        http_response_code($status);
        
        // Production Security Headers
        header('Content-Type: application/json');
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: DENY');
        header('X-XSS-Protection: 1; mode=block');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
        header('Content-Security-Policy: default-src \'none\'; frame-ancestors \'none\';');
        
        echo json_encode($payload, JSON_UNESCAPED_SLASHES);
    }
}
