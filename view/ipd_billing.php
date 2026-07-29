<?php
session_start();
require_once '../config/SecurityConfig.php';
require_once '../security/EncryptionManager.php';
require_once '../Database/SecureDatabase.php';
use GM_HMS\Database\SecureDatabase;

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

$pageTitle = 'IPD Billing Terminal';
$userRole  = $_SESSION['role'] ?? 'admin';
$userName  = $_SESSION['username'] ?? 'Admin';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IPD Billing Terminal — GM HMS</title>
    <meta name="description" content="IPD Billing Terminal for GM Hospital Management System">
    <link rel="stylesheet" href="/GM_HMS/assets/css/gm-theme.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <link rel="stylesheet" href="assets/css/admin_common.css">
    <link rel="stylesheet" href="assets/css/ipd_billing.css?v=<?= time() ?>">
    <style>
        /* Strict Color Palette Enforcement: #1f6b4a (Primary Green) and #f3efe6 (Cream) */
        :root {
            --primary-color: #1f6b4a;
            --bg-color: #f3efe6;
        }
        
        body, .bg-slate-50, .billing-workspace, .billing-search-zone, .billing-empty-state, .panel-card, .financial-summary-card, .patient-header-card, .quick-stats-bar {
            background-color: var(--bg-color) !important;
            color: var(--primary-color) !important;
        }

        /* Modals and Overlays */
        .billing-modal {
            background-color: var(--bg-color) !important;
            color: var(--primary-color) !important;
            border: 2px solid var(--primary-color) !important;
        }
        .bm-head, .bm-head.green-head, .bm-head.blue-head {
            background-color: var(--primary-color) !important;
            color: var(--bg-color) !important;
        }
        .bm-head .bm-close { color: var(--bg-color) !important; }
        
        /* Inputs and Selects */
        input, select, textarea {
            background-color: var(--bg-color) !important;
            color: var(--primary-color) !important;
            border: 1px solid var(--primary-color) !important;
        }
        input::placeholder, textarea::placeholder { color: rgba(31, 107, 74, 0.6) !important; }

        /* Buttons */
        button, .btn-add-charge, .btn-room-rent, .btn-add-payment, .btn-ins-receipt, .phc-btn, .fs-btn, .bm-btn {
            background-color: var(--bg-color) !important;
            color: var(--primary-color) !important;
            border: 1px solid var(--primary-color) !important;
            transition: all 0.2s ease;
        }
        button:hover, .btn-add-charge:hover, .btn-room-rent:hover, .btn-add-payment:hover, .bm-btn-primary, .bm-btn-green, .fs-btn:hover {
            background-color: var(--primary-color) !important;
            color: var(--bg-color) !important;
        }
        
        /* Table overrides */
        .billing-items-table th {
            background-color: var(--primary-color) !important;
            color: var(--bg-color) !important;
        }
        .billing-items-table td, .billing-items-table tr {
            border-bottom: 1px solid rgba(31, 107, 74, 0.2) !important;
            color: var(--primary-color) !important;
        }
        
        /* Text classes overrides */
        .green, .red, .amber, .blue, .bold { color: var(--primary-color) !important; font-weight: bold; }
        
        /* Shortcut Highlights */
        kbd {
            background-color: var(--primary-color) !important;
            color: var(--bg-color) !important;
            padding: 4px 8px;
            border-radius: 4px;
            font-family: monospace;
            font-weight: bold;
            font-size: 0.9em;
            box-shadow: 0 2px 0 rgba(0,0,0,0.2);
            border: 1px solid var(--primary-color);
            margin: 0 2px;
        }
        
        .search-zone-hint {
            background-color: rgba(31, 107, 74, 0.1);
            padding: 10px 15px;
            border-radius: 6px;
            border: 1px dashed var(--primary-color);
            display: inline-block;
            margin-top: 15px;
            font-weight: 500;
        }

        /* Toasts */
        .toast-success, .toast-error, .toast-info, .toast-warning {
            background-color: var(--bg-color) !important;
            color: var(--primary-color) !important;
            border-left: 4px solid var(--primary-color) !important;
            box-shadow: 0 4px 12px rgba(31, 107, 74, 0.2) !important;
        }
    </style>
