<?php
/**
 * VitalRed - Blood Bank Management System
 * Database Configuration & Global Setup
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Database Credentials (Supports Environment Variables for Cloud / Render and fallback for Localhost)
if (getenv('DATABASE_URL')) {
    $db_parts = parse_url(getenv('DATABASE_URL'));
    define('DB_HOST', $db_parts['host'] ?? 'localhost');
    define('DB_PORT', (string)($db_parts['port'] ?? '3306'));
    define('DB_NAME', ltrim($db_parts['path'] ?? 'vitalred_db', '/'));
    define('DB_USER', $db_parts['user'] ?? 'root');
    define('DB_PASS', $db_parts['pass'] ?? '');
} else {
    define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
    define('DB_PORT', (string)(getenv('DB_PORT') ?: '3306'));
    define('DB_NAME', getenv('DB_NAME') ?: 'vitalred_db');
    define('DB_USER', getenv('DB_USER') ?: 'root');
    define('DB_PASS', getenv('DB_PASS') !== false ? getenv('DB_PASS') : '');
}
define('DB_CHARSET', 'utf8mb4');

// Google OAuth Client ID Configuration (Optional - Can be provided or simulated)
define('GOOGLE_CLIENT_ID', getenv('GOOGLE_CLIENT_ID') ?: 'YOUR_GOOGLE_CLIENT_ID_HERE.apps.googleusercontent.com');

// Application Constants
define('APP_NAME', 'VitalRed');
define('APP_TAGLINE', 'National Blood Transfusion Network & Inventory Management');
define('APP_VERSION', '2.0.0-PRO');

// Detect Base URL dynamically (Supports Render HTTPS reverse proxy)
$is_https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || (($_SERVER['SERVER_PORT'] ?? 80) == 443)
    || (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');
$protocol = $is_https ? "https://" : "http://";
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$script_name = $_SERVER['SCRIPT_NAME'] ?? '';

// Determine base web path
if (stripos($script_name, '/VitalRed') !== false) {
    $base_path = '/VitalRed/';
} elseif (stripos($script_name, '/blood-bank-management-system') !== false) {
    $base_path = '/blood-bank-management-system/';
} else {
    // If run directly via php -S or Docker root
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

    // Automatically enable SSL for cloud providers (TiDB Cloud, Aiven, Railway)
    if (getenv('DB_SSL') === 'true' || stripos(DB_HOST, 'tidbcloud') !== false || stripos(DB_HOST, 'aiven') !== false) {
        if (file_exists('/etc/ssl/certs/ca-certificates.crt')) {
            $options[PDO::MYSQL_ATTR_SSL_CA] = '/etc/ssl/certs/ca-certificates.crt';
        }
        $options[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = false;
    }

    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
} catch (PDOException $e) {
    die("<div style='font-family:sans-serif;padding:30px;background:#fee2e2;color:#991b1b;border-radius:8px;max-width:600px;margin:50px auto;border:1px solid #f87171;'>
        <h2>Database Connection Error</h2>
        <p>Could not connect to MySQL database <strong>" . htmlspecialchars(DB_NAME) . "</strong> on <strong>" . htmlspecialchars(DB_HOST) . "</strong>.</p>
        <p><em>" . htmlspecialchars($e->getMessage()) . "</em></p>
        <hr style='border:0;border-top:1px solid #fca5a5;'>
        <p>If running locally, make sure MySQL is running in XAMPP. If on Render, please configure the database environment variables in your Render dashboard.</p>
    </div>");
}

/**
 * Robust helper to fetch blood groups with auto self-healing
 */
function get_all_blood_groups($pdo_conn = null) {
    global $pdo;
    $conn = $pdo_conn ?: $pdo;
    
    $default_groups = [
        ['blood_group_id' => 1, 'group_name' => 'A+',  'rh_factor' => 'Positive', 'critical_threshold' => 6],
        ['blood_group_id' => 2, 'group_name' => 'A-',  'rh_factor' => 'Negative', 'critical_threshold' => 3],
        ['blood_group_id' => 3, 'group_name' => 'B+',  'rh_factor' => 'Positive', 'critical_threshold' => 6],
        ['blood_group_id' => 4, 'group_name' => 'B-',  'rh_factor' => 'Negative', 'critical_threshold' => 3],
        ['blood_group_id' => 5, 'group_name' => 'AB+', 'rh_factor' => 'Positive', 'critical_threshold' => 4],
        ['blood_group_id' => 6, 'group_name' => 'AB-', 'rh_factor' => 'Negative', 'critical_threshold' => 2],
        ['blood_group_id' => 7, 'group_name' => 'O+',  'rh_factor' => 'Positive', 'critical_threshold' => 8],
        ['blood_group_id' => 8, 'group_name' => 'O-',  'rh_factor' => 'Negative', 'critical_threshold' => 4],
    ];

    if (!$conn) {
        return $default_groups;
    }

    try {
        $groups = $conn->query("SELECT * FROM blood_groups ORDER BY blood_group_id ASC")->fetchAll();
        if (!empty($groups)) {
            return $groups;
        }

        // Auto self-heal: populate if table is empty
        foreach ($default_groups as $g) {
            $conn->prepare("INSERT IGNORE INTO blood_groups (blood_group_id, group_name, rh_factor, critical_threshold, description) VALUES (?, ?, ?, ?, ?)")
                 ->execute([$g['blood_group_id'], $g['group_name'], $g['rh_factor'], $g['critical_threshold'], 'Standard ABO Blood Group']);
        }
        return $default_groups;
    } catch (Exception $e) {
        return $default_groups;
    }
}


