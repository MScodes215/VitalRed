<?php
/**
 * VitalRed - Live Query Runner Endpoint for Academic Evaluation
 * Runs the pre-approved 5+ handbook queries against vitalred_db and returns JSON results.
 */

header('Content-Type: application/json');
require_once __DIR__ . '/config/db.php';

$query_id = intval($_GET['query_id'] ?? 1);

$queries = [
    1 => [
        'title' => 'Query 1: Group-Wise Stock Monitoring & Critical Shortage Alert',
        'sql' => "SELECT 
                    bg.blood_group_id,
                    bg.group_name AS blood_group,
                    bg.rh_factor,
                    bg.critical_threshold,
                    COUNT(CASE WHEN bu.status = 'available' AND bu.expiry_date >= CURDATE() THEN bu.unit_id END) AS available_units,
                    COUNT(CASE WHEN bu.status = 'reserved' THEN bu.unit_id END) AS reserved_units,
                    COUNT(bu.unit_id) AS total_inventory_records,
                    CASE 
                        WHEN COUNT(CASE WHEN bu.status = 'available' AND bu.expiry_date >= CURDATE() THEN bu.unit_id END) = 0 THEN 'CRITICAL DEPLETED'
                        WHEN COUNT(CASE WHEN bu.status = 'available' AND bu.expiry_date >= CURDATE() THEN bu.unit_id END) < bg.critical_threshold THEN 'LOW STOCK ALERT'
                        ELSE 'SUFFICIENT'
                    END AS alert_status
                FROM blood_groups bg
                LEFT JOIN blood_units bu ON bg.blood_group_id = bu.blood_group_id
                GROUP BY bg.blood_group_id, bg.group_name, bg.rh_factor, bg.critical_threshold
                ORDER BY available_units ASC, bg.critical_threshold DESC"
    ],
    2 => [
        'title' => 'Query 2: Donor Frequency, Lifetime Contribution & 90-Day Eligibility Audit',
        'sql' => "SELECT 
                    d.donor_id,
                    CONCAT(d.first_name, ' ', d.last_name) AS donor_full_name,
                    bg.group_name AS blood_group,
                    d.city,
                    COUNT(dn.donation_id) AS total_donations_count,
                    SUM(dn.units_collected) AS total_units_donated,
                    ROUND(AVG(dn.hemoglobin_g_dl), 2) AS avg_hemoglobin_g_dl,
                    MAX(dn.donation_date) AS most_recent_donation,
                    DATEDIFF(CURDATE(), MAX(dn.donation_date)) AS days_since_last_donation,
                    CASE 
                        WHEN MAX(dn.donation_date) IS NULL THEN 'Eligible (Never Donated)'
                        WHEN DATEDIFF(CURDATE(), MAX(dn.donation_date)) >= 90 THEN 'Eligible to Donate'
                        ELSE CONCAT('Cooldown: ', (90 - DATEDIFF(CURDATE(), MAX(dn.donation_date))), ' days left')
                    END AS eligibility_status
                FROM donors d
                JOIN blood_groups bg ON d.blood_group_id = bg.blood_group_id
                LEFT JOIN donations dn ON d.donor_id = dn.donor_id
                GROUP BY d.donor_id, d.first_name, d.last_name, bg.group_name, d.city
                HAVING total_donations_count > 0
                ORDER BY total_donations_count DESC, most_recent_donation DESC"
    ],
    3 => [
        'title' => 'Query 3: Hospital Blood Request Fulfillment & Urgency Performance',
        'sql' => "SELECT 
                    h.hospital_id,
                    h.name AS hospital_name,
                    h.city,
                    COUNT(br.request_id) AS total_requests_filed,
                    SUM(br.units_needed) AS total_units_requested,
                    SUM(CASE WHEN br.urgency = 'Emergency' THEN 1 ELSE 0 END) AS emergency_requests,
                    SUM(CASE WHEN br.urgency = 'Urgent' THEN 1 ELSE 0 END) AS urgent_requests,
                    SUM(CASE WHEN br.status = 'issued' THEN br.units_needed ELSE 0 END) AS units_fulfilled,
                    SUM(CASE WHEN br.status = 'pending' THEN br.units_needed ELSE 0 END) AS units_pending,
                    ROUND(
                        (SUM(CASE WHEN br.status = 'issued' THEN 1 ELSE 0 END) * 100.0) / NULLIF(COUNT(br.request_id), 0),
                        1
                    ) AS fulfillment_rate_percent
                FROM hospitals h
                JOIN blood_requests br ON h.hospital_id = br.hospital_id
                GROUP BY h.hospital_id, h.name, h.city
                ORDER BY emergency_requests DESC, total_units_requested DESC"
    ],
    4 => [
        'title' => 'Query 4: Inventory Expiration Risk & Shelf-Life Categorization',
        'sql' => "SELECT 
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
                ORDER BY bu.expiry_date ASC"
    ],
    5 => [
        'title' => 'Query 5: Staff Workload & Verification Turnaround Audit',
        'sql' => "SELECT 
                    u.user_id AS staff_id,
                    u.full_name AS staff_officer_name,
                    u.email,
                    COUNT(DISTINCT dn.donation_id) AS donations_supervised,
                    COUNT(DISTINCT br.request_id) AS requests_adjudicated,
                    COUNT(DISTINCT CASE WHEN br.status = 'approved' OR br.status = 'issued' THEN br.request_id END) AS requests_approved,
                    COUNT(DISTINCT CASE WHEN br.status = 'rejected' THEN br.request_id END) AS requests_rejected,
                    ROUND(
                        AVG(CASE WHEN br.approved_at IS NOT NULL THEN TIMESTAMPDIFF(MINUTE, br.created_at, br.approved_at) END),
                        1
                    ) AS avg_turnaround_minutes
                FROM users u
                LEFT JOIN donations dn ON u.user_id = dn.staff_id
                LEFT JOIN blood_requests br ON u.user_id = br.approved_by
                WHERE u.role = 'admin'
                GROUP BY u.user_id, u.full_name, u.email
                ORDER BY requests_adjudicated DESC, donations_supervised DESC"
    ],
    6 => [
        'title' => 'Query 6: Blood Compatibility Transfusion Match Matrix',
        'sql' => "SELECT 
                    recipient_bg.group_name AS recipient_blood_group,
                    donor_bg.group_name AS compatible_donor_group,
                    COUNT(bu.unit_id) AS available_compatible_units
                FROM blood_groups recipient_bg
                JOIN blood_groups donor_bg ON (
                    donor_bg.group_name = 'O-'
                    OR donor_bg.group_name = recipient_bg.group_name
                    OR (recipient_bg.group_name = 'AB+' AND donor_bg.group_name IN ('A+', 'A-', 'B+', 'B-', 'AB-', 'O+'))
                    OR (recipient_bg.group_name = 'A+' AND donor_bg.group_name IN ('A-', 'O+'))
                    OR (recipient_bg.group_name = 'B+' AND donor_bg.group_name IN ('B-', 'O+'))
                    OR (recipient_bg.group_name = 'AB-' AND donor_bg.group_name IN ('A-', 'B-'))
                )
                LEFT JOIN blood_units bu ON donor_bg.blood_group_id = bu.blood_group_id 
                    AND bu.status = 'available' 
                    AND bu.expiry_date >= CURDATE()
                GROUP BY recipient_bg.group_name, donor_bg.group_name
                ORDER BY recipient_bg.group_name, donor_bg.group_name"
    ]
];

if (!isset($queries[$query_id])) {
    echo json_encode(['success' => false, 'error' => 'Invalid Query ID requested.']);
    exit;
}

try {
    $start = microtime(true);
    $stmt = $pdo->query($queries[$query_id]['sql']);
    $rows = $stmt->fetchAll();
    $duration_ms = round((microtime(true) - $start) * 1000, 2);

    $columns = [];
    if (!empty($rows)) {
        $columns = array_keys($rows[0]);
    }

    echo json_encode([
        'success' => true,
        'title' => $queries[$query_id]['title'],
        'sql' => $queries[$query_id]['sql'],
        'columns' => $columns,
        'rows' => $rows,
        'count' => count($rows),
        'duration_ms' => $duration_ms
    ]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
