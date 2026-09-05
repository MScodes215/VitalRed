-- ============================================================================
-- VitalRed - Blood Bank Management System
-- Comprehensive Seed Data for Kosi Division (Saharsa, Madhepura, Supaul)
-- 10 Hospitals, 7 Doctors, 15 Donors, 5 Requesters, Inventory & Transfusions
-- Default Passwords:
-- Admin:      admin123
-- Staff:      staff123
-- Donor:      donor123
-- Requester:  req123 / hospital123
-- ============================================================================

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
-- 1. Blood Groups Master (All 8 ABO/Rh Types)
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
-- 2. Users (Authentication System)
-- Password Hash works with bcrypt or fallback demo logins (admin123, donor123, req123)
-- ----------------------------------------------------------------------------
INSERT INTO users (user_id, full_name, email, password_hash, phone, role, status) VALUES
-- Admin / Medical Staff (admin123 / staff123)
(1, 'Dr. Rajesh Verma (Admin & CMO)', 'admin@vitalred.org', '$2y$10$YSHpCYyPxsalN9kY5AQbYuQnWiryUI6i/0kAL/uHfWMXfPERh5C.2', '+91 98100 12345', 'admin', 'active'),
(2, 'Priya Nair (Blood Bank Officer)', 'staff.priya@vitalred.org', '$2y$10$qsNgPTXKlTN0bh85sBN42.3DI.wdiNSa6BUlyjHROf3slFCJbeFQ6', '+91 98111 23456', 'admin', 'active'),

-- 15 Donors (donor123)
(3,  'Rahul Sharma',        'rahul.sharma@gmail.com',    '$2y$10$Eb6h5jCOUWqIYcxvGPbWdekr7FuEbPjAGqXg3WOJ.v917IkHKM9Fy', '+91 98765 43210', 'donor', 'active'),
(4,  'Ananya Verma',        'ananya.verma@outlook.com',  '$2y$10$Eb6h5jCOUWqIYcxvGPbWdekr7FuEbPjAGqXg3WOJ.v917IkHKM9Fy', '+91 98222 34567', 'donor', 'active'),
(5,  'Vikram Singh',        'vikram.singh@gmail.com',    '$2y$10$Eb6h5jCOUWqIYcxvGPbWdekr7FuEbPjAGqXg3WOJ.v917IkHKM9Fy', '+91 98333 45678', 'donor', 'active'),
(6,  'Sneha Patel',         'sneha.patel@gmail.com',     '$2y$10$Eb6h5jCOUWqIYcxvGPbWdekr7FuEbPjAGqXg3WOJ.v917IkHKM9Fy', '+91 98444 56789', 'donor', 'active'),
(7,  'Amit Kumar',          'amit.kumar@yahoo.com',      '$2y$10$Eb6h5jCOUWqIYcxvGPbWdekr7FuEbPjAGqXg3WOJ.v917IkHKM9Fy', '+91 98555 67890', 'donor', 'active'),
(8,  'Pooja Iyer',          'pooja.iyer@gmail.com',      '$2y$10$Eb6h5jCOUWqIYcxvGPbWdekr7FuEbPjAGqXg3WOJ.v917IkHKM9Fy', '+91 98666 78901', 'donor', 'active'),
(9,  'Karan Mehra',         'karan.mehra@gmail.com',     '$2y$10$Eb6h5jCOUWqIYcxvGPbWdekr7FuEbPjAGqXg3WOJ.v917IkHKM9Fy', '+91 98777 89012', 'donor', 'active'),
(10, 'Ritu Chopra',         'ritu.chopra@gmail.com',     '$2y$10$Eb6h5jCOUWqIYcxvGPbWdekr7FuEbPjAGqXg3WOJ.v917IkHKM9Fy', '+91 98888 90123', 'donor', 'active'),
(11, 'Alok Kumar Jha',      'alok.jha@gmail.com',        '$2y$10$Eb6h5jCOUWqIYcxvGPbWdekr7FuEbPjAGqXg3WOJ.v917IkHKM9Fy', '+91 98999 01234', 'donor', 'active'),
(12, 'Neha Kumari',         'neha.kumari@gmail.com',     '$2y$10$Eb6h5jCOUWqIYcxvGPbWdekr7FuEbPjAGqXg3WOJ.v917IkHKM9Fy', '+91 98111 12345', 'donor', 'active'),
(13, 'Deepak Kumar Yadav',  'deepak.yadav@gmail.com',    '$2y$10$Eb6h5jCOUWqIYcxvGPbWdekr7FuEbPjAGqXg3WOJ.v917IkHKM9Fy', '+91 98222 23456', 'donor', 'active'),
(14, 'Suman Saurav',        'suman.saurav@gmail.com',    '$2y$10$Eb6h5jCOUWqIYcxvGPbWdekr7FuEbPjAGqXg3WOJ.v917IkHKM9Fy', '+91 98333 34567', 'donor', 'active'),
(15, 'Priyanka Roy',        'priyanka.roy@gmail.com',    '$2y$10$Eb6h5jCOUWqIYcxvGPbWdekr7FuEbPjAGqXg3WOJ.v917IkHKM9Fy', '+91 98444 45678', 'donor', 'active'),
(16, 'Md. Tarique Anwar',   'tarique.anwar@gmail.com',   '$2y$10$Eb6h5jCOUWqIYcxvGPbWdekr7FuEbPjAGqXg3WOJ.v917IkHKM9Fy', '+91 98555 56789', 'donor', 'active'),
(17, 'Rajesh Ranjan',       'rajesh.ranjan@gmail.com',   '$2y$10$Eb6h5jCOUWqIYcxvGPbWdekr7FuEbPjAGqXg3WOJ.v917IkHKM9Fy', '+91 98666 67890', 'donor', 'active'),

