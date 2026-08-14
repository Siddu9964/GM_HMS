# GM_HMS — Reception Role Documentation

---

> **Document Type:** Role-Based User Guide  
> **Role:** Receptionist  
> **Version:** 2.0.0  
> **Audience:** Management · Technical Team · Training  
> **Date:** August 2026

---

## Table of Contents
1. [Role Overview](#1-role-overview)
2. [Login & Session](#2-login--session)
3. [Reception Dashboard](#3-reception-dashboard)
4. [Patient Registration](#4-patient-registration)
5. [Patient Profile](#5-patient-profile)
6. [Appointment Management](#6-appointment-management)
7. [OPD Management](#7-opd-management)
8. [OPD Billing](#8-opd-billing)
9. [Doctor Availability](#9-doctor-availability)
10. [IPD Management (Full Sub-System)](#10-ipd-management-full-sub-system)
    - [IPD Admissions](#101-ipd-admissions)
    - [Bed Management](#102-bed-management)
    - [IPD Billing Engine](#103-ipd-billing-engine)
    - [IPD Payments](#104-ipd-payments)
    - [IPD Discharge](#105-ipd-discharge)
    - [Procedures & Visitors](#106-procedures--visitors)
11. [Print Operations](#11-print-operations)
12. [Security & Permissions](#12-security--permissions)
13. [Complete Workflow Flowchart](#13-complete-workflow-flowchart)

---

## 1. Role Overview

The **Receptionist** is the primary front-desk operator who manages the complete patient journey from arrival to billing. This role interacts with the most screens and processes in GM_HMS.

### Key Responsibilities
- Register new patients and update existing patient information
- Book and manage OPD appointments with token allocation
- Manage IPD admissions, bed allocation, and discharge
- Generate and collect OPD and IPD bills
- Coordinate with doctors for availability schedules
- Process advance payments and final settlements

### Access Level
```
FULL ACCESS: Patient Management, Appointments, OPD, IPD, Billing
READ ONLY: Prescriptions (view only)
NO ACCESS: Doctor management, Staff management, System config
```

---

## 2. Login & Session

**Login URL:** `http://localhost/GM_HMS/login.php`  
**Role Code:** `receptionist`  
**Redirect After Login:** `/GM_HMS/reception_view/index.php`

---

## 3. Reception Dashboard

**File:** `reception_view/index.php`  
**APIs Called:**
- `GET /api/reception/dashboard/summary`
- `GET /api/reception/dashboard/today-appointments`
- `GET /api/reception/dashboard/recent-patients`
- `GET /api/reception/dashboard/kpis` (via `api/get_dashboard_kpis.php`)

### Dashboard Sections

#### Today's Summary KPIs
| KPI | Description |
|-----|-------------|
| Today's Appointments | Count of today's scheduled OPD |
| Active IPD Admissions | Currently admitted patients |
| Available Beds | Beds ready for admission |
| Pending Bills | OPD bills awaiting payment |

#### Today's Appointment Table
- Shows all today's appointments
- Columns: Token | Patient Name | Doctor | Time | Status | Actions
- Quick actions: Mark Arrived | Mark Completed | Cancel

#### Recent Patient Registrations
- Last 10 patients registered
- Quick links to patient profiles

#### Quick Action Buttons
- ➕ Register New Patient
- 📅 Book Appointment
- 🛏 New IPD Admission
- 💰 New OPD Bill

---

## 4. Patient Registration

**File:** `reception_view/patient_registration.php`  
**APIs Used:**
- `GET /api/patients` — search existing
- `POST /api/patients` — register new
- `PUT /api/patients/{id}` — edit patient
- `GET /api/patients/check-duplicate` — Aadhar/phone check

### Registration Workflow

```
Step 1: Check for existing patient
        Search by name / phone / Aadhar
        ↓ If found → Open Edit Mode
        ↓ If not found → Open New Registration

Step 2: Fill registration form
        Mandatory: First Name, Age, Gender, Phone
        Optional: Last Name, Aadhar, Email, Address, Blood Group

Step 3: Duplicate Check (auto-triggered)
        On Aadhar/phone field exit:
        → API: GET /api/patients/check-duplicate
        → If duplicate: Show warning with existing patient link

Step 4: Submit
        → POST /api/patients
        → Patient ID generated: PID-YYYYMMDD-NNN
        → Success message shown

Step 5: Options after registration
        → Book Appointment
        → Register for IPD Admission
        → View Patient Profile
```

### Form Field Validations
| Field | Rule |
|-------|------|
| First Name | Required, min 2 chars, letters only |
| Age | Required, 0–120 |
| Gender | Required, select |
| Phone | Required, exactly 10 digits, numeric |
| Aadhar | Optional, 12 digits, unique check |
| Email | Optional, valid format |
| Photo | Optional, JPG/PNG, max 2MB |

---

## 5. Patient Profile

**File:** `reception_view/patient_profile.php`  
**Query:** `?patient_id=PID-20260814-001`  
**APIs:**
- `GET /api/patients/{id}` — patient details
- `GET /api/prescriptions/receptionist/view/{patient_id}` — prescriptions
- `GET /api/appointments?patient_id=` — appointment history

### Profile Sections

#### 1. Patient Information Card
- Photo, name, age, gender, blood group
- Contact details (phone, email, address)
- Aadhar number
- Registration date

#### 2. Medical History Tab
- Past appointments list
- Diagnoses from consultations
- Lab results (if any)

#### 3. Prescriptions Tab (Read Only)
- All prescriptions issued
- View details per prescription
- Print option

#### 4. Billing History Tab
- OPD bill history
- IPD bill history if admitted before
- Payment status for each

#### 5. Quick Actions
- Edit Patient Info
- Book Appointment
- Admit to IPD
- Generate OPD Bill

---

## 6. Appointment Management

**File:** `reception_view/appointment_management.php`  
**APIs Used:**
- `GET /api/appointments` — list appointments
- `POST /api/appointments` — book new
- `PUT /api/appointments/{id}` — update status
- `DELETE /api/appointments/{id}` — cancel
- `GET /api/appointments/check-availability` — availability check
- `GET /api/appointments/doctors` — doctor list
- `GET /api/appointments/departments` — department list

### Appointment Booking Workflow

```
Step 1: Select Patient
        Search by name/ID/phone → Select2 dropdown
        API: reception_view/api/search_appointment_patient.php

Step 2: Select Doctor
        Filter by department → Select doctor
        Auto-fill: Consultation fee

Step 3: Check Availability
        Select date → API: GET /api/appointments/check-availability
        Show: Doctor available / On leave
        Show: Current booking count for that date

Step 4: Fill Appointment Details
        ├── Date & Time
        ├── Appointment Type: OPD / Emergency / Walk-in
        ├── Reason for visit
        ├── Payment mode (if paid appointment)
        └── Discount (if any)

Step 5: Submit
        → POST /api/appointments
        → Token number auto-generated
        → Appointment ID: APT-YYYYMMDD-XXXX

Step 6: Print Token / Receipt (optional)
```

### Token Number Generation
```
Logic: Count appointments for same doctor on same date
Token = count + 1
Example: 3rd patient today → Token 3
```

### Appointment Status Values
| Status | Meaning |
|--------|---------|
| `Scheduled` | Booked, patient not yet arrived |
| `Arrived` | Patient checked in |
| `In-Progress` | Doctor currently consulting |
| `Completed` | Consultation done |
| `Cancelled` | Appointment cancelled |
| `No-Show` | Patient didn't arrive |
| `Doctor On Leave` | Auto-set if doctor has no availability |

---

## 7. OPD Management

**File:** `reception_view/opd_management.php`  
**APIs Used:**
- `GET /api/opd/queue` — live OPD queue
- `GET /api/opd/encounter/{id}` — encounter details
- `PUT /api/appointments/{id}` — update status

### OPD Queue View
- Live list of today's appointments ordered by token number
- Color coded by status
- Actions per patient: Mark Arrived | View Details | Cancel

### Vitals Entry
Receptionist can enter initial vitals before patient sees doctor:
- Blood Pressure, Temperature, Weight, Height, SpO2, Pulse

---

## 8. OPD Billing

**File:** `reception_view/opd_billing.php`  
**APIs Used:**
- `GET /api/billing/opd` — bill list
- `POST /api/billing/opd` — create bill
- `POST /api/billing/opd/payment` — record payment
- `GET /api/billing/opd/services` — service list
- `GET /api/billing/opd/consultation-fee` — doctor fee

### OPD Billing Workflow

```
Step 1: Select Patient
        Search patient → Select from dropdown
        Shows: Recent bills, last visit date

Step 2: Select Doctor
        Auto-fill consultation fee from doctor profile

Step 3: Add Services/Items
        From predefined service list OR manual entry
        Each item: Name | Qty | Unit Price | Total
        Duplicate check: Alert if same service billed today

Step 4: Apply Discount (if any)
        Flat amount OR percentage

Step 5: Payment
        Payment mode: Cash | Card | UPI | Insurance | Corporate
        Amount received
        Change calculation

Step 6: Generate Bill
        → POST /api/billing/opd
        → Bill ID: format varies (OPDBILL-YYYYMMDD-NNN)
        → Receipt No: ORC-YYYY-NNN

Step 7: Print Receipt
        → reception_view/print_opd_bill.php
```

### OPD Bill Components
| Component | Source |
|-----------|--------|
| Consultation Fee | `doctors.consultation_fee` |
| Service Charges | Manually added services |
| Lab Charges | If lab ordered through billing |
| Discount | Manually applied |
| Net Payable | Total - Discount |

### Additional OPD Billing Pages
| Page | Purpose |
|------|---------|
| `opd_billing_advanced.php` | Advanced billing with referrals/sponsors |
| `opd_billing_report.php` | Revenue report for receptionists |
| `appointment_bill.php` | Direct billing from appointment screen |
| `print_opd_bill.php` | Print-ready OPD bill |
| `print_opd_receipt.php` | Payment receipt |
| `print_opd_invoice_advanced.php` | Detailed invoice |

---

## 9. Doctor Availability

**File:** `reception_view/doctor_availability.php`  
**API:** `reception_view/api/get_available_doctors.php`

### Features
- Calendar view of doctor schedules
- Each doctor: Available days, In-time, Out-time
- Shows number of appointments booked per doctor per day
- Allows receptionist to plan appointment distribution

---

## 10. IPD Management (Full Sub-System)

**Entry Point:** `reception_view/ipd_management/public/index.php`

The IPD Management module is a **complete sub-application** with its own MVC architecture.

### Sub-System Routes (via `routes/api.php`)

| Route | Controller | Purpose |
|-------|-----------|---------|
| `/api/admissions` | AdmissionsController | CRUD for admissions |
| `/api/beds` | BedsController | Bed availability/assignment |
| `/api/ipd-billing` | IpdBillingController | Billing operations |
| `/api/ipd-billing-master` | IpdBillingMasterController | Financial master |
| `/api/ipd-billing-items` | IpdBillingItemController | Charge items |
| `/api/ipd-payments` | IpdPaymentController | Payment recording |
| `/api/ipd-insurance` | IpdInsuranceController | Insurance management |
| `/api/procedures` | ProceduresController | OT procedures |
| `/api/visitors` | VisitorsController | Visitor logging |
| `/api/discharge` | DischargeController | Patient discharge |
| `/api/dashboard` | DashboardController | IPD dashboard stats |

---

### 10.1 IPD Admissions

**View:** `reception_view/ipd_management/views/admissions/`  
**Model:** `Admission.php`  
**Controller:** `AdmissionsController.php`

#### New Admission Workflow

```
Step 1: Select Patient
        Search existing patient by name/ID/phone
        (Patient must already be registered)

Step 2: Select Bed
        → Filter by Ward → Room Type → Available beds
        → API: GET /api/beds?status=Available
        → Select bed → Auto-fill room rates

Step 3: Fill Admission Details
        ├── Admitting Doctor
        ├── Referral Doctor (if referred)
        ├── Reason for Admission / Chief Complaint
        ├── Provisional Diagnosis
        ├── Admission Date & Time
        ├── Bill Type: SELF / INSURANCE / CORPORATE
        └── Sponsor/Insurance Company (if applicable)

Step 4: Create Admission
        → POST /api/admissions
        → Admission ID: ADM-YYYYMMDD-NNN
        → Bed status updated to 'Occupied'
        → IPD Billing Master created (BILL-YYYYMMDD-NNNN)
        → Daily room rent starts automatically

Step 5: Print Admission Slip
```

#### Admission Form Fields
| Field | Type | Required | Notes |
|-------|------|----------|-------|
| Patient ID | Search | ✅ | Must be registered |
| Admitting Doctor | Select | ✅ | From doctors list |
| Bed | Select | ✅ | Available beds only |
| Admission Date | Date | ✅ | Default: today |
| Admission Time | Time | ✅ | Default: now |
| Reason | Textarea | ✅ | Chief complaint |
| Diagnosis | Textarea | ❌ | Provisional |
| Bill Type | Select | ✅ | SELF/INSURANCE/CORPORATE |
| Credit Type | Select | ❌ | If insurance/corporate |
| Sponsor | Text | ❌ | Insurance company/corporate name |
| Referred By | Text | ❌ | Referring doctor |
| Advance Amount | Number | ❌ | Initial advance |

#### Admission ID Format
```
ADM-YYYYMMDD-NNN
Example: ADM-20260814-001
```

---

### 10.2 Bed Management

**View:** `reception_view/ipd_management/views/beds/`  
**Model:** `Bed.php`  
**Controller:** `BedsController.php`

#### Bed Status Values
| Status | Color | Description |
|--------|-------|-------------|
| Available | 🟢 Green | Ready for admission |
| Occupied | 🔴 Red | Patient admitted |
| Reserved | 🟡 Yellow | Tentatively held |
| Under Maintenance | ⚪ Grey | Temporarily unavailable |

#### Bed Information Stored
| Field | Description |
|-------|-------------|
| ward_name | Ward/unit name (General, ICU, NICU) |
| room_name | Room identifier |
| bed_number | Bed number within room |
| room_type | General / Semi-Private / Private / ICU |
| amount_per_day | Base bed rental per day (₹) |
| nursig_charge | Daily nursing charge (₹) |
| doctor_charge | Duty doctor daily charge (₹) |
| service_charge | Daily service charge (₹) |
| total_bed_amount | Combined per-day charge (₹) |
| patient_id | Current patient (if occupied) |
| status | Current status |

---

### 10.3 IPD Billing Engine

**APIs:** `/api/ipd-billing-master` and `/api/ipd-billing-items`  
**Models:** `IpdBillingMaster.php`, `IpdBillingItem.php`

#### Billing Master (One Per Admission)

When a patient is admitted, a **billing master** is automatically created:

```
Admission Created
       │
       ▼
IpdBillingMaster::getOrCreateForAdmission()
       │
       ▼
Record inserted in ipd_billing_master:
- bill_id: BILL-20260814-0001
- admission_id: ADM-20260814-001
- patient_id: PID-20260814-001
- billing_status: OPEN
- payment_status: Pending
- All charge categories: 0.00
```

#### Daily Room Rent Auto-Generation

Every time the billing page is opened, the system checks for missing daily charges:

```
catchUpBedCharges() is called:
1. Calculate hours since admission
2. Total periods = ceil(hours / 24), min 1
3. Count existing ROOM_RENT entries
4. Add missing entries (1 per missing day)
5. Each entry = total_bed_amount + ₹570 food
```

#### Add Manual Charge

```
Receptionist selects:
  Charge Type → LAB / PHARMACY / PROCEDURE / etc.
  Date → charge date
  Description → what was done
  Unit Price × Quantity = Total

→ POST /api/ipd-billing-items (action: add_item)
→ Item saved to ipd_billing_items
→ IpdBillingMaster::recalculateMaster() called automatically
→ Bill totals updated in real-time
```

#### Billing Categories Available
| Category | Charge Type | Examples |
|----------|-------------|---------|
| Room & Food | ROOM_RENT | Auto-generated daily |
| Doctor Visits | DOCTOR_VISIT | Daily visiting doctor fee |
| Laboratory | LAB | Blood tests, culture |
| Radiology | RADIOLOGY | X-ray, CT scan, MRI |
| Pharmacy | PHARMACY | Medicines dispensed |
| OT Charges | OT | Surgery charges |
| Procedures | PROCEDURE | Minor procedures |
| Consumables | CONSUMABLE | Syringes, gloves, IV sets |
| Others | OTHER | Miscellaneous |

#### Financial Calculation (Auto)
```
subtotal = SUM of all non-cancelled billing items

discount_amount = manual override OR (subtotal × discount_pct / 100)

grand_total = subtotal - discount_amount

insurance_approved = from ipd_insurance table

patient_payable = grand_total - insurance_approved

amount_paid = SUM of payments (non-insurance, non-refund)

insurance_received = SUM of insurance payments

balance_due = grand_total - amount_paid - insurance_received

payment_status:
  - Pending: no payment made
  - Partial: payment < grand_total
  - Paid: balance_due ≤ 0
```

#### Cancel a Charge
```
Receptionist clicks "Cancel" on a line item
→ DELETE /api/ipd-billing-items (body: {item_id})
→ Item status set to CANCELLED (not deleted)
→ Bill recalculated
Note: Only MANUAL items can be cancelled from billing UI
```

---

### 10.4 IPD Payments

**API:** `/api/ipd-payment`  
**Model:** `IpdPayment.php`

#### Payment Types
| Type | When Used |
|------|-----------|
| ADVANCE | Collected at/during admission |
| FINAL | At discharge, final settlement |
| REFUND | If overpaid, refunded to patient |

#### Record Payment Workflow
```
Step 1: Open payment modal
Step 2: Select payment type (ADVANCE/FINAL)
Step 3: Select payment mode (Cash/Card/UPI/NEFT/Cheque)
Step 4: Enter amount
Step 5: Enter reference number (for Card/UPI/NEFT)
Step 6: Add remarks
Step 7: Submit
→ POST /api/ipd-payment (action: record_payment)
→ Record inserted in ipd_payment (always INSERT, never UPDATE)
→ Master recalculated
```

#### Record Insurance Payment
```
When insurance company pays:
Step 1: Click "Record Insurance Receipt"
Step 2: Enter amount received from insurer
Step 3: Enter reference/TPA number
→ POST /api/ipd-payment (action: record_insurance)
→ payment_mode = 'INSURANCE'
→ Updates ipd_insurance.received_amount
```

#### Refund Process
```
Step 1: Click "Issue Refund"
Step 2: Enter refund amount
Step 3: Enter refund reason (REQUIRED)
Step 4: Enter approved by (REQUIRED — senior staff name)
Step 5: Select payment mode for refund
→ POST /api/ipd-payment (action: record_refund)
→ payment_type = 'REFUND'
→ amount_paid decremented in recalculation
```

---

### 10.5 IPD Discharge

**Model:** `Discharge.php`  
**Controller:** `DischargeController.php`  
**API:** `POST /api/admissions (action: discharge)`

#### Discharge Workflow

```
Nurse sends discharge notification
       │
       ▼
Admin/Reception Reviews Bill
       │
       ▼
Step 1: Open billing for patient
Step 2: Review all charges — verify all items correct
Step 3: Apply final discount (if any)
Step 4: Record final payment
Step 5: Confirm balance = 0 (or approved credit/insurance)
Step 6: Click "Finalize & Discharge"
       │
       ▼
POST /api/admissions (action: discharge)
       │
       ├── Updates ipd_admissions.status = 'Discharged'
       ├── Sets ipd_admissions.discharge_date = today
       ├── Updates ipd_billing_master.billing_status = 'FINALIZED'
       ├── Updates ipd_billing_master.discharge_date = today
       ├── Releases bed → hospital_beds.status = 'Available'
       └── hospital_beds.patient_id = NULL
       │
       ▼
Print Discharge Summary (if doctor has completed it)
Print Final Bill
```

---

### 10.6 Procedures & Visitors

#### Procedures (OT / Surgery)
- Record surgical procedures for IPD patients
- Fields: Procedure name, date, surgeon, anesthetist, charges
- Automatically added to IPD bill under OT/PROCEDURE category

#### Visitor Log
- Track visitors for each IPD patient
- Fields: Visitor name, relation, contact, visit date/time
- Purpose: Security and infection control

---

## 11. Print Operations

| Print Page | Purpose |
|------------|---------|
| `print_final.php` | Final IPD discharge bill |
| `print_interim.php` | Interim IPD bill (during stay) |
| `print_receipt.php` | IPD payment receipt |
| `print_opd_bill.php` | OPD bill |
| `print_opd_receipt.php` | OPD payment receipt |
| `print_opd_invoice_advanced.php` | Detailed OPD invoice |
| `print_final.php` | Final settlement receipt |

---

## 12. Security & Permissions

### What Receptionist CAN Do
```
✅ Register and edit patients
✅ Book and manage appointments
✅ Enter vitals
✅ Create OPD bills and receive payment
✅ Admit patients to IPD
✅ Assign beds
✅ Add IPD billing charges
✅ Record IPD payments (advance/final)
✅ Discharge patients (with billing clearance)
✅ View prescriptions (read-only)
✅ Print bills and receipts
```

### What Receptionist CANNOT Do
```
❌ Delete/modify completed OPD bills
❌ Create/edit doctor profiles
❌ Create/edit staff accounts
❌ Manage system settings
❌ Access pharmacy inventory
❌ Enter lab results
❌ Prescribe medications
```

---

## 13. Complete Workflow Flowchart

```
RECEPTIONIST LOGIN
       │
       ▼
DASHBOARD
  │
  ├─── OPD WORKFLOW ────────────────────────────────────┐
  │    │                                                │
  │    ├─ Check Patient (existing/new?)                 │
  │    │    └─ New → Register Patient                   │
  │    │                                                │
  │    ├─ Book Appointment → Assign Token               │
  │    │                                                │
  │    ├─ Patient Arrives → Mark Arrived                │
  │    │                                                │
  │    ├─ Enter Vitals → Forward to Doctor              │
  │    │                                                │
  │    ├─ Doctor Completes → Generate OPD Bill          │
  │    │    ├─ Add services/items                       │
  │    │    ├─ Apply discount                           │
  │    │    └─ Collect payment                          │
  │    │                                                │
  │    └─ Print Receipt                                 │
  │                                                     │
  └─── IPD WORKFLOW ────────────────────────────────────┘
       │
       ├─ Search Patient → Create Admission
       │    ├─ Select Bed (Ward/Room/Bed)
       │    ├─ Set Bill Type (Self/Insurance/Corporate)
       │    └─ Record advance payment
       │
       ├─ DURING STAY:
       │    ├─ Add charges manually (Lab, Pharmacy, OT)
       │    ├─ Room rent auto-generated daily
       │    └─ Record advance payments as needed
       │
       ├─ DISCHARGE PROCESS:
       │    ├─ Receive nurse discharge notification
       │    ├─ Review all billing items
       │    ├─ Apply discount (if approved)
       │    ├─ Collect final payment
       │    ├─ Finalize bill → Discharge patient
       │    └─ Print discharge bill + receipts
       │
       └─ Bed Released → Available for next patient
```

---

*End of Document — Reception Role Documentation*

---
**Document Control** | Version 2.0 | August 2026
