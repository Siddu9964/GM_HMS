# GM_HMS — Admin Role Documentation

---

> **Document Type:** Role-Based User Guide  
> **Role:** Administrator  
> **Version:** 2.0.0  
> **Audience:** Management · Technical Team  
> **Date:** August 2026

---

## Table of Contents
1. [Role Overview](#1-role-overview)
2. [Login & Access](#2-login--access)
3. [Admin Dashboard](#3-admin-dashboard)
4. [Patient Registration (Admin)](#4-patient-registration-admin)
5. [Doctor Management](#5-doctor-management)
6. [Staff Management](#6-staff-management)
7. [Department Management](#7-department-management)
8. [IPD Billing (Admin View)](#8-ipd-billing-admin-view)
9. [OPD Billing Management](#9-opd-billing-management)
10. [Bed Management](#10-bed-management)
11. [OPD Information](#11-opd-information)
12. [IPD Information](#12-ipd-information)
13. [Laboratory Management](#13-laboratory-management)
14. [Nurse Duty Scheduler](#14-nurse-duty-scheduler)
15. [OT Billing](#15-ot-billing)
16. [Discharge Notifications](#16-discharge-notifications)
17. [Security & Permissions](#17-security--permissions)
18. [Workflow Flowchart](#18-workflow-flowchart)

---

## 1. Role Overview

The **Administrator** role has the highest level of access in GM_HMS. An administrator can view, create, update, and delete records across all modules. The admin is typically the Hospital Administrator, IT Manager, or designated management personnel.

### Key Responsibilities
- Configure and maintain hospital master data (doctors, departments, beds)
- Monitor system-wide KPIs through the dashboard
- Manage staff accounts and roles
- Oversee all billing (OPD + IPD)
- Process discharge notifications from nurses
- Generate and review system reports
- Handle escalated billing disputes

### Access Level
```
FULL SYSTEM ACCESS — No restrictions
```

---

## 2. Login & Access

### Login URL
```
http://localhost/GM_HMS/login.php
```

### Credentials Format
| Field | Example |
|-------|---------|
| Username | `admin` |
| Password | `Admin@1234` (bcrypt hashed in DB) |

### Login Flow
```
1. User enters credentials on login.php
2. POST → /api/auth/login
3. AuthController verifies username in 'user' table
4. password_verify() against bcrypt hash
5. Session set: role='admin', user_id, username, full_name
6. Redirect → /GM_HMS/view/admin_dashboard.php
```

### Post-Login Session Variables
```php
$_SESSION['user_id']      // Integer user ID
$_SESSION['role']         // 'admin'
$_SESSION['username']     // Login username
$_SESSION['full_name']    // Display name
$_SESSION['hospital_branch'] // Branch identifier
```

---

## 3. Admin Dashboard

**File:** `view/admin_dashboard.php`  
**API Calls:** `GET /api/admin/dashboard-summary`, `GET /api/admin/bed-availability`, `GET /api/admin/analytics`

### KPI Cards Displayed

| Card | Data Source | Metric |
|------|-------------|--------|
| Today's OPD | `appointments` table | Count of today's appointments |
| Today's Admissions | `ipd_admissions` | New admissions today |
| Total Beds | `hospital_beds` | Total bed count |
| Occupied Beds | `hospital_beds` WHERE status='Occupied' | Occupied count |
| Available Beds | Calculation | Total - Occupied |
| Today's Revenue | `opd_billing_master` + `ipd_billing_master` | Sum of today's payments |
| Pending Bills | `ipd_billing_master` WHERE payment_status='Pending' | Count |
| Active Doctors | `doctors` WHERE status='Active' | Count |

### Dashboard Sections

#### 1. Revenue Analytics Chart
- Line/Bar chart (Chart.js)
- X-axis: Last 30 days
- Y-axis: Revenue in ₹
- API: `GET /api/admin/analytics`

#### 2. Discharge Notifications Panel
- Shows pending discharge requests from nurses
- Table: Patient Name | Admission ID | Requested By | Time | Actions
- Action Buttons: **View Bill** | **Clear Notification**
- API: `POST /view/api/dismiss_discharge_notification.php`

#### 3. Bed Availability Quick View
- Visual grid showing bed status (Green=Available, Red=Occupied)
- Click bed → View patient details
- API: `GET /api/admin/bed-availability`

#### 4. Recent Admissions Widget
- Last 5 IPD admissions
- Shows: Patient name, doctor, ward, date

#### 5. OPD Queue Summary
- Today's appointment count by status
- Scheduled | Completed | Cancelled | Doctor on Leave

### Business Logic
```
Revenue Today = SUM(ipd_payment.amount WHERE DATE(payment_date) = TODAY)
              + SUM(opd_billing_master.total_amount WHERE bill_date = TODAY AND payment_status = 'Paid')

Bed Occupancy % = (occupied_beds / total_beds) × 100
```

---

## 4. Patient Registration (Admin)

**File:** `view/patient_registration.php`  
**APIs Used:**
- `GET /api/patients` — list patients
- `POST /api/patients` — create patient
- `PUT /api/patients/{id}` — update patient
- `DELETE /api/patients/{id}` — soft delete
- `GET /api/patients/check-duplicate` — duplicate check

### Page Features

#### Patient List (DataTable)
- Columns: Patient ID | Name | Age | Gender | Phone | Aadhar | Status | Actions
- Search: Real-time across name, ID, phone, Aadhar
- Pagination: 25 records per page
- Export: CSV, Excel, Print

#### Registration Form Fields
| Field | Type | Required | Validation |
|-------|------|----------|-----------|
| First Name | Text | ✅ | Min 2 chars, alpha only |
| Last Name | Text | ❌ | Alpha only |
| Age | Number | ✅ | 0–120 |
| Gender | Select | ✅ | Male/Female/Other |
| Phone | Tel | ✅ | 10 digits |
| Aadhar | Text | ❌ | 12 digits, duplicate check |
| Blood Group | Select | ❌ | A+/A-/B+/B-/AB+/AB-/O+/O- |
| Address | Textarea | ❌ | Max 500 chars |
| City | Text | ❌ | — |
| Email | Email | ❌ | Valid email format |
| Emergency Contact | Tel | ❌ | 10 digits |
| Photo | File | ❌ | JPG/PNG, max 2MB |

#### Patient ID Generation
```
Format: PID-YYYYMMDD-NNN
Example: PID-20260814-001

Logic:
1. Get today's date → YYYYMMDD
2. Count existing patients with same date prefix
3. Sequence = count + 1, padded to 3 digits
```

#### Duplicate Detection
```
Trigger: On phone/Aadhar entry blur
API: GET /api/patients/check-duplicate?aadhar=XXXX
Response: { "duplicate": true/false, "patient_id": "PID-..." }
Action: Show warning dialog if duplicate found
```

#### Soft Delete
- Delete button sets `patient.status = 'Inactive'`
- Patient not physically deleted from database
- Cannot be recovered from UI (admin can query DB)
- Inactive patients excluded from all listings

---

## 5. Doctor Management

**File:** `view/doctor_management.php`  
**APIs Used:**
- `GET /api/doctors` — list doctors
- `POST /api/doctors` — create doctor
- `PUT /api/doctors/{id}` — update doctor
- `DELETE /api/doctors/{id}` — remove doctor
- `GET /api/departments` — populate department dropdown

### Page Features

#### Doctor List (DataTable)
- Columns: Doctor ID | Name | Specialization | Department | Phone | Consultation Fee | Status | Actions
- Filter by: Department, Specialization

#### Add/Edit Doctor Form Fields
| Field | Type | Required | Notes |
|-------|------|----------|-------|
| Full Name | Text | ✅ | With 'Dr.' prefix |
| Specialization | Text | ✅ | e.g., Cardiology |
| Department | Select | ✅ | From departments list |
| Phone | Tel | ✅ | 10 digits |
| Email | Email | ❌ | — |
| Consultation Fee | Number | ✅ | In ₹ |
| In Time | Time | ✅ | Availability start |
| Out Time | Time | ✅ | Availability end |
| Days Available | Checkboxes | ✅ | Mon–Sun |
| Qualification | Text | ❌ | MBBS, MD, etc. |
| Registration No. | Text | ❌ | Medical council no. |
| Photo | File | ❌ | JPG/PNG |

#### Doctor ID Generation
```
Format: DOC-NNNN (sequential)
Example: DOC-0042
```

#### User Account Creation
When a doctor is added, the system:
1. Creates record in `doctors` table
2. Creates login credentials in `user` table (role = 'doctor')
3. Initial password: hashed value of phone number
4. Doctor must change password on first login

---

## 6. Staff Management

**File:** `view/staff_management.php`  
**APIs Used:**
- `GET /api/staff` — list staff
- `POST /api/staff` — create staff
- `PUT /api/staff/{id}` — update staff
- `DELETE /api/staff/{id}` — remove staff
- `GET /api/staff/designations` — designation list

### Staff Roles Managed
- Receptionist
- Nurse
- Pharmacist
- Lab Technician
- Radiologist
- Store Keeper
- Accountant
- Security

### Staff Form Fields
| Field | Type | Required |
|-------|------|----------|
| Full Name | Text | ✅ |
| Role | Select | ✅ |
| Department | Select | ✅ |
| Designation | Text | ✅ |
| Phone | Tel | ✅ |
| Email | Email | ❌ |
| Joining Date | Date | ✅ |
| Qualification | Text | ❌ |
| Address | Textarea | ❌ |

#### User Account Auto-Creation
Similar to doctors — login account created in `user` table upon staff registration.

---

## 7. Department Management

**File:** `view/department_management.php`  
**APIs Used:** CRUD via `/api/departments`

### Department Fields
| Field | Notes |
|-------|-------|
| Department Name | e.g., Cardiology, General Medicine |
| Department Code | Short code for reporting |
| HOD (Doctor) | Head of Department |
| Description | Purpose of department |
| Status | Active / Inactive |

### Usage
Departments are referenced by:
- Doctors (assignment)
- Staff (assignment)
- OPD billing (service category)
- Lab services (category)

---

## 8. IPD Billing (Admin View)

**File:** `view/ipd_billing.php`  
**APIs Used:**
- `GET /api/ipd-billing-master` (action: get_all_bills, dashboard_stats)
- `GET /api/ipd-billing-items` (action: get_by_bill)
- `GET /api/ipd-payment` (action: get_by_bill)

### Page Sections

#### Billing List Table
- Columns: Bill ID | Patient | Doctor | Admission ID | Total | Paid | Balance | Status | Actions
- Filters: Payment Status | Billing Status | Date Range | Search
- Export: CSV

#### Bill Detail View
When clicking a bill:
1. **Patient Info Panel** — name, age, doctor, ward, bed
2. **Charge Summary** — breakdown by category (Room, Doctor, Lab, Pharmacy, OT)
3. **Items Table** — all billing line items with dates and amounts
4. **Payment History** — all payments, advances, refunds
5. **Financial Summary** — grand total, discount, insurance, balance

#### Admin Actions on Bills
| Action | Description |
|--------|-------------|
| View Bill | Open full bill detail |
| Print Bill | Print-ready bill format |
| Record Payment | Add payment entry |
| Apply Discount | Override discount for any bill |
| Change Status | OPEN → FINALIZED → CANCELLED |
| Process Refund | Issue refund with approval |

---

## 9. OPD Billing Management

**File:** `view/billing_management.php`  
**APIs Used:**
- `GET /api/billing/opd` — bill list
- `GET /api/billing/opd/analytics` — analytics data
- `GET /api/billing/opd/stats` — summary stats

### Analytics Displayed
- Daily revenue trend (bar chart)
- Top services billed
- Doctor-wise billing breakdown
- Payment mode distribution (Cash/Card/UPI/Insurance)

---

## 10. Bed Management

**File:** `view/opd_beds.php`  
**API:** `GET /api/hospital-beds`

### Page Features
- Visual bed grid by ward/floor
- Color coding: Green (Available), Red (Occupied), Yellow (Reserved)
- Click bed → Patient details popup
- Bed stats: Total | Occupied | Available | Under Maintenance

### Bed Configuration
Beds are added/edited via this page:
| Field | Notes |
|-------|-------|
| Ward Name | e.g., General Ward, ICU, NICU |
| Room Name | e.g., Room 101 |
| Bed Number | e.g., Bed-1 |
| Room Type | General/Semi-Private/Private/ICU |
| Amount Per Day | Base bed rate (₹) |
| Nursing Charge | Per day nursing (₹) |
| Doctor Charge | Per day duty doctor (₹) |
| Service Charge | Per day service charge (₹) |
| Total Bed Amount | Sum of above components (₹) |

**Total Bed Amount** = amount_per_day + nursig_charge + doctor_charge + service_charge

---

## 11. OPD Information

**File:** `view/opd_info.php`  
**Purpose:** Admin view of all OPD encounters

### Data Shown
- All appointments with encounter status
- SOAP notes summary
- Lab test orders from OPD
- Prescription issued

---

## 12. IPD Information

**File:** `view/ipd_info.php`  
**Purpose:** Admin view of all IPD admissions

### Filters Available
- Status: Active / Discharged / All
- Date range
- Doctor
- Ward/Bed

---

## 13. Laboratory Management

**File:** `view/laboratory.php`  
**APIs Used:**
- `GET /api/laboratory/services` — test catalog
- `POST /api/laboratory/services` — add test
- `PUT /api/laboratory/services/{cat}/{id}` — update

### Test Catalog Management
| Field | Notes |
|-------|-------|
| Test Name | e.g., CBC, LFT, KFT |
| Category | Hematology, Biochemistry, Microbiology, etc. |
| Price | In ₹ |
| Normal Range | Reference values |
| Parameters | Sub-tests with units and reference ranges |
| TAT | Turnaround time in hours |

---

## 14. Nurse Duty Scheduler

**File:** `view/nurse_duty_scheduler.php`  
**APIs Used:** `/api/nurse-shifts` CRUD

### Scheduler Features
- Weekly calendar view of shift assignments
- Assign nurse to ward/shift/date
- Shift types: Morning (7AM–3PM), Evening (3PM–11PM), Night (11PM–7AM)
- Conflict detection (nurse assigned to 2 shifts simultaneously)
- Bulk assignment across date range

---

## 15. OT Billing

**File:** `view/ot_billing.php`  
**API:** `POST /api/ot-billing`

### OT Bill Fields
| Field | Notes |
|-------|-------|
| Patient ID | IPD patient |
| Procedure Name | Surgery type |
| Surgeon | Doctor who performed |
| Anesthetist | Anesthesia provider |
| OT Date | Date of surgery |
| OT Charges | Base OT room charge |
| Surgeon Fee | Surgeon's fee |
| Anesthesia Fee | Anesthetist fee |
| Materials Cost | Consumables used |
| Total | Sum of all above |

---

## 16. Discharge Notifications

### Overview
The discharge notification system is a **real-time alert channel** from nurses to the admin/billing team.

### Flow
```
NURSE ACTION                    ADMIN VIEW
─────────────                   ──────────
1. Nurse clicks                 1. Dashboard shows
   "Request Discharge"             notification badge
   on ipd_summary.php
                                2. Notification table
2. POST → nurse_view/api/          shows pending requests
   send_discharge_notification.php
                                3. Admin clicks "View Bill"
3. Inserts to                      → goes to IPD billing
   discharge_notifications         for that patient
   (status: 'Pending')
                                4. Admin processes payment
                                   and billing clearance

                                5. Admin clicks "Clear"
                                   → POST → view/api/
                                   dismiss_discharge_notification.php
                                   → status updated to 'Cleared'
```

### Database Table: `discharge_notifications`
| Column | Type | Description |
|--------|------|-------------|
| id | INT AUTO_INCREMENT | Primary key |
| patient_id | VARCHAR | Patient reference |
| admission_id | VARCHAR | Admission reference |
| message | TEXT | Notification message |
| status | ENUM | Pending / Cleared |
| created_at | TIMESTAMP | When notification was sent |

---

## 17. Security & Permissions

### What Admin CAN Do
```
✅ Create/Edit/Delete Doctors
✅ Create/Edit/Delete Staff
✅ Create/Edit/Delete Departments
✅ View ALL bills (OPD + IPD)
✅ Edit any bill amount/discount
✅ Cancel any bill
✅ Process refunds
✅ Clear discharge notifications
✅ View all patient records
✅ Delete (soft) patients
✅ Configure beds/wards
✅ Schedule nurse shifts
✅ Access all reports
```

### Session Guard
```php
// Every admin page checks:
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: /GM_HMS/login.php');
    exit();
}
```

---

## 18. Workflow Flowchart

```
Admin Logs In
      │
      ▼
Dashboard
  ├── View KPIs (OPD, IPD, Revenue, Beds)
  ├── Check Discharge Notifications
  │     ├── View Patient Bill → Process Payment → Clear Notification
  │     └── Dismiss without action
  │
  ├── MASTER DATA MANAGEMENT
  │     ├── Doctor Management (Add/Edit/Delete)
  │     ├── Staff Management (Add/Edit/Delete)
  │     ├── Department Management (Add/Edit/Delete)
  │     └── Bed Management (Configure wards/rooms/beds)
  │
  ├── BILLING OVERSIGHT
  │     ├── OPD Billing (View all, generate reports)
  │     └── IPD Billing (View, apply discounts, finalize)
  │
  ├── CLINICAL OVERSIGHT
  │     ├── OPD Info (All encounters)
  │     ├── IPD Info (All admissions + status)
  │     └── Laboratory (Manage test catalog)
  │
  └── OPERATIONS
        ├── Nurse Duty Scheduler
        └── OT Billing
```

---

*End of Document — Admin Role Documentation*

---
**Document Control** | Version 2.0 | August 2026
