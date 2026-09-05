<?php
/**
 * VitalRed - Donor Donation History & Certificate Generator
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth_helper.php';
require_auth(['donor']);

$user_id = $_SESSION['user_id'];

// Fetch Donor & Donations
$stmt = $pdo->prepare("SELECT d.*, bg.group_name, bg.rh_factor FROM donors d 
                      JOIN blood_groups bg ON d.blood_group_id = bg.blood_group_id 
                      WHERE d.user_id = ? LIMIT 1");
$stmt->execute([$user_id]);
$donor = $stmt->fetch();

$donations = [];
if ($donor) {
    $d_stmt = $pdo->prepare("SELECT * FROM donations WHERE donor_id = ? ORDER BY donation_date DESC");
    $d_stmt->execute([$donor['donor_id']]);
    $donations = $d_stmt->fetchAll();
}

$page_title = 'Donation History & Certificates';
require_once __DIR__ . '/../includes/header.php';
?>

<style>
/* Print Optimization: Completely isolate certificate and hide all site UI */
@media print {
    body {
        background: #ffffff !important;
        margin: 0 !important;
        padding: 0 !important;
    }
    body > *:not(.modal) {
        display: none !important;
    }
    .navbar, footer, .modal-backdrop, .modal-footer, .modal-header, .btn, .table-responsive, h2, p, .vr-card {
        display: none !important;
    }
    .modal {
        position: static !important;
        display: block !important;
        overflow: visible !important;
        background: none !important;
        padding: 0 !important;
    }
    .modal-dialog {
        max-width: 100% !important;
        margin: 0 !important;
        padding: 0 !important;
    }
    .modal-content {
        border: none !important;
        box-shadow: none !important;
        background: none !important;
    }
    .modal-body {
        padding: 0 !important;
    }
    .certificate-clean-card {
        border: 5px double #dc3545 !important;
        padding: 30px !important;
        margin: 0 auto !important;
        box-shadow: none !important;
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
    }
}
</style>

