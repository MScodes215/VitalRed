<?php
/**
 * VitalRed - Blood Request Approval & Dispatch Workflow
 * Implements the Staff Approval Workflow Pattern with status, approver, and timestamp tracking.
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth_helper.php';
require_auth(['admin']);

$error = '';
$admin_id = $_SESSION['user_id'];

// Handle Workflow Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $token = $_POST['csrf_token'] ?? '';

    if (!verify_csrf($token)) {
        set_flash('danger', 'Security session expired. Please retry.');
        redirect('admin/requests.php');
    }

    $request_id = intval($_POST['request_id'] ?? 0);

    if ($action === 'approve') {
        $notes = trim($_POST['approval_notes'] ?? 'Approved by blood bank staff.');
        try {
            $stmt = $pdo->prepare("UPDATE blood_requests 
                                  SET status = 'approved', approved_by = ?, approval_notes = ?, approved_at = NOW() 
                                  WHERE request_id = ? AND status = 'pending'");
            $stmt->execute([$admin_id, $notes, $request_id]);
            set_flash('success', "Request #REQ-{$request_id} has been Approved. Ready for unit allocation.");
            redirect('admin/requests.php');
        } catch (PDOException $e) {
            $error = 'Approval failed: ' . $e->getMessage();
        }
    } elseif ($action === 'reject') {
        $notes = trim($_POST['approval_notes'] ?? 'Rejected due to clinical contraindication or stock shortage.');
        try {
            $stmt = $pdo->prepare("UPDATE blood_requests 
                                  SET status = 'rejected', approved_by = ?, approval_notes = ?, approved_at = NOW() 
                                  WHERE request_id = ? AND status = 'pending'");
            $stmt->execute([$admin_id, $notes, $request_id]);
            set_flash('warning', "Request #REQ-{$request_id} was Rejected.");
            redirect('admin/requests.php');
        } catch (PDOException $e) {
            $error = 'Rejection failed: ' . $e->getMessage();
        }
    } elseif ($action === 'dispatch') {
        // Issue / allocate blood unit(s)
        $selected_units = $_POST['selected_units'] ?? [];
        if (empty($selected_units)) {
            $error = 'Please select at least one available blood unit to dispatch.';
        } else {
            try {
                $pdo->beginTransaction();

                // Check request details
                $r_stmt = $pdo->prepare("SELECT * FROM blood_requests WHERE request_id = ?");
                $r_stmt->execute([$request_id]);
                $req = $r_stmt->fetch();

                if (!$req || !in_array($req['status'], ['approved', 'pending'])) {
                    throw new Exception('Request is not in an approvable/issuable state.');
                }

                // Update selected units to 'issued' and link to this request
                $u_stmt = $pdo->prepare("UPDATE blood_units 
                                         SET status = 'issued', issued_request_id = ? 
                                         WHERE unit_id = ? AND status = 'available'");

                foreach ($selected_units as $uid) {
                    $u_stmt->execute([$request_id, intval($uid)]);
                }

                // Mark request as 'issued' with timestamp and approver
                $fin_stmt = $pdo->prepare("UPDATE blood_requests 
                                           SET status = 'issued', approved_by = COALESCE(approved_by, ?), issued_at = NOW() 
                                           WHERE request_id = ?");
                $fin_stmt->execute([$admin_id, $request_id]);

                $pdo->commit();
                set_flash('success', "Dispatched " . count($selected_units) . " blood unit(s) for #REQ-{$request_id}. Status updated to Issued.");
                redirect('admin/requests.php');
            } catch (Exception $e) {
                $pdo->rollBack();
                $error = 'Dispatch transaction failed: ' . $e->getMessage();
            }
        }
    }
}

// Fetch Filters
$status_filter = trim($_GET['status'] ?? '');
$urgency_filter = trim($_GET['urgency'] ?? '');

$sql = "SELECT br.*, r.patient_name, r.contact_phone, h.name AS hospital_name, h.city AS hospital_city, 
               bg.group_name, bg.rh_factor, u.full_name AS approver_name
        FROM blood_requests br
        JOIN recipients r ON br.recipient_id = r.recipient_id
        JOIN hospitals h ON br.hospital_id = h.hospital_id
        JOIN blood_groups bg ON br.blood_group_id = bg.blood_group_id
        LEFT JOIN users u ON br.approved_by = u.user_id
        WHERE 1=1";
$params = [];

if (!empty($status_filter)) {
    $sql .= " AND br.status = ?";
    $params[] = $status_filter;
}
if (!empty($urgency_filter)) {
    $sql .= " AND br.urgency = ?";
    $params[] = $urgency_filter;
}

$sql .= " ORDER BY 
            CASE br.status WHEN 'pending' THEN 1 WHEN 'approved' THEN 2 ELSE 3 END,
            CASE br.urgency WHEN 'Emergency' THEN 1 WHEN 'Urgent' THEN 2 ELSE 3 END,
            br.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$requests = $stmt->fetchAll();

// Pre-fetch available units for each blood group for the dispatch modal
$avail_units_stmt = $pdo->query("SELECT bu.*, bg.group_name, d.first_name, d.last_name 
                                  FROM blood_units bu
                                  JOIN blood_groups bg ON bu.blood_group_id = bg.blood_group_id
                                  JOIN donations dn ON bu.donation_id = dn.donation_id
                                  JOIN donors d ON dn.donor_id = d.donor_id
                                  WHERE bu.status = 'available' AND bu.expiry_date >= CURDATE()
                                  ORDER BY bu.expiry_date ASC");
$all_available_units = $avail_units_stmt->fetchAll();

$page_title = 'Blood Requisition & Approval Workflow';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container py-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
        <div>
            <h2 class="fw-bold mb-1"><i class="fa-solid fa-clipboard-check text-danger me-2"></i>Hospital Requisition Approval Workflow</h2>
            <p class="text-muted small mb-0">Multi-stage review: Pending &rarr; Staff Verified &rarr; Blood Unit Cross-Match &rarr; Issued Dispatch.</p>
        </div>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger py-2 px-3 small mb-3"><?= e($error) ?></div>
    <?php endif; ?>

    <!-- Filter Pills -->
    <div class="vr-card p-3 mb-4">
        <form method="GET" class="row g-2 align-items-center">
            <div class="col-md-5">
                <input type="text" id="tableSearchInput" class="form-control" placeholder="Search by patient, hospital, doctor...">
            </div>
            <div class="col-md-3">
                <select name="status" class="form-select" onchange="this.form.submit()">
                    <option value="">All Statuses</option>
                    <option value="pending" <?= $status_filter === 'pending' ? 'selected' : '' ?>>Pending Review</option>
                    <option value="approved" <?= $status_filter === 'approved' ? 'selected' : '' ?>>Approved (Ready to Dispatch)</option>
                    <option value="issued" <?= $status_filter === 'issued' ? 'selected' : '' ?>>Issued / Dispatched</option>
                    <option value="rejected" <?= $status_filter === 'rejected' ? 'selected' : '' ?>>Rejected</option>
                </select>
            </div>
            <div class="col-md-3">
                <select name="urgency" class="form-select" onchange="this.form.submit()">
                    <option value="">All Urgency Tiers</option>
                    <option value="Emergency" <?= $urgency_filter === 'Emergency' ? 'selected' : '' ?>>Emergency</option>
                    <option value="Urgent" <?= $urgency_filter === 'Urgent' ? 'selected' : '' ?>>Urgent</option>
                    <option value="Normal" <?= $urgency_filter === 'Normal' ? 'selected' : '' ?>>Normal</option>
                </select>
            </div>
            <div class="col-md-1 text-end">
                <a href="<?= BASE_URL ?>admin/requests.php" class="btn btn-light border w-100">Reset</a>
            </div>
        </form>
    </div>

    <!-- Requests Table -->
    <div class="vr-card p-4">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Req #</th>
                        <th>Patient &amp; Hospital</th>
                        <th>Blood Group</th>
                        <th>Required</th>
                        <th>Urgency</th>
                        <th>Reason / Clinical Indication</th>
                        <th>Status</th>
                        <th>Approver &amp; Timestamps</th>
                        <th class="text-end">Workflow Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($requests)): ?>
                        <tr><td colspan="9" class="text-center py-4 text-muted">No requisition records found.</td></tr>
                    <?php else: ?>
                        <?php foreach ($requests as $r): ?>
                            <tr>
                                <td class="font-monospace fw-bold text-dark">
                                    #REQ-<?= $r['request_id'] ?>
                                </td>
                                <td>
                                    <div class="fw-bold"><?= e($r['patient_name']) ?></div>
                                    <small class="text-muted"><i class="fa-solid fa-hospital me-1 text-secondary"></i><?= e($r['hospital_name']) ?> (<?= e($r['hospital_city']) ?>)</small>
                                </td>
                                <td><?= get_blood_badge($r['group_name']) ?></td>
                                <td><span class="badge bg-dark"><?= e($r['units_needed']) ?> Unit(s)</span></td>
                                <td><?= get_urgency_badge($r['urgency']) ?></td>
                                <td>
                                    <div class="small text-truncate" style="max-width: 200px;" title="<?= e($r['reason']) ?>">
                                        <?= e($r['reason']) ?>
                                    </div>
                                    <div class="text-muted" style="font-size:11px;">Doctor: <?= e($r['doctor_name']) ?></div>
                                </td>
                                <td><?= get_status_badge($r['status']) ?></td>
                                <td class="small">
                                    <?php if ($r['approver_name']): ?>
                                        <div class="fw-semibold text-primary"><i class="fa-solid fa-user-shield me-1"></i><?= e($r['approver_name']) ?></div>
                                    <?php endif; ?>
                                    <?php if ($r['approved_at']): ?>
                                        <div class="text-muted" style="font-size:11px;">Approved: <?= format_date($r['approved_at'], 'd M, H:i') ?></div>
                                    <?php endif; ?>
                                    <?php if ($r['issued_at']): ?>
                                        <div class="text-success" style="font-size:11px;">Issued: <?= format_date($r['issued_at'], 'd M, H:i') ?></div>
                                    <?php endif; ?>
                                    <?php if (!$r['approved_at'] && !$r['issued_at']): ?>
                                        <span class="text-muted" style="font-size:11px;">Filed: <?= format_date($r['created_at'], 'd M, H:i') ?></span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end">
                                    <?php if ($r['status'] === 'pending'): ?>
                                        <div class="btn-group">
                                            <button class="btn btn-outline-success btn-sm" data-bs-toggle="modal" data-bs-target="#approveModal<?= $r['request_id'] ?>">
                                                <i class="fa-solid fa-check me-1"></i>Approve
                                            </button>
                                            <button class="btn btn-outline-danger btn-sm" data-bs-toggle="modal" data-bs-target="#rejectModal<?= $r['request_id'] ?>">
                                                <i class="fa-solid fa-xmark"></i>
                                            </button>
                                        </div>
                                    <?php elseif ($r['status'] === 'approved'): ?>
                                        <button class="btn btn-vitalred btn-sm" data-bs-toggle="modal" data-bs-target="#dispatchModal<?= $r['request_id'] ?>">
                                            <i class="fa-solid fa-truck-ramp-box me-1"></i> Issue Units
                                        </button>
                                    <?php elseif ($r['status'] === 'issued'): ?>
                                        <?php
                                            // Find barcode of issued unit(s)
                                            $issued_units = $pdo->query("SELECT unit_barcode FROM blood_units WHERE issued_request_id = {$r['request_id']}")->fetchAll(PDO::FETCH_COLUMN);
                                        ?>
                                        <span class="badge bg-success-subtle text-success border border-success-subtle font-monospace" style="font-size:11px;">
                                            <?= implode(', ', $issued_units) ?: 'DISPATCHED' ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-light text-muted border">Rejected</span>
                                    <?php endif; ?>
                                </td>
                            </tr>

                            <!-- Approve Modal -->
                            <div class="modal fade" id="approveModal<?= $r['request_id'] ?>" tabindex="-1">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <form method="POST" action="<?= BASE_URL ?>admin/requests.php">
                                            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                            <input type="hidden" name="action" value="approve">
                                            <input type="hidden" name="request_id" value="<?= $r['request_id'] ?>">
                                            <div class="modal-header">
                                                <h5 class="modal-title fw-bold text-success"><i class="fa-solid fa-clipboard-check me-2"></i>Approve Blood Request #REQ-<?= $r['request_id'] ?></h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body">
                                                <p>Are you sure you want to approve this requisition for <strong><?= e($r['units_needed']) ?> unit(s)</strong> of <strong><?= e($r['group_name']) ?></strong> for <strong><?= e($r['patient_name']) ?></strong> at <?= e($r['hospital_name']) ?>?</p>
                                                <div class="mb-3">
                                                    <label class="form-label small fw-semibold">Clinical Approval Notes (Optional)</label>
                                                    <input type="text" class="form-control" name="approval_notes" value="Verified patient cross-match and hospital credentials." required>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                                                <button type="submit" class="btn btn-success">Confirm Approval</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>

                            <!-- Reject Modal -->
                            <div class="modal fade" id="rejectModal<?= $r['request_id'] ?>" tabindex="-1">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <form method="POST" action="<?= BASE_URL ?>admin/requests.php">
                                            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                            <input type="hidden" name="action" value="reject">
                                            <input type="hidden" name="request_id" value="<?= $r['request_id'] ?>">
                                            <div class="modal-header">
                                                <h5 class="modal-title fw-bold text-danger"><i class="fa-solid fa-ban me-2"></i>Reject Requisition #REQ-<?= $r['request_id'] ?></h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body">
                                                <div class="mb-3">
                                                    <label class="form-label small fw-semibold">Rejection Reason *</label>
                                                    <textarea class="form-control" name="approval_notes" rows="3" placeholder="Provide medical or procedural reason for declining this request..." required></textarea>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                                                <button type="submit" class="btn btn-danger">Reject Request</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>

                            <!-- Dispatch / Issue Modal -->
                            <div class="modal fade" id="dispatchModal<?= $r['request_id'] ?>" tabindex="-1">
                                <div class="modal-dialog modal-lg">
                                    <div class="modal-content">
                                        <form method="POST" action="<?= BASE_URL ?>admin/requests.php">
                                            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                            <input type="hidden" name="action" value="dispatch">
                                            <input type="hidden" name="request_id" value="<?= $r['request_id'] ?>">
                                            <div class="modal-header">
                                                <h5 class="modal-title fw-bold text-danger"><i class="fa-solid fa-truck-ramp-box me-2"></i>Allocate &amp; Dispatch Blood Units</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body">
                                                <div class="alert alert-light border mb-3">
                                                    <strong>Requirement:</strong> <?= e($r['units_needed']) ?> Unit(s) of <strong><?= e($r['group_name']) ?></strong> for patient <strong><?= e($r['patient_name']) ?></strong>.
                                                </div>

                                                <label class="form-label fw-bold small text-uppercase">Select Matching Available Blood Units (FIFO Order):</label>
                                                
                                                <?php 
                                                    // Filter available units matching this blood group
                                                    $matching = array_filter($all_available_units, fn($u) => $u['blood_group_id'] == $r['blood_group_id']);
                                                ?>

                                                <?php if (empty($matching)): ?>
                                                    <div class="alert alert-danger mb-0">
                                                        <i class="fa-solid fa-triangle-exclamation me-1"></i> No available <?= e($r['group_name']) ?> units in stock! Check the Blood Compatibility matrix for universal O- alternatives or register new units.
                                                    </div>
                                                <?php else: ?>
                                                    <div class="table-responsive border rounded-3">
                                                        <table class="table table-sm table-hover align-middle mb-0">
                                                            <thead class="table-light">
                                                                <tr>
                                                                    <th width="40">Select</th>
                                                                    <th>Barcode</th>
                                                                    <th>Storage Rack</th>
                                                                    <th>Expiry Date</th>
                                                                    <th>Source Donor</th>
                                                                </tr>
                                                            </thead>
                                                            <tbody>
                                                                <?php $counter = 0; foreach ($matching as $mu): $counter++; ?>
                                                                    <tr>
                                                                        <td>
                                                                            <input class="form-check-input" type="checkbox" name="selected_units[]" value="<?= $mu['unit_id'] ?>" <?= $counter <= $r['units_needed'] ? 'checked' : '' ?>>
                                                                        </td>
                                                                        <td class="font-monospace fw-bold text-danger"><?= e($mu['unit_barcode']) ?></td>
                                                                        <td><?= e($mu['storage_rack']) ?></td>
                                                                        <td class="small"><?= format_date($mu['expiry_date']) ?></td>
                                                                        <td class="small"><?= e($mu['first_name'] . ' ' . $mu['last_name']) ?></td>
                                                                    </tr>
                                                                <?php endforeach; ?>
                                                            </tbody>
                                                        </table>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                                                <?php if (!empty($matching)): ?>
                                                    <button type="submit" class="btn btn-vitalred">Confirm Dispatch &amp; Issue Certificate</button>
                                                <?php endif; ?>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>

                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
