<?php
/**
 * VitalRed - Requisition Status & Dispatch Tracking
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth_helper.php';
require_auth(['requester']);

$user_id = $_SESSION['user_id'];
$focus_req_id = intval($_GET['req'] ?? 0);

// Fetch Recipient
$stmt = $pdo->prepare("SELECT recipient_id FROM recipients WHERE user_id = ? LIMIT 1");
$stmt->execute([$user_id]);
$recipient_id = $stmt->fetchColumn();

// Fetch Requests
$query = "SELECT br.*, bg.group_name, h.name AS hospital_name, u.full_name AS approver_name
          FROM blood_requests br
          JOIN blood_groups bg ON br.blood_group_id = bg.blood_group_id
          JOIN hospitals h ON br.hospital_id = h.hospital_id
          LEFT JOIN users u ON br.approved_by = u.user_id
          WHERE br.recipient_id = ?";
$params = [$recipient_id];

if ($focus_req_id > 0) {
    $query .= " AND br.request_id = ?";
    $params[] = $focus_req_id;
}
$query .= " ORDER BY br.created_at DESC";

$r_stmt = $pdo->prepare($query);
$r_stmt->execute($params);
$requests = $r_stmt->fetchAll();

$page_title = 'Track Blood Requisitions';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container py-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
        <div>
            <h2 class="fw-bold mb-1"><i class="fa-solid fa-truck-medical text-danger me-2"></i>Live Transfusion Dispatch Stepper</h2>
            <p class="text-muted small mb-0">Follow real-time status as staff verifies, approves, cross-matches, and issues physical blood units.</p>
        </div>
        <a href="<?= BASE_URL ?>requester/new_request.php" class="btn btn-vitalred">
            <i class="fa-solid fa-plus me-1"></i> New Request
        </a>
    </div>

    <?php if (empty($requests)): ?>
        <div class="vr-card p-5 text-center">
            <div class="fs-1 text-muted mb-3"><i class="fa-solid fa-clipboard-question"></i></div>
            <h4>No Requisitions Found</h4>
            <p class="text-muted">You have not submitted any blood requests yet.</p>
            <a href="<?= BASE_URL ?>requester/new_request.php" class="btn btn-vitalred">File Requisition Now</a>
        </div>
    <?php else: ?>
        <?php foreach ($requests as $req): ?>
            <?php
                // Fetch issued units if any
                $units_stmt = $pdo->prepare("SELECT unit_barcode, storage_rack, collection_date, expiry_date 
                                            FROM blood_units WHERE issued_request_id = ?");
                $units_stmt->execute([$req['request_id']]);
                $issued_units = $units_stmt->fetchAll();

                // Determine step status
                $step1 = true; // Filed
                $step2 = in_array($req['status'], ['approved', 'issued']); // Approved
                $step3 = $req['status'] === 'issued'; // Dispatched
                $is_rejected = $req['status'] === 'rejected';
            ?>
            <div class="vr-card p-4 p-md-5 mb-4">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 border-bottom pb-3 mb-4">
                    <div>
                        <span class="font-monospace fw-bold text-danger fs-5">#REQ-<?= $req['request_id'] ?></span>
                        <span class="ms-2"><?= get_blood_badge($req['group_name']) ?></span>
                        <span class="ms-1"><?= get_urgency_badge($req['urgency']) ?></span>
                    </div>
                    <div>
                        <span class="text-muted small me-2">Filed: <?= format_date($req['created_at'], 'd M Y, H:i') ?></span>
                        <?= get_status_badge($req['status']) ?>
                    </div>
                </div>

                <!-- Visual Stepper -->
                <?php if ($is_rejected): ?>
                    <div class="alert alert-danger p-3 mb-4">
                        <h6 class="fw-bold mb-1"><i class="fa-solid fa-circle-xmark me-2"></i>Requisition Rejected by Medical Staff</h6>
                        <p class="small mb-0"><strong>Reason:</strong> <?= e($req['approval_notes'] ?: 'No reason specified.') ?></p>
                    </div>
                <?php else: ?>
                    <div class="row text-center mb-5 gy-3">
                        <div class="col-4">
                            <div class="step-node mx-auto completed mb-2">
                                <i class="fa-solid fa-check"></i>
                            </div>
                            <div class="fw-bold small">1. Filed</div>
                            <div class="text-muted" style="font-size:11px;"><?= format_date($req['created_at'], 'd M, H:i') ?></div>
                        </div>

                        <div class="col-4">
                            <div class="step-node mx-auto <?= $step2 ? 'completed' : ($req['status'] === 'pending' ? 'active' : '') ?> mb-2">
                                <i class="fa-solid <?= $step2 ? 'fa-check' : 'fa-clipboard-check' ?>"></i>
                            </div>
                            <div class="fw-bold small">2. Medical Review &amp; Approval</div>
                            <div class="text-muted" style="font-size:11px;">
                                <?= $req['approved_at'] ? format_date($req['approved_at'], 'd M, H:i') : 'Pending Staff Review' ?>
                            </div>
                        </div>

                        <div class="col-4">
                            <div class="step-node mx-auto <?= $step3 ? 'completed' : ($step2 ? 'active' : '') ?> mb-2">
                                <i class="fa-solid <?= $step3 ? 'fa-check' : 'fa-truck-fast' ?>"></i>
                            </div>
                            <div class="fw-bold small">3. Dispatched &amp; Issued</div>
                            <div class="text-muted" style="font-size:11px;">
                                <?= $req['issued_at'] ? format_date($req['issued_at'], 'd M, H:i') : 'Awaiting Dispatch' ?>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Requisition Details -->
                <div class="row g-3 bg-light p-3 rounded-3">
                    <div class="col-md-3">
                        <small class="text-muted d-block">Hospital Destination</small>
                        <span class="fw-semibold"><?= e($req['hospital_name']) ?></span>
                    </div>
                    <div class="col-md-3">
                        <small class="text-muted d-block">Units Requested</small>
                        <span class="fw-semibold"><?= e($req['units_needed']) ?> Unit(s) of <?= e($req['group_name']) ?></span>
                    </div>
                    <div class="col-md-3">
                        <small class="text-muted d-block">Prescribing Doctor</small>
                        <span class="fw-semibold"><?= e($req['doctor_name']) ?></span>
                    </div>
                    <div class="col-md-3">
                        <small class="text-muted d-block">Clinical Reviewer</small>
                        <span class="fw-semibold text-primary"><?= e($req['approver_name'] ?: 'Pending Assignment') ?></span>
                    </div>
                </div>

                <!-- Issued Blood Units Section -->
                <?php if (!empty($issued_units)): ?>
                    <div class="mt-4 pt-3 border-top">
                        <h6 class="fw-bold text-success mb-2">
                            <i class="fa-solid fa-circle-check me-1"></i> Allocated &amp; Dispatched Physical Blood Units
                        </h6>
                        <div class="row g-2">
                            <?php foreach ($issued_units as $iu): ?>
                                <div class="col-md-6">
                                    <div class="p-3 border rounded-3 bg-white shadow-sm d-flex align-items-center justify-content-between">
                                        <div>
                                            <div class="font-monospace fw-bold text-danger fs-6">
                                                <i class="fa-solid fa-barcode me-1"></i><?= e($iu['unit_barcode']) ?>
                                            </div>
                                            <small class="text-muted">Storage: <?= e($iu['storage_rack']) ?> | Expiry: <?= format_date($iu['expiry_date']) ?></small>
                                        </div>
                                        <span class="badge bg-success">DISPATCHED</span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
