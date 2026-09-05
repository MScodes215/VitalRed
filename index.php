<?php
/**
 * VitalRed - Public Landing Page & Live Stock Portal
 */

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth_helper.php';

// Fetch Live Group-Wise Stock
try {
    $stock_stmt = $pdo->query("SELECT * FROM vw_group_wise_stock ORDER BY blood_group_id ASC");
    $stocks = $stock_stmt->fetchAll();

    // Overall aggregate stats
    $total_available = 0;
    $critical_alerts_count = 0;
    foreach ($stocks as $s) {
        $total_available += $s['available_units'];
        if ($s['stock_status'] !== 'OPTIMAL') {
            $critical_alerts_count++;
        }
    }

    $donors_count = $pdo->query("SELECT COUNT(*) FROM donors")->fetchColumn();
    $hospitals_count = $pdo->query("SELECT COUNT(*) FROM hospitals WHERE is_active = 1")->fetchColumn();
    $fulfilled_count = $pdo->query("SELECT COUNT(*) FROM blood_requests WHERE status = 'issued'")->fetchColumn();

} catch (PDOException $e) {
    $stocks = [];
    $total_available = 0;
    $critical_alerts_count = 0;
    $donors_count = 0;
    $hospitals_count = 0;
    $fulfilled_count = 0;
}

$page_title = 'Home - Lifesaving Transfusion Network';
require_once __DIR__ . '/includes/header.php';
?>

