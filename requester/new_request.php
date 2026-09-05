<?php
/**
 * VitalRed - Submit New Blood Requisition
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth_helper.php';
require_auth(['requester']);

$user_id = $_SESSION['user_id'];

$stmt = $pdo->prepare("SELECT r.*, h.name AS hospital_name 
                      FROM recipients r 
                      JOIN hospitals h ON r.hospital_id = h.hospital_id 
                      WHERE r.user_id = ? LIMIT 1");
$stmt->execute([$user_id]);
$recipient = $stmt->fetch();

$blood_groups = get_all_blood_groups($pdo);
$hospitals = [];
try {
    $hospitals = $pdo->query("SELECT * FROM hospitals WHERE is_active = 1 ORDER BY name ASC")->fetchAll();
} catch (Exception $e) {}
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!verify_csrf($token)) {
        $error = 'Security session expired. Please retry.';
    } else {
        $hospital_id = intval($_POST['hospital_id'] ?? 1);
        $blood_group_id = intval($_POST['blood_group_id'] ?? 1);
        $units_needed = intval($_POST['units_needed'] ?? 1);
        $urgency = $_POST['urgency'] ?? 'Normal';
        $doctor_name = trim($_POST['doctor_name'] ?? '');
        $reason = trim($_POST['reason'] ?? '');

        if (empty($doctor_name) || empty($reason)) {
            $error = 'Attending doctor name and clinical indication are required.';
        } else {
            try {
                $ins = $pdo->prepare("INSERT INTO blood_requests (recipient_id, hospital_id, blood_group_id, units_needed, urgency, reason, doctor_name, status) 
                                      VALUES (?, ?, ?, ?, ?, ?, ?, 'pending')");
                $ins->execute([$recipient['recipient_id'], $hospital_id, $blood_group_id, $units_needed, $urgency, $reason, $doctor_name]);
                $new_req_id = $pdo->lastInsertId();

                set_flash('success', "Blood requisition #REQ-{$new_req_id} submitted successfully! It has been added to the urgent staff review queue.");
                redirect('requester/track.php?req=' . $new_req_id);
            } catch (PDOException $e) {
                $error = 'Submission failed: ' . $e->getMessage();
            }
        }
    }
}

$page_title = 'Submit Blood Request';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="vr-card p-4 p-md-5">
                <div class="text-center mb-4">
                    <span class="badge bg-danger-subtle text-danger px-3 py-1 mb-2"><i class="fa-solid fa-truck-medical me-1"></i> Hospital Requisition Form</span>
                    <h2 class="fw-bold">Request Blood Units</h2>
                    <p class="text-muted small">Submit clinical transfusion requirements for staff approval and immediate dispatch.</p>
                </div>

                <?php if ($error): ?>
                    <div class="alert alert-danger py-2 px-3 small mb-3"><?= e($error) ?></div>
                <?php endif; ?>

                <form method="POST" action="<?= BASE_URL ?>requester/new_request.php">
                    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Patient Full Name</label>
                            <input type="text" class="form-control" value="<?= e($recipient['patient_name']) ?>" readonly>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Admitted / Requesting Hospital *</label>
                            <select class="form-select" name="hospital_id" required>
                                <?php foreach ($hospitals as $h): ?>
                                    <option value="<?= $h['hospital_id'] ?>" <?= $recipient['hospital_id'] == $h['hospital_id'] ? 'selected' : '' ?>>
                                        <?= e($h['name']) ?> (<?= e($h['city']) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Required Blood Group *</label>
                            <select class="form-select" name="blood_group_id" required>
                                <?php foreach ($blood_groups as $bg): ?>
                                    <option value="<?= $bg['blood_group_id'] ?>" <?= $recipient['blood_group_id'] == $bg['blood_group_id'] ? 'selected' : '' ?>>
                                        <?= e($bg['group_name']) ?> (<?= e($bg['rh_factor']) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Units Needed *</label>
                            <select class="form-select" name="units_needed">
                                <option value="1">1 Unit (450 mL)</option>
                                <option value="2">2 Units (900 mL)</option>
                                <option value="3">3 Units (1,350 mL)</option>
                                <option value="4">4 Units (1,800 mL)</option>
                                <option value="5">5 Units (Massive Transfusion)</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Clinical Urgency Tier *</label>
                            <select class="form-select" name="urgency" required>
                                <option value="Normal">Normal (Scheduled Procedure)</option>
                                <option value="Urgent">Urgent (Within 4-6 Hours)</option>
                                <option value="Emergency">Emergency (Immediate / Trauma ICU)</option>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Attending Physician / Surgeon *</label>
                            <input type="text" class="form-control" name="doctor_name" placeholder="e.g., Dr. Meenakshi Sundaram" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Contact Phone *</label>
                            <input type="tel" class="form-control" value="<?= e($recipient['contact_phone']) ?>" readonly>
                        </div>

                        <div class="col-12">
                            <label class="form-label small fw-semibold">Clinical Reason / Surgical Indication *</label>
                            <textarea class="form-control" name="reason" rows="3" placeholder="Provide brief medical diagnosis, e.g., Acute internal hemorrhage following road traffic collision..." required></textarea>
                        </div>
                    </div>

                    <div class="d-grid mt-4">
                        <button type="submit" class="btn btn-vitalred py-2 fs-6">
                            <i class="fa-solid fa-paper-plane me-2"></i> Submit Requisition to Blood Bank Staff
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
