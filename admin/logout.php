<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/functions.php';

// Clear the whole session and the session cookie.
$_SESSION = [];
if (ini_get('session.use_cookies')) {
    $p = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], (bool) $p['secure'], (bool) $p['httponly']);
}
session_destroy();

session_start();
session_regenerate_id(true);
flash('success', 'You have been logged out.');
redirect('login.php');