</head>
<body class="bg-slate-50">
    <div class="flex h-screen overflow-hidden">
        <!-- Sidebar -->
        <?php include 'includes/sidebar.php'; ?>
        
        <div class="flex-1 flex flex-col overflow-hidden">
            <!-- Navbar -->
            <?php include 'includes/navbar.php'; ?>

            <main class="flex-1 ipd-billing-page" id="ipdBillingPage">
                
                <!-- ═══════════ ZONE 1: TOP SEARCH BAR ═══════════ -->
                <div class="billing-search-zone" id="billingSearchZone">
                    <div class="search-zone-inner">
                        <div class="search-zone-title">
                            <i data-lucide="hospital" class="search-zone-icon"></i>
                            <div style="display:flex; justify-content:space-between; align-items:center; width:100%;">
                                <div>
                                    <h1>IPD Billing Terminal</h1>
                                    <p>Search by Patient Name, UHID, Admission ID, or Mobile</p>
                                </div>
                                <button class="bm-btn" style="background:var(--blue-600); color:white; border:none; padding:8px 16px;" onclick="billing.openDischargeHistory()">
                                    <i data-lucide="history"></i> Discharge History
                                </button>
                            </div>
                        </div>
                        <div class="search-zone-input-wrap">
                            <i data-lucide="search" class="search-icon"></i>
                            <input
                                type="text"
                                id="admissionSearchInput"
                                class="admission-search-input"
                                placeholder="Search Patient..."
                                autocomplete="off"
                            >
                            <div id="admissionSearchDropdown" class="admission-search-dropdown"></div>
                        </div>
                        <div class="search-zone-hint">
                            <i data-lucide="keyboard"></i>
                            Press <kbd>P</kbd>=Payment &nbsp; <kbd>A</kbd>=Add Charge &nbsp; <kbd>B</kbd>=Bed Rent &nbsp; <kbd>Esc</kbd>=Close
                        </div>
                    </div>
                </div>

                <!-- ═══════════ ZONE 2: EMPTY STATE ═══════════ -->
                <div class="billing-empty-state" id="billingEmptyState">
                    <div class="empty-state-icon"><i data-lucide="receipt"></i></div>
                    <h2>No Patient Selected</h2>
                    <p>Search for an admitted patient above to open the billing terminal</p>
                    <div class="empty-state-stats" id="emptyStateStats">
                        <div class="estat-card">
                            <div class="estat-val" id="estTotalBills">—</div>
                            <div class="estat-label">Total Bills</div>
                        </div>
                        <div class="estat-card">
                            <div class="estat-val green" id="estTotalCollected">—</div>
                            <div class="estat-label">Collected Today</div>
                        </div>
                        <div class="estat-card">
                            <div class="estat-val amber" id="estPending">—</div>
                            <div class="estat-label">Pending Balance</div>
                        </div>
                    </div>
                </div>

                <!-- ═══════════ ZONE 3: BILLING WORKSPACE ═══════════ -->
                <div class="billing-workspace" id="billingWorkspace" style="display:none;">
                    
                    <!-- ── HEADER ACTIONS ── -->
                    <div class="workspace-header-actions">
                        <div class="patient-header-card" id="patientHeaderCard">
                            <div class="phc-left">
                                <div class="phc-avatar" id="phcAvatar">JD</div>
                                <div class="phc-info">
                                    <div class="phc-name" id="phcName">Patient Name</div>
                                    <div class="phc-meta">
                                        <span class="phc-tag" id="phcAdmId"></span>
                                        <span class="phc-dot">·</span>
                                        <span id="phcAge"></span>
                                        <span class="phc-dot">·</span>
                                        <span id="phcDoctor"></span>
                                    </div>
                                    <div class="phc-meta">
                                        <i data-lucide="bed"></i>
                                        <span id="phcBed"></span>
                                        <span class="phc-dot">·</span>
                                        <i data-lucide="calendar"></i>
                                        <span id="phcDates"></span>
                                        <span class="phc-dot">·</span>
                                        <span id="phcDays" class="phc-days-badge"></span>
                                    </div>
                                </div>
                            </div>
                            <div class="phc-right">
                                <div class="phc-bill-card">
                                    <div class="phc-bill-no" id="phcBillNo">BILL-0000</div>
                                    <div class="phc-billing-status" id="phcBillingStatus"></div>
                                </div>
                                <div class="phc-actions">
                                    <button class="phc-btn phc-btn-print" onclick="billing.printInterim()" title="Interim Bill">
                                        <i data-lucide="printer"></i> Interim
                                    </button>
                                    <button class="phc-btn phc-btn-print" onclick="billing.printFinal()" id="btnPrintFinal" title="Final Bill">
                                        <i data-lucide="file-text"></i> Final Bill
                                    </button>
                                    <button class="phc-btn phc-btn-print" onclick="billing.printReceipt()" title="Receipt">
                                        <i data-lucide="receipt"></i> Receipt
                                    </button>
                                    <button class="phc-btn phc-btn-green" onclick="billing.openStatusModal()" title="Change Status">
                                        <i data-lucide="toggle-right"></i> Status
                                    </button>
                                    <button class="phc-btn" style="background:var(--amber-600); color:white; border-color:var(--amber-700);" onclick="billing.dischargePatient()" title="Discharge Patient">
                                        <i data-lucide="sign-out"></i> Discharge
                                    </button>
                                    <button class="phc-btn phc-btn-blue" id="btnInsuranceInfo" onclick="billing.openInsuranceModal()">
                                        <i data-lucide="shield"></i> Insurance
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ── QUICK STATS KPI CARDS ── -->
                    <div class="quick-stats-bar" id="quickStatsBar">
                        <div class="qs-item">
                            <div class="qs-icon qs-green"><i data-lucide="list"></i></div>
                            <div><div class="qs-val" id="qsItemCount">0</div><div class="qs-lbl">Total Charges</div></div>
                        </div>
                        <div class="qs-item">
                            <div class="qs-icon qs-cream"><i data-lucide="calculator"></i></div>
                            <div><div class="qs-val" id="qsGrandTotal">₹0</div><div class="qs-lbl">Grand Total</div></div>
                        </div>
                        <div class="qs-item">
                            <div class="qs-icon qs-blue"><i data-lucide="check-circle-2"></i></div>
                            <div><div class="qs-val" id="qsAmountPaid">₹0</div><div class="qs-lbl">Advance Paid</div></div>
                        </div>
                        <div class="qs-item">
                            <div class="qs-icon qs-red"><i data-lucide="alert-circle"></i></div>
                            <div><div class="qs-val" id="qsBalanceDue">₹0</div><div class="qs-lbl">Balance Due</div></div>
                        </div>
                    </div>

                    <!-- ── MAIN 2-PANEL LAYOUT ── -->
                    <div class="billing-main-panels">
                        
                        <!-- ───────── LEFT PANEL (Scrollable) ───────── -->
                        <div class="billing-left-panel">
                            
                            <!-- Billing Items Section -->
                            <div class="panel-card" id="billingItemsCard">
                                <div class="panel-card-head">
                                    <div class="panel-card-title">
                                        <i data-lucide="list"></i> Billing Items
                                        <span class="item-count-badge" id="itemCountBadge">0</span>
                                    </div>
                                    <div class="panel-card-actions">
                                        <div class="add-charge-wrap">
                                            <button class="btn-add-charge" id="btnAddCharge" onclick="billing.toggleChargeMenu()">
                                                <i data-lucide="plus-circle"></i> Add Charge
                                                <i data-lucide="chevron-down" class="charge-arrow" id="chargeArrow"></i>
                                            </button>
                                            <div class="charge-menu" id="chargeMenu">
                                                <?php
                                                $chargeTypes = [
                                                    ['ROOM_RENT',    'bed-double',      'Room Rent',       'Opens room rent generator'],
                                                    ['DOCTOR_VISIT', 'stethoscope',     'Doctor Visit',    'Consultant/visiting doctor'],
                                                    ['LAB',          'flask-conical',   'Laboratory',      'Lab tests & reports'],
                                                    ['RADIOLOGY',    'radio',           'Radiology',       'X-ray, MRI, CT, USG'],
                                                    ['PHARMACY',     'pill',            'Pharmacy',        'Medicines & drugs'],
                                                    ['OT',           'syringe',         'Operation Theatre','OT charges'],
                                                    ['PROCEDURE',    'activity',        'Procedure',       'Minor procedures'],
                                                    ['CONSUMABLE',   'bandage',         'Consumables',     'Dressings, gloves etc.'],
                                                    ['MISC',         'more-horizontal', 'Miscellaneous',   'Misc charges'],
                                                    ['OTHER',        'layers',          'Other',           'Other charges'],
                                                ];
                                                foreach ($chargeTypes as [$type, $icon, $label, $desc]):
                                                    if ($type === 'ROOM_RENT'):
                                                ?>
                                                <div class="charge-menu-item" onclick="billing.closeChargeMenu(); billing.openRoomRentModal();">
                                                    <i data-lucide="<?= $icon ?>" class="charge-menu-icon"></i>
                                                    <div><div class="cmi-label"><?= $label ?></div><div class="cmi-desc"><?= $desc ?></div></div>
                                                </div>
                                                <?php else: ?>
                                                <div class="charge-menu-item" onclick="billing.closeChargeMenu(); billing.openAddChargeModal('<?= $type ?>');">
                                                    <i data-lucide="<?= $icon ?>" class="charge-menu-icon"></i>
                                                    <div><div class="cmi-label"><?= $label ?></div><div class="cmi-desc"><?= $desc ?></div></div>
                                                </div>
                                                <?php endif; endforeach; ?>
                                            </div>
                                        </div>
                                        <button class="btn-room-rent" onclick="billing.openRoomRentModal()">
                                            <i data-lucide="bed-double"></i> Room Rent
                                        </button>
                                    </div>
                                </div>

                                <!-- Category Filter Tabs -->
                                <div class="category-filter-tabs" id="categoryFilterTabs">
                                    <button class="cat-tab active" data-type="" onclick="billing.filterItems(this,'')">All</button>
                                    <button class="cat-tab" data-type="ROOM_RENT"    onclick="billing.filterItems(this,'ROOM_RENT')">Room</button>
                                    <button class="cat-tab" data-type="DOCTOR_VISIT" onclick="billing.filterItems(this,'DOCTOR_VISIT')">Doctor</button>
                                    <button class="cat-tab" data-type="LAB"          onclick="billing.filterItems(this,'LAB')">Lab</button>
                                    <button class="cat-tab" data-type="RADIOLOGY"    onclick="billing.filterItems(this,'RADIOLOGY')">Radiology</button>
                                    <button class="cat-tab" data-type="PHARMACY"     onclick="billing.filterItems(this,'PHARMACY')">Pharmacy</button>
                                    <button class="cat-tab" data-type="OT"           onclick="billing.filterItems(this,'OT')">OT</button>
                                    <button class="cat-tab" data-type="PROCEDURE"    onclick="billing.filterItems(this,'PROCEDURE')">Procedure</button>
                                    <button class="cat-tab" data-type="CONSUMABLE"   onclick="billing.filterItems(this,'CONSUMABLE')">Consumables</button>
                                    <button class="cat-tab" data-type="MISC"         onclick="billing.filterItems(this,'MISC')">Misc</button>
                                </div>

                                <!-- Items Table -->
                                <div class="items-table-wrap">
                                    <table class="billing-items-table" id="billingItemsTable">
                                        <thead>
                                            <tr>
                                                <th>Sl.No</th>
                                                <th>Date</th>
                                                <th>Category</th>
                                                <th>Description</th>
                                                <th>Qty</th>
                                                <th>Rate</th>
                                                <th>Total</th>
                                                <th>Status</th>
                                                <th></th>
                                            </tr>
                                        </thead>
                                        <tbody id="itemsTableBody">
                                            <tr class="empty-row" id="itemsEmptyRow">
                                                <td colspan="9">
                                                    <div class="table-empty-state">
                                                        <i data-lucide="clipboard-list"></i>
                                                        <p>No charges added yet</p>
                                                    </div>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <!-- Payment History Section -->
                            <div class="panel-card" id="paymentHistoryCard">
                                <div class="panel-card-head">
                                    <div class="panel-card-title">
                                        <i data-lucide="credit-card"></i> Payment History
                                        <span class="item-count-badge blue-badge" id="payCountBadge">0</span>
                                    </div>
                                    <div class="panel-card-actions">
                                        <button class="btn-add-payment" onclick="billing.openPaymentModal('PARTIAL')">
                                            <i data-lucide="plus-circle"></i> Record Payment
                                        </button>
                                        <button class="btn-ins-receipt" id="btnInsReceipt" onclick="billing.openInsuranceReceiptModal()" style="display:none;">
                                            <i data-lucide="shield"></i> Insurance Receipt
                                        </button>
                                    </div>
                                </div>
                                <div class="payments-table-wrap">
                                    <table class="billing-items-table" id="paymentsTable">
                                        <thead>
                                            <tr>
                                                <th>Date</th>
                                                <th>Type</th>
                                                <th>Mode</th>
                                                <th>Amount</th>
                                                <th>Reference</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody id="paymentsTableBody">
                                            <tr class="empty-row" id="paymentsEmptyRow">
                                                <td colspan="6">
                                                    <div class="table-empty-state">
                                                        <i data-lucide="wallet"></i>
                                                        <p>No payments recorded yet</p>
                                                    </div>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <!-- ───────── RIGHT PANEL (Financial Summary) ───────── -->
                        <div class="billing-right-panel">
                            <div class="financial-summary-card" id="financialSummaryCard">
                                <div class="fs-header">
                                    <i data-lucide="pie-chart"></i> Financial Summary
                                </div>

                                <div class="fs-section">
                                    <?php
                                    $categories = [
                                        ['ROOM_RENT',    'Room Rent',        'room_charges'],
                                        ['DOCTOR_VISIT', 'Doctor Visit',     'doctor_charges'],
                                        ['LAB',          'Laboratory',       'lab_charges'],
                                        ['RADIOLOGY',    'Radiology',        'radiology_charges'],
                                        ['PHARMACY',     'Pharmacy',         'pharmacy_charges'],
                                        ['OT',           'Operation Theatre','ot_charges'],
                                        ['PROCEDURE',    'Procedure',        'procedure_charges'],
                                        ['CONSUMABLE',   'Consumables',      'consumable_charges'],
                                        ['MISC',         'Miscellaneous',    'other_charges'],
                                    ];
                                    foreach ($categories as [$type, $label, $col]):
                                    ?>
                                    <div class="fs-cat-row" id="fsCat_<?= $type ?>" data-col="<?= $col ?>">
                                        <div class="fs-cat-label"><?= $label ?></div>
                                        <div class="fs-cat-val" id="fsVal_<?= $type ?>">₹0</div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>

                                <div class="fs-divider"></div>

                                <div class="fs-row discount-row">
                                    <span class="fs-label">Discount <button class="btn-edit-discount" onclick="billing.openDiscountModal()"><i data-lucide="pen-line"></i></button></span>
                                    <span class="fs-val red" id="fsDiscount">-₹0.00</span>
                                </div>
                                
                                <div class="fs-insurance-block" id="fsInsuranceBlock" style="display:none;">
                                    <div class="fs-row"><span class="fs-label">Ins. Approved</span><span class="fs-val blue" id="fsInsApproved">₹0.00</span></div>
                                    <div class="fs-row"><span class="fs-label">Ins. Received</span><span class="fs-val green" id="fsInsReceived">₹0.00</span></div>
                                    <div class="fs-row"><span class="fs-label">Patient Payable</span><span class="fs-val bold" id="fsPatientPayable">₹0.00</span></div>
                                </div>

                                <div class="fs-grand-total" id="fsGrandTotalBox">
                                    <span>GRAND TOTAL</span>
                                    <span id="fsGrandTotal">₹0.00</span>
                                </div>

                                <div class="fs-row">
                                    <span class="fs-label">Advance Paid</span>
                                    <span class="fs-val green" id="fsAmountPaid">₹0.00</span>
                                </div>

                                <div class="fs-balance-box" id="fsBalanceBox">
                                    <span>BALANCE DUE</span>
                                    <span id="fsBalanceDue">₹0.00</span>
                                </div>

                                <div class="fs-action-buttons">
                                    <button class="fs-btn fs-btn-payment" onclick="billing.openPaymentModal('PARTIAL')">
                                        <i data-lucide="plus-circle"></i> Record Payment
                                    </button>
                                    <button class="fs-btn fs-btn-discount" onclick="billing.openDiscountModal()">
                                        <i data-lucide="tag"></i> Discount
                                    </button>
                                    <button class="fs-btn fs-btn-ins" id="fsBtnIns" onclick="billing.openInsuranceModal()">
                                        <i data-lucide="shield"></i> Insurance
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ═══════════ TOAST CONTAINER ═══════════ -->
                <div class="billing-toast-container" id="billingToastContainer"></div>

            </main>

    </div>
