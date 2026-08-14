# GM_HMS — Doctor Role Documentation

---

> **Document Type:** Role-Based User Guide  
> **Role:** Doctor  
> **Version:** 2.0.0  
> **Audience:** Management · Technical Team · Doctors  
> **Date:** August 2026

---

## Table of Contents
1. [Role Overview](#1-role-overview)
2. [Login & Session](#2-login--session)
3. [Doctor Dashboard](#3-doctor-dashboard)
4. [My OPD Patients](#4-my-opd-patients)
5. [My IPD Patients](#5-my-ipd-patients)
6. [Consultation (SOAP Notes)](#6-consultation-soap-notes)
7. [Prescription Management](#7-prescription-management)
8. [AI Symptom Analysis](#8-ai-symptom-analysis)
9. [Security & Permissions](#9-security--permissions)
10. [Workflow Flowchart](#10-workflow-flowchart)

---

## 1. Role Overview

The **Doctor** role allows clinical staff to view their assigned patients, conduct SOAP consultations, issue prescriptions, and order investigations. Doctors have a read-only view of billing but cannot create or modify bills.

### Key Responsibilities
- Review patient medical history before consultation
- Enter SOAP (Subjective, Objective, Assessment, Plan) notes
- Issue digital prescriptions with generic and brand medicine names
- Order lab tests and investigations
- Monitor IPD patients' daily progress
- Use AI assistance for symptom analysis and diagnosis support

### Access Level
```
FULL ACCESS: Consultations, Prescriptions, My Patients (OPD+IPD)
READ ONLY: Lab results, Billing details
NO ACCESS: Patient registration, Staff management, Pharmacy stock
```

---

## 2. Login & Session

**Login URL:** `http://localhost/GM_HMS/login.php`  
**Role Code:** `doctor`  
**Redirect After Login:** `/GM_HMS/doctors_view/dashboard.php`

### Session Variables
```php
$_SESSION['role']       = 'doctor'
$_SESSION['doctor_id']  = 'DOC-001'  // Doctor's unique ID
$_SESSION['user_id']    = 15          // User table ID
$_SESSION['full_name']  = 'Dr. Ravi Sharma'
```

---

## 3. Doctor Dashboard

**File:** `doctors_view/dashboard.php`  
**APIs Called:**
- `GET /api/doctors/{doctor_id}/analytics`
- `GET /api/appointments?doctor_id=&date=today`
- `GET /api/doctors/{doctor_id}/ipd-patients`

### Dashboard Sections

#### Today's OPD Schedule
- List of today's appointments ordered by token
- Columns: Token | Patient Name | Time | Reason | Status | Action
- Quick action: **Start Consultation**

#### My IPD Patients
- All currently admitted patients under this doctor
- Columns: Patient | Bed | Ward | Days Admitted | Last Visit | Action
- Quick action: **View Patient** | **Add Visit Note**

#### Performance Metrics
| Metric | Description |
|--------|-------------|
| Today's Patients | Appointments for today |
| This Month OPD | Total OPD this month |
| IPD Patients | Currently admitted |
| Completed Today | Consultations done today |

---

## 4. My OPD Patients

**File:** `doctors_view/opd_patients.php`  
**API:** `GET /api/doctors/{doctor_id}/opd-patients`

### Filters Available
- Date range
- Status: All / Completed / Scheduled
- Search by patient name

### Patient Card Shows
- Name, Age, Gender, Chief Complaint
- Last vitals (BP, Temp, SpO2)
- Appointment token and status
- Action: Open Consultation

---

## 5. My IPD Patients

**File:** `doctors_view/ipd_patients.php`  
**API:** `GET /api/doctors/{doctor_id}/ipd-patients`

### Patient List Shows
- Patient name, age, gender
- Current ward and bed number
- Admission date and days admitted
- Primary diagnosis
- Last nurse vitals update
- Action buttons: View Details | Add Note | Order Tests

---

## 6. Consultation (SOAP Notes)

**File:** `doctors_view/consultation.php`  
**Query:** `?appointment_id=APT-20260814-0001`

### SOAP Framework

SOAP is a structured medical documentation format:

| Letter | Stands For | Content |
|--------|-----------|---------|
| **S** | Subjective | What the patient tells the doctor |
| **O** | Objective | What the doctor observes/measures |
| **A** | Assessment | Diagnosis / Clinical impression |
| **P** | Plan | Treatment plan, investigations, referrals |

### Consultation Page Layout

#### Left Panel: Patient Information
- Name, age, gender, blood group
- Today's vitals (entered by nurse/receptionist)
- Previous consultation history
- Current medications

#### Center Panel: SOAP Entry

```
SUBJECTIVE (S):
┌────────────────────────────────────────────┐
│ Chief Complaint:                           │
│ [Patient complains of fever for 3 days,   │
│  cough, and breathlessness...]             │
│                                            │
│ History of Present Illness:               │
│ [Sudden onset fever 102°F, productive     │
│  cough with yellow sputum...]              │
└────────────────────────────────────────────┘

OBJECTIVE (O):
┌────────────────────────────────────────────┐
│ Temp: 102°F  BP: 130/85  PR: 98/min       │
│ SpO2: 94%   RR: 22/min  Wt: 70kg         │
│                                            │
│ Examination Findings:                     │
│ [Bilateral crepts in lung bases.          │
│  No wheeze. Throat hyperemic...]          │
└────────────────────────────────────────────┘

ASSESSMENT (A):
┌────────────────────────────────────────────┐
│ [Provisional Diagnosis:]                  │
│ 1. Community-acquired Pneumonia            │
│ 2. Rule out COVID-19                      │
└────────────────────────────────────────────┘

PLAN (P):
┌────────────────────────────────────────────┐
│ [Treatment Plan:]                         │
│ 1. Chest X-ray + CBC + CRP               │
│ 2. Amoxicillin 500mg TID × 7 days        │
│ 3. Paracetamol 500mg SOS                 │
│ 4. Review after 3 days                   │
│ 5. Admit if SpO2 < 92%                   │
└────────────────────────────────────────────┘
```

### Voice-to-Text Feature
```
Doctor clicks microphone icon next to any SOAP field
→ Browser captures audio
→ POST /api/consultations/translate-audio (multipart/form-data)
→ Groq Whisper API transcribes audio
→ Text auto-inserted into SOAP field
```

### Save Consultation
```
POST /api/consultations
Body: {
  appointment_id, patient_id, doctor_id,
  soap_subjective, soap_objective,
  soap_assessment, soap_plan,
  diagnosis, follow_up_date
}
→ Saved to 'consultations' table
→ consultation_id returned for prescription linking
```

### Order Lab Tests
From consultation page, doctor can order investigations:
```
Click "Order Tests" → Select tests from catalog
→ POST /api/laboratory/orders
→ Test order created with status 'Pending'
→ Lab technician sees the order
```

### Follow-up Scheduling
Doctor sets follow-up date:
```
follow_up_date saved with consultation
Reception sees follow-up dates in appointment calendar
```

---

## 7. Prescription Management

**File:** `doctors_view/prescription.php`  
**APIs:**
- `POST /api/prescriptions` — create prescription
- `GET /api/prescriptions/patient/{id}` — history
- `POST /api/prescriptions/log-print` — log when printed

### Prescription Page

#### Medicine Selection
Medicines have a **generic-to-brand mapping**:

```
Doctor searches: "Paracetamol"
System shows:
  Generic: Paracetamol 500mg
  Brands: Calpol 500mg | Dolo 650 | Crocin 650 | Paracip 500
Doctor selects brand (or leaves as generic)
```

This is documented in `doctors_view/MEDICATION_DROPDOWN_DOCS.md`

#### Prescription Form
| Field | Description |
|-------|-------------|
| Medicine Name | Generic or brand |
| Dosage | e.g., 500mg, 1 tablet |
| Frequency | OD / BD / TID / QID / SOS / PRN |
| Route | Oral / IV / IM / Topical |
| Duration | e.g., 5 days, 1 week |
| Instructions | Before food / After food / With water |
| Remarks | Special notes for patient |

#### Dosage Frequency Codes
| Code | Meaning | Times/Day |
|------|---------|-----------|
| OD | Once Daily | 1 |
| BD | Twice Daily | 2 |
| TID | Three times daily | 3 |
| QID | Four times daily | 4 |
| SOS | As needed (pain/fever) | As required |
| PRN | As needed | As required |
| HS | At bedtime | 1 |

### Save Prescription
```
POST /api/prescriptions
Body: {
  consultation_id,
  patient_id,
  doctor_id,
  medications: [
    { medicine_name, brand, dosage, route, frequency, duration, instructions }
  ],
  special_instructions
}
```

### Prescription History
Doctor can view all past prescriptions for a patient:
- `GET /api/prescriptions/patient/{patient_id}`
- Shows: Date, medications, doctor name, follow-up

### Print Prescription
- Letterhead with hospital logo, doctor name, registration number
- Patient details, date
- Medication table
- Doctor signature space
- API call to log print: `POST /api/prescriptions/log-print`

---

## 8. AI Symptom Analysis

**File:** `doctors_view/ai_symptom_analysis.php`  
**API:** `POST /api/opd/analyze-symptoms`

### How It Works

```
Step 1: Doctor enters patient symptoms in text area
        OR uses voice-to-text to dictate

Step 2: System prepares context:
        - Patient age, gender
        - Chief complaints
        - Vital signs if available
        - Any lab results

Step 3: POST /api/opd/analyze-symptoms
        → Sent to Groq (primary) or Google Gemini (fallback)
        → LLM processes the clinical context

Step 4: AI Response displayed:
        ├── Possible Diagnoses (ranked by probability)
        ├── Key findings to look for
        ├── Recommended investigations
        ├── Suggested medications (informational)
        └── Urgency assessment: Routine / Urgent / Emergency

Step 5: Doctor reviews AI suggestions
        (AI is an aid, not a replacement for clinical judgment)
```

### AI Response Format
```json
{
  "possible_conditions": [
    { "name": "Community Acquired Pneumonia", "probability": "High" },
    { "name": "COVID-19", "probability": "Moderate" },
    { "name": "Acute Bronchitis", "probability": "Low" }
  ],
  "recommended_investigations": ["Chest X-ray", "CBC", "CRP", "COVID RT-PCR"],
  "treatment_suggestions": ["Amoxicillin", "Azithromycin if atypical"],
  "urgency": "Urgent",
  "notes": "Patient with SpO2 94% needs monitoring. Consider hospitalization if not improving."
}
```

> ⚠️ **Clinical Note:** AI suggestions are informational only. All clinical decisions must be made by the qualified doctor based on complete clinical assessment.

---

## 9. Security & Permissions

### What Doctor CAN Do
```
✅ View own schedule (OPD + IPD patients)
✅ View any patient's medical history
✅ Create/edit SOAP consultation notes
✅ Issue prescriptions
✅ Order lab tests
✅ Use AI symptom analysis
✅ View lab results
✅ View patient vitals
✅ Use voice-to-text for documentation
```

### What Doctor CANNOT Do
```
❌ Register new patients
❌ Book appointments
❌ Create or modify bills
❌ Access pharmacy inventory
❌ Manage other doctors' profiles
❌ View financial data
❌ Schedule nurse shifts
```

### Page Guard
```php
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'doctor') {
    header('Location: /GM_HMS/login.php');
    exit();
}
```

---

## 10. Workflow Flowchart

```
DOCTOR LOGS IN
      │
      ▼
DASHBOARD
  ├── View Today's OPD Queue
  │     │
  │     └── Click Patient → CONSULTATION PAGE
  │           │
  │           ├── Review: Previous history, vitals, medications
  │           │
  │           ├── Enter SOAP Notes:
  │           │    ├── S: Chief complaint (voice or type)
  │           │    ├── O: Examination findings + vitals
  │           │    ├── A: Diagnosis / Assessment
  │           │    └── P: Treatment plan
  │           │
  │           ├── AI Assist (optional):
  │           │    └── Enter symptoms → Get AI differential diagnosis
  │           │
  │           ├── Issue Prescription:
  │           │    └── Select medicines → Set dosage/frequency/duration
  │           │
  │           ├── Order Lab Tests:
  │           │    └── Select tests → Send to Lab
  │           │
  │           └── Set Follow-up Date → Save
  │
  └── View My IPD Patients
        │
        ├── Daily Rounds: View patient → Add daily note
        │
        ├── Review Lab Results posted by Lab Tech
        │
        ├── Update treatment plan
        │
        └── Decide discharge → Inform Nurse → Nurse sends notification
```

---

*End of Document — Doctor Role Documentation*

---
**Document Control** | Version 2.0 | August 2026
