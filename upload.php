<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/functions.php';

$errors  = [];
$oldName = '';
$oldCode = isset($_GET['code']) ? normalize_visitor_code((string) $_GET['code']) : '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // If the whole request was bigger than PHP's post_max_size, PHP drops $_POST and $_FILES.
    if (empty($_POST) && empty($_FILES) && (int) ($_SERVER['CONTENT_LENGTH'] ?? 0) > 0) {
        $errors[] = 'The upload was too large for the server. Please upload fewer or smaller photos (max ' . MAX_FILES_PER_UPLOAD . ' photos, 5 MB each).';
    } elseif (!csrf_valid()) {
        $errors[] = 'Your session expired. Please try again.';
    } else {
        $oldName = trim((string) ($_POST['visitor_name'] ?? ''));
        $oldCode = normalize_visitor_code((string) ($_POST['visitor_code'] ?? ''));
        $consent = isset($_POST['consent']) && $_POST['consent'] === '1';
        $files   = normalize_files($_FILES['photos'] ?? null);

        // 1. Who is uploading? (returning visitor with code, or a new visitor with a name)
        $visitor   = null;
        $cleanName = '';
        if ($oldCode !== '') {
            $visitor = find_visitor_by_code($oldCode);
            if (!$visitor) {
                $errors[] = 'We could not find that visitor code. Check it, or leave the code empty to register as a new visitor.';
            }
        } else {
            [$cleanName, $nameError] = validate_visitor_name($oldName);
            if ($nameError) {
                $errors[] = $nameError;
            }
        }

        // 2. Consent and files
        if (!$consent) {
            $errors[] = 'Please tick the consent box so we may display your photos publicly after approval.';
        }
        if (count($files) === 0) {
            $errors[] = 'Please choose at least one photo.';
        } elseif (count($files) > MAX_FILES_PER_UPLOAD) {
            $errors[] = 'You can upload at most ' . MAX_FILES_PER_UPLOAD . ' photos at a time.';
        } else {
            $extensions = [];
            foreach ($files as $i => $file) {
                [$ext, $fileError] = validate_upload($file);
                if ($fileError) {
                    $errors[] = $fileError;
                } else {
                    $extensions[$i] = $ext;
                }
            }
        }

        // 3. Save everything (all or nothing)
        if (!$errors) {
            $pdo        = db();
            $movedFiles = [];
            try {
                $pdo->beginTransaction();

                if (!$visitor) {
                    $code = '';
                    for ($try = 0; $try < 5; $try++) {
                        $code = generate_visitor_code();
                        try {
                            $ins = $pdo->prepare('INSERT INTO visitors (visitor_code, visitor_name) VALUES (:code, :name)');
                            $ins->execute([':code' => $code, ':name' => $cleanName]);
                            break;
                        } catch (PDOException $dup) {
                            if ($try === 4 || ($dup->errorInfo[1] ?? 0) !== 1062) {
                                throw $dup;
                            }
                        }
                    }
                    $visitorId   = (int) $pdo->lastInsertId();
                    $visitorCode = $code;
                } else {
                    $visitorId   = (int) $visitor['id'];
                    $visitorCode = $visitor['visitor_code'];
                }

                $insPhoto = $pdo->prepare(
                    "INSERT INTO photos (visitor_id, photo_name, photo_path, status, consent_at)
                     VALUES (:vid, :name, :path, 'pending', NOW())"
                );
                foreach ($files as $i => $file) {
                    $stored = bin2hex(random_bytes(16)) . '.' . $extensions[$i];
                    $target = upload_dir() . DIRECTORY_SEPARATOR . $stored;
                    if (!move_uploaded_file($file['tmp_name'], $target)) {
                        throw new RuntimeException('Could not save an uploaded file.');
                    }
                    $movedFiles[] = $stored;
                    $insPhoto->execute([':vid' => $visitorId, ':name' => $stored, ':path' => UPLOAD_DIR_NAME . '/' . $stored]);
                }

                $pdo->commit();
                $_SESSION['visitor_code'] = $visitorCode;
                flash('success', count($files) . ' photo(s) uploaded successfully. They will appear in the slideshow after admin approval.');
                redirect('my_photos.php?uploaded=' . count($files) . '&code=' . urlencode($visitorCode));
            } catch (Throwable $ex) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                foreach ($movedFiles as $name) {
                    delete_photo_file($name);
                }
                error_log('[VisitorPhotoSlideshow] upload failed: ' . $ex->getMessage());
                $errors[] = 'Sorry, something went wrong while saving your photos. Nothing was saved - please try again.';
            }
        }
    }
}

