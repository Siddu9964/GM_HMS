<?php
session_start();
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['Receptionist', 'admin', 'Admin'])) {
    header("Location: ../receptionist_login.php");
    exit();
}
$pageTitle = 'OPD Billing';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="stylesheet" href="/GM_HMS/assets/css/gm-theme.css">

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OPD Billing — GM HMS</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/reception_dashboard.css">
    <link rel="stylesheet" href="assets/css/opd_billing.css?v=<?= time() ?>">
</head>
<body>
<div class="reception-layout">
    <?php include 'includes/reception_sidebar.php'; ?>

    <div class="reception-main-content">
        <?php include 'includes/reception_navbar.php'; ?>

        <main class="reception-content billing-page">

            <!-- Bento-Box Workspace -->
            <div class="bento-workspace">
                
                <!-- Main Ledger Module (65%) -->
                <div class="bento-ledger">
                    <div class="bento-table-container">
                        <div class="bento-header-block">
                            <h3>Recent Bills</h3>
                            <div class="bento-header-actions">
                                <div class="bento-search-wrap">
                                    <i class="fas fa-search"></i>
                                    <input type="text" id="billSearchInput" placeholder="Search bills..." autocomplete="off">
                                </div>
                                <select class="bento-filter" id="billStatusFilter" onchange="opdBilling.filterBills()">
                                    <option value="">All Status</option>
                                    <option value="Paid">Paid</option>
                                    <option value="Pending">Pending</option>
                                </select>
                                <button class="bento-icon-btn" onclick="opdBilling.loadRecentBills()">
                                    <i class="fas fa-sync-alt"></i>
                                </button>
                            </div>
                        </div>
                        <div class="bento-table-wrap">
                            <table class="bento-table">
                                <thead>
                                    <tr>
                                        <th>BILL ID</th>
                                        <th>RECEIPT NO</th>
                                        <th>PATIENT</th>
                                        <th>DATE</th>
                                        <th>GRAND TOTAL</th>
                                        <th>ACTIONS</th>
                                    </tr>
                                </thead>
                                <tbody id="recentBillsTbody">
                                    <tr><td colspan="6"><div class="bento-empty"><i class="fas fa-receipt"></i><p>Loading bills…</p></div></td></tr>
                                </tbody>
                            </table>
                        </div>
                        <div id="billsPagination" class="bento-pagination"></div>
                    </div>
                </div>

                <!-- Contextual Insights Sidebar (35%) -->
                <div class="bento-sidebar" style="background: #f3efe6; border-radius: 20px; padding: 1.5rem; border: 1px solid rgba(31, 107, 74, 0.15); display: block;">
                    
                    <!-- Micro-widget: Find Patient -->
                    <div class="bento-widget bento-search-widget" style="margin-bottom: 2rem; border-radius: 12px; background: white; padding: 1rem; border: 1px solid rgba(31, 107, 74, 0.15);">
                        <div class="bento-widget-head" style="font-weight: 700; color: #1f6b4a; margin-bottom: 0.75rem; display: flex; align-items: center; gap: 0.5rem;">
                            <i class="fas fa-user-injured"></i> Find Patient
                        </div>
                        <div class="bento-widget-body">
                            <div class="bento-search-wrap" style="width: 100%; border-radius: 20px; background: rgba(31,107,74,0.05); border: 1px solid rgba(31,107,74,0.2); padding: 0.4rem 1rem; display: flex; align-items: center; margin-bottom: 0.5rem;">
                                <i class="fas fa-search" style="color: #1f6b4a; margin-right: 0.5rem;"></i>
                                <input type="text" id="patientSearchInput" placeholder="Search Name, Phone..." autocomplete="off" style="border: none; outline: none; background: transparent; width: 100%; color: #1f6b4a; font-size: 0.85rem;">
                            </div>
                            <div class="patient-results bento-results" id="patientResults">
                                <div class="bento-empty" style="text-align: center; padding: 1rem; opacity: 0.7; color: #1f6b4a; font-size: 0.8rem;">
                                    <i class="fas fa-user-search" style="font-size: 1.2rem; margin-bottom: 0.5rem; display: block;"></i>
                                    <p style="margin: 0;">Enter at least 2 characters</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <h2 style="color: #1f6b4a; margin-top: 0; margin-bottom: 1.5rem; font-size: 1.25rem;">Insights & Actions</h2>
                    
                    <!-- Section 1: Bills by Status -->
                    <div style="margin-bottom: 2.5rem;">
                        <div style="display: flex; gap: 1rem; align-items: flex-start;">
                            <div style="flex: 1;">
                                <h4 style="color: #1f6b4a; margin-top: 0; margin-bottom: 1rem; font-size: 0.85rem; font-weight: 700;">Bills by Status (Filtered)</h4>
                                <!-- CSS Pie Chart -->
                                <div id="insightPieChart" style="width: 120px; height: 120px; border-radius: 50%; background: conic-gradient(#1f6b4a 0% 75%, rgba(31,107,74,0.15) 75% 100%); position: relative; box-shadow: 0 4px 12px rgba(31,107,74,0.1); margin: 0 auto;">
                                    <div id="insightPaidLabel" style="position: absolute; color: #f3efe6; font-size: 0.65rem; font-weight: 700; left: 25px; top: 60px;">Paid (0%)</div>
                                    <div id="insightPendingLabel" style="position: absolute; color: #1f6b4a; font-size: 0.65rem; font-weight: 700; right: 15px; top: 25px;">Pending (0%)</div>
                                </div>
                            </div>
                            <!-- Quick Actions -->
                            <div style="display: flex; flex-direction: column; gap: 0.65rem; width: 140px;">
                                <div style="font-size: 0.85rem; font-weight: 700; color: #1f6b4a; margin-bottom: 0.25rem;">Quick Actions</div>
                                <button class="bento-quick-action" onclick="opdBilling.exportBillsCSV()">Export All (CSV)</button>
                                <button class="bento-quick-action" onclick="opdBilling.sendBulkReminders()">Send Reminders</button>
                            </div>
                        </div>
                    </div>

                    <!-- Section 2: Trend Line -->
                    <div style="margin-bottom: 2.5rem;">
                        <h4 style="color: #1f6b4a; margin-top: 0; margin-bottom: 1rem; font-size: 0.85rem; font-weight: 700;">Billing Volume Trend (Last 7 Days)</h4>
                        <div id="insightTrendContainer">
                            <!-- Dynamic trend line will be injected here -->
                        </div>
                    </div>



                </div><!-- /bento-sidebar -->

                <!-- RIGHT: Billing Form -->
                <div class="billing-right">

                    <!-- UNIFIED BILLING MODAL -->
                    <div class="modal-overlay" id="billingModalOverlay">
                        <div class="billing-modal-card">
                            <div class="billing-modal-head">
                                <h3><i class="fas fa-file-invoice-dollar"></i> OPD Billing Terminal</h3>
                                <button class="btn-close-modal" onclick="opdBilling.hideBillingModal()">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                            <div class="modal-body-scroll">
                                
                                <!-- Section 1: Patient Details -->
                                <div class="modal-section-card">
                                    <div class="modal-section-head">
                                        <i class="fas fa-user-circle"></i> Patient Registration Details
                                    </div>
                                    <div class="modal-section-body">
                                        <div class="patient-info-header" id="patientInfoGrid">
                                            <!-- Populated by JS -->
                                        </div>
                                    </div>
                                </div>

                                <!-- Section 2: Referral / Sponsor Information -->
                                <div class="modal-section-card">
                                    <div class="modal-section-head">
                                        <i class="fas fa-handshake"></i> Referral / Sponsor Information
                                    </div>
                                    <div class="modal-section-body">
                                        <div class="form-row" style="grid-template-columns: 15rem 1fr 1fr;">
                                            <div class="form-group">
                                                <label>Referral Type</label>
                                                <select id="referralType">
                                                    <option value="">Select</option>
                                                    <option value="Internal">Internal</option>
                                                    <option value="External">External</option>
                                                </select>
                                            </div>
                                            <div class="form-group">
                                                <label>Referred By</label>
                                                <div class="input-with-icon-inside suggestion-wrapper">
                                                    <input type="text" id="referredBy" placeholder="Enter name" autocomplete="off">
                                                    <div id="referralSuggestions" class="suggestion-list"></div>
                                                    <button type="button" class="btn-inside-action" style="display: none; width: auto; padding: 0 0.5rem; border-radius: 6px; right: 4px; font-size: 0.75rem; font-weight: 600; background: var(--teal-light);" onclick="opdBilling.showReferralModal()" title="Add New Referral">
                                                        <i class="fas fa-plus-circle"></i><span style="margin-left: 0.2rem;">Add Referral</span>
                                                    </button>
                                                </div>
                                            </div>
                                            <div class="form-group">
                                                <label>Sponsor</label>
                                                <div class="input-with-icon-inside suggestion-wrapper">
                                                    <input type="text" id="sponsorName" placeholder="Enter sponsor name" autocomplete="off">
                                                    <div id="sponsorSuggestions" class="suggestion-list"></div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Section 3: Billing Items -->
                                <div class="modal-section-card">
                                    <div class="modal-section-head">
                                        <i class="fas fa-list-ul"></i> Billing Items
                                    </div>
                                    <div class="modal-section-body" style="padding-bottom:0.5rem;">
                                        <!-- Quick Add from Services -->
                                        <div class="form-row cols-2" style="margin-bottom:1rem;">
                                            <div class="form-group" style="position:relative;">
                                                <label>Quick Add Service</label>
                                                <input type="text" id="serviceSearchInput" placeholder="Type to search services…" autocomplete="off"
                                                       oninput="opdBilling.filterServiceDropdown(this.value)"
                                                       onfocus="opdBilling.openServiceDropdown()"
                                                       style="padding-right:2rem;">
                                                <i class="fas fa-search" style="position:absolute;right:.75rem;top:2.1rem;color:var(--gray-400);font-size:.8rem;"></i>
                                                <div id="serviceDropdown" style="
                                                    display:none;position:absolute;top:100%;left:0;right:0;z-index:500;
                                                    background:white;border:1.5px solid var(--teal);border-radius:0 0 8px 8px;
                                                    max-height:240px;overflow-y:auto;box-shadow:0 8px 24px rgba(0,0,0,.12);
                                                ">
                                                    <div id="serviceDropdownList"></div>
                                                </div>
                                            </div>
                                            <div class="form-group" style="display: none; align-self:flex-end;">
                                                <button class="btn btn-secondary" onclick="opdBilling.addFromService()" style="width:100%;">
                                                    <i class="fas fa-plus"></i> Add Item
                                                </button>
                                            </div>
                                        </div>

                                        <!-- Items Table -->
                                        <div class="table-wrap">
                                            <table class="items-table">
                                                <thead>
                                                    <tr>
                                                        <th>Type</th>
                                                        <th>Item / Service Name</th>
                                                        <th style="width:60px;">Qty</th>
                                                        <th style="width:100px;">Unit Price (₹)</th>
                                                        <th style="width:90px;">Discount (₹)</th>
                                                        <th style="width:100px;">Total (₹)</th>
                                                        <th style="width:36px;"></th>
                                                    </tr>
                                                </thead>
                                                <tbody id="itemsTableBody">
                                                    <!-- Rows inserted by JS -->
                                                </tbody>
                                            </table>
                                        </div>
                                        <div class="add-item-row" style="border-top:none; padding:1rem 0;">
                                            <button class="btn-add-item" onclick="opdBilling.addItemRow()">
                                                <i class="fas fa-plus-circle"></i> Add Manual Item
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <!-- Section 3: Summary & Payment -->
                                <div class="modal-section-card">
                                    <div class="modal-section-head">
                                        <i class="fas fa-credit-card"></i> Payment & Summary
                                    </div>
                                    <div class="modal-section-body">
                                        <div class="form-row cols-3" style="align-items:flex-end; margin-bottom:1.5rem;">
                                            <div class="form-group">
                                                <label>Bill Discount (₹)</label>
                                                <input type="number" id="billDiscount" min="0" step="0.01" value="0" oninput="opdBilling.onDiscountAmountChange()">
                                            </div>
                                            <div class="form-group">
                                                <label>Discount (%)</label>
                                                <input type="number" id="billDiscountPct" min="0" max="100" step="0.01" value="0" placeholder="0.00" oninput="opdBilling.onDiscountPctChange()">
                                            </div>
                                            <div class="form-group">
                                                <label>Notes / Remarks</label>
                                                <input type="text" id="billNotes" placeholder="Optional remarks…">
                                            </div>
                                        </div>

                                        <div style="display:grid; grid-template-columns: 1.2fr 1fr; gap:2rem; align-items: start;">
                                            <div>
                                                <div class="payment-modes" id="paymentModes">
                                                    <button class="mode-btn active" data-mode="Cash"     onclick="opdBilling.setMode(this)"><i class="fas fa-money-bill-wave"></i>Cash</button>
                                                    <button class="mode-btn"         data-mode="UPI"      onclick="opdBilling.setMode(this)"><i class="fas fa-mobile-alt"></i>UPI</button>
                                                    <button class="mode-btn"         data-mode="Card"     onclick="opdBilling.setMode(this)"><i class="fas fa-credit-card"></i>Credit Card</button>
                                                    <button class="mode-btn"         data-mode="Debit Card" onclick="opdBilling.setMode(this)"><i class="fas fa-credit-card"></i>Debit Card</button>
                                                    <button class="mode-btn"         data-mode="Insurance" onclick="opdBilling.setMode(this)"><i class="fas fa-shield-alt"></i>Insurance</button>
                                                    <button class="mode-btn"         data-mode="NetBanking" onclick="opdBilling.setMode(this)"><i class="fas fa-university"></i>NetBanking</button>
                                                </div>

                                                <div class="form-row cols-2" style="margin-top:1rem;">
                                                    <div class="form-group">
                                                        <label>Amount Paid (₹)</label>
                                                        <input type="number" id="amountPaid" min="0" step="0.01" value="0" placeholder="0.00">
                                                    </div>
                                                    <div class="form-group">
                                                        <label>Reference No.</label>
                                                        <input type="text" id="refNo" placeholder="Optional">
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <div class="bill-summary" style="margin-top:0;">
                                                <div class="summary-row"><span>Subtotal</span><span id="sumSubtotal">₹0.00</span></div>
                                                <div class="summary-row"><span>Discount</span><span id="sumDiscount">₹0.00</span></div>
                                                <div class="summary-row total-row"><span>Grand Total</span><span id="sumTotal">₹0.00</span></div>
                                                
                                                <div style="margin-top:1.5rem;">
                                                    <button class="btn btn-success" id="btnGenerateBill" onclick="opdBilling.submitBill()" style="width:100%; justify-content:center; padding:0.8rem;">
                                                        <i class="fas fa-file-invoice-dollar"></i> Generate Bill & Close
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                            </div>
                        </div>
                    </div>



                </div><!-- /billing-right -->
            </div><!-- /billing-workspace -->

        </main>
    </div>