-- 5 Requesters (Hospital Desks & Requisitions) (req123)
(18, 'LBKMCH Saharsa Blood Desk',          'lbkmch.req@vitalred.org',     '$2y$10$Qqv54QsFu4jTtvHsV.NUM.bcqYP7cNCmmZiTKlWg.4RENV1BaxmeC', '+91 98777 78901', 'requester', 'active'),
(19, 'Sadar Hospital Saharsa Emergency',   'saharsa.sadar@vitalred.org',  '$2y$10$Qqv54QsFu4jTtvHsV.NUM.bcqYP7cNCmmZiTKlWg.4RENV1BaxmeC', '+91 98888 89012', 'requester', 'active'),
(20, 'JNKTMCH Madhepura Trauma Unit',      'jnktmch.req@vitalred.org',    '$2y$10$Qqv54QsFu4jTtvHsV.NUM.bcqYP7cNCmmZiTKlWg.4RENV1BaxmeC', '+91 98999 90123', 'requester', 'active'),
(21, 'Sadar Hospital Supaul Blood Center', 'supaul.sadar@vitalred.org',   '$2y$10$Qqv54QsFu4jTtvHsV.NUM.bcqYP7cNCmmZiTKlWg.4RENV1BaxmeC', '+91 98111 01234', 'requester', 'active'),
(22, 'SDH Simri Bakhtiyarpur Desk',        'simri.sdh@vitalred.org',      '$2y$10$Qqv54QsFu4jTtvHsV.NUM.bcqYP7cNCmmZiTKlWg.4RENV1BaxmeC', '+91 98222 12345', 'requester', 'active');