</div>

<!-- ══════════════════════════════════════════════════════════════════
     MODALS
══════════════════════════════════════════════════════════════════ -->

<!-- MODAL: Add Charge -->
<div class="billing-modal-overlay" id="modalAddCharge">
    <div class="billing-modal" style="max-width:560px;">
        <div class="bm-head">
            <div class="bm-title"><i data-lucide="plus-circle"></i> Add Billing Charge</div>
            <button class="bm-close" onclick="billing.closeModal('modalAddCharge')"><i data-lucide="x"></i></button>
        </div>
        <div class="bm-body">
            <div class="bm-form-row two-col">
                <div class="bm-form-group">
                    <label>Charge Date <span class="req">*</span></label>
                    <input type="date" id="chargeDate">
                </div>
                <div class="bm-form-group">
                    <label>Charge Type <span class="req">*</span></label>
                    <select id="chargeType" onchange="billing.onChargeTypeChange()">
                        <option value="">Select Category</option>
                        <option value="ROOM_RENT">🛏 Room Rent</option>
                        <option value="DOCTOR_VISIT">👨‍⚕️ Doctor Visit</option>
                        <option value="LAB">🔬 Laboratory</option>
                        <option value="RADIOLOGY">📡 Radiology</option>
                        <option value="PHARMACY">💊 Pharmacy</option>
                        <option value="OT">🏥 Operation Theatre</option>
                        <option value="PROCEDURE">🩹 Procedure</option>
                        <option value="CONSUMABLE">🧪 Consumables</option>
                        <option value="MISC">📦 Miscellaneous</option>
                        <option value="OTHER">📁 Other</option>
                    </select>
                </div>
            </div>
            <div class="bm-form-group">
                <label>Department</label>
                <input type="text" id="chargeDept" placeholder="Optional — e.g. Cardiology, Orthopaedics">
            </div>
            <div class="bm-form-group">
                <label>Description / Item Name <span class="req">*</span></label>
                <input type="text" id="chargeDesc" placeholder="Enter service or item name...">
            </div>
            <div class="bm-form-row three-col">
                <div class="bm-form-group">
                    <label>Quantity</label>
                    <input type="number" id="chargeQty" value="1" min="0.01" step="0.01" oninput="billing.calcChargeTotal()">
                </div>
                <div class="bm-form-group">
                    <label>Unit Price (₹)</label>
                    <input type="number" id="chargeUnitPrice" value="0" min="0" step="0.01" oninput="billing.calcChargeTotal()">
                </div>
                <div class="bm-form-group">
                    <label>Discount (₹)</label>
                    <input type="number" id="chargeDiscount" value="0" min="0" step="0.01" oninput="billing.calcChargeTotal()">
                </div>
            </div>
            <div class="bm-total-preview">
                <span>Total Amount:</span>
                <span id="chargeTotalPreview" class="bm-total-val">₹ 0.00</span>
            </div>
            <div class="bm-duplicate-warning" id="chargeDupWarning" style="display:none;">
                <i data-lucide="alert-triangle"></i>
                <span id="chargeDupMsg"></span>
                <button class="bm-dup-btn" onclick="billing.forceAddCharge()">Add Anyway</button>
            </div>
            <div class="bm-form-group">
                <label>Notes / Reference</label>
                <input type="text" id="chargeNotes" placeholder="Optional reference or notes">
            </div>
            <div class="bm-footer">
                <button class="bm-btn bm-btn-cancel" onclick="billing.closeModal('modalAddCharge')">Cancel</button>
                <button class="bm-btn bm-btn-primary" id="btnSaveCharge" onclick="billing.saveCharge()">
                    <i data-lucide="plus-circle"></i> Add Charge
                </button>
            </div>
        </div>
    </div>
