# GM_HMS — Complete API Reference

---

> **Document Type:** API Reference  
> **Version:** 2.0.0  
> **Base URL:** `http://localhost/GM_HMS/api`  
> **Authentication:** Session-based (PHP sessions)  
> **Response Format:** `application/json`  
> **Date:** August 2026

---

## Table of Contents
1. [Authentication APIs](#1-authentication-apis)
2. [Patient APIs](#2-patient-apis)
3. [Appointment APIs](#3-appointment-apis)
4. [OPD APIs](#4-opd-apis)
5. [OPD Billing APIs](#5-opd-billing-apis)
6. [IPD Billing Master APIs](#6-ipd-billing-master-apis)
7. [IPD Billing Item APIs](#7-ipd-billing-item-apis)
8. [IPD Payment APIs](#8-ipd-payment-apis)
9. [IPD Insurance APIs](#9-ipd-insurance-apis)
10. [IPD Summary APIs](#10-ipd-summary-apis)
11. [IPD Clinical APIs](#11-ipd-clinical-apis)
12. [Doctor APIs](#12-doctor-apis)
13. [Consultation APIs](#13-consultation-apis)
14. [Prescription APIs](#14-prescription-apis)
15. [Staff APIs](#15-staff-apis)
16. [Nurse Shift APIs](#16-nurse-shift-apis)
17. [Pharmacy APIs](#17-pharmacy-apis)
18. [Laboratory APIs](#18-laboratory-apis)
19. [Admin APIs](#19-admin-apis)
20. [Notification APIs](#20-notification-apis)
21. [Miscellaneous APIs](#21-miscellaneous-apis)
22. [Nurse-Specific APIs](#22-nurse-specific-apis)
23. [Standard Response Formats](#23-standard-response-formats)

---

## Standard Response Format

### Success Response
```json
{
  "success": true,
  "message": "Operation successful",
  "data": { ... }
}
```

### Error Response
```json
{
  "success": false,
  "error": "Error message",
  "details": ["Validation error 1", "Validation error 2"]
}
```

### HTTP Status Codes Used
| Code | Meaning |
|------|---------|
| 200 | OK — Request successful |
| 201 | Created — Resource created |
| 400 | Bad Request — Validation failed |
| 401 | Unauthorized — Not logged in |
| 403 | Forbidden — Insufficient role |
| 404 | Not Found — Resource not found |
| 405 | Method Not Allowed |
| 429 | Too Many Requests — Rate limited |
| 500 | Internal Server Error |

---

## 1. Authentication APIs

### POST /api/auth/login
**Purpose:** Login and establish session  
**Auth Required:** No

**Request Body:**
```json
{
  "username": "admin",
  "password": "Admin@1234"
}
```

**Success Response:**
```json
{
  "status": "success",
  "role": "Admin",
  "user": {
    "id": 1,
    "username": "admin",
    "full_name": "Admin User"
  },
  "redirect_url": "/GM_HMS/view/admin_dashboard.php"
}
```

**Role → Redirect Mapping:**
| Role | Redirect URL |
|------|-------------|
| Admin | `/view/admin_dashboard.php` |
| Doctor | `/doctors_view/dashboard.php` |
| Receptionist | `/reception_view/index.php` |
| Nurse | `/nurse_view/dashboard.php` |
| Pharmacist | `/pharmacy_view/dashboard.php` |

---

### POST /api/auth/logout
**Auth Required:** Yes

**Response:**
```json
{ "success": true, "message": "Logged out successfully" }
```

---

### POST /api/auth/change-password
**Auth Required:** Yes

**Request Body:**
```json
{
  "current_password": "OldPass@123",
  "new_password": "NewPass@456"
}
```
> Password must be ≥ 8 characters

---

### GET /api/auth/me
**Auth Required:** Yes  
**Response:** Current user info with role and permissions

---

## 2. Patient APIs

### GET /api/patients
**Purpose:** List all patients with pagination and filters  
**Auth Required:** Yes

**Query Parameters:**
| Param | Type | Description |
|-------|------|-------------|
| `page` | int | Page number (default: 1) |
| `limit` | int | Records per page (default: 10) |
| `search` | string | Search by name/ID/phone/Aadhar |
| `gender` | string | Filter by gender (Male/Female) |
| `status` | string | Active/Inactive |
| `doctor_id` | string | Filter by doctor |
| `city` | string | Filter by city |

**Response:**
```json
{
  "success": true,
  "data": {
    "patients": [
      {
        "patient_id": "PID-20260814-001",
        "first_name": "Ramesh",
        "last_name": "Kumar",
        "age": 45,
        "sex": "Male",
        "phone": "9876543210",
        "aadhar": "1234 5678 9012",
        "blood_group": "B+",
        "address": "Bangalore",
        "status": "Active",
        "doctor_name": "Dr. Sharma",
        "latest_appointment_status": "Completed"
      }
    ],
    "pagination": {
      "page": 1,
      "limit": 10,
      "total": 250,
      "pages": 25
    }
  }
}
```

---

### GET /api/patients/{patient_id}
**Purpose:** Get single patient details  
**Example:** `GET /api/patients/PID-20260814-001`

---

### POST /api/patients
**Purpose:** Register a new patient

**Request Body:**
```json
{
  "first_name": "Ramesh",
  "last_name": "Kumar",
  "age": 45,
  "sex": "Male",
  "phone": "9876543210",
  "aadhar": "1234 5678 9012",
  "blood_group": "B+",
  "address": "Bangalore",
  "city": "Bangalore",
  "email": "ramesh@email.com",
  "emergency_contact": "9876543211"
}
```

**Response:**
```json
{
  "success": true,
  "message": "Patient registered successfully",
  "data": { "patient_id": "PID-20260814-001" }
}
```

---

### PUT /api/patients/{patient_id}
**Purpose:** Update patient details

---

### DELETE /api/patients/{patient_id}
**Purpose:** Soft-delete patient (sets status = Inactive)

---

### GET /api/patients/check-duplicate
**Purpose:** Check for duplicate patient by Aadhar or phone

**Query:** `?aadhar=1234+5678+9012` or `?phone=9876543210`

---

### GET /api/patients/{id}/lab-results
**Purpose:** Get all lab test results for a patient

---

### POST /api/patients/{id}/image
**Purpose:** Upload patient photo  
**Content-Type:** `multipart/form-data`  
**Field:** `image` (file)

---

## 3. Appointment APIs

### GET /api/appointments
**Purpose:** List appointments  

**Query Parameters:**
| Param | Description |
|-------|-------------|
| `status` | Scheduled/Completed/Cancelled |
| `doctor_id` | Filter by doctor |
| `date` | Specific date (YYYY-MM-DD) |
| `date_from` | Date range start |
| `date_to` | Date range end |
| `type` | OPD/Emergency/Walk-in |

---

### POST /api/appointments
**Purpose:** Book a new appointment

**Request Body:**
```json
{
  "patient_id": "PID-20260814-001",
  "doctor_id": "DOC-001",
  "appointment_date": "2026-08-15",
  "appointment_time": "10:00",
  "appointment_type": "OPD",
  "reason": "Fever and cough",
  "consultation_fee": 500,
  "payment_mode": "Cash"
}
```

**Response:** Includes `appointment_id` (APT-YYYYMMDD-XXXX) and `token_number`

---

### PUT /api/appointments/{id}
**Purpose:** Update appointment status or details

---

### DELETE /api/appointments/{id}
**Purpose:** Cancel appointment

---

### GET /api/appointments/check-availability
**Query:** `?doctor_id=DOC-001&date=2026-08-15`  
**Purpose:** Check if doctor is available on a given date

---

### GET /api/appointments/stats
**Purpose:** Get appointment statistics for dashboard

---

## 4. OPD APIs

### GET /api/opd/queue
**Purpose:** Get live OPD queue for today  
**Returns:** All appointments for today ordered by token number

---

### GET /api/opd/encounter/{appointment_id}
**Purpose:** Get full encounter details for an OPD appointment  
**Returns:** Patient info, vitals, SOAP notes, prescriptions, lab results

---

### POST /api/opd/vitals
**Purpose:** Save patient vitals at OPD counter

**Request Body:**
```json
{
  "appointment_id": "APT-20260814-0001",
  "patient_id": "PID-20260814-001",
  "blood_pressure": "120/80",
  "temperature": 98.6,
  "pulse": 72,
  "spo2": 99,
  "weight": 70,
  "height": 170,
  "respiratory_rate": 16
}
```

---

### POST /api/opd/invoice
**Purpose:** Create OPD invoice from encounter

---

### POST /api/opd/analyze-symptoms
**Purpose:** AI-powered symptom analysis (Groq/Gemini)

**Request Body:**
```json
{
  "symptoms": "Patient has fever 102°F, cough for 5 days, shortness of breath",
  "patient_age": 45,
  "patient_sex": "Male"
}
```

**Response:**
```json
{
  "success": true,
  "analysis": {
    "possible_conditions": ["Pneumonia", "Bronchitis", "COVID-19"],
    "recommendations": "Chest X-ray, CBC, CRP levels",
    "urgency": "Moderate"
  }
}
```

---

## 5. OPD Billing APIs

### GET /api/billing/opd
**Purpose:** List all OPD bills with filters

**Query Parameters:**
| Param | Description |
|-------|-------------|
| `patient_id` | Filter by patient |
| `date_from` | Start date |
| `date_to` | End date |
| `status` | Paid/Pending/Partial |
| `search` | Search by bill ID or patient name |

---

### POST /api/billing/opd
**Purpose:** Create OPD bill

**Request Body:**
```json
{
  "patient_id": "PID-20260814-001",
  "doctor_id": "DOC-001",
  "doctor_name": "Dr. Sharma",
  "appointment_id": "APT-20260814-0001",
  "bill_date": "2026-08-14",
  "payment_mode": "Cash",
  "discount_amount": 0,
  "items": [
    {
      "item_name": "Consultation Fee",
      "quantity": 1,
      "unit_price": 500,
      "total": 500
    },
    {
      "item_name": "Blood Test - CBC",
      "quantity": 1,
      "unit_price": 300,
      "total": 300
    }
  ]
}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "bill_id": "OPD-20260814-0001",
    "receipt_no": "ORC-2026-001",
    "total_amount": 800,
    "payment_status": "Paid"
  }
}
```

---

### POST /api/billing/opd/payment
**Purpose:** Record payment against an OPD bill

**Request Body:**
```json
{
  "bill_id": "OPD-20260814-0001",
  "amount": 800,
  "payment_mode": "UPI",
  "reference_no": "UPI-TXN-12345"
}
```

---

### GET /api/billing/opd/stats
**Purpose:** Get OPD billing statistics  
**Returns:** Total bills, revenue, paid/pending counts

---

### GET /api/billing/opd/services
**Purpose:** Get list of available OPD services with prices

---

### GET /api/billing/opd/consultation-fee
**Query:** `?doctor_id=DOC-001`  
**Purpose:** Get a doctor's consultation fee

---

## 6. IPD Billing Master APIs

### GET /api/ipd-billing-master
**Purpose:** Handles multiple actions based on `action` query param

| `action` | Description |
|----------|-------------|
| `get_by_bill` | Get bill master by bill_id |
| `get_by_admission` | Get bill by admission_id |
| `get_all_bills` | List all bills with filters |
| `dashboard_stats` | Financial summary statistics |
| `search_admissions` | Select2 search for active admissions |

---

### POST /api/ipd-billing-master
| `action` | Description |
|----------|-------------|
| `get_or_create` | Create billing master for admission |
| `apply_discount` | Apply flat or percentage discount |
| `update_bill_type` | Change SELF/INSURANCE/CORPORATE |
| `sync_clinical` | Sync clinical records to billing |

**Apply Discount Body:**
```json
{
  "action": "apply_discount",
  "bill_id": "BILL-20260814-0001",
  "discount_amt": 500,
  "discount_pct": 0,
  "reason": "Doctor's concession",
  "updated_by": "admin"
}
```

---

### PUT /api/ipd-billing-master
| `action` | Description |
|----------|-------------|
| `update_status` | Update billing status (OPEN/FINALIZED/CANCELLED) |

---

## 7. IPD Billing Item APIs

### GET /api/ipd-billing-items
| `action` | Description |
|----------|-------------|
| `get_by_bill` | All items for a bill |
| `category_summary` | Grouped totals by charge type |
| `preview_room_rent` | Preview room rent without saving |

---

### POST /api/ipd-billing-items
| `action` | Description |
|----------|-------------|
| `add_item` | Add single charge item |
| `generate_room_rent` | Bulk generate daily room rent entries |
| `cancel_item` | Cancel (not delete) a charge item |
| `update_total` | Modify an item's total amount |

**Add Item Body:**
```json
{
  "action": "add_item",
  "bill_id": "BILL-20260814-0001",
  "admission_id": "ADM-20260814-0001",
  "patient_id": "PID-20260814-001",
  "charge_type": "LAB",
  "charge_date": "2026-08-14",
  "description": "Blood CBC + LFT",
  "unit_price": 800,
  "quantity": 1,
  "created_by": "receptionist"
}
```

**Charge Types:**
| Type | Category |
|------|----------|
| `ROOM_RENT` | Room & food charges |
| `DOCTOR_VISIT` | Doctor visit fees |
| `LAB` | Laboratory charges |
| `RADIOLOGY` | Radiology charges |
| `PHARMACY` | Medicine charges |
| `OT` | Operation theatre |
| `PROCEDURE` | Surgical procedures |
| `CONSUMABLE` | Consumable items |
| `OTHER` / `MISC` | Miscellaneous |

---

### DELETE /api/ipd-billing-items
**Body:** `{ "item_id": 123, "updated_by": "admin" }`  
**Note:** Sets status = CANCELLED (never physically deletes)

---

## 8. IPD Payment APIs

### GET /api/ipd-payment
| `action` | Description |
|----------|-------------|
| `get_by_bill` | Payment history for a bill |
| `get_summary` | Aggregated payment summary |

---

### POST /api/ipd-payment
| `action` | Description |
|----------|-------------|
| `record_payment` | Record ADVANCE or FINAL payment |
| `record_insurance` | Record insurance payment receipt |
| `record_refund` | Record refund (requires reason + approval) |

**Record Payment Body:**
```json
{
  "action": "record_payment",
  "bill_id": "BILL-20260814-0001",
  "admission_id": "ADM-20260814-0001",
  "patient_id": "PID-20260814-001",
  "payment_type": "ADVANCE",
  "payment_mode": "CASH",
  "amount": 5000,
  "reference_no": null,
  "remarks": "Advance at admission",
  "created_by": "receptionist"
}
```

**Payment Types:**
| Type | Meaning |
|------|---------|
| `ADVANCE` | Payment before final settlement |
| `FINAL` | Final settlement payment |
| `REFUND` | Refund to patient |

**Payment Modes:**
`CASH` | `CARD` | `UPI` | `NEFT` | `CHEQUE` | `INSURANCE`

---

## 9. IPD Insurance APIs

### GET /api/ipd-insurance
**Query:** `?bill_id=BILL-20260814-0001`

### POST /api/ipd-insurance
**Purpose:** Create or update insurance policy details

**Body:**
```json
{
  "bill_id": "BILL-20260814-0001",
  "insurance_company": "Star Health",
  "policy_number": "POL-12345",
  "approval_number": "APR-67890",
  "approved_amount": 50000
}
```

---

## 10. IPD Summary APIs

### GET /api/ipd-summary
**Query:** `?patient_id=PID-...&admission_id=ADM-...`  
**Purpose:** Get discharge summary for an IPD admission

### GET /api/ipd-summary/draft
**Purpose:** Get in-progress/draft summary

### POST /api/ipd-summary
**Purpose:** Save/update discharge summary

**Body:**
```json
{
  "patient_id": "PID-20260814-001",
  "admission_id": "ADM-20260814-001",
  "chief_complaint": "Chest pain and breathlessness",
  "history_of_illness": "Sudden onset chest pain for 2 days",
  "diagnosis": "Acute Myocardial Infarction",
  "treatment": "Aspirin, Heparin drip, Statin therapy",
  "operative_procedure": null,
  "condition_at_discharge": "Stable and improved",
  "discharge_date": "2026-08-14",
  "discharge_instructions": "Avoid exertion. Follow-up in 1 week.",
  "follow_up_date": "2026-08-21",
  "status": "Final"
}
```

### DELETE /api/ipd-summary
**Body:** `{ "summary_id": 12 }`

---

## 11. IPD Clinical APIs

### GET /api/ipd-clinical/visits
**Query:** `?admission_id=ADM-...`

### POST /api/ipd-clinical/visits
**Purpose:** Record a doctor visit for an IPD patient

### GET /api/ipd-clinical/medications
**Query:** `?admission_id=ADM-...`

### POST /api/ipd-clinical/medications
**Purpose:** Add medication to patient's IPD record

### GET /api/ipd-clinical/investigations
**Query:** `?admission_id=ADM-...`

### POST /api/ipd-clinical/investigations
**Purpose:** Add investigation (lab test) to IPD record

---

## 12. Doctor APIs

### GET /api/doctors
**Purpose:** List all doctors

**Query:** `?department_id=DEPT-001&status=Active`

---

### GET /api/doctors/{id}/analytics
**Purpose:** Doctor performance analytics (patients seen, revenue)

---

### GET /api/doctors/{id}/opd-patients
**Purpose:** Get OPD patient list for a doctor

### GET /api/doctors/{id}/ipd-patients
**Purpose:** Get IPD patient list for a doctor

---

### POST /api/doctors
**Purpose:** Register a new doctor

**Body:**
```json
{
  "full_name": "Dr. Ravi Sharma",
  "specialization": "Cardiology",
  "department_id": "DEPT-001",
  "phone": "9876543210",
  "email": "ravi.sharma@hospital.com",
  "consultation_fee": 500,
  "in_time": "09:00",
  "out_time": "17:00"
}
```

---

## 13. Consultation APIs

### GET /api/consultations/{appointment_id}
**Purpose:** Get SOAP notes for an appointment

### POST /api/consultations
**Purpose:** Create SOAP consultation notes

**Body:**
```json
{
  "appointment_id": "APT-20260814-0001",
  "patient_id": "PID-20260814-001",
  "doctor_id": "DOC-001",
  "soap_subjective": "Patient complains of chest pain and breathlessness for 2 days",
  "soap_objective": "BP 140/90, HR 95, SpO2 95%. Bilateral crepts heard.",
  "soap_assessment": "Suspected Acute MI / Congestive Heart Failure",
  "soap_plan": "ECG, Troponin, Echo. Start ASA + Furosemide. Cardiology referral.",
  "diagnosis": "Acute MI",
  "follow_up_date": "2026-08-21"
}
```

### POST /api/consultations/translate-audio
**Purpose:** Transcribe voice recording to text (Whisper AI)  
**Content-Type:** `multipart/form-data`  
**Field:** `audio` (audio file)

---

## 14. Prescription APIs

### GET /api/prescriptions/patient/{patient_id}
**Purpose:** Get full prescription history for a patient

### GET /api/prescriptions/patient/{id}/latest
**Purpose:** Get most recent prescription

### GET /api/prescriptions/doctor/{doctor_id}
**Purpose:** Get all prescriptions issued by a doctor

### POST /api/prescriptions
**Purpose:** Issue a new prescription

**Body:**
```json
{
  "consultation_id": "CONS-001",
  "patient_id": "PID-20260814-001",
  "doctor_id": "DOC-001",
  "medications": [
    {
      "medicine_name": "Paracetamol 500mg",
      "brand": "Calpol",
      "dosage": "1-0-1",
      "duration": "5 days",
      "instructions": "After food"
    }
  ],
  "special_instructions": "Avoid NSAIDs. Increase fluid intake."
}
```

---

## 15. Staff APIs

### GET /api/staff
**Purpose:** List all staff members  
**Query:** `?role=nurse&department_id=DEPT-001`

### POST /api/staff
**Purpose:** Register new staff member

**Body:**
```json
{
  "full_name": "Priya Nurse",
  "role": "Nurse",
  "department_id": "DEPT-001",
  "phone": "9876543210",
  "email": "priya@hospital.com",
  "designation": "Senior Nurse",
  "joining_date": "2026-08-14"
}
```

### GET /api/staff/designations
**Purpose:** Get list of all staff designations

---

## 16. Nurse Shift APIs

### GET /api/nurse-shifts
**Purpose:** List shift assignments  
**Query:** `?nurse_id=&ward_id=&date=`

### POST /api/nurse-shifts
**Purpose:** Create shift assignment

**Body:**
```json
{
  "nurse_id": 25,
  "ward_id": "WARD-001",
  "shift_type": "Morning",
  "start_time": "07:00",
  "end_time": "15:00",
  "date": "2026-08-15"
}
```

### GET /api/nurse-shifts/nurses
**Purpose:** List all nurses for shift assignment

### GET /api/nurse-shifts/wards
**Purpose:** List all wards

### GET /api/nurse-shifts/rooms
**Purpose:** List all rooms in a ward  
**Query:** `?ward_id=WARD-001`

### GET /api/nurse-shifts/floors
**Purpose:** List all floors

---

## 17. Pharmacy APIs

### GET /api/pharmacy/dashboard
**Purpose:** Pharmacy dashboard KPIs

**Response:**
```json
{
  "today_sales": 15000,
  "pending_orders": 5,
  "low_stock_items": 12,
  "expiring_soon": 8
}
```

---

### GET/POST /api/pharmacy/products
**Purpose:** Medicine master CRUD

**POST Body:**
```json
{
  "product_name": "Paracetamol 500mg",
  "generic_name": "Paracetamol",
  "category": "Analgesic",
  "unit": "Tablet",
  "reorder_level": 100,
  "hsn_code": "30049099"
}
```

---

### POST /api/pharmacy/billing/checkout
**Purpose:** Process pharmacy POS sale

**Body:**
```json
{
  "patient_id": "PID-20260814-001",
  "items": [
    {
      "product_id": 101,
      "batch_id": 501,
      "quantity": 10,
      "unit_price": 5.50,
      "total": 55.00
    }
  ],
  "payment_mode": "Cash",
  "discount": 0
}
```

---

### POST /api/pharmacy/grn
**Purpose:** Create Goods Receipt Note (stock received from supplier)

**Body:**
```json
{
  "supplier_id": 10,
  "invoice_number": "INV-2026-001",
  "invoice_date": "2026-08-14",
  "items": [
    {
      "product_id": 101,
      "batch_number": "BATCH-001",
      "expiry_date": "2028-12-31",
      "quantity": 500,
      "purchase_price": 4.50,
      "mrp": 6.00
    }
  ]
}
```

---

### POST /api/pharmacy/indents
**Purpose:** Create purchase indent (request for medicines)

### POST /api/pharmacy/indents/auto-generate
**Purpose:** Auto-generate indents for low stock items

### POST /api/pharmacy/indents/bulk-assign
**Purpose:** Bulk assign vendor to indent items

---

### GET/POST /api/pharmacy/patient-returns
**Purpose:** Process patient medicine returns (OPD/IPD)

---

### GET /api/pharmacy/reports/sales
### GET /api/pharmacy/reports/expiry
### GET /api/pharmacy/reports/low-stock
### GET /api/pharmacy/reports/top-products

---

## 18. Laboratory APIs

### GET /api/laboratory/services
**Purpose:** Get test catalog with pricing

### POST /api/laboratory/services
**Purpose:** Add new lab test service

### GET /api/laboratory/orders
**Purpose:** List OPD lab test orders

**Query:** `?status=Pending&date=2026-08-14`

### POST /api/laboratory/orders
**Purpose:** Create lab test order

**Body:**
```json
{
  "patient_id": "PID-20260814-001",
  "appointment_id": "APT-20260814-0001",
  "tests": [
    { "service_id": "LAB-001", "test_name": "CBC" },
    { "service_id": "LAB-002", "test_name": "LFT" }
  ],
  "ordered_by": "DOC-001",
  "priority": "Routine"
}
```

### PUT /api/laboratory/orders/{id}/status
**Purpose:** Update test order status (Pending → InProgress → Completed)

**Body:**
```json
{ "status": "Completed" }
```

### POST /api/laboratory/orders/{id}/result
**Purpose:** Upload test results

**Body:**
```json
{
  "parameters": [
    { "name": "Hemoglobin", "value": "13.5", "unit": "g/dL", "normal_range": "12-16", "flag": "Normal" },
    { "name": "WBC", "value": "12000", "unit": "/cumm", "normal_range": "4000-11000", "flag": "High" }
  ],
  "remarks": "Elevated WBC — suggest clinical correlation",
  "reported_by": "Lab Tech 1"
}
```

### GET /api/laboratory/ipd-orders
**Purpose:** List IPD lab test orders

### GET /api/laboratory/notifications
**Purpose:** Critical lab value notifications

---

## 19. Admin APIs

### GET /api/admin/dashboard-summary
**Purpose:** Main dashboard KPIs

**Response:**
```json
{
  "today_opd": 45,
  "today_ipd_admissions": 3,
  "total_beds": 120,
  "occupied_beds": 87,
  "today_revenue": 125000,
  "pending_bills": 12,
  "active_doctors": 15
}
```

### GET /api/admin/bed-availability
**Response:** All beds with occupancy status, patient names

### GET /api/admin/analytics
**Purpose:** Revenue analytics over date range

### GET /api/admin/active-departments
**Purpose:** List departments with active doctors count

---

## 20. Notification APIs

### GET /api/notifications
**Purpose:** Get all notifications for current user

### GET /api/notifications/unread-count
**Response:** `{ "count": 5 }`

### POST /api/notifications/mark-read
**Body:** `{ "notification_ids": [1, 2, 3] }`

### POST /api/notifications
**Purpose:** Create a new notification

---

## 21. Miscellaneous APIs

### GET /api/hospital-beds
**Purpose:** Get all hospital beds with current patient info  
**No route params required**

**Response includes:** ward_name, room_name, bed_number, room_type, patient_id, patient_name, patient_phone

---

### GET /api/departments
### POST /api/departments
### PUT /api/departments/{id}
### DELETE /api/departments/{id}

---

### GET /api/referred-doctors
### POST /api/referred-doctors
**Purpose:** Manage external referring doctors

---

### POST /api/payment/clinical-billing-sync
**Purpose:** Sync clinical records from nursing charts to IPD billing items  
**Triggered when:** Billing page opened or manual sync requested

---

### GET /api/vendor/indents
**Purpose:** Vendor portal — view open indent requests

### POST /api/vendor/quotations
**Purpose:** Vendor submits price quotation against indent

---

## 22. Nurse-Specific APIs

*Located at: `nurse_view/api/`*

### POST nurse_view/api/save_vitals.php
**Purpose:** Save patient vitals from nurse station

### POST nurse_view/api/save_clinical_record.php
**Purpose:** Save clinical chart data (Dialysis, Oxygen, Ventilation, Blood Transfusion)

### POST nurse_view/api/save_pharmacy_order.php
**Purpose:** Nurse places pharmacy order for IPD patient

### POST nurse_view/api/submit_pharmacy_return.php
**Purpose:** Nurse submits medicine return request

### GET nurse_view/api/get_clinical_records.php
**Query:** `?admission_id=ADM-...`  
**Purpose:** Fetch all clinical chart entries for an IPD patient

### POST nurse_view/api/send_discharge_notification.php
**Purpose:** Nurse sends discharge notification to Admin

**Body (form-data):**
```
patient_id: PID-20260814-001
admission_id: ADM-20260814-001
```

**What it does:**
1. Fetches patient name from DB
2. Inserts record into `discharge_notifications` table with status = 'Pending'
3. Admin sees notification on dashboard
4. Admin dismisses via `view/api/dismiss_discharge_notification.php`

---

### GET nurse_view/api/search_ipd_patient.php
**Query:** `?q=Ramesh`  
**Purpose:** Search IPD patients by name/ID

### GET nurse_view/api/search_medicine.php
**Query:** `?q=Paracetamol`  
**Purpose:** Medicine autocomplete for nurse orders

### GET nurse_view/api/search_tests.php
**Query:** `?q=CBC`  
**Purpose:** Lab test search for nurse test orders

---

## 23. Standard Response Formats

### Paginated List Response
```json
{
  "success": true,
  "data": {
    "items": [...],
    "pagination": {
      "page": 1,
      "limit": 25,
      "total": 150,
      "pages": 6
    }
  }
}
```

### Financial Summary Response (IPD)
```json
{
  "bill_id": "BILL-20260814-0001",
  "room_charges": 5000,
  "doctor_charges": 1500,
  "lab_charges": 2000,
  "pharmacy_charges": 3500,
  "other_charges": 500,
  "subtotal": 12500,
  "discount_amount": 500,
  "grand_total": 12000,
  "insurance_approved_amount": 5000,
  "patient_payable": 7000,
  "amount_paid": 7000,
  "balance_due": 0,
  "payment_status": "Paid",
  "billing_status": "FINALIZED",
  "total_days": 5
}
```

---

*End of Document — GM_HMS API Reference*

---
**Document Control**  
| Version | Date | Changes |
|---------|------|---------|
| 2.0 | Aug 2026 | Complete API documentation |
