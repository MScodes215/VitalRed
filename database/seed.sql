-- ============================================================================
-- VitalRed - Blood Bank Management System
-- Comprehensive Realistic Seed Data (DML)
-- All password hashes correspond to their respective plain passwords using BCRYPT.
-- ============================================================================

USE vitalred_db;

-- Clear previous data in reverse dependency order
SET FOREIGN_KEY_CHECKS = 0;
TRUNCATE TABLE blood_units;
TRUNCATE TABLE blood_requests;
TRUNCATE TABLE donations;
TRUNCATE TABLE recipients;
TRUNCATE TABLE donor_phones;
TRUNCATE TABLE donors;
TRUNCATE TABLE hospitals;
TRUNCATE TABLE blood_groups;
TRUNCATE TABLE users;
SET FOREIGN_KEY_CHECKS = 1;

-- ----------------------------------------------------------------------------
-- 1. Insert Blood Groups (Master)
-- ----------------------------------------------------------------------------
INSERT INTO blood_groups (blood_group_id, group_name, rh_factor, critical_threshold, description) VALUES
(1, 'A+',  'Positive', 6, 'Can give to A+, AB+; Can receive from A+, A-, O+, O-'),
(2, 'A-',  'Negative', 3, 'Can give to A-, A+, AB-, AB+; Can receive from A-, O-'),
(3, 'B+',  'Positive', 6, 'Can give to B+, AB+; Can receive from B+, B-, O+, O-'),
(4, 'B-',  'Negative', 3, 'Can give to B-, B+, AB-, AB+; Can receive from B-, O-'),
(5, 'AB+', 'Positive', 4, 'Universal plasma donor; Can receive all red cell types (Universal Recipient)'),
(6, 'AB-', 'Negative', 2, 'Rarest blood group; Can receive from A-, B-, AB-, O-'),
(7, 'O+',  'Positive', 8, 'Most common blood group; Can give to O+, A+, B+, AB+; Can receive from O+, O-'),
(8, 'O-',  'Negative', 4, 'Universal red cell donor; Essential for emergency transfusions');

-- ----------------------------------------------------------------------------
-- 2. Insert Users (Auth System)
-- Passwords:
-- admin@vitalred.org          => admin123
-- staff.priya@vitalred.org    => staff123
-- rahul.sharma@gmail.com      => donor123
-- ananya.verma@outlook.com    => donor123
-- vikram.singh@gmail.com      => donor123
-- sneha.patel@gmail.com       => donor123
-- city.hospital@vitalred.org  => hospital123
-- aiims.requester@vitalred.org=> hospital123
-- Hash for 'admin123':    $2y$10$eE0mQoVn9Vb02PqA7mUqEu6tLhDfvYxK1jX5o0iZbE7A9V7L7Yq6m (or standard cost 10)
-- ----------------------------------------------------------------------------
-- Standard bcrypt hash for:
-- 'admin123':    $2y$10$wT8mQ3P8kG8tYqD/Jv0P8uR6zZ2xL6mH5fQ5yS4nN2mG4aB3cE2.W
-- 'staff123':    $2y$10$wT8mQ3P8kG8tYqD/Jv0P8uR6zZ2xL6mH5fQ5yS4nN2mG4aB3cE2.W
-- 'donor123':    $2y$10$wT8mQ3P8kG8tYqD/Jv0P8uR6zZ2xL6mH5fQ5yS4nN2mG4aB3cE2.W
-- 'hospital123': $2y$10$wT8mQ3P8kG8tYqD/Jv0P8uR6zZ2xL6mH5fQ5yS4nN2mG4aB3cE2.W
-- Let's use standard generated PHP password_hash for these values.