</div>

<!-- MODAL: Room Rent Generator -->
<div class="billing-modal-overlay" id="modalRoomRent">
    <div class="billing-modal" style="max-width:640px;">
        <div class="bm-head green-head">
            <div class="bm-title"><i data-lucide="bed-double"></i> Generate Room Rent</div>
            <button class="bm-close" onclick="billing.closeModal('modalRoomRent')"><i data-lucide="x"></i></button>
        </div>
        <div class="bm-body">
            <div class="rr-bed-info-card" id="rrBedInfoCard">
                <div class="rr-bed-details">
                    <div class="rr-bed-icon"><i data-lucide="bed-double"></i></div>
                    <div>
                        <div class="rr-bed-name" id="rrBedName">Loading...</div>
                        <div class="rr-bed-rate" id="rrBedRate"></div>
                    </div>
                </div>
                <div class="rr-rate-breakdown" id="rrRateBreakdown"></div>
            </div>
            <div class="bm-form-row two-col">
                <div class="bm-form-group">
                    <label>From Date <span class="req">*</span></label>
                    <input type="date" id="rrFromDate" onchange="billing.loadRoomRentPreview()">
                </div>
                <div class="bm-form-group">
                    <label>To Date <span class="req">*</span></label>
                    <input type="date" id="rrToDate" onchange="billing.loadRoomRentPreview()">
                </div>
            </div>
            <div class="rr-preview" id="rrPreview">
                <div class="rr-preview-loading"><i data-lucide="loader-2" class="lucide-spin"></i> Select dates to preview...</div>
            </div>
            <div class="rr-preview-summary" id="rrPreviewSummary" style="display:none;">
                <div class="rr-ps-item">
                    <span class="rr-ps-label">New Days</span>
                    <span class="rr-ps-val green" id="rrNewCount">0</span>
                </div>
                <div class="rr-ps-item">
                    <span class="rr-ps-label">Skipped (duplicate)</span>
                    <span class="rr-ps-val amber" id="rrSkipCount">0</span>
                </div>
                <div class="rr-ps-item">
                    <span class="rr-ps-label">Total to Add</span>
                    <span class="rr-ps-val bold" id="rrNewTotal">₹0</span>
                </div>
            </div>
            <div class="bm-footer">
                <button class="bm-btn bm-btn-cancel" onclick="billing.closeModal('modalRoomRent')">Cancel</button>
                <button class="bm-btn bm-btn-green" id="btnConfirmRoomRent" onclick="billing.confirmRoomRent()" disabled>
                    <i data-lucide="check-circle-2"></i> <span id="rrConfirmBtnLabel">Confirm & Generate</span>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- MODAL: Record Payment -->