</div>

<!-- Bill Detail Modal -->
<div class="modal-overlay bill-detail-modal" id="billDetailModalOverlay">
    <div class="modal-card">
        <div class="modal-head">
            <h3><i class="fas fa-file-invoice"></i> Bill Details: <span id="detailBillId"></span></h3>
            <div style="margin-left:auto; display:flex; gap:10px; align-items:center;">
                <button class="btn btn-secondary" id="btnPrintDetail" title="Print this bill">
                    <i class="fas fa-print"></i> Print
                </button>
                <button class="btn-close-modal" onclick="opdBilling.hideBillDetails()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>
        <div class="modal-body" id="billDetailContent">
            <!-- Content dynamically loaded here -->
            <div class="loading-state" style="padding:4rem; text-align:center;">
                <i class="fas fa-spinner fa-spin" style="font-size:2rem; color:var(--teal);"></i>
                <p style="margin-top:1rem; color:#64748b;">Fetching bill details...</p>
            </div>
        </div>
    </div>
</div>

<!-- Referral Modal -->
<div class="modal-overlay referral-modal" id="referralModalOverlay">
    <div class="modal-card small">
        <div class="modal-head">
            <h3><i class="fas fa-user-plus"></i> Add New Referral</h3>
            <button class="btn-close-modal" onclick="opdBilling.hideReferralModal()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="modal-body">
            <div class="form-group mb-4">
                <label>Referral Name</label>
                <input type="text" id="newReferralName" placeholder="Enter full name">
            </div>
            <div class="form-group mb-4">
                <label>Mobile Number (Optional)</label>
                <input type="tel" id="newReferralPhone" placeholder="Optional 10-digit number" maxlength="10">
            </div>
            <div style="margin-top:1.5rem;">
                <button class="btn btn-primary" onclick="opdBilling.saveNewReferral()" style="width:100%; justify-content:center;">
                    <i class="fas fa-save"></i> Save Referral
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Settlement Modal (Professional Way) -->
<div class="modal-overlay" id="settlementModalOverlay">
    <div class="modal-card small" style="max-width:400px;">
        <div class="modal-head">
            <h3><i class="fas fa-money-check-alt"></i> Settle Balance</h3>
            <button class="btn-close-modal" onclick="opdBilling.hideSettlementModal()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="modal-body">
            <div style="background:#f0f9ff; border:1px solid #bae6fd; border-radius:10px; padding:1rem; margin-bottom:1.5rem; display:flex; align-items:center; gap:12px;">
                <div style="width:40px; height:40px; border-radius:50%; background:#7dd3fc; color:#0369a1; display:flex; align-items:center; justify-content:center; font-size:1.2rem;">
                    <i class="fas fa-wallet"></i>
                </div>
                <div>
                    <div style="font-size:0.75rem; color:#0369a1; font-weight:600; text-transform:uppercase;">Outstanding Balance</div>
                    <div style="font-size:1.25rem; font-weight:800; color:#0c4a6e;" id="settleBalanceDisplay">₹0.00</div>
                </div>
            </div>

            <div class="form-group mb-4">
                <label>Amount to Pay (₹)</label>
                <input type="number" id="settleAmount" step="0.01" style="font-size:1.1rem; font-weight:700; color:var(--teal);">
            </div>

            <div class="form-group mb-4">
                <label>Payment Mode</label>
                <select id="settleMode" style="width:100%; padding:0.6rem; border:1.5px solid #e2e8f0; border-radius:8px; outline:none;">
                    <option value="Cash">Cash</option>
                    <option value="UPI">UPI (PhonePe / GPay)</option>
                    <option value="Card">Credit / Debit Card</option>
                    <option value="Net Banking">Net Banking</option>
                    <option value="Cheque">Cheque</option>
                </select>
            </div>

            <div class="form-group mb-4">
                <label>Reference No. / UPI ID</label>
                <input type="text" id="settleRefNo" placeholder="Optional transaction ID">
            </div>

            <div style="margin-top:2rem;">
                <button class="btn btn-primary" onclick="opdBilling.submitSettlement()" style="width:100%; justify-content:center; padding:0.8rem; font-size:1rem; background:linear-gradient(135deg, var(--teal), #0d9488); border:none; box-shadow: 0 4px 12px rgba(31, 107, 74,0.3);">
                    <i class="fas fa-check-circle"></i> Confirm Payment
                </button>
            </div>
        </div>
    </div>
</div>
<div class="toast-container" id="toastContainer"></div>

<script>
    window.HOSPITAL_BRANCH = '<?= addslashes($_SESSION['hospital_branch'] ?? 'Nagarabhavi') ?>';
</script>
<script src="assets/js/opd_billing.js?v=<?= time() ?>"></script>
</body>
</html>
