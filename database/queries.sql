-- ============================================================================
-- VitalRed - Blood Bank Management System
-- Course Deliverable: 5+ Meaningful Complex SQL Queries
-- Demonstrating Multi-Table JOINs, GROUP BY, HAVING, and Aggregate Functions
-- ============================================================================

USE vitalred_db;

-- ----------------------------------------------------------------------------
-- QUERY 1: Group-Wise Stock Monitoring & Critical Shortage Alert
-- Purpose: Aggregates currently available, non-expired blood units for each blood
-- group, compares against its clinical threshold, and flags shortages.
-- Concepts: LEFT OUTER JOIN, COUNT with CASE/Conditional Aggregation, GROUP BY,
-- and CASE Expression.
-- ----------------------------------------------------------------------------
SELECT 
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
ORDER BY available_units ASC, bg.critical_threshold DESC;

-- ----------------------------------------------------------------------------
-- QUERY 2: Donor Frequency, Lifetime Contribution & Next Eligibility Audit
-- Purpose: Analyzes repeat donor participation, total units donated, average
-- hemoglobin health indicator, days elapsed since last donation, and computes
-- eligibility for their next donation (mandatory 90-day cooldown period).
-- Concepts: INNER JOIN across 3 tables (donors, blood_groups, donations),
-- Aggregate functions (COUNT, SUM, AVG, MAX), DATEDIFF, CASE.
-- ----------------------------------------------------------------------------
SELECT 
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
ORDER BY total_donations_count DESC, most_recent_donation DESC;

-- ----------------------------------------------------------------------------
-- QUERY 3: Hospital Blood Request Fulfillment & Urgency Performance
-- Purpose: Evaluates demand metrics per partner hospital, calculating total units
-- requested, breakdown across urgency tiers, and fulfillment percentage.
-- Concepts: INNER JOIN (hospitals, blood_requests), Conditional Aggregation,
-- ROUND, arithmetic expression, GROUP BY, ORDER BY.
-- ----------------------------------------------------------------------------
SELECT 
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
ORDER BY emergency_requests DESC, total_units_requested DESC;

-- ----------------------------------------------------------------------------
-- QUERY 4: Inventory Expiration Risk & Shelf-Life Categorization
-- Purpose: Audits blood units currently in inventory, calculates remaining shelf life,
-- and classifies units into risk tiers (Expired, High Risk <7 days, Medium Risk <14 days, Fresh).
-- Concepts: Multi-table JOIN (blood_units, blood_groups, donations, donors),
-- Date arithmetic functions (DATEDIFF, CURDATE), CASE classification, ORDER BY.
-- ----------------------------------------------------------------------------
SELECT 
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
ORDER BY bu.expiry_date ASC;

-- ----------------------------------------------------------------------------
-- QUERY 5: Staff Workload & Verification Turnaround Audit
-- Purpose: Evaluates staff operational activity in reviewing requests and
-- supervising blood donations, calculating total actions and average approval time.
-- Concepts: LEFT JOIN with users (role='admin'), Subquery/Aggregation,
-- TIMESTAMPDIFF, AVG, GROUP BY.
-- ----------------------------------------------------------------------------
SELECT 
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
ORDER BY requests_adjudicated DESC, donations_supervised DESC;

-- ----------------------------------------------------------------------------
-- QUERY 6 (Bonus): Blood ABO/Rh Compatibility Match Matrix
-- Purpose: Given a recipient's blood group, aggregates compatible units currently
-- in stock available for immediate life-saving transfusion.
-- ----------------------------------------------------------------------------
SELECT 
    recipient_bg.group_name AS recipient_blood_group,
    donor_bg.group_name AS compatible_donor_group,
    COUNT(bu.unit_id) AS available_compatible_units
FROM blood_groups recipient_bg
JOIN blood_groups donor_bg ON (
    -- O- is universal red cell donor
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
ORDER BY recipient_bg.group_name, donor_bg.group_name;
