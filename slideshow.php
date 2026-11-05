<?php
declare(strict_types=1);
/* Full-screen slideshow. No login needed; it only shows approved photos (via slideshow_data.php). */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Slideshow | Visitor Photo Slideshow</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body class="slideshow-body">
<div id="show" class="show" data-feed="slideshow_data.php">
    <div class="slide" id="slideA"><img alt=""></div>
    <div class="slide" id="slideB"><img alt=""></div>

    <div id="emptyState" class="show-empty" hidden>
        <i class="bi bi-images"></i>
        <h1>No photos to show yet</h1>
        <p>Approved photos will appear here automatically.</p>
        <a href="upload.php" class="show-link">Upload a photo</a>
    </div>

    <div class="show-caption" id="caption" hidden>
        <div class="caption-name"><i class="bi bi-person-circle"></i> <span id="capName"></span></div>
        <div class="caption-info"><span id="capDate"></span> &middot; Photo <span id="capIndex">0</span> of <span id="capTotal">0</span></div>
    </div>

    <div class="show-notice" id="notice" hidden></div>

    <div class="show-progress"><div id="progressBar"></div></div>

    <div class="show-controls" id="controls">
        <a href="index.php" class="ctl" title="Back to site" aria-label="Back to site"><i class="bi bi-house-door"></i></a>
        <button type="button" class="ctl" id="btnPrev" title="Previous (Left arrow)" aria-label="Previous photo"><i class="bi bi-skip-backward-fill"></i></button>
        <button type="button" class="ctl ctl-main" id="btnPlay" title="Play / Pause (Space)" aria-label="Pause"><i class="bi bi-pause-fill"></i></button>
        <button type="button" class="ctl" id="btnNext" title="Next (Right arrow)" aria-label="Next photo"><i class="bi bi-skip-forward-fill"></i></button>
        <button type="button" class="ctl" id="btnFull" title="Full screen (F)" aria-label="Full screen"><i class="bi bi-arrows-fullscreen"></i></button>
    </div>
</div>
<script src="assets/js/slideshow.js"></script>
</body>
</html>
