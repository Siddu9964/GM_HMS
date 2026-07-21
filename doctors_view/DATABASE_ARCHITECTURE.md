# Database Architecture - Updated Approach

## ✅ Using Your Existing Tables

You're absolutely right! We'll use your existing `patient_issue_description` table instead of creating duplicates.

## 📊 Table Relationships & Workflow

```
┌─────────────────────────────────────────────────────────────────┐
│                    PATIENT VISIT WORKFLOW                        │
└─────────────────────────────────────────────────────────────────┘

Step 1: INITIAL SYMPTOM CAPTURE (AI-Powered)
┌──────────────────────────────────────┐
│  patient_issue_description (EXISTS)  │
│  ─────────────────────────────────── │
│  • issue_text_raw                    │
│  • issue_text_transcript (voice)     │
│  • symptoms (AI extracted)           │
│  • severity, duration                │
│  • ai_possible_conditions            │
│  • ai_risk_level                     │
│  • ai_confidence_score               │
│  • status: 'draft'                   │
└──────────────────────────────────────┘
           ↓
Step 2: DOCTOR CONSULTATION (SOAP Notes)
┌──────────────────────────────────────┐
│  consultations (NEW - CREATES)       │
│  ─────────────────────────────────── │
│  • issue_description_id (LINKS ↑)   │
│  • soap_subjective                   │
│  • soap_objective                    │
│  • soap_assessment                   │
│  • soap_plan                         │
│  • vital_signs                       │
│  • final_diagnosis                   │
│  • status: 'Completed'               │
└──────────────────────────────────────┘
           ↓
Step 3: PRESCRIPTION
┌──────────────────────────────────────┐
│  prescriptions (NEW - CREATES)       │
│  ─────────────────────────────────── │
│  • consultation_id (LINKS ↑)         │
│  • medicines (JSON array)            │
│  • diagnosis                         │
│  • instructions                      │
└──────────────────────────────────────┘
           ↓
Step 4: LAB ORDERS (if needed)
┌──────────────────────────────────────┐
│  lab_orders (NEW - CREATES)          │
│  ─────────────────────────────────── │
│  • consultation_id (LINKS ↑)         │
│  • test_name                         │
│  • priority, status                  │
└──────────────────────────────────────┘
           ↓
Step 5: LAB RESULTS
┌──────────────────────────────────────┐
│  lab_results (NEW - CREATES)         │
│  ─────────────────────────────────── │
│  • order_id (LINKS ↑)                │
│  • result_data (JSON)                │
│  • abnormal_flags (AI detected)      │
│  • status: 'Pending Review'          │
└──────────────────────────────────────┘
```

## 🔗 Key Relationships

### 1. patient_issue_description → consultations
- **Link**: `consultations.issue_description_id` → `patient_issue_description.sl_no`
- **Purpose**: Connect AI symptom analysis to detailed consultation notes
- **Example**: 
  - Patient enters "Headache and mild fever" (AI analyzes)
  - Doctor reviews AI analysis, examines patient, writes SOAP notes

### 2. consultations → prescriptions
- **Link**: `prescriptions.consultation_id` → `consultations.consultation_id`
- **Purpose**: Link prescription to specific consultation
- **Example**: Based on diagnosis, doctor prescribes medicines

### 3. consultations → lab_orders
- **Link**: `lab_orders.consultation_id` → `consultations.consultation_id`
- **Purpose**: Track which consultation ordered which tests
- **Example**: Doctor orders blood test based on symptoms

### 4. lab_orders → lab_results
- **Link**: `lab_results.order_id` → `lab_orders.order_id`
- **Purpose**: Connect test orders to results
- **Example**: Blood test results uploaded, AI flags abnormal values

## 📋 What Each Table Does

### ✅ patient_issue_description (YOUR EXISTING TABLE)
**Purpose**: AI-powered initial symptom capture
- Patient describes issue (text or voice)
- AI extracts symptoms, severity, affected body parts
- AI suggests possible conditions with confidence scores
- **Status**: draft → reviewed → finalized

### 🆕 consultations (NEW TABLE)
**Purpose**: Detailed SOAP consultation notes
- Extends the initial symptom description
- Doctor's physical examination findings
- Professional clinical assessment
- Treatment plan and follow-up
- **Status**: Draft → Completed → Reviewed

### 🆕 prescriptions (NEW TABLE)
**Purpose**: Medicine prescriptions
- Linked to consultation
- JSON array of medicines with dosage
- Dietary advice, instructions
- **Status**: Active → Completed → Cancelled

### 🆕 lab_orders (NEW TABLE)
**Purpose**: Lab test orders
- Which tests to perform
- Priority (Routine/Urgent/STAT)
- **Status**: Ordered → In Progress → Completed

### 🆕 lab_results (NEW TABLE)
**Purpose**: Lab test results
- Test values (JSON format)
- AI-highlighted abnormal values
- PDF report storage
- **Status**: Pending Review → Reviewed → Critical

### 🆕 notifications (NEW TABLE)
**Purpose**: Doctor alerts and notifications
- Appointment reminders
- Lab results available
- Emergency alerts
- Follow-up reminders

## 🎯 Why This Approach?

### ✅ Advantages:
1. **Uses Your Existing Data**: All 15 patient issues you showed are already there!
2. **Separation of Concerns**: 
   - AI analysis (patient_issue_description)
   - Clinical notes (consultations)
   - Prescriptions (prescriptions)
3. **Traceability**: Full audit trail from symptom to treatment
4. **Flexibility**: Can have multiple consultations for same issue

### 📝 Example Flow:

**Patient: "Headache and mild fever"**

1. **patient_issue_description**:
   ```
   issue_text_raw: "Headache and mild fever"
   symptoms: "Headache, Fever"
   ai_possible_conditions: "Common Cold"
   ai_risk_level: "Low"
   status: "draft"
   ```

2. **Doctor Reviews** → Creates **consultation**:
   ```
   issue_description_id: 1 (links to above)
   soap_subjective: "Patient complains of headache and fever for 2 days"
   soap_objective: "Temp: 100°F, BP: 120/80, Throat: Red"
   soap_assessment: "Viral Fever"
   soap_plan: "Rest, fluids, paracetamol"
   ```

3. **Doctor Prescribes** → Creates **prescription**:
   ```
   consultation_id: "CONS-20251226-001"
   medicines: [
     {name: "Paracetamol", dosage: "500mg", frequency: "3 times/day"}
   ]
   ```

## 🚀 Next Steps

1. **Run the updated SQL** (database_setup.sql)
2. **Refresh dashboard** - errors will be gone
3. **Test with existing data** - Your 15 patient issues will work!

The dashboard will now show:
- ✅ AI symptom analysis from `patient_issue_description`
- ✅ Consultation notes from `consultations`
- ✅ Prescriptions from `prescriptions`
- ✅ Lab orders/results from `lab_orders`/`lab_results`
