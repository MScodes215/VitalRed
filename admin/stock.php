<?php
/**
 * VitalRed - Blood Stock & Inventory Management (CRUD)
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth_helper.php';
require_auth(['admin']);

$error = '';
$success = '';

// Handle Form Submissions (Add new unit or update status)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $token = $_POST['csrf_token'] ?? '';

    if (!verify_csrf($token)) {
        set_flash('danger', 'Security token expired. Please retry.');
        redirect('admin/stock.php');
    }

    if ($action === 'add_unit') {
        $donation_id = intval($_POST['donation_id'] ?? 0);
        $blood_group_id = intval($_POST['blood_group_id'] ?? 0);
        $storage_rack = trim($_POST['storage_rack'] ?? 'RACK-A1');
        $collection_date = $_POST['collection_date'] ?? date('Y-m-d');
        
        // Calculate expiry date: 42 days for Whole Blood
        $expiry_date = date('Y-m-d', strtotime($collection_date . ' + 42 days'));
        
        // Generate unique clinical barcode
        $prefix = $pdo->query("SELECT group_name FROM blood_groups WHERE blood_group_id = $blood_group_id")->fetchColumn() ?: 'GEN';
        $prefix = str_replace(['+', '-'], ['POS', 'NEG'], $prefix);
        $barcode = 'UNIT-' . $prefix . '-' . date('Ymd', strtotime($collection_date)) . '-' . strtoupper(substr(bin2hex(random_bytes(2)), 0, 4));

        try {
            $stmt = $pdo->prepare("INSERT INTO blood_units (unit_barcode, donation_id, blood_group_id, collection_date, expiry_date, storage_rack, status) 
                                  VALUES (?, ?, ?, ?, ?, ?, 'available')");
            $stmt->execute([$barcode, $donation_id, $blood_group_id, $collection_date, $expiry_date, $storage_rack]);
            set_flash('success', "Blood unit [{$barcode}] added to stock successfully.");
            redirect('admin/stock.php');
        } catch (PDOException $e) {
            $error = 'Failed to register blood unit: ' . $e->getMessage();
        }
    } elseif ($action === 'change_status') {
        $unit_id = intval($_POST['unit_id'] ?? 0);
        $new_status = $_POST['new_status'] ?? 'discarded';
        try {
            $stmt = $pdo->prepare("UPDATE blood_units SET status = ? WHERE unit_id = ?");
            $stmt->execute([$new_status, $unit_id]);
            set_flash('success', "Unit #{$unit_id} status updated to " . ucfirst($new_status));
            redirect('admin/stock.php');
        } catch (PDOException $e) {
            $error = 'Failed to update unit: ' . $e->getMessage();
        }
    }
}

// Fetch Filter Parameters
$filter_group = intval($_GET['group'] ?? 0);
$filter_status = trim($_GET['status'] ?? '');

$query = "SELECT bu.*, bg.group_name, bg.rh_factor, d.first_name, d.last_name, d.city, dn.donation_date 
          FROM blood_units bu
          JOIN blood_groups bg ON bu.blood_group_id = bg.blood_group_id
          JOIN donations dn ON bu.donation_id = dn.donation_id
          JOIN donors d ON dn.donor_id = d.donor_id
          WHERE 1=1";
$params = [];

if ($filter_group > 0) {
    $query .= " AND bu.blood_group_id = ?";
    $params[] = $filter_group;
}
if (!empty($filter_status)) {
    $query .= " AND bu.status = ?";
    $params[] = $filter_status;
}
$query .= " ORDER BY bu.expiry_date ASC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$units = $stmt->fetchAll();

$blood_groups = $pdo->query("SELECT * FROM blood_groups ORDER BY group_name ASC")->fetchAll();
$recent_donations = $pdo->query("SELECT dn.donation_id, dn.donation_date, bg.group_name, d.first_name, d.last_name 
                                  FROM donations dn
                                  JOIN donors d ON dn.donor_id = d.donor_id
                                  JOIN blood_groups bg ON dn.blood_group_id = bg.blood_group_id
                                  ORDER BY dn.donation_date DESC LIMIT 20")->fetchAll();

$page_title = 'Blood Stock & Inventory';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container py-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
        <div>
            <h2 class="fw-bold mb-1"><i class="fa-solid fa-boxes-stacked text-danger me-2"></i>Blood Inventory &amp; Traceability</h2>
            <p class="text-muted small mb-0">Full CRUD operations on unit barcodes, storage racks, shelf-life and donor linkage.</p>
        </div>
        <button class="btn btn-vitalred" data-bs-toggle="modal" data-bs-target="#addUnitModal">
            <i class="fa-solid fa-plus me-1"></i> Register New Blood Unit
        </button>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger py-2 px-3 small mb-3"><?= e($error) ?></div>
    <?php endif; ?>

    <!-- Filter & Search Bar -->
    <div class="vr-card p-3 mb-4">
        <form method="GET" class="row g-3 align-items-center">
            <div class="col-md-4">
                <input type="text" id="tableSearchInput" class="form-control" placeholder="Search barcode, rack, donor...">
            </div>
            <div class="col-md-3">
                <select name="group" class="form-select" onchange="this.form.submit()">
                    <option value="0">All Blood Groups</option>
                    <?php foreach ($blood_groups as $bg): ?>
                        <option value="<?= $bg['blood_group_id'] ?>" <?= $filter_group == $bg['blood_group_id'] ? 'selected' : '' ?>>
                            <?= e($bg['group_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <select name="status" class="form-select" onchange="this.form.submit()">
                    <option value="">All Statuses</option>
                    <option value="available" <?= $filter_status === 'available' ? 'selected' : '' ?>>Available</option>
                    <option value="reserved" <?= $filter_status === 'reserved' ? 'selected' : '' ?>>Reserved</option>
                    <option value="issued" <?= $filter_status === 'issued' ? 'selected' : '' ?>>Issued</option>
                    <option value="expired" <?= $filter_status === 'expired' ? 'selected' : '' ?>>Expired</option>
                    <option value="discarded" <?= $filter_status === 'discarded' ? 'selected' : '' ?>>Discarded</option>
                </select>
            </div>
            <div class="col-md-2 text-end">
                <a href="<?= BASE_URL ?>admin/stock.php" class="btn btn-light border w-100">Reset</a>
            </div>
        </form>
    </div>

    <!-- Inventory Table -->
    <div class="vr-card p-4">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" id="stockTable">
                <thead class="table-light">
                    <tr>
                        <th>Barcode</th>
                        <th>Blood Type</th>
                        <th>Source Donor</th>
                        <th>Donor City</th>
                        <th>Collection Date</th>
                        <th>Expiry Date</th>
                        <th>Shelf Life</th>
                        <th>Storage Rack</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($units)): ?>
                        <tr><td colspan="10" class="text-center py-4 text-muted">No blood units match the chosen filter.</td></tr>
                    <?php else: ?>
                        <?php foreach ($units as $u): ?>
                            <?php 
                                $days_left = (new DateTime())->diff(new DateTime($u['expiry_date']))->days;
                                $is_expired = new DateTime($u['expiry_date']) < new DateTime();
                            ?>
                            <tr>
                                <td class="font-monospace fw-bold text-danger">
                                    <i class="fa-solid fa-barcode me-1"></i><?= e($u['unit_barcode']) ?>
                                </td>
                                <td><?= get_blood_badge($u['group_name']) ?></td>
                                <td><?= e($u['first_name'] . ' ' . $u['last_name']) ?></td>
                                <td>
                                    <span class="badge bg-light text-dark border px-2 py-1">
                                        <i class="fa-solid fa-location-dot text-danger me-1"></i><?= e($u['city']) ?>
                                    </span>
                                </td>
                                <td class="small text-muted"><?= format_date($u['collection_date']) ?></td>
                                <td class="small fw-semibold <?= $is_expired ? 'text-danger' : '' ?>">
                                    <?= format_date($u['expiry_date']) ?>
                                </td>
                                <td>
                                    <?php if ($is_expired): ?>
                                        <span class="badge bg-danger">Expired</span>
                                    <?php elseif ($days_left <= 7): ?>
                                        <span class="badge bg-warning text-dark"><?= $days_left ?> days left</span>
                                    <?php else: ?>
                                        <span class="text-success small fw-semibold"><?= $days_left ?> days</span>
                                    <?php endif; ?>
                                </td>
                                <td><span class="badge bg-light text-dark border font-monospace"><?= e($u['storage_rack']) ?></span></td>
                                <td><?= get_status_badge($u['status']) ?></td>
                                <td class="text-end">
                                    <?php if ($u['status'] === 'available'): ?>
                                        <form method="POST" class="d-inline" onsubmit="return confirm('Mark this blood unit as discarded?');">
                                            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                            <input type="hidden" name="action" value="change_status">
                                            <input type="hidden" name="unit_id" value="<?= $u['unit_id'] ?>">
                                            <input type="hidden" name="new_status" value="discarded">
                                            <button type="submit" class="btn btn-outline-danger btn-sm py-0 px-2" title="Discard Unit">
                                                <i class="fa-solid fa-trash-can"></i>
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <span class="text-muted small">—</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add Blood Unit Modal -->
<div class="modal fade" id="addUnitModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="<?= BASE_URL ?>admin/stock.php">
                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                <input type="hidden" name="action" value="add_unit">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold text-danger"><i class="fa-solid fa-droplet me-2"></i>Register New Blood Unit</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Source Donation Record *</label>
                        <select class="form-select" name="donation_id" required>
                            <?php foreach ($recent_donations as $rd): ?>
                                <option value="<?= $rd['donation_id'] ?>">
                                    #<?= $rd['donation_id'] ?> - <?= e($rd['first_name'] . ' ' . $rd['last_name']) ?> (<?= e($rd['group_name']) ?>) on <?= format_date($rd['donation_date']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <small class="text-muted" style="font-size:11px;">Preserves referential integrity: Every unit ties back to a real donor donation event.</small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Blood Group *</label>
                        <select class="form-select" name="blood_group_id" required>
                            <?php foreach ($blood_groups as $bg): ?>
                                <option value="<?= $bg['blood_group_id'] ?>"><?= e($bg['group_name']) ?> (<?= e($bg['rh_factor']) ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Collection Date *</label>
                            <input type="date" class="form-control" name="collection_date" value="<?= date('Y-m-d') ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Storage Rack *</label>
                            <select class="form-select font-monospace" name="storage_rack">
                                <option value="RACK-A1">RACK-A1 (Refrig 4°C)</option>
                                <option value="RACK-A2">RACK-A2 (Refrig 4°C)</option>
                                <option value="RACK-B1">RACK-B1 (Refrig 4°C)</option>
                                <option value="RACK-C1">RACK-C1 (Refrig 4°C)</option>
                                <option value="RACK-D1">RACK-D1 (Emergency)</option>
                            </select>
                        </div>
                    </div>

                    <div class="alert alert-info py-2 small mb-0">
                        <i class="fa-solid fa-calendar-check me-1"></i> Expiration date will be automatically computed to <strong>+42 days</strong> from collection date (standard clinical shelf-life for Whole Blood).
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-vitalred">Confirm &amp; Generate Barcode</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
