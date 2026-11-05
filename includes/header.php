<?php
/**
 * Public page header.
 * Set before including:  $pageTitle (string), $activeNav (string), $base ('' for root pages, '../' for admin pages)
 */
$base       = $base ?? '';
$pageTitle  = $pageTitle ?? 'Visitor Photo Slideshow';
$activeNav  = $activeNav ?? '';
$navItems   = [
    'home'      => ['index.php', 'house-door', 'Home'],
    'upload'    => ['upload.php', 'cloud-arrow-up', 'Upload Photos'],
    'my'        => ['my_photos.php', 'images', 'My Photos'],
    'slideshow' => ['slideshow.php', 'play-btn', 'Slideshow'],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($pageTitle) ?> | Visitor Photo Slideshow</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="<?= e($base) ?>assets/css/style.css" rel="stylesheet">
</head>
<body class="public-body">
<nav class="navbar navbar-expand-lg navbar-dark vp-nav sticky-top">
    <div class="container">
        <a class="navbar-brand fw-bold" href="<?= e($base) ?>index.php"><i class="bi bi-camera-fill me-2"></i>Visitor Gallery</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav" aria-controls="mainNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="mainNav">
            <ul class="navbar-nav ms-auto align-items-lg-center gap-lg-1">
                <?php foreach ($navItems as $key => [$href, $icon, $label]): ?>
                    <li class="nav-item">
                        <a class="nav-link <?= $activeNav === $key ? 'active' : '' ?>" href="<?= e($base . $href) ?>"><i class="bi bi-<?= e($icon) ?> me-1"></i><?= e($label) ?></a>
                    </li>
                <?php endforeach; ?>
                <li class="nav-item ms-lg-2">
                    <a class="btn btn-sm btn-outline-light rounded-pill px-3" href="<?= e($base) ?>admin/login.php"><i class="bi bi-shield-lock me-1"></i>Admin</a>
                </li>
            </ul>
        </div>
    </div>
</nav>
<main>
<?php if (!empty($_SESSION['flash'])): ?>
    <div class="container pt-4"><?php render_flashes(); ?></div>
<?php endif; ?>
