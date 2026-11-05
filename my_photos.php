<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/functions.php';

// The code may come from the link (?code=...), or from the session after an upload.
$codeInput = isset($_GET['code']) ? (string) $_GET['code'] : (string) ($_SESSION['visitor_code'] ?? '');
$visitor   = null;
$photos    = [];
$notFound  = false;

if (trim($codeInput) !== '') {
    $visitor = find_visitor_by_code($codeInput);
    if ($visitor) {
        $_SESSION['visitor_code'] = $visitor['visitor_code'];
        $stmt = db()->prepare('SELECT id, photo_path, status, uploaded_at FROM photos WHERE visitor_id = :id ORDER BY uploaded_at DESC, id DESC');
        $stmt->execute([':id' => $visitor['id']]);
        $photos = $stmt->fetchAll();
    } else {
        $notFound = true;
    }
}

$counts = ['pending' => 0, 'approved' => 0, 'rejected' => 0];
foreach ($photos as $p) {
    $counts[$p['status']]++;
}
$justUploaded = isset($_GET['uploaded']) ? (int) $_GET['uploaded'] : 0;

$pageTitle = 'My Photos';
$activeNav = 'my';
$base      = '';
$extraScripts = ['assets/js/copy.js'];
require __DIR__ . '/includes/header.php';
?>
<section class="container py-5">
<?php if ($visitor): ?>
    <div class="row justify-content-center mb-4">
        <div class="col-lg-8">
            <div class="code-card text-center">
                <?php if ($justUploaded > 0): ?>
                    <div class="success-check"><i class="bi bi-check-lg"></i></div>
                    <h1 class="h3">Thank you, <?= e($visitor['visitor_name']) ?>!</h1>
                    <p class="text-muted mb-3">Your photos are waiting for admin approval.</p>
                <?php else: ?>
                    <h1 class="h3">Hello, <?= e($visitor['visitor_name']) ?>!</h1>
                    <p class="text-muted mb-3">Here are all the photos you have shared.</p>
                <?php endif; ?>
                <div class="small text-uppercase text-muted fw-semibold">Your visitor code</div>
                <div class="code-display" id="visitorCode"><?= e($visitor['visitor_code']) ?></div>
                <button type="button" class="btn btn-sm btn-outline-primary rounded-pill mb-2" id="copyCode" data-target="visitorCode"><i class="bi bi-clipboard me-1"></i>Copy code</button>
                <p class="small text-muted mb-3"><i class="bi bi-exclamation-circle me-1"></i>Save this code! You need it to see or add photos on your next visit.</p>
                <div class="d-flex justify-content-center flex-wrap gap-2">
                    <a class="btn btn-primary rounded-pill px-4" href="upload.php?code=<?= e(urlencode($visitor['visitor_code'])) ?>"><i class="bi bi-plus-circle me-1"></i>Upload more photos</a>
                    <a class="btn btn-outline-secondary rounded-pill px-4" href="slideshow.php"><i class="bi bi-play-circle me-1"></i>View slideshow</a>
                </div>
            </div>
        </div>
    </div>

    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
        <h2 class="h4 mb-0">Your photos (<?= count($photos) ?>)</h2>
        <div class="d-flex gap-2 flex-wrap">
            <span class="badge badge-soft-success"><?= $counts['approved'] ?> approved</span>
            <span class="badge badge-soft-warning"><?= $counts['pending'] ?> pending</span>
            <span class="badge badge-soft-danger"><?= $counts['rejected'] ?> rejected</span>
        </div>
    </div>
    <?php if (!$photos): ?>
        <div class="empty-state"><i class="bi bi-image"></i><p class="mb-0">No photos yet. Upload your first photo!</p></div>
    <?php else: ?>
        <div class="photo-grid">
            <?php foreach ($photos as $p): ?>
                <figure class="photo-card">
                    <img src="<?= e($p['photo_path']) ?>" alt="Photo uploaded on <?= e(format_date($p['uploaded_at'])) ?>" loading="lazy">
                    <figcaption>
                        <?= status_badge($p['status']) ?>
                        <span class="small text-muted"><?= e(format_date($p['uploaded_at'])) ?></span>
                    </figcaption>
                </figure>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

<?php else: ?>
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
            <div class="upload-card text-center">
                <div class="feature-icon mx-auto"><i class="bi bi-person-check"></i></div>
                <h1 class="h3">Find my photos</h1>
                <p class="text-muted">Enter your visitor code to see the photos you uploaded before.</p>
                <?php if ($notFound): ?>
                    <div class="alert alert-danger text-start"><i class="bi bi-exclamation-triangle-fill me-1"></i>We could not find that visitor code. Please check it and try again.</div>
                <?php endif; ?>
                <form method="get" action="my_photos.php" class="d-flex gap-2">
                    <input type="text" name="code" class="form-control form-control-lg" placeholder="VP-XXXXXXXX" maxlength="11" autocomplete="off" required>
                    <button class="btn btn-primary btn-lg" type="submit"><i class="bi bi-search"></i></button>
                </form>
                <p class="small text-muted mt-3 mb-0">New here? <a href="upload.php">Upload your first photos</a> to get a code.</p>
            </div>
        </div>
    </div>
<?php endif; ?>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
