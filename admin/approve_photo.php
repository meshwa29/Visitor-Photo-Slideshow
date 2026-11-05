<?php
declare(strict_types=1);
/**
 * Handles approve / reject / delete for ONE photo. POST only, admin only, CSRF protected.
 */
require_once __DIR__ . '/../includes/functions.php';
require_admin();

/** Only allow redirects back to our own admin pages. */
function safe_return(string $target): string
{
    return preg_match('/^(dashboard|photos|visitors)\.php(\?[A-Za-z0-9_=&]*)?$/', $target) ? $target : 'photos.php';
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('photos.php');
}

$return = safe_return((string) ($_POST['return'] ?? 'photos.php'));

if (!csrf_valid()) {
    flash('danger', 'Security check failed. Please try again.');
    redirect($return);
}

$photoId = filter_var($_POST['photo_id'] ?? null, FILTER_VALIDATE_INT);
$action  = (string) ($_POST['action'] ?? '');

if (!$photoId || !in_array($action, ['approve', 'reject', 'delete'], true)) {
    flash('danger', 'Invalid request.');
    redirect($return);
}

$pdo  = db();
$stmt = $pdo->prepare('SELECT id, photo_name FROM photos WHERE id = :id');
$stmt->execute([':id' => $photoId]);
$photo = $stmt->fetch();

if (!$photo) {
    flash('warning', 'That photo no longer exists.');
    redirect($return);
}

if ($action === 'delete') {
    $del = $pdo->prepare('DELETE FROM photos WHERE id = :id');
    $del->execute([':id' => $photoId]);
    delete_photo_file($photo['photo_name']);
    flash('success', 'Photo deleted.');
} else {
    $newStatus = $action === 'approve' ? 'approved' : 'rejected';
    $upd = $pdo->prepare('UPDATE photos SET status = :s WHERE id = :id');
    $upd->execute([':s' => $newStatus, ':id' => $photoId]);
    flash('success', 'Photo ' . $newStatus . '.');
}

redirect($return);
