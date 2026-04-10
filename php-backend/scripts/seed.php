<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/bootstrap.php';

use App\Core\Database;

$db = Database::connection();

echo "Seeding database...\n";

// Clear existing data (optional, but good for consistent state)
$db->exec('DELETE FROM contact_submissions');
$db->exec('DELETE FROM notifications');
$db->exec('DELETE FROM projects');

// Seed Contact Submissions
$contacts = [
    ['John Doe', 'john@example.com', '555-0101', 'Looking for a wedding photoshoot in May.'],
    ['Jane Smith', 'jane@test.com', null, 'Interested in senior portraits for my daughter.'],
    ['Mike Ross', 'mike@pearson.com', '555-0202', 'Do you offer corporate headshots?'],
];

$stmt = $db->prepare('INSERT INTO contact_submissions (name, email, phone, message, created_at) VALUES (?, ?, ?, ?, ?)');
foreach ($contacts as $c) {
    $stmt->execute([...$c, date('c', strtotime('-' . rand(1, 30) . ' days'))]);
}
echo "Seeded " . count($contacts) . " contact submissions.\n";

// Seed Notifications
$notifications = [
    ['in_app', 'New Form Submission', 'John Doe just sent a message.'],
    ['in_app', 'System Upgrade', 'Your account has been upgraded to Premium.'],
    ['in_app', 'Payment Received', 'Invoice #1234 has been paid.'],
];

$stmt = $db->prepare('INSERT INTO notifications (user_id, channel, title, body, created_at) VALUES (?, ?, ?, ?, ?)');
foreach ($notifications as $n) {
    $stmt->execute([1, ...$n, date('c')]);
}
echo "Seeded " . count($notifications) . " notifications.\n";

// Seed Projects (Orders)
$projects = [
    ['Wedding Bliss', 'Full wedding coverage'],
    ['Senior Portraits 2026', 'School portraits'],
];
$stmt = $db->prepare('INSERT INTO projects (organization_id, name, description, created_by, created_at) VALUES (?, ?, ?, ?, ?)');
foreach ($projects as $p) {
    $stmt->execute([1, ...$p, 1, date('c')]);
}
echo "Seeded " . count($projects) . " projects.\n";

// Seed Galleries
$galleries = [
    ['European Summer', 'pic1.jpg'],
    ['Sunset Dreams', 'pic2.jpg'],
];
$stmt = $db->prepare('INSERT INTO galleries (organization_id, name, cover_image_url, created_at) VALUES (?, ?, ?, ?)');
foreach ($galleries as $g) {
    $stmt->execute([1, ...$g, date('c')]);
}
echo "Seeded " . count($galleries) . " galleries.\n";

// Seed Appointments
$appointments = [
    ['Senior Session - Ty Swartz', date('c', strtotime('+1 day')), date('c', strtotime('+2 hours'))],
    ['Wedding Consultation', date('c', strtotime('+3 days')), date('c', strtotime('+3 days +1 hour'))],
];
$stmt = $db->prepare('INSERT INTO appointments (user_id, title, start_time, end_time, created_at) VALUES (?, ?, ?, ?, ?)');
foreach ($appointments as $a) {
    $stmt->execute([1, ...$a, date('c')]);
}
echo "Seeded " . count($appointments) . " appointments.\n";

// Seed Blog Posts
$blog = [
    ['New Portrait Studio Opening', 'studio-opening', 'We are excited to announce...', 'thumb1.jpg'],
    ['Top 10 Senior Portrait Tips', 'portrait-tips', 'Planning your shoot is key...', 'thumb2.jpg'],
];
$stmt = $db->prepare('INSERT INTO blog_posts (title, slug, content, thumbnail_url, is_published, published_at, created_at) VALUES (?, ?, ?, ?, ?, ?, ?)');
foreach ($blog as $b) {
    $stmt->execute([...$b, 1, date('c'), date('c')]);
}
echo "Seeded " . count($blog) . " blog posts.\n";

echo "Done!\n";
