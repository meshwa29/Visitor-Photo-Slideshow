<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/functions.php';

$pageTitle = 'Welcome';
$activeNav = 'home';
$base      = '';
require __DIR__ . '/includes/header.php';
?>
<section class="hero">
    <div class="container">
        <div class="row align-items-center g-5">
            <div class="col-lg-7">
                <span class="hero-pill"><i class="bi bi-stars me-1"></i> Share your moment with us</span>
                <h1 class="hero-title">Welcome to Our Visitor Gallery</h1>
                <p class="hero-text">Tell us your name, upload your favourite photos and watch them appear on our live slideshow once they are approved. Come back any time with your personal visitor code to see everything you have shared.</p>
                <div class="d-flex flex-wrap gap-3 mt-4">
                    <a href="upload.php" class="btn btn-light btn-lg rounded-pill px-4 fw-semibold"><i class="bi bi-cloud-arrow-up me-2"></i>Upload Photos</a>
                    <a href="slideshow.php" class="btn btn-outline-light btn-lg rounded-pill px-4"><i class="bi bi-play-circle me-2"></i>Watch Slideshow</a>
                </div>
            </div>
            <div class="col-lg-5">
                <div class="glass-card" id="returning">
                    <h2 class="h5 mb-1"><i class="bi bi-person-check me-2"></i>Returning visitor?</h2>
                    <p class="small mb-3 text-muted-light">Enter the visitor code you received after your first upload (for example <code>VP-7K3M9QXA</code>).</p>
                    <form action="my_photos.php" method="get" class="d-flex gap-2">
                        <input type="text" name="code" class="form-control form-control-lg" placeholder="VP-XXXXXXXX" maxlength="11" autocomplete="off" required>
                        <button class="btn btn-primary btn-lg" type="submit" title="Find my photos"><i class="bi bi-search"></i></button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="container py-5">
    <div class="text-center mb-5">
        <h2 class="section-title">How it works</h2>
        <p class="text-muted">Four simple steps from upload to slideshow.</p>
    </div>
    <div class="row g-4">
        <?php
        $steps = [
            ['pencil-square', 'Enter your name', 'Type your full name. We give you a unique visitor code so you are never mixed up with someone who has the same name.'],
            ['images', 'Upload photos', 'Choose or drag and drop JPG, PNG or WEBP images (up to 5 MB each) and tick the consent box.'],
            ['patch-check', 'Admin approval', 'Every photo is checked by an administrator before it is shown to the public.'],
            ['collection-play', 'Live slideshow', 'Approved photos appear automatically on the slideshow screen with your name.'],
        ];
        foreach ($steps as $i => [$icon, $title, $text]): ?>
            <div class="col-md-6 col-lg-3">
                <div class="feature-card h-100">
                    <div class="feature-icon"><i class="bi bi-<?= e($icon) ?>"></i></div>
                    <div class="step-number">Step <?= $i + 1 ?></div>
                    <h3 class="h5"><?= e($title) ?></h3>
                    <p class="mb-0 text-muted"><?= e($text) ?></p>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</section>

<section class="container pb-5">
    <div class="cta-band">
        <div>
            <h2 class="h4 mb-1">Ready to be part of the gallery?</h2>
            <p class="mb-0">It takes less than a minute.</p>
        </div>
        <a href="upload.php" class="btn btn-light rounded-pill px-4 fw-semibold">Start uploading <i class="bi bi-arrow-right ms-1"></i></a>
    </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
