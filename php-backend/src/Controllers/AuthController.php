<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Auth\AuthService;
use App\Core\Request;
use App\Core\Response;
use RuntimeException;

final class AuthController
{
    public function __construct(private AuthService $authService) {}

    public function register(Request $request): void
    {
        $body = $request->body();

        try {
            $tokens = $this->authService->register(
                trim((string) ($body['name'] ?? '')),
                trim((string) ($body['email'] ?? '')),
                (string) ($body['password'] ?? '')
            );
            Response::success('Registration successful', $tokens, 201);
        } catch (RuntimeException $e) {
            Response::error($e->getMessage(), 422);
        }
    }

    public function login(Request $request): void
    {
        $body = $request->body();

        try {
            $tokens = $this->authService->login(
                trim((string) ($body['email'] ?? '')),
                (string) ($body['password'] ?? '')
            );
            Response::success('Login successful', $tokens);
        } catch (RuntimeException $e) {
            Response::error($e->getMessage(), 401);
        }
    }

    public function refresh(Request $request): void
    {
        $body = $request->body();

        try {
            $tokens = $this->authService->refresh((string) ($body['refresh_token'] ?? ''));
            Response::success('Token refreshed', $tokens);
        } catch (RuntimeException $e) {
            Response::error($e->getMessage(), 401);
        }
    }
}
