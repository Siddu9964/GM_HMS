<?php
require_once 'includes/db.php';
session_start();

if (!isset($_SESSION['vendor_id'])) {
    header("Location: login.php");
    exit();
}

$db          = getDB();
$vendor_id   = $_SESSION['vendor_id'];
$vendorName  = $_SESSION['vendor_name'] ?? 'Vendor';

// Get filters
$indent_no = $_GET['indent_no'] ?? '';
$is_global = empty($indent_no);

// Ensure batch_no column exists
try {
    $db->execute("ALTER TABLE ph_quotations ADD COLUMN batch_no VARCHAR(100) NULL");
} catch (Exception $e) {}

// Build Query — always restricted to approved quotations only
$query  = "SELECT q.*, 
            p.content, p.strength, p.form, p.therapeutic, 
            p.purchase_rate, p.pack_rate, p.individual_rate, p.mrp, 
            p.pack, p.unit, p.pack_size 
           FROM ph_quotations q 
           LEFT JOIN ph_product p ON q.product_id = p.product_id 
           WHERE q.supplier_id = ? AND q.status = 'approved'";
$params = [$vendor_id];

if (!$is_global) {
    $query   .= " AND q.indent_no = ?";
    $params[] = $indent_no;
}
$query .= " ORDER BY q.quotation_date DESC";

$quotations = $db->fetchAll($query, $params);

// Stats
$total_all = $is_global
    ? $db->fetchOne("SELECT COUNT(*) as cnt, SUM(total_amount) as total FROM ph_quotations WHERE supplier_id=? AND status='approved'", [$vendor_id])
    : $db->fetchOne("SELECT COUNT(*) as cnt, SUM(total_amount) as total FROM ph_quotations WHERE indent_no=? AND supplier_id=? AND status='approved'", [$indent_no, $vendor_id]);

