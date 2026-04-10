<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Database;
use App\Core\Request;
use App\Core\Response;

final class ContactController
{
    public function submit(Request $request): void
    {
        $body = $request->body();
        $name = trim((string) ($body['name'] ?? ''));
        $email = trim((string) ($body['email'] ?? ''));
        $phone = trim((string) ($body['phone'] ?? ''));
        $message = trim((string) ($body['message'] ?? ''));

        if (strlen($name) < 2) {
            Response::error('Please enter your name.', 422);
            return;
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            Response::error('Please enter a valid email address.', 422);
            return;
        }
        if (strlen($message) < 10) {
            Response::error('Please write a message (at least 10 characters).', 422);
            return;
        }

        $db = Database::connection();
        $stmt = $db->prepare(
            'INSERT INTO contact_submissions (name, email, phone, message, created_at)
             VALUES (:name, :email, :phone, :message, :created_at)'
        );
        $stmt->execute([
            ':name' => $name,
            ':email' => strtolower($email),
            ':phone' => $phone !== '' ? $phone : null,
            ':message' => $message,
            ':created_at' => date('c'),
        ]);

        Response::success('Thank you. We will get back to you soon.', [
            'id' => (int) $db->lastInsertId(),
        ], 201);
    }
    public function listSubmissions(): void
    {
        $db = Database::connection();
        $stmt = $db->query('SELECT * FROM contact_submissions ORDER BY created_at DESC');
        $submissions = $stmt->fetchAll();

        Response::success('Contact submissions retrieved', [
            'submissions' => $submissions,
        ]);
    }
}
