<?php
/**
 * ClubHub - Database Connection Configuration
 * Connects to MySQL using PDO prepared statement options.
 * Catches connection exceptions, logs error, and renders friendly error page.
 */

require_once __DIR__ . '/../includes/functions.php';

$db_host = 'localhost';
$db_port = '3306';
$db_name = 'clubhub';
$db_user = 'root';
$db_pass = ''; // Default XAMPP MySQL password is empty

$dsn = "mysql:host={$db_host};port={$db_port};dbname={$db_name};charset=utf8mb4";

$pdo_options = [
    // Throw exceptions on SQL errors for reliable error handling
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    // Return associative arrays by default
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    // Disable emulated prepares to use native MySQL prepared statements
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $db_user, $db_pass, $pdo_options);
} catch (PDOException $e) {
    // Log detailed failure to logs/error.log
    log_error("Database Connection Failure: " . $e->getMessage(), [
        'code' => $e->getCode(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);

    // Render friendly user error page without raw stack traces
    render_custom_error_page();
}
