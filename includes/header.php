<?php
/**
 * VitalRed - Common Header & Navigation
 * Menu strictly aligned to Core Roles & Tables:
 * Admin/Staff, Donor, Requester, BloodGroup, BloodUnit, Donation
 */
require_once __DIR__ . '/auth_helper.php';
$current_user = current_user();
$role = current_role();
$active_page = basename($_SERVER['PHP_SELF'] ?? '', '.php');
$req_uri = $_SERVER['REQUEST_URI'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($page_title) ? e($page_title) . ' - ' . APP_NAME : APP_NAME . ' | ' . APP_TAGLINE ?></title>
    
    <!-- Google Fonts: Inter & Plus Jakarta Sans -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Plus+Jakarta+Sans:wght@600;700;800&display=swap" rel="stylesheet">
    
    <!-- Bootstrap 5.3 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome 6 Icons -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
    
    <!-- Custom VitalRed Stylesheet -->
    <link href="<?= BASE_URL ?>assets/css/style.css" rel="stylesheet">
    
    <!-- Chart.js for Dashboards -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    
    <script>
        window.VITALRED_BASE_URL = '<?= BASE_URL ?>';
    </script>
</head>
<body>

<!-- Global Navigation Bar -->
<nav class="navbar navbar-expand-lg navbar-vitalred sticky-top shadow-sm">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center gap-2" href="<?= BASE_URL ?>index.php">
            <span class="brand-icon-drop">
                <i class="fa-solid fa-droplet text-white"></i>
            </span>
            <div class="brand-text-block">
                <span class="brand-title">Vital<span class="brand-title-accent">Red</span></span>
                <span class="brand-subtitle">Blood Bank System</span>
            </div>
        </a>

        <button class="navbar-toggler border-0 shadow-none" type="button" data-bs-toggle="collapse" data-bs-target="#navbarContent">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarContent">
            <!-- Exact Navigation Menu: Admin/Staff, Donor, Requester, BloodGroup, BloodUnit, Donation -->
            <ul class="navbar-nav me-auto mb-2 mb-lg-0 ms-lg-3">
                
                <!-- 1. Admin/Staff Menu Item -->
                <li class="nav-item">
                    <a class="nav-link text-nowrap <?= strpos($req_uri, 'admin/index') !== false ? 'active fw-bold' : '' ?>" 
                       href="<?= BASE_URL ?><?= $role === 'admin' ? 'admin/index.php' : 'login.php' ?>">
                        <i class="fa-solid fa-user-shield me-1 text-danger"></i> Admin/Staff
                    </a>
                </li>

                <!-- 2. Donor Menu Item -->
                <li class="nav-item">
                    <a class="nav-link text-nowrap <?= (strpos($req_uri, 'donor') !== false && strpos($req_uri, 'donations') === false) ? 'active fw-bold' : '' ?>" 
                       href="<?= BASE_URL ?><?= $role === 'admin' ? 'admin/donors.php' : ($role === 'donor' ? 'donor/index.php' : 'register.php?role=donor') ?>">
                        <i class="fa-solid fa-hand-holding-heart me-1 text-success"></i> Donor
                    </a>
                </li>

                <!-- 3. Requester Menu Item -->
                <li class="nav-item">
                    <a class="nav-link text-nowrap <?= strpos($req_uri, 'request') !== false ? 'active fw-bold' : '' ?>" 
                       href="<?= BASE_URL ?><?= $role === 'admin' ? 'admin/requests.php' : ($role === 'requester' ? 'requester/index.php' : 'requester/new_request.php') ?>">
                        <i class="fa-solid fa-hospital-user me-1 text-primary"></i> Requester
                    </a>
                </li>

                <!-- 4. BloodGroup Menu Item with Dropdown -->
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle text-nowrap <?= strpos($req_uri, 'blood_groups') !== false ? 'active fw-bold' : '' ?>" 
                       href="#" id="navbarBloodGroupDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fa-solid fa-droplet me-1 text-danger"></i> BloodGroup
                    </a>
                    <ul class="dropdown-menu shadow-sm border-0 py-2" aria-labelledby="navbarBloodGroupDropdown" style="min-width: 220px;">
                        <li>
                            <a class="dropdown-item fw-semibold text-danger" href="<?= BASE_URL ?><?= $role === 'admin' ? 'admin/blood_groups.php' : 'index.php#stock-section' ?>">
                                <i class="fa-solid fa-layer-group me-2"></i> All Blood Groups &amp; Inventory
                            </a>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li><h6 class="dropdown-header text-uppercase small text-muted">Filter by Group:</h6></li>
                        <li>
                            <div class="px-3 py-1 d-flex flex-wrap gap-1">
                                <a href="<?= BASE_URL ?>index.php#stock-section" class="badge bg-danger-subtle text-danger border border-danger-subtle text-decoration-none px-2 py-1">A+</a>
                                <a href="<?= BASE_URL ?>index.php#stock-section" class="badge bg-danger-subtle text-danger border border-danger-subtle text-decoration-none px-2 py-1">A-</a>
                                <a href="<?= BASE_URL ?>index.php#stock-section" class="badge bg-danger-subtle text-danger border border-danger-subtle text-decoration-none px-2 py-1">B+</a>
                                <a href="<?= BASE_URL ?>index.php#stock-section" class="badge bg-danger-subtle text-danger border border-danger-subtle text-decoration-none px-2 py-1">B-</a>
                                <a href="<?= BASE_URL ?>index.php#stock-section" class="badge bg-danger-subtle text-danger border border-danger-subtle text-decoration-none px-2 py-1">AB+</a>
                                <a href="<?= BASE_URL ?>index.php#stock-section" class="badge bg-danger-subtle text-danger border border-danger-subtle text-decoration-none px-2 py-1">AB-</a>
                                <a href="<?= BASE_URL ?>index.php#stock-section" class="badge bg-danger-subtle text-danger border border-danger-subtle text-decoration-none px-2 py-1">O+</a>
                                <a href="<?= BASE_URL ?>index.php#stock-section" class="badge bg-danger-subtle text-danger border border-danger-subtle text-decoration-none px-2 py-1">O-</a>
                            </div>
                        </li>
                    </ul>
                </li>

                <!-- 5. BloodUnit Menu Item -->
                <li class="nav-item">
                    <a class="nav-link text-nowrap <?= strpos($req_uri, 'stock') !== false ? 'active fw-bold' : '' ?>" 
                       href="<?= BASE_URL ?><?= $role === 'admin' ? 'admin/stock.php' : 'index.php#stock-section' ?>">
                        <i class="fa-solid fa-cubes-stacked me-1 text-warning"></i> BloodUnit
                    </a>
                </li>

                <!-- 6. Donation Menu Item -->
                <li class="nav-item">
                    <a class="nav-link text-nowrap <?= strpos($req_uri, 'donations') !== false || strpos($req_uri, 'schedule') !== false ? 'active fw-bold' : '' ?>" 
                       href="<?= BASE_URL ?><?= $role === 'admin' ? 'admin/donations.php' : ($role === 'donor' ? 'donor/history.php' : 'donor/schedule.php') ?>">
                        <i class="fa-solid fa-heart-pulse me-1 text-danger"></i> Donation
                    </a>
                </li>

                <?php if ($role === 'admin'): ?>
                    <li class="nav-item">
                        <a class="nav-link <?= strpos($req_uri, 'admin/reports') !== false ? 'active fw-bold' : '' ?>" href="<?= BASE_URL ?>admin/reports.php">
                            <i class="fa-solid fa-file-invoice me-1 text-secondary"></i> Reports
                        </a>
                    </li>
                <?php endif; ?>
            </ul>

            <div class="d-flex align-items-center gap-2">
                <?php if ($current_user): ?>
                    <div class="dropdown">
                        <button class="btn btn-light border dropdown-toggle d-flex align-items-center gap-2 py-1 px-2 rounded-pill" type="button" data-bs-toggle="dropdown">
                            <?php if (!empty($current_user['avatar_url'])): ?>
                                <img src="<?= e($current_user['avatar_url']) ?>" alt="Avatar" class="rounded-circle" width="28" height="28">
                            <?php else: ?>
                                <span class="bg-danger text-white rounded-circle d-inline-flex align-items-center justify-content-center" style="width:28px;height:28px;font-size:12px;">
                                    <?= strtoupper(substr($current_user['full_name'], 0, 1)) ?>
                                </span>
                            <?php endif; ?>
                            <span class="small fw-semibold"><?= e(explode(' ', $current_user['full_name'])[0]) ?></span>
                            <span class="badge bg-danger-subtle text-danger text-uppercase" style="font-size: 10px;"><?= e($current_user['role']) ?></span>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                            <li class="px-3 py-1 text-muted small border-bottom mb-1">
                                Signed in as<br><strong><?= e($current_user['email']) ?></strong>
                            </li>
                            <?php if ($role === 'admin'): ?>
                                <li><a class="dropdown-item" href="<?= BASE_URL ?>admin/index.php"><i class="fa-solid fa-gauge me-2 text-muted"></i>Dashboard</a></li>
                            <?php elseif ($role === 'donor'): ?>
                                <li><a class="dropdown-item" href="<?= BASE_URL ?>donor/index.php"><i class="fa-solid fa-gauge me-2 text-muted"></i>Dashboard</a></li>
                            <?php else: ?>
                                <li><a class="dropdown-item" href="<?= BASE_URL ?>requester/index.php"><i class="fa-solid fa-gauge me-2 text-muted"></i>Dashboard</a></li>
                            <?php endif; ?>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="<?= BASE_URL ?>auth/logout.php"><i class="fa-solid fa-right-from-bracket me-2"></i>Sign Out</a></li>
                        </ul>
                    </div>
                <?php else: ?>
                    <a href="<?= BASE_URL ?>login.php" class="btn btn-outline-secondary btn-sm px-3 rounded-pill">
                        <i class="fa-solid fa-arrow-right-to-bracket me-1"></i> Sign In
                    </a>
                    <a href="<?= BASE_URL ?>register.php" class="btn btn-vitalred btn-sm px-3 rounded-pill">
                        <i class="fa-solid fa-user-plus me-1"></i> Register
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</nav>

<!-- Flash Notifications Area -->
<div class="container mt-3">
    <?php if ($flash = get_flash()): ?>
        <div class="alert alert-<?= e($flash['type']) ?> alert-dismissible fade show shadow-sm" role="alert">
            <i class="fa-solid <?= $flash['type'] === 'success' ? 'fa-circle-check' : 'fa-circle-exclamation' ?> me-2"></i>
            <?= e($flash['message']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
</div>

<main class="flex-grow-1">
