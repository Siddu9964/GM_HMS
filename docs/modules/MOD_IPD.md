# GM_HMS — IPD Module Documentation

---

> **Document Type:** Module Reference  
> **Module:** In-Patient Department (IPD)  
> **Version:** 2.0.0  
> **Audience:** All Stakeholders  
> **Date:** August 2026

---

## Table of Contents
1. [Module Overview](#1-module-overview)
2. [Business Purpose](#2-business-purpose)
3. [Data Model (Database)](#3-data-model-database)
4. [Complete Patient Journey](#4-complete-patient-journey)
5. [Admission Module](#5-admission-module)
6. [Bed Management Module](#6-bed-management-module)
7. [IPD Billing Engine — Complete Reference](#7-ipd-billing-engine--complete-reference)
8. [IPD Payment System](#8-ipd-payment-system)
9. [Insurance Module](#9-insurance-module)
10. [Discharge Module](#10-discharge-module)
11. [Nursing Clinical Records](#11-nursing-clinical-records)
12. [IPD Discharge Summary](#12-ipd-discharge-summary)
13. [Functions Documentation](#13-functions-documentation)
14. [API Reference](#14-api-reference)
15. [Validation Rules](#15-validation-rules)
16. [Error Scenarios](#16-error-scenarios)
17. [Module Flowchart](#17-module-flowchart)

---

## 1. Module Overview

The **IPD (In-Patient Department)** module is the most complex and business-critical module in GM_HMS. It manages the entire lifecycle of a hospitalized patient — from the moment of admission through daily clinical care, billing, and final discharge.

The module contains:
- **Sub-Application MVC** (`reception_view/ipd_management/`) for receptionist
- **Nurse interfaces** (`nurse_view/`) for clinical care
- **API Layer** (40+ endpoints)
- **Billing Engine** (automated daily charge calculation)
- **Integration points** with Pharmacy, Laboratory, OT, and Nursing modules

---

## 2. Business Purpose

| Purpose | Description |
|---------|-------------|
| Patient Hospitalization | Formal record of hospital stay |
| Bed Resource Management | Real-time allocation and release |
| Financial Tracking | Accurate daily charge accumulation |
| Clinical Documentation | Nursing and doctor records |
| Insurance Processing | TPA/corporate billing support |
| Discharge Coordination | Multi-team notification system |

---

## 3. Data Model (Database)

### Core Tables

#### `ipd_admissions`
| Column | Type | Description |
|--------|------|-------------|
| sl_no | INT PK | System primary key |
| admission_id | VARCHAR | Display ID (ADM-YYYYMMDD-NNN) |
| patient_id | VARCHAR FK | References `patient.patient_id` |
| admitting_doctor_id | VARCHAR FK | References `doctors.doctor_id` |
| bed_id | INT FK | References `hospital_beds.sl_no` |
| admission_date | DATE | Date of admission |
| admission_time | TIME | Time of admission |
| discharge_date | DATE | Set on discharge |
| status | ENUM | Admitted / Discharged / LAMA / Expired |
| diagnosis | TEXT | Initial/final diagnosis |
| reason_for_admission | TEXT | Chief complaint |
| bill_type | ENUM | SELF / INSURANCE / CORPORATE |
| credit_type | VARCHAR | Insurance/corporate name |
| sponsor | VARCHAR | Insurance company or employer |
| referred_by | VARCHAR | Referring doctor |
| created_by | VARCHAR | Who created the record |
| created_at | TIMESTAMP | Record creation time |

#### `hospital_beds`
| Column | Type | Description |
|--------|------|-------------|
| sl_no | INT PK | Primary key |
| bed_number | VARCHAR | Bed display number |
| ward_name | VARCHAR | Ward name |
| room_name | VARCHAR | Room name |
| room_type | ENUM | General / Semi-Private / Private / ICU |
| room_category | VARCHAR | Additional classification |
| amount_per_day | DECIMAL | Base bed rental |
| nursig_charge | DECIMAL | Nursing charge per day |
| doctor_charge | DECIMAL | Duty doctor per day |
| service_charge | DECIMAL | Service charge per day |
| total_bed_amount | DECIMAL | Sum of all charges per day |
| status | ENUM | Available / Occupied / Reserved / Maintenance |
| patient_id | VARCHAR | Current patient (if occupied) |

#### `ipd_billing_master`
| Column | Type | Description |
|--------|------|-------------|
| bill_id | VARCHAR PK | BILL-YYYYMMDD-NNNN |
| admission_id | VARCHAR FK | Linked admission |
| patient_id | VARCHAR FK | Patient reference |
| bill_date | DATE | Bill creation date |
| billing_status | ENUM | OPEN / FINALIZED / CANCELLED |
| payment_status | ENUM | Pending / Partial / Paid |
| room_charges | DECIMAL | Sum of ROOM_RENT items |
| doctor_charges | DECIMAL | Sum of DOCTOR_VISIT items |
| lab_charges | DECIMAL | Sum of LAB items |
| radiology_charges | DECIMAL | Sum of RADIOLOGY items |
| pharmacy_charges | DECIMAL | Sum of PHARMACY items |
| ot_charges | DECIMAL | Sum of OT items |
| procedure_charges | DECIMAL | Sum of PROCEDURE items |
| consumable_charges | DECIMAL | Sum of CONSUMABLE items |
| other_charges | DECIMAL | Sum of OTHER/MISC items |
| subtotal | DECIMAL | Sum of all categories |
| discount_amount | DECIMAL | Applied discount |
| discount_percentage | DECIMAL | If % discount |
| discount_reason | TEXT | Reason for discount |
| grand_total | DECIMAL | subtotal - discount |
| insurance_approved_amount | DECIMAL | Approved by TPA |
| insurance_received | DECIMAL | Amount paid by insurer |
| patient_payable | DECIMAL | grand_total - insurance_approved |
| amount_paid | DECIMAL | Sum of patient payments |
| balance_due | DECIMAL | Remaining to pay |
| total_days | INT | Admission days count |
| discharge_date | DATE | Set on discharge |
| bill_type | ENUM | SELF / INSURANCE / CORPORATE |
| updated_at | TIMESTAMP | Last recalculation time |

#### `ipd_billing_items`
| Column | Type | Description |
|--------|------|-------------|
| item_id | INT PK | Auto-increment |
| bill_id | VARCHAR FK | Parent bill |
| admission_id | VARCHAR | Admission reference |
| patient_id | VARCHAR | Patient reference |
| charge_type | VARCHAR | ROOM_RENT/LAB/PHARMACY/etc. |
| charge_date | DATE | Date this charge applies to |
| description | TEXT | Description (may contain HTML) |
| unit_price | DECIMAL | Price per unit |
| quantity | INT | Quantity |
| total | DECIMAL | unit_price × quantity |
| status | ENUM | ACTIVE / CANCELLED |
| items_json | JSON | Sub-items for grouped charges |
| created_by | VARCHAR | Creator |
| created_at | TIMESTAMP | Creation time |
| updated_at | TIMESTAMP | Last update |

#### `ipd_payment`
| Column | Type | Description |
|--------|------|-------------|
| payment_id | INT PK | Auto-increment |
| bill_id | VARCHAR FK | Parent bill |
| admission_id | VARCHAR | Admission reference |
| patient_id | VARCHAR | Patient reference |
| payment_type | ENUM | ADVANCE / FINAL / REFUND |
| payment_mode | VARCHAR | CASH/CARD/UPI/NEFT/CHEQUE/INSURANCE |
| amount | DECIMAL | Amount of this transaction |
| reference_no | VARCHAR | Transaction reference |
| remarks | TEXT | Notes |
| refund_reason | TEXT | Required for REFUND type |
| approved_by | VARCHAR | Required for REFUND type |
| payment_date | DATETIME | When recorded |
| created_by | VARCHAR | Who recorded |

#### `ipd_insurance`
| Column | Type | Description |
|--------|------|-------------|
| insurance_id | INT PK | Auto-increment |
| bill_id | VARCHAR FK | Parent bill |
| patient_id | VARCHAR | Patient reference |
| insurance_company | VARCHAR | Company name |
| policy_number | VARCHAR | Policy number |
| approval_number | VARCHAR | Pre-auth/TPA approval number |
| approved_amount | DECIMAL | Approved coverage amount |
| received_amount | DECIMAL | Actually received from insurer |
| status | VARCHAR | Pending / Approved / Rejected |
| notes | TEXT | Additional notes |

### Table Relationships
```
ipd_admissions
  ├── → patient (via patient_id)
  ├── → doctors (via admitting_doctor_id)
  ├── → hospital_beds (via bed_id)
  └── ← ipd_billing_master (via admission_id)
        └── ← ipd_billing_items (via bill_id)
        └── ← ipd_payment (via bill_id)
        └── ← ipd_insurance (via bill_id)
```

---

## 4. Complete Patient Journey

```
DOCTOR: Patient needs admission
        │
        ▼
RECEPTION: Search patient in system
        │
        ├── If not registered: Register patient first
        └── If registered: Select patient
        │
        ▼
RECEPTION: Create Admission
        ├── Select bed (available beds shown)
        ├── Enter diagnosis, reason
        ├── Set bill type (SELF/INSURANCE/CORPORATE)
        └── Record initial advance payment (optional)
        │
        ▼
SYSTEM: Automatic processes
        ├── admission_id generated (ADM-YYYYMMDD-NNN)
        ├── Bed status → Occupied
        ├── ipd_billing_master created (BILL-YYYYMMDD-NNNN)
        └── Daily room rent cycle begins
        │
        ▼
DURING HOSPITALIZATION (Days 1–N):
        │
        ├── NURSING (every shift):
        │    ├── Record vitals → K-Sheet
        │    ├── Administer medications → MAR
        │    └── Update clinical charts
        │
        ├── DAILY (auto):
        │    └── Room rent + food charge generated per day
        │
        ├── AS NEEDED:
        │    ├── Doctor visits → charged to bill
        │    ├── Lab tests → charged to bill
        │    ├── Medicines dispensed → charged to bill
        │    ├── Procedures/OT → charged to bill
        │    └── Advance payments collected
        │
        ▼
DOCTOR: Discharge decision
        │
        ▼
NURSE: Send discharge notification → Admin
        │
        ▼
RECEPTION/BILLING: Review and finalize bill
        ├── Verify all charges
        ├── Apply final discount (if any)
        ├── Process final payment / insurance claim
        └── Balance = 0
        │
        ▼
DOCTOR: Complete discharge summary
        │
        ▼
SYSTEM: Discharge processes
        ├── ipd_admissions.status → Discharged
        ├── ipd_admissions.discharge_date → today
        ├── ipd_billing_master.billing_status → FINALIZED
        └── hospital_beds.status → Available
        │
        ▼
PATIENT DEPARTS
        ├── Print: Discharge bill
        ├── Print: Payment receipt
        └── Print: Discharge summary (if completed)
```

---

## 5. Admission Module

### Creating an Admission

**File:** `reception_view/ipd_management/models/Admission.php`  
**Method:** `createAdmission($data)`

#### Logic Flow
```php
createAdmission($data):
  1. validateRequired(['patient_id', 'bed_id', 'admitting_doctor_id', 
                       'admission_date', 'reason_for_admission'])
  2. Check bed availability: bed.status must be 'Available'
  3. Generate admission_id: ADM-YYYYMMDD-NNN
     - Count existing admissions today → sequence
  4. Begin DB transaction
  5. INSERT into ipd_admissions
  6. UPDATE hospital_beds SET status='Occupied', patient_id=patient_id
  7. IF advance_amount > 0:
     - Create billing master first
     - Record advance payment
  8. Commit transaction
  9. Return {success: true, admission_id: 'ADM-...'}
```

#### Admission ID Generation
```php
function generateAdmissionId():
  $date = date('Ymd');  // e.g., 20260814
  $count = COUNT(ipd_admissions WHERE admission_id LIKE 'ADM-{date}-%');
  $seq = str_pad($count + 1, 3, '0', STR_PAD_LEFT);  // 001, 002...
  return "ADM-{$date}-{$seq}";  // ADM-20260814-001
```

### Updating an Admission

**Method:** `updateAdmission($slNo, $data)`

Editable fields:
- Diagnosis
- Doctor (can change admitting doctor)
- Bed (transfer to different bed)
- Reason for admission
- Bill type / Sponsor

Non-editable after creation:
- Patient ID
- Admission date
- Admission ID

### Discharge a Patient

**Method:** `dischargePatient($admissionId, $data)`

```php
dischargePatient():
  1. Fetch current admission record
  2. Validate: status must be 'Admitted' (not already discharged)
  3. Validate: billing_status = 'FINALIZED' (bill must be settled)
  4. Begin transaction
  5. UPDATE ipd_admissions SET 
        status = 'Discharged',
        discharge_date = $data['discharge_date'] ?? date('Y-m-d')
  6. UPDATE hospital_beds SET 
        status = 'Available',
        patient_id = NULL
  7. UPDATE ipd_billing_master SET discharge_date = today
  8. Commit
  9. Return {success: true}
```

---

## 6. Bed Management Module

### Bed Availability Check

**Method:** `Bed::getAvailableBeds($filters)`

```php
getAvailableBeds($ward=null, $room_type=null):
  SELECT hb.*, p.first_name, p.last_name
  FROM hospital_beds hb
  LEFT JOIN patient p ON hb.patient_id = p.patient_id
  WHERE hb.status = 'Available'
    AND (ward_name = $ward OR $ward IS NULL)
    AND (room_type = $room_type OR $room_type IS NULL)
  ORDER BY ward_name, room_name, bed_number
```

### Bed Pricing
```
total_bed_amount = amount_per_day 
                 + nursig_charge 
                 + doctor_charge 
                 + service_charge

Example for Private Room:
  amount_per_day: ₹2,000
  nursig_charge:  ₹500
  doctor_charge:  ₹500
  service_charge: ₹200
  ─────────────────────
  total_bed_amount: ₹3,200 per day
  + food: ₹570 per day (fixed)
  = ₹3,770 billed per day
```

---

## 7. IPD Billing Engine — Complete Reference

### Architecture Overview

```
IpdBillingMaster (financial hub)
  ├── getOrCreateForAdmission()  → Create/retrieve bill master
  ├── catchUpBedCharges()        → Auto-generate daily room rent
  ├── recalculateMaster()        → THE CORE ENGINE
  ├── applyDiscount()            → Discount management
  ├── updateBillingStatus()      → Status management
  └── getFullDetails()           → Full bill with recalculation

IpdBillingItem (charge line items)
  ├── addItem()                  → Add any charge
  ├── generateRoomRent()         → Bulk day-by-day room rent
  ├── previewRoomRent()          → Preview without saving
  ├── cancelItem()               → Cancel (not delete)
  └── getByBill()                → All items for a bill
```

### The Core Engine: `recalculateMaster()`

This method is the **single source of financial truth**. It is called automatically whenever:
- A new billing item is added
- An item is cancelled
- A payment is recorded
- A discount is applied
- The billing page is opened

```php
recalculateMaster($billId):

  Step 1: catchUpBedCharges($billId)
    // Ensure all day's room rent are generated

  Step 2: Sum items by category
    SELECT charge_type, SUM(total) as category_total
    FROM ipd_billing_items
    WHERE bill_id = $billId AND status = 'ACTIVE'
    GROUP BY charge_type

    $totals = [
      'ROOM_RENT'  → room_charges,
      'DOCTOR_VISIT' → doctor_charges,
      'LAB'        → lab_charges,
      'RADIOLOGY'  → radiology_charges,
      'PHARMACY'   → pharmacy_charges,
      'OT'         → ot_charges,
      'PROCEDURE'  → procedure_charges,
      'CONSUMABLE' → consumable_charges,
      'OTHER'      → other_charges,
    ]

  Step 3: Calculate subtotal
    $subtotal = SUM of all category totals

  Step 4: Apply discount
    $discountAmt = max(master.discount_amount, 
                       subtotal * master.discount_percentage / 100)

  Step 5: Calculate grand_total
    $grandTotal = $subtotal - $discountAmt

  Step 6: Get insurance details
    SELECT approved_amount, received_amount FROM ipd_insurance
    WHERE bill_id = $billId

  Step 7: Patient payable
    $patientPayable = $grandTotal - $insuranceApproved

  Step 8: Sum payments
    SELECT SUM(amount) FROM ipd_payment
    WHERE bill_id = $billId 
      AND payment_mode != 'INSURANCE'
      AND payment_type != 'REFUND'
    → $amountPaid

    SELECT SUM(amount) FROM ipd_payment
    WHERE bill_id = $billId AND payment_mode = 'INSURANCE'
    → $insuranceReceived
    
    SELECT SUM(amount) FROM ipd_payment
    WHERE bill_id = $billId AND payment_type = 'REFUND'
    → $totalRefunds

  Step 9: Balance due
    $netPaid = $amountPaid - $totalRefunds
    $balanceDue = $grandTotal - $netPaid - $insuranceReceived

  Step 10: Payment status
    IF $balanceDue <= 0: payment_status = 'Paid'
    ELIF $netPaid > 0 OR $insuranceReceived > 0: payment_status = 'Partial'
    ELSE: payment_status = 'Pending'

  Step 11: UPDATE ipd_billing_master
    SET room_charges = ?, doctor_charges = ?, ...,
        subtotal = ?, discount_amount = ?, grand_total = ?,
        patient_payable = ?, amount_paid = ?, 
        insurance_received = ?, balance_due = ?,
        payment_status = ?, updated_at = NOW()
    WHERE bill_id = $billId

  RETURN updated master record
```

### Auto Bed Charge: `catchUpBedCharges()`

```php
catchUpBedCharges($billId):

  Step 1: Get admission details
    SELECT ia.admission_date, ia.discharge_date, 
           hb.total_bed_amount, hb.ward_name, hb.bed_number
    FROM ipd_billing_master ibm
    JOIN ipd_admissions ia USING(admission_id)
    JOIN hospital_beds hb ON ia.bed_id = hb.sl_no
    WHERE ibm.bill_id = $billId

  Step 2: Calculate expected periods
    $endDate = discharge_date ?? today
    $hoursDiff = hours_between(admission_date, $endDate)
    $expectedPeriods = ceil($hoursDiff / 24)
    $expectedPeriods = max(1, min($expectedPeriods, 365))  // Safety cap

  Step 3: Count existing ROOM_RENT entries
    $existingCount = COUNT(ipd_billing_items)
    WHERE bill_id = $billId AND charge_type = 'ROOM_RENT'

  Step 4: Add missing entries
    $foodPrice = 570.00  // HARDCODED (known issue — should come from settings)
    
    FOR $day FROM existingCount TO expectedPeriods:
      $dayDate = admission_date + ($day - 1) days
      $dayTotal = $totalBedAmount + $foodPrice
      
      CHECK: Does ROOM_RENT entry exist for this $dayDate?
      → If yes: skip
      → If no: INSERT ipd_billing_items:
           charge_type = 'ROOM_RENT'
           charge_date = $dayDate
           description = "Room Rent ({ward} - {bed}) + Food"
           unit_price = $dayTotal
           quantity = 1
           total = $dayTotal
           status = 'ACTIVE'
```

---

## 8. IPD Payment System

### Payment Recording: `IpdPayment::recordPayment()`

```php
recordPayment($data):
  validateRequired(['bill_id', 'patient_id', 'payment_type', 
                    'payment_mode', 'amount'])
  
  IF payment_type == 'REFUND':
    validateRequired(['refund_reason', 'approved_by'])
  
  IF amount <= 0:
    throw Exception("Amount must be positive")
  
  INSERT INTO ipd_payment (bill_id, admission_id, patient_id,
    payment_type, payment_mode, amount, reference_no,
    remarks, refund_reason, approved_by, payment_date,
    created_by)
  
  // IMPORTANT: Payments are ALWAYS INSERT — never UPDATE existing records
  // This ensures complete audit trail
  
  IpdBillingMaster::recalculateMaster($billId)
  
  RETURN {success: true, payment_id: lastInsertId}
```

### Payment Summary: `IpdPayment::getSummary()`

```php
getSummary($billId):
  SELECT 
    SUM(CASE WHEN payment_mode != 'INSURANCE' AND payment_type != 'REFUND' 
        THEN amount ELSE 0 END) as total_cash_payments,
    SUM(CASE WHEN payment_mode = 'INSURANCE' THEN amount ELSE 0 END) as insurance_payments,
    SUM(CASE WHEN payment_type = 'REFUND' THEN amount ELSE 0 END) as total_refunds,
    SUM(CASE WHEN payment_type = 'ADVANCE' THEN amount ELSE 0 END) as total_advances,
    SUM(CASE WHEN payment_type = 'FINAL' THEN amount ELSE 0 END) as total_final,
    COUNT(*) as transaction_count
  FROM ipd_payment WHERE bill_id = ?
```

---

## 9. Insurance Module

### Insurance Data: `IpdInsurance`

For INSURANCE or CORPORATE bill types:

```php
createOrUpdate($data):
  1. Check if insurance record exists for bill_id
  2. If exists: UPDATE
  3. If not: INSERT
  
Fields:
  insurance_company: Name of insurer/TPA
  policy_number: Patient's policy
  approval_number: Pre-authorization number
  approved_amount: Coverage amount approved
  received_amount: Actual amount received (updated per payment)
```

---

## 10. Discharge Module

### Discharge Validation

```php
dischargePatient($admissionId, $data):
  1. Fetch admission → must exist and status = 'Admitted'
  2. Fetch billing master → billing_status must be 'FINALIZED'
  3. Fetch payment → balance_due must be <= 0 (or grace approval)
  4. All validations pass?
     → Begin transaction
     → Update admissions, beds, billing
     → Commit
  5. Return success
```

### What Happens on Discharge

| Table | Column | Before | After |
|-------|--------|--------|-------|
| `ipd_admissions` | `status` | Admitted | Discharged |
| `ipd_admissions` | `discharge_date` | NULL | today |
| `hospital_beds` | `status` | Occupied | Available |
| `hospital_beds` | `patient_id` | PID-... | NULL |
| `ipd_billing_master` | `billing_status` | OPEN | FINALIZED |
| `ipd_billing_master` | `discharge_date` | NULL | today |

---

## 11. Nursing Clinical Records

### Database Table: `ipd_clinical_records`

| Column | Description |
|--------|-------------|
| record_id | Primary key |
| patient_id | Patient reference |
| admission_id | Admission reference |
| chart_type | dialysis_chart / oxygen_chart / ventilation_chart / blood_transfusion_chart |
| record_date | Date of record |
| chart_data | JSON array of chart entries |
| recorded_by | Nurse name |
| created_at | Timestamp |

---

## 12. IPD Discharge Summary

### Database Table: `ipd_summary` (or `ipd_summaries`)

| Column | Description |
|--------|-------------|
| summary_id | Primary key |
| patient_id | Patient reference |
| admission_id | Admission reference |
| chief_complaint | Presenting symptoms |
| history_of_illness | Detailed illness history |
| diagnosis | Final diagnosis |
| treatment | Treatment given |
| operative_procedure | If surgery performed |
| condition_at_discharge | Clinical condition on discharge |
| discharge_date | Date of discharge |
| discharge_instructions | Patient instructions |
| follow_up_date | Next appointment |
| status | Draft / Final |
| prepared_by | Doctor name |

---

## 13. Functions Documentation

### `IpdBillingMaster::generateBillId()`
- **File:** `models/IpdBillingMaster.php`
- **Purpose:** Generate unique bill ID
- **Logic:** `BILL-YYYYMMDD-` + COUNT(today's bills + 1) padded to 4 digits
- **Returns:** String like `BILL-20260814-0001`

### `IpdBillingItem::addItem($data)`
- **File:** `models/IpdBillingItem.php`
- **Purpose:** Add a single billing line item
- **Parameters:** bill_id, admission_id, patient_id, charge_type, charge_date, description, unit_price, quantity, created_by
- **Side Effects:** Calls `recalculateMaster()` after insert
- **Returns:** Array with item_id and updated financial summary

### `IpdBillingItem::generateRoomRent($billId, $admissionId, $patientId, $fromDate, $toDate, $createdBy)`
- **File:** `models/IpdBillingItem.php`
- **Purpose:** Generate room rent entries for a date range
- **Returns:** Count of new entries added

### `IpdBillingItem::cancelItem($itemId, $updatedBy)`
- **File:** `models/IpdBillingItem.php`
- **Purpose:** Mark item as CANCELLED
- **Note:** Never physically deletes — only status change
- **Side Effects:** Calls `recalculateMaster()`

### `Admission::getAllWithDetails($filters, $limit, $offset)`
- **File:** `reception_view/ipd_management/models/Admission.php`
- **Purpose:** Get all admissions with patient, doctor, bed details
- **Joins:** patient, doctors, hospital_beds
- **Calculated:** DATEDIFF for days_admitted

---

## 14. API Reference

See [03_API_COMPLETE_REFERENCE.md](../03_API_COMPLETE_REFERENCE.md) for full API details.

### Quick Reference
| Endpoint | Method | Purpose |
|----------|--------|---------|
| `/api/ipd-billing-master` | GET/POST/PUT | Bill master CRUD |
| `/api/ipd-billing-items` | GET/POST/DELETE | Charge items |
| `/api/ipd-payment` | GET/POST | Payment recording |
| `/api/ipd-insurance` | GET/POST/PUT | Insurance details |
| `/api/ipd-summary` | GET/POST/DELETE | Discharge summary |
| `/api/ipd-clinical/*` | GET/POST | Clinical records |
| `ipd_management/api/admissions` | CRUD | Admission management |
| `ipd_management/api/beds` | CRUD | Bed management |

---

## 15. Validation Rules

| Field | Rule |
|-------|------|
| patient_id | Must exist in patient table |
| bed_id | Must be status='Available' |
| admission_date | Cannot be future date |
| discharge_date | Must be >= admission_date |
| amount (payment) | Must be > 0 |
| refund_reason | Required for REFUND type |
| approved_by | Required for REFUND type |
| discount | Cannot exceed grand_total |

---

## 16. Error Scenarios

| Scenario | Handling |
|----------|---------|
| Bed already occupied | HTTP 400: "Bed is not available" |
| Patient already admitted | HTTP 400: "Patient has an active admission" |
| Bill not finalized for discharge | HTTP 400: "Please settle the bill before discharge" |
| Payment > balance_due | Warning shown, allowed with override |
| Cancelling last room rent item | Warning: "At least one room rent entry must remain" |
| Admitting to non-existent bed | HTTP 404: "Bed not found" |

---

## 17. Module Flowchart

```
                    ┌──────────────────────┐
                    │   ADMISSION CREATED   │
                    │   ADM-YYYYMMDD-NNN   │
                    └──────────┬───────────┘
                               │
                    ┌──────────▼───────────┐
                    │   BED ALLOCATED       │
                    │   Status: Occupied    │
                    └──────────┬───────────┘
                               │
                    ┌──────────▼───────────┐
                    │  BILL MASTER CREATED  │
                    │  BILL-YYYYMMDD-NNNN  │
                    │  Status: OPEN        │
                    └──────────┬───────────┘
                               │
            ┌──────────────────┼──────────────────┐
            │                  │                  │
            ▼                  ▼                  ▼
    ┌──────────────┐  ┌──────────────┐  ┌──────────────┐
    │ DAILY        │  │ ON-DEMAND    │  │ PAYMENTS     │
    │ ROOM RENT    │  │ CHARGES      │  │ COLLECTED    │
    │ Auto-gen     │  │ Lab/Pharmacy │  │ Advance/Final│
    │ ₹X + ₹570   │  │ /OT/Doctor   │  │              │
    └──────┬───────┘  └──────┬───────┘  └──────┬───────┘
           │                 │                  │
           └────────────┬────┘                  │
                        │                       │
            ┌───────────▼───────────┐           │
            │  recalculateMaster()  │◄──────────┘
            │  Updates all totals   │
            └───────────┬───────────┘
                        │
            ┌───────────▼───────────┐
            │  DISCHARGE READINESS  │
            │  Nurse → Notification │
            │  Review Bill          │
            │  Final Payment        │
            └───────────┬───────────┘
                        │
            ┌───────────▼───────────┐
            │  DISCHARGE PATIENT    │
            │  Status: Discharged   │
            │  Bill: FINALIZED      │
            │  Bed: Available       │
            └───────────────────────┘
```

---

*End of Document — IPD Module Documentation*

---
**Document Control** | Version 2.0 | August 2026