$pageTitle    = 'Upload Photos';
$activeNav    = 'upload';
$base         = '';
$extraScripts = ['assets/js/upload.js'];
require __DIR__ . '/includes/header.php';
?>
<section class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8 col-xl-7">
            <div class="text-center mb-4">
                <h1 class="section-title">Upload your photos</h1>
                <p class="text-muted">Photos are reviewed by an administrator before they appear on the public slideshow.</p>
            </div>

            <?php if ($errors): ?>
                <div class="alert alert-danger" role="alert">
                    <strong><i class="bi bi-exclamation-triangle-fill me-1"></i>Please fix the following:</strong>
                    <ul class="mb-0 mt-2">
                        <?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form id="uploadForm" class="upload-card" method="post" action="upload.php" enctype="multipart/form-data" novalidate>
                <?= csrf_field() ?>

                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label for="visitor_name" class="form-label fw-semibold">Full name <span class="text-danger" id="nameStar">*</span></label>
                        <input type="text" class="form-control form-control-lg" id="visitor_name" name="visitor_name" maxlength="100" value="<?= e($oldName) ?>" placeholder="e.g. Priya Sharma" autocomplete="name">
                    </div>
                    <div class="col-md-6">
                        <label for="visitor_code" class="form-label fw-semibold">Visitor code <span class="text-muted fw-normal">(returning visitors)</span></label>
                        <input type="text" class="form-control form-control-lg" id="visitor_code" name="visitor_code" maxlength="11" value="<?= e($oldCode) ?>" placeholder="VP-XXXXXXXX" autocomplete="off">
                    </div>
                </div>
                <div class="info-box mb-4">
                    <i class="bi bi-info-circle-fill me-2"></i>
                    <span><strong>First time?</strong> Just enter your name &ndash; you will receive a personal visitor code after uploading. <strong>Been here before?</strong> Enter your visitor code to add photos to your existing account.</span>
                </div>

                <label class="form-label fw-semibold">Photos</label>
                <div id="dropzone" class="dropzone" tabindex="0" role="button" aria-label="Choose photos"
                     data-max-size="<?= MAX_FILE_SIZE ?>" data-max-files="<?= MAX_FILES_PER_UPLOAD ?>">
                    <i class="bi bi-cloud-arrow-up dropzone-icon"></i>
                    <p class="mb-1 fw-semibold">Drag &amp; drop photos here, or click to browse</p>
                    <p class="small text-muted mb-0">JPG, JPEG, PNG or WEBP &middot; max 5 MB each &middot; up to <?= MAX_FILES_PER_UPLOAD ?> photos</p>
                    <input type="file" id="photos" name="photos[]" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp" multiple hidden>
                </div>
                <div id="clientErrors" class="text-danger small mt-2" aria-live="polite"></div>
                <div id="previewGrid" class="preview-grid mt-3"></div>

                <div class="form-check consent-box my-4">
                    <input class="form-check-input" type="checkbox" value="1" id="consent" name="consent">
                    <label class="form-check-label" for="consent">
                        I confirm that I own these photos (or have permission to share them) and I <strong>consent to them being displayed publicly</strong> on the slideshow after approval. I understand an administrator can remove them at any time.
                    </label>
                </div>

                <button type="submit" id="submitBtn" class="btn btn-primary btn-lg w-100 rounded-pill fw-semibold">
                    <i class="bi bi-upload me-2"></i>Upload Photos
                </button>
            </form>
        </div>
    </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
