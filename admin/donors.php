<?php
/**
 * VitalRed - Donors Directory Management (CRUD)
 * Features distinct Blood Type and City display with filtering.
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth_helper.php';
require_auth(['admin']);

$error = '';
$blood_groups = get_all_blood_groups($pdo);
try {
    $cities = $pdo->query("SELECT DISTINCT city FROM donors WHERE city IS NOT NULL AND city != '' ORDER BY city ASC")->fetchAll(PDO::FETCH_COLUMN);
} catch (Exception $e) {
    $cities = [];
}

// Handle Add Donor Form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $token = $_POST['csrf_token'] ?? '';

    if (!verify_csrf($token)) {
        set_flash('danger', 'Security session expired. Please retry.');
        redirect('admin/donors.php');
    }

    if ($action === 'add_donor') {
        $first_name = trim($_POST['first_name'] ?? '');
        $last_name = trim($_POST['last_name'] ?? '');
        $dob = $_POST['dob'] ?? '1995-01-01';
        $gender = $_POST['gender'] ?? 'Male';
        $blood_group_id = intval($_POST['blood_group_id'] ?? 1);
        $phone = trim($_POST['phone'] ?? '');
        $street = trim($_POST['street'] ?? 'Main Road');
        $city = trim($_POST['city'] ?? 'New Delhi');
        $pincode = trim($_POST['pincode'] ?? '110001');
        $emergency_contact = trim($_POST['emergency_contact'] ?? $phone);

        if (empty($first_name) || empty($phone)) {
            $error = 'First name and phone number are required.';
        } else {
            try {
                $pdo->beginTransaction();
                $stmt = $pdo->prepare("INSERT INTO donors (first_name, last_name, dob, gender, blood_group_id, address_street, city, state, pincode, emergency_contact) 
                                      VALUES (?, ?, ?, ?, ?, ?, ?, 'Delhi NCR', ?, ?)");
                $stmt->execute([$first_name, $last_name, $dob, $gender, $blood_group_id, $street, $city, $pincode, $emergency_contact]);
                $donor_id = $pdo->lastInsertId();

                if (!empty($phone)) {
                    $p_stmt = $pdo->prepare("INSERT INTO donor_phones (donor_id, phone_number, phone_type) VALUES (?, ?, 'Primary')");
                    $p_stmt->execute([$donor_id, $phone]);
                }

                $pdo->commit();
                set_flash('success', "Donor [{$first_name} {$last_name}] registered successfully.");
                redirect('admin/donors.php');
            } catch (Exception $e) {
                $pdo->rollBack();
                $error = 'Failed to register donor: ' . $e->getMessage();
            }
        }
    }
}

// Filters
$filter_group = intval($_GET['group'] ?? 0);
$filter_city = trim($_GET['city'] ?? '');

$sql = "SELECT d.*, bg.group_name AS blood_type, bg.rh_factor,
               GROUP_CONCAT(DISTINCT dp.phone_number SEPARATOR ', ') AS phone_numbers,
               COUNT(dn.donation_id) AS total_donations,
               DATEDIFF(CURDATE(), d.last_donation_date) AS days_since_last_donation,
               CASE 
                   WHEN d.last_donation_date IS NULL THEN 'Eligible (Never Donated)'
                   WHEN DATEDIFF(CURDATE(), d.last_donation_date) >= 90 THEN 'Eligible to Donate'
                   ELSE CONCAT('Cooldown: ', (90 - DATEDIFF(CURDATE(), d.last_donation_date)), 'd left')
               END AS eligibility_status
        FROM donors d
        JOIN blood_groups bg ON d.blood_group_id = bg.blood_group_id
        LEFT JOIN donor_phones dp ON d.donor_id = dp.donor_id
        LEFT JOIN donations dn ON d.donor_id = dn.donor_id
        WHERE 1=1";
$params = [];

if ($filter_group > 0) {
    $sql .= " AND d.blood_group_id = ?";
    $params[] = $filter_group;
}
if (!empty($filter_city)) {
    $sql .= " AND d.city = ?";
    $params[] = $filter_city;
}

$sql .= " GROUP BY d.donor_id, bg.group_name, bg.rh_factor
          ORDER BY d.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$donors = $stmt->fetchAll();

$page_title = 'Donors Directory';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container py-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
        <div>
            <h2 class="fw-bold mb-1"><i class="fa-solid fa-users text-danger me-2"></i>Voluntary Donors Directory</h2>
            <p class="text-muted small mb-0">Central verified voluntary donor repository tracking donations, contact numbers, and medical eligibility.</p>
        </div>
        <div class="d-flex gap-2">
            <button class="btn btn-vitalred" data-bs-toggle="modal" data-bs-target="#addDonorModal">
                <i class="fa-solid fa-user-plus me-1"></i> Add New Donor
            </button>
        </div>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger py-2 px-3 small mb-3"><?= e($error) ?></div>
    <?php endif; ?>

    <!-- Filter & Search Bar -->
    <div class="vr-card p-3 mb-4">
        <form method="GET" class="row g-2 align-items-center">
            <div class="col-md-4">
                <input type="text" id="tableSearchInput" class="form-control" placeholder="Search donor name, blood type, city, phone...">
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
                <a href="<?= BASE_URL ?>admin/donors.php" class="btn btn-light border w-100">Reset Filters</a>
            </div>
        </form>
    </div>

    <!-- Donors Table -->
    <div class="vr-card p-4">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Donor ID</th>
                        <th>Donor Name</th>
                        <th>Blood Type</th>
                        <th>City</th>
                        <th>Address</th>
                        <th>Contact Numbers</th>
                        <th>Gender &amp; Age</th>
                        <th>Donations</th>
                        <th>Eligibility Status</th>
                        <th>Last Donated</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($donors)): ?>
                        <tr><td colspan="10" class="text-center py-4 text-muted">No donor records found matching criteria.</td></tr>
                    <?php else: ?>
                        <?php foreach ($donors as $d): ?>
                            <?php 
                                $age = (new DateTime())->diff(new DateTime($d['dob']))->y;
                                $is_eligible = strpos($d['eligibility_status'], 'Eligible') !== false;
                            ?>
                            <tr>
                                <td class="font-monospace fw-bold text-muted">#DNR-<?= $d['donor_id'] ?></td>
                                <td>
                                    <div class="fw-bold text-dark"><?= e($d['first_name'] . ' ' . $d['last_name']) ?></div>
                                    <small class="text-muted">Emergency: <?= e($d['emergency_contact'] ?: 'None') ?></small>
                                </td>
                                <td>
                                    <?= get_blood_badge($d['blood_type']) ?>
                                    <span class="small text-muted d-block" style="font-size:11px;"><?= e($d['rh_factor']) ?></span>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark border px-2 py-1">
                                        <i class="fa-solid fa-location-dot text-danger me-1"></i><?= e($d['city']) ?>
                                    </span>
                                </td>
                                <td class="small text-muted" style="max-width: 150px;">
                                    <?= e($d['address_street']) ?>
                                </td>
                                <td class="small font-monospace text-primary"><?= e($d['phone_numbers'] ?: 'N/A') ?></td>
                                <td class="small"><?= e($d['gender']) ?>, <?= $age ?> yrs</td>
                                <td><span class="badge bg-dark px-2 py-1"><?= e($d['total_donations']) ?> times</span></td>
                                <td>
                                    <?php if ($is_eligible): ?>
                                        <span class="badge bg-success-subtle text-success border border-success-subtle">
                                            <i class="fa-solid fa-circle-check me-1"></i><?= e($d['eligibility_status']) ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-warning-subtle text-warning border border-warning-subtle text-dark">
                                            <i class="fa-solid fa-hourglass-half me-1"></i><?= e($d['eligibility_status']) ?>
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="small text-muted"><?= format_date($d['last_donation_date']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add Donor Modal -->
<div class="modal fade" id="addDonorModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" action="<?= BASE_URL ?>admin/donors.php">
                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                <input type="hidden" name="action" value="add_donor">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold text-danger"><i class="fa-solid fa-user-plus me-2"></i>Register New Blood Donor</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">First Name *</label>
                            <input type="text" class="form-control" name="first_name" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Last Name</label>
                            <input type="text" class="form-control" name="last_name">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Blood Type *</label>
                            <select class="form-select" name="blood_group_id">
                                <?php foreach ($blood_groups as $bg): ?>
                                    <option value="<?= $bg['blood_group_id'] ?>"><?= e($bg['group_name']) ?> (<?= e($bg['rh_factor']) ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Date of Birth *</label>
                            <input type="date" class="form-control" name="dob" value="1998-05-15" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Gender</label>
                            <select class="form-select" name="gender">
                                <option value="Male">Male</option>
                                <option value="Female">Female</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Phone Number *</label>
                            <input type="tel" class="form-control" name="phone" placeholder="+91 98765 43210" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Emergency Contact</label>
                            <input type="tel" class="form-control" name="emergency_contact" placeholder="+91 98111 00000">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Street Address</label>
                            <input type="text" class="form-control" name="street" placeholder="Address line">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-semibold">City *</label>
                            <input type="text" class="form-control" name="city" value="New Delhi" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-semibold">Pincode</label>
                            <input type="text" class="form-control" name="pincode" value="110001">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-vitalred">Save Donor</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
