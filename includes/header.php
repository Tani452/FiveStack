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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? e($page_title) . ' - ClubHub' : 'ClubHub - College Clubs Operations Hub'; ?></title>
    <!-- Linked stylesheet (empty for Phase 1) -->
    <link rel="stylesheet" href="<?php echo e($base_url); ?>assets/css/style.css">
</head>
<body>

<header>
    <p><strong><a href="<?php echo e($base_url); ?>index.php">ClubHub</a></strong> - College Clubs Operations Hub</p>
    <nav aria-label="Main Navigation">
        <ul>
            <li><a href="<?php echo e($base_url); ?>index.php">Home</a></li>
            <li><a href="<?php echo e($base_url); ?>pages/clubs.php">Clubs</a></li>
            <li><a href="<?php echo e($base_url); ?>pages/events.php">Events</a></li>
            <li><a href="<?php echo e($base_url); ?>pages/about.php">About</a></li>
            <li><a href="<?php echo e($base_url); ?>pages/contact.php">Contact</a></li>
            <li><a href="<?php echo e($base_url); ?>pages/login.php">Login</a></li>
            <li><a href="<?php echo e($base_url); ?>pages/register.php">Register</a></li>
        </ul>
    </nav>
</header>

<main>
