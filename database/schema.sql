-- ============================================================================
-- VitalRed - Blood Bank Management System
-- Relational Database Schema (3NF Compliant)
-- Course: Database Management Systems (DBMS)
-- Target DBMS: MySQL 8.0+ / MariaDB 10.4+ (InnoDB Engine)
-- ============================================================================

DROP DATABASE IF EXISTS vitalred_db;
CREATE DATABASE vitalred_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE vitalred_db;

-- ----------------------------------------------------------------------------
-- Table 1: users (Authentication & Role-Based Access Control)
-- ----------------------------------------------------------------------------
CREATE TABLE users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(120) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    phone VARCHAR(20) NULL,
    role ENUM('admin', 'donor', 'requester') NOT NULL DEFAULT 'donor',
    google_id VARCHAR(100) NULL UNIQUE,
    avatar_url VARCHAR(255) NULL,
    status ENUM('active', 'inactive', 'suspended') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_user_role (role),
    INDEX idx_user_status (status)
) ENGINE=InnoDB;

-- ----------------------------------------------------------------------------
-- Table 2: blood_groups (Master Table for ABO & Rh System)
-- ----------------------------------------------------------------------------
CREATE TABLE blood_groups (
    blood_group_id INT AUTO_INCREMENT PRIMARY KEY,
    group_name VARCHAR(5) NOT NULL UNIQUE,      -- A+, A-, B+, B-, AB+, AB-, O+, O-
    rh_factor ENUM('Positive', 'Negative') NOT NULL,
    critical_threshold INT NOT NULL DEFAULT 5,  -- Minimum units required before alert
    description VARCHAR(255) NULL
) ENGINE=InnoDB;

-- ----------------------------------------------------------------------------
-- Table 3: hospitals (Registered Partner Hospitals & Medical Centers)
-- ----------------------------------------------------------------------------
CREATE TABLE hospitals (
    hospital_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(180) NOT NULL,
    license_no VARCHAR(60) NOT NULL UNIQUE,
    address VARCHAR(255) NOT NULL,
    city VARCHAR(80) NOT NULL,
    state VARCHAR(80) NOT NULL DEFAULT 'Delhi NCR',
    pincode VARCHAR(15) NOT NULL,
    phone VARCHAR(25) NOT NULL,
    email VARCHAR(150) NOT NULL,
    contact_person VARCHAR(120) NOT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_hospital_city (city)
) ENGINE=InnoDB;

-- ----------------------------------------------------------------------------
-- Table 4: donors (Donor Master - 3NF Decomposed)
-- Note: Address composite attributes decomposed into atomic columns.
-- Age is derived from DOB and NOT stored (computed dynamically).
-- ----------------------------------------------------------------------------
CREATE TABLE donors (
    donor_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL UNIQUE,                     -- 1:1 optional link to login user
    first_name VARCHAR(60) NOT NULL,
    last_name VARCHAR(60) NOT NULL,
    dob DATE NOT NULL,
    gender ENUM('Male', 'Female', 'Other') NOT NULL,
    blood_group_id INT NOT NULL,
    address_street VARCHAR(180) NOT NULL,
    city VARCHAR(80) NOT NULL,
    state VARCHAR(80) NOT NULL DEFAULT 'Delhi NCR',
    pincode VARCHAR(15) NOT NULL,
    emergency_contact VARCHAR(25) NOT NULL,
    last_donation_date DATE NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_donor_user FOREIGN KEY (user_id) 
        REFERENCES users (user_id) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_donor_blood_group FOREIGN KEY (blood_group_id) 
        REFERENCES blood_groups (blood_group_id) ON DELETE RESTRICT ON UPDATE CASCADE,
    INDEX idx_donor_blood_group (blood_group_id),
    INDEX idx_donor_city (city)
) ENGINE=InnoDB;