<div class="container py-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
        <div>
            <h2 class="fw-bold mb-1"><i class="fa-solid fa-award text-danger me-2"></i>My Donation Records &amp; Certificates</h2>
            <p class="text-muted small mb-0">Transfusion logs with downloadable verified appreciation certificates.</p>
        </div>
        <a href="<?= BASE_URL ?>donor/schedule.php" class="btn btn-vitalred">
            <i class="fa-solid fa-calendar-plus me-1"></i> Book Next Donation
        </a>
    </div>

    <div class="vr-card p-4">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Record #</th>
                        <th>Donor Name</th>
                        <th>Blood Type</th>
                        <th>City</th>
                        <th>Donation Date</th>
                        <th>Type</th>
                        <th>Volume</th>
                        <th>Hemoglobin</th>
                        <th>Vitals</th>
                        <th class="text-end">Certificate</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($donations)): ?>
                        <tr><td colspan="10" class="text-center py-4 text-muted">No donation history recorded yet.</td></tr>
                    <?php else: ?>
                        <?php foreach ($donations as $dn): ?>
                            <tr>
                                <td class="font-monospace fw-bold">#DON-<?= $dn['donation_id'] ?></td>
                                <td class="fw-bold"><?= e(($donor['first_name'] ?? '') . ' ' . ($donor['last_name'] ?? '')) ?></td>
                                <td><?= get_blood_badge($donor['group_name'] ?? '') ?></td>
                                <td><span class="badge bg-light text-dark border"><i class="fa-solid fa-location-dot text-danger me-1"></i><?= e($donor['city'] ?? 'N/A') ?></span></td>
                                <td class="fw-bold"><?= format_date($dn['donation_date']) ?></td>
                                <td><span class="badge bg-light text-dark border"><?= e($dn['donation_type']) ?></span></td>
                                <td><span class="badge bg-dark"><?= e($dn['units_collected']) ?> Unit(s)</span></td>
                                <td><?= e($dn['hemoglobin_g_dl']) ?> g/dL</td>
                                <td class="small text-muted">BP: <?= e($dn['blood_pressure']) ?></td>
                                <td class="text-end">
                                    <button class="btn btn-outline-danger btn-sm" data-bs-toggle="modal" data-bs-target="#certModal<?= $dn['donation_id'] ?>">
                                        <i class="fa-solid fa-certificate me-1"></i> View Certificate
                                    </button>
                                </td>
                            </tr>

                            <!-- Certificate Modal -->
                            <div class="modal fade" id="certModal<?= $dn['donation_id'] ?>" tabindex="-1" aria-labelledby="certModalLabel<?= $dn['donation_id'] ?>" aria-hidden="true">
                                <div class="modal-dialog modal-lg modal-dialog-centered">
                                    <div class="modal-content border-0 shadow">
                                        <div class="modal-header border-0 pb-0">
                                            <h6 class="modal-title fw-bold text-danger" id="certModalLabel<?= $dn['donation_id'] ?>">
                                                <i class="fa-solid fa-file-invoice me-1"></i> Official Appreciation Certificate
                                            </h6>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body p-3 p-md-4">
                                            <div id="certificateArea<?= $dn['donation_id'] ?>" class="certificate-clean-card border border-4 border-danger p-4 p-md-5 rounded-4 position-relative bg-white text-center shadow-sm" style="border-style: double !important;">
                                                <div class="mb-3">
                                                    <span class="brand-icon-drop mb-2" style="width: 48px; height: 48px; display:inline-flex; align-items:center; justify-content:center; background:#dc3545; border-radius:50%;">
                                                        <i class="fa-solid fa-droplet text-white" style="font-size:1.4rem;"></i>
                                                    </span>
                                                    <h3 class="fw-bold text-danger text-uppercase tracking-wide mb-0">Certificate of Appreciation</h3>
                                                    <small class="text-muted text-uppercase fw-semibold">National Voluntary Blood Donation Council</small>
                                                </div>

                                                <p class="text-muted my-3 fs-6">This certificate of honor is proudly presented to</p>
                                                
                                                <h2 class="display-6 fw-bold text-dark border-bottom pb-2 d-inline-block px-4 mb-2 font-serif">
                                                    <?= e(($donor['first_name'] ?? '') . ' ' . ($donor['last_name'] ?? '')) ?>
                                                </h2>
                                                <div class="mb-3 text-muted small">
                                                    <span class="me-3"><i class="fa-solid fa-location-dot text-danger me-1"></i><strong>City:</strong> <?= e($donor['city'] ?? 'N/A') ?></span>
                                                    <span><i class="fa-solid fa-droplet text-danger me-1"></i><strong>Blood Type:</strong> <?= e($donor['group_name'] ?? '') ?><?= !empty($donor['rh_factor']) ? ' (' . e($donor['rh_factor']) . ')' : '' ?></span>
                                                </div>

                                                <p class="text-muted max-w-600 mx-auto mb-4" style="line-height: 1.6;">
                                                    In grateful recognition of your voluntary, selfless gift of <strong><?= e($dn['units_collected']) ?> unit</strong> of <strong><?= e($donor['group_name'] ?? '') ?> blood</strong> on <strong><?= format_date($dn['donation_date'], 'F d, Y') ?></strong> in <strong><?= e($donor['city'] ?? 'N/A') ?></strong>. Your noble donation helps sustain critical lives in emergency trauma surgeries and intensive care units.
                                                </p>

                                                <div class="row pt-4 border-top text-center align-items-center">
                                                    <div class="col-3">
                                                        <div class="font-monospace text-muted small">Certificate ID</div>
                                                        <div class="fw-bold font-monospace small text-truncate">VR-CERT-<?= $dn['donation_id'] ?>-<?= date('Y', strtotime($dn['donation_date'])) ?></div>
                                                    </div>
                                                    <div class="col-3">
                                                        <div class="font-monospace text-muted small">Blood Type</div>
                                                        <div class="fw-bold text-danger fs-5"><?= e($donor['group_name'] ?? '') ?></div>
                                                    </div>
                                                    <div class="col-3">
                                                        <div class="font-monospace text-muted small">City</div>
                                                        <div class="fw-bold text-dark text-truncate"><?= e($donor['city'] ?? 'N/A') ?></div>
                                                    </div>
                                                    <div class="col-3">
                                                        <div class="font-monospace text-muted small">Authorized Officer</div>
                                                        <div class="fw-bold text-dark small">Dr. Rajesh Verma</div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="modal-footer d-flex justify-content-between">
                                            <span class="text-muted small">
                                                <i class="fa-solid fa-circle-info text-primary me-1"></i>Tip: Choose "Save as PDF" to download the certificate
                                            </span>
                                            <div>
                                                <button type="button" class="btn btn-light me-2" data-bs-dismiss="modal">Close</button>
                                                <button type="button" class="btn btn-vitalred" onclick="printCleanCertificate('certificateArea<?= $dn['donation_id'] ?>')">
                                                    <i class="fa-solid fa-file-arrow-down me-1"></i> Download / Print Certificate
                                                </button>
                                            </div>
                                        </div>
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

<script>
/**
 * Isolates and prints ONLY the certificate without any website interface,
 * navbar, tables, or buttons.
 */
function printCleanCertificate(elementId) {
    const certElement = document.getElementById(elementId);
    if (!certElement) return;

    let iframe = document.getElementById('certPrintIframe');
    if (!iframe) {
        iframe = document.createElement('iframe');
        iframe.id = 'certPrintIframe';
        iframe.style.position = 'fixed';
        iframe.style.right = '0';
        iframe.style.bottom = '0';
        iframe.style.width = '0';
        iframe.style.height = '0';
        iframe.style.border = '0';
        document.body.appendChild(iframe);
    }

    const doc = iframe.contentWindow.document;
    doc.open();
    doc.write(`<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>VitalRed - Blood Donation Certificate</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <style>
        @page {
            size: A4 landscape;
            margin: 10mm;
        }
        * {
            box-sizing: border-box;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }
        body {
            margin: 0;
            padding: 20px;
            background: #ffffff !important;
            color: #1a1a1a;
            font-family: system-ui, -apple-system, 'Segoe UI', Roboto, sans-serif;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 94vh;
        }
        .cert-isolated-container {
            width: 100%;
            max-width: 900px;
            margin: auto;
            border: 6px double #dc3545 !important;
            border-radius: 16px;
            padding: 40px;
            background: #ffffff;
            text-align: center;
        }
        .brand-icon-drop {
            width: 48px;
            height: 48px;
            background: #dc3545;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }
    </style>
</head>
<body>
    <div class="cert-isolated-container">
        ${certElement.innerHTML}
    </div>
</body>
</html>`);
    doc.close();

    setTimeout(function() {
        iframe.contentWindow.focus();
        iframe.contentWindow.print();
    }, 400);
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