<!-- Hero Banner -->
<section class="hero-banner">
    <div class="container position-relative" style="z-index: 2;">
        <div class="row align-items-center gy-4">
            <div class="col-lg-7 text-center text-lg-start">
                <span class="badge bg-white bg-opacity-20 text-white px-3 py-2 rounded-pill mb-3 fs-6">
                    <i class="fa-solid fa-heart-pulse me-2"></i> National Blood Transfusion Infrastructure
                </span>
                <h1 class="display-4 fw-bold text-white mb-3">
                    Every Drop Counts.<br>Every Second Matters.
                </h1>
                <p class="lead text-white-50 mb-4 pe-lg-5">
                    VitalRed links verified voluntary donors, certified medical officers, and hospital trauma units in real time. Ensuring complete traceability, rapid emergency dispatch, and cold-chain safety from donor arm to recipient bedside.
                </p>
                <div class="d-flex flex-wrap gap-3 justify-content-center justify-content-lg-start">
                    <a href="<?= BASE_URL ?>register.php" class="btn btn-light btn-lg px-4 py-3 fw-bold text-danger rounded-pill shadow">
                        <i class="fa-solid fa-hand-holding-heart me-2"></i> Register as Donor
                    </a>
                    <a href="<?= is_logged_in() ? (current_role() === 'requester' ? BASE_URL . 'requester/new_request.php' : BASE_URL . 'login.php') : BASE_URL . 'login.php' ?>" class="btn btn-outline-light btn-lg px-4 py-3 fw-bold rounded-pill">
                        <i class="fa-solid fa-truck-medical me-2"></i> Request Blood Units
                    </a>
                    <a href="#compatibility" class="btn btn-dark bg-black bg-opacity-40 border border-white border-opacity-25 btn-lg px-4 py-3 fw-bold rounded-pill">
                        <i class="fa-solid fa-circle-question me-2 text-warning"></i> Compatibility Guide
                    </a>
                </div>
            </div>

            <div class="col-lg-5">
                <div class="bg-white text-dark p-4 rounded-4 shadow-lg border">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="fw-bold mb-0 text-danger"><i class="fa-solid fa-tower-broadcast me-2"></i>Live System Snapshot</h5>
                        <span class="badge bg-success-subtle text-success border border-success-subtle pulse-dot">
                            <i class="fa-solid fa-circle fa-2xs me-1"></i> Live Sync
                        </span>
                    </div>

                    <div class="row g-3 text-center">
                        <div class="col-6">
                            <div class="p-3 bg-light rounded-3">
                                <div class="fs-2 fw-bold text-danger"><?= $total_available ?></div>
                                <div class="text-muted small fw-semibold">Available Units</div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="p-3 bg-light rounded-3">
                                <div class="fs-2 fw-bold text-primary"><?= $donors_count ?></div>
                                <div class="text-muted small fw-semibold">Registered Donors</div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="p-3 bg-light rounded-3">
                                <div class="fs-2 fw-bold text-success"><?= $fulfilled_count ?></div>
                                <div class="text-muted small fw-semibold">Dispatches Fulfilled</div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="p-3 bg-light rounded-3">
                                <div class="fs-2 fw-bold text-dark"><?= $hospitals_count ?></div>
                                <div class="text-muted small fw-semibold">Partner Hospitals</div>
                            </div>
                        </div>
                    </div>

                    <?php if ($critical_alerts_count > 0): ?>
                        <div class="alert alert-danger mb-0 mt-3 p-2 small text-center">
                            <i class="fa-solid fa-triangle-exclamation me-1"></i> <strong><?= $critical_alerts_count ?> blood groups</strong> are currently below safe threshold!
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Live Group-Wise Blood Availability Section -->
<section class="py-5 bg-white border-bottom" id="stock-section">
    <div class="container">
        <div class="text-center max-w-700 mx-auto mb-5">
            <span class="badge bg-danger-subtle text-danger px-3 py-1 mb-2"><i class="fa-solid fa-cubes me-1"></i> Real-Time Inventory Count</span>
            <h2 class="fw-bold">Available Blood Stock by Group</h2>
            <p class="text-muted">
                Direct verified counts of temperature-regulated, clinically tested, safe units ready in central storage.
            </p>
        </div>

        <div class="row g-3">
            <?php foreach ($stocks as $stock): ?>
                <?php 
                    $is_critical = $stock['stock_status'] !== 'OPTIMAL';
                    $status_class = $stock['stock_status'] === 'CRITICAL DEPLETED' ? 'bg-danger text-white' : ($stock['stock_status'] === 'LOW STOCK ALERT' ? 'bg-warning text-dark' : 'bg-success text-white');
                ?>
                <div class="col-6 col-md-3">
                    <div class="blood-stock-card <?= $is_critical ? 'critical' : '' ?>">
                        <div class="blood-type-badge"><?= e($stock['group_name']) ?></div>
                        <h4 class="fw-bold mb-1"><?= e($stock['available_units']) ?> <small class="fs-6 text-muted font-monospace">Units</small></h4>
                        <div class="small text-muted mb-2">Safety Buffer: <?= e($stock['critical_threshold']) ?> units</div>
                        <div>
                            <span class="badge <?= $status_class ?> px-2 py-1 small" style="font-size: 11px;">
                                <?= e($stock['stock_status']) ?>
                            </span>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="text-center mt-4">
            <small class="text-muted">
                <i class="fa-solid fa-circle-info me-1 text-primary"></i> All units are monitored 24/7 with strict 42-day lifespan cold-chain standards.
            </small>
        </div>
    </div>
</section>

<!-- Transfusion Compatibility Matrix -->
<section class="py-5 bg-light" id="compatibility">
    <div class="container">
        <div class="row align-items-center gy-4">
            <div class="col-lg-6">
                <span class="badge bg-primary-subtle text-primary px-3 py-1 mb-2">Clinical Reference</span>
                <h2 class="fw-bold mb-3">ABO &amp; Rh Compatibility Guide</h2>
                <p class="text-muted">
                    In emergency trauma situations where exact matching blood is in deficit, cross-matching guidelines dictate acceptable safe alternative units. <strong>O-</strong> is the universal red blood cell donor, and <strong>AB+</strong> is the universal recipient.
                </p>
                <div class="card p-3 border-0 shadow-sm rounded-3">
                    <div class="d-flex align-items-center gap-3">
                        <div class="fs-1 text-danger"><i class="fa-solid fa-shield-virus"></i></div>
                        <div>
                            <h6 class="fw-bold mb-1">Clinical Safety &amp; Cross-Matching</h6>
                            <p class="small text-muted mb-0">
                                VitalRed verifies compatibility during the medical review workflow whenever primary group stock requires backup allocation.
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="table-responsive bg-white rounded-3 shadow-sm border p-3">
                    <table class="table table-sm table-hover align-middle mb-0" style="font-size: 13px;">
                        <thead class="table-light">
                            <tr>
                                <th>Recipient</th>
                                <th>Compatible Donor Blood Groups</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr><td><strong class="text-danger">A+</strong></td><td>A+, A-, O+, O-</td></tr>
                            <tr><td><strong class="text-danger">A-</strong></td><td>A-, O-</td></tr>
                            <tr><td><strong class="text-primary">B+</strong></td><td>B+, B-, O+, O-</td></tr>
                            <tr><td><strong class="text-primary">B-</strong></td><td>B-, O-</td></tr>
                            <tr><td><strong class="text-purple">AB+</strong></td><td><em>All Types (Universal Recipient)</em></td></tr>
                            <tr><td><strong class="text-purple">AB-</strong></td><td>AB-, A-, B-, O-</td></tr>
                            <tr><td><strong class="text-success">O+</strong></td><td>O+, O-</td></tr>
                            <tr><td><strong class="text-success">O-</strong></td><td><em>O- Only (Universal Red Cell Donor)</em></td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- About VitalRed Section -->
