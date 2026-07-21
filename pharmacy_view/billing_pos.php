<?php
// Extend session to 8 hours for full-shift use
ini_set('session.gc_maxlifetime', 28800);
ini_set('session.cookie_lifetime', 28800);
session_start();
if (!isset($_SESSION['user_id'])) {
  header("Location: ../login.php");
  exit;
}
require_once __DIR__ . '/../core/Autoloader.php';

use GM_HMS\Database\SecureDatabase;

$db = SecureDatabase::getInstance();
$setting = $db->fetchOne("SELECT setting_value FROM ph_settings WHERE setting_key = 'tax_rate_default'");
$defaultTax = $setting ? (float)$setting['setting_value'] : 12.0;

$doctors = $db->fetchAll("SELECT full_name FROM doctors WHERE status = 'Active' ORDER BY full_name ASC");

$pageTitle  = 'Billing / POS';
include 'includes/ph_head.php';
?>
<div class="ph-wrap">
  <?php include 'includes/pharmacy_sidebar.php'; ?>
  <div id="ph-content">
    <?php include 'includes/pharmacy_navbar.php'; ?>
    <div class="ph-page-body p-0" style="background: #f1f5f9; height: calc(100vh - 60px); display: flex; flex-direction: column; overflow: hidden;">

      <style>
        :root {
          --ph-primary: #1f6b4a;
          --ph-primary-dark: #096b6b;
          --ph-bg: #f8fafc;
          --ph-card-bg: #ffffff;
          --ph-border: #e2e8f0;
          --ph-text: #1e293b;
          --ph-muted: #64748b;
          --ph-danger: #ef4444;
          --ph-success: #10b981;
        }

        /* Keyboard Shortcuts Bar */
        .shortcuts-bar {
          background: #1e293b;
          color: #cbd5e1;
          padding: 8px 16px;
          font-size: 0.8rem;
          display: flex;
          align-items: center;
          justify-content: space-between;
          font-weight: 500;
        }

        .shortcut-item {
          display: inline-flex;
          align-items: center;
          gap: 6px;
          margin-right: 16px;
        }

        .key-badge {
          background: #334155;
          color: #fff;
          padding: 2px 6px;
          border-radius: 4px;
          font-weight: 700;
          font-size: 0.75rem;
          font-family: monospace;
        }

        /* 2-Column Layout */
        .pos-layout {
          display: flex;
          gap: 10px;
          padding: 8px 16px;
          align-items: stretch;
          flex: 1;
          min-height: 0;
        }

        .pos-left {
          flex: 1;
          min-width: 0;
          display: flex;
          flex-direction: column;
          gap: 10px;
          min-height: 0;
        }

        .pos-right {
          width: 320px;
          flex-shrink: 0;
          display: flex;
          flex-direction: column;
          gap: 8px;
          overflow: hidden;
        }

        .pos-card {
          background: var(--ph-card-bg);
          border-radius: 12px;
          box-shadow: 0 4px 12px rgba(0, 0, 0, 0.03);
          border: 1px solid var(--ph-border);
        }

        /* Search Bars */
        .search-container {
          display: grid;
          grid-template-columns: 1fr 1fr;
          gap: 12px;
          padding: 8px 12px;
        }

        .search-box-wrap {
          position: relative;
        }

        .search-header {
          display: flex;
          justify-content: space-between;
          align-items: center;
          margin-bottom: 8px;
        }

        .search-title {
          font-size: 0.75rem;
          font-weight: 800;
          color: var(--ph-primary);
          text-transform: uppercase;
          letter-spacing: 0.5px;
          display: flex;
          align-items: center;
          gap: 6px;
        }

        .ph-input-compact {
          width: 100%;
          padding: 10px 12px 10px 36px;
          font-size: 0.9rem;
          font-weight: 600;
          border: 1px solid var(--ph-border);
          border-radius: 8px;
          background: #f8fafc;
          transition: all 0.2s;
        }

        .ph-input-compact:focus {
          outline: none;
          border-color: var(--ph-primary);
          background: #fff;
          box-shadow: 0 0 0 3px rgba(31, 107, 74, 0.1);
        }

        .input-icon {
          position: absolute;
          left: 12px;
          bottom: 12px;
          color: var(--ph-muted);
          font-size: 0.9rem;
          pointer-events: none;
        }

        /* Cart Table Compact */
        .cart-header-bar {
          display: flex;
          justify-content: space-between;
          align-items: center;
          padding: 12px 16px;
          border-bottom: 1px solid var(--ph-border);
        }

        .cart-table {
          width: 100%;
          border-collapse: collapse;
        }

        .cart-table th {
          background: #f8fafc;
          color: var(--ph-muted);
          font-size: 0.7rem;
          font-weight: 800;
          text-transform: uppercase;
          padding: 8px 6px;
          border-bottom: 2px solid var(--ph-border);
          text-align: left;
          letter-spacing: 0.5px;
        }

        .cart-table td {
          padding: 5px 6px;
          border-bottom: 1px solid var(--ph-border);
          font-size: 0.82rem;
          font-weight: 600;
          vertical-align: middle;
        }

        .cart-table .row-input {
          width: 100%;
          padding: 4px;
          font-size: 0.8rem;
          font-weight: 700;
          text-align: center;
          border: 1px solid var(--ph-border);
          border-radius: 4px;
        }

        .cart-table .row-input:focus {
          border-color: var(--ph-primary);
          outline: none;
        }

        /* Sticky Summary Panel */
        .summary-header {
          background: var(--ph-primary);
          color: #fff;
          padding: 6px 12px;
          font-weight: 700;
          border-radius: 12px 12px 0 0;
          display: flex;
          justify-content: space-between;
          align-items: center;
        }

        .summary-body {
          padding: 8px 12px;
          display: flex;
          flex-direction: column;
          gap: 4px;
        }

        .sum-row {
          display: flex;
          justify-content: space-between;
          align-items: center;
          font-size: 0.8rem;
          font-weight: 600;
          color: var(--ph-text);
        }

        .sum-val {
          font-weight: 800;
          font-size: 0.85rem;
        }

        .sum-grand {
          background: #ecfdfd;
          padding: 6px 10px;
          border-radius: 8px;
          display: flex;
          justify-content: space-between;
          align-items: center;
          border: 1px solid #bcf0f4;
          margin-top: 2px;
        }

        .sum-grand .lbl {
          font-size: 0.7rem;
          font-weight: 800;
          color: var(--ph-primary-dark);
          text-transform: uppercase;
        }

        .sum-grand .val {
          font-size: 1.1rem;
          font-weight: 900;
          color: var(--ph-primary);
        }

        /* Payment Section */
        .pay-tabs {
          display: flex;
          gap: 4px;
          background: #f1f5f9;
          padding: 4px;
          border-radius: 8px;
          margin-bottom: 4px;
        }

        .pay-tab {
          flex: 1;
          text-align: center;
          padding: 4px;
          font-size: 0.75rem;
          font-weight: 700;
          color: var(--ph-muted);
          cursor: pointer;
          border-radius: 6px;
        }

        .pay-tab.active {
          background: var(--ph-primary);
          color: #fff;
          box-shadow: 0 2px 6px rgba(31, 107, 74, 0.2);
        }

        .pay-input-grp {
          margin-bottom: 4px;
        }

        .pay-input-grp label {
          display: block;
          font-size: 0.65rem;
          font-weight: 800;
          color: var(--ph-muted);
          text-transform: uppercase;
          margin-bottom: 2px;
        }

        .pay-input {
          width: 100%;
          padding: 6px;
          font-size: 0.85rem;
          font-weight: 800;
          border: 1px solid var(--ph-border);
          border-radius: 8px;
        }

        .btn-checkout {
          width: 100%;
          padding: 8px;
          background: var(--ph-text);
          color: #fff;
          font-size: 0.95rem;
          font-weight: 800;
          border: none;
          border-radius: 8px;
          display: flex;
          justify-content: center;
          align-items: center;
          gap: 8px;
          cursor: pointer;
          transition: all 0.2s;
        }

        .btn-checkout:hover {
          background: #0f172a;
        }

        .btn-checkout:disabled {
          opacity: 0.5;
          cursor: not-allowed;
        }

        .dropdown-menu {
          position: absolute;
          top: 100%;
          left: 0;
          right: 0;
          background: #fff;
          border: 1px solid var(--ph-border);
          border-radius: 8px;
          box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
          z-index: 1000;
          max-height: 250px;
          overflow-y: auto;
          display: none;
        }

        .dropdown-item {
          padding: 8px 12px;
          border-bottom: 1px solid #f1f5f9;
          cursor: pointer;
          font-size: 0.8rem;
        }

        .dropdown-item:hover,
        .dropdown-item.active {
          background: #f0fdf9;
        }

        /* Walk-in inline inputs */
        .walkin-grid {
          display: grid;
          grid-template-columns: 2fr 60px 100px;
          gap: 8px;
          margin-top: 8px;
        }

        .walkin-input {
          padding: 8px;
          font-size: 0.8rem;
          border: 1px solid var(--ph-border);
          border-radius: 6px;
          font-weight: 600;
        }

        /* Hide Number Input Spinners */
        input[type=number]::-webkit-inner-spin-button,
        input[type=number]::-webkit-outer-spin-button {
          -webkit-appearance: none;
          margin: 0;
        }

        input[type=number] {
          -moz-appearance: textfield;
        }
      </style>

      <!-- Shortcuts Bar -->
      <div class="shortcuts-bar">
        <div>
          <div class="shortcut-item"><span class="key-badge">F2</span> Medicine Search</div>
          <div class="shortcut-item"><span class="key-badge">Enter</span> Add to Cart</div>
          <div class="shortcut-item"><span class="key-badge">Tab</span> Cycle Qty&rarr;Disc&rarr;Rate</div>
        </div>
        <div>
          <div class="shortcut-item"><span class="key-badge">F9</span> Finalize &amp; Print</div>
          <div class="shortcut-item"><span class="key-badge">F6</span> Hold Bill</div>
          <div class="shortcut-item"><span class="key-badge">F5</span> Clear Cart</div>
          <div class="shortcut-item"><span class="key-badge">Esc</span> Close Dropdowns</div>
        </div>
      </div>

      <div class="pos-layout">

        <!-- LEFT COLUMN: Search & Cart -->
        <div class="pos-left">

          <!-- Top Search -->
          <div class="pos-card search-container">

            <!-- Patient Search -->
            <div class="search-box-wrap">
              <div class="search-header">
                <div class="search-title"><i class="fas fa-user-injured"></i> Patient</div>
                <div style="display:flex; gap:4px;">
                  <button class="btn btn-sm" id="btnModeSearch" style="font-size:0.7rem; font-weight:700; padding:2px 8px; background:#e2e8f0; color:var(--ph-muted); border-radius:4px; border:none;" onclick="setPatientMode('search')">Search</button>
                  <button class="btn btn-sm" id="btnModeWalkin" style="font-size:0.7rem; font-weight:700; padding:2px 8px; background:var(--ph-primary); color:#fff; border-radius:4px; border:none;" onclick="setPatientMode('walkin')">Walk-in</button>
                </div>
              </div>

              <div id="patSearchView" style="display:none;">
                <i class="fas fa-search input-icon"></i>
                <input type="text" id="patientSearch" class="ph-input-compact" placeholder="Search by ID, Name or Phone..." autocomplete="off">
                <div id="patDropdown" class="dropdown-menu"></div>

                <!-- Selected Patient Info -->
                <div id="selectedPatientInfo" style="display:none; margin-top:8px; padding:8px 12px; background:#ecfdfd; border:1px solid #bcf0f4; border-radius:8px;">
                  <div style="display:flex; justify-content:space-between; align-items:center;">
                    <div>
                      <div style="font-size:0.85rem; font-weight:800; color:var(--ph-primary-dark);" id="selPatName"></div>
                      <div style="font-size:0.75rem; font-weight:600; color:var(--ph-muted);" id="selPatDetails"></div>
                    </div>
                    <button class="btn btn-sm text-danger p-0 m-0" style="font-size:0.8rem;" onclick="clearPatient()"><i class="fas fa-times-circle"></i></button>
                  </div>
                </div>
              </div>

              <div id="patWalkinView">
                <input type="text" id="wiName" class="ph-input-compact" placeholder="Walk-in Customer Name" style="width:100%;" autocomplete="off">
                <div class="walkin-grid">
                  <input type="text" id="wiPhone" class="walkin-input" placeholder="Phone No." oninput="this.value = this.value.replace(/[^0-9]/g, '').slice(0, 10);" maxlength="10">
                  <input type="number" id="wiAge" class="walkin-input" placeholder="Age">
                  <select id="wiSex" class="walkin-input">
                    <option value="">Sex</option>
                    <option value="Male">Male</option>
                    <option value="Female">Female</option>
                    <option value="Other">Other</option>
                  </select>
                </div>
                <input list="doctorsList" id="wiDoctor" class="walkin-input" style="width:100%; margin-top:8px;" placeholder="Ref. Doctor (Optional)" autocomplete="off">
                <datalist id="doctorsList">
                  <?php foreach ($doctors as $doc): ?>
                    <option value="<?= htmlspecialchars($doc['full_name']) ?>"></option>
                  <?php endforeach; ?>
                </datalist>
              </div>
            </div>

            <!-- Medicine Search -->
            <div class="search-box-wrap" style="flex-shrink: 0;">
              <div class="search-header">
                <div class="search-title"><i class="fas fa-pills"></i> Medicine / Barcode</div>
                <div style="font-size:0.7rem; font-weight:700; color:var(--ph-muted); background:#f1f5f9; padding:2px 8px; border-radius:4px;"><span class="key-badge" style="background:#cbd5e1; color:#475569;">F2</span> to focus</div>
              </div>
              <div style="position:relative;">
                <i class="fas fa-barcode input-icon" style="color:var(--ph-primary);"></i>
                <input type="text" id="productSearch" class="ph-input-compact" placeholder="Scan barcode or search medicine..." autocomplete="off" style="border-color:#bcf0f4; background:#f4fcfc;">
                <div id="prodDropdown" class="dropdown-menu"></div>
              </div>
            </div>

          </div>

          <!-- Cart Table -->
          <div class="pos-card" style="display: flex; flex-direction: column; flex: 1; min-height: 0;">
            <div class="cart-header-bar" style="flex-shrink: 0;">
              <div style="font-size:0.9rem; font-weight:800; color:var(--ph-primary-dark); display:flex; align-items:center; gap:8px;">
                <i class="fas fa-shopping-basket"></i> Cart Items
                <span id="badgeItems" style="background:#e0f2fe; color:#0284c7; padding:2px 8px; border-radius:12px; font-size:0.7rem;">0 Items</span>
                <span id="badgeQty" style="background:#dcfce7; color:#166534; padding:2px 8px; border-radius:12px; font-size:0.7rem;">0 Qty</span>
              </div>
              <button class="btn btn-sm" style="font-size:0.75rem; font-weight:700; border:1px solid var(--ph-border); background:#fff;" onclick="addManualRow()"><i class="fas fa-plus"></i> Add Row</button>
            </div>

            <!-- Scrolling wrapper for table -->
            <div style="width:100%; flex: 1; overflow-y: auto; overflow-x: auto;">
              <table class="cart-table">
                <thead>
                  <tr>
                    <th style="width:30px; text-align:center;">SL NO</th>
                    <th style="width:180px;">Description</th>
                    <th style="width:70px; text-align:center;">HSN</th>
                    <th style="width:90px;">Manufacturer</th>
                    <th style="width:70px; text-align:center;">Batch</th>
                    <th style="width:60px; text-align:center;">Expiry</th>
                    <th style="width:60px; text-align:center;">Qty</th>
                    <th style="width:110px; text-align:right; white-space:nowrap;">MRP (₹)</th>
                    <th style="width:50px; text-align:center;">Disc%</th>
                    <th style="width:60px; text-align:center; white-space:nowrap;">Disc ₹</th>
                    <th style="width:50px; text-align:center;">GST%</th>
                    <th style="width:110px; text-align:right; padding-right:12px; white-space:nowrap;">Total ₹</th>
                    <th style="width:30px;"></th>
                  </tr>
                </thead>
                <tbody id="cartBody">
                  <tr id="emptyCart">
                    <td colspan="16" style="text-align:center; padding:60px 20px; color:#cbd5e1;">
                      <i class="fas fa-box-open" style="font-size:3rem; margin-bottom:12px;"></i>
                      <div style="font-weight:800; font-size:1.1rem; color:#64748b;">Cart is empty</div>
                      <div style="font-size:0.8rem; font-weight:600;">Scan or search a medicine above &middot; Press <span class="key-badge">F2</span> to focus</div>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>

        </div>

        <!-- RIGHT COLUMN: Sticky Summary -->
        <div class="pos-right">

          <div class="pos-card" style="overflow:hidden;">
            <div class="summary-header">
              <div><i class="fas fa-receipt me-1"></i> Bill Summary</div>
              <div style="font-size:0.75rem; background:rgba(0,0,0,0.15); padding:2px 8px; border-radius:4px;"><?= date('d M Y') ?></div>
            </div>
            <div class="summary-body">

              <div class="sum-row">
                <span style="color:var(--ph-muted);">Items / Qty</span>
                <span class="sum-val" id="sumItemsQty" style="color:var(--ph-primary);">0 / 0</span>
              </div>
              <div class="sum-row">
                <span style="color:var(--ph-muted);">Total</span>
                <span class="sum-val" id="sumSubtotal">₹0.00</span>
              </div>
              <div class="sum-row" style="align-items:center;">
                <span style="color:var(--ph-muted);">Discount</span>
                <div style="display:flex; align-items:center; gap:8px;">
                  <input type="number" id="globalDiscount" class="row-input" style="width:60px; font-size:0.85rem;" value="0" min="0" oninput="recalc()">
                  <span class="sum-val" id="sumDiscount" style="color:var(--ph-danger);">-₹0.00</span>
                </div>
              </div>
              <div class="sum-row" style="border-bottom:1px dashed var(--ph-border); padding-bottom:8px;">
                <!-- Tax omitted from subtotal addition -->
              </div>

              <div class="sum-grand">
                <span class="lbl">Net Payable</span>
                <span class="val" id="sumGrand">₹0.00</span>
              </div>

            </div>
          </div>

          <div class="pos-card" style="overflow:hidden;">
            <div class="summary-header" style="background:#1e293b;">
              <div><i class="fas fa-wallet me-1"></i> Payment</div>
            </div>
            <div class="summary-body">

              <div class="pay-tabs">
                <div class="pay-tab active" id="tabDirect" onclick="setPayMode('cash')"><i class="fas fa-money-bill"></i> Direct</div>
                <div class="pay-tab" id="tabCredit" onclick="setPayMode('credit')"><i class="fas fa-id-card"></i> Credit</div>
                <div class="pay-tab" id="tabSplit" onclick="setPayMode('split')"><i class="fas fa-bolt"></i> Split</div>
              </div>

              <!-- Direct Mode -->
              <div id="payDirectView">
                <div class="pay-input-grp">
                  <label>Payment Method</label>
                  <select id="payMethod" class="pay-input" style="font-size:0.9rem; padding:8px;">
                    <option value="cash">💵 Cash</option>
                    <option value="upi">📱 UPI</option>
                    <option value="card">💳 Card</option>
                    <option value="card">💳 Affordplan</option>
                    <option value="card">💳 DD</option>

                  </select>
                </div>
                <div class="pay-input-grp">
                  <label>Amount Received (₹)</label>
                  <input type="number" id="payReceived" class="pay-input" style="color:var(--ph-success); background:#f0fdf4; border-color:#bbf7d0;" value="0" min="0" oninput="recalc()">
                </div>

                <div class="sum-row mt-2" style="margin-bottom:0;">
                  <span style="color:var(--ph-muted); font-size:0.75rem; font-weight:800; text-transform:uppercase;">Change Due</span>
                  <span class="sum-val" id="payBalance" style="color:var(--ph-success); font-size:1.1rem;">₹0.00</span>
                </div>
              </div>

              <!-- Credit Mode -->
              <div id="payCreditView" style="display:none;">
                <div class="pay-input-grp">
                  <label>Select Sponsor</label>
                  <select id="paySponsor" class="pay-input" style="font-size:0.9rem; padding:8px; border-color:var(--ph-primary); background:#f4fcfc;">
                    <option value="">-- Choose Sponsor --</option>
                  </select>
                </div>
                <div style="font-size:0.75rem; color:#b91c1c; background:#fef2f2; padding:8px; border-radius:6px; border:1px dashed #f87171; font-weight:600;">
                  <i class="fas fa-info-circle"></i> Entire balance will be assigned to the Sponsor.
                </div>
              </div>

              <!-- Split Mode -->
              <div id="paySplitView" style="display:none;">
                <div id="splitContainer" style="display:flex; flex-direction:column; gap:8px;"></div>
                <button class="btn btn-sm w-100 mt-2" style="background:#f1f5f9; color:var(--ph-text); font-weight:700; border:1px dashed var(--ph-border);" onclick="addSplitRow()"><i class="fas fa-plus"></i> Add Mode</button>
                <div class="sum-row mt-3 pt-2" style="border-top:1px solid var(--ph-border);">
                  <span style="color:var(--ph-muted); font-size:0.75rem; font-weight:800; text-transform:uppercase;">Change Due</span>
                  <span class="sum-val" id="splitBalance" style="color:var(--ph-success); font-size:1.1rem;">₹0.00</span>
                </div>
              </div>

            </div>
          </div>

          <button class="btn-checkout" id="btnCheckout" onclick="checkout()">
            <i class="fas fa-check-circle"></i> Finalize &amp; Print <span class="key-badge" style="background:rgba(255,255,255,0.2); color:#fff; border:1px solid rgba(255,255,255,0.4); margin-left:4px;">F9</span>
          </button>

        </div>
      </div>

    </div>
  </div>
