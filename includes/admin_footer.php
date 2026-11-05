<?php /** Admin layout footer. Optional: $extraScripts (paths relative to /admin, e.g. '../assets/js/admin.js'). */
$extraScripts = $extraScripts ?? []; ?>
        </div>
    </div>
</div>
<div class="modal fade" id="previewModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content bg-dark text-white border-0">
            <div class="modal-header border-0">
                <h2 class="modal-title h6" id="previewCaption"></h2>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center pt-0"><img id="previewImage" src="" alt="Photo preview" class="img-fluid rounded"></div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<?php foreach ($extraScripts as $script): ?>
<script src="<?= e($script) ?>"></script>
<?php endforeach; ?>
</body>
</html>
