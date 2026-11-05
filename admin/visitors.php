<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/functions.php';
require_admin();

$pdo       = db();
$visitorId = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT) ?: null;

// ------------------------------------------------------------ one visitor
if ($visitorId) {
    $stmt = $pdo->prepare('SELECT id, visitor_code, visitor_name, created_at FROM visitors WHERE id = :id');
    $stmt->execute([':id' => $visitorId]);
    $visitor = $stmt->fetch();

    if (!$visitor) {
        flash('warning', 'Visitor not found.');
        redirect('visitors.php');
    }

    $ph = $pdo->prepare('SELECT id, photo_path, status, uploaded_at FROM photos WHERE visitor_id = :id ORDER BY uploaded_at DESC, id DESC');
    $ph->execute([':id' => $visitorId]);
    $photos = $ph->fetchAll();

    $counts = ['pending' => 0, 'approved' => 0, 'rejected' => 0];
    foreach ($photos as $p) {
        $counts[$p['status']]++;
    }
    $returnTo = 'visitors.php?id=' . $visitorId;

    $pageTitle = 'Visitor Details';
    $activeNav = 'visitors';
    require __DIR__ . '/../includes/admin_header.php';
    ?>
    <a href="visitors.php" class="btn btn-sm btn-outline-secondary rounded-pill mb-3"><i class="bi bi-arrow-left me-1"></i>All visitors</a>
    <div class="panel mb-4">
        <div class="row g-3 align-items-center">
            <div class="col-md">
                <h2 class="h4 mb-1"><?= e($visitor['visitor_name']) ?></h2>
                <div class="text-muted">Visitor code: <code><?= e($visitor['visitor_code']) ?></code></div>
                <div class="text-muted">Registered: <?= e(format_date($visitor['created_at'])) ?></div>
                <div class="mt-2 d-flex flex-wrap gap-2">
                    <span class="badge badge-soft-secondary"><?= count($photos) ?> total</span>
                    <span class="badge badge-soft-success"><?= $counts['approved'] ?> approved</span>
                    <span class="badge badge-soft-warning"><?= $counts['pending'] ?> pending</span>
                    <span class="badge badge-soft-danger"><?= $counts['rejected'] ?> rejected</span>
                </div>
            </div>
            <div class="col-md-auto">
                <form method="post" action="delete_visitor.php" data-confirm="Delete this visitor and ALL of their photos permanently?">
                    <?= csrf_field() ?>
                    <input type="hidden" name="visitor_id" value="<?= (int) $visitor['id'] ?>">
                    <button type="submit" class="btn btn-outline-danger"><i class="bi bi-trash me-1"></i>Delete visitor</button>
                </form>
            </div>
        </div>
    </div>

    <?php if (!$photos): ?>
        <div class="panel"><div class="empty-state"><i class="bi bi-image"></i><p class="mb-0">This visitor has no photos.</p></div></div>
    <?php else: ?>
        <div class="admin-gallery">
            <?php foreach ($photos as $p): ?>
                <div class="admin-photo">
                    <button type="button" class="admin-photo-img" data-preview="../<?= e($p['photo_path']) ?>" data-caption="<?= e($visitor['visitor_name']) ?>">
                        <img src="../<?= e($p['photo_path']) ?>" alt="Photo by <?= e($visitor['visitor_name']) ?>" loading="lazy">
                        <span class="admin-photo-status"><?= status_badge($p['status']) ?></span>
                    </button>
                    <div class="admin-photo-body">
                        <div class="small text-muted mb-2"><?= e(format_date($p['uploaded_at'])) ?></div>
                        <?= photo_action_buttons((int) $p['id'], $p['status'], $returnTo) ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    <?php
    $extraScripts = ['../assets/js/admin.js'];
    require __DIR__ . '/../includes/admin_footer.php';
    exit;
}

// ----------------------------------------------------------- visitor list
$search = trim((string) ($_GET['q'] ?? ''));
$sql    = "SELECT v.id, v.visitor_name, v.visitor_code, v.created_at,
                  COUNT(p.id) AS total_photos,
                  SUM(p.status = 'approved') AS approved_photos,
                  SUM(p.status = 'pending')  AS pending_photos
           FROM visitors v LEFT JOIN photos p ON p.visitor_id = v.id";
$params = [];
if ($search !== '') {
    $sql .= ' WHERE v.visitor_name LIKE :q OR v.visitor_code LIKE :q2';
    $like = '%' . addcslashes($search, '%_\\') . '%';
    $params = [':q' => $like, ':q2' => $like];
}
$sql .= ' GROUP BY v.id, v.visitor_name, v.visitor_code, v.created_at ORDER BY v.created_at DESC, v.id DESC';
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$visitors = $stmt->fetchAll();

$pageTitle = 'Visitors';
$activeNav = 'visitors';
require __DIR__ . '/../includes/admin_header.php';
?>
<div class="panel">
    <form method="get" action="visitors.php" class="row g-2 mb-3">
        <div class="col-sm">
            <input type="search" name="q" class="form-control" placeholder="Search by name or visitor code" value="<?= e($search) ?>">
        </div>
        <div class="col-sm-auto d-flex gap-2">
            <button class="btn btn-primary" type="submit"><i class="bi bi-search me-1"></i>Search</button>
            <?php if ($search !== ''): ?><a href="visitors.php" class="btn btn-outline-secondary">Clear</a><?php endif; ?>
        </div>
    </form>

    <?php if (!$visitors): ?>
        <div class="empty-state"><i class="bi bi-people"></i><p class="mb-0">No visitors found.</p></div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr><th>#</th><th>Name</th><th>Visitor code</th><th class="text-center">Photos</th><th class="text-center">Approved</th><th class="text-center">Pending</th><th>Registered</th><th class="text-end">Actions</th></tr>
                </thead>
                <tbody>
                <?php foreach ($visitors as $v): ?>
                    <tr>
                        <td><?= (int) $v['id'] ?></td>
                        <td class="fw-semibold"><?= e($v['visitor_name']) ?></td>
                        <td><code><?= e($v['visitor_code']) ?></code></td>
                        <td class="text-center"><?= (int) $v['total_photos'] ?></td>
                        <td class="text-center"><?= (int) $v['approved_photos'] ?></td>
                        <td class="text-center"><?= (int) $v['pending_photos'] ?></td>
                        <td><?= e(format_date($v['created_at'])) ?></td>
                        <td class="text-end text-nowrap">
                            <a href="visitors.php?id=<?= (int) $v['id'] ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i> View</a>
                            <a href="photos.php?visitor_id=<?= (int) $v['id'] ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-images"></i> Photos</a>
                            <form method="post" action="delete_visitor.php" class="d-inline" data-confirm="Delete this visitor and ALL of their photos permanently?">
                                <?= csrf_field() ?>
                                <input type="hidden" name="visitor_id" value="<?= (int) $v['id'] ?>">
                                <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i> Delete</button>
                            </form>
                        </td>
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
