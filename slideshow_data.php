<?php
declare(strict_types=1);
/**
 * JSON feed for the slideshow. Returns ONLY admin-approved photos.
 * The slideshow page polls this file so new approvals appear automatically.
 */
require_once __DIR__ . '/includes/functions.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate');

try {
    $rows = db()->query(
        "SELECT p.id, p.photo_path, p.uploaded_at, v.visitor_name
         FROM photos p
         INNER JOIN visitors v ON v.id = p.visitor_id
         WHERE p.status = 'approved'
         ORDER BY p.uploaded_at ASC, p.id ASC"
    )->fetchAll();

    $photos = [];
    foreach ($rows as $r) {
        $photos[] = [
            'id'      => (int) $r['id'],
            'url'     => $r['photo_path'],
            'name'    => $r['visitor_name'],
            'date'    => date('d M Y', strtotime((string) $r['uploaded_at'])),
        ];
    }
    echo json_encode(['ok' => true, 'photos' => $photos], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
} catch (Throwable $e) {
    error_log('[VisitorPhotoSlideshow] slideshow_data: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['ok' => false, 'photos' => []]);
}
