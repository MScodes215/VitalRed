<?php
/**
 * VitalRed - Blood Bank Management System
 * Database Configuration & Global Setup
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Database Credentials
define('DB_HOST', 'localhost');
define('DB_PORT', '3306');
define('DB_NAME', 'vitalred_db');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// Google OAuth Client ID Configuration (Optional - Can be provided or simulated)
define('GOOGLE_CLIENT_ID', 'YOUR_GOOGLE_CLIENT_ID_HERE.apps.googleusercontent.com');

// Application Constants
define('APP_NAME', 'VitalRed');
define('APP_TAGLINE', 'National Blood Transfusion Network & Inventory Management');
define('APP_VERSION', '2.0.0-PRO');

// Detect Base URL dynamically
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || ($_SERVER['SERVER_PORT'] ?? 80) == 443) ? "https://" : "http://";
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$script_name = $_SERVER['SCRIPT_NAME'] ?? '';

// Determine base web path
if (stripos($script_name, '/VitalRed') !== false) {
    $base_path = '/VitalRed/';
} elseif (stripos($script_name, '/blood-bank-management-system') !== false) {
    $base_path = '/blood-bank-management-system/';
} else {
    // If run directly via php -S localhost:8000
    $base_path = '/';
}
define('BASE_URL', $protocol . $host . $base_path);

try {
    $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
} catch (PDOException $e) {
    die("<div style='font-family:sans-serif;padding:30px;background:#fee2e2;color:#991b1b;border-radius:8px;max-width:600px;margin:50px auto;border:1px solid #f87171;'>
        <h2>Database Connection Error</h2>
        <p>Could not connect to MySQL database <strong>" . DB_NAME . "</strong>.</p>
        <p><em>" . htmlspecialchars($e->getMessage()) . "</em></p>
        <hr style='border:0;border-top:1px solid #fca5a5;'>
        <p>Please make sure MySQL is running in XAMPP and the schema has been imported.</p>
    </div>");
}
