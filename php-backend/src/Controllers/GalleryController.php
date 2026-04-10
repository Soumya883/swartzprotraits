<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Auth\PasswordService;
use App\Core\Request;
use App\Core\Response;
use App\Repositories\GalleryRepository;

final class GalleryController
{
    public function __construct(
        private GalleryRepository $galleries,
        private PasswordService $passwords
    ) {}

    // ── GET /api/admin/gallery/clients ─────────────────────────────────
    public function listClients(): void
    {
        $clients = $this->galleries->listClients();
        Response::success('Client list', ['clients' => $clients]);
    }

    // ── GET /api/admin/gallery/photos?user_id=X ────────────────────────
    public function listPhotos(Request $req): void
    {
        $userId = (int) ($_GET['user_id'] ?? 0);
        if ($userId <= 0) {
            Response::error('user_id is required', 422);
            return;
        }

        $photos = $this->galleries->getPhotos($userId);
        Response::success('Client photos', ['photos' => $photos]);
    }

    // ── POST /api/admin/gallery/password ───────────────────────────────
    // Body: { user_id, password }
    public function setPassword(Request $req): void
    {
        $body = $req->body();
        $userId   = isset($body['user_id'])  ? (int) $body['user_id']  : 0;
        $password = $body['password'] ?? '';

        if ($userId <= 0 || trim($password) === '') {
            Response::error('user_id and password are required', 422);
            return;
        }

        $hash = $this->passwords->hash($password);
        $this->galleries->setGalleryPassword($userId, $hash);
        Response::success('Gallery password updated');
    }

    // ── POST /api/admin/gallery/photo ──────────────────────────────────
    // Body: { user_id, url, caption? }
    public function addPhoto(Request $req, array $claims): void
    {
        $body    = $req->body();
        $userId  = isset($body['user_id']) ? (int) $body['user_id'] : 0;
        $url     = $body['url']     ?? '';
        $caption = $body['caption'] ?? '';

        if ($userId <= 0 || trim($url) === '') {
            Response::error('user_id and url are required', 422);
            return;
        }

        $photoId = $this->galleries->addPhoto($userId, $url, $caption, (int) $claims['sub']);
        Response::success('Photo added', ['photo_id' => $photoId]);
    }

    // ── DELETE /api/admin/gallery/photo ───────────────────────────────
    // Body: { photo_id }
    public function deletePhoto(Request $req): void
    {
        $body    = $req->body();
        $photoId = isset($body['photo_id']) ? (int) $body['photo_id'] : 0;

        if ($photoId <= 0) {
            Response::error('photo_id is required', 422);
            return;
        }

        $this->galleries->deletePhoto($photoId);
        Response::success('Photo deleted');
    }

    // ── GET /api/gallery/meta ─────────────────────────────────────────
    public function getMeta(array $claims): void
    {
        $userId = (int) $claims['sub'];
        $meta   = $this->galleries->getGalleryMeta($userId);

        if (!$meta) {
            Response::success('No gallery yet', ['gallery' => null]);
            return;
        }

        Response::success('Gallery meta', ['gallery' => $meta]);
    }

    // ── POST /api/gallery/unlock ──────────────────────────────────────
    // Body: { password }
    public function unlock(Request $req, array $claims): void
    {
        $userId   = (int) $claims['sub'];
        $password = $req->body()['password'] ?? '';

        $hash = $this->galleries->getPasswordHash($userId);

        if ($hash === null) {
            Response::error('No gallery found', 404);
            return;
        }

        if (!$this->passwords->verify($password, $hash)) {
            Response::error('Incorrect gallery password', 401);
            return;
        }

        $photos = $this->galleries->getPhotos($userId);
        Response::success('Gallery unlocked', ['photos' => $photos]);
    }

    // ── POST /api/admin/gallery/upload-folder ──────────────────────────
    // Body: multipart/form-data (user_id, photos[])
    public function uploadPhotosBatch(Request $req, array $claims): void
    {
        $userId = (int) ($req->body()['user_id'] ?? 0);
        $files  = $req->files()['photos'] ?? null;

        if ($userId <= 0 || !$files || !isset($files['name']) || !is_array($files['name'])) {
            Response::error('user_id and photos folder are required', 422);
            return;
        }

        // ── 1. Preparation ───────────────────────────────────────────
        $baseDir = __DIR__ . '/../../../pictures/uploads';
        $clientDir = $baseDir . '/client_' . $userId;

        if (!is_dir($baseDir)) {
            mkdir($baseDir, 0777, true);
        }
        if (!is_dir($clientDir)) {
            mkdir($clientDir, 0777, true);
        }

        $results = [
            'success' => 0,
            'failed'  => 0,
            'errors'  => []
        ];

        // ── 2. Process Files ─────────────────────────────────────────
        foreach ($files['name'] as $i => $originalName) {
            $tmpPath = $files['tmp_name'][$i];
            $error   = $files['error'][$i];
            $size    = $files['size'][$i];
            
            if ($error !== UPLOAD_ERR_OK) {
                $results['failed']++;
                $results['errors'][] = "Upload error for $originalName: $error";
                continue;
            }

            // Validation: Size (5MB)
            if ($size > 5 * 1024 * 1024) {
                $results['failed']++;
                $results['errors'][] = "$originalName is too large (>5MB)";
                continue;
            }

            // Validation: Type
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_file($finfo, $tmpPath);
            finfo_close($finfo);

            $allowed = ['image/jpeg', 'image/png', 'image/jpg', 'image/webp'];
            if (!in_array($mimeType, $allowed)) {
                $results['failed']++;
                $results['errors'][] = "$originalName is not a supported image type ($mimeType)";
                continue;
            }

            // Filename sanitation & duplicate avoidance
            $ext = pathinfo($originalName, PATHINFO_EXTENSION);
            $cleanName = preg_replace('/[^A-Za-z0-9_\-\.]/', '_', pathinfo($originalName, PATHINFO_FILENAME));
            $finalPath = $clientDir . '/' . $cleanName . '.' . $ext;

            if (file_exists($finalPath)) {
                $finalPath = $clientDir . '/' . $cleanName . '_' . time() . '_' . $i . '.' . $ext;
            }

            if (move_uploaded_file($tmpPath, $finalPath)) {
                // Determine relative URL for the browser
                // The router serves from project root, so we want "/pictures/uploads/client_X/..."
                $relativeUrl = '/pictures/uploads/client_' . $userId . '/' . basename($finalPath);
                
                $this->galleries->addPhoto($userId, $relativeUrl, $originalName, (int) $claims['sub']);
                $results['success']++;
            } else {
                $results['failed']++;
                $results['errors'][] = "Failed to move $originalName to destination";
            }
        }

        Response::success('Bulk upload complete', $results);
    }
}
