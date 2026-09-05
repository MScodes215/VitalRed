<?php
/**
 * VitalRed - Administrative Reports & Stock Analytics
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth_helper.php';
require_auth(['admin']);

// 1. Stock Status Aggregate Query
$stock_report = $pdo->query("SELECT 
    bg.blood_group_id,
    bg.group_name AS blood_group,
    bg.rh_factor,
    bg.critical_threshold,
    COUNT(CASE WHEN bu.status = 'available' AND bu.expiry_date >= CURDATE() THEN bu.unit_id END) AS available_units,
    COUNT(CASE WHEN bu.status = 'reserved' THEN bu.unit_id END) AS reserved_units,
    COUNT(CASE WHEN bu.status = 'issued' THEN bu.unit_id END) AS issued_units,
    COUNT(bu.unit_id) AS total_inventory_records,
    CASE 
        WHEN COUNT(CASE WHEN bu.status = 'available' AND bu.expiry_date >= CURDATE() THEN bu.unit_id END) = 0 THEN 'CRITICAL DEPLETED'
        WHEN COUNT(CASE WHEN bu.status = 'available' AND bu.expiry_date >= CURDATE() THEN bu.unit_id END) < bg.critical_threshold THEN 'LOW STOCK ALERT'
        ELSE 'SUFFICIENT'
    END AS alert_status
FROM blood_groups bg
LEFT JOIN blood_units bu ON bg.blood_group_id = bu.blood_group_id
GROUP BY bg.blood_group_id, bg.group_name, bg.rh_factor, bg.critical_threshold
ORDER BY available_units ASC")->fetchAll();

// 2. Hospital Demand & Fulfillment Aggregate Query
$hospital_report = $pdo->query("SELECT 
    h.hospital_id,
    h.name AS hospital_name,
    h.city,
    COUNT(br.request_id) AS total_requests_filed,
    SUM(br.units_needed) AS total_units_requested,
    SUM(CASE WHEN br.urgency = 'Emergency' THEN 1 ELSE 0 END) AS emergency_requests,
    SUM(CASE WHEN br.urgency = 'Urgent' THEN 1 ELSE 0 END) AS urgent_requests,
    SUM(CASE WHEN br.status = 'issued' THEN br.units_needed ELSE 0 END) AS units_fulfilled,
    ROUND(
        (SUM(CASE WHEN br.status = 'issued' THEN 1 ELSE 0 END) * 100.0) / NULLIF(COUNT(br.request_id), 0),
        1
    ) AS fulfillment_rate_percent
FROM hospitals h
JOIN blood_requests br ON h.hospital_id = br.hospital_id
GROUP BY h.hospital_id, h.name, h.city
ORDER BY total_requests_filed DESC")->fetchAll();

// 3. Expiration Risk Aggregate Query
$expiring_report = $pdo->query("SELECT 
    bu.unit_id,
    bu.unit_barcode,
    bg.group_name AS blood_group,
    bu.storage_rack,
    bu.collection_date,
    bu.expiry_date,
    DATEDIFF(bu.expiry_date, CURDATE()) AS days_remaining,
    bu.status,
    CONCAT(d.first_name, ' ', d.last_name) AS source_donor,
    CASE 
        WHEN bu.expiry_date < CURDATE() THEN 'EXPIRED - DISCARD'
        WHEN DATEDIFF(bu.expiry_date, CURDATE()) <= 7 THEN 'HIGH RISK (< 7 Days)'
        WHEN DATEDIFF(bu.expiry_date, CURDATE()) <= 14 THEN 'MEDIUM RISK (< 14 Days)'
        ELSE 'SAFE (> 14 Days)'
    END AS expiration_risk_tier
FROM blood_units bu
JOIN blood_groups bg ON bu.blood_group_id = bg.blood_group_id
JOIN donations dn ON bu.donation_id = dn.donation_id
JOIN donors d ON dn.donor_id = d.donor_id
WHERE bu.status IN ('available', 'reserved')
ORDER BY bu.expiry_date ASC LIMIT 10")->fetchAll();

$page_title = 'Executive Summary Reports';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container py-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
        <div>
            <h2 class="fw-bold mb-1"><i class="fa-solid fa-file-invoice text-danger me-2"></i>Executive Summary Reports</h2>
            <p class="text-muted small mb-0">Consolidated real-time inventory performance, safety thresholds, and hospital fulfillment.</p>
        </div>
        <div class="d-flex gap-2">
            <button class="btn btn-outline-secondary" onclick="exportTableToCSV('stockSummaryTable', 'vitalred_stock_summary.csv')">
                <i class="fa-solid fa-file-csv me-1"></i> Export CSV
            </button>
            <button class="btn btn-vitalred" onclick="window.print()">
                <i class="fa-solid fa-print me-1"></i> Print / PDF Report
            </button>
        </div>
    </div>

    <!-- Report Section 1: Group-Wise Stock & Deficit Breakdown -->
    <div class="vr-card p-4 mb-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h5 class="fw-bold mb-0 text-danger"><i class="fa-solid fa-boxes-stacked me-2"></i>Section 1: Blood Inventory &amp; Critical Threshold Analysis</h5>
                <small class="text-muted">Real-time status of available units compared against mandatory buffer levels.</small>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-bordered table-hover align-middle mb-0" id="stockSummaryTable">
                <thead class="table-light">
                    <tr>
                        <th>Blood Group</th>
                        <th>Rh Factor</th>
                        <th>Available Safe Units</th>
                        <th>Reserved Units</th>
                        <th>Total Issued</th>
                        <th>Total Registered</th>
                        <th>Safety Threshold</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                        $sum_avail = 0; $sum_res = 0; $sum_issued = 0; $sum_tot = 0;
                        foreach ($stock_report as $sr): 
                            $sum_avail += $sr['available_units'];
                            $sum_res += $sr['reserved_units'];
                            $sum_issued += $sr['issued_units'];
                            $sum_tot += $sr['total_inventory_records'];
                            $alert_badge = $sr['alert_status'] === 'CRITICAL DEPLETED' ? 'bg-danger text-white' : ($sr['alert_status'] === 'LOW STOCK ALERT' ? 'bg-warning text-dark' : 'bg-success text-white');
                    ?>
                        <tr>
                            <td class="fw-bold"><?= get_blood_badge($sr['blood_group']) ?></td>
                            <td><?= e($sr['rh_factor']) ?></td>
                            <td class="fw-bold fs-6 <?= $sr['available_units'] < $sr['critical_threshold'] ? 'text-danger' : 'text-success' ?>">
                                <?= e($sr['available_units']) ?>
                            </td>
                            <td><?= e($sr['reserved_units']) ?></td>
                            <td><?= e($sr['issued_units']) ?></td>
                            <td class="fw-semibold"><?= e($sr['total_inventory_records']) ?></td>
                            <td class="text-muted font-monospace"><?= e($sr['critical_threshold']) ?></td>
                            <td>
                                <span class="badge <?= $alert_badge ?> px-2 py-1">
                                    <?= e($sr['alert_status']) ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <tr class="table-secondary fw-bold">
                        <td colspan="2">System Grand Total</td>
                        <td class="text-danger fs-6"><?= $sum_avail ?> Units</td>
                        <td><?= $sum_res ?> Units</td>
                        <td><?= $sum_issued ?> Units</td>
                        <td><?= $sum_tot ?> Units</td>
                        <td colspan="2">—</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Report Section 2: Hospital Demand & Fulfillment Performance -->
    <div class="vr-card p-4 mb-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h5 class="fw-bold mb-0 text-primary"><i class="fa-solid fa-hospital-user me-2"></i>Section 2: Partner Hospital Requisition Fulfillment</h5>
                <small class="text-muted">Turnaround speed and fulfillment rates for emergency and scheduled demands.</small>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-bordered table-hover align-middle mb-0" id="hospitalReportTable">
                <thead class="table-light">
                    <tr>
                        <th>Hospital Name</th>
                        <th>City</th>
                        <th>Total Requests</th>
                        <th>Units Demanded</th>
                        <th>Emergency Tiers</th>
                        <th>Urgent Tiers</th>
                        <th>Units Fulfilled</th>
                        <th>Fulfillment Rate</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($hospital_report as $hr): ?>
                        <tr>
                            <td class="fw-bold"><?= e($hr['hospital_name']) ?></td>
                            <td><?= e($hr['city']) ?></td>
                            <td class="text-center fw-bold"><?= e($hr['total_requests_filed']) ?></td>
                            <td class="text-center"><?= e($hr['total_units_requested']) ?></td>
                            <td class="text-center text-danger fw-bold"><?= e($hr['emergency_requests']) ?></td>
                            <td class="text-center text-warning fw-bold"><?= e($hr['urgent_requests']) ?></td>
                            <td class="text-center text-success fw-bold"><?= e($hr['units_fulfilled']) ?></td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <div class="progress flex-grow-1" style="height: 6px;">
                                        <div class="progress-bar bg-success" style="width: <?= e($hr['fulfillment_rate_percent']) ?>%"></div>
                                    </div>
                                    <span class="small fw-bold"><?= e($hr['fulfillment_rate_percent']) ?>%</span>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Report Section 3: Expiration Risk Audit -->
    <div class="vr-card p-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h5 class="fw-bold mb-0 text-warning text-dark"><i class="fa-solid fa-clock-rotate-left me-2 text-warning"></i>Section 3: Shelf-Life Surveillance &amp; Expiry Risk</h5>
                <small class="text-muted">Automated shelf-life monitoring for proactive stock rotation.</small>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-sm table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Barcode</th>
                        <th>Blood Group</th>
                        <th>Storage Rack</th>
                        <th>Collection Date</th>
                        <th>Expiry Date</th>
                        <th>Days Remaining</th>
                        <th>Source Donor</th>
                        <th>Risk Tier</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($expiring_report as $er): ?>
                        <?php 
                            $risk_badge = $er['expiration_risk_tier'] === 'HIGH RISK (< 7 Days)' ? 'bg-danger text-white' : ($er['expiration_risk_tier'] === 'MEDIUM RISK (< 14 Days)' ? 'bg-warning text-dark' : 'bg-success text-white');
                        ?>
                        <tr>
                            <td class="font-monospace fw-bold text-danger"><?= e($er['unit_barcode']) ?></td>
                            <td><?= get_blood_badge($er['blood_group']) ?></td>
                            <td><span class="badge bg-light text-dark border"><?= e($er['storage_rack']) ?></span></td>
                            <td class="small"><?= format_date($er['collection_date']) ?></td>
                            <td class="small fw-semibold"><?= format_date($er['expiry_date']) ?></td>
                            <td class="fw-bold"><?= e($er['days_remaining']) ?> days</td>
                            <td class="small text-muted"><?= e($er['source_donor']) ?></td>
                            <td><span class="badge <?= $risk_badge ?> px-2 py-1"><?= e($er['expiration_risk_tier']) ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
