# GM_HMS — CHANGELOG

---

> **Document Type:** Version History  
> **Project:** GM Hospital Management System  
> **Format:** Semantic Versioning  

---

## [2.0.0] — August 2026

### 🆕 New Features
- **Discharge Notification System:** Nurses can now send discharge requests directly to Admin dashboard via `send_discharge_notification.php`. Admin sees real-time alerts and can clear them after processing.
- **IPD Modular Billing Engine:** Complete rewrite of IPD billing into modular classes: `IpdBillingMaster`, `IpdBillingItem`, `IpdPayment`, `IpdInsurance` — replacing the monolithic billing approach.
- **AI Symptom Analysis:** Integration with Google Gemini and Groq LLM for differential diagnosis suggestions from symptom input.
- **Voice-to-Text Consultation:** Groq Whisper API integration for audio transcription directly into SOAP notes.
- **IPD Insurance Module:** Dedicated `ipd_insurance` table and API for TPA/insurance billing with approved amount tracking.
- **Multi-Branch Database Support:** `SecureDatabase` now auto-selects database based on `HTTP_X_HOSPITAL_BRANCH` header or session variable.
- **Auto Bed Charge Generation (`catchUpBedCharges`):** Automated 24-hour cycle room rent generation — never miss a day's charges.
- **Refund Module:** Formal refund workflow for IPD with mandatory `refund_reason` and `approved_by` fields.
- **FIFO Pharmacy Inventory:** Pharmacy POS now uses First-In-First-Out batch selection (oldest expiry dispensed first).
- **Vendor Portal:** External vendors can submit quotations against indents via `/api/vendor/` endpoints.
- **IPD Returns Verification:** Pharmacist can verify nurse-submitted medicine return requests before processing.

### 🔧 Improvements
- `OpdBillingModel::createBill()` — Added duplicate detection: prevents double billing for same patient, same date, same items
- `PatientModel::getAllPatients()` — Status filter now properly excludes `Inactive` patients by default
- `AppointmentModel::getAllAppointments()` — Added `CASE` logic to auto-detect `Doctor On Leave` status
- `SecureDatabase::execute()` — Added bool → int conversion for MySQL compatibility
- `IpdBillingMaster::recalculateMaster()` — Improved to handle NULL payments gracefully
- Lab test orders now show ward/bed information for IPD patients

### 🐛 Bug Fixes
- Fixed session not being regenerated on login (session fixation vulnerability)
- Fixed ROOM_RENT duplicate generation for same date when billing page refreshed rapidly
- Fixed Select2 patient search not working when name contains special characters
- Fixed pharmacy FIFO not selecting correct batch when multiple batches have same expiry date

### ⚠️ Known Issues (v2.0.0)
- `dismiss_discharge_notification.php` uses raw `new mysqli()` instead of `SecureDatabase` — to be fixed in v2.1.0
- Food charge `₹570` hardcoded in `catchUpBedCharges()` — should be configurable via `settings` table
- Duplicate IPD model classes exist in `/models/` and `/reception_view/ipd_management/models/` — consolidation planned

---

## [1.5.0] — June 2026

### 🆕 New Features
- **IPD Summary (Discharge Summary):** Doctor can now create formal discharge summaries via `nurse_view/ipd_summary.php`
- **Nurse K-Sheet:** Complete vital signs chart with trend visualization
- **MAR (Medication Administration Record):** `nurse_view/medication.php` — full medication tracking per shift
- **Clinical Charts:** Dialysis, Oxygen, Ventilation, and Blood Transfusion chart recording
- **Kanban Board for Lab:** `laboratory_view/kanban.php` for visual lab workflow management
- **OT Billing:** `view/ot_billing.php` for Operation Theatre billing

### 🔧 Improvements
- PatientModel updated with advanced city/state/blood group filters
- Lab results now auto-flag critical values with visual indicators
- Pharmacy alerts improved with 30/60/90 day expiry categories

---

## [1.2.0] — April 2026

### 🆕 New Features
- **Laboratory Module (LIS):** Full laboratory information system with test catalog, orders, and results
- **Nurse Shift Scheduling:** `view/nurse_duty_scheduler.php` and API
- **Doctor Analytics:** Per-doctor performance metrics via `/api/doctors/{id}/analytics`
- **Patient Photo Upload:** Profile photos for patient records

### 🔧 Improvements
- OPD billing advanced view with referral type and sponsor tracking
- Prescription history accessible from patient profile

---

## [1.0.0] — February 2026

### 🆕 Initial Release Features
- **Patient Registration** with unique PID format
- **Appointment Booking** with token system
- **OPD Management** (queue, vitals, consultation)
- **SOAP Consultation Notes**
- **Basic Prescription Management**
- **Doctor Management**
- **Staff Management**
- **Department Management**
- **Basic IPD Admissions**
- **IPD Bed Management**
- **Basic OPD Billing**
- **Basic IPD Billing**
- **Pharmacy POS (basic)**
- **Pharmacy GRN**
- **Pharmacy Indents**
- **Role-Based Access Control** (5 roles)
- **Multi-role Login System**

---

*End of Changelog — GM_HMS*
