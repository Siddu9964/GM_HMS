 <?php
session_start();
if (!isset($_SESSION['vendor_id'])) { header("Location: login.php"); exit(); }
$vendorName = htmlspecialchars($_SESSION['vendor_name'] ?? 'Vendor');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="stylesheet" href="/GM_HMS/assets/css/gm-theme.css">

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MediVend Nexus | Procurement Marketplace</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/vendor.css">
    <link rel="stylesheet" href="assets/css/sidebar_layout.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        :root {
            --erp-bg: #F3EFE6;
            --erp-green: #1F6B4A;
            --erp-text: #2c3e50;
            --erp-border: #d5d0c5;
        }
        
        body { font-family: 'Plus Jakarta Sans', sans-serif; margin: 0; padding: 0; background-color: var(--erp-bg) !important; }
        .nexus-main { background: var(--erp-bg) !important; border: none !important; box-shadow: none !important; }
        .nexus-content { flex: 1; overflow-y: auto; padding: 24px 32px; scroll-behavior: smooth; }
        
        .marketplace-wrapper {
            width: 100%;
        }
        .marketplace-container {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(380px, 1fr));
            gap: 24px;
            padding-bottom: 120px;
        }
        
        .market-card {
            background: #fff; 
            border-radius: 20px; 
            padding: 24px;
            display: flex;
            flex-direction: column;
            gap: 20px;
            border: 1px solid var(--erp-border);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 4px 10px rgba(0,0,0,0.02);
            position: relative;
        }
        .market-card:hover { 
            transform: translateY(-4px); 
            box-shadow: 0 16px 32px rgba(31,107,74,0.08); 
            border-color: rgba(31,107,74,0.3); 
        }
        .market-card.selected { 
            background: #fcfbfa;
            border: 2px solid var(--erp-green); 
            box-shadow: 0 8px 24px rgba(31,107,74,0.1);
        }

        /* Card Header */
        .mc-header {
            display: flex;
            align-items: flex-start;
            gap: 16px;
            border-bottom: 1px solid rgba(0,0,0,0.04);
            padding-bottom: 16px;
        }
        
        .mc-checkbox-wrap {
            margin-top: 4px;
        }

        .mc-header-info {
            flex: 1;
        }

        .id-badge { background: #e3f2ec; color: var(--erp-green); padding: 4px 10px; border-radius: 6px; font-size: 0.65rem; font-weight: 800; display: inline-block; margin-bottom: 8px; }
        .item-name { font-weight: 800; color: var(--erp-text); font-size: 1.05rem; line-height: 1.3; }
        .item-sku { font-size: 0.75rem; color: #7a8694; font-weight: 600; margin-top: 4px; }

        /* Card Body (Inputs Grid) */
        .mc-inputs {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
        }

        .mc-field {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .mc-field label {
            font-size: 0.65rem; 
            color: var(--erp-green); 
            font-weight: 800; 
            text-transform: uppercase; 
            letter-spacing: 0.5px;
        }

        .market-input {
            width: 100%; background: #fdfcfa; border: 1px solid var(--erp-border);
            border-radius: 10px; padding: 10px 14px; font-size: 0.9rem; font-weight: 700;
            color: var(--erp-text); transition: all 0.2s;
        }
        .market-input:focus { border-color: var(--erp-green); background: #fff; outline: none; box-shadow: 0 0 0 4px rgba(31,107,74,0.1); }

        /* Card Footer */
        .mc-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 16px;
            border-top: 1px dashed var(--erp-border);
        }

        .market-total-label { font-size: 0.7rem; color: #7a8694; font-weight: 800; text-transform: uppercase; }
        .market-total-val { font-size: 1.3rem; font-weight: 900; color: var(--erp-green); letter-spacing: -0.5px; }
    </style>
</head>
<body>

<div class="nexus-layout">
    <?php $current_page = 'quotation'; include 'includes/sidebar.php'; ?>

    <div class="nexus-main">
        <?php $page_title = 'Procurement Marketplace'; include 'includes/topbar.php'; ?>

        <main class="nexus-content">
            <div style="margin-bottom:32px;display:flex;align-items:center;justify-content:space-between;">
                <div>
                    <h1 style="font-size:1.75rem;font-weight:900;letter-spacing:-0.5px;color:var(--erp-green);">
                        <span class="nexus-live-dot" style="background:var(--erp-green);"></span>Live Marketplace
                    </h1>
                    <p style="color:#5a6670;font-size:0.85rem;margin-top:4px;font-weight:600;">
                        Submit your best rates for the following indents.
                    </p>
                </div>
                <div style="display:flex;align-items:center;gap:16px;">
                    <div style="background:rgba(255,255,255,0.8); padding:10px 20px; border-radius:14px; border:1px solid var(--erp-border); display:flex; align-items:center; gap:12px;">
                        <span style="font-size:0.8rem; font-weight:800; color:var(--erp-text);">Select All</span>
                        <input type="checkbox" id="selectAll" style="width:18px; height:18px; accent-color:var(--erp-green); cursor:pointer;">
                    </div>
                </div>
            </div>

            <div class="marketplace-wrapper">
                <div id="indent-container" class="marketplace-container">
                    <!-- Loaded via AJAX -->
                </div>
            </div>
        </main>

        <div class="nexus-floating-bar" id="nexusFloatingBar" style="display: none; background: var(--erp-green); border-color: #134630;">
            <div style="display:flex; gap:40px; align-items:center;">
                <div><div class="f-stat-label" style="color:#8bbfa6;">Selected</div><div class="f-stat-value" id="selected-count" style="color:#fff;">0</div></div>
                <div style="width:1px; height:36px; background:rgba(255,255,255,0.2);"></div>
                <div><div class="f-stat-label" style="color:#8bbfa6;">Total Quotation Value</div><div class="f-stat-value" id="total-value" style="color:#fff;">₹ 0.00</div></div>
            </div>
            <div style="display:flex;gap:20px;align-items:center;">
                <div style="font-size:0.75rem; color:#8bbfa6; font-weight:700;"><i class="fas fa-shield-alt" style="margin-right:6px;"></i>Secure Submission</div>
                <button class="btn-submit" id="submit-btn" onclick="submitQuotations()" style="background:#F3EFE6; color:var(--erp-green);">
                    <i class="fas fa-paper-plane"></i> Submit Quotations
                </button>
            </div>
        </div>
    </div>
</div>


<script>
    document.addEventListener('DOMContentLoaded', () => {
        loadIndents();
        const searchInput = document.getElementById('nexusSearchGlobal');
        if(searchInput) {
            searchInput.addEventListener('input', (e) => { filterIndents(e.target.value); });
        }
        document.getElementById('selectAll').addEventListener('change', (e) => {
            document.querySelectorAll('.indent-checkbox').forEach(cb => {
                cb.checked = e.target.checked;
                updateSelectionState(cb);
            });
        });
    });

    let allIndents = [];
    let selectedIndents = new Set();

    async function loadIndents() {
        try {
            const r = await fetch('api.php?action=getIndents');
            const res = await r.json();
            if (res.success) {
                allIndents = res.data;
                
                const pendingIndent = localStorage.getItem('pending_indent');
                if (pendingIndent) {
                    const searchInput = document.getElementById('nexusSearchGlobal');
                    if (searchInput) {
                        searchInput.value = pendingIndent;
                    }
                    const filtered = allIndents.filter(item => item.indent_no === pendingIndent || item.indent_no.toLowerCase().includes(pendingIndent.toLowerCase()));
                    renderIndents(filtered.length ? filtered : allIndents);
                    
                    // Auto-select them
                    setTimeout(() => {
                        document.querySelectorAll('.indent-checkbox').forEach(cb => {
                            cb.checked = true;
                            updateSelectionState(cb);
                        });
                        localStorage.removeItem('pending_indent');
                    }, 500);
                } else {
                    renderIndents(allIndents);
                }
                
                // Trigger initial calculations
                allIndents.forEach(item => calculateRow(item.id));
            }
        } catch (e) {
            console.error(e);
        }
    }

    function renderIndents(data) {
        const container = document.getElementById('indent-container');
        if (!data || data.length === 0) {
            container.innerHTML = `<div style="text-align:center;padding:100px;color:#9ca3af;font-weight:700;">No indents found</div>`;
            return;
        }
        container.innerHTML = data.map(item => `
            <div class="market-card" id="row-${item.id}">
                <div class="mc-header">
                    <div class="mc-checkbox-wrap">
                        <input type="checkbox" class="indent-checkbox" data-id="${item.id}" data-product="${item.product_id}" onchange="updateSelectionState(this)" style="width:20px;height:20px;accent-color:var(--erp-green); cursor:pointer;">
                    </div>
                    <div class="mc-header-info">
                        <span class="id-badge">${item.indent_no}</span>
                        <div class="item-name">${item.item_name}</div>
                        <div class="item-sku">SKU-${item.id + 1000}</div>
                    </div>
                </div>
                
                <div class="mc-inputs">
                    <div class="mc-field">
                        <label>Unit Rate (₹)</label>
                        <input type="number" step="0.01" class="market-input rate-input" value="${item.rate || ''}" placeholder="0.00" oninput="calculateRow(${item.id})">
                    </div>
                    <div class="mc-field">
                        <label>Quantity</label>
                        <input type="number" step="1" class="market-input qty-input" value="${item.qty || 0}" max="${item.qty || 0}" oninput="calculateRow(${item.id})" title="You can only reduce the requested quantity">
                    </div>
                    <div class="mc-field">
                        <label>Discount %</label>
                        <input type="number" step="0.01" class="market-input discount-input" value="0" placeholder="0" oninput="calculateRow(${item.id})">
                    </div>
                    <div class="mc-field">
                        <label>Scheme (Optional)</label>
                        <input type="text" class="market-input scheme-input" value="" placeholder="e.g. 10+1">
                    </div>
                    <div class="mc-field">
                        <label>Tax %</label>
                        <select class="market-input tax-input" onchange="calculateRow(${item.id})">
                            <option value="0">0%</option>
                            <option value="5" selected>5%</option>
                            <option value="12">12%</option>
                            <option value="16">16%</option>
                        </select>
                    </div>
                    <div class="mc-field">
                        <label>Expiry Date</label>
                        <input type="date" class="market-input expiry-input" value="${item.expiry_date || ''}">
                    </div>
                </div>
                
                <div class="mc-footer">
                    <div>
                        <div class="market-total-label">Est. Total Amount</div>
                        <div style="font-size:0.7rem;color:#94a3b8;font-weight:700;" id="tax-amt-${item.id}">+ ₹ 0.00 Tax</div>
                    </div>
                    <div style="text-align: right;">
                        <div class="market-total-val" id="total-${item.id}">₹ 0.00</div>
                    </div>
                </div>
            </div>
        `).join('');
    }

    function calculateRow(id) {
        const row = document.getElementById(`row-${id}`);
        const qtyInput = row.querySelector('.qty-input');
        let qty = parseFloat(qtyInput.value) || 0;
        const maxQty = parseFloat(qtyInput.getAttribute('max')) || 0;
        
        // Ensure qty doesn't exceed requested qty
        if (qty > maxQty) {
            qty = maxQty;
            qtyInput.value = maxQty;
        }

        const rate = parseFloat(row.querySelector('.rate-input').value) || 0;
        const discount = parseFloat(row.querySelector('.discount-input').value) || 0;
        const tax = parseFloat(row.querySelector('.tax-input').value) || 0;
        
        const subtotal = qty * rate;
        const discountAmt = (subtotal * discount) / 100;
        const taxableAmt = subtotal - discountAmt;
        const taxAmt = (taxableAmt * tax) / 100;
        const total = taxableAmt + taxAmt;
        
        document.getElementById(`tax-amt-${id}`).textContent = '+ ₹ ' + taxAmt.toFixed(2) + ' Tax';
        document.getElementById(`total-${id}`).textContent = '₹ ' + total.toLocaleString('en-IN', {minimumFractionDigits: 2});
        updateOverallTotal();
    }

    function updateSelectionState(cb) {
        const row = document.getElementById(`row-${cb.dataset.id}`);
        if (cb.checked) { row.classList.add('selected'); selectedIndents.add(cb.dataset.id); }
        else { row.classList.remove('selected'); selectedIndents.delete(cb.dataset.id); }
        updateOverallTotal();
    }

    function updateOverallTotal() {
        let totalCount = 0;
        let totalValue = 0;
        document.querySelectorAll('.indent-checkbox:checked').forEach(cb => {
            const id = cb.dataset.id;
            const totalText = document.getElementById(`total-${id}`).textContent;
            const total = parseFloat(totalText.replace('₹ ', '').replace(/,/g, '')) || 0;
            totalCount++;
            totalValue += total;
        });
        document.getElementById('selected-count').textContent = totalCount;
        document.getElementById('total-value').textContent = '₹ ' + totalValue.toLocaleString('en-IN', {minimumFractionDigits: 2});
        const bar = document.getElementById('nexusFloatingBar');
        if (totalCount > 0) {
            bar.style.display = 'flex';
            bar.classList.add('active');
        } else {
            bar.style.display = 'none';
            bar.classList.remove('active');
        }
    }

    async function submitQuotations() {
        const items = [];
        const checked = document.querySelectorAll('.indent-checkbox:checked');
        if (checked.length === 0) return;
        let valid = true;
        checked.forEach(cb => {
            const id = cb.dataset.id;
            const row = document.getElementById(`row-${id}`);
            const indent_no = row.querySelector('.id-badge').textContent;
            const product_id = cb.dataset.product;
            const item_name = row.querySelector('.item-name').textContent;
            const qtyInput = row.querySelector('.qty-input');
            let qty = parseFloat(qtyInput.value) || 0;
            const maxQty = parseFloat(qtyInput.getAttribute('max')) || 0;
            if (qty > maxQty) qty = maxQty;

            const rate = parseFloat(row.querySelector('.rate-input').value) || 0;
            const scheme = row.querySelector('.scheme-input').value || '';
            const discount_percent = parseFloat(row.querySelector('.discount-input').value) || 0;
            const tax_percent = parseFloat(row.querySelector('.tax-input').value) || 0;
            
            const subtotal = qty * rate;
            const discount_amount = (subtotal * discount_percent) / 100;
            const taxableAmt = subtotal - discount_amount;
            const tax_amount = (taxableAmt * tax_percent) / 100;
            const totalText = document.getElementById(`total-${id}`).textContent;
            const total_amount = totalText.replace('₹ ', '').replace(/,/g, '');
            const validity_date = row.querySelector('.expiry-input').value;

            if (!rate || rate <= 0 || !validity_date || !qty || qty <= 0) { 
                valid = false; row.style.borderColor = '#ef4444'; row.style.background = 'rgba(239,68,68,0.02)'; 
            } else { 
                row.style.borderColor = ''; row.style.background = ''; 
                items.push({ indent_no, product_id, item_name, qty, rate, discount_percent, scheme, tax_percent, tax_amount, total_amount, validity_date }); 
            }
        });
        if (!valid) { Swal.fire({ icon: 'warning', title: 'Action Required', text: 'Please provide rate and expiry for all selected indents.', customClass: { popup: 'swal-glass' } }); return; }
        const btn = document.getElementById('submit-btn');
        btn.disabled = true; btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
        try {
            const r = await fetch('api.php?action=submitQuotation', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ items })
            });
            const res = await r.json();
            if (res.success) { Swal.fire({ icon: 'success', title: 'Submitted!', text: res.message, customClass: { popup: 'swal-glass' } }).then(() => location.reload()); }
            else { Swal.fire({ icon: 'error', title: 'Failed', text: res.message, customClass: { popup: 'swal-glass' } }); }
        } catch (e) { Swal.fire({ icon: 'error', title: 'Error', text: 'Connection failed', customClass: { popup: 'swal-glass' } }); }
        finally { btn.disabled = false; btn.innerHTML = '<i class="fas fa-paper-plane"></i> Submit Quotations'; }
    }

    function filterIndents(query) {
        const q = query.toLowerCase();
        const filtered = allIndents.filter(item => item.indent_no.toLowerCase().includes(q) || item.item_name.toLowerCase().includes(q));
        renderIndents(filtered);
    }
</script>
</body>
</html>