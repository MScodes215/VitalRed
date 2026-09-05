# VitalRed - Blood Bank Management System

A production-grade, secure Blood Bank Management System and Transfusion Network.

Built with **HTML5, CSS3, JavaScript, Bootstrap 5** for the front-end, **PHP 8.2 (PDO)** for the back-end, and a high-performance **MySQL** relational database.

---

## 🌟 Key Features

1. **Live Group-Wise Blood Stock Monitoring & Alerts**:
   - Aggregates available non-expired units per blood group in real time.
   - Triggers dynamic visual alerts when stock falls below critical clinical safety thresholds.

2. **Hospital Blood Requisitions & Approval Workflow**:
   - Hospital blood requisitions carry status (`pending`, `approved`, `issued`, `rejected`).
   - Multi-step approval pattern with approver ID, clinical notes, and timestamp auditing.
   - Physical unit allocation with barcode assignment and traceability back to real voluntary donors.

3. **Official Google Sign-In & Role Authentication**:
   - Integrated with Google Identity Services (GSI) and instant one-click Google Sign-In.
   - Role-Based Access Control (RBAC) across three roles: **Admin / Staff**, **Donor**, and **Hospital Requester**.

4. **Donor Portal & Health Tracking**:
   - Digital Donor Card with unique Donor ID and ABO/Rh classification.
   - 90-Day Medical Cooldown countdown protecting donor wellness.
   - Voluntary donation scheduling and verified appreciation certificates.

5. **Hospital Requester Portal & Live Dispatch Tracking**:
   - Emergency and urgent blood requisition filing with clinical indications.
   - Real-time visual timeline stepper tracking unit cross-match and dispatch.

6. **Executive Summary Reports**:
   - Real-time inventory surveillance, hospital fulfillment turnaround, and expiry risk tiers.
   - Export to CSV and printable report formats.

---

## 🚀 How to Run the Project

### Option A: Using XAMPP (Recommended)
1. Open XAMPP Control Panel and ensure **Apache** and **MySQL** are running.
2. The project directory is available at:
   ```
   http://localhost/VitalRed/
   ```

### Option B: Using PHP Built-In Server
Run the following command from the project root:
```powershell
& "C:\xampp\php\php.exe" -S localhost:8000
```
Then visit `http://localhost:8000/`.

---

## 🔑 Demo Login Accounts

| Role | Email | Password | Access / Dashboard |
|---|---|---|---|
| **Google Sign-In** | *Any Google Account* | *One-Click* | Instant OAuth authentication & auto-provisioning |
| **Admin / Staff** | `admin@vitalred.org` | `admin123` | Full control: Stock CRUD, Approvals, Dispatches, Donors, Reports |
| **Blood Bank Officer** | `staff.priya@vitalred.org` | `staff123` | Verification, Collections, Barcode allocation |
| **Voluntary Donor** | `rahul.sharma@gmail.com` | `donor123` | Donor Card, 90-Day Cooldown Timer, Certificates, Scheduling |
| **Hospital Requester**| `city.hospital@vitalred.org`| `hospital123` | File Emergency Requisitions, Live Dispatch Stepper |
