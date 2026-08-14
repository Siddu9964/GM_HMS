# GM_HMS — Laboratory Role Documentation

---

> **Document Type:** Role-Based User Guide  
> **Role:** Laboratory Technician  
> **Version:** 2.0.0  
> **Audience:** Management · Lab Staff · Technical Team  
> **Date:** August 2026

---

## Table of Contents
1. [Role Overview](#1-role-overview)
2. [Laboratory Dashboard](#2-laboratory-dashboard)
3. [OPD Test Orders](#3-opd-test-orders)
4. [IPD Test Orders](#4-ipd-test-orders)
5. [Service (Test Catalog) Management](#5-service-test-catalog-management)
6. [Kanban Workflow Board](#6-kanban-workflow-board)
7. [Lab Reports & Printing](#7-lab-reports--printing)
8. [Inventory Management](#8-inventory-management)
9. [Critical Alerts](#9-critical-alerts)
10. [Reports](#10-reports)
11. [Security & Permissions](#11-security--permissions)
12. [Workflow Flowchart](#12-workflow-flowchart)

---

## 1. Role Overview

The **Laboratory Technician** manages the complete Laboratory Information System (LIS) within GM_HMS. This role handles processing test orders from doctors and nurses, performing tests, recording results, and generating reports.

### Key Responsibilities
- Receive and process lab test orders (OPD and IPD)
- Record test results with parameters and reference ranges
- Flag critical values and send alerts
- Manage test service catalog and pricing
- Generate printable lab reports
- Maintain lab inventory (reagents, consumables)
- Provide test turnaround time tracking

### Access Level
```
FULL ACCESS: All laboratory operations
READ ONLY: Patient demographics, Doctor information
NO ACCESS: Billing, Pharmacy, Clinical charts
```

---

## 2. Laboratory Dashboard

**File:** `laboratory_view/dashboard.php`  
**API:** `GET /api/laboratory/dashboard`

### KPI Cards

| Card | Metric |
|------|--------|
| Today's Orders | Total test orders received today |
| Pending | Tests not yet processed |
| In Progress | Tests being processed |
| Completed | Results uploaded |
| Critical Alerts | Tests with critical values |
| TAT (Avg) | Average turnaround time today |

### Dashboard Panels

#### Pending Tests Panel
- Top 10 pending tests ordered earliest
- Click → Open test for processing

#### Critical Results Panel
- Tests with out-of-critical-range results
- Patient name, test name, critical value

---

## 3. OPD Test Orders

**File:** `laboratory_view/test_orders.php` (114KB — largest view file)  
**APIs:**
- `GET /api/laboratory/orders` — list orders
- `GET /api/laboratory/orders/{id}` — single order
- `PUT /api/laboratory/orders/{id}/status` — update status
- `POST /api/laboratory/orders/{id}/result` — upload result

### Test Order List

#### Columns
| Column | Description |
|--------|-------------|
| Order ID | Lab order reference |
| Patient | Name + Patient ID |
| Doctor | Referring doctor |
| Tests Ordered | List of tests |
| Priority | Routine / Urgent / STAT |
| Order Time | When ordered |
| Status | Pending/In-Progress/Completed |
| Actions | View / Start / Upload Result |

#### Filters Available
- Date range
- Status
- Doctor
- Priority
- Test category

---

### Test Order Lifecycle

```
Order Created (by Doctor / Receptionist / Nurse)
       │
       ├── Status: Pending
       ▼
Lab Tech Receives Order → Reviews
       │
       ├── Status: In Progress (when sample collected)
       ▼
Sample Collection
       │
       ├── Collected by: Lab tech / Nurse
       ├── Collection time: Documented
       └── Sample type: Blood / Urine / Sputum / etc.
       │
       ▼
Analysis / Processing
       │
       └── In lab analyzer / manual testing
       │
       ▼
Result Entry
       │
       ├── Each parameter entered with value
       ├── System compares to normal range
       ├── Status: Pending → In Progress → Completed
       └── POST /api/laboratory/orders/{id}/result
       │
       ▼
Report Generated
       │
       ├── Auto-formatted with hospital letterhead
       ├── Patient details, doctor, test date
       ├── Results table with normal ranges
       └── Critical values highlighted
       │
       ▼
Doctor/Nurse Views Result
       │
       └── doctors_view/consultation.php → Lab Results tab
```

---

### Result Entry Interface

For each ordered test, results are entered as:

```
Test: CBC (Complete Blood Count)

Parameter           Value    Unit      Reference Range    Flag
───────────────────────────────────────────────────────────────
Hemoglobin          11.2     g/dL      12.0–16.0          LOW ⚠️
WBC Count           12,400   /cumm     4,000–11,000       HIGH ⚠️
Platelet Count      2,40,000 /cumm     1,50,000–4,50,000  Normal ✓
Hematocrit (PCV)    34.5     %         37–47              LOW ⚠️
MCV                 82       fL        80–100             Normal ✓
MCH                 28       pg        27–33              Normal ✓
MCHC                33       g/dL      31.5–36            Normal ✓
RBC Count           3.9      M/cumm    4.2–5.4            LOW ⚠️
Neutrophils         75       %         40–75              Normal ✓
Lymphocytes         18       %         20–45              LOW ⚠️
```

#### Result Entry API
```json
POST /api/laboratory/orders/{order_id}/result
{
  "parameters": [
    {
      "name": "Hemoglobin",
      "value": "11.2",
      "unit": "g/dL",
      "normal_range": "12.0-16.0",
      "flag": "Low"
    }
  ],
  "remarks": "Anemia noted. Peripheral smear advised.",
  "reported_by": "Lab Tech",
  "verification": "Pathologist signature"
}
```

#### Automatic Flagging
```
For each parameter:
  IF value < lower_range → flag = 'Low'
  IF value > upper_range → flag = 'High'
  ELSE → flag = 'Normal'

If flag = 'Critical' (extreme values):
  → Insert into critical_alerts table
  → Lab dashboard shows alert
  → Notification sent to doctor
```

---

## 4. IPD Test Orders

**File:** `laboratory_view/ipd_test_orders.php` (112KB)  
**APIs:**
- `GET /api/laboratory/ipd-orders` — list IPD orders
- `PUT /api/laboratory/ipd-orders/{id}/status` — update status
- `POST /api/laboratory/ipd-orders/{id}/result` — upload result

### Differences from OPD Orders
| Aspect | OPD | IPD |
|--------|-----|-----|
| Ordered by | Doctor/Receptionist | Doctor/Nurse |
| Patient location | OPD queue | Admitted (bed/ward shown) |
| Repeat tests | Rare | Common (daily monitoring) |
| Priority | Usually Routine | Often Urgent |
| Bill | OPD bill | IPD bill (auto-charged) |

### IPD Order List Shows
- Patient name + Bed/Ward
- Admission ID
- Tests ordered
- Ordered by (doctor or nurse)
- Priority level
- Status

---

## 5. Service (Test Catalog) Management

**File:** `laboratory_view/services.php`  
**APIs:**
- `GET /api/laboratory/services` — list all tests
- `POST /api/laboratory/services` — add new test
- `PUT /api/laboratory/services/{category}/{id}` — update
- `DELETE /api/laboratory/services/{category}/{id}` — remove

### Test Catalog Structure

#### Test Categories
| Category | Examples |
|----------|---------|
| Hematology | CBC, ESR, Peripheral Smear, Coagulation |
| Biochemistry | LFT, KFT, Blood Sugar, Lipid Profile |
| Serology | HIV, HBsAg, HCV, VDRL, Widal |
| Microbiology | Culture & Sensitivity, AFB |
| Immunology | ANA, RA Factor, CRP, Procalcitonin |
| Urine Analysis | Routine Urine, Urine Culture |
| Stool | Stool Routine, Occult Blood |
| Hormones | TSH, FT3, FT4, Cortisol, Insulin |
| Tumor Markers | PSA, CA-125, CEA, AFP |
| Cardiac | Troponin, CK-MB, BNP, D-Dimer |

#### Test Service Fields
| Field | Description |
|-------|-------------|
| Service Name | Test name (e.g., "CBC — Complete Blood Count") |
| Category | Test category |
| Price | In ₹ |
| TAT | Turnaround time (hours) |
| Parameters | Sub-tests with units and reference ranges |
| Sample Type | Blood/Urine/Stool/Sputum/CSF |
| Collection Instructions | e.g., "Fasting 8 hours required" |
| Status | Active / Inactive |

#### Parameter Configuration
Each test can have multiple parameters:
```
Test: LFT (Liver Function Test)
Parameters:
  - Total Bilirubin (range: 0.2–1.2 mg/dL)
  - Direct Bilirubin (range: 0–0.3 mg/dL)
  - Indirect Bilirubin (range: 0.1–0.8 mg/dL)
  - SGOT/AST (range: 10–40 U/L)
  - SGPT/ALT (range: 7–56 U/L)
  - Alkaline Phosphatase (range: 44–147 U/L)
  - Total Protein (range: 6.3–8.2 g/dL)
  - Albumin (range: 3.5–5.5 g/dL)
```

---

## 6. Kanban Workflow Board

**File:** `laboratory_view/kanban.php`  
**API:** `GET /api/laboratory/kanban`

### Kanban Board Layout

```
┌─────────────────┬────────────────────┬────────────────────┐
│    PENDING      │    IN PROGRESS     │    COMPLETED       │
│ (Sample needed) │ (Processing)       │ (Results ready)    │
├─────────────────┼────────────────────┼────────────────────┤
│ ┌─────────────┐ │ ┌─────────────┐   │ ┌─────────────┐   │
│ │ Ramesh K.   │ │ │ Priya S.    │   │ │ Arun M.     │   │
│ │ CBC, LFT    │ │ │ Urine R/E   │   │ │ Blood Sugar │   │
│ │ Dr. Sharma  │ │ │ Dr. Patel   │   │ │ Completed   │   │
│ │ URGENT      │ │ │ In Lab      │   │ │ 10:30 AM    │   │
│ └─────────────┘ │ └─────────────┘   │ └─────────────┘   │
└─────────────────┴────────────────────┴────────────────────┘
```

- Drag and drop cards between columns (if enabled)
- Color coding by priority (Red=STAT, Orange=Urgent, Blue=Routine)
- TAT countdown timer on each card

---

## 7. Lab Reports & Printing

**File:** `laboratory_view/print_result.php`

### Report Format
```
┌────────────────────────────────────────────────────────┐
│           GM HOSPITAL — LABORATORY REPORT              │
│                  Basaveshwaranagar                     │
├────────────────────────────────────────────────────────┤
│ Patient: Ramesh Kumar      Age/Sex: 45Y/M              │
│ Ref. By: Dr. Ravi Sharma   Date: 14-Aug-2026           │
│ Lab No.: LAB-20260814-001  Bill No.: OPD-20260814-001  │
├────────────────────────────────────────────────────────┤
│ TEST: CBC — COMPLETE BLOOD COUNT                       │
│ Sample: Venous Blood     Collection: 09:00 AM          │
│ Reported: 10:30 AM       TAT: 1.5 Hours                │
├────────────────┬────────┬────────┬──────────────────────┤
│ Parameter      │ Result │ Unit   │ Reference Range       │
├────────────────┼────────┼────────┼──────────────────────┤
│ Hemoglobin     │ 13.5   │ g/dL   │ 13.0 – 17.0          │
│ Total WBC      │ 8,200  │ /cumm  │ 4,000 – 11,000       │
│ Platelet Count │ 2,50,000│/cumm  │ 1,50,000 – 4,50,000  │
├────────────────┴────────┴────────┴──────────────────────┤
│ INTERPRETATION: Within normal limits                   │
│                                                        │
│ Reported by: Lab Tech. Suma              [SIGNATURE]   │
└────────────────────────────────────────────────────────┘
```

### Print Options
- Single test result
- All tests for an order
- Full report for a patient (multiple tests)

---

## 8. Inventory Management

**File:** `laboratory_view/inventory.php`

### Lab Consumables Tracked
- Reagent kits (CBC reagents, biochemistry reagents)
- Collection tubes (EDTA, plain, SST, fluoride)
- Lancets, needles, syringes
- Slides, cover slips
- Culture media
- PPE (gloves, masks)

### Features
- Add/edit consumables
- Record usage (manual deduction)
- Set reorder levels
- Expiry tracking

---

## 9. Critical Alerts

**File:** `laboratory_view/critical_alerts.php`  
**API:** `GET /api/laboratory/notifications`

### Critical Value Thresholds

| Test | Critical Low | Critical High |
|------|-------------|---------------|
| Hemoglobin | < 7 g/dL | > 20 g/dL |
| WBC | < 2,000 | > 30,000 |
| Platelet | < 50,000 | > 10,00,000 |
| Potassium | < 2.5 | > 6.5 mEq/L |
| Sodium | < 120 | > 160 mEq/L |
| Blood Sugar | < 40 | > 500 mg/dL |
| Creatinine | — | > 10 mg/dL |
| INR | — | > 5 |

### Alert Workflow
```
Result entered → Parameter flagged as Critical
       │
       ▼
Critical alert created in lab_notifications table
       │
       ▼
Lab Tech must:
  1. Immediately call the doctor/nurse
  2. Document who was informed
  3. Mark alert as Acknowledged in system
       │
       ▼
Doctor reviews → Adjusts treatment
```

---

## 10. Reports

**File:** `laboratory_view/reports.php`  
**APIs:** `GET /api/laboratory/reports/*`

### Available Reports

| Report | Parameters | Data |
|--------|-----------|------|
| Test Volume | Date range, Category | Tests done per day/category |
| Pending TAT | — | Tests past expected TAT |
| Doctor-wise | Doctor, Date | Tests ordered per doctor |
| Revenue | Date range | Income from lab tests |
| Critical Alerts | Date range | All critical values |
| Sample Rejection | Date range | Rejected/recollected samples |

---

## 11. Security & Permissions

### What Lab Tech CAN Do
```
✅ View all test orders (OPD + IPD)
✅ Update test status (Pending → In Progress → Completed)
✅ Upload test results with parameters
✅ Flag critical values
✅ Manage test service catalog
✅ Generate and print lab reports
✅ Manage lab inventory
✅ View patient demographics
✅ Generate lab reports
```

### What Lab Tech CANNOT Do
```
❌ Create or edit bills
❌ Register patients
❌ Prescribe medications
❌ Access pharmacy
❌ Modify clinical charts
❌ Access financial data
```

---

## 12. Workflow Flowchart

```
LAB TECH LOGS IN
      │
      ▼
LABORATORY DASHBOARD
  ├── View Pending Orders (STAT first, then Urgent, then Routine)
  │
  ├── FOR EACH TEST ORDER:
  │     │
  │     ├── Collect sample from patient
  │     │    (Note: For IPD, nurse may collect and send)
  │     │
  │     ├── Log sample received → Status: In Progress
  │     │
  │     ├── Process in analyzer / manually
  │     │
  │     ├── Enter results parameter by parameter
  │     │    ├── System auto-flags abnormal values
  │     │    └── Critical values → IMMEDIATE ALERT
  │     │
  │     ├── Status → Completed
  │     │
  │     └── Print lab report (or auto-available to doctor)
  │
  ├── CRITICAL ALERTS:
  │     ├── Immediate phone call to attending doctor/nurse
  │     ├── Document communication in system
  │     └── Mark alert acknowledged
  │
  ├── CATALOG MANAGEMENT:
  │     ├── Add new tests as needed
  │     ├── Update prices
  │     └── Update reference ranges
  │
  └── REPORTS:
        ├── Daily test volume
        ├── Pending TAT violations
        └── Revenue summary
```

---

*End of Document — Laboratory Role Documentation*

---
**Document Control** | Version 2.0 | August 2026
