# GM_HMS - Hospital Management System
## Project Structure Documentation

### Overview
GM_HMS is a comprehensive Hospital Management System built with PHP, MySQL, and modern web technologies. The system supports multiple user roles with dedicated interfaces for Admin, Doctors, and Reception staff.

---

## Directory Structure

```
GM_HMS/
├── api/                    # API endpoints
├── assets/                 # Public assets (CSS, JS, images)
├── config/                 # Configuration files
├── controler/              # Business logic controllers (18 files)
├── core/                   # Core system files
├── Database/               # Database schema and migrations
├── doctors_view/           # Doctor interface
│   ├── api/
│   ├── assets/
│   ├── includes/          # Navbar, Sidebar
│   ├── consultation.php
│   ├── dashboard.php
│   ├── mypatient.php
│   └── prescription.php
├── logs/                   # System logs
├── middleware/             # Authentication & authorization
├── models/                 # Data models (8 files)
│   ├── AppointmentModel.php
│   ├── Database.php
│   ├── DoctorModel.php
│   ├── InvoiceModel.php
│   ├── IpdBillingModel.php
│   ├── OpdBillingModel.php
│   ├── PatientModel.php
│   └── PrescriptionModel.php
├── nurse_view/             # Nurse interface
├── pharmacy_view/          # Pharmacy interface
├── reception_view/         # Reception interface
│   ├── api/
│   ├── assets/
│   ├── includes/          # Navbar, Sidebar
│   ├── ipd_management/
│   ├── appointment_management.php
│   ├── billing.php
│   ├── patient_registration.php
│   └── opd_management.php
├── security/               # Security utilities
├── setup/                  # Installation scripts
├── temp/                   # Temporary files
├── view/                   # Admin interface
│   ├── assets/
│   ├── includes/          # Navbar, Sidebar
│   ├── admin_dashboard.php
│   ├── doctor_management.php
│   ├── patient_registration.php
│   ├── billing_management.php
│   └── staff_management.php
├── index.php               # Main entry point
├── login.php               # Authentication
└── logout.php              # Session termination
```

---

## Core Components

### 1. Models Layer (`/models`)
**Purpose:** Database interaction and business logic

| Model | Purpose | Key Methods |
|-------|---------|-------------|
| **AppointmentModel** | Manage appointments | `getAllAppointments()`, `createAppointment()`, `updateAppointment()` |
| **DoctorModel** | Doctor management | `getAllDoctors()`, `createDoctor()`, `updateDoctor()` |
| **PatientModel** | Patient records | `getAllPatients()`, `createPatient()`, `searchPatients()` |
| **InvoiceModel** | Invoice generation | `createInvoice()`, `getInvoiceById()`, `getStatistics()` |
| **IpdBillingModel** | IPD billing | `createAdmissionBill()`, `generateDischargeBill()`, `recordPayment()` |
| **OpdBillingModel** | OPD billing | `createBill()`, `recordPayment()`, `calculateTotals()` |
| **PrescriptionModel** | Prescriptions | `getAllPrescriptions()`, `getPrescriptionById()` |
| **Database** | DB connection | `connect()`, `fetchAll()`, `execute()` |

### 2. Controllers Layer (`/controler`)
**Purpose:** Handle HTTP requests and coordinate between models and views

**Total Controllers:** 18 files
- API controllers for AJAX requests
- Business logic orchestration
- Data validation and sanitization

### 3. Views Layer
**Purpose:** User interfaces for different roles

#### Admin View (`/view`)
- Dashboard with KPIs
- Doctor management
- Patient registration
- Staff management
- Billing overview
- Department management

#### Doctors View (`/doctors_view`)
- Personal dashboard
- Patient consultations
- Prescription management
- My patients list
- AI symptom analysis

#### Reception View (`/reception_view`)
- Appointment scheduling
- Patient registration
- OPD management
- IPD management
- Billing and payments
- Doctor availability

---

## Key Features

### 1. Appointment Management
- Token-based queue system
- Doctor availability checking
- Automatic billing integration
- Multiple appointment types (OPD/IPD)

### 2. Billing System
- **OPD Billing:** Outpatient billing with itemized charges
- **IPD Billing:** Inpatient billing with room charges, procedures, medications
- Automatic tax calculation (18% GST)
- Multiple payment methods (Cash, Card, UPI, Insurance)
- Receipt generation

### 3. Patient Management
- Comprehensive patient records
- Search by ID, name, phone, Aadhar
- Medical history tracking
- Consultation records

