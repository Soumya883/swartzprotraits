<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Auth\AuthorizationService;
use App\Core\Response;

final class RoleMiddleware
{
    public function __construct(private AuthorizationService $authorization) {}

    public function handle(array $claims, string $requiredRole): bool
    {
        if (!$this->authorization->canAccessRole($claims, $requiredRole)) {
            Response::error('Forbidden. Insufficient role.', 403);
            return false;
        }

        return true;
    }
}