-- ----------------------------------------------------------------------------
-- 3. 10 Hospitals (Covering All Major Hospitals in Kosi Division: Saharsa, Madhepura, Supaul)
-- With Contact Person as Authorized Doctors / Medical Officers
-- ----------------------------------------------------------------------------
INSERT INTO hospitals (hospital_id, name, license_no, address, city, state, pincode, phone, email, contact_person) VALUES
(1,  'Lord Buddha Koshi Medical College & Hospital (LBKMCH)',        'LIC-LBKMCH-2018-01', 'NH-107, Baijnathpur',                  'Saharsa',             'Bihar', '852201', '+91 6478 224500', 'bloodbank@lbkmch.edu.in',        'Dr. Amit Kumar Jha'),
(2,  'Sadar Hospital Saharsa',                                       'LIC-SH-SAH-2015-02', 'Hospital Road, Near Collectorate',     'Saharsa',             'Bihar', '852201', '+91 6478 223101', 'sadar.saharsa@biharhealth.gov.in', 'Dr. Rajesh Verma'),
(3,  'Sub-Divisional Hospital (SDH) Simri Bakhtiyarpur',             'LIC-SDH-SB-2019-03', 'Station Road, Simri Bakhtiyarpur',     'Simri Bakhtiyarpur',  'Bihar', '852127', '+91 6478 238202', 'sdh.simri@biharhealth.gov.in',    'Dr. Anand Vardhan'),
(4,  'Koshi Lifeline Multi-Speciality Hospital',                     'LIC-KLM-SAH-2021-04', 'Tiwari Tola, Bypass Road',             'Saharsa',             'Bihar', '852201', '+91 6478 229900', 'emergency@koshilifeline.com',     'Dr. Alok Ranjan'),
(5,  'Jannayak Karpoori Thakur Medical College & Hospital (JNKTMCH)','LIC-JNKTMCH-2020-05', 'Singheshwar Road',                   'Madhepura',            'Bihar', '852113', '+91 6476 226100', 'bloodcell@jnktmch.edu.in',        'Dr. Priya Ranjan'),
(6,  'Sadar Hospital Madhepura',                                     'LIC-SH-MDP-2016-06', 'Main Hospital Chowk',                  'Madhepura',            'Bihar', '852113', '+91 6476 222303', 'sadar.madhepura@biharhealth.gov.in','Dr. Ritu Kumari'),
(7,  'Sub-Divisional Hospital (SDH) Udakishanganj',                  'LIC-SDH-UKG-2020-07', 'Block Campus, Udakishanganj',         'Udakishanganj',       'Bihar', '852220', '+91 6476 244101', 'sdh.udakishanganj@biharhealth.gov.in','Dr. Pankaj Kumar'),
(8,  'Sadar Hospital Supaul',                                        'LIC-SH-SUP-2017-08', 'Kachehari Road, Supaul Town',          'Supaul',              'Bihar', '852131', '+91 6473 224201', 'sadar.supaul@biharhealth.gov.in',  'Dr. Sunil Kumar Yadav'),
(9,  'Sub-Divisional Hospital (SDH) Triveniganj',                    'LIC-SDH-TRV-2019-09', 'Market Road, Triveniganj',            'Triveniganj',         'Bihar', '852139', '+91 6473 233402', 'sdh.triveniganj@biharhealth.gov.in', 'Dr. Mukesh Kumar'),
(10, 'Sub-Divisional Hospital (SDH) Birpur',                         'LIC-SDH-BRP-2021-10', 'Indo-Nepal Border Road, Birpur',       'Birpur',              'Bihar', '854340', '+91 6473 241105', 'sdh.birpur@biharhealth.gov.in',      'Dr. Sanjeev Kumar');

