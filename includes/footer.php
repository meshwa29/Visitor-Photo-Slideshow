<?php
/** Public page footer. Optional: $extraScripts = ['assets/js/upload.js'] (paths relative to $base). */
$base         = $base ?? '';
$extraScripts = $extraScripts ?? [];
?>
</main>
<footer class="vp-footer">
    <div class="container d-flex flex-column flex-md-row justify-content-between align-items-center gap-2">
        <span><i class="bi bi-camera-fill me-1"></i> Visitor Photo Slideshow &middot; MCA College Project</span>
        <span class="text-muted-light">Photos are shown publicly only after admin approval.</span>
    </div>
</footer>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<?php foreach ($extraScripts as $script): ?>
<script src="<?= e($base . $script) ?>"></script>
<?php endforeach; ?>
</body>
</html>
