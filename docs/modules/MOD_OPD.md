# GM_HMS — OPD Module Documentation

---

> **Document Type:** Module Reference  
> **Module:** Out-Patient Department (OPD)  
> **Version:** 2.0.0  
> **Audience:** All Stakeholders  
> **Date:** August 2026

---

## Table of Contents
1. [Module Overview](#1-module-overview)
2. [Business Purpose](#2-business-purpose)
3. [Database Tables](#3-database-tables)
4. [OPD Patient Journey](#4-opd-patient-journey)
5. [Appointment Booking](#5-appointment-booking)
6. [OPD Queue Management](#6-opd-queue-management)
7. [Vitals Recording](#7-vitals-recording)
8. [Doctor Consultation (SOAP)](#8-doctor-consultation-soap)
9. [OPD Billing](#9-opd-billing)
10. [Functions Documentation](#10-functions-documentation)
11. [Validation Rules](#11-validation-rules)
12. [Module Flowchart](#12-module-flowchart)

---

## 1. Module Overview

The **OPD (Out-Patient Department)** module manages the complete outpatient visit lifecycle — from appointment booking through consultation, investigation, prescription, and billing.

### Files Involved

| File | Role | Purpose |
|------|------|---------|
| `reception_view/appointment_management.php` | Reception | Book & manage appointments |
| `reception_view/opd_management.php` | Reception | Live OPD queue |
| `reception_view/opd_billing.php` | Reception | Create OPD bills |
| `doctors_view/opd_patients.php` | Doctor | View my OPD patients |
| `doctors_view/consultation.php` | Doctor | SOAP notes + prescription |
| `view/opd_info.php` | Admin | OPD encounter overview |
| `controler/api/AppointmentController.php` | API | Appointment CRUD |
| `controler/api/OpdController.php` | API | OPD operations |
| `controler/api/OpdBillingController.php` | API | OPD billing |
| `controler/api/ConsultationController.php` | API | SOAP notes |
| `models/AppointmentModel.php` | Model | Appointment logic |
| `models/OpdBillingModel.php` | Model | Billing logic |

---

## 2. Business Purpose

| Goal | Achieved Through |
|------|-----------------|
| Reduce waiting time | Token-based queue system |
| Paperless records | Digital SOAP notes |
| Accurate billing | Itemized services + duplicate detection |
| Continuity of care | Full patient history accessible |
| Doctor analytics | Per-doctor visit and revenue tracking |

---

## 3. Database Tables

### Primary Tables

| Table | Purpose |
|-------|---------|
| `appointments` | Appointment records |
| `consultations` | SOAP notes |
| `prescriptions` | Medications issued |
| `opd_billing_master` | Bill headers |
| `opd_billing_items` | Bill line items |
| `patient` | Patient demographics |
| `doctors` | Doctor profiles |
| `departments` | Department catalog |

---

## 4. OPD Patient Journey

```
Patient Arrives at Reception
         │
         ▼
Search Patient in System
   ├── Found → Select existing patient
   └── Not found → Register new patient
         │
         ▼
Check Doctor Availability
   → GET /api/appointments/check-availability
   → Doctor must have in_time and out_time set for the day
         │
         ▼
Book Appointment
   → POST /api/appointments
   → Token auto-assigned (sequential per doctor per day)
   → Appointment ID: APT-YYYYMMDD-XXXX
         │
         ▼
Patient Waits → Called by Token Number
         │
         ▼
Receptionist: Mark Patient Arrived
   → PUT /api/appointments/{id}
   → Status: Scheduled → Arrived
         │
         ▼
Receptionist: Enter Vitals
   → POST /api/opd/vitals
   → BP, Temp, Pulse, SpO2, Weight, Height
         │
         ▼
Doctor: Starts Consultation
   → Opens consultation.php?appointment_id=APT-...
   → Sees: Patient history, today's vitals, previous prescriptions
         │
         ▼
Doctor: Enter SOAP Notes
   → S: Chief complaint
   → O: Clinical findings
   → A: Diagnosis
   → P: Treatment plan
         │
         ▼
Doctor: Issue Prescription
   → Add medications with dosage/frequency/duration
   → POST /api/prescriptions
         │
         ▼
Doctor: Order Lab Tests (if needed)
   → POST /api/laboratory/orders
         │
         ▼
Appointment Status → Completed
   → PUT /api/appointments/{id}
         │
         ▼
Receptionist: Generate OPD Bill
   → POST /api/billing/opd
   → Services: Consultation fee + additional tests/services
   → Collect payment
   → Print receipt
         │
         ▼
Patient Exits / Follow-up Scheduled
```

---

## 5. Appointment Booking

### AppointmentModel::createAppointment($data)

```php
createAppointment($data):
  1. Validate required: patient_id, doctor_id, appointment_date, appointment_time
  2. Check doctor exists: SELECT from doctors WHERE doctor_id = ?
  3. Check doctor availability for date:
     - doctor.in_time and out_time must not be NULL or '00:00:00'
     - If unavailable: status auto-set to 'Doctor On Leave'
  4. Generate appointment_id: APT-YYYYMMDD-XXXX
  5. Generate token_number:
     SELECT COUNT(*) FROM appointments 
     WHERE doctor_id = ? AND appointment_date = ?
     token = count + 1
  6. INSERT into appointments
  7. Return {appointment_id, token_number}
```

### Appointment ID Format
```
APT-YYYYMMDD-XXXX
Example: APT-20260814-0042
```

### Doctor Availability Check

```php
getAvailableDoctors($date):
  SELECT d.*, 
    CASE WHEN (d.in_time IS NULL OR d.out_time IS NULL 
              OR d.in_time = '00:00:00' OR d.out_time = '00:00:00')
    THEN 'Unavailable' ELSE 'Available' END as availability
  FROM doctors d
  WHERE d.status = 'Active'
```

---

## 6. OPD Queue Management

### Live Queue Display

```php
getLiveQueue():
  SELECT a.*, p.first_name, p.last_name, d.full_name as doctor_name
  FROM appointments a
  JOIN patient p ON a.patient_id = p.patient_id
  JOIN doctors d ON a.doctor_id = d.doctor_id
  WHERE a.appointment_date = CURDATE()
  ORDER BY a.token_number ASC, a.appointment_time ASC
```

### Queue Status Flow
```
Scheduled → Arrived → In-Progress → Completed
     └─────────────────────────────→ Cancelled
     └─────────────────────────────→ No-Show
```

---

## 7. Vitals Recording

### Vitals Saved To
```
Table: patient_vitals (or vitals table)
Fields:
  - patient_id, appointment_id
  - blood_pressure_systolic, blood_pressure_diastolic
  - temperature (°F)
  - pulse_rate (/min)
  - respiratory_rate (/min)
  - spo2 (%)
  - weight (kg)
  - height (cm)
  - recorded_at (timestamp)
  - recorded_by
```

---

## 8. Doctor Consultation (SOAP)

### ConsultationModel::createConsultation($data)

```php
createConsultation($data):
  1. Validate: appointment_id, patient_id, doctor_id
  2. Check: no existing consultation for this appointment
  3. INSERT into consultations:
     - soap_subjective (S)
     - soap_objective (O)
     - soap_assessment (A)
     - soap_plan (P)
     - diagnosis
     - follow_up_date
  4. UPDATE appointments SET status = 'Completed'
  5. Return consultation_id
```

### Voice-to-Text Integration

```
POST /api/consultations/translate-audio
Content-Type: multipart/form-data
Field: audio (audio/webm or audio/mp3)

→ File saved temporarily
→ Sent to Groq Whisper API
→ Transcription text returned
→ Deleted from server after transcription
→ Text inserted into SOAP field
```

---

## 9. OPD Billing

### OpdBillingModel::createBill($billData, $items)

```php
createBill():
  1. Duplicate check:
     - Same patient + same date + same item names?
     - If YES → throw Exception (prevent double billing)
  
  2. Generate bill_id (sequential)
  
  3. Generate receipt_no: ORC-YYYY-NNN
  
  4. BEGIN TRANSACTION
  
  5. INSERT opd_billing_master with:
     - patient_id, doctor_id, bill_date, bill_time
     - discount_amount, discount_percentage
     - payment_mode, receipt_no
  
  6. For each item:
     INSERT opd_billing_items:
       - bill_id, item_name, service_id
       - quantity, unit_price, total
  
  7. Calculate totals:
     - gross = SUM(items)
     - net = gross - discount
     - tax = net × tax_rate
  
  8. UPDATE opd_billing_master SET
     total_amount = net + tax
     payment_status = 'Paid' (if payment received)
  
  9. COMMIT
  
  10. Return {bill_id, receipt_no, total_amount}
```

### OPD Bill ID Format
```
OPDBILL-YYYYMMDD-NNN
or
OPD-20260814-001 (varies by implementation)
```

### Duplicate Bill Detection
```php
// OpdBillingModel::createBill()
// Check if same patient has bill today with same items:
$existingBills = $db->fetchAll(
    "SELECT obm.bill_id, obi.item_name 
     FROM opd_billing_master obm
     JOIN opd_billing_items obi ON obm.bill_id = obi.bill_id
     WHERE obm.patient_id = ? AND obm.bill_date = ?",
    [$patientId, $billDate]
);

$existingItemNames = array_map(fn($r) => strtolower(trim($r['item_name'])), $existingBills);

foreach ($items as $newItem) {
    $newName = strtolower(trim($newItem['item_name'] ?? ''));
    if (in_array($newName, $existingItemNames)) {
        throw new Exception("Duplicate bill: {$existingBillId} already exists today");
    }
}
```

---

## 10. Functions Documentation

### AppointmentModel::generateBillId()
- **Purpose:** Generate unique appointment ID
- **Format:** APT-YYYYMMDD-XXXX
- **Returns:** String

### AppointmentModel::getTokenNumber($doctorId, $date)
- **Purpose:** Get next token for doctor on given date
- **Logic:** COUNT + 1
- **Returns:** Integer

### OpdBillingModel::generateBillId()
- **Purpose:** Generate unique bill ID
- **Returns:** String

### OpdBillingModel::generateORCNumber()
- **Purpose:** Generate official receipt number
- **Format:** ORC-YYYY-NNNNN
- **Returns:** String

### OpdBillingModel::getStatistics($dateFrom, $dateTo)
- **Purpose:** Revenue analytics
- **Returns:** Total bills, total revenue, average per bill, by payment mode

### OpdController::analyzeSymptoms($data)
- **Purpose:** AI symptom analysis
- **Calls:** Groq API or Gemini API
- **Returns:** Differential diagnoses, recommended tests, urgency

---

## 11. Validation Rules

| Field | Rule |
|-------|------|
| patient_id | Must exist in patient table |
| doctor_id | Must exist in doctors table |
| appointment_date | Cannot be past date (for booking) |
| appointment_time | Must be within doctor's in_time–out_time |
| consultation_fee | Must be > 0 |
| bill items | At least 1 item required |
| payment_mode | Must be valid enum value |

---

## 12. Module Flowchart

```
┌─────────────────────────────────────────────────┐
│              OPD MODULE FLOW                     │
└────────────────┬────────────────────────────────┘
                 │
      ┌──────────▼──────────┐
      │  Patient Registration│
      │  (if new patient)    │
      └──────────┬──────────┘
                 │
      ┌──────────▼──────────┐
      │  Appointment Booking │
      │  Token Assigned      │
      └──────────┬──────────┘
                 │
      ┌──────────▼──────────┐
      │  OPD Queue (Live)   │
      │  Token Display      │
      └──────────┬──────────┘
                 │
      ┌──────────▼──────────┐
      │  Vitals Entry       │
      │  (Receptionist)     │
      └──────────┬──────────┘
                 │
      ┌──────────▼──────────┐
      │  SOAP Consultation  │
      │  (Doctor)           │
      ├── Prescription      │
      └── Lab Orders ───────┤
                 │           │
      ┌──────────▼──────────┐ │
      │  OPD Billing        │ │
      │  Payment Collection │ │
      └──────────┬──────────┘ │
                 │             │
      ┌──────────▼──────────┐ │
      │  Lab Processing     │◄┘
      │  (Laboratory)       │
      └─────────────────────┘
```

---

*End of Document — OPD Module Documentation*

---
**Document Control** | Version 2.0 | August 2026