-- ----------------------------------------------------------------------------
-- 4. 15 Donors (With Kosi Division Cities & Blood Types)
-- ----------------------------------------------------------------------------
INSERT INTO donors (donor_id, user_id, first_name, last_name, dob, gender, blood_group_id, address_street, city, state, pincode, emergency_contact, last_donation_date) VALUES
(1,  3,  'Rahul',   'Sharma', '1995-04-12', 'Male',   7, 'Ward No. 12, D.B. Road',          'Saharsa',            'Bihar', '852201', '+91 98111 99999', '2026-05-15'),
(2,  4,  'Ananya',  'Verma',  '1998-09-24', 'Female', 1, 'College Chowk, Professor Colony', 'Madhepura',           'Bihar', '852113', '+91 98222 88888', '2026-07-20'),
(3,  5,  'Vikram',  'Singh',  '1992-11-03', 'Male',   3, 'Cinema Road, Supaul Bazaar',      'Supaul',             'Bihar', '852131', '+91 98333 77777', '2026-03-10'),
(4,  6,  'Sneha',   'Patel',  '2001-02-18', 'Female', 8, 'Refugee Colony, Bengali Tola',    'Saharsa',            'Bihar', '852201', '+91 98444 66666', '2026-08-01'),
(5,  7,  'Amit',    'Kumar',  '1990-06-30', 'Male',   5, 'Singheshwar Mandir Road',         'Madhepura',           'Bihar', '852113', '+91 98555 55555', '2026-06-12'),
(6,  8,  'Pooja',   'Iyer',   '1997-08-14', 'Female', 2, 'Ward 4, Near Gandhi Maidan',      'Supaul',             'Bihar', '852131', '+91 98666 44444', '2026-04-05'),
(7,  9,  'Karan',   'Mehra',  '1994-01-22', 'Male',   4, 'Purani Bazaar, Station Road',     'Simri Bakhtiyarpur', 'Bihar', '852127', '+91 98777 33333', '2026-07-02'),
(8,  10, 'Ritu',    'Chopra', '1996-12-05', 'Female', 6, 'Hospital Road, Block Chowk',      'Triveniganj',        'Bihar', '852139', '+91 98888 22222', '2026-01-18'),
(9,  11, 'Alok',    'Jha',    '1993-07-19', 'Male',   7, 'Naya Bazaar, Purab Tola',         'Saharsa',            'Bihar', '852201', '+91 98999 11111', '2026-08-10'),
(10, 12, 'Neha',    'Kumari', '1999-11-28', 'Female', 3, 'Goshala Road, Ward 7',           'Madhepura',           'Bihar', '852113', '+91 98111 22222', '2026-07-28'),
(11, 13, 'Deepak',  'Yadav',  '1991-03-15', 'Male',   1, 'Station Road, Ward 9',            'Supaul',             'Bihar', '852131', '+91 98222 33333', '2026-06-05'),
(12, 14, 'Suman',   'Saurav', '1996-08-22', 'Male',   7, 'Tiwari Tola, Gangjala',           'Saharsa',            'Bihar', '852201', '+91 98333 44444', '2026-08-15'),
(13, 15, 'Priyanka','Roy',    '2000-05-14', 'Female', 2, 'Main Road, Puraini Mor',          'Udakishanganj',      'Bihar', '852220', '+91 98444 55555', '2026-05-20'),
(14, 16, 'Tarique', 'Anwar',  '1994-10-09', 'Male',   3, 'Barrage Colony, Ward 3',          'Birpur',             'Bihar', '854340', '+91 98555 66666', '2026-07-12'),
(15, 17, 'Rajesh',  'Ranjan', '1989-12-01', 'Male',   5, 'Shankar Chowk, Ward 15',          'Saharsa',            'Bihar', '852201', '+91 98666 77777', '2026-08-20');

-- ----------------------------------------------------------------------------
-- 5. Multivalued Phone Numbers (donor_phones)
-- ----------------------------------------------------------------------------
INSERT INTO donor_phones (donor_id, phone_number, phone_type) VALUES
(1,  '+91 98765 43210', 'Primary'),
(1,  '+91 98111 00001', 'Alternate'),
(2,  '+91 98222 34567', 'Primary'),
(3,  '+91 98333 45678', 'Primary'),
(3,  '+91 6473 221234', 'Work'),
(4,  '+91 98444 56789', 'Primary'),
(5,  '+91 98555 67890', 'Primary'),
(6,  '+91 98666 78901', 'Primary'),
(7,  '+91 98777 89012', 'Primary'),
(8,  '+91 98888 90123', 'Primary'),
(9,  '+91 98999 01234', 'Primary'),
(10, '+91 98111 12345', 'Primary'),
(11, '+91 98222 23456', 'Primary'),
(12, '+91 98333 34567', 'Primary'),
(13, '+91 98444 45678', 'Primary'),
(14, '+91 98555 56789', 'Primary'),
(15, '+91 98666 67890', 'Primary');

-- ----------------------------------------------------------------------------
-- 6. 5 Requesters & Patients (Associated with Kosi Division Hospitals)
-- ----------------------------------------------------------------------------
INSERT INTO recipients (recipient_id, user_id, hospital_id, patient_name, dob, gender, blood_group_id, contact_phone, medical_record_no) VALUES
(1, 18, 1, 'Ramesh Das',      '1978-03-14', 'Male',   7, '+91 98777 78901', 'MRN-LBKMCH-88219'),
(2, 19, 2, 'Sunita Devi',     '1985-07-28', 'Female', 3, '+91 98888 89012', 'MRN-SAH-SADAR-44012'),
(3, 20, 5, 'Manoj Yadav',     '1991-10-05', 'Male',   1, '+91 98999 90123', 'MRN-JNKTMCH-99120'),
(4, 21, 8, 'Geeta Kumari',    '1995-12-19', 'Female', 8, '+91 98111 01234', 'MRN-SUP-SADAR-12845'),
(5, 22, 3, 'Md. Farooque',    '1982-04-02', 'Male',   4, '+91 98222 12345', 'MRN-SDH-SIMRI-55610');