$stat_count = $total_all['cnt']   ?? 0;
$stat_total = $total_all['total'] ?? 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="stylesheet" href="/GM_HMS/assets/css/gm-theme.css">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Approved Quotations | ERP View</title>
    <link rel="stylesheet" href="assets/css/vendor.css">
    <link rel="stylesheet" href="assets/css/sidebar_layout.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --erp-bg: #F3EFE6;
            --erp-green: #1F6B4A;
            --erp-green-light: #2c8e63;
            --erp-text: #2c3e50;
            --erp-border: #d5d0c5;
        }

        body { 
            font-family: 'Plus Jakarta Sans', sans-serif; 
            margin: 0; 
            padding: 0; 
            background-color: var(--erp-bg) !important;
        }

        .pv-container { 
            padding: 24px; 
            flex: 1; 
            display: flex;
            flex-direction: column;
            overflow: hidden; 
        }

        /* Override layouts for this page */
        .nexus-main { background: var(--erp-bg) !important; border: none !important; box-shadow: none !important; }

        /* Page Header */
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 12px;
            border-bottom: 2px solid var(--erp-green);
        }
        .page-header h2 {
            margin: 0;
            color: var(--erp-green);
            font-size: 1.4rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .header-stats {
            display: flex;
            gap: 24px;
            font-size: 0.9rem;
            font-weight: 600;
            color: var(--erp-green);
        }

        /* ERP Table Container */
        .erp-table-container {
            flex: 1;
            background: #ffffff;
            border: 1px solid var(--erp-green);
            overflow-y: auto;
            position: relative;
        }

        .erp-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.85rem;
            color: var(--erp-text);
        }

        .erp-table th {
            position: sticky;
            top: 0;
            background: var(--erp-green);
            color: #ffffff;
            font-weight: 600;
            padding: 10px 12px;
            text-align: left;
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 0.5px;
            z-index: 10;
            border-bottom: 2px solid #134630;
            white-space: nowrap;
        }

        .erp-table td {
            padding: 10px 12px;
            border-bottom: 1px solid var(--erp-border);
            vertical-align: middle;
        }

        .erp-table tr:nth-child(even) { background-color: #fdfcfa; }
        .erp-table tr:hover { background-color: #e8e3d5; }
        .erp-table tr.selected { background-color: #d1e3da; }

        /* Batch Input Form */
        .batch-form {
            display: flex;
            gap: 6px;
            align-items: center;
        }
        
        .batch-input {
            width: 110px;
            padding: 6px 8px;
            border: 1px solid var(--erp-border);
            border-radius: 4px;
            font-size: 0.8rem;
            font-family: inherit;
            outline: none;
            transition: 0.2s;
            background: #fff;
        }
        .batch-input:focus { border-color: var(--erp-green); box-shadow: 0 0 0 2px rgba(31,107,74,0.15); }
        
        .btn-update-batch {
            background: var(--erp-green);
            color: #fff;
            border: none;
            padding: 6px 12px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.75rem;
            font-weight: 600;
            transition: 0.2s;
        }
        .btn-update-batch:hover { background: var(--erp-green-light); }
        
        .batch-display {
            font-weight: 700;
            color: var(--erp-green);
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .btn-edit-batch {
            background: none;
            border: none;
            color: var(--erp-green);
            cursor: pointer;
            font-size: 0.8rem;
        }
        .btn-edit-batch:hover { color: #111; }

        /* Floating Order Bar */
        .erp-action-bar {
            background: var(--erp-green);
            color: #ffffff;
            padding: 12px 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 16px;
            border-radius: 4px;
        }

        .action-stats {
            display: flex;
            gap: 32px;
            align-items: center;
            font-size: 0.95rem;
            font-weight: 600;
        }
        
        .action-form {
            display: flex;
            gap: 12px;
            align-items: center;
        }

        .erp-input {
            padding: 8px 12px;
            border: 1px solid #48936f;
            background: #134630;
            color: #fff;
            border-radius: 4px;
            font-size: 0.85rem;
            outline: none;
        }
        .erp-input::placeholder { color: #8bbfa6; }
        .erp-input:focus { border-color: #fff; }

        .erp-btn {
            background: #F3EFE6;
            color: var(--erp-green);
            border: none;
            padding: 9px 18px;
            font-size: 0.85rem;
            font-weight: 700;
            border-radius: 4px;
            cursor: pointer;
            transition: 0.2s;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .erp-btn:hover { background: #fff; }
        .erp-btn:disabled { opacity: 0.6; cursor: not-allowed; }

        .btn-outline {
            background: transparent;
            color: #F3EFE6;
            border: 1px solid #F3EFE6;
        }
        .btn-outline:hover { background: rgba(243, 239, 230, 0.1); }
        .btn-outline.done { background: #F3EFE6; color: var(--erp-green); }

        .status-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.7rem;
            font-weight: 700;
            background: #e3f2ec;
            color: var(--erp-green);
        }
        .btn-expand {
            background: none;
            border: none;
            color: var(--erp-green);
            cursor: pointer;
            font-size: 0.9rem;
            padding: 4px;
            transition: 0.2s;
            outline: none;
        }
        .btn-expand:hover { color: #111; }
        .btn-expand i { transition: transform 0.3s ease; }
        .btn-expand.open i { transform: rotate(180deg); }

        /* Inner Details Area - Professional ERP Styling */
        .details-row { display: none; background-color: #F3EFE6; }
        .details-row.show { display: table-row; }
        
        .details-container { 
            padding: 24px 32px; 
            display: grid; 
            grid-template-columns: 1fr 1fr; 
            gap: 40px; 
            background: #F3EFE6;
            border-bottom: 2px solid var(--erp-green); 
            box-shadow: inset 0 4px 10px rgba(31,107,74,0.05);
        }
        
        .details-section { 
            background: transparent; 
            border: none; 
            padding: 0; 
            box-shadow: none; 
        }
        
        .details-section h4 { 
            margin: 0 0 20px 0; 
            color: var(--erp-green); 
            font-size: 0.9rem; 
            font-weight: 800; 
            text-transform: uppercase; 
            letter-spacing: 0.5px; 
            border-bottom: 2px solid rgba(31,107,74, 0.15); 
            padding-bottom: 10px; 
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .details-grid { 
            display: grid; 
            grid-template-columns: repeat(2, 1fr); 
            gap: 16px; 
            align-items: start; 
        }
        
        .detail-item { 
            display: flex;
            flex-direction: column;
            gap: 6px;
        }
        
        .detail-item label { 
            font-size: 0.75rem; 
            font-weight: 700; 
            color: var(--erp-green); 
            margin: 0; 
            text-transform: uppercase;
        }
        
        .detail-item input, .detail-item select { 
            width: 100%; box-sizing: border-box; padding: 8px 12px; 
            border: 1px solid rgba(31,107,74, 0.2); border-radius: 8px; 
            font-size: 0.85rem; font-weight: 600; color: var(--erp-green); 
            transition: all 0.2s ease; 
            background: #ffffff; font-family: inherit;
        }
        
        .detail-item input:focus, .detail-item select:focus { 
            border-color: var(--erp-green); outline: none; 
            box-shadow: 0 0 0 3px rgba(31,107,74,0.15); 
        }
    </style>
</head>
<body>

<div class="nexus-layout">
    <?php $current_page = 'product'; include 'includes/sidebar.php'; ?>

    <div class="nexus-main">
        <?php $page_title = 'Approved Quotations'; include 'includes/topbar.php'; ?>

        <div class="pv-container">

            <div class="page-header">
                <h2>Approved Quotations</h2>
                <div class="header-stats">
                    <div>Total Quotes: <?= $stat_count ?></div>
                    <div>Value: ₹<?= number_format($stat_total, 2) ?></div>
                </div>
            </div>

            <div class="erp-table-container">
                <table class="erp-table" id="quotationsTable">
                    <thead>
                        <tr>
                            <th style="width: 40px; text-align: center;">
                                <input type="checkbox" id="selectAll" onchange="toggleSelectAll(this)">
                            </th>
                            <th style="width: 30px;"></th>
                            <th>Quotation No</th>
                            <th>Indent No</th>
                            <th>Product Name</th>
                            <th style="text-align: right;">Quantity</th>
                            <th style="text-align: right;">Rate (₹)</th>
                            <th style="width: 250px;">Batch Number</th>
                            <th style="text-align: right;">Total Amount</th>
                            <th style="text-align: center;">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($quotations)): ?>
                        <tr>
                            <td colspan="9" style="text-align: center; padding: 40px;">No approved quotations found.</td>
                        </tr>
                        <?php else: ?>
                        
                        <?php foreach ($quotations as $q): ?>
                        <tr id="row-<?= $q['id'] ?>">
                            <td style="text-align: center;">
                                <input type="checkbox" class="row-check"
                                    data-id="<?= $q['id'] ?>"
                                    data-qty="<?= $q['qty'] ?>"
                                    data-rate="<?= $q['rate'] ?>"
                                    data-tax="<?= $q['tax_amount'] ?>"
                                    data-total="<?= $q['total_amount'] ?>"
                                    data-batch="<?= htmlspecialchars($q['batch_no'] ?? '') ?>"
                                    onchange="updateSelection()">
                            </td>
                            <td style="text-align: center;">
                                <button type="button" class="btn-expand" onclick="toggleDetails(this)"><i class="fas fa-chevron-down"></i></button>
                            </td>
                            <td><?= htmlspecialchars($q['quotation_no']) ?></td>
                            <td><?= htmlspecialchars($q['indent_no']) ?></td>
                            <td><strong><?= htmlspecialchars($q['item_name'] ?? 'Item') ?></strong></td>
                            <td style="text-align: right;"><?= number_format($q['qty']) ?></td>
                            <td style="text-align: right;"><?= number_format($q['rate'], 2) ?></td>
                            <td>
                                <input type="text" class="batch-input" id="batch-input-<?= $q['id'] ?>" 
                                       value="<?= htmlspecialchars($q['batch_no'] ?? '') ?>" 
                                       placeholder="Enter Batch" 
                                       oninput="syncBatchNumbers(this)">
                            </td>
                            <td style="text-align: right; font-weight: 700; color: var(--erp-green);">₹<?= number_format($q['total_amount'], 2) ?></td>
                            <td style="text-align: center;">
                                <span class="status-badge"><?= strtoupper($q['status']) ?></span>
                            </td>
                        </tr>
                        <tr id="details-<?= $q['id'] ?>" class="details-row">
                            <td colspan="10" style="padding: 0;">
                                <div class="details-container">
                                    <div class="details-section">
                                        <h4><i class="fas fa-prescription-bottle-alt"></i> Product Specifications</h4>
                                        <div class="details-grid">
                                            <div class="detail-item"><label>Content</label><input type="text" class="input-content input-content-<?= $q['id'] ?>" value="<?= htmlspecialchars($q['content'] ?? '') ?>" placeholder="-" oninput="syncInputs(this, 'input-content')"></div>
                                            <div class="detail-item">
                                                <label>Strength</label>
                                                <select class="input-strength input-strength-<?= $q['id'] ?>" onchange="syncDropdowns(this, 'input-strength')">
                                                    <option value="">- Select Strength -</option>
                                                    <?php 
                                                        $common_strengths = ['1mg', '2mg', '2.5mg', '5mg', '10mg', '20mg', '25mg', '40mg', '50mg', '100mg', '125mg', '150mg', '200mg', '250mg', '300mg', '400mg', '500mg', '600mg', '625mg', '800mg', '1g', '1.5g', '2g', '0.5%', '1%', '2%', '5%', '5ml', '10ml', '15ml', '30ml', '50ml', '100ml'];
                                                        $current_st = $q['strength'] ?? '';
                                                        if ($current_st && !in_array($current_st, $common_strengths)) {
                                                            echo '<option value="'.htmlspecialchars($current_st).'" selected>'.htmlspecialchars($current_st).'</option>';
                                                        }
                                                        foreach ($common_strengths as $st) {
                                                            $sel = ($current_st === $st) ? 'selected' : '';
                                                            echo "<option value=\"$st\" $sel>$st</option>";
                                                        }
                                                    ?>
                                                </select>
                                            </div>
                                            <div class="detail-item">
                                                <label>Form</label>
                                                <select class="input-form input-form-<?= $q['id'] ?>" onchange="syncDropdowns(this, 'input-form')">
                                                    <option value="">- Select Form -</option>
                                                    <?php 
                                                        $common_forms = ['Tablet', 'Capsule', 'Syrup', 'Suspension', 'Injection', 'Infusion', 'Cream', 'Ointment', 'Gel', 'Drops', 'Inhaler', 'Suppository', 'Powder', 'Sachet', 'Lotion', 'Patch', 'Spray', 'Solution', 'Liquid', 'Granules'];
                                                        $current_fm = $q['form'] ?? '';
                                                        if ($current_fm && !in_array($current_fm, $common_forms)) {
                                                            echo '<option value="'.htmlspecialchars($current_fm).'" selected>'.htmlspecialchars($current_fm).'</option>';
                                                        }
                                                        foreach ($common_forms as $fm) {
                                                            $sel = ($current_fm === $fm) ? 'selected' : '';
                                                            echo "<option value=\"$fm\" $sel>$fm</option>";
                                                        }
                                                    ?>
                                                </select>
                                            </div>
                                            <div class="detail-item"><label>Therapeutic</label><input type="text" class="input-therapeutic input-therapeutic-<?= $q['id'] ?>" value="<?= htmlspecialchars($q['therapeutic'] ?? '') ?>" placeholder="e.g. Antibiotic" oninput="syncInputs(this, 'input-therapeutic')"></div>
                                            <div class="detail-item"><label>Pack</label><input type="text" class="input-pack input-pack-<?= $q['id'] ?>" value="<?= htmlspecialchars($q['pack'] ?? '') ?>" placeholder="e.g. 1x10" oninput="syncInputs(this, 'input-pack')"></div>
                                            <div class="detail-item">
                                                <label>Unit</label>
                                                <select class="input-unit input-unit-<?= $q['id'] ?>" onchange="syncDropdowns(this, 'input-unit')">
                                                    <option value="">- Select Unit -</option>
                                                    <option value="Tablet" <?= (($q['unit']??'')=='Tablet')?'selected':'' ?>>Tablet</option>
                                                    <option value="Capsule" <?= (($q['unit']??'')=='Capsule')?'selected':'' ?>>Capsule</option>
                                                    <option value="Bottle" <?= (($q['unit']??'')=='Bottle')?'selected':'' ?>>Bottle</option>
                                                    <option value="Tube" <?= (($q['unit']??'')=='Tube')?'selected':'' ?>>Tube</option>
                                                    <option value="Injection" <?= (($q['unit']??'')=='Injection')?'selected':'' ?>>Injection</option>
                                                    <option value="Vial" <?= (($q['unit']??'')=='Vial')?'selected':'' ?>>Vial</option>
                                                    <option value="Ampoule" <?= (($q['unit']??'')=='Ampoule')?'selected':'' ?>>Ampoule</option>
                                                    <option value="Drop" <?= (($q['unit']??'')=='Drop')?'selected':'' ?>>Drop</option>
                                                    <option value="Sachet" <?= (($q['unit']??'')=='Sachet')?'selected':'' ?>>Sachet</option>
                                                    <option value="Syringe" <?= (($q['unit']??'')=='Syringe')?'selected':'' ?>>Syringe</option>
                                                    <option value="Piece" <?= (($q['unit']??'')=='Piece')?'selected':'' ?>>Piece</option>
                                                    <option value="Box" <?= (($q['unit']??'')=='Box')?'selected':'' ?>>Box</option>
                                                    <option value="Strip" <?= (($q['unit']??'')=='Strip')?'selected':'' ?>>Strip</option>
                                                </select>
                                            </div>
                                            <div class="detail-item"><label>Pack Size</label><input type="text" class="input-pack_size input-pack_size-<?= $q['id'] ?>" value="<?= htmlspecialchars($q['pack_size'] ?? '') ?>" placeholder="e.g. 10" oninput="syncInputs(this, 'input-pack_size')"></div>
                                        </div>
                                    </div>
                                    <div class="details-section">
                                        <h4><i class="fas fa-tags"></i> Pricing & Taxation</h4>
                                        <div class="details-grid">
                                            <div class="detail-item"><label>Purchase Rate (₹)</label><input type="number" step="0.01" class="input-purchase_rate-<?= $q['id'] ?>" value="<?= htmlspecialchars($q['purchase_rate'] ?? '') ?>" placeholder="0.00"></div>
                                            <div class="detail-item"><label>Pack Rate (₹)</label><input type="number" step="0.01" class="input-pack_rate-<?= $q['id'] ?>" value="<?= htmlspecialchars($q['pack_rate'] ?? '') ?>" placeholder="0.00"></div>
                                            <div class="detail-item"><label>Ind. Rate (₹)</label><input type="number" step="0.01" class="input-individual_rate-<?= $q['id'] ?>" value="<?= htmlspecialchars($q['individual_rate'] ?? '') ?>" placeholder="0.00"></div>
                                            <div class="detail-item"><label>MRP (₹)</label><input type="number" step="0.01" class="input-mrp-<?= $q['id'] ?>" value="<?= htmlspecialchars($q['mrp'] ?? '') ?>" placeholder="0.00"></div>
                                            <div class="detail-item"><label>Tax %</label><input type="number" step="0.01" class="input-tax_percent-<?= $q['id'] ?>" value="<?= htmlspecialchars($q['tax_percent'] ?? '') ?>" placeholder="0.00"></div>
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Floating Order Bar -->
            <div class="erp-action-bar" id="actionBar" style="display: none;">
                <div class="action-stats">
                    <div>Selected: <span id="selectedCount">0</span> Items</div>
                    <div>Payable: <span id="estTotal">₹ 0.00</span></div>
                </div>
                <form id="orderForm" class="action-form" onsubmit="event.preventDefault(); submitOrder();">
                    <input type="text" name="invoice_no" id="invoice_no" class="erp-input" placeholder="Invoice No." required>
                    <input type="hidden" name="subtotal" id="h_sub">
                    <input type="hidden" name="tax_total" id="h_tax">
                    <input type="hidden" name="grand_total" id="h_grand">
                    <input type="hidden" name="po_no" id="h_po">
                    <input type="file" id="fileInput" name="attachment" style="display:none;" accept=".pdf" onchange="updateFileUI(this)">
                    <button type="button" class="erp-btn btn-outline" id="attachBtn" onclick="document.getElementById('fileInput').click()">
                        <i class="fas fa-paperclip"></i> <span id="attachText">Attach PDF</span>
                    </button>
                    <button type="submit" class="erp-btn" id="submitBtn">
                        <i class="fas fa-check"></i> Place Order
                    </button>
                </form>
            </div>

        </div><!-- /.pv-container -->
    </div><!-- /.nexus-main -->
</div><!-- /.nexus-layout -->

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
let state = { count: 0, subtotal: 0, tax: 0, total: 0 };

function toggleDetails(btn) {
    const tr = btn.closest('tr');
    if (!tr) return;
    
    const detailsRow = tr.nextElementSibling;
    if (detailsRow && detailsRow.classList.contains('details-row')) {
        const isShowing = detailsRow.classList.contains('show');
        
        // Collapse all first (optional, but keeps view clean)
        document.querySelectorAll('.details-row.show').forEach(r => {
            if (r !== detailsRow) {
                r.classList.remove('show');
                const prevBtn = r.previousElementSibling?.querySelector('.btn-expand');
                if (prevBtn) prevBtn.classList.remove('open');
            }
        });

        if (isShowing) {
            detailsRow.classList.remove('show');
            btn.classList.remove('open');
        } else {
            detailsRow.classList.add('show');
            btn.classList.add('open');
        }
    }
}

function toggleSelectAll(master) {
    document.querySelectorAll('.row-check').forEach(c => {
        c.checked = master.checked;
    });
    updateSelection();
}

function updateSelection() {
    const selected = document.querySelectorAll('.row-check:checked');
    document.querySelectorAll('tr').forEach(tr => tr.classList.remove('selected'));
    
    state = { count: selected.length, subtotal: 0, tax: 0, total: 0 };
    
    selected.forEach(c => {
        document.getElementById('row-' + c.dataset.id).classList.add('selected');
        state.subtotal += parseFloat(c.dataset.qty) * parseFloat(c.dataset.rate);
        state.tax      += parseFloat(c.dataset.tax);
        state.total    += parseFloat(c.dataset.total);
    });
    
    document.getElementById('selectedCount').textContent = state.count;
    document.getElementById('estTotal').textContent = '₹ ' + state.total.toLocaleString('en-IN', { minimumFractionDigits: 2 });
    
    const bar = document.getElementById('actionBar');
    bar.style.display = state.count > 0 ? 'flex' : 'none';
}

function syncBatchNumbers(sourceInput) {
    const val = sourceInput.value;
    const oldVal = sourceInput.dataset.oldVal || '';
    
    // Copy this batch number to all other inputs that are either empty or match the previous keystroke
    document.querySelectorAll('.batch-input').forEach(input => {
        if (input !== sourceInput) {
            if (input.value === '' || input.value === oldVal) {
                input.value = val;
                input.dataset.oldVal = val;
            }
        }
    });
    
    // Save current value for the next keystroke comparison
    sourceInput.dataset.oldVal = val;
}

function syncInputs(sourceInput, className) {
    const val = sourceInput.value;
    const oldVal = sourceInput.dataset.oldVal || '';
    
    document.querySelectorAll('.' + className).forEach(input => {
        if (input !== sourceInput) {
            if (input.value === '' || input.value === oldVal) {
                input.value = val;
                input.dataset.oldVal = val;
            }
        }
    });
    sourceInput.dataset.oldVal = val;
}

function syncDropdowns(sourceSelect, className) {
    const val = sourceSelect.value;
    document.querySelectorAll('.' + className).forEach(sel => {
        if (sel !== sourceSelect) {
            if (sel.value === '') {
                sel.value = val;
            }
        }
    });
}

function updateFileUI(input) {
    const btn = document.getElementById('attachBtn');
    const txt = document.getElementById('attachText');
    if (input.files && input.files[0]) {
        btn.classList.add('done');
        txt.textContent = 'PDF Attached';
    } else {
        btn.classList.remove('done');
        txt.textContent = 'Attach PDF';
    }
}

async function submitOrder() {
    const selected = document.querySelectorAll('.row-check:checked');
    if (selected.length === 0) return;

    const file = document.getElementById('fileInput').files[0];
    if (!file) {
        return Swal.fire({ icon: 'warning', title: 'PDF Required', text: 'Please attach a PDF document before submitting.', confirmButtonColor: '#1F6B4A' });
    }

    // Check batches
    let missingBatch = false;
    const items = [];
    selected.forEach(c => {
        const batchInput = document.getElementById('batch-input-' + c.dataset.id);
        const batchNo = batchInput ? batchInput.value.trim() : '';
        
        if (!batchNo) {
            missingBatch = true;
            document.getElementById('row-' + c.dataset.id).style.backgroundColor = '#fdf2f2';
        }

        
        items.push({
            id: c.dataset.id,
            qty: c.dataset.qty,
            rate: c.dataset.rate,
            total: c.dataset.total,
            batch_no: batchNo,
            content: document.querySelector('.input-content-'+c.dataset.id)?.value || '',
            strength: document.querySelector('.input-strength-'+c.dataset.id)?.value || '',
            form: document.querySelector('.input-form-'+c.dataset.id)?.value || '',
            therapeutic: document.querySelector('.input-therapeutic-'+c.dataset.id)?.value || '',
            pack: document.querySelector('.input-pack-'+c.dataset.id)?.value || '',
            unit: document.querySelector('.input-unit-'+c.dataset.id)?.value || '',
            pack_size: document.querySelector('.input-pack_size-'+c.dataset.id)?.value || '',
            purchase_rate: document.querySelector('.input-purchase_rate-'+c.dataset.id)?.value || 0,
            pack_rate: document.querySelector('.input-pack_rate-'+c.dataset.id)?.value || 0,
            individual_rate: document.querySelector('.input-individual_rate-'+c.dataset.id)?.value || 0,
            mrp: document.querySelector('.input-mrp-'+c.dataset.id)?.value || 0,
            tax_percent: document.querySelector('.input-tax_percent-'+c.dataset.id)?.value || 0
        });
    });

    if (missingBatch) {
        return Swal.fire({ icon: 'warning', title: 'Batch Required', text: 'Please enter and save Batch Numbers for all selected items.', confirmButtonColor: '#1F6B4A' });
    }

    const btn = document.getElementById('submitBtn');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';

    // Set h_ fields
    document.getElementById('h_po').value = 'PO-' + Date.now();
    document.getElementById('h_sub').value = state.subtotal.toFixed(2);
    document.getElementById('h_tax').value = state.tax.toFixed(2);
    document.getElementById('h_grand').value = state.total.toFixed(2);

    const fd = new FormData(document.getElementById('orderForm'));
    fd.append('items', JSON.stringify(items));

    try {
        const r = await fetch('api.php?action=submitOrder', { method: 'POST', body: fd });
        const res = await r.json();
        if (res.success) {
            Swal.fire({ icon: 'success', title: 'Order Placed!', text: res.message, confirmButtonColor: '#1F6B4A' }).then(() => location.reload());
        } else {
            Swal.fire({ icon: 'error', title: 'Submission Failed', text: res.message, confirmButtonColor: '#1F6B4A' });
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-check"></i> Place Order';
        }
    } catch (e) {
        Swal.fire({ icon: 'error', title: 'Connection Error', text: 'Could not reach the server.', confirmButtonColor: '#1F6B4A' });
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-check"></i> Place Order';
    }
}
</script>
</body>
</html>
