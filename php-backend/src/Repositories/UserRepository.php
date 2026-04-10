<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

final class UserRepository
{
    public function __construct(private PDO $db) {}

    public function createUser(string $name, string $email, string $passwordHash, string $role = 'user'): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO users (name, email, password_hash, role, created_at) VALUES (:name, :email, :password_hash, :role, :created_at)'
        );
        $stmt->execute([
            ':name' => $name,
            ':email' => strtolower($email),
            ':password_hash' => $passwordHash,
            ':role' => $role,
            ':created_at' => date('c'),
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function findByEmail(string $email): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM users WHERE email = :email LIMIT 1');
        $stmt->execute([':email' => strtolower($email)]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT id, name, email, role, created_at FROM users WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function storeRefreshToken(int $userId, string $tokenHash, string $expiresAt): void
    {
        $stmt = $this->db->prepare(
            'INSERT INTO refresh_tokens (user_id, token_hash, expires_at, created_at, revoked_at) VALUES (:user_id, :token_hash, :expires_at, :created_at, NULL)'
        );
        $stmt->execute([
            ':user_id' => $userId,
            ':token_hash' => $tokenHash,
            ':expires_at' => $expiresAt,
            ':created_at' => date('c'),
        ]);
    }

    public function getActiveRefreshToken(string $tokenHash): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM refresh_tokens WHERE token_hash = :token_hash AND revoked_at IS NULL LIMIT 1'
        );
        $stmt->execute([':token_hash' => $tokenHash]);
        $row = $stmt->fetch();

        if (!$row) {
            return null;
        }

        if (strtotime((string) $row['expires_at']) < time()) {
            return null;
        }

        return $row;
    }

    public function revokeRefreshToken(int $id): void
    {
        $stmt = $this->db->prepare('UPDATE refresh_tokens SET revoked_at = :revoked_at WHERE id = :id');
        $stmt->execute([':revoked_at' => date('c'), ':id' => $id]);
    }
}
