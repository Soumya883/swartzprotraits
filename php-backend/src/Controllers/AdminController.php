<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Database;
use App\Core\Response;

final class AdminController
{
    public function info(): void
    {
        $db = Database::connection();
        
        // Count form submissions
        $forms = $db->query('SELECT COUNT(*) FROM contact_submissions')->fetchColumn();
        
        // Count notifications
        $notifications = $db->query('SELECT COUNT(*) FROM notifications')->fetchColumn();

        // Count projects (mocking for orders)
        $orders = $db->query('SELECT COUNT(*) FROM projects')->fetchColumn();

        // Count Galleries
        $galleries = $db->query('SELECT COUNT(*) FROM galleries')->fetchColumn();

        // Count Appointments
        $appointments = $db->query('SELECT COUNT(*) FROM appointments')->fetchColumn();

        // Count Blog Posts
        $blog = $db->query('SELECT COUNT(*) FROM blog_posts')->fetchColumn();

        Response::success('Admin dashboard stats', [
            'stats' => [
                'forms' => (int)$forms,
                'orders' => (int)$orders,
                'notifications' => (int)$notifications,
                'galleries' => (int)$galleries,
                'appointments' => (int)$appointments,
                'blog' => (int)$blog,
                'conversations' => (int)$forms, // Mapping forms to conversations as seen in image
                'usage' => [
                    'forms' => [
                        'total' => 500,
                        'used' => (int)$forms,
                    ],
                    'emails' => [
                        'total' => 10000,
                        'used' => 0,
                    ],
                    'files' => [
                        'total' => 10000,
                        'used' => 5078,
                    ],
                    'ai' => [
                        'total' => 100,
                        'used' => 0,
                    ]
                ]
            ],
        ]);
    }
}