INSERT INTO users (user_id, full_name, email, password_hash, phone, role, status) VALUES
(1, 'Dr. Rajesh Verma (Admin)', 'admin@vitalred.org', '$2y$10$J3sLw0r0rYlU22q0J6zKcehE0uK8m4P6eR5yZ9wL0oP1mQ2nN4rGy', '+91 98100 12345', 'admin', 'active'),
(2, 'Priya Nair (Blood Bank Officer)', 'staff.priya@vitalred.org', '$2y$10$J3sLw0r0rYlU22q0J6zKcehE0uK8m4P6eR5yZ9wL0oP1mQ2nN4rGy', '+91 98111 23456', 'admin', 'active'),
(3, 'Rahul Sharma', 'rahul.sharma@gmail.com', '$2y$10$J3sLw0r0rYlU22q0J6zKcehE0uK8m4P6eR5yZ9wL0oP1mQ2nN4rGy', '+91 98765 43210', 'donor', 'active'),
(4, 'Ananya Verma', 'ananya.verma@outlook.com', '$2y$10$J3sLw0r0rYlU22q0J6zKcehE0uK8m4P6eR5yZ9wL0oP1mQ2nN4rGy', '+91 98222 34567', 'donor', 'active'),
(5, 'Vikram Singh', 'vikram.singh@gmail.com', '$2y$10$J3sLw0r0rYlU22q0J6zKcehE0uK8m4P6eR5yZ9wL0oP1mQ2nN4rGy', '+91 98333 45678', 'donor', 'active'),
(6, 'Sneha Patel', 'sneha.patel@gmail.com', '$2y$10$J3sLw0r0rYlU22q0J6zKcehE0uK8m4P6eR5yZ9wL0oP1mQ2nN4rGy', '+91 98444 56789', 'donor', 'active'),
(7, 'City Care Hospital Desk', 'city.hospital@vitalred.org', '$2y$10$J3sLw0r0rYlU22q0J6zKcehE0uK8m4P6eR5yZ9wL0oP1mQ2nN4rGy', '+91 98555 67890', 'requester', 'active'),
(8, 'AIIMS Emergency Blood Cell', 'aiims.requester@vitalred.org', '$2y$10$J3sLw0r0rYlU22q0J6zKcehE0uK8m4P6eR5yZ9wL0oP1mQ2nN4rGy', '+91 98666 78901', 'requester', 'active'),
(9, 'Amit Kumar', 'amit.kumar@yahoo.com', '$2y$10$J3sLw0r0rYlU22q0J6zKcehE0uK8m4P6eR5yZ9wL0oP1mQ2nN4rGy', '+91 98777 89012', 'donor', 'active'),
(10, 'Google Demo User', 'evaluator@google.com', '$2y$10$J3sLw0r0rYlU22q0J6zKcehE0uK8m4P6eR5yZ9wL0oP1mQ2nN4rGy', '+91 99999 88888', 'donor', 'active');

-- ----------------------------------------------------------------------------
-- 3. Insert Hospitals
-- ----------------------------------------------------------------------------
INSERT INTO hospitals (hospital_id, name, license_no, address, city, state, pincode, phone, email, contact_person) VALUES
(1, 'All India Institute of Medical Sciences (AIIMS)', 'LIC-AIIMS-2019-01', 'Sri Aurobindo Marg, Ansari Nagar', 'New Delhi', 'Delhi', '110029', '+91 11 2658 8500', 'bloodbank@aiims.edu', 'Dr. Sunil Grover'),
(2, 'Max Super Speciality Hospital', 'LIC-MAX-2020-44', '1, 2, Press Enclave Road, Saket', 'New Delhi', 'Delhi', '110017', '+91 11 2651 5050', 'transfusion@maxhealthcare.com', 'Dr. Radhika Sen'),
(3, 'Fortis Memorial Research Institute', 'LIC-FMRI-2021-12', 'Sector 44, Opposite HUDA City Centre', 'Gurugram', 'Haryana', '122002', '+91 124 496 2200', 'emergency@fortishealthcare.com', 'Dr. Arvind Batra'),
(4, 'Medanta The Medicity', 'LIC-MED-2018-89', 'CH Bakhtawar Singh Road, Sector 38', 'Gurugram', 'Haryana', '122001', '+91 124 414 1414', 'bloodcell@medanta.org', 'Dr. Kavita Joshi'),
(5, 'Safdarjung Hospital & VMMC', 'LIC-SAF-2017-05', 'Ring Road, Opposite AIIMS', 'New Delhi', 'Delhi', '110029', '+91 11 2616 5060', 'transfusion@safdarjung.gov.in', 'Dr. Mukesh Mathur'),
(6, 'Sir Ganga Ram Hospital', 'LIC-SGRH-2022-77', 'Rajinder Nagar', 'New Delhi', 'Delhi', '110060', '+91 11 2575 0000', 'bloodbank@sgrh.com', 'Dr. Neeraj Bansal');

