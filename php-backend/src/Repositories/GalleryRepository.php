<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

final class GalleryRepository
{
    public function __construct(private PDO $db) {}

    // ── Admin: list all clients with galleries ─────────────────────────
    public function listClients(): array
    {
        $stmt = $this->db->query(
            "SELECT u.id, u.name, u.email,
                    cg.id AS gallery_id,
                    cg.title,
                    cg.gallery_password_hash,
                    (SELECT COUNT(*) FROM client_gallery_photos cgp WHERE cgp.user_id = u.id) AS photo_count
             FROM users u
             LEFT JOIN client_galleries cg ON cg.user_id = u.id
             WHERE u.role = 'user'
             ORDER BY u.created_at DESC"
        );
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // ── Admin: set or update gallery password for a client ─────────────
    public function setGalleryPassword(int $userId, string $passwordHash): void
    {
        $this->db->prepare(
            "INSERT INTO client_galleries (user_id, gallery_password_hash, title, updated_at)
             VALUES (:uid, :hash, 'My Portraits', CURRENT_TIMESTAMP)
             ON CONFLICT(user_id) DO UPDATE SET gallery_password_hash = :hash, updated_at = CURRENT_TIMESTAMP"
        )->execute([':uid' => $userId, ':hash' => $passwordHash]);
    }

    // ── Admin: add a photo URL to a client's gallery ───────────────────
    public function addPhoto(int $userId, string $url, string $caption, int $uploadedBy): int
    {
        // Ensure gallery row exists
        $this->db->prepare(
            "INSERT OR IGNORE INTO client_galleries (user_id, title) VALUES (:uid, 'My Portraits')"
        )->execute([':uid' => $userId]);

        $stmt = $this->db->prepare(
            "INSERT INTO client_gallery_photos (user_id, url, caption, uploaded_by)
             VALUES (:uid, :url, :caption, :by)"
        );
        $stmt->execute([':uid' => $userId, ':url' => $url, ':caption' => $caption, ':by' => $uploadedBy]);
        return (int) $this->db->lastInsertId();
    }

    // ── Admin: remove a photo ──────────────────────────────────────────
    public function deletePhoto(int $photoId): void
    {
        $this->db->prepare("DELETE FROM client_gallery_photos WHERE id = :id")
            ->execute([':id' => $photoId]);
    }

    // ── Client: get gallery metadata (no password hash exposed) ────────
    public function getGalleryMeta(int $userId): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT id, title, gallery_password_hash IS NOT NULL AS has_password
             FROM client_galleries WHERE user_id = :uid LIMIT 1"
        );
        $stmt->execute([':uid' => $userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    // ── Client: verify gallery password ────────────────────────────────
    public function getPasswordHash(int $userId): ?string
    {
        $stmt = $this->db->prepare(
            "SELECT gallery_password_hash FROM client_galleries WHERE user_id = :uid LIMIT 1"
        );
        $stmt->execute([':uid' => $userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? (string) ($row['gallery_password_hash'] ?? '') : null;
    }

    // ── Client: get photos for a gallery ───────────────────────────────
    public function getPhotos(int $userId): array
    {
        $stmt = $this->db->prepare(
            "SELECT id, url, caption, sort_order, created_at
             FROM client_gallery_photos WHERE user_id = :uid ORDER BY sort_order, created_at"
        );
        $stmt->execute([':uid' => $userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
