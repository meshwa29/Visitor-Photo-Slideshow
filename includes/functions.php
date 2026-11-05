<?php
declare(strict_types=1);

/**
 * Shared helpers used by every page:
 * sessions, escaping, CSRF, flash messages, upload validation, admin checks.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax']);
    session_start();
}

require_once __DIR__ . '/../config/database.php';

// ---------------------------------------------------------------- settings
const MAX_FILE_SIZE        = 5 * 1024 * 1024;   // 5 MB per photo
const MAX_FILES_PER_UPLOAD = 6;                 // keeps one request below XAMPP's 40 MB post limit
const ALLOWED_TYPES        = [
    'jpg'  => 'image/jpeg',
    'jpeg' => 'image/jpeg',
    'png'  => 'image/png',
    'webp' => 'image/webp',
];
const UPLOAD_DIR_NAME      = 'uploads';
const CODE_ALPHABET        = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789'; // no I, O, 0, 1 (easy to read)

function upload_dir(): string
{
    return dirname(__DIR__) . DIRECTORY_SEPARATOR . UPLOAD_DIR_NAME;
}

// ------------------------------------------------------ friendly error page
set_exception_handler(function (Throwable $e): void {
    error_log('[VisitorPhotoSlideshow] ' . $e->getMessage());
    if (!headers_sent()) {
        http_response_code(500);
    }
    $detail = APP_DEBUG ? '<pre>' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . '</pre>' : '';
    echo '<!DOCTYPE html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">'
        . '<title>Something went wrong</title>'
        . '<style>body{font-family:system-ui,sans-serif;background:#0f172a;color:#e2e8f0;display:grid;place-items:center;min-height:100vh;margin:0;padding:1rem}'
        . '.box{max-width:480px;background:#1e293b;border-radius:16px;padding:2rem;text-align:center}a{color:#a5b4fc}pre{text-align:left;white-space:pre-wrap;font-size:.8rem}</style></head>'
        . '<body><div class="box"><h1>Something went wrong</h1>'
        . '<p>The application could not complete your request. Please make sure MySQL is running in XAMPP and the database was imported (sql/database.sql).</p>'
        . $detail . '<p><a href="javascript:history.back()">Go back</a></p></div></body></html>';
});

// -------------------------------------------------------------- escaping
function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function redirect(string $url): void
{
    header('Location: ' . $url);
    exit;
}

// ------------------------------------------------------------------ CSRF
function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

function csrf_valid(): bool
{
    $sent = $_POST['csrf_token'] ?? '';
    return is_string($sent) && $sent !== '' && hash_equals(csrf_token(), $sent);
}

// --------------------------------------------------------- flash messages
function flash(string $type, string $message): void
{
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

function take_flashes(): array
{
    $items = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $items;
}

function render_flashes(): void
{
    foreach (take_flashes() as $f) {
        $type = in_array($f['type'], ['success', 'danger', 'warning', 'info'], true) ? $f['type'] : 'info';
        $icon = ['success' => 'check-circle-fill', 'danger' => 'exclamation-triangle-fill', 'warning' => 'exclamation-circle-fill', 'info' => 'info-circle-fill'][$type];
        echo '<div class="alert alert-' . $type . ' d-flex align-items-start gap-2" role="alert">'
            . '<i class="bi bi-' . $icon . ' mt-1"></i><div>' . e($f['message']) . '</div></div>';
    }
}

// ------------------------------------------------------------- formatting
function format_date(?string $date): string
{
    if (!$date) {
        return '-';
    }
    $ts = strtotime($date);
    return $ts ? date('d M Y, h:i A', $ts) : '-';
}

function status_badge(string $status): string
{
    $map = [
        'pending'  => ['warning', 'hourglass-split', 'Pending'],
        'approved' => ['success', 'check-circle', 'Approved'],
        'rejected' => ['danger', 'x-circle', 'Rejected'],
    ];
    [$colour, $icon, $label] = $map[$status] ?? ['secondary', 'question-circle', ucfirst($status)];
    return '<span class="badge badge-soft-' . $colour . '"><i class="bi bi-' . $icon . ' me-1"></i>' . e($label) . '</span>';
}

// --------------------------------------------------------------- visitors
/** Visitor code looks like VP-7K3M9QXA. Random, so two people with the same name never share one. */
function generate_visitor_code(): string
{
    $code = '';
    $max  = strlen(CODE_ALPHABET) - 1;
    for ($i = 0; $i < 8; $i++) {
        $code .= CODE_ALPHABET[random_int(0, $max)];
    }
    return 'VP-' . $code;
}

function normalize_visitor_code(string $code): string
{
    return strtoupper(trim($code));
}

function is_valid_visitor_code(string $code): bool
{
    return (bool) preg_match('/^VP-[A-HJ-NP-Z2-9]{8}$/', $code);
}

function find_visitor_by_code(string $code): ?array
{
    $code = normalize_visitor_code($code);
    if (!is_valid_visitor_code($code)) {
        return null;
    }
    $stmt = db()->prepare('SELECT id, visitor_code, visitor_name, created_at FROM visitors WHERE visitor_code = :code LIMIT 1');
    $stmt->execute([':code' => $code]);
    $row = $stmt->fetch();
    return $row ?: null;
}

