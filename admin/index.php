<?php
/**
 * VitalRed - Admin Dashboard
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth_helper.php';
require_auth(['admin']);

// Fetch Summary KPIs
$total_units = $pdo->query("SELECT COUNT(*) FROM blood_units WHERE status = 'available' AND expiry_date >= CURDATE()")->fetchColumn();
$pending_reqs = $pdo->query("SELECT COUNT(*) FROM blood_requests WHERE status = 'pending'")->fetchColumn();
$total_donors = $pdo->query("SELECT COUNT(*) FROM donors")->fetchColumn();
$total_hospitals = $pdo->query("SELECT COUNT(*) FROM hospitals WHERE is_active = 1")->fetchColumn();

// Fetch stock status
$stock_data = $pdo->query("SELECT * FROM vw_group_wise_stock ORDER BY blood_group_id ASC")->fetchAll();
$critical_groups = array_filter($stock_data, fn($s) => $s['stock_status'] !== 'OPTIMAL');

// Fetch Urgent Pending Requests
$pending_list = $pdo->query("SELECT * FROM vw_pending_requests LIMIT 5")->fetchAll();

// Fetch Request distribution for chart
$req_stats = $pdo->query("SELECT status, COUNT(*) as count FROM blood_requests GROUP BY status")->fetchAll();
$req_distribution = ['pending' => 0, 'approved' => 0, 'issued' => 0, 'rejected' => 0];
foreach ($req_stats as $rs) {
    $req_distribution[$rs['status']] = intval($rs['count']);
}

$page_title = 'Admin Management Dashboard';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container py-4">
    <!-- Top Header -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
        <div>
            <h2 class="fw-bold mb-1"><i class="fa-solid fa-chart-pie text-danger me-2"></i>Transfusion Operations Hub</h2>
            <p class="text-muted small mb-0">Live central repository tracking blood stock, pending requisitions, and donor activity.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="<?= BASE_URL ?>admin/requests.php" class="btn btn-vitalred">
                <i class="fa-solid fa-clipboard-check me-1"></i> Review Requests (<?= $pending_reqs ?>)
            </a>
            <a href="<?= BASE_URL ?>admin/stock.php" class="btn btn-outline-secondary">
                <i class="fa-solid fa-boxes-stacked me-1"></i> Manage Stock
            </a>
        </div>
    </div>

    <!-- Critical Alerts Notification Banner -->
    <?php if (!empty($critical_groups)): ?>
        <div class="alert alert-danger shadow-sm border-2 border-danger mb-4 p-3">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                <div class="d-flex align-items-center gap-3">
                    <div class="fs-2 text-danger"><i class="fa-solid fa-triangle-exclamation"></i></div>
                    <div>
                        <h5 class="fw-bold text-danger mb-1">Critical Blood Shortage Alert!</h5>
                        <p class="mb-0 small text-dark">
                            The following blood groups have fallen below minimum clinical safety thresholds:
                            <?php foreach ($critical_groups as $cg): ?>
                                <span class="badge bg-danger text-white me-1"><?= e($cg['group_name']) ?> (<?= e($cg['available_units']) ?> left / Min <?= e($cg['critical_threshold']) ?>)</span>
                            <?php endforeach; ?>
                        </p>
                    </div>
                </div>
                <a href="<?= BASE_URL ?>admin/donors.php" class="btn btn-danger btn-sm px-3">
                    <i class="fa-solid fa-bullhorn me-1"></i> Alert Donors
                </a>
            </div>
        </div>
    <?php endif; ?>

    <!-- 4 Key Stat Metric Cards -->
    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-xl-3">
            <div class="vr-card stat-widget">
                <div>
                    <div class="stat-label">Available Blood Units</div>
                    <div class="stat-number text-danger"><?= $total_units ?></div>
                    <small class="text-muted"><i class="fa-solid fa-droplet text-danger me-1"></i>Tested &amp; safe units</small>
                </div>
                <div class="stat-icon-wrapper bg-danger-subtle text-danger">
                    <i class="fa-solid fa-cubes-stacked"></i>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-3">
            <div class="vr-card stat-widget">
                <div>
                    <div class="stat-label">Pending Requisitions</div>
                    <div class="stat-number text-warning"><?= $pending_reqs ?></div>
                    <small class="text-muted"><i class="fa-solid fa-clock text-warning me-1"></i>Awaiting review</small>
                </div>
                <div class="stat-icon-wrapper bg-warning-subtle text-warning">
                    <i class="fa-solid fa-hospital-user"></i>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-3">
            <div class="vr-card stat-widget">
                <div>
                    <div class="stat-label">Registered Donors</div>
                    <div class="stat-number text-primary"><?= $total_donors ?></div>
                    <small class="text-muted"><i class="fa-solid fa-user-check text-primary me-1"></i>Verified active</small>
                </div>
                <div class="stat-icon-wrapper bg-primary-subtle text-primary">
                    <i class="fa-solid fa-users"></i>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-3">
            <div class="vr-card stat-widget">
                <div>
                    <div class="stat-label">Partner Hospitals</div>
                    <div class="stat-number text-success"><?= $total_hospitals ?></div>
                    <small class="text-muted"><i class="fa-solid fa-circle-check text-success me-1"></i>Authorized trauma centers</small>
                </div>
                <div class="stat-icon-wrapper bg-success-subtle text-success">
                    <i class="fa-solid fa-hospital"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Row -->
    <div class="row g-4 mb-4">
        <!-- Chart 1: Blood Units by Group -->
        <div class="col-lg-8">
            <div class="vr-card p-4 h-100">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="fw-bold mb-0">Group-Wise Available Stock vs. Safety Threshold</h5>
                    <span class="badge bg-light text-muted border">Live Chart.js</span>
                </div>
                <div style="height: 280px;">
                    <canvas id="stockBarChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Chart 2: Request Distribution -->
        <div class="col-lg-4">
            <div class="vr-card p-4 h-100">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="fw-bold mb-0">Request Workflow Status</h5>
                    <span class="badge bg-light text-muted border">Total Requests</span>
                </div>
                <div style="height: 240px;" class="d-flex align-items-center justify-content-center">
                    <canvas id="requestStatusChart"></canvas>
                </div>
                <div class="text-center mt-2">
                    <small class="text-muted">Workflow: Pending &rarr; Approved &rarr; Issued (or Rejected)</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Urgent Pending Requests Queue -->
    <div class="vr-card p-4 mb-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h5 class="fw-bold mb-0">Priority Requisition Queue (vw_pending_requests)</h5>
                <p class="text-muted small mb-0">Immediate hospital requests requiring staff medical cross-check and unit allocation.</p>
            </div>
            <a href="<?= BASE_URL ?>admin/requests.php" class="btn btn-outline-danger btn-sm">
                View All Requisitions &rarr;
            </a>
        </div>

        <?php if (empty($pending_list)): ?>
            <div class="alert alert-success py-3 text-center mb-0">
                <i class="fa-solid fa-check-circle me-1"></i> Great! No pending blood requests waiting for approval.
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Req ID</th>
                            <th>Patient / Recipient</th>
                            <th>Hospital</th>
                            <th>Blood Group</th>
                            <th>Units</th>
                            <th>Urgency</th>
                            <th>Attending Doctor</th>
                            <th>Wait Time</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pending_list as $pr): ?>
                            <tr>
                                <td class="font-monospace fw-bold">#REQ-<?= $pr['request_id'] ?></td>
                                <td class="fw-semibold"><?= e($pr['patient_name']) ?></td>
                                <td><?= e($pr['hospital_name']) ?></td>
                                <td><?= get_blood_badge($pr['group_name']) ?></td>
                                <td><span class="badge bg-dark"><?= e($pr['units_needed']) ?> Unit(s)</span></td>
                                <td><?= get_urgency_badge($pr['urgency']) ?></td>
                                <td class="small text-muted"><?= e($pr['doctor_name']) ?></td>
                                <td class="small text-danger fw-semibold">
                                    <i class="fa-regular fa-clock me-1"></i><?= e($pr['hours_pending']) ?>h ago
                                </td>
                                <td class="text-end">
                                    <a href="<?= BASE_URL ?>admin/requests.php?focus=<?= $pr['request_id'] ?>" class="btn btn-vitalred btn-sm">
                                        Review &amp; Approve
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

<script>
document.addEventListener('DOMContentLoaded', function () {
    // Stock Chart Data
    const stockLabels = <?= json_encode(array_column($stock_data, 'group_name')) ?>;
    const availableData = <?= json_encode(array_map('intval', array_column($stock_data, 'available_units'))) ?>;
    const thresholdData = <?= json_encode(array_map('intval', array_column($stock_data, 'critical_threshold'))) ?>;

    const ctxStock = document.getElementById('stockBarChart').getContext('2d');
    new Chart(ctxStock, {
        type: 'bar',
        data: {
            labels: stockLabels,
            datasets: [
                {
                    label: 'Available Units (Non-Expired)',
                    data: availableData,
                    backgroundColor: '#dc2626',
                    borderRadius: 6,
                },
                {
                    label: 'Minimum Safety Threshold',
                    data: thresholdData,
                    backgroundColor: '#cbd5e1',
                    borderRadius: 6,
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'top' }
            },
            scales: {
                y: { beginAtZero: true, ticks: { precision: 0 } }
            }
        }
    });

    // Request Donut Chart
    const ctxReq = document.getElementById('requestStatusChart').getContext('2d');
    new Chart(ctxReq, {
        type: 'doughnut',
        data: {
            labels: ['Pending', 'Approved', 'Issued / Fulfilled', 'Rejected'],
            datasets: [{
                data: [
                    <?= $req_distribution['pending'] ?>,
                    <?= $req_distribution['approved'] ?>,
                    <?= $req_distribution['issued'] ?>,
                    <?= $req_distribution['rejected'] ?>
                ],
                backgroundColor: ['#f59e0b', '#0ea5e9', '#10b981', '#ef4444']
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'bottom' }
            }
        }
    });
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