</div>
<?php include 'includes/ph_foot.php'; ?>

<script>
  const DEFAULT_TAX = <?= $defaultTax ?>;
  const API = API_BASE + 'pharmacy/billing';

  let cart = [];
  let splitPayments = [];
  let currentPatMode = 'walkin';
  let currentPayMode = 'cash';
  let activeSearchIdx = -1;
  let patSearchTimeout, prodSearchTimeout;

  // Global Patient Data
  let g_patId = '',
    g_patName = '',
    g_patPhone = '',
    g_patAge = '',
    g_patSex = '';

  document.addEventListener('DOMContentLoaded', async () => {
    // Load Sponsors
    try {
      const res = await fetch(API + '/sponsors');
      const json = await res.json();
      if (json.success && json.data) {
        document.getElementById('paySponsor').innerHTML += json.data.map(s => `<option value="${s.name}">${s.name}</option>`).join('');
      }
    } catch (e) {}

    restoreHeldBill();
  });

  // --- Keyboard Shortcuts & Navigation ---
  document.addEventListener('keydown', e => {
    // F2: Medicine Search
    if (e.key === 'F2') {
      e.preventDefault();
      document.getElementById('productSearch').focus();
    }
    // F5: Clear
    else if (e.key === 'F5') {
      e.preventDefault();
      clearCart(false);
    }
    // F6: Hold Bill
    else if (e.key === 'F6') {
      e.preventDefault();
      holdBill();
    }
    // F9: Checkout
    else if (e.key === 'F9') {
      e.preventDefault();
      if (cart.length > 0) checkout();
    }
    // Esc: Close dropdowns
    else if (e.key === 'Escape') {
      closeDropdowns();
    }

    // Dropdown Arrow Navigation
    const prodDd = document.getElementById('prodDropdown');
    if (prodDd.style.display === 'block' && ['ArrowDown', 'ArrowUp', 'Enter'].includes(e.key)) {
      e.preventDefault();
      const items = prodDd.querySelectorAll('.dropdown-item');
      if (!items.length) return;

      if (e.key === 'ArrowDown') {
        activeSearchIdx = (activeSearchIdx + 1) % items.length;
        updateDropdownHighlight(items);
      } else if (e.key === 'ArrowUp') {
        activeSearchIdx = activeSearchIdx <= 0 ? items.length - 1 : activeSearchIdx - 1;
        updateDropdownHighlight(items);
      } else if (e.key === 'Enter' && activeSearchIdx >= 0) {
        items[activeSearchIdx].click();
      }
    }
  });

  function updateDropdownHighlight(items) {
    items.forEach(el => el.classList.remove('active'));
    if (items[activeSearchIdx]) {
      items[activeSearchIdx].classList.add('active');
      items[activeSearchIdx].scrollIntoView({
        block: 'nearest'
      });
    }
  }

  function closeDropdowns() {
    document.getElementById('patDropdown').style.display = 'none';
    document.getElementById('prodDropdown').style.display = 'none';
    activeSearchIdx = -1;
  }
  document.addEventListener('click', e => {
    if (!e.target.closest('.search-box-wrap')) closeDropdowns();
  });

  // --- Patient Logic ---
  function setPatientMode(mode) {
    currentPatMode = mode;
    document.getElementById('patSearchView').style.display = mode === 'search' ? 'block' : 'none';
    document.getElementById('patWalkinView').style.display = mode === 'walkin' ? 'block' : 'none';

    document.getElementById('btnModeSearch').style.background = mode === 'search' ? 'var(--ph-primary)' : '#e2e8f0';
    document.getElementById('btnModeSearch').style.color = mode === 'search' ? '#fff' : 'var(--ph-muted)';

    document.getElementById('btnModeWalkin').style.background = mode === 'walkin' ? 'var(--ph-primary)' : '#e2e8f0';
    document.getElementById('btnModeWalkin').style.color = mode === 'walkin' ? '#fff' : 'var(--ph-muted)';

    if (mode === 'walkin') clearPatientData();
  }

  document.getElementById('patientSearch').addEventListener('input', function() {
    clearTimeout(patSearchTimeout);
    const q = this.value.trim();
    if (q.length < 2) {
      document.getElementById('patDropdown').style.display = 'none';
      return;
    }
    patSearchTimeout = setTimeout(async () => {
      try {
        const res = await fetch(API + '/patients?q=' + encodeURIComponent(q));
        const json = await res.json();
        const dd = document.getElementById('patDropdown');
        if (json.success && json.data.length) {
          window._patData = json.data;
          dd.innerHTML = json.data.map((p, i) => `
                    <div class="dropdown-item" onclick="selectPatient(${i})">
                        <div style="font-weight:700; color:var(--ph-primary);">${p.patient_id}</div>
                        <div style="font-weight:600;">${p.patient_name || 'Unknown'}</div>
                        <div style="font-size:0.7rem; color:var(--ph-muted);">${p.phone||''} &middot; ${p.age?p.age+' yrs':''} ${p.sex||''}</div>
                    </div>
                `).join('');
        } else {
          dd.innerHTML = `<div style="padding:12px;text-align:center;color:var(--ph-muted);">No patients found</div>`;
        }
        dd.style.display = 'block';
      } catch (e) {}
    }, 300);
  });

  async function selectPatient(idx) {
    const p = window._patData[idx];
    closeDropdowns();
    g_patId = p.patient_id;
    g_patName = p.patient_name || p.patient_id;
    g_patPhone = p.phone || '';
    g_patAge = p.age || '';
    g_patSex = p.sex || '';

    document.getElementById('patientSearch').value = '';
    document.getElementById('patientSearch').style.display = 'none';
    document.querySelector('#patSearchView .input-icon').style.display = 'none';

    document.getElementById('selPatName').textContent = g_patName;
    document.getElementById('selPatDetails').textContent = `${g_patId} \u2022 ${g_patPhone} \u2022 ${g_patAge?g_patAge+'yrs':''} ${g_patSex}`;
    document.getElementById('selectedPatientInfo').style.display = 'block';

    // Suggestions
    try {
      const res = await fetch(API + '/prescriptions?patient_id=' + encodeURIComponent(g_patId));
      const json = await res.json();
      if (json.success && json.data.length > 0 && json.data[0].medicines) {
        for (const m of json.data[0].medicines) addRxToCart(m);
      }
    } catch (e) {}
  }

  function clearPatient() {
    clearPatientData();
    document.getElementById('patientSearch').style.display = 'block';
    document.querySelector('#patSearchView .input-icon').style.display = 'block';
    document.getElementById('selectedPatientInfo').style.display = 'none';
    document.getElementById('patientSearch').focus();
  }

  function clearPatientData() {
    g_patId = '';
    g_patName = '';
    g_patPhone = '';
    g_patAge = '';
    g_patSex = '';
  }

  // --- Product Logic ---
  document.getElementById('productSearch').addEventListener('input', function() {
    clearTimeout(prodSearchTimeout);
    const q = this.value.trim();
    activeSearchIdx = -1;
    if (q.length < 2) {
      document.getElementById('prodDropdown').style.display = 'none';
      return;
    }
    prodSearchTimeout = setTimeout(async () => {
      try {
        const res = await fetch(API + '/products?q=' + encodeURIComponent(q));
        const json = await res.json();
        const dd = document.getElementById('prodDropdown');
        if (json.success && json.data.length) {
          window._prodData = json.data;
          dd.innerHTML = json.data.map((p, i) => `
                    <div class="dropdown-item" onclick="selectProduct(${i})">
                        <div style="font-weight:700; color:var(--ph-text); display:flex; justify-content:space-between; align-items:center;">
                            <span>${p.product_name} ${p.strength||''}</span>
                            <span style="background:${p.quantity < 20 ? 'var(--ph-danger)' : 'var(--ph-primary)'}; color:#fff; padding:2px 6px; border-radius:4px; font-size:0.7rem; font-weight:800; letter-spacing:0.5px;">[${p.quantity}]</span>
                        </div>
                        <div style="display:flex; justify-content:space-between; font-size:0.7rem; color:var(--ph-muted); margin-top:2px;">
                            <span>Batch: ${p.batch_number||'—'}</span>
                            <span>MRP: ₹${p.mrp||'0.00'}</span>
                        </div>
                    </div>
                `).join('');
          dd.style.display = 'block';
        } else {
          dd.innerHTML = `<div style="padding:12px;text-align:center;color:var(--ph-muted);">No products found</div>`;
          dd.style.display = 'block';
        }
      } catch (e) {}
    }, 250);
  });

  function selectProduct(idx) {
    const p = window._prodData[idx];
    if (p) addToCart(p);
  }

  async function addRxToCart(med) {
    let name = (med.medicine_name || med.name || '').replace(/^(Tab\.|Syp\.|Inj\.|Cap\.|Drops|Oint\.)\s*/i, '').trim();
    if (!name) return;
    try {
      let res = await fetch(API + '/products?q=' + encodeURIComponent(name));
      let json = await res.json();
      if (json.success && json.data.length > 0) {
        addToCart(json.data[0], true);
      } else {
        cart.push({
          product_id: 'RX-' + Date.now() + Math.floor(Math.random() * 1000),
          product_name: name,
          batch_no: 'NOT IN DB',
          qty: '',
          max_qty: 9999,
          rate: 0,
          discount_pct: 0,
          discount_amount: 0,
          tax_pct: DEFAULT_TAX,
          gst_price: DEFAULT_TAX,
          hsn_code: '',
          manufacturer: '',
          expiry_date: '',
          manual: true
        });
        renderCart();
      }
    } catch (e) {}
  }

  // --- Cart Logic ---
  function addToCart(p, silent = false) {
    closeDropdowns();
    document.getElementById('productSearch').value = '';

    const existing = cart.findIndex(c => c.product_id === p.product_id);
    if (existing >= 0) {
      let currentQty = parseInt(cart[existing].qty) || 0;
      if (currentQty >= p.quantity) {
        return;
      }
      cart[existing].qty = currentQty + 1;
    } else {
      cart.push({
        product_id: p.product_id,
        product_name: p.product_name,
        batch_no: p.batch_number || '',
        qty: '',
        max_qty: parseInt(p.quantity) || 0,
        rate: parseFloat(p.individual_rate) || parseFloat(p.mrp) || 0,
        discount_pct: 0,
        discount_amount: 0,
        tax_pct: parseFloat(p.tax_percent) || DEFAULT_TAX,
        gst_price: p.GST_price || p.tax_percent || DEFAULT_TAX,
        hsn_code: p.hsn_code || '',
        manufacturer: p.manufacturer || '',
        expiry_date: p.expiry_date || '',
        manual: false
      });
    }
    renderCart();

    // Keyboard friendly: Focus Qty of newly added row
    setTimeout(() => {
      const rows = document.querySelectorAll('.cart-table tbody tr:not(#emptyCart)');
      if (rows.length > 0) {
        const lastRowQty = rows[rows.length - 1].querySelector('.inp-qty');
        if (lastRowQty) {
          lastRowQty.focus();
          lastRowQty.select();
        }
      }
    }, 50);
  }

  function addManualRow() {
    cart.push({
      product_id: 'MNL-' + Date.now(),
      product_name: '',
      batch_no: '',
      qty: '',
      max_qty: 9999,
      rate: 0,
      discount_pct: 0,
      discount_amount: 0,
      tax_pct: DEFAULT_TAX,
      gst_price: DEFAULT_TAX,
      hsn_code: '',
      manufacturer: '',
      expiry_date: '',
      manual: true
    });
    renderCart();
  }

  function removeCartItem(i) {
    cart.splice(i, 1);
    renderCart();
  }

  function renderCart() {
    const tbody = document.getElementById('cartBody');
    let totalQty = 0;
    if (!cart.length) {
      tbody.innerHTML = `<tr id="emptyCart"><td colspan="16" style="text-align:center; padding:60px 20px; color:#cbd5e1;"><i class="fas fa-box-open" style="font-size:3rem; margin-bottom:12px;"></i><div style="font-weight:800; font-size:1.1rem; color:#64748b;">Cart is empty</div><div style="font-size:0.8rem; font-weight:600;">Scan or search a medicine above &middot; Press <span class="key-badge">F2</span> to focus</div></td></tr>`;
      updateBadges(0, 0);
      recalc();
      return;
    }

    tbody.innerHTML = cart.map((c, i) => {
      const currentQty = parseInt(c.qty) || 0;
      totalQty += currentQty;
      const cgstPct = c.tax_pct / 2,
        sgstPct = c.tax_pct / 2;
      const gross = currentQty * c.rate;
      const lineDisc = gross * c.discount_pct / 100;
      const taxable = gross - lineDisc;
      const lineTax = taxable - (taxable / (1 + c.tax_pct / 100));
      const cgstAmt = lineTax / 2,
        sgstAmt = lineTax / 2;
      const subtotal = taxable;

      const nm = c.manual ? `<input class="row-input" style="text-align:left;font-size:0.8rem;" value="${c.product_name}" oninput="cart[${i}].product_name=this.value">` : `<div style="font-weight:700;color:var(--ph-text);font-size:0.8rem;">${c.product_name}</div>`;
      const hs = c.manual ? `<input class="row-input" value="${c.hsn_code||''}" oninput="cart[${i}].hsn_code=this.value">` : `<div style="font-size:0.75rem;color:var(--ph-muted);font-family:monospace;">${c.hsn_code||'—'}</div>`;
      const mf = c.manual ? `<input class="row-input" style="text-align:left;" value="${c.manufacturer||''}" oninput="cart[${i}].manufacturer=this.value">` : `<div style="font-size:0.75rem;color:var(--ph-muted);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:90px;" title="${c.manufacturer||''}">${c.manufacturer||'—'}</div>`;
      const bt = c.manual ? `<input class="row-input" value="${c.batch_no||''}" oninput="cart[${i}].batch_no=this.value">` : `<div style="font-size:0.75rem;color:var(--ph-muted);font-family:monospace;">${c.batch_no||'—'}</div>`;
      const ex = c.manual ? `<input class="row-input" value="${c.expiry_date||''}" oninput="cart[${i}].expiry_date=this.value">` : `<div style="font-size:0.75rem;color:var(--ph-muted);font-family:monospace;">${c.expiry_date?c.expiry_date.substring(0,7):'—'}</div>`;

      return `<tr>
            <td style="text-align:center; color:var(--ph-muted); font-size:0.75rem; font-weight:700;">${i+1}</td>
            <td>${nm}</td>
            <td style="text-align:center;">${hs}</td>
            <td>${mf}</td>
            <td style="text-align:center;">${bt}</td>
            <td style="text-align:center;">${ex}</td>
            <td><input type="number" class="row-input inp-qty tab-grp-${i}" data-row="${i}" value="${c.qty}" min="1" max="${c.max_qty}" oninput="updateRow(${i}, 'qty', this.value)"></td>
            <td><input type="number" class="row-input inp-rate tab-grp-${i}" style="text-align:right;" value="${c.rate.toFixed(2)}" oninput="updateRow(${i}, 'rate', this.value)"></td>
            <td><input type="number" class="row-input inp-disc tab-grp-${i}" style="color:var(--ph-success); background:#ecfdf5; border-color:#a7f3d0;" value="${c.discount_pct||0}" min="0" oninput="updateRow(${i}, 'disc', this.value)"></td>
            <td id="td-da-${i}" style="text-align:center; color:var(--ph-success); font-size:0.8rem; font-weight:700;">${lineDisc.toFixed(2)}</td>
            <td style="text-align:center; color:var(--ph-muted); font-size:0.8rem; font-weight:700;">${c.gst_price}</td>
            <td id="td-tot-${i}" style="text-align:right; font-weight:800; color:var(--ph-primary-dark); font-size:0.85rem; padding-right:12px;">${subtotal.toFixed(2)}</td>
            <td style="text-align:center;"><button class="btn btn-sm text-danger p-0 m-0" onclick="removeCartItem(${i})"><i class="fas fa-times"></i></button></td>
        </tr>`;
    }).join('');

    updateBadges(cart.length, totalQty);

    // Setup Tab cycling within cart
    setupCartTabbing();
    recalc();
  }

  function updateBadges(items, qty) {
    document.getElementById('badgeItems').textContent = `${items} Items`;
    document.getElementById('badgeQty').textContent = `${qty} Qty`;
    document.getElementById('sumItemsQty').textContent = `${items} / ${qty}`;
  }

  function updateRow(i, field, val) {
    const c = cart[i];
    if (field === 'qty') {
      let v = val === '' ? '' : parseInt(val);
      if (v > c.max_qty && !c.manual) {
        v = c.max_qty;
      }
      c.qty = v;
    } else if (field === 'rate') {
      c.rate = parseFloat(val) || 0;
    } else if (field === 'disc') {
      c.discount_pct = parseFloat(val) || 0;
    }

    const currentQty = parseInt(c.qty) || 0;
    const gross = currentQty * c.rate;
    const lineDisc = gross * c.discount_pct / 100;
    const taxable = gross - lineDisc;
    const lineTax = taxable - (taxable / (1 + c.tax_pct / 100));
    const subtotal = taxable;

    document.getElementById(`td-da-${i}`).textContent = lineDisc.toFixed(2);
    document.getElementById(`td-tot-${i}`).textContent = subtotal.toFixed(2);

    let totalQty = cart.reduce((s, x) => s + (parseInt(x.qty) || 0), 0);
    updateBadges(cart.length, totalQty);

    recalc();
  }

  function setupCartTabbing() {
    const rows = document.querySelectorAll('.cart-table tbody tr:not(#emptyCart)');
    rows.forEach((row, i) => {
      const qty = row.querySelector('.inp-qty');
      const disc = row.querySelector('.inp-disc');
      const rate = row.querySelector('.inp-rate');

      if (qty && disc && rate) {
        qty.addEventListener('keydown', e => {
          if (e.key === 'Tab' && !e.shiftKey) {
            e.preventDefault();
            disc.focus();
            disc.select();
          }
        });
        disc.addEventListener('keydown', e => {
          if (e.key === 'Tab' && !e.shiftKey) {
            e.preventDefault();
            rate.focus();
            rate.select();
          }
        });
        rate.addEventListener('keydown', e => {
          if (e.key === 'Tab' && !e.shiftKey) {
            e.preventDefault();
            if (i < rows.length - 1) {
              const nq = rows[i + 1].querySelector('.inp-qty');
              if (nq) {
                nq.focus();
                nq.select();
              }
            } else {
              document.getElementById('globalDiscount').focus();
              document.getElementById('globalDiscount').select();
            }
          }
        });
      }
    });
  }

  function recalc() {
    let sub = 0,
      tax = 0,
      grand = 0;
    cart.forEach(c => {
      const currentQty = parseInt(c.qty) || 0;
      const g = currentQty * c.rate;
      const d = g * c.discount_pct / 100;
      const taxable = g - d;
      const t = taxable - (taxable / (1 + c.tax_pct / 100));
      sub += g;
      tax += t;
      grand += taxable;
    });
    const gDisc = parseFloat(document.getElementById('globalDiscount').value) || 0;
    const net = Math.max(0, grand - gDisc);

    document.getElementById('sumSubtotal').textContent = `₹${sub.toFixed(2)}`;
    document.getElementById('sumDiscount').textContent = `-₹${gDisc.toFixed(2)}`;
    document.getElementById('sumGrand').textContent = `₹${net.toFixed(2)}`;

    let bal = 0;
    if (currentPayMode === 'split') {
      const paid = splitPayments.reduce((s, p) => s + (p.amount || 0), 0);
      bal = paid - net;
      document.getElementById('splitBalance').textContent = `₹${bal.toFixed(2)}`;
      document.getElementById('splitBalance').style.color = bal < 0 ? 'var(--ph-danger)' : 'var(--ph-success)';
    } else {
      const paid = parseFloat(document.getElementById('payReceived').value) || 0;
      bal = paid - net;
      document.getElementById('payBalance').textContent = `₹${bal.toFixed(2)}`;
      document.getElementById('payBalance').style.color = bal < 0 ? 'var(--ph-danger)' : 'var(--ph-success)';
    }

    document.getElementById('btnCheckout').disabled = cart.length === 0;
  }

  // --- Payment & Split Logic ---
  function setPayMode(mode) {
    currentPayMode = mode;
    document.getElementById('tabDirect').className = mode === 'cash' ? 'pay-tab active' : 'pay-tab';
    document.getElementById('tabCredit').className = mode === 'credit' ? 'pay-tab active' : 'pay-tab';
    document.getElementById('tabSplit').className = mode === 'split' ? 'pay-tab active' : 'pay-tab';

    document.getElementById('payDirectView').style.display = mode === 'cash' ? 'block' : 'none';
    document.getElementById('payCreditView').style.display = mode === 'credit' ? 'block' : 'none';
    document.getElementById('paySplitView').style.display = mode === 'split' ? 'block' : 'none';

    if (mode === 'credit') document.getElementById('payReceived').value = 0;
    if (mode === 'split' && splitPayments.length === 0) {
      addSplitRow('cash');
      addSplitRow('upi');
    }
    recalc();
  }

  function addSplitRow(def = 'upi') {
    splitPayments.push({
      method: def,
      amount: 0
    });
    renderSplitRows();
  }

  function removeSplitRow(i) {
    if (splitPayments.length <= 1) return;
    splitPayments.splice(i, 1);
    renderSplitRows();
  }

  function renderSplitRows() {
    document.getElementById('splitContainer').innerHTML = splitPayments.map((p, i) => `
        <div style="display:flex; gap:8px;">
            <select class="pay-input" style="flex:1; padding:6px; font-size:0.8rem;" onchange="splitPayments[${i}].method=this.value">
                <option value="cash" ${p.method==='cash'?'selected':''}>Cash</option>
                <option value="upi" ${p.method==='upi'?'selected':''}>UPI</option>
                <option value="card" ${p.method==='card'?'selected':''}>Card</option>
            </select>
            <input type="number" class="pay-input" style="width:100px; padding:6px; font-size:0.9rem;" value="${p.amount||0}" oninput="splitPayments[${i}].amount=parseFloat(this.value)||0; recalc()">
            <button class="btn btn-sm text-danger" style="border:1px solid #f1f5f9; background:#fff;" onclick="removeSplitRow(${i})"><i class="fas fa-times"></i></button>
        </div>
    `).join('');
    recalc();
  }

  // --- Checkout ---
  function clearCart(bypass = false) {
    if (!bypass && cart.length > 0) {
      if (!confirm('Clear all items?')) return;
    }
    cart = [];
    document.getElementById('globalDiscount').value = 0;
    document.getElementById('payReceived').value = 0;
    if (currentPatMode === 'search') clearPatient();
    renderCart();
  }

  function holdBill() {
    if (cart.length === 0) {
      PH.warning('Cart is empty.');
      return;
    }
    const state = {
      cart,
      g_patId,
      g_patName,
      g_patPhone,
      currentPatMode,
      wiName: document.getElementById('wiName').value,
      disc: document.getElementById('globalDiscount').value
    };
    localStorage.setItem('held_ph_bill', JSON.stringify(state));
    PH.success('Bill Held. You can restore it later.');
    clearCart(true);
  }

  function restoreHeldBill() {
    const data = localStorage.getItem('held_ph_bill');
    if (data) {
      try {
        const state = JSON.parse(data);
        if (state.cart && state.cart.length > 0) {
          if (confirm('A held bill was found. Restore it?')) {
            cart = state.cart;
            document.getElementById('globalDiscount').value = state.disc || 0;
            if (state.currentPatMode === 'search' && state.g_patId) {
              setPatientMode('search');
              g_patId = state.g_patId;
              g_patName = state.g_patName;
              document.getElementById('patientSearch').style.display = 'none';
              document.querySelector('#patSearchView .input-icon').style.display = 'none';
              document.getElementById('selPatName').textContent = g_patName;
              document.getElementById('selPatDetails').textContent = g_patId;
              document.getElementById('selectedPatientInfo').style.display = 'block';
            } else if (state.currentPatMode === 'walkin') {
              setPatientMode('walkin');
              document.getElementById('wiName').value = state.wiName || '';
            }
            renderCart();
            localStorage.removeItem('held_ph_bill');
          }
        }
      } catch (e) {}
    }
  }

  async function checkout() {
    if (cart.length === 0) return;

    let paid = 0,
      pay = 'cash',
      sponsor = null,
      arr = [];
    const disc = parseFloat(document.getElementById('globalDiscount').value) || 0;

    let sub = 0,
      tax = 0,
      grand = 0;
    cart.forEach(c => {
      const currentQty = parseInt(c.qty) || 0;
      const g = currentQty * c.rate;
      const d = g * c.discount_pct / 100;
      const taxable = g - d;
      const t = taxable - (taxable / (1 + c.tax_pct / 100));
      grand += taxable;
    });
    const gFinal = Math.max(0, grand - disc);

    if (currentPayMode === 'cash') {
      pay = document.getElementById('payMethod').value;
      paid = parseFloat(document.getElementById('payReceived').value) || 0;
    } else if (currentPayMode === 'credit') {
      pay = 'credit';
      sponsor = document.getElementById('paySponsor').value;
      if (!sponsor) {
        PH.warning('Select a sponsor');
        return;
      }
    } else if (currentPayMode === 'split') {
      pay = 'split';
      paid = splitPayments.reduce((s, p) => s + (p.amount || 0), 0);
      arr = splitPayments.filter(p => p.amount > 0);
      if (arr.length === 0) {
        PH.warning('Enter split amounts');
        return;
      }
      if (paid < gFinal) {
        PH.warning('Split amounts less than payable');
        return;
      }
    }

    let cName = 'Walk-in',
      cId = '',
      cPhone = '',
      cAge = null,
      cSex = null,
      cDoc = null;
    if (currentPatMode === 'search') {
      cId = g_patId;
      cName = g_patName || 'WALK-IN';
      cPhone = g_patPhone;
      cAge = g_patAge;
      cSex = g_patSex;
    } else {
      cName = document.getElementById('wiName').value.trim() || 'WALK-IN';
      cPhone = document.getElementById('wiPhone').value.trim();
      cAge = document.getElementById('wiAge').value.trim();
      cSex = document.getElementById('wiSex').value.trim();
      cDoc = document.getElementById('wiDoctor').value.trim();
    }

    const body = {
      customer_id: cId,
      customer_name: cName.toUpperCase(),
      customer_phone: cPhone,
      customer_age: cAge,
      customer_gender: cSex,
      doctor_name: cDoc,
      patient_type: currentPatMode === 'search' ? 'PATIENT' : 'WALK-IN',
      payment_method: pay,
      payments: arr,
      sponsor: sponsor,
      paid_amount: paid,
      discount_amount: disc,
      cart: cart.map(c => ({
        product_id: c.product_id,
        product_name: c.product_name.toUpperCase(),
        batch_no: c.batch_no.toUpperCase(),
        qty: (parseInt(c.qty) || 0),
        rate: c.rate,
        discount_percent: c.discount_pct,
        tax_percent: c.tax_pct
      }))
    };

    PH.loading('Finalizing...');
    try {
      const res = await fetch(API + '/checkout', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify(body)
      });
      const json = await res.json();
      PH.close();
      if (!json.success) {
        PH.error(json.error || 'Checkout failed');
        return;
      }

      const w = window.open('', '_blank', 'width=880,height=720');
      if (w) {
        w.document.write(json.data.invoice_html);
        w.document.close();
      }

      clearCart(true);
      if (currentPatMode === 'walkin') {
        document.getElementById('wiName').value = '';
        document.getElementById('wiPhone').value = '';
        document.getElementById('wiAge').value = '';
        document.getElementById('wiSex').value = '';
        document.getElementById('wiDoctor').value = '';
      }
    } catch (e) {
      PH.close();
      PH.error('Network Error');
    }
  }
</script>