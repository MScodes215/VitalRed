<?php
/**
 * VitalRed - BloodGroup Master Management
 * Direct management for Blood Groups, Rh Factors, Safety Thresholds, and Compatibility.
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth_helper.php';
require_auth(['admin']);

$error = '';

// Handle Update Critical Threshold
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $token = $_POST['csrf_token'] ?? '';

    if (!verify_csrf($token)) {
        set_flash('danger', 'Security session expired. Please retry.');
        redirect('admin/blood_groups.php');
    }

    if ($action === 'update_threshold') {
        $bg_id = intval($_POST['blood_group_id'] ?? 0);
        $threshold = intval($_POST['critical_threshold'] ?? 5);
        $desc = trim($_POST['description'] ?? '');

        try {
            $stmt = $pdo->prepare("UPDATE blood_groups SET critical_threshold = ?, description = ? WHERE blood_group_id = ?");
            $stmt->execute([$threshold, $desc, $bg_id]);
            set_flash('success', "Updated threshold for Blood Group #{$bg_id}.");
            redirect('admin/blood_groups.php');
        } catch (PDOException $e) {
            $error = 'Update failed: ' . $e->getMessage();
        }
    }
}

// Fetch Blood Groups with live stock counts
$sql = "SELECT bg.*, 
               COUNT(CASE WHEN bu.status = 'available' AND bu.expiry_date >= CURDATE() THEN bu.unit_id END) AS available_units,
               COUNT(CASE WHEN bu.status = 'reserved' THEN bu.unit_id END) AS reserved_units,
               COUNT(CASE WHEN bu.status = 'issued' THEN bu.unit_id END) AS total_issued,
               COUNT(bu.unit_id) AS total_units,
               CASE 
                   WHEN COUNT(CASE WHEN bu.status = 'available' AND bu.expiry_date >= CURDATE() THEN bu.unit_id END) = 0 THEN 'CRITICAL DEPLETED'
                   WHEN COUNT(CASE WHEN bu.status = 'available' AND bu.expiry_date >= CURDATE() THEN bu.unit_id END) < bg.critical_threshold THEN 'LOW STOCK ALERT'
                   ELSE 'OPTIMAL'
               END AS stock_status
        FROM blood_groups bg
        LEFT JOIN blood_units bu ON bg.blood_group_id = bu.blood_group_id
        GROUP BY bg.blood_group_id, bg.group_name, bg.rh_factor, bg.critical_threshold
        ORDER BY bg.blood_group_id ASC";
$groups = $pdo->query($sql)->fetchAll();

$page_title = 'BloodGroup Master';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container py-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
        <div>
            <h2 class="fw-bold mb-1"><i class="fa-solid fa-droplet text-danger me-2"></i>BloodGroup Master Directory</h2>
            <p class="text-muted small mb-0">ABO &amp; Rh Factor classification, clinical safety thresholds, and inventory status.</p>
        </div>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger py-2 px-3 small mb-3"><?= e($error) ?></div>
    <?php endif; ?>

    <div class="vr-card p-4">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>ID</th>
                        <th>BloodGroup</th>
                        <th>Rh Factor</th>
                        <th>Safety Threshold</th>
                        <th>Available Units</th>
                        <th>Reserved</th>
                        <th>Total Dispatched</th>
                        <th>Total Recorded</th>
                        <th>Stock Alert Status</th>
                        <th class="text-end">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($groups as $g): ?>
                        <?php 
                            $status_class = $g['stock_status'] === 'CRITICAL DEPLETED' ? 'bg-danger text-white' : ($g['stock_status'] === 'LOW STOCK ALERT' ? 'bg-warning text-dark' : 'bg-success text-white');
                        ?>
                        <tr>
                            <td class="font-monospace fw-bold text-muted">#BG-<?= $g['blood_group_id'] ?></td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <span class="fs-5 fw-bold"><?= get_blood_badge($g['group_name']) ?></span>
                                </div>
                            </td>
                            <td><span class="badge bg-light text-dark border"><?= e($g['rh_factor']) ?></span></td>
                            <td>
                                <span class="fw-bold font-monospace"><?= e($g['critical_threshold']) ?> Units</span>
                            </td>
                            <td>
                                <span class="fw-bold fs-6 <?= $g['available_units'] < $g['critical_threshold'] ? 'text-danger' : 'text-success' ?>">
                                    <?= e($g['available_units']) ?> Units
                                </span>
                            </td>
                            <td><span class="badge bg-light text-dark"><?= e($g['reserved_units']) ?></span></td>
                            <td><span class="badge bg-light text-dark"><?= e($g['total_issued']) ?></span></td>
                            <td class="fw-semibold"><?= e($g['total_units']) ?></td>
                            <td>
                                <span class="badge <?= $status_class ?> px-2 py-1">
                                    <?= e($g['stock_status']) ?>
                                </span>
                            </td>
                            <td class="text-end">
                                <button class="btn btn-outline-danger btn-sm" data-bs-toggle="modal" data-bs-target="#editModal<?= $g['blood_group_id'] ?>">
                                    <i class="fa-solid fa-pen-to-square me-1"></i> Edit Threshold
                                </button>
                            </td>
                        </tr>

                        <!-- Edit Threshold Modal -->
                        <div class="modal fade" id="editModal<?= $g['blood_group_id'] ?>" tabindex="-1">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <form method="POST" action="<?= BASE_URL ?>admin/blood_groups.php">
                                        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                        <input type="hidden" name="action" value="update_threshold">
                                        <input type="hidden" name="blood_group_id" value="<?= $g['blood_group_id'] ?>">
                                        <div class="modal-header">
                                            <h5 class="modal-title fw-bold text-danger">Edit <?= e($g['group_name']) ?> Threshold</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="mb-3">
                                                <label class="form-label small fw-semibold">Critical Minimum Safety Threshold (Units) *</label>
                                                <input type="number" class="form-control" name="critical_threshold" value="<?= e($g['critical_threshold']) ?>" min="1" max="50" required>
                                                <small class="text-muted">Alert triggers if available stock falls below this quantity.</small>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label small fw-semibold">Clinical Description / Compatibility Notes</label>
                                                <textarea class="form-control" name="description" rows="3"><?= e($g['description']) ?></textarea>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                                            <button type="submit" class="btn btn-vitalred">Save Changes</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>

                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
