<?php
/**
 * VitalRed - Donor Dashboard Portal
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth_helper.php';
require_auth(['donor']);

$user_id = $_SESSION['user_id'];

// Fetch Donor Profile
$stmt = $pdo->prepare("SELECT d.*, bg.group_name, bg.rh_factor, u.email 
                      FROM donors d
                      JOIN blood_groups bg ON d.blood_group_id = bg.blood_group_id
                      JOIN users u ON d.user_id = u.user_id
                      WHERE d.user_id = ? LIMIT 1");
$stmt->execute([$user_id]);
$donor = $stmt->fetch();

if (!$donor) {
    // If user has no donor record yet (e.g. fresh google user), create one
    $pdo->prepare("INSERT INTO donors (user_id, first_name, last_name, dob, gender, blood_group_id, address_street, city, state, pincode, emergency_contact)
                  VALUES (?, ?, 'Donor', '1995-01-01', 'Male', 7, 'Default Street', 'New Delhi', 'Delhi NCR', '110001', '+91 99999 00000')")
        ->execute([$user_id, $_SESSION['user']['full_name']]);
    redirect('donor/index.php');
}

$donor_id = $donor['donor_id'];

// Calculate Eligibility
$last_date = $donor['last_donation_date'];
$is_eligible = true;
$cooldown_days_left = 0;
$percent_cooldown = 100;

if ($last_date) {
    $days_since = (new DateTime())->diff(new DateTime($last_date))->days;
    if ($days_since < 90) {
        $is_eligible = false;
        $cooldown_days_left = 90 - $days_since;
        $percent_cooldown = min(100, round(($days_since / 90) * 100));
    }
}

// Fetch Donation Records
$hist_stmt = $pdo->prepare("SELECT * FROM donations WHERE donor_id = ? ORDER BY donation_date DESC");
$hist_stmt->execute([$donor_id]);
$donations = $hist_stmt->fetchAll();

$total_donations = count($donations);
$total_units = array_sum(array_column($donations, 'units_collected'));
$lives_saved = $total_units * 3;

$page_title = 'Donor Portal';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container py-4">
    <!-- Welcome Header -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
        <div>
            <h2 class="fw-bold mb-1">
                Hello, <?= e($donor['first_name']) ?>! <span class="badge bg-danger-subtle text-danger fs-6"><?= e($donor['group_name']) ?> Donor</span>
            </h2>
            <p class="text-muted small mb-0">Track your voluntary donation history, medical eligibility, and lifesaving impact.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="<?= BASE_URL ?>donor/schedule.php" class="btn btn-vitalred <?= !$is_eligible ? 'disabled' : '' ?>">
                <i class="fa-solid fa-calendar-plus me-1"></i> Schedule Donation
            </a>
            <a href="<?= BASE_URL ?>donor/history.php" class="btn btn-outline-secondary">
                <i class="fa-solid fa-award me-1"></i> View Certificates
            </a>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <!-- Digital Donor Card -->
        <div class="col-lg-4">
            <div class="card border-0 shadow-lg text-white h-100" style="background: linear-gradient(135deg, #881337 0%, #be123c 100%); border-radius: 20px; overflow: hidden;">
                <div class="card-body p-4 position-relative">
                    <div class="d-flex justify-content-between align-items-start mb-4">
                        <div>
                            <div class="text-white-50 small text-uppercase fw-bold">Official Donor Card</div>
                            <h4 class="fw-bold text-white mb-0">VitalRed Donor</h4>
                        </div>
                        <span class="brand-icon-drop bg-white"><i class="fa-solid fa-droplet text-danger"></i></span>
                    </div>

                    <div class="d-flex align-items-center gap-3 mb-4">
                        <div class="bg-white text-danger fw-bold rounded-circle d-flex align-items-center justify-content-center shadow" style="width: 58px; height: 58px; font-size: 1.6rem;">
                            <?= e($donor['group_name']) ?>
                        </div>
                        <div>
                            <div class="fs-5 fw-bold"><?= e($donor['first_name'] . ' ' . $donor['last_name']) ?></div>
                            <div class="text-white-50 font-monospace small">#DNR-<?= str_pad($donor['donor_id'], 6, '0', STR_PAD_LEFT) ?></div>
                        </div>
                    </div>

                    <div class="pt-3 border-top border-white border-opacity-25 small">
                        <div class="d-flex justify-content-between mb-1">
                            <span class="text-white-50">Rh Factor:</span>
                            <span class="fw-semibold"><?= e($donor['rh_factor']) ?></span>
                        </div>
                        <div class="d-flex justify-content-between mb-1">
                            <span class="text-white-50">City:</span>
                            <span class="fw-semibold"><?= e($donor['city']) ?></span>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span class="text-white-50">Emergency Contact:</span>
                            <span class="fw-semibold font-monospace"><?= e($donor['emergency_contact']) ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Eligibility Countdown & Impact Stats -->
        <div class="col-lg-8">
            <div class="vr-card p-4 mb-4">
                <h5 class="fw-bold mb-3"><i class="fa-solid fa-heart-pulse text-danger me-2"></i>Medical Eligibility Status (90-Day Rule)</h5>
                
                <?php if ($is_eligible): ?>
                    <div class="alert alert-success d-flex align-items-center gap-3 mb-3">
                        <div class="fs-1 text-success"><i class="fa-solid fa-circle-check"></i></div>
                        <div>
                            <h6 class="fw-bold mb-1">You Are Clinically Eligible to Donate Today!</h6>
                            <p class="small mb-0">It has been over 90 days since your last donation. Hospital trauma banks need regular replenishment.</p>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="alert alert-warning d-flex align-items-center gap-3 mb-3">
                        <div class="fs-1 text-warning"><i class="fa-solid fa-hourglass-half"></i></div>
                        <div class="flex-grow-1">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <h6 class="fw-bold mb-0">Cooldown Period Active</h6>
                                <span class="badge bg-warning text-dark"><?= $cooldown_days_left ?> Days Left</span>
                            </div>
                            <p class="small mb-2">To safeguard donor health, clinical standards require a 90-day interval between whole blood donations.</p>
                            <div class="progress" style="height: 8px;">
                                <div class="progress-bar bg-warning" style="width: <?= $percent_cooldown ?>%"></div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="row g-3 text-center pt-2">
                    <div class="col-md-4">
                        <div class="p-3 bg-light rounded-3">
                            <div class="fs-2 fw-bold text-dark"><?= $total_donations ?></div>
                            <div class="text-muted small fw-semibold">Lifetime Donations</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="p-3 bg-light rounded-3">
                            <div class="fs-2 fw-bold text-danger"><?= $total_units ?> <small class="fs-6">Units</small></div>
                            <div class="text-muted small fw-semibold">Total Blood Contributed</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="p-3 bg-light rounded-3">
                            <div class="fs-2 fw-bold text-success"><?= $lives_saved ?></div>
                            <div class="text-muted small fw-semibold">Potential Lives Impacted</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Donation History -->
    <div class="vr-card p-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="fw-bold mb-0"><i class="fa-solid fa-clock-rotate-left text-danger me-2"></i>Recent Donation History</h5>
            <a href="<?= BASE_URL ?>donor/history.php" class="btn btn-outline-danger btn-sm">Full History &amp; Certificate &rarr;</a>
        </div>

        <?php if (empty($donations)): ?>
            <div class="alert alert-light border text-center py-4 text-muted mb-0">
                You haven't made any donations yet. Click "Schedule Donation" to schedule your first voluntary camp visit!
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Donation ID</th>
                            <th>Donor Name</th>
                            <th>Blood Type</th>
                            <th>City</th>
                            <th>Date</th>
                            <th>Donation Type</th>
                            <th>Units</th>
                            <th>Hemoglobin (Hb)</th>
                            <th>Blood Pressure</th>
                            <th>Remarks</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach (array_slice($donations, 0, 5) as $dn): ?>
                            <tr>
                                <td class="font-monospace fw-bold">#DON-<?= $dn['donation_id'] ?></td>
                                <td class="fw-bold"><?= e($donor['first_name'] . ' ' . $donor['last_name']) ?></td>
                                <td><?= get_blood_badge($donor['group_name']) ?></td>
                                <td><span class="badge bg-light text-dark border"><i class="fa-solid fa-location-dot text-danger me-1"></i><?= e($donor['city']) ?></span></td>
                                <td class="fw-semibold"><?= format_date($dn['donation_date']) ?></td>
                                <td><span class="badge bg-light text-dark border"><?= e($dn['donation_type']) ?></span></td>
                                <td><span class="badge bg-danger"><?= e($dn['units_collected']) ?> Unit</span></td>
                                <td><?= e($dn['hemoglobin_g_dl']) ?> g/dL</td>
                                <td><?= e($dn['blood_pressure']) ?></td>
                                <td class="small text-muted"><?= e($dn['remarks'] ?: 'Healthy donation') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