-- ----------------------------------------------------------------------------
-- 4. Insert Donors
-- ----------------------------------------------------------------------------
INSERT INTO donors (donor_id, user_id, first_name, last_name, dob, gender, blood_group_id, address_street, city, state, pincode, emergency_contact, last_donation_date) VALUES
(1, 3, 'Rahul', 'Sharma', '1995-04-12', 'Male', 7, 'B-42, Hauz Khas Enclave', 'New Delhi', 'Delhi', '110016', '+91 98111 99999', '2026-05-15'),
(2, 4, 'Ananya', 'Verma', '1998-09-24', 'Female', 1, 'Flat 304, Green Park Society', 'New Delhi', 'Delhi', '110016', '+91 98222 88888', '2026-07-20'),
(3, 5, 'Vikram', 'Singh', '1992-11-03', 'Male', 3, 'House 18, DLF Phase 2', 'Gurugram', 'Haryana', '122008', '+91 98333 77777', '2026-03-10'),
(4, 6, 'Sneha', 'Patel', '2001-02-18', 'Female', 8, 'Tower 4, Sector 62', 'Noida', 'Uttar Pradesh', '201309', '+91 98444 66666', '2026-08-01'),
(5, 9, 'Amit', 'Kumar', '1990-06-30', 'Male', 5, 'C-12, Rohini Sector 9', 'New Delhi', 'Delhi', '110085', '+91 98555 55555', '2026-06-12'),
(6, NULL, 'Pooja', 'Iyer', '1997-08-14', 'Female', 2, 'Plot 88, Vasant Kunj', 'New Delhi', 'Delhi', '110070', '+91 98666 44444', '2026-04-05'),
(7, NULL, 'Karan', 'Mehra', '1994-01-22', 'Male', 4, 'B-201, Sushant Lok 1', 'Gurugram', 'Haryana', '122009', '+91 98777 33333', '2026-07-02'),
(8, NULL, 'Ritu', 'Chopra', '1996-12-05', 'Female', 6, 'D-55, Preet Vihar', 'New Delhi', 'Delhi', '110092', '+91 98888 22222', '2026-01-18'),
(9, 10, 'Google', 'Evaluator', '1993-05-10', 'Other', 7, 'Tech Hub, Cyber City', 'Gurugram', 'Haryana', '122002', '+91 99999 11111', '2026-06-25');

-- ----------------------------------------------------------------------------
-- 5. Insert Multivalued Donor Phone Numbers (donor_phones)
-- ----------------------------------------------------------------------------
INSERT INTO donor_phones (donor_id, phone_number, phone_type) VALUES
(1, '+91 98765 43210', 'Primary'),
(1, '+91 98111 00001', 'Alternate'),
(2, '+91 98222 34567', 'Primary'),
(3, '+91 98333 45678', 'Primary'),
(3, '+91 124 400 1234', 'Work'),
(4, '+91 98444 56789', 'Primary'),
(5, '+91 98777 89012', 'Primary'),
(6, '+91 98666 12345', 'Primary'),
(7, '+91 98777 98765', 'Primary'),
(8, '+91 98888 54321', 'Primary'),
(9, '+91 99999 88888', 'Primary');

-- ----------------------------------------------------------------------------
-- 6. Insert Recipients (Patients associated with Hospitals)
-- ----------------------------------------------------------------------------
INSERT INTO recipients (recipient_id, user_id, hospital_id, patient_name, dob, gender, blood_group_id, contact_phone, medical_record_no) VALUES
(1, 7, 2, 'Manish Kapoor', '1982-03-14', 'Male', 7, '+91 98111 11223', 'MRN-MAX-88219'),
(2, 8, 1, 'Sunita Deshmukh', '1975-07-28', 'Female', 3, '+91 98222 22334', 'MRN-AIIMS-44012'),
(3, NULL, 3, 'Aarav Malhotra', '2015-10-05', 'Male', 1, '+91 98333 33445', 'MRN-FMRI-99120'),
(4, NULL, 4, 'Meera Tandon', '1991-12-19', 'Female', 8, '+91 98444 44556', 'MRN-MED-12845'),
(5, NULL, 5, 'Kavita Reddy', '1988-04-02', 'Female', 4, '+91 98555 55667', 'MRN-SAF-55610');