<div class="billing-modal-overlay" id="modalPayment">
    
    <div class="billing-modal" style="max-width:520px;">
        <div class="bm-head">
            <div class="bm-title"><i data-lucide="credit-card"></i> Record Payment</div>
            <button class="bm-close" onclick="billing.closeModal('modalPayment')"><i data-lucide="x"></i></button>
        </div>
        <div class="bm-body">
            <div class="pay-balance-preview" id="payBalancePreview">
                <div class="pbp-label">Current Balance Due</div>
                <div class="pbp-val" id="payBalanceVal">₹0.00</div>
            </div>
            <div class="bm-form-row two-col">
                <div class="bm-form-group">
                    <label>Payment Date <span class="req">*</span></label>
                    <input type="date" id="payDate">
                </div>
                <div class="bm-form-group">
                    <label>Payment Type <span class="req">*</span></label>
                    <div class="pay-type-group" id="payTypeGroup">
                        <button class="pay-type-btn" data-type="ADVANCE">Advance</button>
                        <button class="pay-type-btn active" data-type="PARTIAL">Partial</button>
                        <button class="pay-type-btn" data-type="FINAL">Final</button>
                        <button class="pay-type-btn refund-btn" data-type="REFUND">Refund</button>
                    </div>
                </div>
            </div>
            <div class="bm-form-group">
                <label>Payment Mode <span class="req">*</span></label>
                <div class="pay-mode-group" id="payModeGroup">
                    <button class="pay-mode-btn active" data-mode="CASH"><i data-lucide="banknote"></i> Cash</button>
                    <button class="pay-mode-btn" data-mode="UPI"><i data-lucide="smartphone"></i> UPI</button>
                    <button class="pay-mode-btn" data-mode="CARD"><i data-lucide="credit-card"></i> Card</button>
                    <button class="pay-mode-btn" data-mode="BANK"><i data-lucide="landmark"></i> Bank</button>
                    <button class="pay-mode-btn" data-mode="CHEQUE"><i data-lucide="receipt"></i> Cheque</button>
                </div>
            </div>
            <div class="bm-form-row two-col">
                <div class="bm-form-group">
                    <label>Amount (₹) <span class="req">*</span></label>
                    <div style="position:relative;">
                        <input type="number" id="payAmount" min="0.01" step="0.01" placeholder="0.00" oninput="billing.updatePayPreview()">
                        <button class="btn-full-amount" id="btnFullAmount" onclick="billing.fillFullAmount()" title="Fill full balance">Full</button>
                    </div>
                </div>
                <div class="bm-form-group" id="payRefGroup">
                    <label>Reference No.</label>
                    <input type="text" id="payRef" placeholder="UPI txn / Cheque no.">
                </div>
            </div>
            <!-- Refund extra fields -->
            <div id="refundExtraFields" style="display:none;">
                <div class="bm-form-group">
                    <label>Refund Reason <span class="req">*</span></label>
                    <textarea id="refundReason" rows="2" placeholder="Reason for refund (required)"></textarea>
                </div>
                <div class="bm-form-group">
                    <label>Authorized By <span class="req">*</span></label>
                    <input type="text" id="refundApprovedBy" placeholder="Manager / Doctor name">
                </div>
            </div>
            <div class="bm-form-group">
                <label>Remarks</label>
                <input type="text" id="payRemarks" placeholder="Optional notes">
            </div>
            <div class="pay-after-preview" id="payAfterPreview">
                Balance after payment: <strong id="payAfterVal">—</strong>
            </div>
            <div class="bm-footer">
                <button class="bm-btn bm-btn-cancel" onclick="billing.closeModal('modalPayment')">Cancel</button>
                <button class="bm-btn bm-btn-primary" id="btnSavePayment" onclick="billing.savePayment()">
                    <i data-lucide="check-circle-2"></i> Record Payment
                </button>
            </div>
        </div>
    </div>
