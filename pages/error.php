<?php
/**
 * ClubHub - Friendly Custom Error Page
 * Displays a clean, user-friendly notice without exposing PHP errors or stack traces.
 */

$page_title = 'System Notice';
require_once __DIR__ . '/../includes/header.php';
?>

<section class="page-header">
    <h1>Oops! Something Went Wrong</h1>
    <p>We encountered an unexpected situation while processing your request.</p>
</section>

<section class="card form-card">
    <h2>Notice</h2>
    <p>The system was unable to complete your last action. Don't worry—the issue has been recorded in our technical system logs (<code>logs/error.log</code>) for our administrator to review.</p>
    
    <p><strong>What can you do?</strong></p>
    <ul>
        <li>If you were connecting to the database, verify that <strong>MySQL is running in XAMPP</strong>.</li>
        <li>Ensure the database schema has been imported.</li>
        <li>Try refreshing the page or returning to the home portal.</li>
    </ul>

    <p class="form-actions">
        <a href="<?php echo e($base_url); ?>index.php" class="btn btn-primary">Return to Home</a>
        <a href="javascript:history.back()" class="btn btn-secondary">Go Back</a>
    </p>
</section>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>

