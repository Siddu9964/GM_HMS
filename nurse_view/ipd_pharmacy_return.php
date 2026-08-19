<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>IPD Pharmacy Medication Return - GM HMS</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@600;700&display=swap" rel="stylesheet">
    <style>
        /* ── GM HMS Signature 2-Color Design System (#f3efe6 & #1f6b4a) ── */
        :root {
            --gm-bg: #f3efe6;
            --gm-bg-card: #ffffff;
            --gm-primary: #1f6b4a;
            --gm-primary-dark: #144d34;
            --gm-primary-light: rgba(31, 107, 74, 0.08);
            --gm-primary-mid: rgba(31, 107, 74, 0.16);
            --gm-border: rgba(31, 107, 74, 0.22);
            --gm-border-strong: #1f6b4a;
            --gm-text: #1f6b4a;
            --gm-text-body: #23342b;
            --gm-text-muted: #527967;
            --gm-sidebar-w: 185px;

            --shadow-subtle: 0 4px 16px rgba(31, 107, 74, 0.06);
            --shadow-elevated: 0 10px 30px rgba(31, 107, 74, 0.12);
        }

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, sans-serif; }
        body { background: var(--gm-bg); min-height: 100vh; display: flex; color: var(--gm-text-body); overflow-x: hidden; -webkit-font-smoothing: antialiased; }
        .main-layout { display: flex; width: 100%; min-height: 100vh; }
        .content-wrapper { flex: 1; display: flex; flex-direction: column; min-width: 0; background-color: var(--gm-bg); transition: margin-left 0.25s ease; }
        
        @media (min-width: 1024px) {
            .content-wrapper { margin-left: var(--gm-sidebar-w, 185px); }
        }

        .main-content { flex: 1; padding: 24px 30px; overflow-y: auto; }
        .container { max-width: 1180px; margin: 0 auto; animation: fadeIn 0.35s ease-out; }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(8px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* ── Header Toolbar ── */
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 22px;
            padding-bottom: 16px;
            border-bottom: 2px solid var(--gm-border);
            flex-wrap: wrap;
            gap: 14px;
        }

        .header-title-box { display: flex; align-items: center; gap: 14px; }
        .header-icon {
            width: 46px; height: 46px; border-radius: 12px;
            background: var(--gm-primary); color: #f3efe6;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.25rem; box-shadow: 0 4px 14px rgba(31, 107, 74, 0.25);
            flex-shrink: 0;
        }
        .header-title-box h1 { font-size: 1.45rem; font-weight: 800; color: var(--gm-primary); margin: 0; letter-spacing: -0.3px; }
        .header-title-box p { color: var(--gm-text-muted); font-size: 0.84rem; font-weight: 600; margin-top: 2px; }

        /* ── Cards & Containers ── */
        .glass-card {
            background: #ffffff;
            border-radius: 16px;
            padding: 22px 26px;
            border: 1.5px solid var(--gm-border);
            box-shadow: var(--shadow-subtle);
            margin-bottom: 22px;
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .glass-card:hover {
            border-color: var(--gm-primary);
            box-shadow: var(--shadow-elevated);
        }

        .card-heading {
            font-size: 1.05rem;
            font-weight: 800;
            color: var(--gm-primary);
            margin: 0 0 16px 0;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        /* ── Search Box & Dropdown ── */
        .search-container {
            position: relative;
            width: 100%;
            max-width: 640px;
        }

        .search-box {
            width: 100%;
            padding: 12px 18px 12px 46px;
            border-radius: 12px;
            border: 1.5px solid var(--gm-border);
            font-size: 0.9rem;
            font-weight: 600;
            color: var(--gm-primary);
            background: var(--gm-bg);
            outline: none;
            transition: all 0.2s ease;
        }

        .search-box:focus {
            background: #ffffff;
            border-color: var(--gm-primary);
            box-shadow: 0 0 0 3px var(--gm-primary-light);
        }

        .search-icon {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--gm-primary);
            font-size: 1rem;
        }

        .suggestions-dropdown {
            position: absolute;
            top: calc(100% + 6px);
            left: 0;
            right: 0;
            background: #ffffff;
            border-radius: 12px;
            border: 1.5px solid var(--gm-border);
            box-shadow: 0 12px 36px rgba(31, 107, 74, 0.15);
            z-index: 100;
            max-height: 340px;
            overflow-y: auto;
            display: none;
        }

        .suggestion-item {
            padding: 12px 16px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid var(--gm-bg);
            cursor: pointer;
            transition: background 0.15s;
        }

        .suggestion-item:hover {
            background: var(--gm-primary-light);
        }

        .suggestion-item:last-child {
            border-bottom: none;
        }

        .suggestion-details strong {
            display: block;
            font-size: 0.92rem;
            font-weight: 800;
            color: var(--gm-primary);
        }

        .suggestion-details span {
            font-size: 0.78rem;
            color: var(--gm-text-muted);
            font-weight: 600;
            margin-top: 2px;
            display: block;
        }

        /* ── Selected Patient Matrix Banner ── */
        .patient-matrix-card {
            background: #ffffff;
            border: 2px solid var(--gm-primary);
            border-radius: 14px;
            padding: 16px 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 16px;
            margin-bottom: 22px;
            box-shadow: 0 4px 16px rgba(31, 107, 74, 0.08);
        }

        .p-matrix-col {
            display: flex;
            flex-direction: column;
        }

        .p-matrix-lbl {
            font-size: 0.7rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: var(--gm-text-muted);
            margin-bottom: 2px;
        }

        .p-matrix-val {
            font-size: 0.95rem;
            font-weight: 800;
            color: var(--gm-primary);
        }

        /* ── Modern Clinical Cart Table ── */
        .table-responsive {
            width: 100%;
            overflow-x: auto;
            border: 1.5px solid var(--gm-border);
            border-radius: 12px;
            overflow: hidden;
            background: #ffffff;
        }

        .cart-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.86rem;
            min-width: 580px;
        }

        .cart-table thead th {
            background: var(--gm-bg);
            color: var(--gm-primary);
            padding: 12px 16px;
            text-align: left;
            font-weight: 800;
            font-size: 0.74rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 1.5px solid var(--gm-border);
        }

        .cart-table tbody td {
            padding: 12px 16px;
            border-bottom: 1px solid var(--gm-bg);
            color: var(--gm-text-body);
            vertical-align: middle;
        }

        .cart-table tbody tr:hover td {
            background: var(--gm-primary-light);
        }

        .cart-table tbody tr:last-child td {
            border-bottom: none;
        }

        .qty-input {
            width: 75px;
            padding: 6px 10px;
            border: 1.5px solid var(--gm-border);
            border-radius: 8px;
            text-align: center;
            font-weight: 800;
            font-size: 0.88rem;
            color: var(--gm-primary);
            background: var(--gm-bg);
            outline: none;
        }

        .qty-input:focus {
            background: #ffffff;
            border-color: var(--gm-primary);
        }

        .btn-submit {
            background: var(--gm-primary);
            color: #f3efe6;
            border: 1.5px solid var(--gm-primary);
            padding: 10px 24px;
            border-radius: 10px;
            font-size: 0.88rem;
            font-weight: 800;
            cursor: pointer;
            transition: all 0.2s ease;
            box-shadow: 0 4px 12px rgba(31, 107, 74, 0.25);
        }

        .btn-submit:hover {
            background: var(--gm-primary-dark);
            color: #ffffff;
            transform: translateY(-1px);
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 4px 10px;
            border-radius: 6px;
            font-size: 0.74rem;
            font-weight: 800;
            text-transform: uppercase;
        }

        .status-accepted { background: var(--gm-primary-light); color: var(--gm-primary); border: 1px solid var(--gm-border); }
        .status-pending  { background: rgba(217, 119, 6, 0.12); color: #b45309; border: 1px solid rgba(217, 119, 6, 0.25); }
        .status-rejected { background: rgba(220, 38, 38, 0.1); color: #dc2626; border: 1px solid rgba(220, 38, 38, 0.25); }
    </style>
</head>
<body>
<div class="main-layout">
    <!-- Sidebar Navigation -->
    <?php include 'includes/nurse_sidebar.php'; ?>
    
    <div class="content-wrapper">
        <!-- Top Navbar -->
        <?php 
        $pageTitle = 'Pharmacy Medication Return';
        include 'includes/nurse_navbar.php'; 
        ?>
        
        <div class="main-content">
            <div class="container">
                
                <!-- Page Header -->
                <div class="page-header">
                    <div class="header-title-box">
                        <div class="header-icon"><i class="fas fa-undo-alt"></i></div>
                        <div>
                            <h1>IPD Inpatient Pharmacy Medication Return</h1>
                            <p>Process unused or discontinued medication returns for pharmacy verification and billing adjustment.</p>
                        </div>
                    </div>
                </div>

                <!-- Patient Selection Card -->
                <div class="glass-card">
                    <h3 class="card-heading"><i class="fas fa-user-injured"></i> Step 1: Search Admitted Patient</h3>
                    <div class="search-container">
                        <i class="fas fa-search search-icon"></i>
                        <input type="text" id="searchInput" class="search-box" placeholder="Search admitted patient by Name, Patient ID, or Phone..." autocomplete="off">
                        <div id="suggestions" class="suggestions-dropdown"></div>
                    </div>
                </div>

                <!-- Return Section Container -->
                <div id="billSection" class="glass-card" style="display: none;">
                    
                    <!-- Patient Matrix Banner -->
                    <div class="patient-matrix-card" id="billInfo">
                        <!-- Populated dynamically -->
                    </div>

                    <!-- Billed Items Table -->
                    <h3 class="card-heading"><i class="fas fa-prescription-bottle-alt"></i> Billed Pharmacy Items & Return Allocation</h3>
                    <div class="table-responsive">
                        <table class="cart-table" id="cartTable">
                            <thead>
                                <tr>
                                    <th>Medicine Name & Batch</th>
                                    <th>Unit Price</th>
                                    <th>Available Qty</th>
                                    <th>Return Qty</th>
                                </tr>
                            </thead>
                            <tbody id="cartBody">
                                <!-- Populated dynamically -->
                            </tbody>
                        </table>
                    </div>
                    
                    <div style="display: flex; justify-content: flex-end; margin-top: 20px;">
                        <button class="btn-submit" onclick="submitReturnRequest()">
                            <i class="fas fa-paper-plane"></i> Submit Return Request to Pharmacy
                        </button>
                    </div>

                    <!-- Past Return Requests Table -->
                    <h3 class="card-heading" style="margin-top: 30px;"><i class="fas fa-history"></i> Past Return Requests & Verification Status</h3>
                    <div class="table-responsive">
                        <table class="cart-table" id="pastReturnsTable">
                            <thead>
                                <tr>
                                    <th>Request Date & Time</th>
                                    <th>Medicine Name & Batch</th>
                                    <th>Return Qty</th>
                                    <th>Refund Amount</th>
                                    <th>Pharmacy Status</th>
                                </tr>
                            </thead>
                            <tbody id="pastReturnsBody">
                                <!-- Populated dynamically -->
                            </tbody>
                        </table>
                    </div>

                </div>

            </div>
        </div>
    </div>
</div>
    
<script>
    let currentAdmission = null;
    let currentCharges = [];
    
    const searchInput = document.getElementById('searchInput');
    const suggestionsBox = document.getElementById('suggestions');
    let timeout = null;
    
    searchInput.addEventListener('input', function() {
        clearTimeout(timeout);
        const query = this.value.trim();
        
        if (query.length < 2) {
            suggestionsBox.style.display = 'none';
            return;
        }
        
        timeout = setTimeout(() => {
            fetch(`api/search_ipd_patient.php?q=${encodeURIComponent(query)}`)
                .then(res => res.json())
                .then(data => {
                    if (data.success && data.data.length > 0) {
                        renderSuggestions(data.data);
                    } else {
                        suggestionsBox.innerHTML = '<div style="padding:15px; color:var(--gm-text-muted); font-weight:600; text-align:center;">No admitted patients found.</div>';
                        suggestionsBox.style.display = 'block';
                    }
                })
                .catch(err => console.error(err));
        }, 300);
    });
    
    function renderSuggestions(patients) {
        suggestionsBox.innerHTML = '';
        patients.forEach(patient => {
            const div = document.createElement('div');
            div.className = 'suggestion-item';
            div.innerHTML = `
                <div class="suggestion-details">
                    <strong>${patient.first_name} ${patient.last_name || ''}</strong>
                    <span>Adm ID: ${patient.admission_id} &bull; Ward: ${patient.ward || 'General'} (Bed: ${patient.bed_id || 'N/A'})</span>
                </div>
                <div style="text-align: right;">
                    <span style="font-size:0.75rem; color:var(--gm-primary); font-weight:800;">UHID: ${patient.patient_id}</span>
                </div>
            `;
            div.onclick = () => loadPatient(patient);
            suggestionsBox.appendChild(div);
        });
        suggestionsBox.style.display = 'block';
    }
    
    document.addEventListener('click', function(e) {
        if (!searchInput.contains(e.target) && !suggestionsBox.contains(e.target)) {
            suggestionsBox.style.display = 'none';
        }
    });
    
    function loadPatient(patient) {
        suggestionsBox.style.display = 'none';
        searchInput.value = '';
        currentAdmission = patient;
        
        document.getElementById('billInfo').innerHTML = `
            <div class="p-matrix-col">
                <span class="p-matrix-lbl">Patient Full Name</span>
                <strong class="p-matrix-val">${patient.first_name} ${patient.last_name || ''}</strong>
            </div>
            <div class="p-matrix-col">
                <span class="p-matrix-lbl">IP Admission No</span>
                <strong class="p-matrix-val" style="font-family:'JetBrains Mono', monospace;">${patient.admission_id}</strong>
            </div>
            <div class="p-matrix-col">
                <span class="p-matrix-lbl">Ward / Bed Location</span>
                <strong class="p-matrix-val">${patient.ward || 'General'} / Bed ${patient.bed_id || 'N/A'}</strong>
            </div>
            <div class="p-matrix-col">
                <span class="p-matrix-lbl">Phone Number</span>
                <strong class="p-matrix-val">${patient.phone || 'N/A'}</strong>
            </div>
        `;
        
        document.getElementById('billSection').style.display = 'block';
        
        fetchCharges(patient.admission_id);
        fetchPastReturns(patient.admission_id);
    }

    function fetchCharges(admissionId) {
        fetch(`api/get_pharmacy_charges.php?admission_id=${admissionId}`)
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    currentAdmission.patient_id = data.patient_id;
                    currentAdmission.bill_id = data.bill_id;
                    currentCharges = data.data.map(item => ({
                        ...item,
                        return_qty: '',
                        available_qty: item.quantity - item.returned_qty
                    }));
                    renderCart();
                }
            });
    }

    function fetchPastReturns(admissionId) {
        fetch(`api/get_pharmacy_return_requests.php?admission_id=${admissionId}`)
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    const tbody = document.getElementById('pastReturnsBody');
                    tbody.innerHTML = '';
                    if (data.data.length === 0) {
                        tbody.innerHTML = '<tr><td colspan="5" style="text-align:center; padding:18px; color:var(--gm-text-muted);">No past return requests logged for this admission.</td></tr>';
                        return;
                    }
                    data.data.forEach(req => {
                        let statusBadge = '<span class="status-badge status-pending"><i class="fas fa-clock"></i> PENDING</span>';
                        if (req.status === 'ACCEPTED') statusBadge = '<span class="status-badge status-accepted"><i class="fas fa-check-circle"></i> ACCEPTED</span>';
                        if (req.status === 'REJECTED') statusBadge = '<span class="status-badge status-rejected"><i class="fas fa-times-circle"></i> REJECTED</span>';
                        
                        tbody.innerHTML += `
                            <tr>
                                <td><strong>${req.requested_at}</strong></td>
                                <td><strong style="color:var(--gm-primary);">${req.medicine_name}</strong><br><small style="color:var(--gm-text-muted);">Batch: ${req.batch_no || 'N/A'}</small></td>
                                <td><strong>${req.return_qty}</strong></td>
                                <td><strong style="color:var(--gm-primary);">₹${parseFloat(req.return_amount).toFixed(2)}</strong></td>
                                <td>${statusBadge}</td>
                            </tr>
                        `;
                    });
                }
            });
    }
    
    function updateReturnQty(itemId, qty) {
        const item = currentCharges.find(x => x.item_id == itemId);
        if (item) {
            if (qty === '') {
                item.return_qty = '';
            } else {
                let newQty = parseFloat(qty) || 0;
                if (newQty < 0) newQty = 0;
                if (newQty > item.available_qty) {
                    alert('Cannot return more than available billed quantity (' + item.available_qty + ')');
                    newQty = item.available_qty;
                }
                item.return_qty = newQty;
            }
            renderCart();
        }
    }
    
    function renderCart() {
        const tbody = document.getElementById('cartBody');
        tbody.innerHTML = '';
        
        if (currentCharges.length === 0) {
            tbody.innerHTML = '<tr><td colspan="4" style="text-align:center; padding:24px; color:var(--gm-text-muted);">No pharmacy items billed for this patient.</td></tr>';
            return;
        }

        currentCharges.forEach(item => {
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td><strong style="color:var(--gm-primary);">${item.product_name}</strong><br><small style="color:var(--gm-text-muted);">Batch: ${item.batch_no || 'N/A'}</small></td>
                <td><strong style="color:var(--gm-primary);">₹${item.unit_price.toFixed(2)}</strong></td>
                <td><strong style="color:var(--gm-primary);">${item.available_qty}</strong> <small style="color:var(--gm-text-muted);">(Total Billed: ${item.quantity})</small></td>
                <td>
                    <input type="number" class="qty-input" value="${item.return_qty}" placeholder="0" min="0" max="${item.available_qty}" 
                        onchange="updateReturnQty('${item.item_id}', this.value)">
                </td>
            `;
            tbody.appendChild(tr);
        });
    }

    function submitReturnRequest() {
        const returns = currentCharges.filter(x => x.return_qty > 0).map(x => ({
            item_id: x.item_id,
            return_qty: x.return_qty
        }));

        if (returns.length === 0) {
            alert('Please enter a return quantity for at least one billed item.');
            return;
        }

        fetch('api/submit_pharmacy_return.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({
                patient_id: currentAdmission.patient_id,
                admission_id: currentAdmission.admission_id,
                bill_id: currentAdmission.bill_id,
                returns: returns
            })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                alert('Return request submitted successfully for pharmacy verification!');
                fetchCharges(currentAdmission.admission_id);
                fetchPastReturns(currentAdmission.admission_id);
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(err => {
            console.error(err);
            alert('An error occurred while submitting the return request.');
        });
    }
</script>
</body>
</html>
