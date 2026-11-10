<?php
/**
 * ClubHub - Authentication & Access Control
 * Phase 7a: Role-based access control middleware and helpers.
 * Every protected page calls require_role() at the top to enforce authentication
 * and authorization across Admin, Club Head, and Member roles.
 */

require_once __DIR__ . '/functions.php';

/**
 * Checks whether an authenticated user session is active.
 *
 * @return bool
 */
if (!function_exists('is_logged_in')) {
    function is_logged_in() {
        return !empty($_SESSION['user']) && !empty($_SESSION['user']['id']);
    }
}

/**
 * Returns current user's session data.
 *
 * @return array|null User record array or null.
 */
if (!function_exists('current_user')) {
    function current_user() {
        return $_SESSION['user'] ?? null;
    }
}

/**
 * Checks if current user matches a given role or array of roles.
 *
 * @param string|array $roles Single role or list of allowed roles.
 * @return bool
 */
if (!function_exists('has_role')) {
    function has_role($roles) {
        if (!is_logged_in()) {
            return false;
        }
        $role = $_SESSION['user']['role'] ?? '';
        if (is_array($roles)) {
            return in_array($role, $roles, true);
        }
        return $role === $roles;
    }
}

/**
 * Enforces user authentication; redirects unauthenticated visitors to login.
 */
if (!function_exists('require_login')) {
    function require_login() {
        if (!is_logged_in()) {
            set_flash('danger', 'Please log in to access this page.');
            
            // Remember requested URL for return redirect
            if (!empty($_SERVER['REQUEST_URI'])) {
                $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
            }
            
            // Determine relative path to login.php
            $script_path = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? $_SERVER['PHP_SELF'] ?? '');
            $target = preg_match('#(^|/)pages/#i', $script_path) ? 'login.php' : 'pages/login.php';
            redirect($target);
        }
    }
}

/**
 * Enforces role-based authorization.
 * Every protected page calls this function at the top.
 * Protects pages from unauthorized access, even if users type the URL directly.
 *
 * @param string|array $allowed_roles Single role string or array of permitted roles
 */
if (!function_exists('require_role')) {
    function require_role($allowed_roles) {
        // First ensure user is authenticated
        require_login();

        // Check if user has one of the allowed roles
        if (!has_role($allowed_roles)) {
            set_flash('danger', 'Access denied. You do not have permission to access that page.');
            
            // Determine relative path to dashboard.php
            $script_path = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? $_SERVER['PHP_SELF'] ?? '');
            $target = preg_match('#(^|/)pages/#i', $script_path) ? 'dashboard.php' : 'pages/dashboard.php';
            redirect($target);
        }
    }
}