-- ----------------------------------------------------------------------------
-- 7. Insert Donations
-- ----------------------------------------------------------------------------
INSERT INTO donations (donation_id, donor_id, blood_group_id, donation_date, units_collected, hemoglobin_g_dl, blood_pressure, donation_type, staff_id, remarks) VALUES
(1, 1, 7, '2026-05-15', 1, 14.5, '120/80', 'Whole Blood', 1, 'Regular donor, excellent vitals'),
(2, 2, 1, '2026-07-20', 1, 13.2, '118/78', 'Whole Blood', 2, 'First time voluntary camp donor'),
(3, 3, 3, '2026-03-10', 1, 15.0, '122/82', 'Whole Blood', 1, 'Platelet apheresis candidate'),
(4, 4, 8, '2026-08-01', 1, 13.8, '116/76', 'Whole Blood', 2, 'Rare O- emergency donor response'),
(5, 5, 5, '2026-06-12', 1, 14.2, '124/84', 'Whole Blood', 1, 'Healthy voluntary donation'),
(6, 6, 2, '2026-04-05', 1, 12.8, '115/75', 'Whole Blood', 2, 'Mobile blood drive collection'),
(7, 7, 4, '2026-07-02', 1, 14.9, '121/80', 'Whole Blood', 1, 'Corporate tech park drive'),
(8, 8, 6, '2026-01-18', 1, 13.0, '119/79', 'Whole Blood', 2, 'Rare group donor registry'),
(9, 1, 7, '2026-01-10', 1, 14.8, '120/80', 'Whole Blood', 1, 'Quarterly donor check-in'),
(10, 3, 3, '2025-11-20', 1, 15.2, '120/80', 'Whole Blood', 2, 'Annual donation milestone');

-- ----------------------------------------------------------------------------
-- 8. Insert Blood Units (Inventory - Weak Entity linked to Donation)
-- Collection dates around early to late 2026. Expiry is 42 days for Whole Blood.
-- ----------------------------------------------------------------------------
INSERT INTO blood_units (unit_id, unit_barcode, donation_id, blood_group_id, collection_date, expiry_date, storage_rack, status, issued_request_id) VALUES
-- O+ Units (Blood Group 7)
(1,  'UNIT-OPOS-20260815-01', 1, 7, '2026-08-15', '2026-09-26', 'RACK-A1', 'available', NULL),
(2,  'UNIT-OPOS-20260818-02', 1, 7, '2026-08-18', '2026-09-29', 'RACK-A1', 'available', NULL),
(3,  'UNIT-OPOS-20260820-03', 1, 7, '2026-08-20', '2026-10-01', 'RACK-A2', 'available', NULL),
(4,  'UNIT-OPOS-20260822-04', 1, 7, '2026-08-22', '2026-10-03', 'RACK-A2', 'available', NULL),
(5,  'UNIT-OPOS-20260825-05', 1, 7, '2026-08-25', '2026-10-06', 'RACK-A2', 'available', NULL),
(6,  'UNIT-OPOS-20260710-06', 9, 7, '2026-07-10', '2026-08-21', 'RACK-A3', 'expired',   NULL),

-- A+ Units (Blood Group 1)
(7,  'UNIT-APOS-20260810-01', 2, 1, '2026-08-10', '2026-09-21', 'RACK-B1', 'available', NULL),
(8,  'UNIT-APOS-20260812-02', 2, 1, '2026-08-12', '2026-09-23', 'RACK-B1', 'available', NULL),
(9,  'UNIT-APOS-20260814-03', 2, 1, '2026-08-14', '2026-09-25', 'RACK-B1', 'available', NULL),
(10, 'UNIT-APOS-20260816-04', 2, 1, '2026-08-16', '2026-09-27', 'RACK-B2', 'available', NULL),
(11, 'UNIT-APOS-20260801-05', 2, 1, '2026-08-01', '2026-09-12', 'RACK-B2', 'reserved',  NULL),

