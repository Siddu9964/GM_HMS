# GM_HMS — Database Documentation

---

> **Document Type:** Database Reference  
> **Version:** 2.0.0  
> **Database:** MySQL — `hmsc_basaveshwranagara` / `hmsci`  
> **Audience:** Technical Team · Database Administrators  
> **Date:** August 2026

---

## Table of Contents
1. [Database Overview](#1-database-overview)
2. [User & Authentication Tables](#2-user--authentication-tables)
3. [Patient Management Tables](#3-patient-management-tables)
4. [Appointment & OPD Tables](#4-appointment--opd-tables)
5. [Consultation & Prescription Tables](#5-consultation--prescription-tables)
6. [IPD Tables](#6-ipd-tables)
7. [Billing Tables](#7-billing-tables)
8. [Pharmacy Tables](#8-pharmacy-tables)
9. [Laboratory Tables](#9-laboratory-tables)
10. [Staff & Shift Tables](#10-staff--shift-tables)
11. [Configuration Tables](#11-configuration-tables)
12. [Notification Tables](#12-notification-tables)
13. [Table Relationships (ER Narrative)](#13-table-relationships-er-narrative)
14. [CRUD Operations by Module](#14-crud-operations-by-module)

---

## 1. Database Overview

### Connection Configuration

| Setting | Value |
|---------|-------|
| Host | localhost |
| Port | 3306 |
| Charset | utf8mb4 |
| Collation | utf8mb4_unicode_ci |
| SQL Mode | STRICT_ALL_TABLES, NO_ZERO_DATE |
| Time Zone | +05:30 (IST) |

### Multi-Branch Databases

| Branch | Database |
|--------|----------|
| Basaveshwaranagar | `hmsc_basaveshwranagara` |
| Nagarabhavi | `hmsci` |

### Table Count
Total tables: **35+** across categories

---

## 2. User & Authentication Tables

### `user`
Primary authentication table for all system users.

| Column | Type | Null | Key | Description |
|--------|------|------|-----|-------------|
| id | INT AUTO_INCREMENT | NO | PK | Primary key |
| username | VARCHAR(100) | NO | UNIQUE | Login username |
| password | VARCHAR(255) | NO | — | bcrypt hashed password |
| role | ENUM | NO | — | admin/doctor/receptionist/nurse/pharmacist |
| staff_id | INT | YES | FK | Reference to staff table |
| doctor_id | VARCHAR(20) | YES | FK | Reference to doctors table |
| is_active | TINYINT(1) | YES | — | 1=active, 0=disabled |
| last_login | TIMESTAMP | YES | — | Last successful login |
| created_at | TIMESTAMP | NO | — | Account creation time |

**Role Values:** `admin` | `doctor` | `receptionist` | `nurse` | `pharmacist`

**CRUD Operations:**
- CREATE: When doctor/staff is registered
- READ: On every login attempt
- UPDATE: Password change, disable account
- DELETE: Not recommended (soft disable via is_active)

---

## 3. Patient Management Tables

### `patient`
Core patient demographics and registration.

| Column | Type | Null | Key | Description |
|--------|------|------|-----|-------------|
| patient_id | VARCHAR(25) | NO | PK | PID-YYYYMMDD-NNN format |
| first_name | VARCHAR(100) | NO | — | First name |
| last_name | VARCHAR(100) | YES | — | Last name |
| age | INT | YES | — | Age in years |
| sex | ENUM | YES | — | Male/Female/Other |
| phone | VARCHAR(15) | YES | IDX | Primary phone |
| aadhar | VARCHAR(20) | YES | IDX | Aadhar number |
| blood_group | VARCHAR(10) | YES | — | Blood group |
| address | TEXT | YES | — | Residential address |
| city | VARCHAR(100) | YES | — | City |
| state | VARCHAR(100) | YES | — | State |
| pincode | VARCHAR(10) | YES | — | PIN code |
| email | VARCHAR(150) | YES | — | Email address |
| emergency_contact | VARCHAR(15) | YES | — | Emergency contact number |
| emergency_contact_name | VARCHAR(100) | YES | — | Emergency contact name |
| emergency_relation | VARCHAR(50) | YES | — | Relationship to patient |
| photo_path | VARCHAR(255) | YES | — | Profile photo file path |
| status | VARCHAR(20) | YES | — | Active/Inactive |
| created_at | TIMESTAMP | NO | — | Registration timestamp |
| updated_at | TIMESTAMP | YES | — | Last update |
| created_by | VARCHAR(100) | YES | — | Who registered |

**Indexes:** phone, aadhar, patient_id (PK)

---

## 4. Appointment & OPD Tables

### `appointments`
OPD appointment booking records.

| Column | Type | Null | Key | Description |
|--------|------|------|-----|-------------|
| appointment_id | VARCHAR(25) | NO | PK | APT-YYYYMMDD-XXXX |
| patient_id | VARCHAR(25) | YES | FK | References patient |
| doctor_id | VARCHAR(20) | YES | FK | References doctors |
| appointment_date | DATE | NO | IDX | Date of appointment |
| appointment_time | TIME | YES | — | Time slot |
| appointment_type | VARCHAR(50) | YES | — | OPD/Emergency/Walk-in |
| reason | TEXT | YES | — | Chief complaint |
| appointment_status | VARCHAR(50) | YES | IDX | Scheduled/Completed/Cancelled |
| payment_status | VARCHAR(50) | YES | — | Paid/Pending |
| consultation_fee | DECIMAL(10,2) | YES | — | Fee charged |
| discount | DECIMAL(10,2) | YES | — | Discount applied |
| total_amount | DECIMAL(10,2) | YES | — | Net amount |
| payment_mode | VARCHAR(50) | YES | — | Cash/Card/UPI |
| token_number | INT | YES | IDX | Queue token |
| phone | VARCHAR(15) | YES | — | Contact for this appointment |
| remarks | TEXT | YES | — | Additional notes |
| created_at | TIMESTAMP | NO | — | Booking time |
| created_by | VARCHAR(100) | YES | — | Who booked |

---

### `doctors`
Doctor profiles and availability.

| Column | Type | Null | Key | Description |
|--------|------|------|-----|-------------|
| doctor_id | VARCHAR(20) | NO | PK | DOC-NNNN format |
| full_name | VARCHAR(200) | NO | — | Full name with title |
| specialization | VARCHAR(100) | YES | — | Medical specialty |
| department_id | VARCHAR(20) | YES | FK | Department reference |
| phone | VARCHAR(15) | YES | — | Contact number |
| email | VARCHAR(150) | YES | — | Email |
| consultation_fee | DECIMAL(10,2) | YES | — | Standard fee |
| in_time | TIME | YES | — | Daily start time |
| out_time | TIME | YES | — | Daily end time |
| qualification | VARCHAR(200) | YES | — | Medical qualifications |
| registration_no | VARCHAR(50) | YES | — | Medical council number |
| status | VARCHAR(20) | YES | — | Active/Inactive |
| created_at | TIMESTAMP | YES | — | Registration date |

---

## 5. Consultation & Prescription Tables

### `consultations`
SOAP notes from doctor consultations.

| Column | Type | Null | Key | Description |
|--------|------|------|-----|-------------|
| consultation_id | INT AUTO_INCREMENT | NO | PK | System ID |
| appointment_id | VARCHAR(25) | YES | FK | Linked appointment |
| patient_id | VARCHAR(25) | YES | FK | Patient reference |
| doctor_id | VARCHAR(20) | YES | FK | Doctor who consulted |
| consultation_date | DATE | YES | IDX | Date of consultation |
| consultation_time | TIME | YES | — | Time |
| soap_subjective | TEXT | YES | — | S: Patient complaint |
| soap_objective | TEXT | YES | — | O: Clinical findings |
| soap_assessment | TEXT | YES | — | A: Diagnosis |
| soap_plan | TEXT | YES | — | P: Treatment plan |
| diagnosis | VARCHAR(500) | YES | IDX | Primary diagnosis |
| status | VARCHAR(50) | YES | — | Completed/Follow-up |
| follow_up_date | DATE | YES | — | Next appointment |
| created_at | TIMESTAMP | YES | — | Consultation timestamp |

---

### `prescriptions`
Medication prescriptions issued by doctors.

| Column | Type | Null | Key | Description |
|--------|------|------|-----|-------------|
| prescription_id | INT AUTO_INCREMENT | NO | PK | System ID |
| consultation_id | INT | YES | FK | Linked consultation |
| patient_id | VARCHAR(25) | YES | FK | Patient reference |
| doctor_id | VARCHAR(20) | YES | FK | Prescribing doctor |
| prescription_date | DATE | YES | IDX | Date of prescription |
| medications | JSON / TEXT | YES | — | Medications list (JSON array) |
| special_instructions | TEXT | YES | — | Patient instructions |
| status | VARCHAR(50) | YES | — | Active/Completed |
| printed | TINYINT(1) | YES | — | Whether printed |
| printed_at | TIMESTAMP | YES | — | Print timestamp |
| created_at | TIMESTAMP | YES | — | Creation time |

**medications JSON structure:**
```json
[
  {
    "medicine_name": "Amoxicillin 500mg",
    "brand": "Amoxil",
    "dosage": "500mg",
    "route": "Oral",
    "frequency": "TID",
    "duration": "7 days",
    "instructions": "After food"
  }
]
```

---

## 6. IPD Tables

### `ipd_admissions`
(See [MOD_IPD.md](./modules/MOD_IPD.md) for full column reference)

Key columns:
- `admission_id` PK: ADM-YYYYMMDD-NNN
- `patient_id` FK → patient
- `admitting_doctor_id` FK → doctors
- `bed_id` FK → hospital_beds
- `status`: Admitted / Discharged / LAMA / Expired
- `bill_type`: SELF / INSURANCE / CORPORATE

---

### `hospital_beds`
Bed inventory with pricing.

| Column | Type | Description |
|--------|------|-------------|
| sl_no | INT PK | Primary key |
| bed_number | VARCHAR | Display bed number |
| ward_name | VARCHAR | Ward/unit |
| room_name | VARCHAR | Room |
| floor | VARCHAR | Floor number |
| room_type | ENUM | General/Semi-Private/Private/ICU |
| room_category | VARCHAR | Additional classification |
| amount_per_day | DECIMAL | Base rent |
| nursig_charge | DECIMAL | Nursing per day |
| doctor_charge | DECIMAL | Duty doctor per day |
| service_charge | DECIMAL | Service per day |
| total_bed_amount | DECIMAL | Sum of all components |
| status | ENUM | Available/Occupied/Reserved/Maintenance |
| patient_id | VARCHAR | Current patient (if occupied) |

---

### `ipd_clinical_records`
Clinical monitoring charts for ICU/specialized patients.

| Column | Type | Description |
|--------|------|-------------|
| record_id | INT PK | Primary key |
| patient_id | VARCHAR FK | Patient reference |
| admission_id | VARCHAR FK | Admission reference |
| chart_type | ENUM | dialysis_chart/oxygen_chart/ventilation_chart/blood_transfusion_chart |
| record_date | DATE | Date of record |
| chart_data | JSON | Array of chart entries (structure varies by type) |
| recorded_by | VARCHAR | Nurse who recorded |
| created_at | TIMESTAMP | Record time |

---

## 7. Billing Tables

### `opd_billing_master`
OPD bill header record.

| Column | Type | Description |
|--------|------|-------------|
| bill_id | VARCHAR PK | Bill identifier |
| patient_id | VARCHAR FK | Patient |
| name | VARCHAR | Patient name at time of bill |
| mobile | VARCHAR | Phone at time of bill |
| appointment_id | VARCHAR FK | Linked appointment |
| doctor_id | VARCHAR FK | Doctor |
| doctor_name | VARCHAR | Denormalized doctor name |
| bill_date | DATE | Date of bill |
| bill_time | TIME | Time of bill |
| referral_type | VARCHAR | Walk-in/Referral/etc. |
| referred_by | VARCHAR | Referring doctor |
| sponsor | VARCHAR | Insurance/corporate |
| discount_amount | DECIMAL | Flat discount |
| discount_percentage | DECIMAL | Percentage discount |
| service_id | VARCHAR | Primary service |
| item_name | VARCHAR | Primary service name |
| payment_mode | VARCHAR | Cash/Card/UPI/etc. |
| status | VARCHAR | Paid/Pending/Partial |
| receipt_no | VARCHAR | ORC number |
| created_by | VARCHAR | Who billed |
| created_at | TIMESTAMP | Bill creation time |

---

### `opd_billing_items`
OPD bill line items.

| Column | Type | Description |
|--------|------|-------------|
| id | INT PK | Primary key |
| bill_id | VARCHAR FK | Parent bill |
| service_id | VARCHAR | Service reference |
| item_name | VARCHAR | Service name |
| quantity | INT | Quantity |
| unit_price | DECIMAL | Price per unit |
| total | DECIMAL | Line total |
| category | VARCHAR | Service category |

---

### IPD Billing Tables
See [Database section in MOD_IPD.md](./modules/MOD_IPD.md#3-data-model-database) for detailed schemas of:
- `ipd_billing_master`
- `ipd_billing_items`
- `ipd_payment`
- `ipd_insurance`

---

## 8. Pharmacy Tables

### `pharmacy_products`
Medicine master catalog.

| Column | Type | Description |
|--------|------|-------------|
| product_id | INT PK | Auto-increment |
| product_name | VARCHAR | Full medicine name |
| generic_name | VARCHAR | Active ingredient |
| category | VARCHAR | Tablet/Capsule/Syrup/etc. |
| manufacturer | VARCHAR | Brand manufacturer |
| hsn_code | VARCHAR | GST classification |
| unit | VARCHAR | Unit of measure |
| pack_size | INT | Units per pack |
| reorder_level | INT | Low stock threshold |
| max_stock | INT | Maximum stock target |
| mrp | DECIMAL | Maximum retail price |
| rack_location | VARCHAR | Physical storage location |
| schedule | VARCHAR | Schedule H/H1/X/OTC |
| status | ENUM | Active/Inactive |
| created_at | TIMESTAMP | — |

---

### `pharmacy_inventory`
Batch-wise stock management (FIFO basis).

| Column | Type | Description |
|--------|------|-------------|
| inventory_id | INT PK | Auto-increment |
| product_id | INT FK | Links to pharmacy_products |
| grn_id | INT FK | GRN that created this batch |
| batch_number | VARCHAR | Manufacturer batch |
| manufacturing_date | DATE | Manufacturing date |
| expiry_date | DATE | IDX — Expiry for FIFO ordering |
| quantity_received | INT | Original quantity |
| quantity_available | INT | Current stock (decremented on sale) |
| purchase_price | DECIMAL | Cost price |
| mrp | DECIMAL | Retail price |
| selling_price | DECIMAL | Actual selling price |
| gst_rate | DECIMAL | GST % |
| created_at | TIMESTAMP | — |

**FIFO Query:**
```sql
SELECT * FROM pharmacy_inventory
WHERE product_id = ? AND quantity_available > 0
ORDER BY expiry_date ASC, inventory_id ASC
```

---

### `pharmacy_grn`
Goods Receipt Notes (stock received).

| Column | Type | Description |
|--------|------|-------------|
| grn_id | INT PK | Auto-increment |
| supplier_id | INT FK | Supplier reference |
| invoice_number | VARCHAR | Supplier invoice number |
| invoice_date | DATE | Invoice date |
| po_number | VARCHAR | Linked purchase order |
| total_amount | DECIMAL | Total invoice value |
| status | VARCHAR | Draft/Submitted/Verified |
| created_by | VARCHAR | Who received |
| created_at | TIMESTAMP | — |

---

### `pharmacy_grn_items`
Line items of a GRN.

| Column | Type | Description |
|--------|------|-------------|
| item_id | INT PK | Auto-increment |
| grn_id | INT FK | Parent GRN |
| product_id | INT FK | Medicine |
| batch_number | VARCHAR | Batch |
| expiry_date | DATE | Expiry |
| quantity | INT | Received quantity |
| free_quantity | INT | Bonus quantity |
| purchase_price | DECIMAL | Per unit cost |
| gst_rate | DECIMAL | GST % |
| mrp | DECIMAL | MRP |
| total | DECIMAL | Line total |

---

### `pharmacy_sales`
POS sale header.

| Column | Type | Description |
|--------|------|-------------|
| sale_id | INT PK | Auto-increment |
| patient_id | VARCHAR FK | Patient (if known) |
| sale_date | DATE | IDX |
| bill_number | VARCHAR | Invoice number |
| total_amount | DECIMAL | Gross total |
| discount | DECIMAL | Applied discount |
| net_amount | DECIMAL | Net payable |
| payment_mode | VARCHAR | Cash/Card/UPI |
| created_by | VARCHAR | Pharmacist |
| created_at | TIMESTAMP | — |

---

### `pharmacy_sale_items`
Line items of a pharmacy sale.

| Column | Type | Description |
|--------|------|-------------|
| item_id | INT PK | Auto-increment |
| sale_id | INT FK | Parent sale |
| product_id | INT FK | Medicine |
| inventory_id | INT FK | Specific batch used |
| batch_number | VARCHAR | Batch for this sale |
| quantity | INT | Sold |
| unit_price | DECIMAL | Selling price |
| total | DECIMAL | Line total |

---

### `pharmacy_indents`
Purchase request headers.

| Column | Type | Description |
|--------|------|-------------|
| indent_id | INT PK | Auto-increment |
| indent_date | DATE | Date created |
| status | VARCHAR | Draft/Dispatched/Sent/Ordered |
| notes | TEXT | Indent notes |
| created_by | VARCHAR | Who created |
| created_at | TIMESTAMP | — |

---

### `pharmacy_indent_items`
Line items of an indent.

| Column | Type | Description |
|--------|------|-------------|
| item_id | INT PK | Auto-increment |
| indent_id | INT FK | Parent indent |
| product_id | INT FK | Medicine needed |
| quantity_requested | INT | How much needed |
| quantity_ordered | INT | How much PO'd |
| quantity_received | INT | How much received |
| vendor_id | INT FK | Assigned vendor |
| status | VARCHAR | Draft/Quoted/Ordered/Received |

---

## 9. Laboratory Tables

### `lab_services`
Test catalog.

| Column | Type | Description |
|--------|------|-------------|
| service_id | VARCHAR PK | Test code |
| service_name | VARCHAR | Test name |
| category | VARCHAR | Test category |
| price | DECIMAL | In ₹ |
| tat_hours | INT | Turnaround time |
| sample_type | VARCHAR | Blood/Urine/etc. |
| parameters | JSON | Sub-parameters with normal ranges |
| collection_instructions | TEXT | Pre-test requirements |
| status | ENUM | Active/Inactive |

---

### `lab_orders`
OPD lab test orders.

| Column | Type | Description |
|--------|------|-------------|
| order_id | VARCHAR PK | Order reference |
| patient_id | VARCHAR FK | Patient |
| appointment_id | VARCHAR FK | Linked appointment |
| ordered_by | VARCHAR FK | Doctor who ordered |
| order_date | DATE | IDX |
| priority | ENUM | Routine/Urgent/STAT |
| status | ENUM | Pending/InProgress/Completed/Cancelled |
| test_details | JSON | Tests ordered with service_ids |
| collection_time | DATETIME | When sample collected |
| result_data | JSON | Results per parameter |
| remarks | TEXT | Lab tech remarks |
| reported_by | VARCHAR | Lab tech name |
| reported_at | TIMESTAMP | When results uploaded |

---

### `lab_ipd_orders`
IPD lab test orders (same structure as lab_orders with additional):

| Column | Type | Description |
|--------|------|-------------|
| admission_id | VARCHAR FK | IPD admission reference |
| ward | VARCHAR | Patient's ward |
| bed_number | VARCHAR | Patient's bed |

---

## 10. Staff & Shift Tables

### `staff`
Non-doctor hospital staff.

| Column | Type | Description |
|--------|------|-------------|
| id | INT PK | Auto-increment |
| full_name | VARCHAR | Staff name |
| role | VARCHAR | Nurse/Receptionist/Pharmacist/etc. |
| department_id | VARCHAR FK | Department |
| designation | VARCHAR | Job title |
| phone | VARCHAR | Contact |
| email | VARCHAR | Email |
| joining_date | DATE | Start date |
| qualification | VARCHAR | Educational qualification |
| address | TEXT | Home address |
| status | ENUM | Active/Inactive |
| created_at | TIMESTAMP | — |

---

### `nurse_shift_assignments`
Nurse shift scheduling records.

| Column | Type | Description |
|--------|------|-------------|
| id | INT PK | Auto-increment |
| nurse_id | INT FK | Staff reference (nurse) |
| ward_id | VARCHAR | Assigned ward |
| floor | VARCHAR | Floor |
| room_id | VARCHAR | Specific room (optional) |
| shift_type | ENUM | Morning/Evening/Night |
| shift_date | DATE | IDX |
| start_time | TIME | Shift start |
| end_time | TIME | Shift end |
| status | VARCHAR | Scheduled/Completed/Cancelled |
| notes | TEXT | Shift notes |
| created_by | VARCHAR | Who scheduled |
| created_at | TIMESTAMP | — |

---

## 11. Configuration Tables

### `departments`
Hospital departments.

| Column | Type | Description |
|--------|------|-------------|
| department_id | VARCHAR PK | DEPT-NNN |
| name | VARCHAR | Department name |
| code | VARCHAR | Short code |
| hod_doctor_id | VARCHAR FK | Head of department |
| description | TEXT | Department description |
| status | ENUM | Active/Inactive |

---

### `settings`
Hospital-wide configuration.

| Column | Type | Description |
|--------|------|-------------|
| id | INT PK | Auto-increment |
| setting_key | VARCHAR UNIQUE | Configuration key |
| setting_value | TEXT | Configuration value |
| category | VARCHAR | Category grouping |
| updated_at | TIMESTAMP | Last changed |
| updated_by | VARCHAR | Who changed |

**Common Keys:**
- `hospital_name` — Hospital display name
- `hospital_address` — Full address
- `gstin` — GST number
- `drug_license` — Pharmacy drug license
- `tax_rate` — Default GST rate
- `food_charge_per_day` — Daily food charge *(not yet used)*

---

## 12. Notification Tables

### `discharge_notifications`
Nurse-to-Admin discharge alerts.

| Column | Type | Description |
|--------|------|-------------|
| id | INT PK | Auto-increment |
| patient_id | VARCHAR | Patient reference |
| admission_id | VARCHAR | Admission reference |
| message | TEXT | Auto-generated message |
| status | ENUM | Pending / Cleared |
| created_at | TIMESTAMP | When notification sent |
| cleared_at | TIMESTAMP | When admin cleared |

---

## 13. Table Relationships (ER Narrative)

```
patient ─────────────────────────────────────────────────────────┐
  │                                                               │
  ├─── appointments ──── doctors                                 │
  │       │                                                       │
  │       └──── consultations ──── prescriptions                 │
  │                                                               │
  ├─── opd_billing_master ──── opd_billing_items                 │
  │                                                               │
  ├─── ipd_admissions ──── hospital_beds                         │
  │       │                │                                     │
  │       ├─── ipd_billing_master                                │
  │       │       ├─── ipd_billing_items                         │
  │       │       ├─── ipd_payment                               │
  │       │       └─── ipd_insurance                             │
  │       └─── ipd_clinical_records                              │
  │                                                               │
  ├─── lab_orders ──── lab_services                              │
  │                                                               │
  ├─── lab_ipd_orders ──── lab_services                          │
  │                                                               │
  └─── pharmacy_sales ──── pharmacy_sale_items                   │
                │                                                 │
                └── pharmacy_inventory ──── pharmacy_products ───┘

staff ──── nurse_shift_assignments
  │
  └── user (login account)

doctors ── user (login account)
```

---

## 14. CRUD Operations by Module

### Patient Module
| Operation | SQL Type | Tables |
|-----------|---------|--------|
| Register | INSERT | `patient` |
| List patients | SELECT | `patient` |
| View profile | SELECT | `patient`, `appointments`, `prescriptions` |
| Update info | UPDATE | `patient` |
| Soft delete | UPDATE (status) | `patient` |
| Check duplicate | SELECT | `patient` WHERE aadhar/phone |

### Appointment Module
| Operation | SQL Type | Tables |
|-----------|---------|--------|
| Book | INSERT | `appointments` |
| List | SELECT | `appointments`, `patient`, `doctors` |
| Update status | UPDATE | `appointments` |
| Cancel | UPDATE (status) | `appointments` |
| Check availability | SELECT | `appointments` COUNT by doctor+date |

### IPD Module
| Operation | SQL Type | Tables |
|-----------|---------|--------|
| Admit | INSERT + UPDATE | `ipd_admissions`, `hospital_beds` |
| Update admission | UPDATE | `ipd_admissions` |
| List admissions | SELECT | `ipd_admissions`, `patient`, `doctors`, `hospital_beds` |
| Add charge | INSERT | `ipd_billing_items` |
| Cancel charge | UPDATE | `ipd_billing_items` |
| Room rent generation | INSERT (bulk) | `ipd_billing_items` |
| Record payment | INSERT | `ipd_payment` |
| Recalculate | SELECT + UPDATE | `ipd_billing_items`, `ipd_payment`, `ipd_billing_master` |
| Discharge | UPDATE (multiple) | `ipd_admissions`, `hospital_beds`, `ipd_billing_master` |

### Pharmacy Module
| Operation | SQL Type | Tables |
|-----------|---------|--------|
| Add product | INSERT | `pharmacy_products` |
| Receive stock (GRN) | INSERT | `pharmacy_grn`, `pharmacy_grn_items`, `pharmacy_inventory` |
| POS Sale | INSERT + UPDATE | `pharmacy_sales`, `pharmacy_sale_items`, `pharmacy_inventory` |
| Create indent | INSERT | `pharmacy_indents`, `pharmacy_indent_items` |
| Patient return | INSERT + UPDATE | `pharmacy_returns`, `pharmacy_inventory` |

### Laboratory Module
| Operation | SQL Type | Tables |
|-----------|---------|--------|
| Create order | INSERT | `lab_orders` |
| Update status | UPDATE | `lab_orders` |
| Upload result | UPDATE | `lab_orders` (result_data JSON) |
| Manage services | INSERT/UPDATE | `lab_services` |

---

*End of Document — Database Documentation*

---
**Document Control** | Version 2.0 | August 2026