<section class="py-5 bg-white border-top" id="about">
    <div class="container">
        <div class="row align-items-center gy-4">
            <div class="col-lg-5 text-center text-lg-start">
                <span class="badge bg-danger-subtle text-danger px-3 py-1 mb-2">About The Network</span>
                <h2 class="fw-bold mb-3">Connecting Donors &amp; Hospitals Since Inception</h2>
                <p class="text-muted">
                    VitalRed is dedicated to eliminating blood shortages during critical medical emergencies. Our digital platform bridges voluntary blood donors with certified transfusion officers and intensive care surgery units.
                </p>
                <div class="d-flex flex-column gap-2 text-start">
                    <div class="d-flex align-items-center gap-2 text-dark fw-semibold">
                        <i class="fa-solid fa-check-circle text-success"></i> 100% Tested &amp; Barcoded Units
                    </div>
                    <div class="d-flex align-items-center gap-2 text-dark fw-semibold">
                        <i class="fa-solid fa-check-circle text-success"></i> Strict 90-Day Donor Cooldown Protection
                    </div>
                    <div class="d-flex align-items-center gap-2 text-dark fw-semibold">
                        <i class="fa-solid fa-check-circle text-success"></i> Emergency Hospital Fast-Track Approval
                    </div>
                </div>
            </div>

            <div class="col-lg-7">
                <div class="row g-3">
                    <div class="col-sm-6">
                        <div class="p-4 border rounded-4 bg-light h-100">
                            <div class="fs-2 text-danger mb-2"><i class="fa-solid fa-temperature-arrow-down"></i></div>
                            <h5 class="fw-bold">Cold-Chain Compliance</h5>
                            <p class="small text-muted mb-0">Every blood unit is preserved in certified 4°C medical refrigeration units with continuous digital temperature surveillance.</p>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="p-4 border rounded-4 bg-light h-100">
                            <div class="fs-2 text-primary mb-2"><i class="fa-solid fa-certificate"></i></div>
                            <h5 class="fw-bold">Donor Recognition</h5>
                            <p class="small text-muted mb-0">Voluntary blood donors receive verified digital certificates of appreciation and personal health indicators following each donation.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Call to Action Banner -->
<section class="py-5 text-white" style="background: linear-gradient(135deg, #1e293b, #0f172a);">
    <div class="container text-center py-4">
        <h2 class="display-6 fw-bold mb-3">Join the Lifesaving Network Today</h2>
        <p class="lead text-white-50 max-w-700 mx-auto mb-4">
            Whether you are a voluntary donor willing to save lives, or a healthcare institution in need of emergency transfusion units, VitalRed is here for you.
        </p>
        <div class="d-flex justify-content-center gap-3">
            <a href="<?= BASE_URL ?>login.php" class="btn btn-vitalred btn-lg px-4 py-2">
                <i class="fa-solid fa-arrow-right-to-bracket me-2"></i> Access Portal
            </a>
            <a href="<?= BASE_URL ?>register.php" class="btn btn-outline-light btn-lg px-4 py-2">
                <i class="fa-solid fa-user-plus me-2"></i> Register as Donor
            </a>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
