# GM_HMS — Pharmacy Role Documentation

---

> **Document Type:** Role-Based User Guide  
> **Role:** Pharmacist  
> **Version:** 2.0.0  
> **Audience:** Management · Pharmacy Staff · Technical Team  
> **Date:** August 2026

---

## Table of Contents
1. [Role Overview](#1-role-overview)
2. [Pharmacy Dashboard](#2-pharmacy-dashboard)
3. [Billing POS (Point of Sale)](#3-billing-pos-point-of-sale)
4. [Prescription Fulfillment](#4-prescription-fulfillment)
5. [Product (Medicine) Master](#5-product-medicine-master)
6. [Stock Receive (GRN)](#6-stock-receive-grn)
7. [Indent Request (Purchase Request)](#7-indent-request-purchase-request)
8. [Purchase Orders](#8-purchase-orders)
9. [Quotation Management](#9-quotation-management)
10. [Suppliers Management](#10-suppliers-management)
11. [Inventory Alerts](#11-inventory-alerts)
12. [IP Orders (IPD Pharmacy)](#12-ip-orders-ipd-pharmacy)
13. [Returns Management](#13-returns-management)
14. [Reports](#14-reports)
15. [Sales History](#15-sales-history)
16. [Settings](#16-settings)
17. [Security & Permissions](#17-security--permissions)
18. [Workflow Flowchart](#18-workflow-flowchart)

---

## 1. Role Overview

The **Pharmacist** manages the complete pharmacy lifecycle — from purchasing medicines to dispensing them to patients. The pharmacy system uses **FIFO (First In, First Out)** inventory management to ensure older stock is used first and medicines don't expire in storage.

### Key Responsibilities
- Dispense medicines to OPD/IPD patients (POS billing)
- Receive and record stock from suppliers (GRN)
- Manage medicine inventory and alerts
- Process purchase indents and orders
- Handle patient medicine returns
- Fulfill IPD pharmacy orders from nurses
- Generate pharmacy reports

### Access Level
```
FULL ACCESS: All pharmacy operations
READ ONLY: Patient data, Prescriptions (for dispensing)
NO ACCESS: Clinical records, Billing management, Staff admin
```

---

## 2. Pharmacy Dashboard

**File:** `pharmacy_view/dashboard.php`  
**API:** `GET /api/pharmacy/dashboard-summary`

### KPI Cards

| Card | Data | Description |
|------|------|-------------|
| Today's Sales | ₹ amount | Total revenue today |
| Pending IP Orders | Count | Nurse-ordered medicines awaiting dispensing |
| Low Stock Items | Count | Medicines below reorder level |
| Expiring Soon | Count | Items expiring within 90 days |
| Today's Transactions | Count | POS billing transactions |

### Dashboard Charts

#### Sales Trend
- Last 7 days sales bar chart
- API: `GET /api/pharmacy/reports/sales?period=7days`

#### Low Stock Alert Panel
- Table of medicines below reorder level
- Quick link to create indent request

#### Pending IP Orders Panel
- IPD medicine orders from nurses
- Quick dispense button

---

## 3. Billing POS (Point of Sale)

**File:** `pharmacy_view/billing_pos.php` (53KB)  
**APIs:**
- `GET /api/pharmacy/billing/patients` — patient search
- `GET /api/pharmacy/billing/products` — product/medicine search
- `GET /api/pharmacy/billing/prescriptions` — load prescription
- `GET /api/pharmacy/billing/sponsors` — insurance/corporate sponsors
- `POST /api/pharmacy/billing/checkout` — process sale

### POS Workflow

```
Step 1: Select Customer Type
        OPD Patient | IPD Patient | Walk-in Cash Customer

Step 2: Search Patient (if patient sale)
        → API: GET /api/pharmacy/billing/patients?q=Ramesh
        → Select patient from dropdown
        → Load any pending prescription

Step 3: Load Prescription (optional)
        → Prescription items auto-populate cart
        → Nurse-ordered items appear if IPD patient
        → API: GET /api/pharmacy/billing/prescriptions?patient_id=PID-...

Step 4: Add Medicines to Cart
        Search medicine → Select
        → API: GET /api/pharmacy/billing/products?q=Amox
        → Shows: Medicine name, batch, expiry, stock, price, MRP
        
        For each medicine:
        ├── Quantity (validates against available stock)
        ├── Unit price (auto-filled from batch)
        └── Discount (if applicable)

Step 5: Cart Summary
        ├── Items list with quantities and prices
        ├── Subtotal
        ├── Discount
        └── Grand Total

Step 6: Sponsor/Insurance (if corporate/insurance patient)
        → GET /api/pharmacy/billing/sponsors
        → Select sponsor
        → Insurance approved amount / corporate credit limit

Step 7: Payment
        ├── Payment mode: Cash/Card/UPI/Insurance/Credit
        ├── Amount received
        └── Change calculation (for cash)

Step 8: Process Checkout
        → POST /api/pharmacy/billing/checkout
        → FIFO stock deduction (oldest batch first)
        → Sale recorded in pharmacy_sales + pharmacy_sale_items
        → Invoice generated with bill number

Step 9: Print Invoice
        → GET /api/pharmacy/billing/print?sale_id=XXX
```

### FIFO Stock Deduction Logic

```
When quantity X is sold:
1. Fetch batches ordered by expiry_date ASC (oldest first)
2. Deduct from batch 1 until exhausted
3. Continue to batch 2, batch 3... until X units deducted
4. Update pharmacy_inventory stock quantities
5. Record batch-wise sale in pharmacy_sale_items

Example:
  Paracetamol 500mg needed: 20 tablets
  Batch A (exp 2026-12): 15 tablets → deduct all 15
  Batch B (exp 2027-06): 5 remaining → deduct 5
  Total: 20 tablets dispensed
```

---

## 4. Prescription Fulfillment

**File:** `pharmacy_view/prescriptions.php` (73KB)  
**API:** `GET /api/pharmacy/prescriptions`

### Features
- View all pending prescriptions from doctors
- Filter: Date | Doctor | Patient | Status (Pending/Partial/Dispensed)
- Click prescription → Open POS with items pre-loaded
- Mark items as dispensed
- Partial dispensing support

### Prescription Status Tracking
| Status | Meaning |
|--------|---------|
| Pending | Not yet dispensed |
| Partial | Some items dispensed |
| Dispensed | All items dispensed |
| Cancelled | Cancelled by doctor |

---

## 5. Product (Medicine) Master

**File:** `pharmacy_view/products.php`  
**APIs:** CRUD via `/api/pharmacy/products`

### Medicine Master Fields

| Field | Description |
|-------|-------------|
| Product Name | Full medicine name with strength |
| Generic Name | Active ingredient name |
| Category | Tablet / Capsule / Syrup / Injection / Cream / etc. |
| Manufacturer | Manufacturer name |
| HSN Code | Tax classification code |
| Unit | Tablet / Capsule / ML / Gram |
| Pack Size | How many units per pack |
| Reorder Level | Alert when stock falls below |
| Max Stock | Maximum stock to maintain |
| MRP | Maximum retail price |
| Rack Location | Where stored in pharmacy |
| Schedule | Schedule H / H1 / X / OTC |

### Import Products

**File:** `pharmacy_view/product_import.php`  
**API:** `POST /api/pharmacy/import/products`

- CSV bulk import of medicine master
- Template download available
- Validation: duplicate check, required fields
- Error report for failed rows

---

## 6. Stock Receive (GRN)

**File:** `pharmacy_view/stock_receive.php` (60KB)  
**APIs:**
- `GET /api/pharmacy/grn` — list all GRNs
- `POST /api/pharmacy/grn` — create new GRN
- `POST /api/pharmacy/grn/bulk-submit` — submit multiple
- `GET /api/pharmacy/grn/{id}` — view specific GRN
- `DELETE /api/pharmacy/grn/{id}` — delete GRN

### GRN Workflow (Stock Receive)

GRN = **Goods Receipt Note** — official record when medicines arrive from supplier.

```
Step 1: Select Supplier
        → GET /api/pharmacy/suppliers
        → Choose from dropdown

Step 2: Enter Invoice Details
        ├── Supplier Invoice Number
        ├── Invoice Date
        └── Purchase Order Number (if ordered via PO)

Step 3: Add Products
        For each medicine received:
        ├── Product Name (search dropdown)
        ├── Batch Number
        ├── Manufacturing Date
        ├── Expiry Date
        ├── Quantity Received
        ├── Free Quantity (bonus from supplier)
        ├── Purchase Price per unit (excl. GST)
        ├── GST Rate (0% / 5% / 12% / 18%)
        ├── GST Amount (calculated)
        ├── MRP (printed on pack)
        └── Total Amount

Step 4: Submit GRN
        → POST /api/pharmacy/grn
        → Each product-batch combination creates new record in pharmacy_inventory
        → Stock incremented accordingly
        → FIFO queue updated (new batch at back of queue)

Step 5: Print GRN (optional)
```

### Stock Received in pharmacy_inventory
```
Each row = 1 batch of 1 product:
  product_id
  batch_number
  manufacturing_date
  expiry_date
  quantity_received
  quantity_available (starts = received, decrements with sales)
  purchase_price
  mrp
  gst_rate
  grn_id (reference back to GRN)
```

---

## 7. Indent Request (Purchase Request)

**File:** `pharmacy_view/indent_request.php` (85KB)  
**APIs:**
- `GET /api/pharmacy/indents` — list indents
- `POST /api/pharmacy/indents` — create indent
- `POST /api/pharmacy/indents/auto-generate` — auto-generate for low stock
- `POST /api/pharmacy/indents/bulk-assign` — assign vendor to multiple items
- `POST /api/pharmacy/indents/mark-sent` — mark as sent to vendor
- `POST /api/pharmacy/indents/dispatch-drafts` — dispatch draft indents

### Indent Workflow

```
Step 1: Auto-Generate OR Manual Creation
        Auto: System scans inventory → finds items below reorder level
        → POST /api/pharmacy/indents/auto-generate
        → Creates draft indent for each low-stock item
        
        Manual: Pharmacist adds medicines needing purchase
        → POST /api/pharmacy/indents

Step 2: Review Draft Indents
        Review quantities needed
        Adjust quantities if needed
        → POST /api/pharmacy/indents/update-qty

Step 3: Assign Vendor
        → POST /api/pharmacy/indents/bulk-assign
        → Each indent item assigned to preferred vendor

Step 4: Dispatch Indents to Vendor
        → POST /api/pharmacy/indents/dispatch-drafts
        → Status: Draft → Sent

Step 5: Mark as Sent
        → POST /api/pharmacy/indents/mark-sent
        → Vendor receives indent (via vendor portal)

Step 6: Vendor Response
        → Vendor submits quotation: POST /api/vendor/quotations

Step 7: Purchase Order Created
        → Approved quotations become Purchase Orders
```

### Indent Item Status Flow
```
DRAFT → DISPATCHED → SENT → QUOTED → ORDERED → RECEIVED
```

### Vendor Portal Integration
Vendors can access a separate portal:
- `GET /api/vendor/indents` — view assigned indents
- `POST /api/vendor/quotations` — submit price quotation

---

## 8. Purchase Orders

**File:** `pharmacy_view/purchase_order.php`  
**APIs:** CRUD via `/api/pharmacy/purchase-orders`

### PO Workflow
```
Quotation Approved → Create Purchase Order
PO sent to vendor
Vendor delivers → GRN created → Stock received
PO marked as Received
```

### PO Fields
| Field | Description |
|-------|-------------|
| PO Number | Auto-generated |
| Supplier | Selected supplier |
| Expected Delivery | Date |
| Items | Product, quantity, agreed price |
| Terms | Payment terms |
| Status | Draft / Sent / Partial / Received / Cancelled |

---

## 9. Quotation Management

**File:** `pharmacy_view/quotation.php`  
**APIs:** CRUD via `/api/pharmacy/quotations`

### Quotation Comparison
- Multiple vendors can quote for same indent
- Side-by-side price comparison
- Select lowest price or preferred vendor
- Approve → Convert to PO

---

## 10. Suppliers Management

**File:** `pharmacy_view/suppliers.php`  
**APIs:** CRUD via `/api/pharmacy/suppliers`

### Supplier Fields
| Field | Description |
|-------|-------------|
| Company Name | Supplier name |
| Contact Person | Key contact |
| Phone | Primary number |
| Email | For quotations/orders |
| Address | Business address |
| GST Number | For tax compliance |
| Drug License No. | Regulatory requirement |
| Payment Terms | Net 30/60/COD/etc. |
| Category | Medicines/Surgical/Both |

---

## 11. Inventory Alerts

**File:** `pharmacy_view/inventory_alerts.php`  
**APIs:**
- `GET /api/pharmacy/low-stock-alerts` — low stock items
- `GET /api/pharmacy/expiry-alerts` — expiring items

### Alert Categories

#### Low Stock Alert
- Items where `quantity_available < reorder_level`
- Action: Create indent request
- Color coding: 🔴 Critical (<50% of reorder) | 🟡 Warning (<reorder)

#### Expiry Alerts
- Items expiring within:
  - 30 days → 🔴 Critical
  - 60 days → 🟠 Warning
  - 90 days → 🟡 Caution
- Actions:
  - Return to supplier (if unopened)
  - Transfer to another branch
  - Document waste

### Auto-Alert System
Dashboard badge shows count of:
- Low stock items
- Expiring within 30 days
- Pending IP orders

---

## 12. IP Orders (IPD Pharmacy)

**File:** `pharmacy_view/ip_orders.php`  
**APIs:**
- `GET /api/pharmacy/ip-orders` — list pending IPD orders
- `POST /api/pharmacy/ip-orders/complete` — mark fulfilled

### IPD Order Flow

```
Nurse Orders Medicine (from ward)
       │
       ▼
Order appears in Pharmacy: ip_orders.php
  - Patient Name | Bed/Ward | Medicine | Quantity | Ordered At
       │
       ▼
Pharmacist Reviews Order
  - Verify medicine availability
  - Check for interactions (manual check)
       │
       ▼
Pharmacist Prepares and Dispenses
  - Pack medicine with patient label
  - Send to ward with transport slip
       │
       ▼
Mark Order Complete
  → POST /api/pharmacy/ip-orders/complete
  → Stock deducted from pharmacy_inventory (FIFO)
  → Charge automatically posted to patient's IPD bill (PHARMACY category)
  → IPD billing master recalculated
```

---

## 13. Returns Management

### 13.1 Supplier Returns (Damaged/Expired)

**File:** `pharmacy_view/returns.php`  
**API:** CRUD via `/api/pharmacy/returns`

- Return damaged or expired medicines to supplier
- Debit note generation
- Stock adjustment
- Reason: Damaged / Expired / Wrong item / Quality issue

### 13.2 Patient Returns (OPD/IPD)

**File:** `pharmacy_view/opd_ipd_returns.php`  
**API:** CRUD via `/api/pharmacy/patient-returns`

- Patients returning unused medicines
- Must be within return window
- Refund calculation (price at which sold)
- IPD: Deducted from patient's bill
- OPD: Cash refund or credit

### 13.3 IPD Returns Verification

**File:** `pharmacy_view/ipd_returns_verification.php`  
**API:** `pharmacy_view/api/get_ipd_return_requests.php`

- Nurse-submitted return requests appear here
- Pharmacist verifies:
  - Medicine condition (sealed/unused)
  - Quantity matches
  - Same batch as dispensed
- Approve → Stock returned to inventory | Bill adjusted
- Reject → With reason (returned to nurse)

---

## 14. Reports

**File:** `pharmacy_view/reports.php`  
**APIs:**
- `GET /api/pharmacy/reports/sales` — sales report
- `GET /api/pharmacy/reports/expiry` — expiry report
- `GET /api/pharmacy/reports/low-stock` — stock alert report
- `GET /api/pharmacy/reports/top-products` — top selling

### Available Reports

| Report | Parameters | Data |
|--------|-----------|------|
| Sales Report | Date range, Doctor, Patient | Total sales, by medicine, by category |
| Expiry Report | Filter by days | All items expiring in X days |
| Low Stock Report | — | All items below reorder |
| Top Products | Date range | Best selling medicines by qty/value |
| Purchase Report | Date range, Supplier | GRN history, amounts |
| Return Report | Date range | Patient returns, supplier returns |
| FIFO Batch Report | Product | Batch-wise stock position |

---

## 15. Sales History

**File:** `pharmacy_view/sales.php`  
**APIs:**
- `GET /api/pharmacy/sales` — list sales
- `GET /api/pharmacy/sales/{id}` — sale details
- `GET /api/pharmacy/sales/{id}/reprint` — reprint invoice

### Features
- Full transaction history
- Filter by: Date, Patient, Doctor, Payment mode
- Click transaction → View items, patient, total, payment
- Reprint invoice for any past sale

---

## 16. Settings

**File:** `pharmacy_view/settings.php`  
**APIs:**
- `GET /api/pharmacy/settings`
- `POST /api/pharmacy/settings`

### Configurable Settings
| Setting | Description |
|---------|-------------|
| Pharmacy Name | Display on invoices |
| Drug License No. | Regulatory |
| GST Number | Tax |
| Default GST Rate | Default for new products |
| Return Window | Days within which returns accepted |
| Low Stock Default | Default reorder level for new products |
| Invoice Prefix | Billing number prefix |
| Enable FIFO | FIFO stock management on/off |

---

## 17. Security & Permissions

### What Pharmacist CAN Do
```
✅ Dispense medicines via POS
✅ View and fulfill prescriptions
✅ Manage medicine master (add/edit/delete)
✅ Receive stock (create GRN)
✅ Create purchase indents
✅ Manage purchase orders
✅ Handle quotations
✅ Manage suppliers
✅ Process patient returns
✅ Fulfill IPD pharmacy orders
✅ Generate pharmacy reports
✅ Manage pharmacy settings
```

### What Pharmacist CANNOT Do
```
❌ Modify patient clinical records
❌ Create or edit medical prescriptions
❌ Access IPD/OPD billing
❌ Register or delete patients
❌ Manage staff or doctors
❌ Access laboratory module
```

---

## 18. Workflow Flowchart

```
PHARMACIST LOGS IN
      │
      ▼
PHARMACY DASHBOARD
  ├── Check Alerts (Low Stock, Expiry, Pending IP Orders)
  │
  ├── OPD DISPENSING:
  │     ├── Patient arrives with prescription
  │     ├── Search patient in POS
  │     ├── Load prescription items to cart
  │     ├── Verify stock availability (FIFO)
  │     ├── Process payment
  │     └── Print invoice + dispense medicines
  │
  ├── IPD ORDER FULFILLMENT:
  │     ├── Check pending IP orders from nurses
  │     ├── Prepare ordered medicines
  │     ├── Mark complete → Charge posted to patient bill
  │     └── Send to ward
  │
  ├── STOCK MANAGEMENT:
  │     ├── Check inventory alerts
  │     ├── Create indent for low-stock items (auto/manual)
  │     ├── Send to vendor → Vendor quotes → PO created
  │     └── Receive stock → Create GRN → Update inventory
  │
  ├── RETURNS PROCESSING:
  │     ├── IPD nurse returns → Verify → Accept/Reject
  │     ├── Patient OPD returns → Refund calculation
  │     └── Supplier returns → Debit note
  │
  └── REPORTS:
        ├── Daily sales summary
        ├── Inventory position
        └── Expiry report
```

---

*End of Document — Pharmacy Role Documentation*

---
**Document Control** | Version 2.0 | August 2026
