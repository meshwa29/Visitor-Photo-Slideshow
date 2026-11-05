<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/functions.php';
require_admin();

const PER_PAGE = 12;

$status    = (string) ($_GET['status'] ?? 'all');
if (!in_array($status, ['all', 'pending', 'approved', 'rejected'], true)) {
    $status = 'all';
}
$visitorId = filter_var($_GET['visitor_id'] ?? null, FILTER_VALIDATE_INT) ?: null;
$page      = max(1, (int) filter_var($_GET['page'] ?? 1, FILTER_VALIDATE_INT, ['options' => ['default' => 1]]));

$where  = [];
$params = [];
if ($status !== 'all') {
    $where[]           = 'p.status = :status';
    $params[':status'] = $status;
}
if ($visitorId) {
    $where[]              = 'p.visitor_id = :vid';
    $params[':vid']       = $visitorId;
}
$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$pdo = db();
$cnt = $pdo->prepare("SELECT COUNT(*) FROM photos p $whereSql");
$cnt->execute($params);
$total      = (int) $cnt->fetchColumn();
$totalPages = max(1, (int) ceil($total / PER_PAGE));
$page       = min($page, $totalPages);
$offset     = ($page - 1) * PER_PAGE;

$stmt = $pdo->prepare(
    "SELECT p.id, p.photo_path, p.status, p.uploaded_at, v.id AS visitor_id, v.visitor_name
     FROM photos p INNER JOIN visitors v ON v.id = p.visitor_id
     $whereSql ORDER BY p.uploaded_at DESC, p.id DESC LIMIT " . PER_PAGE . " OFFSET " . $offset
);
$stmt->execute($params);
$photos = $stmt->fetchAll();

$visitorName = null;
if ($visitorId) {
    $vs = $pdo->prepare('SELECT visitor_name FROM visitors WHERE id = :id');
    $vs->execute([':id' => $visitorId]);
    $visitorName = $vs->fetchColumn() ?: null;
}

$query = function (array $override) use ($status, $visitorId, $page): string {
    $q = array_merge(['status' => $status, 'visitor_id' => $visitorId, 'page' => $page], $override);
    $q = array_filter($q, fn($v, $k) => $v !== null && $v !== '' && !($k === 'status' && $v === 'all') && !($k === 'page' && (int) $v === 1), ARRAY_FILTER_USE_BOTH);
    return 'photos.php' . ($q ? '?' . http_build_query($q) : '');
};
$returnTo = $query([]);

$pageTitle = 'Photos';
$activeNav = 'photos';
require __DIR__ . '/../includes/admin_header.php';

$tabs = ['all' => 'All', 'pending' => 'Pending', 'approved' => 'Approved', 'rejected' => 'Rejected'];
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <ul class="nav nav-pills filter-pills">
        <?php foreach ($tabs as $key => $label): ?>
            <li class="nav-item"><a class="nav-link <?= $status === $key ? 'active' : '' ?>" href="<?= e($query(['status' => $key, 'page' => 1])) ?>"><?= e($label) ?></a></li>
        <?php endforeach; ?>
    </ul>
    <span class="text-muted small"><?= $total ?> photo(s)</span>
</div>

<?php if ($visitorId): ?>
    <div class="alert alert-info d-flex justify-content-between align-items-center">
        <span><i class="bi bi-funnel me-1"></i>Showing photos of <strong><?= e($visitorName ?? 'unknown visitor') ?></strong></span>
        <a href="<?= e($query(['visitor_id' => null, 'page' => 1])) ?>" class="btn btn-sm btn-outline-primary">Clear filter</a>
    </div>
<?php endif; ?>

<?php if (!$photos): ?>
    <div class="panel"><div class="empty-state"><i class="bi bi-image"></i><p class="mb-0">No photos found for this filter.</p></div></div>
<?php else: ?>
    <div class="admin-gallery">
        <?php foreach ($photos as $p): ?>
            <div class="admin-photo">
                <button type="button" class="admin-photo-img" data-preview="../<?= e($p['photo_path']) ?>" data-caption="<?= e($p['visitor_name']) ?>">
                    <img src="../<?= e($p['photo_path']) ?>" alt="Photo by <?= e($p['visitor_name']) ?>" loading="lazy">
                    <span class="admin-photo-status"><?= status_badge($p['status']) ?></span>
                </button>
                <div class="admin-photo-body">
                    <a href="visitors.php?id=<?= (int) $p['visitor_id'] ?>" class="fw-semibold text-decoration-none d-block text-truncate"><?= e($p['visitor_name']) ?></a>
                    <div class="small text-muted mb-2"><?= e(format_date($p['uploaded_at'])) ?></div>
                    <?= photo_action_buttons((int) $p['id'], $p['status'], $returnTo) ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <?php if ($totalPages > 1): ?>
        <nav class="mt-4" aria-label="Photo pages">
            <ul class="pagination justify-content-center">
                <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>"><a class="page-link" href="<?= e($query(['page' => $page - 1])) ?>">Previous</a></li>
                <li class="page-item disabled"><span class="page-link">Page <?= $page ?> of <?= $totalPages ?></span></li>
                <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>"><a class="page-link" href="<?= e($query(['page' => $page + 1])) ?>">Next</a></li>
            </ul>
        </nav>
    <?php endif; ?>
<?php endif; ?>

<?php
$extraScripts = ['../assets/js/admin.js'];
require __DIR__ . '/../includes/admin_footer.php';
