<?php
/**
 * VitalRed - Hospitals Directory Management (CRUD)
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth_helper.php';
require_auth(['admin']);

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $token = $_POST['csrf_token'] ?? '';

    if (!verify_csrf($token)) {
        set_flash('danger', 'Session expired. Please retry.');
        redirect('admin/hospitals.php');
    }

    if ($action === 'add_hospital') {
        $name = trim($_POST['name'] ?? '');
        $license_no = trim($_POST['license_no'] ?? '');
        $address = trim($_POST['address'] ?? '');
        $city = trim($_POST['city'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $contact_person = trim($_POST['contact_person'] ?? '');

        if (empty($name) || empty($license_no) || empty($phone)) {
            $error = 'Hospital name, license number, and phone number are required.';
        } else {
            try {
                $stmt = $pdo->prepare("INSERT INTO hospitals (name, license_no, address, city, state, pincode, phone, email, contact_person) 
                                      VALUES (?, ?, ?, ?, 'Delhi NCR', '110001', ?, ?, ?)");
                $stmt->execute([$name, $license_no, $address, $city, $phone, $email, $contact_person]);
                set_flash('success', "Hospital [{$name}] registered successfully.");
                redirect('admin/hospitals.php');
            } catch (PDOException $e) {
                $error = 'Failed to add hospital: ' . $e->getMessage();
            }
        }
    }
}

// Fetch Hospitals with request counts
$sql = "SELECT h.*, COUNT(br.request_id) AS total_requests,
               SUM(CASE WHEN br.status = 'issued' THEN 1 ELSE 0 END) AS fulfilled_requests
        FROM hospitals h
        LEFT JOIN blood_requests br ON h.hospital_id = br.hospital_id
        GROUP BY h.hospital_id
        ORDER BY h.name ASC";
$hospitals = $pdo->query($sql)->fetchAll();

$page_title = 'Hospitals Directory';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container py-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
        <div>
            <h2 class="fw-bold mb-1"><i class="fa-solid fa-hospital text-danger me-2"></i>Partner Hospital Network</h2>
            <p class="text-muted small mb-0">Authorized trauma centers, medical colleges, and specialty hospital transfusion desks.</p>
        </div>
        <button class="btn btn-vitalred" data-bs-toggle="modal" data-bs-target="#addHospitalModal">
            <i class="fa-solid fa-plus me-1"></i> Register New Hospital
        </button>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger py-2 px-3 small mb-3"><?= e($error) ?></div>
    <?php endif; ?>

    <div class="vr-card p-4">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Hospital Name</th>
                        <th>License / Accreditation</th>
                        <th>City / Address</th>
                        <th>Emergency Contact</th>
                        <th>Transfusion Officer</th>
                        <th>Total Requisitions</th>
                        <th>Fulfilled</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($hospitals as $h): ?>
                        <tr>
                            <td class="fw-bold text-dark">
                                <i class="fa-solid fa-hospital-user text-primary me-2"></i><?= e($h['name']) ?>
                            </td>
                            <td><span class="badge bg-light text-secondary border font-monospace"><?= e($h['license_no']) ?></span></td>
                            <td class="small text-muted"><?= e($h['city']) ?>, <?= e($h['address']) ?></td>
                            <td class="small font-monospace">
                                <div><i class="fa-solid fa-phone me-1 text-muted"></i><?= e($h['phone']) ?></div>
                                <div class="text-muted"><?= e($h['email']) ?></div>
                            </td>
                            <td class="small fw-semibold"><?= e($h['contact_person']) ?></td>
                            <td><span class="badge bg-dark"><?= e($h['total_requests']) ?></span></td>
                            <td><span class="badge bg-success"><?= e($h['fulfilled_requests']) ?></span></td>
                            <td>
                                <span class="badge bg-success-subtle text-success border border-success-subtle">
                                    <i class="fa-solid fa-check me-1"></i>Active
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add Hospital Modal -->
<div class="modal fade" id="addHospitalModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" action="<?= BASE_URL ?>admin/hospitals.php">
                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                <input type="hidden" name="action" value="add_hospital">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold text-danger"><i class="fa-solid fa-hospital me-2"></i>Register Partner Hospital</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-8">
                            <label class="form-label small fw-semibold">Hospital / Medical Center Name *</label>
                            <input type="text" class="form-control" name="name" placeholder="e.g., Columbia Asia Hospital" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">License Number *</label>
                            <input type="text" class="form-control" name="license_no" placeholder="LIC-MED-2026-01" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Address *</label>
                            <input type="text" class="form-control" name="address" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">City *</label>
                            <input type="text" class="form-control" name="city" value="New Delhi" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Phone Number *</label>
                            <input type="tel" class="form-control" name="phone" placeholder="+91 11 2000 0000" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Email Address *</label>
                            <input type="email" class="form-control" name="email" placeholder="bloodbank@hospital.com" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Contact Person / Transfusion Head</label>
                            <input type="text" class="form-control" name="contact_person" placeholder="Dr. Jane Doe">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-vitalred">Save Hospital</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
