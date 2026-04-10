<?php

declare(strict_types=1);

namespace App\Auth;

final class AuthorizationService
{
    public function canAccessRole(array $userClaims, string $requiredRole): bool
    {
        $roleHierarchy = [
            'user' => 1,
            'manager' => 2,
            'admin' => 3,
        ];

        $current = $roleHierarchy[$userClaims['role'] ?? 'user'] ?? 0;
        $required = $roleHierarchy[$requiredRole] ?? PHP_INT_MAX;

        return $current >= $required;
    }
}