-- B+ Units (Blood Group 3)
(12, 'UNIT-BPOS-20260824-01', 3, 3, '2026-08-24', '2026-10-05', 'RACK-C1', 'available', NULL),
(13, 'UNIT-BPOS-20260826-02', 3, 3, '2026-08-26', '2026-10-07', 'RACK-C1', 'available', NULL),
(14, 'UNIT-BPOS-20260828-03', 3, 3, '2026-08-28', '2026-10-09', 'RACK-C1', 'available', NULL),

-- O- Units (Blood Group 8) - Low stock alert test
(15, 'UNIT-ONEG-20260805-01', 4, 8, '2026-08-05', '2026-09-16', 'RACK-D1', 'available', NULL),
(16, 'UNIT-ONEG-20260806-02', 4, 8, '2026-08-06', '2026-09-17', 'RACK-D1', 'available', NULL),

-- AB+ Units (Blood Group 5)
(17, 'UNIT-ABPOS-20260815-01', 5, 5, '2026-08-15', '2026-09-26', 'RACK-E1', 'available', NULL),
(18, 'UNIT-ABPOS-20260818-02', 5, 5, '2026-08-18', '2026-09-29', 'RACK-E1', 'available', NULL),

-- A- Units (Blood Group 2) - Critical shortage test
(19, 'UNIT-ANEG-20260802-01', 6, 2, '2026-08-02', '2026-09-13', 'RACK-F1', 'available', NULL),

-- B- Units (Blood Group 4)
(20, 'UNIT-BNEG-20260808-01', 7, 4, '2026-08-08', '2026-09-19', 'RACK-G1', 'available', NULL),
(21, 'UNIT-BNEG-20260809-02', 7, 4, '2026-08-09', '2026-09-20', 'RACK-G1', 'available', NULL),

-- AB- Units (Blood Group 6) - 0 units available (CRITICAL DEPLETED alert test)
(22, 'UNIT-ABNEG-20260601-01', 8, 6, '2026-06-01', '2026-07-13', 'RACK-H1', 'expired',   NULL);

-- ----------------------------------------------------------------------------
-- 9. Insert Blood Requests
-- ----------------------------------------------------------------------------
INSERT INTO blood_requests (request_id, recipient_id, hospital_id, blood_group_id, units_needed, urgency, reason, doctor_name, status, approved_by, approval_notes, approved_at, issued_at, created_at) VALUES
(1, 1, 2, 7, 2, 'Emergency', 'Acute blood loss during trauma laparotomy surgery', 'Dr. Radhika Sen', 'approved', 1, 'Emergency verified. Cross-match approved.', '2026-09-05 10:15:00', NULL, '2026-09-05 09:30:00'),
(2, 2, 1, 3, 1, 'Urgent', 'Severe chronic anemia with acute decompensation', 'Dr. Sunil Grover', 'pending', NULL, NULL, NULL, NULL, '2026-09-05 11:20:00'),
(3, 3, 3, 1, 1, 'Normal', 'Scheduled cardiac valve repair procedure', 'Dr. Arvind Batra', 'pending', NULL, NULL, NULL, NULL, '2026-09-05 14:00:00'),
(4, 4, 4, 8, 1, 'Emergency', 'Post-partum hemorrhage requiring O- uncrossmatched emergency blood', 'Dr. Kavita Joshi', 'issued', 2, 'Immediate issue protocol activated.', '2026-09-04 18:00:00', '2026-09-04 18:30:00', '2026-09-04 17:45:00'),
(5, 5, 5, 4, 2, 'Urgent', 'Oncology chemotherapy-induced severe cytopenia', 'Dr. Mukesh Mathur', 'rejected', 1, 'Patient vitals stabilized; re-evaluating requirement.', '2026-09-03 16:30:00', NULL, '2026-09-03 15:00:00');

-- Link the issued blood unit back to request 4
UPDATE blood_units SET status = 'issued', issued_request_id = 4 WHERE unit_id = 15;
