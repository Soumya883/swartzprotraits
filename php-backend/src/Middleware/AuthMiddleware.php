<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Auth\JwtService;
use App\Core\Request;
use App\Core\Response;
use RuntimeException;

final class AuthMiddleware
{
    public function __construct(private JwtService $jwt) {}

    public function handle(Request $request): ?array
    {
        $token = $request->bearerToken();
        if (!$token) {
            Response::error('Missing bearer token', 401);
            return null;
        }

        try {
            $claims = $this->jwt->verify($token);
            if (($claims['type'] ?? '') !== 'access') {
                throw new RuntimeException('Invalid token type');
            }
            return $claims;
        } catch (RuntimeException $e) {
            Response::error('Unauthorized', 401, ['reason' => $e->getMessage()]);
            return null;
        }
    }
}
