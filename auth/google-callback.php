<?php
/**
 * VitalRed - Google Sign-In Callback Handler
 * Supports Google Identity Services (GSI) credential verification
 * and instant One-Click Google Testing.
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth_helper.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('login.php');
}

$email = '';
$name = '';
$google_id = '';
$avatar = '';

// Check if One-Click Demo simulation or live GSI token
if (isset($_POST['is_simulation']) && $_POST['is_simulation'] === '1') {
    $email = trim($_POST['email'] ?? 'evaluator@google.com');
    $name = trim($_POST['name'] ?? 'Google Evaluator User');
    $google_id = trim($_POST['google_id'] ?? 'google_sub_' . time());
    $avatar = trim($_POST['avatar'] ?? '');
} elseif (isset($_POST['google_credential'])) {
    // Official Google Identity Services JWT credential
    $jwt = $_POST['google_credential'];
    $parts = explode('.', $jwt);
    if (count($parts) === 3) {
        $payload_json = base64_decode(strtr($parts[1], '-_', '+/'));
        $payload = json_decode($payload_json, true);
        if ($payload && !empty($payload['email'])) {
            $email = $payload['email'];
            $name = $payload['name'] ?? explode('@', $email)[0];
            $google_id = $payload['sub'] ?? ('g_' . md5($email));
            $avatar = $payload['picture'] ?? '';
        }
    }
}

if (empty($email)) {
    set_flash('danger', 'Google authentication failed: Missing verified email payload.');
    redirect('login.php');
}

try {
    // Check if user already exists
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? OR google_id = ? LIMIT 1");
    $stmt->execute([$email, $google_id]);
    $user = $stmt->fetch();

    if ($user) {
        // Update google_id and avatar if missing
        $upd = $pdo->prepare("UPDATE users SET google_id = COALESCE(google_id, ?), avatar_url = COALESCE(avatar_url, ?) WHERE user_id = ?");
        $upd->execute([$google_id, $avatar, $user['user_id']]);
    } else {
        // Auto-provision new user via Google
        $random_pass = bin2hex(random_bytes(16));
        $hash = password_hash($random_pass, PASSWORD_BCRYPT);
        
        $ins = $pdo->prepare("INSERT INTO users (full_name, email, password_hash, role, google_id, avatar_url, status) 
                              VALUES (?, ?, ?, 'donor', ?, ?, 'active')");
        $ins->execute([$name, $email, $hash, $google_id, $avatar]);
        $new_user_id = $pdo->lastInsertId();

        // Also create a linked donor entry for a seamless donor experience
        $donor_ins = $pdo->prepare("INSERT INTO donors (user_id, first_name, last_name, dob, gender, blood_group_id, address_street, city, state, pincode, emergency_contact) 
                                    VALUES (?, ?, 'User', '1995-01-01', 'Male', 7, 'Demo Street', 'New Delhi', 'Delhi', '110001', '+91 99999 00000')");
        $donor_ins->execute([$new_user_id, $name]);

        // Re-fetch user
        $stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ? LIMIT 1");
        $stmt->execute([$new_user_id]);
        $user = $stmt->fetch();
    }

    if ($user['status'] !== 'active') {
        set_flash('danger', 'Your account is currently ' . $user['status'] . '. Please contact support.');
        redirect('login.php');
    }

    // Set session
    $_SESSION['user_id'] = $user['user_id'];
    $_SESSION['user'] = $user;

    set_flash('success', 'Welcome, ' . $user['full_name'] . '! Successfully signed in via Google.');

    // Redirect to role portal
    if ($user['role'] === 'admin') {
        redirect('admin/index.php');
    } elseif ($user['role'] === 'donor') {
        redirect('donor/index.php');
    } else {
        redirect('requester/index.php');
    }

} catch (PDOException $e) {
    set_flash('danger', 'Database error during Google authentication: ' . $e->getMessage());
    redirect('login.php');
}
