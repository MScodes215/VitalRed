<?php
/**
 * VitalRed - Authentication & Utility Helpers
 */

require_once __DIR__ . '/../config/db.php';

function e($string) {
    return htmlspecialchars((string)($string ?? ''), ENT_QUOTES, 'UTF-8');
}

function is_logged_in(): bool {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

function current_user(): ?array {
    return $_SESSION['user'] ?? null;
}

function current_role(): ?string {
    return $_SESSION['user']['role'] ?? null;
}

function redirect(string $path) {
    if (strpos($path, 'http') === 0) {
        header("Location: " . $path);
    } else {
        header("Location: " . BASE_URL . ltrim($path, '/'));
    }
    exit;
}

function require_auth(array $allowed_roles = []) {
    if (!is_logged_in()) {
        set_flash('danger', 'Please log in to access this page.');
        redirect('login.php');
    }

    if (!empty($allowed_roles)) {
        $user_role = current_role();
        if (!in_array($user_role, $allowed_roles)) {
            set_flash('danger', 'Access Denied: You do not have permission to view this resource.');
            if ($user_role === 'admin') {
                redirect('admin/index.php');
            } elseif ($user_role === 'donor') {
                redirect('donor/index.php');
            } elseif ($user_role === 'requester') {
                redirect('requester/index.php');
            } else {
                redirect('index.php');
            }
        }
    }
}

function set_flash(string $type, string $message) {
    $_SESSION['flash'] = [
        'type' => $type, // success, danger, warning, info
        'message' => $message
    ];
}

function get_flash(): ?array {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

function csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf(?string $token): bool {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token ?? '');
}

function format_date(?string $date, string $format = 'd M Y'): string {
    if (!$date) return 'N/A';
    try {
        $dt = new DateTime($date);
        return $dt->format($format);
    } catch (Exception $e) {
        return $date;
    }
}

function get_blood_badge(string $group): string {
    $colors = [
        'A+' => 'bg-danger text-white',
        'A-' => 'bg-danger text-white',
        'B+' => 'bg-primary text-white',
        'B-' => 'bg-info text-dark',
        'AB+' => 'bg-purple text-white',
        'AB-' => 'bg-dark text-white',
        'O+' => 'bg-success text-white',
        'O-' => 'bg-warning text-dark'
    ];
    $cls = $colors[$group] ?? 'bg-secondary text-white';
    return '<span class="badge ' . $cls . ' px-2 py-1 font-monospace"><i class="fa-solid fa-droplet me-1"></i>' . e($group) . '</span>';
}

function get_urgency_badge(string $urgency): string {
    switch ($urgency) {
        case 'Emergency':
            return '<span class="badge bg-danger text-white px-2 py-1"><i class="fa-solid fa-triangle-exclamation me-1"></i>Emergency</span>';
        case 'Urgent':
            return '<span class="badge bg-warning text-dark px-2 py-1"><i class="fa-solid fa-bolt me-1"></i>Urgent</span>';
        default:
            return '<span class="badge bg-secondary text-white px-2 py-1">Normal</span>';
    }
}

function get_status_badge(string $status): string {
    switch (strtolower($status)) {
        case 'available':
            return '<span class="badge bg-success"><i class="fa-solid fa-check me-1"></i>Available</span>';
        case 'reserved':
            return '<span class="badge bg-info text-dark"><i class="fa-solid fa-lock me-1"></i>Reserved</span>';
        case 'issued':
            return '<span class="badge bg-primary"><i class="fa-solid fa-hand-holding-medical me-1"></i>Issued</span>';
        case 'expired':
            return '<span class="badge bg-danger"><i class="fa-solid fa-ban me-1"></i>Expired</span>';
        case 'discarded':
            return '<span class="badge bg-secondary"><i class="fa-solid fa-trash me-1"></i>Discarded</span>';
        case 'pending':
            return '<span class="badge bg-warning text-dark"><i class="fa-solid fa-clock me-1"></i>Pending Review</span>';
        case 'approved':
            return '<span class="badge bg-info text-dark"><i class="fa-solid fa-check-circle me-1"></i>Approved</span>';
        case 'rejected':
            return '<span class="badge bg-danger"><i class="fa-solid fa-xmark me-1"></i>Rejected</span>';
        default:
            return '<span class="badge bg-light text-dark">' . e(ucfirst($status)) . '</span>';
    }
}
