# GM_HMS — Nurse Role Documentation

---

> **Document Type:** Role-Based User Guide  
> **Role:** Nurse  
> **Version:** 2.0.0  
> **Audience:** Management · Nursing Staff · Technical Team  
> **Date:** August 2026

---

## Table of Contents
1. [Role Overview](#1-role-overview)
2. [Nurse Dashboard](#2-nurse-dashboard)
3. [IPD Patient Summary](#3-ipd-patient-summary)
4. [Medication Administration (MAR)](#4-medication-administration-mar)
5. [K-Sheet (Vital Signs Chart)](#5-k-sheet-vital-signs-chart)
6. [Clinical Charts](#6-clinical-charts)
7. [IPD Pharmacy Orders](#7-ipd-pharmacy-orders)
8. [IPD Medicine Returns](#8-ipd-medicine-returns)
9. [Lab Test Orders](#9-lab-test-orders)
10. [Shift Management](#10-shift-management)
11. [Ward Management](#11-ward-management)
12. [Discharge Notification](#12-discharge-notification)
13. [Security & Permissions](#13-security--permissions)
14. [Workflow Flowchart](#14-workflow-flowchart)

---

## 1. Role Overview

The **Nurse** role is focused on **direct patient care** activities for IPD (in-patient) patients. Nurses monitor vital signs, administer medications, maintain clinical charts, order medicines from pharmacy, and coordinate discharge readiness.

### Key Responsibilities
- Monitor and record patient vital signs (K-sheet)
- Administer and document medications (MAR)
- Maintain special clinical charts (Dialysis, Oxygen, Ventilation, Blood)
- Order medicines from pharmacy for IPD patients
- Process and manage medicine return requests
- Order lab tests for patients
- Prepare and send discharge notifications
- Track nurse shift assignments

### Access Level
```
FULL ACCESS: Patient care, Clinical records, Vitals, Medications
RESTRICTED: Pharmacy ordering (through nurse interface only)
NO ACCESS: Billing, Patient registration, Doctor management
```

---

## 2. Nurse Dashboard

**File:** `nurse_view/dashboard.php`  
**API:** `nurse_view/api/dashboard.php`

### Dashboard Sections

#### My Patients Today
- All IPD patients assigned to this nurse's ward
- Status indicators: Stable / Critical / Monitoring

#### Pending Tasks
- Medications due (from MAR schedule)
- Vitals to be recorded
- Lab samples to collect

#### Ward Overview
- Bed occupancy in assigned ward
- Color-coded patient status

#### Quick Actions
- ➕ Record Vitals
- 💊 Administer Medication
- 🧪 Order Lab Tests
- 📋 Update Clinical Chart
- 🔔 Send Discharge Notification

---

## 3. IPD Patient Summary

**File:** `nurse_view/ipd_summary.php` (64KB — comprehensive)  
**API Calls:**
- `GET /api/ipd-summary?admission_id=ADM-...`
- `GET /api/ipd-clinical/medications?admission_id=...`
- `GET /api/ipd-clinical/visits?admission_id=...`
- `GET /api/ipd-clinical/investigations?admission_id=...`
- `nurse_view/api/get_clinical_records.php`

### Page Sections

#### Patient Header
- Name, age, gender, blood group
- Admission ID, bed number, ward
- Admitting doctor
- Days admitted

#### Tabs/Sections

**1. Summary Tab**
- Chief complaint at admission
- Current diagnosis
- Active medications
- Last vitals
- Recent lab results

**2. Daily Progress Tab**
- Doctor visit notes chronologically
- Nurse nursing notes
- Clinical observations

**3. Vital Signs History**
- Tabular history of all recorded vitals
- Trend visualization

**4. Medication Administration Record (MAR)**
- Current medication schedule
- Administration history

**5. Clinical Charts**
- Dialysis chart (if applicable)
- Oxygen support chart
- Ventilation chart
- Blood transfusion chart

**6. Lab & Investigations**
- All ordered tests and results

**7. Discharge Summary (Read-only)**
- Doctor's discharge notes (when completed)

#### Discharge Notification Button
```
When patient is ready for discharge:
[Request Discharge] button

→ POST nurse_view/api/send_discharge_notification.php
→ patient_id, admission_id sent
→ Notification inserted in discharge_notifications table
→ Admin receives alert on dashboard
```

---

## 4. Medication Administration (MAR)

**File:** `nurse_view/medication.php` (83KB — most complex)  
**API:** `GET/POST /api/ipd-clinical/medications`

### MAR Overview

The **Medication Administration Record** is the official document tracking every medication given to an IPD patient.

### Layout

#### Patient Search / Selection
```
Search IPD patient by name/ID
→ nurse_view/api/search_ipd_patient.php
→ Load patient's prescription and medication schedule
```

#### Medication Schedule Grid

```
MEDICATION SCHEDULE — Patient: Ramesh Kumar (ADM-20260814-001)

Medicine     | Dose   | Route | Frequency | 06:00 | 12:00 | 18:00 | 22:00
─────────────┼────────┼───────┼───────────┼───────┼───────┼───────┼───────
Amox 500mg   | 1 Tab  | Oral  | TID       |  ✅   |  ✅   |  ⏰   |  —
Metop 50mg   | 1 Tab  | Oral  | BD        |  ✅   |  —    |  ⏰   |  —
Heparin      | 5000 IU| IV    | BD        |  ✅   |  —    |  ⏰   |  —
```

Legend:
- ✅ Administered
- ❌ Not given (with reason)
- ⏰ Upcoming/Due
- — Not scheduled at this time

### Administering a Medication

```
Nurse clicks the time slot for a medication:
→ Modal opens:
   ├── Medicine name (pre-filled)
   ├── Dose (pre-filled, can modify)
   ├── Time administered (default: now)
   ├── Route (pre-filled, can modify)
   ├── Administered by: [nurse name]
   └── Notes/remarks

→ Submit → POST /api/ipd-clinical/medications
→ Record saved: patient_id, medication, time, administered_by
→ Slot updated to ✅
```

### Holding/Skipping a Medication

```
Nurse can mark medication as:
- HELD: Patient refused / Clinical decision
- NOT GIVEN: Supply not available
Always requires reason
```

### PRN (As Needed) Medications

```
PRN medications appear in a separate section
Nurse can administer when:
- Patient requests (pain relief)
- Clinical indication (fever above 101°F)
Must document: Indication for giving + patient response
```

---

## 5. K-Sheet (Vital Signs Chart)

**File:** `nurse_view/k_sheet_view.php` (34KB)  
**API:** `nurse_view/api/save_vitals.php`, `nurse_view/api/vitals_rest.php`

### K-Sheet Overview

The **K-Sheet** (Kardex Sheet) is the bedside vital signs monitoring chart for IPD patients.

### Vitals Recorded

| Vital | Unit | Normal Range |
|-------|------|--------------|
| Temperature | °F | 97–99 |
| Blood Pressure | mmHg | 120/80 (systolic/diastolic) |
| Pulse Rate | /min | 60–100 |
| Respiratory Rate | /min | 12–20 |
| SpO₂ | % | 95–100 |
| Blood Sugar (FBS/RBS) | mg/dL | 70–140 |
| Urine Output | mL | >30 mL/hr |
| Fluid Intake | mL | Documented |

### Recording Vitals

```
Step 1: Select patient (IPD patient search)
Step 2: Select frequency (4-hourly / 8-hourly / 12-hourly)
Step 3: Enter readings
Step 4: Submit → POST nurse_view/api/save_vitals.php

Data saved to: 
  Table: patient_vitals (or ipd_clinical_records)
  Fields: patient_id, admission_id, record_date, 
          temperature, bp_systolic, bp_diastolic, 
          pulse, rr, spo2, blood_sugar, urine_output
```

### Visual Chart
- Time-based graph (Chart.js)
- Each vital plotted on Y-axis vs time on X-axis
- Color-coded alert lines for abnormal values
- Critical values highlighted in red

### Vital Sign Alerts
```
Critical thresholds that trigger visual alerts:
- Temperature > 103°F or < 95°F → Red highlight
- SpO2 < 90% → Immediate alert
- BP > 180/120 or < 90/60 → Alert
- Pulse > 120 or < 50 → Alert
```

---

## 6. Clinical Charts

**API:** `nurse_view/api/save_clinical_record.php`, `nurse_view/api/get_clinical_records.php`

Clinical charts capture **specialized interventions** for critically ill patients. These records are also **automatically synced to IPD billing** when billing is reviewed.

### Chart Types

#### 6.1 Dialysis Chart

Records each dialysis session:

| Field | Description |
|-------|-------------|
| dia_date | Date of dialysis |
| dia_start | Time machine connected |
| dia_end | Time machine disconnected |
| dia_dur | Duration (calculated) |
| uf_goal | Ultrafiltration goal (L) |
| uf_achieved | Actual UF achieved |
| access_site | AV fistula / CVC / etc. |
| complications | Any complications |
| nurse_initials | Nurse who performed |

#### 6.2 Oxygen Support Chart

Records oxygen therapy:

| Field | Description |
|-------|-------------|
| oxy_date | Date |
| oxy_start | Therapy started time |
| oxy_end | Therapy ended time |
| oxy_dur | Duration |
| oxy_device | Nasal prongs / Face mask / NRM / HFNC / Ventilator |
| oxy_flow | Flow rate (L/min) |
| fio2 | FiO2 percentage |
| spo2_before | SpO2 before O2 |
| spo2_after | SpO2 on O2 |

#### 6.3 Ventilation Chart

Records mechanical ventilation parameters:

| Field | Description |
|-------|-------------|
| vent_date | Date |
| vent_start | Ventilation start time |
| vent_end | Time of extubation |
| vent_mode | AC/VC / SIMV / PSV / CPAP |
| tidal_volume | Tidal volume (mL) |
| peep | PEEP level |
| fio2 | FiO2 |
| pip | Peak inspiratory pressure |
| compliance | Lung compliance |

#### 6.4 Blood Transfusion Chart

Records each unit of blood product:

| Field | Description |
|-------|-------------|
| trans_date | Date |
| blood_group | Patient blood group |
| bag_number | Blood bag number |
| product_type | PRBC / FFP / Platelets / Whole blood |
| time_started | Transfusion start time |
| time_ended | Transfusion end time |
| volume_transfused | mL transfused |
| pre_temp | Pre-transfusion temperature |
| post_temp | Post-transfusion temperature |
| reactions | Any transfusion reactions |

### Saving Clinical Records

```
POST nurse_view/api/save_clinical_record.php
Body (form-data):
  patient_id: PID-...
  admission_id: ADM-...
  chart_type: dialysis_chart / oxygen_chart / ventilation_chart / blood_transfusion_chart
  chart_data: JSON array of chart entries
  record_date: 2026-08-14
```

### Billing Integration

When the billing page is opened, `IpdBillingMaster::syncClinicalRecords()` runs:
```
1. Fetches all ipd_clinical_records for the admission
2. Processes each chart (dialysis, oxygen, ventilation, blood)
3. Creates billing items in ipd_billing_items (charge_type: OTHER)
4. Adds descriptions with chart details
5. Price set to ₹0.00 (charges configured by admin separately)
6. Recalculates billing master totals
```

---

## 7. IPD Pharmacy Orders

**File:** `nurse_view/ipd_pharmacy_order.php`  
**APIs:**
- `nurse_view/api/save_pharmacy_order.php` — place order
- `nurse_view/api/search_medicine.php` — medicine search
- `nurse_view/api/get_pharmacy_charges.php` — view charges

### Order Workflow

```
Step 1: Select Patient
        Search IPD patient by name/ID

Step 2: Search Medicine
        Type medicine name → Autocomplete dropdown
        API: nurse_view/api/search_medicine.php
        Shows: Medicine name, strength, available stock

Step 3: Add to Order
        Medicine | Quantity | Remarks

Step 4: Submit Order
        → POST nurse_view/api/save_pharmacy_order.php
        → Order sent to pharmacy (pharmacy sees in ip_orders.php)
        → Pharmacy dispenses medicines
        → Charge auto-posted to patient's IPD bill (PHARMACY category)

Step 5: Pharmacy Fulfills
        → Nurse receives medicines at ward
        → Administers as per prescription
```

### Medicine Search Response
```json
{
  "medicines": [
    {
      "product_id": 101,
      "product_name": "Amoxicillin 500mg",
      "generic_name": "Amoxicillin",
      "stock_available": 250,
      "unit": "Capsule",
      "batch_expiry": "2027-12"
    }
  ]
}
```

---

## 8. IPD Medicine Returns

**File:** `nurse_view/ipd_pharmacy_return.php`  
**APIs:**
- `nurse_view/api/submit_pharmacy_return.php` — request return
- `nurse_view/api/get_pharmacy_return_requests.php` — view status
- `nurse_view/api/medicine_return.php` — process return

### Return Reasons
- Patient discharged (unused medicines)
- Medicine changed by doctor
- Medicine expired
- Patient allergic reaction
- Overstocked at ward

### Return Workflow

```
Step 1: Select Patient

Step 2: Select medicines to return
        From: List of medicines ordered for this patient
        Enter quantity to return and reason

Step 3: Submit Return Request
        → POST nurse_view/api/submit_pharmacy_return.php
        → Request created in return_requests table (status: Pending)

Step 4: Pharmacy Verifies
        → Pharmacy sees pending return in ipd_returns_verification.php
        → Pharmacy confirms quantity
        → Approves → medicines returned to stock

Step 5: Bill Adjustment
        → PHARMACY charge reduced on patient's IPD bill
        → Patient refunded if applicable
```

---

## 9. Lab Test Orders

**File:** `nurse_view/ipd_tests.php`  
**APIs:**
- `nurse_view/api/save_tests.php` — order tests
- `nurse_view/api/search_tests.php` — test search

### Ordering Flow

```
Step 1: Select Patient (IPD)

Step 2: Search/Select Test
        API: nurse_view/api/search_tests.php?q=CBC
        Shows: Test name, price, TAT (turnaround time)

Step 3: Add clinical indication
        (Why this test is being ordered)

Step 4: Set priority: Routine / Urgent / STAT

Step 5: Submit
        → POST nurse_view/api/save_tests.php
        → Order created in lab_ipd_orders (status: Pending)
        → Lab technician sees the order
        → Result posted → nurse/doctor can view
```

---

## 10. Shift Management

**Files:** `nurse_view/my_shift.php`, `nurse_view/shift_assignment.php`, `nurse_view/all_shift_assignments.php`

### My Shift Page
- Shows nurse's own schedule for current week/month
- Shift details: Ward assigned, date, time, shift type
- Can view past shifts

### Shift Assignment (Supervisor view)
- Bulk assignment of nurses to shifts
- Calendar-based interface
- Conflict detection (overlapping shifts)

### Shift Types
| Type | Hours | Times |
|------|-------|-------|
| Morning | 8 hours | 7:00 AM – 3:00 PM |
| Evening | 8 hours | 3:00 PM – 11:00 PM |
| Night | 8 hours | 11:00 PM – 7:00 AM |

---

## 11. Ward Management

**File:** `nurse_view/ward_management.php`  
**API:** `GET /api/nurse-shifts/wards`

### Features
- Overview of all patients in assigned ward
- Quick status update per patient
- Nurse notes per patient
- Bed allocation view

---

## 12. Discharge Notification

**File:** Part of `nurse_view/ipd_summary.php`  
**API:** `POST nurse_view/api/send_discharge_notification.php`

### When to Use
When a doctor orders patient discharge and nursing assessment confirms readiness:
- Patient clinically stable
- All investigations complete
- Medications explained to patient/family
- Follow-up instructions ready

### How to Send

```
1. Open patient's IPD Summary page

2. Click [Request Discharge] button
   (Only visible when patient is 'Admitted' status)

3. Confirm in dialog:
   "Send discharge request to Admin/Billing for Ramesh Kumar (ADM-20260814-001)?"

4. Click Confirm
   → POST nurse_view/api/send_discharge_notification.php
   Body: { patient_id, admission_id }

5. System:
   → Fetches patient name from patient table
   → Creates message: "Patient Ramesh Kumar (PID-...) under Admission ID ADM-... is ready for discharge. Please process billing clearance."
   → Inserts to discharge_notifications (status: Pending)

6. Admin sees notification on dashboard
   → Processes bill → Clears notification
```

---

## 13. Security & Permissions

### What Nurse CAN Do
```
✅ View assigned IPD patients
✅ Record vitals (K-Sheet)
✅ Administer and document medications (MAR)
✅ Maintain clinical charts (Dialysis, O2, Ventilation, Blood)
✅ Order medicines from pharmacy
✅ Submit medicine return requests
✅ Order lab tests for IPD patients
✅ View lab results
✅ Send discharge notifications
✅ View own shift schedule
```

### What Nurse CANNOT Do
```
❌ Create or modify bills
❌ Register new patients
❌ Book appointments
❌ Access pharmacy stock management
❌ Modify prescriptions
❌ Create discharge summaries (doctor's responsibility)
❌ Manage staff or shifts (unless senior/supervisor role)
```

---

## 14. Workflow Flowchart

```
NURSE LOGS IN
      │
      ▼
NURSE DASHBOARD
  ├── View Today's Patient List (assigned ward)
  │
  ├── MORNING ROUND:
  │     ├── Record vitals for each patient → K-Sheet
  │     ├── Check MAR for morning medications
  │     ├── Administer medications → Document in MAR
  │     └── Update clinical charts (Dialysis/O2/etc.)
  │
  ├── PHARMACY ORDERING:
  │     ├── Check low medicine stock for patients
  │     ├── Search medicine → Add to order → Submit
  │     └── Pharmacy dispenses → Nurse receives
  │
  ├── LAB TESTS:
  │     ├── Doctor orders test → Nurse arranges sample collection
  │     ├── OR Nurse directly orders if standing order
  │     └── Lab results available → Notify doctor
  │
  ├── MEDICINE RETURNS:
  │     ├── Patient discharged or medicine changed
  │     ├── Submit return request
  │     └── Pharmacy verifies and accepts return
  │
  ├── AFTERNOON ROUND:
  │     ├── Afternoon vitals → K-Sheet
  │     ├── Afternoon medications → MAR
  │     └── Update clinical charts
  │
  ├── DISCHARGE PREPARATION:
  │     ├── Doctor informs readiness for discharge
  │     ├── Nursing assessment complete
  │     ├── Send Discharge Notification → Admin
  │     └── Prepare patient/family for discharge instructions
  │
  └── SHIFT HANDOVER:
        ├── Summarize shift activities
        ├── Pending medications for next shift
        └── Any critical patient notes
```

---

*End of Document — Nurse Role Documentation*

---
**Document Control** | Version 2.0 | August 2026
