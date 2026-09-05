# Walkthrough - VitalRed: Blood Bank Management System

VitalRed has been built from scratch according to all requirements outlined in the **DBMS Project Handbook**. The full-stack system features a 3NF normalized MySQL relational database, a modern PHP backend, and an ultra-professional HealthTech frontend with Google Sign-In, multi-role dashboards, approval workflows, and interactive course evaluation tools.

---

## 🏆 Key Deliverables & Handbook Compliance

### 1. Conceptual ER Diagram (EER Model)
- **Vector Diagram**: [`database/er_diagram.svg`](database/er_diagram.svg)
- **Interactive Academic Viewer**: [`docs.php`](http://localhost/VitalRed/docs.php)
- **Key Constructs Marked**:
  - **Key Attributes (PK)**: Underlined in gold (`donor_id`, `unit_id`, `request_id`, etc.).
  - **Composite Attributes**: Decomposed into atomic sub-attributes (`Name` &rarr; `first_name`, `last_name`; `Address` &rarr; `street`, `city`, `state`, `pincode`).
  - **Multivalued Attribute**: Decomposed into `donor_phones (donor_id, phone_number)`.
  - **Derived Attributes**: `Age` (computed from `dob`), `days_to_expiry` (computed from `expiry_date`), and live `available_units`.
  - **Weak Entity**: `Blood_Unit` identified by `Donation` via the identifying relationship *Yields*.
  - **Cardinalities & Participations**: 1:N, 1:1, M:N with explicit Total vs. Partial participation constraints.

### 2. Relational Schema in Third Normal Form (3NF)
- **Schema File**: [`database/schema.sql`](database/schema.sql)
- **Seed Data**: [`database/seed.sql`](database/seed.sql)
- **Normalized Tables**:
  1. `users` (<u>user_id</u>, full_name, email, password_hash, phone, role, google_id, avatar_url, status, created_at)
  2. `blood_groups` (<u>blood_group_id</u>, group_name, rh_factor, critical_threshold, description)
  3. `hospitals` (<u>hospital_id</u>, name, license_no, address, city, state, pincode, phone, email, contact_person, is_active)
  4. `donors` (<u>donor_id</u>, user_id (FK), first_name, last_name, dob, gender, blood_group_id (FK), address_street, city, state, pincode, emergency_contact, last_donation_date)
  5. `donor_phones` (<u>donor_id (FK), phone_number</u>, phone_type) — *Multivalued phone decomposition*
  6. `recipients` (<u>recipient_id</u>, user_id (FK), hospital_id (FK), patient_name, dob, gender, blood_group_id (FK), contact_phone, medical_record_no)
  7. `donations` (<u>donation_id</u>, donor_id (FK), blood_group_id (FK), donation_date, units_collected, hemoglobin_g_dl, blood_pressure, donation_type, staff_id (FK), remarks)
  8. `blood_requests` (<u>request_id</u>, recipient_id (FK), hospital_id (FK), blood_group_id (FK), units_needed, urgency, reason, doctor_name, status, approved_by (FK), approval_notes, approved_at, issued_at)
  9. `blood_units` (<u>unit_id</u>, unit_barcode, donation_id (FK), blood_group_id (FK), collection_date, expiry_date, storage_rack, status, issued_request_id (FK))

### 3. Five Complex SQL Queries
- **File**: [`database/queries.sql`](database/queries.sql)
- **Live In-App Query Runner**: Available in [`docs.php`](http://localhost/VitalRed/docs.php) with instant AJAX execution.
  - **Query 1**: Group-Wise Stock Monitoring & Critical Shortage Alert (`LEFT JOIN`, `COUNT(CASE ...)`, `GROUP BY`).
  - **Query 2**: Donor Frequency, Lifetime Contribution & 90-Day Eligibility Audit (`JOIN`, `COUNT`, `SUM`, `AVG`, `DATEDIFF`).
  - **Query 3**: Hospital Blood Request Fulfillment & Urgency Performance (`JOIN`, conditional aggregation, `ROUND`).
  - **Query 4**: Inventory Expiration Risk & Shelf-Life Categorization (`JOIN`, `DATEDIFF`, `CASE`).
  - **Query 5**: Staff Operational Workload & Turnaround Audit (`LEFT JOIN`, `TIMESTAMPDIFF`, `AVG`).
  - **Query 6**: Transfusion ABO/Rh Compatibility Match Matrix.

### 4. Professional Frontend UI & Google Sign-In
- **Login Portal** ([`login.php`](http://localhost/VitalRed/login.php)):
  - Official-style **"Sign in with Google"** button with Google Identity Services SDK and instant One-Click Demo Auth.
  - One-Click Demo Credentials Quick-Fill badges for all three roles.
- **Role Portals**:
  - **Admin / Staff Dashboard** ([`admin/index.php`](http://localhost/VitalRed/admin/index.php)): Live KPIs, critical shortage alerts, Chart.js stock distribution and request donut charts, urgent requisitions queue.
  - **Blood Inventory CRUD** ([`admin/stock.php`](http://localhost/VitalRed/admin/stock.php)): Barcode generator, shelf-life monitoring (+42 days for Whole Blood), rack management.
  - **Staff Approval Workflow** ([`admin/requests.php`](http://localhost/VitalRed/admin/requests.php)): Approve/Reject requests, allocate matching available blood units to hospital requests, timestamping, and referential unit linkage.
  - **Donor Portal** ([`donor/index.php`](http://localhost/VitalRed/donor/index.php)): Digital donor card, 90-day cooldown calculator, donation scheduling, printable appreciation certificate.
  - **Requester Portal** ([`requester/index.php`](http://localhost/VitalRed/requester/index.php)): Requisition filing, urgency tiers (Normal, Urgent, Emergency), visual timeline dispatch stepper.
- **Summary Reports Screen** ([`admin/reports.php`](http://localhost/VitalRed/admin/reports.php)):
  - Built from aggregate queries with CSV export and print/PDF formatting.

---

## 🧪 Verification Results

| Check | Tool / Command | Result |
|---|---|---|
| **Database Import** | MySQL CLI | `vitalred_db` created with 9 tables, 3 views, 2 triggers, seed data |
| **Complex Queries Execution** | MySQL CLI | All 6 queries executed successfully with clean tabular outputs |
| **PHP Syntax & Linter** | `php -l` on all 23 files | **0 errors detected** across all scripts |
| **Apache Server Integration** | HTTP Request to `http://localhost/VitalRed/` | `HTTP 200 OK` |
| **Login Page Response** | HTTP Request to `http://localhost/VitalRed/login.php` | `HTTP 200 OK` |
| **Live SQL Runner API** | `ajax_query_runner.php?query_id=1` | `HTTP 200 OK` (Executed in 1.5ms, returned 8 rows) |

---

## 🚀 How to Access & Evaluate

1. **Access URL**: Open your browser and go to:
   ```
   http://localhost/VitalRed/
   ```
2. **Academic Documentation & ERD**:
   ```
   http://localhost/VitalRed/docs.php
   ```
3. **Login Portal**:
   ```
   http://localhost/VitalRed/login.php
   ```
   - **Google Sign-In**: Click **"Sign in with Google"** for instant verified demo login.
   - **Admin Login**: Click the red badge or use `admin@vitalred.org` / `admin123`.
   - **Donor Login**: Click the green badge or use `rahul.sharma@gmail.com` / `donor123`.
   - **Requester Login**: Click the blue badge or use `city.hospital@vitalred.org` / `hospital123`.
