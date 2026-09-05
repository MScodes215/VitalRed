<?php
/**
 * VitalRed - Requester / Hospital Portal Dashboard
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth_helper.php';
require_auth(['requester']);

$user_id = $_SESSION['user_id'];

// Fetch Recipient / Requester Profile
$stmt = $pdo->prepare("SELECT r.*, h.name AS hospital_name, bg.group_name 
                      FROM recipients r
                      JOIN hospitals h ON r.hospital_id = h.hospital_id
                      JOIN blood_groups bg ON r.blood_group_id = bg.blood_group_id
                      WHERE r.user_id = ? LIMIT 1");
$stmt->execute([$user_id]);
$recipient = $stmt->fetch();

if (!$recipient) {
    // If requester has no recipient record yet, create one
    $pdo->prepare("INSERT INTO recipients (user_id, hospital_id, patient_name, dob, gender, blood_group_id, contact_phone) 
                  VALUES (?, 1, ?, '1990-01-01', 'Male', 7, '+91 98000 00000')")
        ->execute([$user_id, $_SESSION['user']['full_name']]);
    redirect('requester/index.php');
}

$recipient_id = $recipient['recipient_id'];

// Fetch Requisitions filed by this recipient or user
$req_stmt = $pdo->prepare("SELECT br.*, bg.group_name, h.name AS hospital_name 
                          FROM blood_requests br
                          JOIN blood_groups bg ON br.blood_group_id = bg.blood_group_id
                          JOIN hospitals h ON br.hospital_id = h.hospital_id
                          WHERE br.recipient_id = ?
                          ORDER BY br.created_at DESC");
$req_stmt->execute([$recipient_id]);
$my_requests = $req_stmt->fetchAll();

// Live Stock Snapshot
$stocks = $pdo->query("SELECT * FROM vw_group_wise_stock ORDER BY blood_group_id ASC")->fetchAll();

$page_title = 'Hospital Requester Dashboard';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container py-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
        <div>
            <h2 class="fw-bold mb-1"><i class="fa-solid fa-hospital-user text-danger me-2"></i>Hospital Requisition Portal</h2>
            <p class="text-muted small mb-0">Patient: <strong><?= e($recipient['patient_name']) ?></strong> | Affiliated: <strong><?= e($recipient['hospital_name']) ?></strong></p>
        </div>
        <div class="d-flex gap-2">
            <a href="<?= BASE_URL ?>requester/new_request.php" class="btn btn-vitalred">
                <i class="fa-solid fa-hand-holding-medical me-1"></i> File New Blood Requisition
            </a>
            <a href="<?= BASE_URL ?>requester/track.php" class="btn btn-outline-secondary">
                <i class="fa-solid fa-truck-medical me-1"></i> Track Dispatches
            </a>
        </div>
    </div>

    <!-- Live Stock Grid for Requesters -->
    <div class="vr-card p-4 mb-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h5 class="fw-bold mb-0"><i class="fa-solid fa-tower-broadcast text-danger me-2"></i>Live Central Inventory Availability</h5>
                <small class="text-muted">Check available units before filing emergency requisitions.</small>
            </div>
            <span class="badge bg-light text-muted border">Live Sync</span>
        </div>

        <div class="row g-2">
            <?php foreach ($stocks as $s): ?>
                <div class="col-6 col-sm-3 col-md-3 col-lg-3">
                    <div class="p-2 border rounded-3 text-center bg-light">
                        <span class="fw-bold fs-5 text-danger"><?= e($s['group_name']) ?>:</span>
                        <span class="fw-bold fs-5 text-dark ms-1"><?= e($s['available_units']) ?></span>
                        <span class="small text-muted font-monospace">units</span>
                        <div>
                            <span class="badge <?= $s['available_units'] > 0 ? 'bg-success' : 'bg-danger' ?>" style="font-size:10px;">
                                <?= $s['available_units'] > 0 ? 'In Stock' : 'Depleted' ?>
                            </span>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- My Requisitions Table -->
    <div class="vr-card p-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="fw-bold mb-0"><i class="fa-solid fa-list-check text-danger me-2"></i>Submitted Requisitions</h5>
            <span class="badge bg-dark"><?= count($my_requests) ?> Total</span>
        </div>

        <?php if (empty($my_requests)): ?>
            <div class="alert alert-light border text-center py-4 text-muted mb-0">
                You haven't filed any blood requisitions yet. Click "File New Blood Requisition" above to submit one.
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Req ID</th>
                            <th>Date Filed</th>
                            <th>Blood Group</th>
                            <th>Units Needed</th>
                            <th>Urgency</th>
                            <th>Doctor &amp; Reason</th>
                            <th>Status</th>
                            <th class="text-end">Track</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($my_requests as $mr): ?>
                            <tr>
                                <td class="font-monospace fw-bold">#REQ-<?= $mr['request_id'] ?></td>
                                <td class="small text-muted"><?= format_date($mr['created_at'], 'd M Y, H:i') ?></td>
                                <td><?= get_blood_badge($mr['group_name']) ?></td>
                                <td><span class="badge bg-dark"><?= e($mr['units_needed']) ?> Unit(s)</span></td>
                                <td><?= get_urgency_badge($mr['urgency']) ?></td>
                                <td>
                                    <div class="small fw-semibold"><?= e($mr['doctor_name']) ?></div>
                                    <div class="small text-muted text-truncate" style="max-width: 220px;"><?= e($mr['reason']) ?></div>
                                </td>
                                <td><?= get_status_badge($mr['status']) ?></td>
                                <td class="text-end">
                                    <a href="<?= BASE_URL ?>requester/track.php?req=<?= $mr['request_id'] ?>" class="btn btn-outline-primary btn-sm">
                                        <i class="fa-solid fa-truck-medical me-1"></i> Track
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
