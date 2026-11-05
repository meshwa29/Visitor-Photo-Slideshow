<?php
declare(strict_types=1);
/** Deletes a visitor and ALL of their photos (database rows and files). POST only. */
require_once __DIR__ . '/../includes/functions.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_valid()) {
    flash('danger', 'Security check failed. Please try again.');
    redirect('visitors.php');
}

$visitorId = filter_var($_POST['visitor_id'] ?? null, FILTER_VALIDATE_INT);
if (!$visitorId) {
    flash('danger', 'Invalid visitor.');
    redirect('visitors.php');
}

$pdo = db();
$sel = $pdo->prepare('SELECT photo_name FROM photos WHERE visitor_id = :id');
$sel->execute([':id' => $visitorId]);
$files = $sel->fetchAll(PDO::FETCH_COLUMN);

$del = $pdo->prepare('DELETE FROM visitors WHERE id = :id');   // photos rows are removed by ON DELETE CASCADE
$del->execute([':id' => $visitorId]);

if ($del->rowCount() > 0) {
    foreach ($files as $name) {
        delete_photo_file((string) $name);
    }
    flash('success', 'Visitor and ' . count($files) . ' photo(s) deleted.');
} else {
    flash('warning', 'That visitor no longer exists.');
}
redirect('visitors.php');
