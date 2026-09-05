<?php
/**
 * VitalRed - Schedule Blood Donation Appointment
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth_helper.php';
require_auth(['donor']);

$user_id = $_SESSION['user_id'];

$stmt = $pdo->prepare("SELECT d.*, bg.group_name, bg.rh_factor FROM donors d 
                      JOIN blood_groups bg ON d.blood_group_id = bg.blood_group_id 
                      WHERE d.user_id = ? LIMIT 1");
$stmt->execute([$user_id]);
$donor = $stmt->fetch();

$hospitals = $pdo->query("SELECT * FROM hospitals WHERE is_active = 1 ORDER BY name ASC")->fetchAll();
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!verify_csrf($token)) {
        $error = 'Security session expired. Please retry.';
    } else {
        $donation_date = $_POST['donation_date'] ?? date('Y-m-d');
        $donation_type = $_POST['donation_type'] ?? 'Whole Blood';
        $hb = floatval($_POST['hemoglobin'] ?? 14.0);
        $bp = trim($_POST['blood_pressure'] ?? '120/80');

        try {
            $pdo->beginTransaction();

            $ins = $pdo->prepare("INSERT INTO donations (donor_id, blood_group_id, donation_date, units_collected, hemoglobin_g_dl, blood_pressure, donation_type, staff_id, remarks) 
                                  VALUES (?, ?, ?, 1, ?, ?, ?, 1, 'Scheduled voluntary portal appointment')");
            $ins->execute([$donor['donor_id'], $donor['blood_group_id'], $donation_date, $hb, $bp, $donation_type]);
            $donation_id = $pdo->lastInsertId();

            // Also register an available blood unit so stock is updated
            $prefix = str_replace(['+', '-'], ['POS', 'NEG'], $donor['group_name']);
            $barcode = 'UNIT-' . $prefix . '-' . date('Ymd', strtotime($donation_date)) . '-' . strtoupper(substr(bin2hex(random_bytes(2)), 0, 4));
            $expiry = date('Y-m-d', strtotime($donation_date . ' + 42 days'));

            $u_ins = $pdo->prepare("INSERT INTO blood_units (unit_barcode, donation_id, blood_group_id, collection_date, expiry_date, storage_rack, status) 
                                   VALUES (?, ?, ?, ?, ?, 'RACK-A1', 'available')");
            $u_ins->execute([$barcode, $donation_id, $donor['blood_group_id'], $donation_date, $expiry]);

            $pdo->commit();

            set_flash('success', "Thank you! Your donation was recorded and blood unit [{$barcode}] has been added to available stock.");
            redirect('donor/history.php');

        } catch (Exception $e) {
            $pdo->rollBack();
            $error = 'Failed to schedule donation: ' . $e->getMessage();
        }
    }
}

$page_title = 'Schedule Blood Donation';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-lg-7">
            <div class="vr-card p-4 p-md-5">
                <div class="text-center mb-4">
                    <span class="badge bg-danger-subtle text-danger px-3 py-1 mb-2"><i class="fa-solid fa-calendar-check me-1"></i> Voluntary Donation</span>
                    <h2 class="fw-bold">Schedule Donation Appointment</h2>
                    <p class="text-muted small">Select your preferred date, transfusion center, and clinical vitals.</p>
                </div>

                <?php if ($error): ?>
                    <div class="alert alert-danger py-2 px-3 small mb-3"><?= e($error) ?></div>
                <?php endif; ?>

                <form method="POST" action="<?= BASE_URL ?>donor/schedule.php">
                    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Preferred Center / Hospital *</label>
                            <select class="form-select" name="hospital_id" required>
                                <?php foreach ($hospitals as $h): ?>
                                    <option value="<?= $h['hospital_id'] ?>"><?= e($h['name']) ?> (<?= e($h['city']) ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Donation Date *</label>
                            <input type="date" class="form-control" name="donation_date" value="<?= date('Y-m-d') ?>" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Donation Type</label>
                            <select class="form-select" name="donation_type">
                                <option value="Whole Blood" selected>Whole Blood</option>
                                <option value="Platelets">Platelets (Apheresis)</option>
                                <option value="Plasma">Plasma</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Hemoglobin (Hb g/dL) *</label>
                            <input type="number" step="0.1" class="form-control" name="hemoglobin" value="13.5" min="12.5" max="18.0" required>
                            <div class="text-muted" style="font-size:11px;">Must be &ge; 12.5 g/dL</div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Blood Pressure (BP) *</label>
                            <input type="text" class="form-control" name="blood_pressure" value="120/80" placeholder="120/80" required>
                        </div>
                    </div>

                    <div class="d-grid mt-4">
                        <button type="submit" class="btn btn-vitalred py-2 fs-6">
                            <i class="fa-solid fa-check me-2"></i> Confirm Appointment &amp; Register Donation
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