</div>

<!-- MODAL: Insurance Receipt -->
<div class="billing-modal-overlay" id="modalInsReceipt">
    <div class="billing-modal" style="max-width:480px;">
        <div class="bm-head blue-head">
            <div class="bm-title"><i data-lucide="shield"></i> Insurance Receipt</div>
            <button class="bm-close" onclick="billing.closeModal('modalInsReceipt')"><i data-lucide="x"></i></button>
        </div>
        <div class="bm-body">
            <div class="ins-summary-card" id="insReceiptSummaryCard">
                <div class="ins-sc-row"><span>Company:</span><strong id="insRcptCompany">—</strong></div>
                <div class="ins-sc-row"><span>Approved:</span><strong id="insRcptApproved">₹0</strong></div>
                <div class="ins-sc-row"><span>Already Received:</span><strong id="insRcptReceived">₹0</strong></div>
                <div class="ins-sc-row highlight"><span>Pending Claim:</span><strong id="insRcptPending">₹0</strong></div>
            </div>
            <div class="bm-form-row two-col">
                <div class="bm-form-group">
                    <label>Receipt Date <span class="req">*</span></label>
                    <input type="date" id="insRcptDate">
                </div>
                <div class="bm-form-group">
                    <label>Amount Received (₹) <span class="req">*</span></label>
                    <input type="number" id="insRcptAmount" min="0.01" step="0.01" placeholder="0.00">
                    <button class="btn-full-amount mt-1" onclick="billing.fillInsFullAmount()">Full Pending</button>
                </div>
            </div>
            <div class="bm-form-group">
                <label>Settlement Reference <span class="req">*</span></label>
                <input type="text" id="insRcptRef" placeholder="Claim settlement / NEFT reference">
            </div>
            <div class="bm-form-group">
                <label>Remarks</label>
                <input type="text" id="insRcptRemarks" placeholder="Optional">
            </div>
            <div class="bm-footer">
                <button class="bm-btn bm-btn-cancel" onclick="billing.closeModal('modalInsReceipt')">Cancel</button>
                <button class="bm-btn bm-btn-primary" onclick="billing.saveInsuranceReceipt()">
                    <i data-lucide="check-circle-2"></i> Record Receipt
                </button>
            </div>
        </div>
    </div>
</div>

