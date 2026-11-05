<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/functions.php';
require_admin();

$stats = get_stats();

$pending = db()->query(
    "SELECT p.id, p.photo_path, p.uploaded_at, v.visitor_name
     FROM photos p INNER JOIN visitors v ON v.id = p.visitor_id
     WHERE p.status = 'pending' ORDER BY p.uploaded_at ASC LIMIT 6"
)->fetchAll();

$recentVisitors = db()->query(
    "SELECT v.id, v.visitor_name, v.visitor_code, v.created_at, COUNT(p.id) AS photo_count
     FROM visitors v LEFT JOIN photos p ON p.visitor_id = v.id
     GROUP BY v.id, v.visitor_name, v.visitor_code, v.created_at
     ORDER BY v.created_at DESC, v.id DESC LIMIT 5"
)->fetchAll();

$pageTitle = 'Dashboard';
$activeNav = 'dashboard';
require __DIR__ . '/../includes/admin_header.php';

$cards = [
    ['Total Visitors', $stats['visitors'] ?? 0, 'people', 'primary', 'visitors.php'],
    ['Total Photos', $stats['photos'] ?? 0, 'images', 'info', 'photos.php'],
    ['Pending Photos', $stats['pending'] ?? 0, 'hourglass-split', 'warning', 'photos.php?status=pending'],
    ['Approved Photos', $stats['approved'] ?? 0, 'check-circle', 'success', 'photos.php?status=approved'],
];
?>
<div class="row g-3 mb-4">
    <?php foreach ($cards as [$label, $value, $icon, $colour, $link]): ?>
        <div class="col-sm-6 col-xl-3">
            <a href="<?= e($link) ?>" class="stat-card stat-<?= e($colour) ?>">
                <div class="stat-icon"><i class="bi bi-<?= e($icon) ?>"></i></div>
                <div>
                    <div class="stat-value"><?= (int) $value ?></div>
                    <div class="stat-label"><?= e($label) ?></div>
                </div>
            </a>
        </div>
    <?php endforeach; ?>
</div>

<div class="panel mb-4">
    <div class="panel-head">
        <h2 class="h5 mb-0"><i class="bi bi-hourglass-split me-2"></i>Photos waiting for approval</h2>
        <a href="photos.php?status=pending" class="btn btn-sm btn-outline-primary rounded-pill">View all pending</a>
    </div>
    <?php if (!$pending): ?>
        <div class="empty-state small"><i class="bi bi-check2-circle"></i><p class="mb-0">All caught up &ndash; no photos are waiting for approval.</p></div>
    <?php else: ?>
        <div class="admin-gallery">
            <?php foreach ($pending as $p): ?>
                <div class="admin-photo">
                    <button type="button" class="admin-photo-img" data-preview="../<?= e($p['photo_path']) ?>" data-caption="<?= e($p['visitor_name']) ?>">
                        <img src="../<?= e($p['photo_path']) ?>" alt="Photo by <?= e($p['visitor_name']) ?>" loading="lazy">
                    </button>
                    <div class="admin-photo-body">
                        <div class="fw-semibold text-truncate"><?= e($p['visitor_name']) ?></div>
                        <div class="small text-muted mb-2"><?= e(format_date($p['uploaded_at'])) ?></div>
                        <?= photo_action_buttons((int) $p['id'], 'pending', 'dashboard.php') ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<div class="panel">
    <div class="panel-head">
        <h2 class="h5 mb-0"><i class="bi bi-people me-2"></i>Latest visitors</h2>
        <a href="visitors.php" class="btn btn-sm btn-outline-primary rounded-pill">All visitors</a>
    </div>
    <?php if (!$recentVisitors): ?>
        <div class="empty-state small"><i class="bi bi-person"></i><p class="mb-0">No visitors yet.</p></div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead><tr><th>Name</th><th>Visitor code</th><th>Photos</th><th>Registered</th></tr></thead>
                <tbody>
                <?php foreach ($recentVisitors as $v): ?>
                    <tr>
                        <td><a href="visitors.php?id=<?= (int) $v['id'] ?>" class="fw-semibold text-decoration-none"><?= e($v['visitor_name']) ?></a></td>
                        <td><code><?= e($v['visitor_code']) ?></code></td>
                        <td><?= (int) $v['photo_count'] ?></td>
                        <td><?= e(format_date($v['created_at'])) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php
$extraScripts = ['../assets/js/admin.js'];
require __DIR__ . '/../includes/admin_footer.php';