-- ----------------------------------------------------------------------------
-- Table 5: donor_phones (Multivalued Attribute Decomposed for 1NF / 3NF)
-- Represents multiple phone numbers per donor without multivalued repetition.
-- ----------------------------------------------------------------------------
CREATE TABLE donor_phones (
    donor_id INT NOT NULL,
    phone_number VARCHAR(20) NOT NULL,
    phone_type ENUM('Primary', 'Alternate', 'Work') DEFAULT 'Primary',
    PRIMARY KEY (donor_id, phone_number),
    CONSTRAINT fk_donor_phones FOREIGN KEY (donor_id) 
        REFERENCES donors (donor_id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

-- ----------------------------------------------------------------------------
-- Table 6: recipients (Patients or Requesters receiving blood)
-- ----------------------------------------------------------------------------
CREATE TABLE recipients (
    recipient_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL UNIQUE,                     -- Requester user account
    hospital_id INT NOT NULL,
    patient_name VARCHAR(120) NOT NULL,
    dob DATE NOT NULL,
    gender ENUM('Male', 'Female', 'Other') NOT NULL,
    blood_group_id INT NOT NULL,
    contact_phone VARCHAR(25) NOT NULL,
    medical_record_no VARCHAR(50) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_recipient_user FOREIGN KEY (user_id) 
        REFERENCES users (user_id) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_recipient_hospital FOREIGN KEY (hospital_id) 
        REFERENCES hospitals (hospital_id) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_recipient_blood_group FOREIGN KEY (blood_group_id) 
        REFERENCES blood_groups (blood_group_id) ON DELETE RESTRICT ON UPDATE CASCADE,
    INDEX idx_recipient_hospital (hospital_id)
) ENGINE=InnoDB;

-- ----------------------------------------------------------------------------
-- Table 7: donations (Donation Event Records)
-- ----------------------------------------------------------------------------
CREATE TABLE donations (
    donation_id INT AUTO_INCREMENT PRIMARY KEY,
    donor_id INT NOT NULL,
    blood_group_id INT NOT NULL,
    donation_date DATE NOT NULL,
    units_collected INT NOT NULL DEFAULT 1,
    hemoglobin_g_dl DECIMAL(4, 1) NOT NULL,      -- Clinical safety check: >= 12.5
    blood_pressure VARCHAR(15) NOT NULL,         -- e.g. 120/80
    donation_type ENUM('Whole Blood', 'Platelets', 'Plasma', 'Red Cells') DEFAULT 'Whole Blood',
    staff_id INT NULL,                           -- Staff member overseeing donation
    remarks VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_donation_donor FOREIGN KEY (donor_id) 
        REFERENCES donors (donor_id) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_donation_blood_group FOREIGN KEY (blood_group_id) 
        REFERENCES blood_groups (blood_group_id) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_donation_staff FOREIGN KEY (staff_id) 
        REFERENCES users (user_id) ON DELETE SET NULL ON UPDATE CASCADE,
    INDEX idx_donation_date (donation_date)
) ENGINE=InnoDB;

-- ----------------------------------------------------------------------------
-- Table 8: blood_requests (Blood Request & Approval Workflow)
-- ----------------------------------------------------------------------------
CREATE TABLE blood_requests (
    request_id INT AUTO_INCREMENT PRIMARY KEY,
    recipient_id INT NOT NULL,
    hospital_id INT NOT NULL,
    blood_group_id INT NOT NULL,
    units_needed INT NOT NULL DEFAULT 1,
    urgency ENUM('Normal', 'Urgent', 'Emergency') NOT NULL DEFAULT 'Normal',
    reason VARCHAR(500) NOT NULL,
    doctor_name VARCHAR(120) NOT NULL,
    status ENUM('pending', 'approved', 'rejected', 'issued') NOT NULL DEFAULT 'pending',
    approved_by INT NULL,                        -- Staff user who approved/rejected
    approval_notes VARCHAR(255) NULL,
    approved_at DATETIME NULL,
    issued_at DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_req_recipient FOREIGN KEY (recipient_id) 
        REFERENCES recipients (recipient_id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_req_hospital FOREIGN KEY (hospital_id) 
        REFERENCES hospitals (hospital_id) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_req_blood_group FOREIGN KEY (blood_group_id) 
        REFERENCES blood_groups (blood_group_id) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_req_approver FOREIGN KEY (approved_by) 
        REFERENCES users (user_id) ON DELETE SET NULL ON UPDATE CASCADE,
    INDEX idx_req_status (status),
    INDEX idx_req_urgency (urgency)
) ENGINE=InnoDB;

-- ----------------------------------------------------------------------------
-- Table 9: blood_units (Individual Blood Unit Inventory - Weak Entity of Donation)
-- ----------------------------------------------------------------------------
CREATE TABLE blood_units (
    unit_id INT AUTO_INCREMENT PRIMARY KEY,
    unit_barcode VARCHAR(40) NOT NULL UNIQUE,
    donation_id INT NOT NULL,
    blood_group_id INT NOT NULL,
    collection_date DATE NOT NULL,
    expiry_date DATE NOT NULL,
    storage_rack VARCHAR(20) NOT NULL DEFAULT 'RACK-A1',
    status ENUM('available', 'reserved', 'issued', 'expired', 'discarded') NOT NULL DEFAULT 'available',
    issued_request_id INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_unit_donation FOREIGN KEY (donation_id) 
        REFERENCES donations (donation_id) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_unit_blood_group FOREIGN KEY (blood_group_id) 
        REFERENCES blood_groups (blood_group_id) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_unit_request FOREIGN KEY (issued_request_id) 
        REFERENCES blood_requests (request_id) ON DELETE SET NULL ON UPDATE CASCADE,
    INDEX idx_unit_status (status),
    INDEX idx_unit_expiry (expiry_date)
) ENGINE=InnoDB;

-- ============================================================================
-- SQL VIEWS FOR AGGREGATES & REAL-TIME REPORTING
-- ============================================================================

CREATE OR REPLACE VIEW vw_group_wise_stock AS
SELECT 
    bg.blood_group_id,
    bg.group_name,
    bg.rh_factor,
    bg.critical_threshold,
    COUNT(CASE WHEN bu.status = 'available' AND bu.expiry_date >= CURDATE() THEN bu.unit_id END) AS available_units,
    COUNT(CASE WHEN bu.status = 'reserved' THEN bu.unit_id END) AS reserved_units,
    COUNT(CASE WHEN bu.status = 'issued' THEN bu.unit_id END) AS total_issued_units,
    COUNT(CASE WHEN bu.status = 'available' AND bu.expiry_date < CURDATE() THEN bu.unit_id END) AS expired_in_stock,
    CASE 
        WHEN COUNT(CASE WHEN bu.status = 'available' AND bu.expiry_date >= CURDATE() THEN bu.unit_id END) = 0 THEN 'CRITICAL DEPLETED'
        WHEN COUNT(CASE WHEN bu.status = 'available' AND bu.expiry_date >= CURDATE() THEN bu.unit_id END) < bg.critical_threshold THEN 'LOW STOCK ALERT'
        ELSE 'OPTIMAL'
    END AS stock_status
FROM blood_groups bg
LEFT JOIN blood_units bu ON bg.blood_group_id = bu.blood_group_id
GROUP BY bg.blood_group_id, bg.group_name, bg.rh_factor, bg.critical_threshold;

CREATE OR REPLACE VIEW vw_expiring_units AS
SELECT 
    bu.unit_id,
    bu.unit_barcode,
    bg.group_name,
    bu.collection_date,
    bu.expiry_date,
    DATEDIFF(bu.expiry_date, CURDATE()) AS days_to_expiry,
    bu.storage_rack,
    bu.status
FROM blood_units bu
JOIN blood_groups bg ON bu.blood_group_id = bg.blood_group_id
WHERE bu.status = 'available' 
  AND bu.expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY);

CREATE OR REPLACE VIEW vw_pending_requests AS
SELECT 
    br.request_id,
    r.patient_name,
    h.name AS hospital_name,
    bg.group_name,
    br.units_needed,
    br.urgency,
    br.reason,
    br.doctor_name,
    br.created_at,
    TIMESTAMPDIFF(HOUR, br.created_at, NOW()) AS hours_pending
FROM blood_requests br
JOIN recipients r ON br.recipient_id = r.recipient_id
JOIN hospitals h ON br.hospital_id = h.hospital_id
JOIN blood_groups bg ON br.blood_group_id = bg.blood_group_id
WHERE br.status = 'pending'
ORDER BY 
    CASE br.urgency 
        WHEN 'Emergency' THEN 1 
        WHEN 'Urgent' THEN 2 
        ELSE 3 
    END, 
    br.created_at ASC;

-- ============================================================================
-- TRIGGERS
-- ============================================================================

DELIMITER //

CREATE TRIGGER trg_after_donation_insert
AFTER INSERT ON donations
FOR EACH ROW
BEGIN
    UPDATE donors 
    SET last_donation_date = NEW.donation_date 
    WHERE donor_id = NEW.donor_id;
END //

CREATE TRIGGER trg_before_request_update
BEFORE UPDATE ON blood_requests
FOR EACH ROW
BEGIN
    IF NEW.status = 'approved' AND OLD.status = 'pending' AND NEW.approved_at IS NULL THEN
        SET NEW.approved_at = NOW();
    END IF;
    IF NEW.status = 'issued' AND OLD.status != 'issued' AND NEW.issued_at IS NULL THEN
        SET NEW.issued_at = NOW();
    END IF;
END //

DELIMITER ;