-- ----------------------------------------------------------------------------
-- 7. Donations (Transfusion Collection Events)
-- ----------------------------------------------------------------------------
INSERT INTO donations (donation_id, donor_id, blood_group_id, donation_date, units_collected, hemoglobin_g_dl, blood_pressure, donation_type, staff_id, remarks) VALUES
(1,  1,  7, '2026-05-15', 1, 14.5, '120/80', 'Whole Blood', 1, 'Camp at Sadar Hospital Saharsa - Dr. Rajesh Verma'),
(2,  2,  1, '2026-07-20', 1, 13.2, '118/78', 'Whole Blood', 2, 'Voluntary drive at JNKTMCH Madhepura - Dr. Priya Ranjan'),
(3,  3,  3, '2026-03-10', 1, 15.0, '122/82', 'Whole Blood', 1, 'Blood bank donation at Sadar Hospital Supaul - Dr. Sunil Kumar Yadav'),
(4,  4,  8, '2026-08-01', 1, 13.8, '116/76', 'Whole Blood', 2, 'Emergency O- donor response at LBKMCH Saharsa - Dr. Amit Kumar Jha'),
(5,  5,  5, '2026-06-12', 1, 14.2, '124/84', 'Whole Blood', 1, 'Healthy donation at Sadar Hospital Madhepura - Dr. Ritu Kumari'),
(6,  6,  2, '2026-04-05', 1, 12.8, '115/75', 'Whole Blood', 2, 'Mobile blood drive in Supaul District'),
(7,  7,  4, '2026-07-02', 1, 14.9, '121/80', 'Whole Blood', 1, 'Voluntary donor camp at SDH Simri Bakhtiyarpur - Dr. Anand Vardhan'),
(8,  8,  6, '2026-01-18', 1, 13.0, '119/79', 'Whole Blood', 2, 'Rare AB- registry donor at SDH Triveniganj - Dr. Mukesh Kumar'),
(9,  9,  7, '2026-08-10', 1, 14.6, '120/80', 'Whole Blood', 1, 'Walk-in voluntary donor at Sadar Hospital Saharsa'),
(10, 10, 3, '2026-07-28', 1, 13.5, '118/78', 'Whole Blood', 2, 'College campus drive in Madhepura'),
(11, 11, 1, '2026-06-05', 1, 14.8, '122/80', 'Whole Blood', 1, 'Police Line blood camp in Supaul'),
(12, 12, 7, '2026-08-15', 1, 15.1, '120/80', 'Whole Blood', 2, 'Independence Day blood donation camp Saharsa'),
(13, 13, 2, '2026-05-20', 1, 12.9, '116/76', 'Whole Blood', 1, 'Rural health outreach in Udakishanganj'),
(14, 14, 3, '2026-07-12', 1, 14.4, '120/82', 'Whole Blood', 2, 'Birpur border health center drive'),
(15, 15, 5, '2026-08-20', 1, 14.0, '122/80', 'Whole Blood', 1, 'Youth red cross wing donation Saharsa');

-- ----------------------------------------------------------------------------
-- 8. Blood Units (Inventory Weak Entity tied to Transfusion events)
-- ----------------------------------------------------------------------------
INSERT INTO blood_units (unit_id, unit_barcode, donation_id, blood_group_id, collection_date, expiry_date, storage_rack, status, issued_request_id) VALUES
-- O+ Units (Blood Group 7)
(1,  'UNIT-OPOS-20260815-01', 1,  7, '2026-08-15', '2026-09-26', 'RACK-A1', 'available', NULL),
(2,  'UNIT-OPOS-20260818-02', 9,  7, '2026-08-10', '2026-09-21', 'RACK-A1', 'available', NULL),
(3,  'UNIT-OPOS-20260820-03', 12, 7, '2026-08-15', '2026-09-26', 'RACK-A2', 'available', NULL),
(4,  'UNIT-OPOS-20260822-04', 1,  7, '2026-08-22', '2026-10-03', 'RACK-A2', 'available', NULL),
(5,  'UNIT-OPOS-20260825-05', 9,  7, '2026-08-25', '2026-10-06', 'RACK-A2', 'available', NULL),

