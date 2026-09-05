<?php
/**
 * VitalRed - User Registration Portal
 * Registers new Donors and Hospital Requesters
 */

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth_helper.php';

if (is_logged_in()) {
    redirect('index.php');
}

$error = '';
try {
    $blood_groups = $pdo->query("SELECT * FROM blood_groups ORDER BY group_name ASC")->fetchAll();
    $hospitals = $pdo->query("SELECT * FROM hospitals WHERE is_active = 1 ORDER BY name ASC")->fetchAll();
} catch (Exception $e) {
    $blood_groups = [];
    $hospitals = [];
}
if (empty($blood_groups)) {
    $blood_groups = [
        ['blood_group_id' => 1, 'group_name' => 'A+', 'rh_factor' => 'Positive'],
        ['blood_group_id' => 2, 'group_name' => 'A-', 'rh_factor' => 'Negative'],
        ['blood_group_id' => 3, 'group_name' => 'B+', 'rh_factor' => 'Positive'],
        ['blood_group_id' => 4, 'group_name' => 'B-', 'rh_factor' => 'Negative'],
        ['blood_group_id' => 5, 'group_name' => 'AB+', 'rh_factor' => 'Positive'],
        ['blood_group_id' => 6, 'group_name' => 'AB-', 'rh_factor' => 'Negative'],
        ['blood_group_id' => 7, 'group_name' => 'O+', 'rh_factor' => 'Positive'],
        ['blood_group_id' => 8, 'group_name' => 'O-', 'rh_factor' => 'Negative'],
    ];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!verify_csrf($token)) {
        $error = 'Session expired. Please try again.';
    } else {
        $role = $_POST['role'] ?? 'donor';
        $full_name = trim($_POST['full_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';

        if (empty($full_name) || empty($email) || empty($password)) {
            $error = 'Please fill out all required fields.';
        } elseif ($password !== $confirm_password) {
            $error = 'Passwords do not match.';
        } elseif (strlen($password) < 6) {
            $error = 'Password must be at least 6 characters.';
        } else {
            // Check if email is taken
            $stmt = $pdo->prepare("SELECT user_id FROM users WHERE email = ? LIMIT 1");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $error = 'This email address is already registered.';
            } else {
                try {
                    $pdo->beginTransaction();

                    $hash = password_hash($password, PASSWORD_BCRYPT);
                    $ins = $pdo->prepare("INSERT INTO users (full_name, email, password_hash, phone, role, status) VALUES (?, ?, ?, ?, ?, 'active')");
                    $ins->execute([$full_name, $email, $hash, $phone, $role]);
                    $user_id = $pdo->lastInsertId();

                    if ($role === 'donor') {
                        $parts = explode(' ', $full_name);
                        $first_name = $parts[0];
                        $last_name = count($parts) > 1 ? implode(' ', array_slice($parts, 1)) : 'Donor';

                        $dob = $_POST['dob'] ?? '2000-01-01';
                        $gender = $_POST['gender'] ?? 'Male';
                        $blood_group_id = intval($_POST['blood_group_id'] ?? 1);
                        $street = trim($_POST['street'] ?? 'Main Road');
                        $city = trim($_POST['city'] ?? 'New Delhi');
                        $pincode = trim($_POST['pincode'] ?? '110001');
                        $emergency_contact = trim($_POST['emergency_contact'] ?? $phone);

                        $d_stmt = $pdo->prepare("INSERT INTO donors (user_id, first_name, last_name, dob, gender, blood_group_id, address_street, city, state, pincode, emergency_contact) 
                                                VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'Delhi NCR', ?, ?)");
                        $d_stmt->execute([$user_id, $first_name, $last_name, $dob, $gender, $blood_group_id, $street, $city, $pincode, $emergency_contact]);
                        $donor_id = $pdo->lastInsertId();

                        // Add primary phone to donor_phones multivalued table
                        if (!empty($phone)) {
                            $p_stmt = $pdo->prepare("INSERT INTO donor_phones (donor_id, phone_number, phone_type) VALUES (?, ?, 'Primary')");
                            $p_stmt->execute([$donor_id, $phone]);
                        }
                    } elseif ($role === 'requester') {
                        $hospital_id = intval($_POST['hospital_id'] ?? 1);
                        $patient_name = $full_name;
                        $dob = $_POST['req_dob'] ?? '1990-01-01';
                        $gender = $_POST['req_gender'] ?? 'Male';
                        $blood_group_id = intval($_POST['req_blood_group_id'] ?? 1);

                        $r_stmt = $pdo->prepare("INSERT INTO recipients (user_id, hospital_id, patient_name, dob, gender, blood_group_id, contact_phone) 
                                                VALUES (?, ?, ?, ?, ?, ?, ?)");
                        $r_stmt->execute([$user_id, $hospital_id, $patient_name, $dob, $gender, $blood_group_id, $phone]);
                    }

                    $pdo->commit();

                    // Log in immediately
                    $stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ? LIMIT 1");
                    $stmt->execute([$user_id]);
                    $user = $stmt->fetch();

                    $_SESSION['user_id'] = $user['user_id'];
                    $_SESSION['user'] = $user;

                    set_flash('success', 'Account registered successfully! Welcome to VitalRed.');

                    if ($role === 'donor') {
                        redirect('donor/index.php');
                    } else {
                        redirect('requester/index.php');
                    }

                } catch (Exception $e) {
                    $pdo->rollBack();
                    $error = 'Registration failed: ' . $e->getMessage();
                }
            }
        }
    }
}

$page_title = 'Register Account';
require_once __DIR__ . '/includes/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8 col-xl-7">
            <div class="vr-card p-4 p-md-5">
                <div class="text-center mb-4">
                    <span class="badge bg-danger-subtle text-danger px-3 py-1 mb-2">
                        <i class="fa-solid fa-heart-pulse me-1"></i> Join The Life-Saving Network
                    </span>
                    <h2 class="fw-bold">Create Your VitalRed Account</h2>
                    <p class="text-muted small">Register as a voluntary donor or authorized hospital requester</p>
                </div>

                <?php if ($error): ?>
                    <div class="alert alert-danger py-2 px-3 small d-flex align-items-center gap-2 mb-3">
                        <i class="fa-solid fa-circle-exclamation"></i>
                        <div><?= e($error) ?></div>
                    </div>
                <?php endif; ?>

                <form method="POST" action="<?= BASE_URL ?>register.php">
                    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">

                    <!-- Role Selector -->
                    <div class="mb-4">
                        <label class="form-label fw-bold small text-uppercase text-muted">Select Account Type</label>
                        <select class="form-select form-select-lg border-2" id="reg_role" name="role" required>
                            <option value="donor" selected>Voluntary Blood Donor (Donate Blood, Track Health &amp; Certificates)</option>
                            <option value="requester">Hospital Requester / Patient Representative (Request Blood Units)</option>
                        </select>
                    </div>

                    <!-- Common Basic Details -->
                    <h5 class="fw-bold border-bottom pb-2 mb-3 text-dark fs-6">Personal &amp; Contact Credentials</h5>
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Full Name *</label>
                            <input type="text" class="form-control" name="full_name" placeholder="e.g., Rohit Malhotra" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Email Address *</label>
                            <input type="email" class="form-control" name="email" placeholder="e.g., rohit@example.com" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Phone Number *</label>
                            <input type="tel" class="form-control" name="phone" placeholder="+91 98765 43210" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-semibold">Password *</label>
                            <input type="password" class="form-control" name="password" placeholder="Min 6 chars" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-semibold">Confirm Password *</label>
                            <input type="password" class="form-control" name="confirm_password" placeholder="Confirm password" required>
                        </div>
                    </div>

                    <!-- Donor Specific Fields -->
                    <div id="donor_specific_fields">
                        <h5 class="fw-bold border-bottom pb-2 mb-3 text-dark fs-6 mt-4">Donor Clinical &amp; Address Details</h5>
                        <div class="row g-3 mb-3">
                            <div class="col-md-4">
                                <label class="form-label small fw-semibold">Blood Group *</label>
                                <select class="form-select" name="blood_group_id">
                                    <?php foreach ($blood_groups as $bg): ?>
                                        <option value="<?= $bg['blood_group_id'] ?>"><?= e($bg['group_name']) ?> (<?= e($bg['rh_factor']) ?>)</option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-semibold">Date of Birth *</label>
                                <input type="date" class="form-control" name="dob" value="2000-01-01" max="<?= date('Y-m-d', strtotime('-18 years')) ?>">
                                <div class="text-muted" style="font-size:11px;">Must be at least 18 years old</div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-semibold">Gender *</label>
                                <select class="form-select" name="gender">
                                    <option value="Male">Male</option>
                                    <option value="Female">Female</option>
                                    <option value="Other">Other</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold">Street Address</label>
                                <input type="text" class="form-control" name="street" placeholder="Flat / House / Street">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small fw-semibold">City</label>
                                <input type="text" class="form-control" name="city" value="New Delhi">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small fw-semibold">Pincode</label>
                                <input type="text" class="form-control" name="pincode" placeholder="110001">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold">Emergency Contact Number</label>
                                <input type="tel" class="form-control" name="emergency_contact" placeholder="+91 98111 00000">
                            </div>
                        </div>
                    </div>

                    <!-- Hospital / Requester Specific Fields -->
                    <div id="hospital_specific_fields" style="display:none;">
                        <h5 class="fw-bold border-bottom pb-2 mb-3 text-dark fs-6 mt-4">Patient &amp; Hospital Affiliation</h5>
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold">Admitted / Partner Hospital *</label>
                                <select class="form-select" name="hospital_id">
                                    <?php foreach ($hospitals as $h): ?>
                                        <option value="<?= $h['hospital_id'] ?>"><?= e($h['name']) ?> (<?= e($h['city']) ?>)</option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small fw-semibold">Patient Blood Group *</label>
                                <select class="form-select" name="req_blood_group_id">
                                    <?php foreach ($blood_groups as $bg): ?>
                                        <option value="<?= $bg['blood_group_id'] ?>"><?= e($bg['group_name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small fw-semibold">Patient Gender</label>
                                <select class="form-select" name="req_gender">
                                    <option value="Male">Male</option>
                                    <option value="Female">Female</option>
                                    <option value="Other">Other</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="d-grid mt-4">
                        <button type="submit" class="btn btn-vitalred py-2 fs-6">
                            <i class="fa-solid fa-user-check me-2"></i> Complete Registration &amp; Enter Portal
                        </button>
                    </div>
                </form>

                <div class="text-center mt-4">
                    <span class="text-muted small">Already have an account?</span>
                    <a href="<?= BASE_URL ?>login.php" class="text-danger fw-bold small text-decoration-none ms-1">Sign In here</a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