<!-- MODAL: Apply Discount -->
<div class="billing-modal-overlay" id="modalDiscount">
    <div class="billing-modal" style="max-width:440px;">
        <div class="bm-head">
            <div class="bm-title"><i data-lucide="tags"></i> Apply Discount</div>
            <button class="bm-close" onclick="billing.closeModal('modalDiscount')"><i data-lucide="x"></i></button>
        </div>
        <div class="bm-body">
            <div class="discount-subtotal-info">
                Current Subtotal: <strong id="discSubtotalDisplay">₹0.00</strong>
            </div>
            <div class="bm-form-row two-col">
                <div class="bm-form-group">
                    <label>Discount Amount (₹)</label>
                    <input type="number" id="discAmount" min="0" step="0.01" placeholder="0.00" oninput="billing.calcDiscountPct()">
                </div>
                <div class="bm-form-group">
                    <label>Discount Percentage (%)</label>
                    <input type="number" id="discPct" min="0" max="100" step="0.01" placeholder="0.00" oninput="billing.calcDiscountAmt()">
                </div>
            </div>
            <div class="bm-form-group">
                <label>Reason <span class="req">*</span></label>
                <select id="discReason">
                    <option value="">Select reason</option>
                    <option>Doctor Approved</option>
                    <option>Management Decision</option>
                    <option>Insurance Negotiation</option>
                    <option>Below Poverty Line</option>
                    <option>Employee Discount</option>
                    <option>Loyalty Discount</option>
                </select>
            </div>
            <div class="discount-after-preview">
                <div class="dap-row"><span>Subtotal</span><span id="dapSubtotal">₹0.00</span></div>
                <div class="dap-row red"><span>Discount</span><span id="dapDiscount">-₹0.00</span></div>
                <div class="dap-row bold green"><span>Grand Total</span><span id="dapGrandTotal">₹0.00</span></div>
            </div>
            <div class="bm-footer">
                <button class="bm-btn bm-btn-cancel" onclick="billing.closeModal('modalDiscount')">Cancel</button>
                <button class="bm-btn bm-btn-primary" onclick="billing.saveDiscount()">
                    <i data-lucide="check-circle-2"></i> Apply Discount
                </button>
            </div>
        </div>
    </div>
</div>

<!-- MODAL: Insurance Details -->
<div class="billing-modal-overlay" id="modalInsurance">
    <div class="billing-modal" style="max-width:600px;">
        <div class="bm-head blue-head">
            <div class="bm-title"><i data-lucide="shield"></i> Insurance / Corporate Details</div>
            <button class="bm-close" onclick="billing.closeModal('modalInsurance')"><i data-lucide="x"></i></button>
        </div>
        <div class="bm-body">
            <div class="bm-form-group">
                <label>Bill Type</label>
                <div class="bill-type-group">
                    <button class="bill-type-btn active" data-type="SELF"      onclick="billing.selectBillType(this)">SELF</button>
                    <button class="bill-type-btn" data-type="INSURANCE"  onclick="billing.selectBillType(this)">INSURANCE</button>
                    <button class="bill-type-btn" data-type="CORPORATE"  onclick="billing.selectBillType(this)">CORPORATE</button>
                </div>
            </div>
            <div id="insFormFields">
                <div class="bm-form-row two-col">
                    <div class="bm-form-group">
                        <label>Company Name <span class="req">*</span></label>
                        <input type="text" id="insCompanyName" placeholder="Insurance company name">
                    </div>
                    <div class="bm-form-group">
                        <label>TPA Name</label>
                        <input type="text" id="insTpaName" placeholder="e.g. Medi Assist">
                    </div>
                </div>
                <div class="bm-form-row three-col">
                    <div class="bm-form-group">
                        <label>Policy Number</label>
                        <input type="text" id="insPolicyNo" placeholder="Policy number">
                    </div>
                    <div class="bm-form-group">
                        <label>Claim Number</label>
                        <input type="text" id="insClaimNo" placeholder="Claim number">
                    </div>
                    <div class="bm-form-group">
                        <label>Approval Number</label>
                        <input type="text" id="insApprovalNo" placeholder="Approval ref">
                    </div>
                </div>
                <div class="bm-form-row two-col">
                    <div class="bm-form-group">
                        <label>Approved Amount (₹)</label>
                        <input type="number" id="insApprovedAmt" min="0" step="0.01" placeholder="0.00" oninput="billing.calcInsPatientPayable()">
                    </div>
                    <div class="bm-form-group">
                        <label>Claim Status</label>
                        <select id="insClaimStatus">
                            <option value="PENDING">PENDING</option>
                            <option value="SUBMITTED">SUBMITTED</option>
                            <option value="APPROVED">APPROVED</option>
                            <option value="PARTIAL_APPROVED">PARTIAL APPROVED</option>
                            <option value="REJECTED">REJECTED</option>
                            <option value="SETTLED">SETTLED</option>
                        </select>
                    </div>
                </div>
                <div class="ins-patient-payable-preview">
                    Grand Total: <strong id="insGtDisplay">₹0</strong> &nbsp;|&nbsp;
                    Insurance Approved: <strong id="insApprDisplay">₹0</strong> &nbsp;|&nbsp;
                    Patient Payable: <strong class="green" id="insPpDisplay">₹0</strong>
                </div>
            </div>
            <div class="bm-footer">
                <button class="bm-btn bm-btn-cancel" onclick="billing.closeModal('modalInsurance')">Cancel</button>
                <button class="bm-btn bm-btn-primary" onclick="billing.saveInsuranceDetails()">
                    <i class="fas fa-save"></i> Save Details
                </button>
            </div>
        </div>
    </div>
</div>

<!-- MODAL: Change Status -->
<div class="billing-modal-overlay" id="modalStatus">
    <div class="billing-modal" style="max-width:420px;">
        <div class="bm-head">
            <div class="bm-title"><i class="fas fa-toggle-on"></i> Change Billing Status</div>
            <button class="bm-close" onclick="billing.closeModal('modalStatus')"><i data-lucide="x"></i></button>
        </div>
        <div class="bm-body">
            <div class="bm-form-group">
                <label>Current Status</label>
                <div class="status-current-display" id="statusCurrentDisplay">OPEN</div>
            </div>
            <div class="bm-form-group">
                <label>New Status <span class="req">*</span></label>
                <div class="status-options" id="statusOptions">
                    <button class="status-option-btn" data-status="OPEN">OPEN</button>
                    <button class="status-option-btn" data-status="UNDER_TREATMENT">UNDER TREATMENT</button>
                    <button class="status-option-btn" data-status="DISCHARGE_PENDING">DISCHARGE PENDING</button>
                    <button class="status-option-btn" data-status="FINALIZED">FINALIZED</button>
                    <button class="status-option-btn danger-btn" data-status="CANCELLED">CANCELLED</button>
                </div>
            </div>
            <div class="bm-form-group">
                <label>Reason for change</label>
                <textarea id="statusReason" rows="2" placeholder="Optional notes..."></textarea>
            </div>
            <div class="bm-footer">
                <button class="bm-btn bm-btn-cancel" onclick="billing.closeModal('modalStatus')">Cancel</button>
                <button class="bm-btn bm-btn-primary" onclick="billing.saveStatus()">
                    <i data-lucide="check-circle-2"></i> Update Status
                </button>
            </div>
        </div>
    </div>
