<?php
/**
 * CampusDesk — Database Configuration (PDO)
 * File: config/database.php
 * Academic Project: BCA Semester 3 (PHP & MySQL)
 */

declare(strict_types=1);

// Prevent direct script access if needed
if (!defined('CAMPUSDESK_APP')) {
    define('CAMPUSDESK_APP', true);
}

// Database Connection Settings for XAMPP Default
$db_host = 'localhost';
$db_port = '3306';
$db_name = 'campusdesk_db';
$db_user = 'root';
$db_pass = ''; // Default XAMPP MySQL root has no password
$db_charset = 'utf8mb4';

$dsn = "mysql:host={$db_host};port={$db_port};dbname={$db_name};charset={$db_charset}";

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // Throw exceptions on error
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,        // Return associative arrays
    PDO::ATTR_EMULATE_PREPARES   => false,                   // Use native prepared statements
    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES {$db_charset} COLLATE utf8mb4_unicode_ci"
];

try {
    $pdo = new PDO($dsn, $db_user, $db_pass, $options);
} catch (PDOException $e) {
    // Academic & Security best practice: do not leak credentials or stack traces to end users
    error_log("Database Connection Error: " . $e->getMessage());
    die("<div style='font-family: sans-serif; padding: 2rem; max-width: 600px; margin: 40px auto; border: 1px solid #f87171; background: #fef2f2; border-radius: 8px;'>
        <h2 style='color: #991b1b; margin-top: 0;'>CampusDesk Database Connection Failure</h2>
        <p style='color: #7f1d1d; line-height: 1.5;'>Could not establish a connection to MySQL server. Please verify:</p>
        <ul style='color: #7f1d1d; line-height: 1.6;'>
            <li>XAMPP Control Panel has <strong>MySQL</strong> and <strong>Apache</strong> started.</li>
            <li>Database <code>campusdesk_db</code> has been created and imported via phpMyAdmin using <code>database.sql</code>.</li>
            <li>Database credentials in <code>config/database.php</code> match your local environment.</li>
        </ul>
        <p style='color: #991b1b; font-size: 0.875rem;'>Technical message logged to server error log.</p>
    </div>");
}
