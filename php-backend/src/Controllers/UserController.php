<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Response;

final class UserController
{
    public function profile(array $claims): void
    {
        Response::success('Profile fetched successfully', [
            'id' => $claims['sub'] ?? null,
            'email' => $claims['email'] ?? null,
            'role' => $claims['role'] ?? null,
        ]);
    }
}
