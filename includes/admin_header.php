<?php
/**
 * Admin layout header (sidebar). Used only by pages inside /admin.
 * Set before including: $pageTitle, $activeNav (dashboard|visitors|photos)
 */
$base      = '../';
$pageTitle = $pageTitle ?? 'Admin';
$activeNav = $activeNav ?? '';
$pendingCount = 0;
try {
    $pendingCount = (int) db()->query("SELECT COUNT(*) FROM photos WHERE status = 'pending'")->fetchColumn();
} catch (Throwable $e) {
    $pendingCount = 0;
}
$adminNav = [
    'dashboard' => ['dashboard.php', 'speedometer2', 'Dashboard'],
    'visitors'  => ['visitors.php', 'people', 'Visitors'],
    'photos'    => ['photos.php', 'images', 'Photos'],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($pageTitle) ?> | Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
</head>
<body class="admin-body">
<div class="admin-shell">
    <aside class="admin-sidebar">
        <a class="admin-brand" href="dashboard.php"><i class="bi bi-camera-fill me-2"></i>Photo Admin</a>
        <nav class="admin-menu">
            <?php foreach ($adminNav as $key => [$href, $icon, $label]): ?>
                <a href="<?= e($href) ?>" class="<?= $activeNav === $key ? 'active' : '' ?>">
                    <i class="bi bi-<?= e($icon) ?>"></i><span><?= e($label) ?></span>
                    <?php if ($key === 'photos' && $pendingCount > 0): ?>
                        <span class="badge rounded-pill bg-warning text-dark ms-auto"><?= $pendingCount ?></span>
                    <?php endif; ?>
                </a>
            <?php endforeach; ?>
            <a href="../slideshow.php" target="_blank" rel="noopener"><i class="bi bi-play-btn"></i><span>View Slideshow</span></a>
            <a href="../index.php" target="_blank" rel="noopener"><i class="bi bi-globe2"></i><span>Public Site</span></a>
            <a href="logout.php" class="logout-link"><i class="bi bi-box-arrow-right"></i><span>Logout</span></a>
        </nav>
        <div class="admin-user"><i class="bi bi-person-circle me-1"></i><?= e($_SESSION['admin_username'] ?? 'admin') ?></div>
    </aside>
    <div class="admin-main">
        <header class="admin-topbar">
            <h1 class="h4 mb-0"><?= e($pageTitle) ?></h1>
        </header>
        <div class="admin-content">
            <?php render_flashes(); ?>