### 4. Doctor Management
- Profile management
- Specialization tracking
- Availability scheduling
- Consultation fee configuration

### 5. Security Features
- Role-based access control (RBAC)
- Session management
- SQL injection prevention (Prepared statements)
- XSS protection
- CSRF tokens

---

## Database Architecture

### Core Tables
- `patient` - Patient demographics
- `doctors` - Doctor profiles
- `appointments` - Appointment records
- `consultations` - Medical consultations
- `prescriptions` - Prescription data
- `opd_billing_master` - OPD bills
- `opd_billing_items` - OPD bill line items
- `ipd_billing_master` - IPD bills
- `ipd_billing_items` - IPD bill line items
- `ipd_admissions` - IPD admission records
- `payment_receipts` - Payment records
- `departments` - Hospital departments
- `settings` - System configuration

---

## Technology Stack

### Backend
- **PHP 7.4+** - Server-side scripting
- **MySQL 5.7+** - Database
- **PDO** - Database abstraction
- **Namespaces** - Code organization

### Frontend
- **HTML5/CSS3** - Structure and styling
- **JavaScript/jQuery** - Client-side interactivity
- **Bootstrap 4** - UI framework
- **DataTables** - Table management
- **Select2** - Enhanced dropdowns

### Security
- **Prepared Statements** - SQL injection prevention
- **Password Hashing** - bcrypt
- **Session Management** - Secure sessions
- **Input Validation** - Server-side validation

---

## User Roles

### 1. Admin
- Full system access
- User management
- System configuration
- Reports and analytics

### 2. Doctor
- Patient consultations
- Prescription management
- View assigned patients
- Update availability

### 3. Reception
- Patient registration
- Appointment booking
- Billing operations
- IPD/OPD management

### 4. Nurse
- Patient care records
- Medication administration
- Vital signs tracking

### 5. Pharmacy
- Medication dispensing
- Inventory management
- Prescription fulfillment

---

## API Endpoints

### Patient APIs
- `GET /api/patients` - List patients
- `POST /api/patients` - Create patient
- `GET /api/patients/{id}` - Get patient details
- `PUT /api/patients/{id}` - Update patient

### Appointment APIs
- `GET /api/appointments` - List appointments
- `POST /api/appointments` - Create appointment
- `PUT /api/appointments/{id}` - Update appointment

### Billing APIs
- `POST /api/billing/opd` - Create OPD bill
- `POST /api/billing/ipd` - Create IPD bill
- `POST /api/payments` - Record payment

---

## Configuration

### Database Configuration (`/config`)
```php
$host = 'localhost';
$db_name = 'hmsci';
$username = 'root';
$password = '';
```

### System Settings
- Hospital name and logo
- Tax rates
- Payment methods
- Department configuration

---

## Recent Updates (2026-02-13)

### Model Layer Cleanup
✅ **AppointmentModel.php** - Cleaned and optimized
- Improved code structure
- Better documentation
- Consistent formatting
- Enhanced error handling

### Pending Cleanup
⏳ DoctorModel.php
⏳ PatientModel.php
⏳ InvoiceModel.php
⏳ IpdBillingModel.php
⏳ OpdBillingModel.php
⏳ PrescriptionModel.php
⏳ Database.php

---

## Development Guidelines

### Code Standards
1. Use PSR-4 autoloading
2. Follow PSR-12 coding style
3. Use type hints where possible
4. Document all public methods
5. Use prepared statements for all queries

### Security Best Practices
1. Validate all user input
2. Sanitize output
3. Use parameterized queries
4. Implement CSRF protection
5. Regular security audits

### Testing
1. Unit tests for models
2. Integration tests for APIs
3. Manual testing for UI
4. Load testing for performance

---

## Maintenance

### Regular Tasks
- Database backup (Daily)
- Log rotation (Weekly)
- Security updates (Monthly)
- Performance optimization (Quarterly)

### Monitoring
- Error logs (`/logs`)
- Database performance
- User activity
- System resources

---

## Support & Documentation

### Internal Documentation
- `DATABASE_ARCHITECTURE.md` - Database schema
- `MEDICATION_DROPDOWN_DOCS.md` - Medication system
- `SETUP_GUIDE.md` - Installation guide
- `HEADER_DOCUMENTATION.md` - UI components

### External Resources
- PHP Documentation: https://www.php.net/docs.php
- MySQL Documentation: https://dev.mysql.com/doc/
- Bootstrap Documentation: https://getbootstrap.com/docs/

---

**Last Updated:** 2026-02-13  
**Version:** 2.0.0  
**Maintainer:** Development Team