-- A+ Units (Blood Group 1)
(6,  'UNIT-APOS-20260810-01', 2,  1, '2026-07-20', '2026-08-31', 'RACK-B1', 'available', NULL),
(7,  'UNIT-APOS-20260812-02', 11, 1, '2026-06-05', '2026-07-17', 'RACK-B1', 'available', NULL),
(8,  'UNIT-APOS-20260814-03', 2,  1, '2026-08-14', '2026-09-25', 'RACK-B1', 'available', NULL),

-- B+ Units (Blood Group 3)
(9,  'UNIT-BPOS-20260824-01', 3,  3, '2026-03-10', '2026-04-21', 'RACK-C1', 'available', NULL),
(10, 'UNIT-BPOS-20260826-02', 10, 3, '2026-07-28', '2026-09-08', 'RACK-C1', 'available', NULL),
(11, 'UNIT-BPOS-20260828-03', 14, 3, '2026-07-12', '2026-08-23', 'RACK-C1', 'available', NULL),

-- O- Units (Blood Group 8) - Critical emergency reserve
(12, 'UNIT-ONEG-20260805-01', 4,  8, '2026-08-01', '2026-09-12', 'RACK-D1', 'available', NULL),
(13, 'UNIT-ONEG-20260806-02', 4,  8, '2026-08-06', '2026-09-17', 'RACK-D1', 'available', NULL),

-- AB+ Units (Blood Group 5)
(14, 'UNIT-ABPOS-20260815-01', 5, 5, '2026-06-12', '2026-07-24', 'RACK-E1', 'available', NULL),
(15, 'UNIT-ABPOS-20260818-02', 15,5, '2026-08-20', '2026-10-01', 'RACK-E1', 'available', NULL),

-- A- Units (Blood Group 2)
(16, 'UNIT-ANEG-20260802-01', 6,  2, '2026-04-05', '2026-05-17', 'RACK-F1', 'available', NULL),
(17, 'UNIT-ANEG-20260803-02', 13, 2, '2026-05-20', '2026-07-01', 'RACK-F1', 'available', NULL),

-- B- Units (Blood Group 4)
(18, 'UNIT-BNEG-20260808-01', 7,  4, '2026-07-02', '2026-08-13', 'RACK-G1', 'available', NULL),

-- AB- Units (Blood Group 6)
(19, 'UNIT-ABNEG-20260601-01', 8, 6, '2026-01-18', '2026-03-01', 'RACK-H1', 'expired',   NULL);

-- ----------------------------------------------------------------------------
-- 9. 5 Blood Requests (Prescribed by Respective Doctors in Kosi Division)
-- ----------------------------------------------------------------------------
INSERT INTO blood_requests (request_id, recipient_id, hospital_id, blood_group_id, units_needed, urgency, reason, doctor_name, status, approved_by, approval_notes, approved_at, issued_at, created_at) VALUES
(1, 1, 1, 7, 2, 'Emergency', 'Acute blood loss in road traffic trauma accident near Baijnathpur', 'Dr. Amit Kumar Jha',    'approved', 1, 'Emergency verified. Cross-match approved.', '2026-09-05 10:15:00', NULL, '2026-09-05 09:30:00'),
(2, 2, 2, 3, 1, 'Urgent',    'Severe anemia with acute heart failure symptoms',                 'Dr. Rajesh Verma',       'pending',  NULL, NULL, NULL, NULL, '2026-09-05 11:20:00'),
(3, 3, 5, 1, 1, 'Normal',    'Elective orthopedic knee replacement surgery',                     'Dr. Priya Ranjan',       'pending',  NULL, NULL, NULL, NULL, '2026-09-05 14:00:00'),
(4, 4, 8, 8, 1, 'Emergency', 'Post-partum hemorrhage in obstetrics wing',                       'Dr. Sunil Kumar Yadav',  'issued',   2, 'Emergency O- issued without delay.', '2026-09-04 18:00:00', '2026-09-04 18:30:00', '2026-09-04 17:45:00'),
(5, 5, 3, 4, 2, 'Urgent',    'Gastrointestinal bleeding patient admitted in emergency ward',     'Dr. Anand Vardhan',      'approved', 1, 'Units reserved in blood bank bank rack.', '2026-09-05 16:30:00', NULL, '2026-09-05 15:00:00');

-- Link issued unit
UPDATE blood_units SET status = 'issued', issued_request_id = 4 WHERE unit_id = 12;
