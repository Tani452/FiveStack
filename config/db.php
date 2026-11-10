<?php
/**
 * ClubHub - Database Connection Configuration
 * Connects to MySQL using PHP Data Objects (PDO) with prepared statement settings.
 */

$db_host = 'localhost';
$db_port = '3306';
$db_name = 'clubhub';
$db_user = 'root';
$db_pass = ''; // Default XAMPP MySQL password is empty

$dsn = "mysql:host={$db_host};port={$db_port};dbname={$db_name};charset=utf8mb4";

$pdo_options = [
    // Throw exceptions on SQL errors for reliable error handling
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    // Return query results as associative arrays by default
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    // Disable emulated prepares to use native MySQL prepared statements
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $db_user, $db_pass, $pdo_options);
} catch (PDOException $e) {
    // If the database has not been imported or MySQL is stopped, display a friendly notice
    die("<h3>Database Connection Error</h3>
         <p>Could not connect to the ClubHub database.</p>
         <p><strong>Tips to resolve:</strong></p>
         <ol>
           <li>Make sure <strong>MySQL</strong> is started in your XAMPP Control Panel.</li>
           <li>Ensure the database <code>clubhub</code> is created and <code>sql/schema.sql</code> is imported.</li>
         </ol>
         <p><small>Error details: " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . "</small></p>");
}
