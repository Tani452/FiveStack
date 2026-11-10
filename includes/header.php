<?php
/**
 * ClubHub - Shared Header Component
 * Provides the document head, stylesheet link, semantic <header>, and <nav>.
 */

require_once __DIR__ . '/functions.php';

// Determine base path based on directory location
if (!isset($base_url)) {
    $script_path = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? $_SERVER['SCRIPT_FILENAME'] ?? '');
    if (preg_match('#(^|/)pages/#i', $script_path)) {
        $base_url = '../';
    } else {
        $base_url = './';
    }
}

// Current page filename for active navigation highlight
$current_page = basename($_SERVER['PHP_SELF'] ?? '');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? e($page_title) . ' - ClubHub' : 'ClubHub - College Clubs Operations Hub'; ?></title>
    <meta name="description" content="ClubHub - College Clubs Operations Hub for student clubs, campus venues, and event coordination.">
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="<?php echo e($base_url); ?>assets/images/favicon.png">
    <!-- CSRF Token for AJAX Requests -->
    <meta name="csrf-token" content="<?php echo e(generate_csrf_token()); ?>">
    <!-- Linked stylesheet -->
    <link rel="stylesheet" href="<?php echo e($base_url); ?>assets/css/style.css">
</head>
<body>

<header class="site-header">
    <p class="site-brand">
        <strong>
            <a href="<?php echo e($base_url); ?>index.php" class="brand-link">
                <img src="<?php echo e($base_url); ?>assets/images/logo.png" alt="ClubHub Logo" class="navbar-logo">
                <span>ClubHub</span>
            </a>
        </strong>
        <span class="brand-tagline"> - College Clubs Operations Hub</span>
    </p>

    <!-- Mobile Hamburger Toggle Button -->
    <button type="button" class="nav-toggle" id="navToggle" aria-label="Toggle navigation menu" aria-expanded="false" aria-controls="navbarMenu">
        <span class="hamburger-bar"></span>
        <span class="hamburger-bar"></span>
        <span class="hamburger-bar"></span>
    </button>

    <nav class="site-navbar" id="navbarMenu" aria-label="Main Navigation">
        <ul class="navbar-nav">
            <li class="nav-item"><a href="<?php echo e($base_url); ?>index.php" class="nav-link <?php echo $current_page === 'index.php' ? 'active' : ''; ?>">Home</a></li>
            <li class="nav-item"><a href="<?php echo e($base_url); ?>pages/clubs.php" class="nav-link <?php echo $current_page === 'clubs.php' ? 'active' : ''; ?>">Clubs</a></li>
            <li class="nav-item"><a href="<?php echo e($base_url); ?>pages/events.php" class="nav-link <?php echo $current_page === 'events.php' || $current_page === 'event-detail.php' ? 'active' : ''; ?>">Events</a></li>
            <li class="nav-item"><a href="<?php echo e($base_url); ?>pages/venues.php" class="nav-link <?php echo $current_page === 'venues.php' ? 'active' : ''; ?>">Venues</a></li>
            <li class="nav-item"><a href="<?php echo e($base_url); ?>pages/about.php" class="nav-link <?php echo $current_page === 'about.php' ? 'active' : ''; ?>">About</a></li>
            <li class="nav-item"><a href="<?php echo e($base_url); ?>pages/contact.php" class="nav-link <?php echo $current_page === 'contact.php' ? 'active' : ''; ?>">Contact</a></li>
            <?php if (is_logged_in()): ?>
                <?php $user = current_user(); ?>
                <li class="nav-item"><a href="<?php echo e($base_url); ?>pages/dashboard.php" class="nav-link <?php echo $current_page === 'dashboard.php' ? 'active' : ''; ?>">Dashboard</a></li>
                <?php if (has_role(['admin', 'club_head'])): ?>
                    <li class="nav-item"><a href="<?php echo e($base_url); ?>pages/budget.php" class="nav-link <?php echo $current_page === 'budget.php' ? 'active' : ''; ?>">Budget</a></li>
                <?php endif; ?>
                <li class="nav-item nav-user-info">
                    <span class="badge badge-role badge-<?php echo e($user['role']); ?>">
                        <?php echo e($user['name']); ?> (<?php echo e($user['role']); ?>)
                    </span>
                </li>
                <li class="nav-item"><a href="<?php echo e($base_url); ?>pages/logout.php" class="nav-link">Logout</a></li>
            <?php else: ?>
                <li class="nav-item"><a href="<?php echo e($base_url); ?>pages/login.php" class="nav-link <?php echo $current_page === 'login.php' ? 'active' : ''; ?>">Login</a></li>
                <li class="nav-item"><a href="<?php echo e($base_url); ?>pages/register.php" class="nav-link <?php echo $current_page === 'register.php' ? 'active' : ''; ?>">Register</a></li>
            <?php endif; ?>
        </ul>
    </nav>
</header>

<main class="site-main">
<?php
// Render flash message if set
$flash = get_flash();
if ($flash):
?>
    <aside class="flash-alert flash-<?php echo e($flash['type']); ?>" role="alert">
        <p><?php echo e($flash['message']); ?></p>
    </aside>
<?php endif; ?>