/** Returns [cleanName, errorOrNull]. */
function validate_visitor_name(string $name): array
{
    $name = trim((string) preg_replace('/\s+/u', ' ', $name));
    $len  = mb_strlen($name);
    if ($len < 2 || $len > 100) {
        return [$name, 'Please enter your full name (2 to 100 characters).'];
    }
    if (!preg_match("/^[\\p{L}\\p{M}0-9 .'\\-]+$/u", $name)) {
        return [$name, 'Your name may contain only letters, numbers, spaces, dots, apostrophes and hyphens.'];
    }
    return [$name, null];
}

// ---------------------------------------------------------------- uploads
/** Turns PHP's awkward $_FILES['photos'] structure into a simple list of files. */
function normalize_files(?array $input): array
{
    $files = [];
    if (!$input || !isset($input['name']) || !is_array($input['name'])) {
        return $files;
    }
    foreach ($input['name'] as $i => $name) {
        if (($input['error'][$i] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            continue;
        }
        $files[] = [
            'name'     => (string) $name,
            'tmp_name' => (string) ($input['tmp_name'][$i] ?? ''),
            'error'    => (int) ($input['error'][$i] ?? UPLOAD_ERR_NO_FILE),
            'size'     => (int) ($input['size'][$i] ?? 0),
        ];
    }
    return $files;
}

/** Checks one uploaded file. Returns [extension|null, errorMessage|null]. */
function validate_upload(array $file): array
{
    $label = '"' . mb_substr(basename($file['name']), 0, 60) . '"';

    if ($file['error'] === UPLOAD_ERR_INI_SIZE || $file['error'] === UPLOAD_ERR_FORM_SIZE) {
        return [null, "$label is larger than the 5 MB limit."];
    }
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return [null, "$label could not be uploaded. Please try again."];
    }
    if ($file['size'] > MAX_FILE_SIZE) {
        return [null, "$label is larger than the 5 MB limit."];
    }
    if ($file['size'] <= 0) {
        return [null, "$label is empty."];
    }
    // Block script-like names such as shell.php.jpg
    if (preg_match('/\.(php\d?|phtml|phar|pl|py|cgi|sh|exe|js|html?)(\.|$)/i', $file['name'])) {
        return [null, "$label looks like a script file and is not allowed."];
    }
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!isset(ALLOWED_TYPES[$ext])) {
        return [null, "$label is not allowed. Only JPG, JPEG, PNG and WEBP images are accepted."];
    }
    if (!is_uploaded_file($file['tmp_name'])) {
        return [null, "$label was not uploaded correctly."];
    }
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime  = $finfo->file($file['tmp_name']);
    if ($mime !== ALLOWED_TYPES[$ext]) {
        return [null, "$label is not a real " . strtoupper($ext) . ' image (file content does not match).'];
    }
    $info = @getimagesize($file['tmp_name']);
    if ($info === false || !in_array($info[2], [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_WEBP], true)) {
        return [null, "$label is not a valid image."];
    }
    return [$ext, null];
}

/** Deletes a stored photo file safely (only inside the uploads folder). */
function delete_photo_file(string $photoName): void
{
    $safe = basename($photoName);
    if ($safe === '' || $safe[0] === '.') {
        return;
    }
    $path = upload_dir() . DIRECTORY_SEPARATOR . $safe;
    if (is_file($path)) {
        @unlink($path);
    }
}

// ------------------------------------------------------------------ admin
function is_admin(): bool
{
    return !empty($_SESSION['admin_id']);
}

function require_admin(): void
{
    if (!is_admin()) {
        flash('warning', 'Please log in to access the admin area.');
        redirect('login.php');
    }
}

function get_stats(): array
{
    $row = db()->query(
        "SELECT
            (SELECT COUNT(*) FROM visitors) AS visitors,
            (SELECT COUNT(*) FROM photos) AS photos,
            (SELECT COUNT(*) FROM photos WHERE status = 'pending') AS pending,
            (SELECT COUNT(*) FROM photos WHERE status = 'approved') AS approved,
            (SELECT COUNT(*) FROM photos WHERE status = 'rejected') AS rejected"
    )->fetch();
    return array_map('intval', $row ?: []);
}

/** Approve / reject / delete buttons for one photo (used by the admin pages). */
function photo_action_buttons(int $photoId, string $status, string $returnTo): string
{
    $html = '';
    $make = function (string $action, string $class, string $icon, string $title, string $confirm = '') use ($photoId, $returnTo): string {
        $onsubmit = $confirm !== '' ? ' onsubmit="return confirm(\'' . e($confirm) . '\');"' : '';
        return '<form method="post" action="approve_photo.php" class="d-inline"' . $onsubmit . '>'
            . csrf_field()
            . '<input type="hidden" name="photo_id" value="' . $photoId . '">'
            . '<input type="hidden" name="action" value="' . e($action) . '">'
            . '<input type="hidden" name="return" value="' . e($returnTo) . '">'
            . '<button type="submit" class="btn btn-sm ' . $class . '" title="' . e($title) . '"><i class="bi bi-' . $icon . '"></i> ' . e($title) . '</button></form> ';
    };
    if ($status !== 'approved') {
        $html .= $make('approve', 'btn-success', 'check-lg', 'Approve');
    }
    if ($status !== 'rejected') {
        $html .= $make('reject', 'btn-warning', 'x-lg', 'Reject');
    }
    $html .= $make('delete', 'btn-outline-danger', 'trash', 'Delete', 'Delete this photo permanently?');
    return $html;
}
