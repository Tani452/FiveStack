<?php
/**
 * ClubHub - Core Utility & Helper Functions
 * Phase 5: Database & PHP Foundation Helpers
 * Includes sanitization, email validation, redirection, flash messages,
 * error logging to logs/error.log, and custom exception handling.
 */

// Disable raw PHP error display to visitors for security and compliance
ini_set('display_errors', '0');
error_reporting(E_ALL);

// Ensure local sessions directory is writable
if (session_status() === PHP_SESSION_NONE) {
    $save_path = session_save_path();
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
 * Global exception handler: catches all uncaught exceptions,
 * logs the details, and displays the friendly custom error page.
 */
set_exception_handler(function ($exception) {
    log_error($exception->getMessage(), [
        'file'  => $exception->getFile(),
        'line'  => $exception->getLine(),
        'trace' => $exception->getTraceAsString(),
    ]);
    render_custom_error_page();
});

/**
 * Global error handler: catches PHP runtime errors and logs them.
 */
set_error_handler(function ($severity, $message, $file, $line) {
    if (!(error_reporting() & $severity)) {
        return false;
    }
    log_error($message, [
        'severity' => $severity,
        'file'     => $file,
        'line'     => $line,
    ]);

    // For fatal-level errors, show friendly error page
    if ($severity === E_USER_ERROR || $severity === E_RECOVERABLE_ERROR) {
        render_custom_error_page();
    }
    return true;
});

/**
 * Displays a friendly user error page without raw PHP errors or stack traces.
 */
function render_custom_error_page() {
    if (!headers_sent()) {
        http_response_code(500);
    }
    // Determine relative base url
    $script_path = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? $_SERVER['PHP_SELF'] ?? '');
    $base_url = preg_match('#(^|/)pages/#i', $script_path) ? '../' : './';
    
    // Redirect or render error template
    $error_page = __DIR__ . '/../pages/error.php';
    if (file_exists($error_page)) {
        include $error_page;
    } else {
        echo "<!DOCTYPE html><html lang='en'><head><title>System Error - ClubHub</title></head><body>";
        echo "<h1>Oops! Something went wrong</h1><p>An unexpected error occurred. The incident has been recorded.</p>";
        echo "<p><a href='{$base_url}index.php'>Return to Home</a></p></body></html>";
    }
    exit;
}

/**
 * Logs an error message with contextual details to logs/error.log.
 *
 * @param string $message The error message to record.
 * @param array $context Optional associative array with extra details (file, line, trace).
 */
function log_error($message, $context = []) {
    $log_dir = __DIR__ . '/../logs';
    if (!is_dir($log_dir)) {
        @mkdir($log_dir, 0777, true);
    }

    $log_file = $log_dir . '/error.log';
    $timestamp = date('Y-m-d H:i:s');
    $script = $_SERVER['SCRIPT_NAME'] ?? 'CLI';
    $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';

    $context_str = '';
    if (!empty($context)) {
        $context_str = ' | Context: ' . json_encode($context, JSON_UNESCAPED_SLASHES);
    }

    $log_line = "[{$timestamp}] [{$ip}] [{$script}] {$message}{$context_str}" . PHP_EOL;
    @file_put_contents($log_file, $log_line, FILE_APPEND | LOCK_EX);
}

/**
 * Sanitizes input and escapes HTML output to prevent XSS.
 *
 * @param mixed $data The input string or array to sanitize.
 * @return mixed Sanitized string or array.
 */
function sanitize($data) {
    if (is_array($data)) {
        return array_map('sanitize', $data);
    }
    return htmlspecialchars(trim((string)$data), ENT_QUOTES, 'UTF-8');
}

/**
 * Short alias for sanitize() to quickly escape output in templates.
 *
 * @param mixed $data
 * @return string Safe HTML string.
 */
function e($data) {
    return sanitize($data);
}

/**
 * Validates an email address format.
 *
 * @param string $email
 * @return bool True if valid, false otherwise.
 */
function is_valid_email($email) {
    if (empty($email) || !is_string($email)) {
        return false;
    }
    return filter_var(trim($email), FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Redirects the browser to another page and halts execution.
 *
 * @param string $url Target redirect URL.
 */
function redirect($url) {
    header("Location: {$url}");
    exit;
}

/**
 * Sets a flash message to display on the next rendered page.
 *
 * @param string $type Bootstrap/CSS alert type: 'success', 'danger', 'warning', 'info'
 * @param string $message The text message to display.
 */
function set_flash($type, $message) {
    $_SESSION['flash'] = [
        'type'    => sanitize($type),
        'message' => sanitize($message),
    ];
}

/**
 * Retrieves and clears the active flash message from session.
 *
 * @return array|null Associative array with 'type' and 'message', or null if empty.
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
 * Generates or retrieves the current CSRF token from session.
 *
 * @return string 64-character hexadecimal token.
 */
function generate_csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Renders a hidden CSRF token input tag for POST forms.
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
 * @param string|null $token
 * @return bool True if valid, false otherwise.
 */
function verify_csrf_token($token) {
    if (empty($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], (string)$token);
}

// Include role-based access control and authentication middleware
require_once __DIR__ . '/auth.php';
