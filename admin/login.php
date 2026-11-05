<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/functions.php';

if (is_admin()) {
    redirect('dashboard.php');
}

const MAX_LOGIN_ATTEMPTS = 5;
const LOCKOUT_SECONDS    = 60;

$error    = '';
$username = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $lockedUntil = (int) ($_SESSION['login_locked_until'] ?? 0);

    if (!csrf_valid()) {
        $error = 'Your session expired. Please try again.';
    } elseif ($lockedUntil > time()) {
        $error = 'Too many failed attempts. Please wait ' . ($lockedUntil - time()) . ' seconds and try again.';
    } else {
        $username = trim((string) ($_POST['username'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');

        $stmt = db()->prepare('SELECT id, username, password_hash FROM admins WHERE username = :u LIMIT 1');
        $stmt->execute([':u' => $username]);
        $admin = $stmt->fetch();

        // Always run password_verify so the response time does not reveal whether the user exists.
        $hash  = $admin['password_hash'] ?? '$2y$12$invalidinvalidinvalidinvalidinvalidinvalidinvalidinvali';
        $valid = password_verify($password, $hash) && $admin;

        if ($valid) {
            session_regenerate_id(true);
            unset($_SESSION['login_failures'], $_SESSION['login_locked_until']);
            $_SESSION['admin_id']       = (int) $admin['id'];
            $_SESSION['admin_username'] = $admin['username'];
            if (password_needs_rehash($admin['password_hash'], PASSWORD_DEFAULT)) {
                $upd = db()->prepare('UPDATE admins SET password_hash = :h WHERE id = :id');
                $upd->execute([':h' => password_hash($password, PASSWORD_DEFAULT), ':id' => $admin['id']]);
            }
            redirect('dashboard.php');
        }

        $_SESSION['login_failures'] = (int) ($_SESSION['login_failures'] ?? 0) + 1;
        if ($_SESSION['login_failures'] >= MAX_LOGIN_ATTEMPTS) {
            $_SESSION['login_locked_until'] = time() + LOCKOUT_SECONDS;
            $_SESSION['login_failures']     = 0;
        }
        $error = 'Invalid username or password.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin Login | Visitor Photo Slideshow</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
</head>
<body class="login-body">
<div class="login-wrap">
    <div class="login-card">
        <div class="text-center mb-4">
            <div class="feature-icon mx-auto"><i class="bi bi-shield-lock"></i></div>
            <h1 class="h3 mb-1">Admin Login</h1>
            <p class="text-muted mb-0">Sign in to manage visitors and photos</p>
        </div>
        <?php render_flashes(); ?>
        <?php if ($error): ?>
            <div class="alert alert-danger"><i class="bi bi-exclamation-triangle-fill me-1"></i><?= e($error) ?></div>
        <?php endif; ?>
        <form method="post" action="login.php" autocomplete="off">
            <?= csrf_field() ?>
            <div class="mb-3">
                <label for="username" class="form-label fw-semibold">Username</label>
                <div class="input-group input-group-lg">
                    <span class="input-group-text"><i class="bi bi-person"></i></span>
                    <input type="text" class="form-control" id="username" name="username" value="<?= e($username) ?>" required autofocus>
                </div>
            </div>
            <div class="mb-4">
                <label for="password" class="form-label fw-semibold">Password</label>
                <div class="input-group input-group-lg">
                    <span class="input-group-text"><i class="bi bi-key"></i></span>
                    <input type="password" class="form-control" id="password" name="password" required>
                </div>
            </div>
            <button type="submit" class="btn btn-primary btn-lg w-100 rounded-pill fw-semibold">Login</button>
        </form>
        <div class="text-center mt-4"><a href="../index.php" class="small text-decoration-none"><i class="bi bi-arrow-left me-1"></i>Back to website</a></div>
    </div>
</div>
</body>
</html>
