<?php
/**
 * VitalRed - Blood Donations Management Dashboard
 * Features dedicated sections for Blood Type Breakdown and City Distribution.
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth_helper.php';
require_auth(['admin']);

$error = '';
$blood_groups = $pdo->query("SELECT * FROM blood_groups ORDER BY group_name ASC")->fetchAll();
$cities = $pdo->query("SELECT DISTINCT city FROM donors WHERE city IS NOT NULL AND city != '' ORDER BY city ASC")->fetchAll(PDO::FETCH_COLUMN);

// Handle Record New Donation
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $token = $_POST['csrf_token'] ?? '';

    if (!verify_csrf($token)) {
        set_flash('danger', 'Security session expired. Please retry.');
        redirect('admin/donations.php');
    }

    if ($action === 'add_donation') {
        $donor_id = intval($_POST['donor_id'] ?? 0);
        $donation_date = $_POST['donation_date'] ?? date('Y-m-d');
        $units = intval($_POST['units'] ?? 1);
        $hb = floatval($_POST['hemoglobin'] ?? 13.5);
        $bp = trim($_POST['blood_pressure'] ?? '120/80');
        $donation_type = $_POST['donation_type'] ?? 'Whole Blood';
        $remarks = trim($_POST['remarks'] ?? 'Camp donation');
        $staff_id = $_SESSION['user_id'];

        $donor_stmt = $pdo->prepare("SELECT blood_group_id, first_name, last_name, city FROM donors WHERE donor_id = ? LIMIT 1");
        $donor_stmt->execute([$donor_id]);
        $donor_info = $donor_stmt->fetch();

        if (!$donor_info) {
            $error = 'Selected donor does not exist.';
        } else {
            try {
                $pdo->beginTransaction();

                $ins = $pdo->prepare("INSERT INTO donations (donor_id, blood_group_id, donation_date, units_collected, hemoglobin_g_dl, blood_pressure, donation_type, staff_id, remarks) 
                                      VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $ins->execute([$donor_id, $donor_info['blood_group_id'], $donation_date, $units, $hb, $bp, $donation_type, $staff_id, $remarks]);
                $donation_id = $pdo->lastInsertId();

                $bg_stmt = $pdo->prepare("SELECT group_name FROM blood_groups WHERE blood_group_id = ?");
                $bg_stmt->execute([$donor_info['blood_group_id']]);
                $bg_name = $bg_stmt->fetchColumn() ?: 'GEN';
                $prefix = str_replace(['+', '-'], ['POS', 'NEG'], $bg_name);

                for ($i = 1; $i <= $units; $i++) {
                    $barcode = 'UNIT-' . $prefix . '-' . date('Ymd', strtotime($donation_date)) . '-' . strtoupper(substr(bin2hex(random_bytes(2)), 0, 4));
                    $expiry = date('Y-m-d', strtotime($donation_date . ' + 42 days'));

                    $u_ins = $pdo->prepare("INSERT INTO blood_units (unit_barcode, donation_id, blood_group_id, collection_date, expiry_date, storage_rack, status) 
                                           VALUES (?, ?, ?, ?, ?, 'RACK-A1', 'available')");
                    $u_ins->execute([$barcode, $donation_id, $donor_info['blood_group_id'], $donation_date, $expiry]);
                }

                $pdo->commit();
                set_flash('success', "Donation recorded for {$donor_info['first_name']} {$donor_info['last_name']} ({$bg_name}, {$donor_info['city']}). Blood units added to stock.");
                redirect('admin/donations.php');

            } catch (Exception $e) {
                $pdo->rollBack();
                $error = 'Failed to record donation: ' . $e->getMessage();
            }
        }
    }
}

// Fetch Aggregates for Dedicated Blood Type & City Sections
$blood_type_stats = $pdo->query("SELECT bg.blood_group_id, bg.group_name, bg.rh_factor, 
                                        COUNT(dn.donation_id) AS total_donations, 
                                        COALESCE(SUM(dn.units_collected), 0) AS total_units 
                                 FROM blood_groups bg 
                                 LEFT JOIN donations dn ON bg.blood_group_id = dn.blood_group_id 
                                 GROUP BY bg.blood_group_id, bg.group_name, bg.rh_factor 
                                 ORDER BY bg.blood_group_id ASC")->fetchAll();

$city_stats = $pdo->query("SELECT d.city, 
                                  COUNT(dn.donation_id) AS total_donations, 
                                  COUNT(DISTINCT d.donor_id) AS total_donors, 
                                  COALESCE(SUM(dn.units_collected), 0) AS total_units 
                           FROM donors d 
                           LEFT JOIN donations dn ON d.donor_id = dn.donor_id 
                           WHERE d.city IS NOT NULL AND d.city != '' 
                           GROUP BY d.city 
                           ORDER BY total_donations DESC")->fetchAll();

// Filters
$filter_group = intval($_GET['group'] ?? 0);
$filter_city = trim($_GET['city'] ?? '');

$query = "SELECT dn.*, 
                 d.first_name, d.last_name, d.city, d.address_street,
                 bg.group_name AS blood_type, bg.rh_factor,
                 u.full_name AS staff_name
          FROM donations dn
          JOIN donors d ON dn.donor_id = d.donor_id
          JOIN blood_groups bg ON dn.blood_group_id = bg.blood_group_id
          LEFT JOIN users u ON dn.staff_id = u.user_id
          WHERE 1=1";
$params = [];

if ($filter_group > 0) {
    $query .= " AND dn.blood_group_id = ?";
    $params[] = $filter_group;
}
if (!empty($filter_city)) {
    $query .= " AND d.city = ?";
    $params[] = $filter_city;
}

$query .= " ORDER BY dn.donation_date DESC, dn.donation_id DESC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$donations = $stmt->fetchAll();

// Available donors for modal
$donor_list = $pdo->query("SELECT d.donor_id, d.first_name, d.last_name, d.city, bg.group_name 
                           FROM donors d 
                           JOIN blood_groups bg ON d.blood_group_id = bg.blood_group_id 
                           ORDER BY d.first_name ASC")->fetchAll();

$page_title = 'Blood Donations Log';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container py-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
        <div>
            <h2 class="fw-bold mb-1"><i class="fa-solid fa-heart-pulse text-danger me-2"></i>Donation Dashboard</h2>
            <p class="text-muted small mb-0">Overview of blood collections, volume breakdown by Blood Type, and City distribution.</p>
        </div>
        <div class="d-flex gap-2">
            <button class="btn btn-vitalred" data-bs-toggle="modal" data-bs-target="#recordDonationModal">
                <i class="fa-solid fa-plus me-1"></i> Record New Donation
            </button>
            <button class="btn btn-outline-secondary" onclick="exportTableToCSV('donationsTable', 'vitalred_donations.csv')">
                <i class="fa-solid fa-file-csv me-1"></i> Export CSV
            </button>
        </div>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger py-2 px-3 small mb-3"><?= e($error) ?></div>
    <?php endif; ?>

    <!-- ==================== SECTION 1: BLOOD TYPE SECTION ==================== -->
    <div class="vr-card p-4 mb-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h5 class="fw-bold mb-0 text-danger">
                    <i class="fa-solid fa-droplet me-2"></i>Donations by Blood Type
                </h5>
                <small class="text-muted">Total units and donation events categorized by ABO &amp; Rh factor</small>
            </div>
            <span class="badge bg-light text-muted border">8 Blood Types</span>
        </div>

        <div class="row g-2">
            <?php foreach ($blood_type_stats as $bts): ?>
                <div class="col-6 col-sm-4 col-md-3 col-lg-3">
                    <a href="<?= BASE_URL ?>admin/donations.php?group=<?= $bts['blood_group_id'] ?><?= !empty($filter_city) ? '&city=' . urlencode($filter_city) : '' ?>" 
                       class="text-decoration-none">
                        <div class="p-3 border rounded-3 bg-white text-center vr-card-hover <?= $filter_group == $bts['blood_group_id'] ? 'border-danger bg-danger-subtle' : '' ?>">
                            <div class="d-flex align-items-center justify-content-center gap-2 mb-1">
                                <span class="fs-4 fw-bold text-danger"><?= e($bts['group_name']) ?></span>
                                <span class="badge bg-light text-dark border small" style="font-size: 10px;"><?= e($bts['rh_factor']) ?></span>
                            </div>
                            <div class="fw-bold fs-5 text-dark"><?= e($bts['total_donations']) ?> <small class="fs-6 text-muted">donations</small></div>
                            <div class="small text-muted font-monospace"><?= e($bts['total_units']) ?> Unit(s) collected</div>
                        </div>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- ==================== SECTION 2: CITY DISTRIBUTION SECTION ==================== -->
    <div class="vr-card p-4 mb-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h5 class="fw-bold mb-0 text-primary">
                    <i class="fa-solid fa-city me-2"></i>Donations by City
                </h5>
                <small class="text-muted">Active donation camps, donor enrollment, and collections across geographic territories</small>
            </div>
            <span class="badge bg-light text-muted border"><?= count($city_stats) ?> Cities Active</span>
        </div>

        <div class="row g-3">
            <?php foreach ($city_stats as $cs): ?>
                <div class="col-sm-6 col-md-4 col-lg-3">
                    <a href="<?= BASE_URL ?>admin/donations.php?city=<?= urlencode($cs['city']) ?><?= $filter_group > 0 ? '&group=' . $filter_group : '' ?>" 
                       class="text-decoration-none">
                        <div class="p-3 border rounded-3 bg-white vr-card-hover <?= $filter_city === $cs['city'] ? 'border-primary bg-primary-subtle' : '' ?>">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <div class="fw-bold text-dark fs-6">
                                    <i class="fa-solid fa-location-dot text-danger me-1"></i><?= e($cs['city']) ?>
                                </div>
                                <span class="badge bg-danger text-white"><?= e($cs['total_units']) ?> Units</span>
                            </div>
                            <div class="d-flex justify-content-between text-muted small">
                                <span><i class="fa-solid fa-heart-pulse me-1 text-danger"></i><?= e($cs['total_donations']) ?> Donations</span>
                                <span><i class="fa-solid fa-users me-1 text-primary"></i><?= e($cs['total_donors']) ?> Donors</span>
                            </div>
                        </div>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Filter & Search Bar -->
    <div class="vr-card p-3 mb-4">
        <form method="GET" class="row g-2 align-items-center">
            <div class="col-md-4">
                <input type="text" id="tableSearchInput" class="form-control" placeholder="Search donor name, blood type, city...">
            </div>
            <div class="col-md-3">
                <select name="group" class="form-select" onchange="this.form.submit()">
                    <option value="0">All Blood Types</option>
                    <?php foreach ($blood_groups as $bg): ?>
                        <option value="<?= $bg['blood_group_id'] ?>" <?= $filter_group == $bg['blood_group_id'] ? 'selected' : '' ?>>
                            Blood Type: <?= e($bg['group_name']) ?> (<?= e($bg['rh_factor']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <select name="city" class="form-select" onchange="this.form.submit()">
                    <option value="">All Cities</option>
                    <?php foreach ($cities as $c): ?>
                        <option value="<?= e($c) ?>" <?= $filter_city === $c ? 'selected' : '' ?>>
                            City: <?= e($c) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2 text-end">
                <a href="<?= BASE_URL ?>admin/donations.php" class="btn btn-light border w-100">Reset Filters</a>
            </div>
        </form>
    </div>

    <!-- Donations Table -->
    <div class="vr-card p-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="fw-bold mb-0">Donation Records</h5>
            <span class="badge bg-dark"><?= count($donations) ?> Records Found</span>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" id="donationsTable">
                <thead class="table-light">
                    <tr>
                        <th>Donation ID</th>
                        <th>Donor Name</th>
                        <th>Blood Type</th>
                        <th>City</th>
                        <th>Donation Date</th>
                        <th>Units</th>
                        <th>Vitals (Hb &amp; BP)</th>
                        <th>Donation Type</th>
                        <th>Supervising Staff</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($donations)): ?>
                        <tr><td colspan="9" class="text-center py-4 text-muted">No donation records found matching criteria.</td></tr>
                    <?php else: ?>
                        <?php foreach ($donations as $dn): ?>
                            <tr>
                                <td class="font-monospace fw-bold text-dark">
                                    #DON-<?= $dn['donation_id'] ?>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="rounded-circle bg-danger-subtle text-danger fw-bold d-flex align-items-center justify-content-center" style="width:32px;height:32px;font-size:12px;">
                                            <?= strtoupper(substr($dn['first_name'], 0, 1)) ?>
                                        </div>
                                        <div>
                                            <div class="fw-bold text-dark"><?= e($dn['first_name'] . ' ' . $dn['last_name']) ?></div>
                                            <small class="text-muted font-monospace">#DNR-<?= $dn['donor_id'] ?></small>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <?= get_blood_badge($dn['blood_type']) ?>
                                    <span class="small text-muted d-block" style="font-size:11px;"><?= e($dn['rh_factor']) ?></span>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark border px-2 py-1">
                                        <i class="fa-solid fa-location-dot text-danger me-1"></i><?= e($dn['city']) ?>
                                    </span>
                                </td>
                                <td class="fw-semibold">
                                    <?= format_date($dn['donation_date']) ?>
                                </td>
                                <td>
                                    <span class="badge bg-dark px-2 py-1"><?= e($dn['units_collected']) ?> Unit(s)</span>
                                </td>
                                <td class="small">
                                    <div><strong>Hb:</strong> <?= e($dn['hemoglobin_g_dl']) ?> g/dL</div>
                                    <div class="text-muted"><strong>BP:</strong> <?= e($dn['blood_pressure']) ?></div>
                                </td>
                                <td>
                                    <span class="badge bg-secondary-subtle text-secondary"><?= e($dn['donation_type']) ?></span>
                                </td>
                                <td class="small text-muted">
                                    <i class="fa-solid fa-user-doctor me-1 text-primary"></i><?= e($dn['staff_name'] ?: 'Medical Officer') ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Record New Donation Modal -->
<div class="modal fade" id="recordDonationModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" action="<?= BASE_URL ?>admin/donations.php">
                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                <input type="hidden" name="action" value="add_donation">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold text-danger"><i class="fa-solid fa-heart-pulse me-2"></i>Record New Blood Donation</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-12">
                            <label class="form-label small fw-semibold">Select Registered Donor *</label>
                            <select class="form-select" name="donor_id" required>
                                <option value="">-- Choose Donor (Name, Blood Type, City) --</option>
                                <?php foreach ($donor_list as $dl): ?>
                                    <option value="<?= $dl['donor_id'] ?>">
                                        <?= e($dl['first_name'] . ' ' . $dl['last_name']) ?> | Blood Type: <?= e($dl['group_name']) ?> | City: <?= e($dl['city']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Donation Date *</label>
                            <input type="date" class="form-control" name="donation_date" value="<?= date('Y-m-d') ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Units Donated *</label>
                            <select class="form-select" name="units">
                                <option value="1" selected>1 Unit (450 mL)</option>
                                <option value="2">2 Units (Double Red Cell)</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Donation Type</label>
                            <select class="form-select" name="donation_type">
                                <option value="Whole Blood" selected>Whole Blood</option>
                                <option value="Platelets">Platelets</option>
                                <option value="Plasma">Plasma</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Hemoglobin (Hb g/dL) *</label>
                            <input type="number" step="0.1" class="form-control" name="hemoglobin" value="14.0" min="12.5" max="18.0" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Blood Pressure (BP) *</label>
                            <input type="text" class="form-control" name="blood_pressure" value="120/80" required>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label small fw-semibold">Remarks / Location</label>
                            <input type="text" class="form-control" name="remarks" value="Center voluntary walk-in donation">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-vitalred">Record Donation &amp; Add to Stock</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