</div>

<!-- MODAL: Discharge Patient -->
<div class="billing-modal-overlay" id="modalDischarge">
    <div class="billing-modal" style="max-width:600px;">
        <div class="bm-head" style="background:var(--amber-500);">
            <div class="bm-title" style="color:white;"><i data-lucide="sign-out"></i> Discharge Patient</div>
            <button class="bm-close" style="color:white;" onclick="billing.closeModal('modalDischarge')"><i data-lucide="x"></i></button>
        </div>
        <div class="bm-body">
            <form id="dischargeFormLocal">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 15px;">
                    <div class="bm-form-group">
                        <label>Discharge Date & Time *</label>
                        <input type="datetime-local" id="dsDate" class="bm-input" required>
                    </div>
                    <div class="bm-form-group">
                        <label>Discharge Type *</label>
                        <select id="dsType" class="bm-input" required>
                            <option value="Normal">Normal</option>
                            <option value="Against Medical Advice">Against Medical Advice</option>
                            <option value="Transferred">Transferred</option>
                            <option value="Deceased">Deceased</option>
                        </select>
                    </div>
                    <div class="bm-form-group">
                        <label>Follow-up Date</label>
                        <input type="date" id="dsFollowup" class="bm-input">
                    </div>
                </div>
                
                <div class="bm-form-group" style="margin-bottom: 15px;">
                    <label>Final Diagnosis</label>
                    <textarea id="dsDiagnosis" class="bm-input" rows="2" placeholder="e.g. Acute appendicitis"></textarea>
                </div>
                
                <div class="bm-form-group" style="margin-bottom: 15px;">
                    <label>Discharge Summary</label>
                    <textarea id="dsSummary" class="bm-input" rows="3" placeholder="Condition at discharge, main events during stay..."></textarea>
                </div>
                
                <div class="bm-form-group" style="margin-bottom: 15px;">
                    <label>Medications Prescribed</label>
                    <textarea id="dsMeds" class="bm-input" rows="2" placeholder="List of discharge medications..."></textarea>
                </div>
            </form>
            <div class="bm-footer" style="margin-top: 20px;">
                <button class="bm-btn bm-btn-cancel" onclick="billing.closeModal('modalDischarge')">Cancel</button>
                <button class="bm-btn" style="background:var(--amber-600); color:white; border:none;" id="btnSubmitDischarge" onclick="billing.submitDischarge()">
                    <i data-lucide="check-circle-2"></i> Complete Discharge
                </button>
            </div>
        </div>
    </div>
</div>

<!-- MODAL: Discharge History -->
<div class="billing-modal-overlay" id="modalDischargeHistory">
    <div class="billing-modal" style="max-width:800px;">
        <div class="bm-head" style="background:var(--blue-600);">
            <div class="bm-title" style="color:white;"><i data-lucide="history"></i> Discharge History</div>
            <button class="bm-close" style="color:white;" onclick="billing.closeModal('modalDischargeHistory')"><i data-lucide="x"></i></button>
        </div>
        <div class="bm-body" style="max-height: 70vh; overflow-y: auto;">
            <div class="table-responsive">
                <table class="table table-bordered table-hover billing-items-table" style="width: 100%;">
                    <thead>
                        <tr>
                            <th>Patient Name</th>
                            <th>Admission ID</th>
                            <th>Discharge Date</th>
                            <th>Final Bill Amount</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody id="dhTableBody">
                        <tr><td colspan="5" class="text-center">Loading...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- MODAL: Cancel Charge Confirmation -->
<div class="billing-modal-overlay" id="modalCancelCharge">
    <div class="billing-modal" style="max-width:420px;">
        <div class="bm-head danger-head">
            <div class="bm-title"><i data-lucide="alert-triangle"></i> Cancel Charge?</div>
            <button class="bm-close" onclick="billing.closeModal('modalCancelCharge')"><i data-lucide="x"></i></button>
        </div>
        <div class="bm-body">
            <div class="cancel-charge-info" id="cancelChargeInfo">
                <div class="cci-row"><i class="fas fa-tag"></i><span id="cciCategory"></span></div>
                <div class="cci-row"><i class="fas fa-info-circle"></i><span id="cciDesc"></span></div>
                <div class="cci-row"><i class="fas fa-calendar-alt"></i><span id="cciDate"></span></div>
                <div class="cci-row bold"><i class="fas fa-rupee-sign"></i><span id="cciAmount"></span></div>
            </div>
            <p class="cancel-charge-warn">This charge will be marked <strong>CANCELLED</strong> and removed from the total. This action cannot be undone.</p>
            <div class="bm-footer">
                <button class="bm-btn bm-btn-cancel" onclick="billing.closeModal('modalCancelCharge')">Go Back</button>
                <button class="bm-btn bm-btn-danger" id="btnConfirmCancelCharge" onclick="billing.confirmCancelCharge()">
                    <i class="fas fa-times-circle"></i> Yes, Cancel Charge
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    window.BILLING_API = '/GM_HMS/api/';
    window.USER_ROLE   = '<?= htmlspecialchars($userRole) ?>';
    window.USER_NAME   = '<?= htmlspecialchars($userName) ?>';
</script>
<script src="assets/js/ipd_billing.js?v=1785143282"></script>
    <script>
        lucide.createIcons();
    </script>
</body>
</html>
