<?php

declare(strict_types=1);

namespace App\Auth;

use App\Repositories\UserRepository;
use RuntimeException;

final class AuthService
{
    public function __construct(
        private UserRepository $users,
        private PasswordService $passwords,
        private JwtService $jwt,
        private int $accessTtl,
        private int $refreshTtl
    ) {}

    public function register(string $name, string $email, string $password): array
    {
        if (strlen($password) < 8) {
            throw new RuntimeException('Password must be at least 8 characters.');
        }

        $existing = $this->users->findByEmail($email);
        if ($existing) {
            throw new RuntimeException('Email already registered.');
        }

        $userId = $this->users->createUser($name, $email, $this->passwords->hash($password), 'user');
        return $this->issueSession($userId, $name, strtolower($email), 'user');
    }

    public function login(string $email, string $password): array
    {
        $user = $this->users->findByEmail($email);
        if (!$user || !$this->passwords->verify($password, (string) $user['password_hash'])) {
            throw new RuntimeException('Invalid credentials.');
        }

        return $this->issueSession((int) $user['id'], (string) $user['name'], (string) $user['email'], (string) $user['role']);
    }

    public function refresh(string $refreshToken): array
    {
        $tokenHash = hash('sha256', $refreshToken);
        $stored = $this->users->getActiveRefreshToken($tokenHash);
        if (!$stored) {
            throw new RuntimeException('Invalid refresh token.');
        }

        $this->users->revokeRefreshToken((int) $stored['id']);
        $user = $this->users->findById((int) $stored['user_id']);
        if (!$user) {
            throw new RuntimeException('User not found.');
        }

        return $this->issueSession((int) $user['id'], (string) $user['name'], (string) $user['email'], (string) $user['role']);
    }

    private function issueSession(int $userId, string $name, string $email, string $role): array
    {
        $accessToken = $this->jwt->issue([
            'sub' => $userId,
            'email' => $email,
            'role' => $role,
            'type' => 'access',
        ], $this->accessTtl);

        $refreshToken = bin2hex(random_bytes(48));
        $refreshExpiry = date('c', time() + $this->refreshTtl);
        $this->users->storeRefreshToken($userId, hash('sha256', $refreshToken), $refreshExpiry);

        return [
            'access_token' => $accessToken,
            'refresh_token' => $refreshToken,
            'token_type' => 'Bearer',
            'expires_in' => $this->accessTtl,
            'user' => [
                'id' => $userId,
                'name' => $name,
                'email' => $email,
                'role' => $role,
            ],
        ];
    }
}
