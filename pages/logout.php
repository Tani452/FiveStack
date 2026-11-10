<?php
/**
 * ClubHub - Logout Handler
 * Phase 6: Clears session data, regenerates session ID, and redirects with flash message.
 */

require_once __DIR__ . '/../includes/functions.php';

// Clear user session
if (isset($_SESSION['user'])) {
    unset($_SESSION['user']);
}

// Clear all session data safely
$_SESSION = [];

// Destroy session cookie if present
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params['path'],
        $params['domain'],
        $params['secure'],
        $params['httponly']
    );
}

// Destroy session
@session_destroy();

// Start a clean session to pass flash message
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
set_flash('info', 'You have been logged out successfully.');
redirect('login.php');

