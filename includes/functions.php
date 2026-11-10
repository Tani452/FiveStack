<?php
/**
 * ClubHub - Core Utility Functions
 * Contains session helpers, CSRF protection, sanitization, and access control.
 */

// Start session if not already active
if (session_status() === PHP_SESSION_NONE) {
    $save_path = session_save_path();
    // If default session path is not writable (e.g. in certain CLI/restricted environments), use a local directory
    if (!empty($save_path) && !@is_writable($save_path)) {
        $local_sessions = __DIR__ . '/../uploads/sessions';
        if (!is_dir($local_sessions)) {
            @mkdir($local_sessions, 0777, true);
        }
        if (is_dir($local_sessions) && is_writable($local_sessions)) {
            session_save_path($local_sessions);
        }
    }
    @session_start();
}

/**
 * Escapes HTML output safely to prevent Cross-Site Scripting (XSS).
 *
 * @param mixed $data The string or data to escape.
 * @return string Safe HTML string.
 */
function e($data) {
    return htmlspecialchars((string)$data, ENT_QUOTES, 'UTF-8');
}

/**
 * Generates a CSRF token for the session if not already set.
 *
 * @return string CSRF token string.
 */
function generate_csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Outputs a hidden input field containing the current CSRF token.
 * Every POST form must include this.
 *
 * @return string HTML hidden input tag.
 */
function csrf_field() {
    $token = generate_csrf_token();
    return '<input type="hidden" name="csrf_token" value="' . e($token) . '">';
}

/**
 * Validates a submitted CSRF token using constant-time comparison.
 *
 * @param string|null $token Submitted token from POST request.
 * @return bool True if valid, false otherwise.
 */
function verify_csrf_token($token) {
    if (empty($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], (string)$token);
}

/**
 * Checks whether a user is currently logged in.
 *
 * @return bool
 */
function is_logged_in() {
    return !empty($_SESSION['user']) && !empty($_SESSION['user']['id']);
}

/**
 * Returns the currently authenticated user's session data.
 *
 * @return array|null User associative array or null if not logged in.
 */
function current_user() {
    return $_SESSION['user'] ?? null;
}

/**
 * Checks if the logged-in user possesses a specific role or one of multiple roles.
 *
 * @param string|array $roles Single role string or array of allowed roles.
 * @return bool
 */
function has_role($roles) {
    if (!is_logged_in()) {
        return false;
    }
    $user_role = $_SESSION['user']['role'] ?? '';
    if (is_array($roles)) {
        return in_array($user_role, $roles, true);
    }
    return $user_role === $roles;
}

/**
 * Enforces authentication. Redirects to login page if user is a guest.
 *
 * @param string $redirect_to Optional URL to redirect after login.
 */
function require_login($redirect_to = '') {
    if (!is_logged_in()) {
        set_flash('danger', 'You must log in to view this page.');
        $url = '../pages/login.php';
        if (!empty($redirect_to)) {
            $url .= '?redirect=' . urlencode($redirect_to);
        }
        redirect($url);
    }
}

/**
 * Enforces role-based authorization.
 *
 * @param string|array $allowed_roles Role(s) permitted to access the page.
 */
function require_role($allowed_roles) {
    require_login();
    if (!has_role($allowed_roles)) {
        set_flash('danger', 'Access denied. You do not have permission to view that section.');
        redirect('../pages/dashboard.php');
    }
}

/**
 * Sets a flash message to be displayed on the next rendered page.
 *
 * @param string $type Bootstrap/CSS alert type: 'success', 'danger', 'warning', 'info'
 * @param string $message The text message to display.
 */
function set_flash($type, $message) {
    $_SESSION['flash'] = [
        'type' => $type,
        'message' => $message,
    ];
}

/**
 * Retrieves and clears the current flash message from session.
 *
 * @return array|null Array with 'type' and 'message', or null if none.
 */
function get_flash() {
    if (!empty($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

/**
 * Redirects the browser to a given location and terminates execution.
 *
 * @param string $url Target URL.
 */
function redirect($url) {
    header("Location: {$url}");
    exit;
}

/**
 * Formats a MySQL date/time string into a human-readable format.
 *
 * @param string $datetime_str
 * @param string $format
 * @return string
 */
function format_date($datetime_str, $format = 'M d, Y') {
    if (empty($datetime_str)) {
        return 'N/A';
    }
    $timestamp = strtotime($datetime_str);
    return $timestamp ? date($format, $timestamp) : 'N/A';
}

/**
 * Formats time string (HH:MM:SS) to standard 12-hour format with AM/PM.
 *
 * @param string $time_str
 * @return string
 */
function format_time($time_str) {
    if (empty($time_str)) {
        return '';
    }
    $timestamp = strtotime("1970-01-01 {$time_str}");
    return $timestamp ? date('g:i A', $timestamp) : $time_str;
}
