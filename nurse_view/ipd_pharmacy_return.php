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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pharmacy Return - IPD</title>
    <link rel="stylesheet" href="/GM_HMS/assets/css/gm-theme.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>

        @media (max-width: 1023px) {
            .patient-card { flex-direction: column; gap: 10px; }
        }

        .top-navbar h2 { margin: 0; font-size: 1.5rem; color: #1F6B4A; display: flex; align-items: center; gap: 10px; }
        
        .glass-card { background: white; border-radius: 15px; padding: 25px; box-shadow: 0 4px 20px rgba(0,0,0,0.05); margin-bottom: 20px; }
        
        .search-container { position: relative; max-width: 600px; margin-bottom: 10px; }
        .search-box { width: 100%; padding: 14px 20px 14px 45px; border-radius: 30px; border: 1px solid #ddd; font-size: 15px; box-sizing: border-box; outline: none; transition: border 0.3s, box-shadow 0.3s; }
        .search-box:focus { border-color: #1F6B4A; box-shadow: 0 0 0 3px rgba(31,107,74,0.1); }
        .search-icon { position: absolute; left: 18px; top: 16px; color: #888; font-size: 16px; }
        
        .suggestions-dropdown { position: absolute; top: 100%; left: 0; right: 0; background: white; border-radius: 10px; border: 1px solid #eee; box-shadow: 0 10px 25px rgba(0,0,0,0.1); margin-top: 5px; z-index: 100; max-height: 350px; overflow-y: auto; display: none; }
        .suggestion-item { padding: 12px 20px; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #f5f5f5; cursor: pointer; transition: background 0.2s; }
        .suggestion-item:last-child { border-bottom: none; }
        .suggestion-item:hover { background: #f0f7f4; }
        .suggestion-details strong { display: block; font-size: 15px; color: #222; }
        .suggestion-details span { font-size: 12px; color: #666; display: block; margin-top:2px; }
        
        .bill-info { display: flex; gap: 20px; padding: 15px; background: #f8fafc; border-radius: 10px; border: 1px solid #e2e8f0; margin-bottom: 20px; }
        .bill-info div { flex: 1; }
        .bill-info span { display: block; font-size: 12px; color: #64748b; text-transform: uppercase; font-weight: 600; margin-bottom: 4px; }
        .bill-info strong { font-size: 15px; color: #0f172a; }

        .cart-table { width: 100%; border-collapse: separate; border-spacing: 0; }
        .cart-table th { background: #f8f9fa; color: #555; padding: 12px 15px; text-align: left; font-weight: 600; font-size: 13px; text-transform: uppercase; border-bottom: 2px solid #ddd; }
        .cart-table td { padding: 15px; border-bottom: 1px solid #eee; vertical-align: middle; }
        .cart-table tr:hover td { background: #fafafa; }
        
        .qty-input { width: 70px; padding: 6px; border: 1px solid #ddd; border-radius: 5px; text-align: center; }
        
        .cart-footer { display: flex; justify-content: flex-end; margin-top: 20px; padding-top: 20px; border-top: 2px solid #eee; }
        .total-amount { font-size: 1.5rem; font-weight: 700; color: #ef4444; }
    </style>
</head>
<body>
<div class="main-layout">
    <?php include 'includes/nurse_sidebar.php'; ?>
    
    <div class="content-wrapper">
        <?php 
        $pageTitle = 'Pharmacy Return';
        include 'includes/nurse_navbar.php'; 
        ?>
        
        <div class="main-content">
            <div class="glass-card">
                <div class="search-container">
                <i class="fas fa-search search-icon"></i>
                <input type="text" id="searchInput" class="search-box" placeholder="Search admitted patient by ID, Name, or Phone..." autocomplete="off">
                <div id="suggestions" class="suggestions-dropdown"></div>
            </div>
        </div>

        <div id="billSection" class="glass-card" style="display: none;">
            <div class="bill-info" id="billInfo">
                <!-- Populated dynamically -->
            </div>

            <h3 style="margin-top: 0; color: #444;">Billed Pharmacy Items</h3>
            <table class="cart-table" id="cartTable">
                <thead>
                    <tr>
                        <th>Medicine</th>
                        <th>Batch</th>
                        <th>Purchased Qty</th>
                        <th>Return Qty</th>
                    </tr>
                </thead>
                <tbody id="cartBody">
                    <!-- Populated dynamically -->
                </tbody>
            </table>
            
            <div class="cart-footer">
                <button onclick="submitReturnRequest()" style="background:#1F6B4A; color:white; border:none; padding:10px 20px; border-radius:5px; cursor:pointer; font-weight:600;">Submit Return Request</button>
            </div>

            <h3 style="margin-top: 30px; color: #444;">Past Return Requests</h3>
            <table class="cart-table" id="pastReturnsTable">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Medicine</th>
                        <th>Qty</th>
                        <th>Amount</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody id="pastReturnsBody">
                    <!-- Populated dynamically -->
                </tbody>
            </table>
        </div>
        </div> <!-- End of main-content -->
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
                            suggestionsBox.innerHTML = '<div style="padding:15px; color:#888;">No patients found in your ward.</div>';
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
                        <span>Adm ID: ${patient.admission_id} | Phone: ${patient.phone || 'N/A'}</span>
                    </div>
                    <div style="text-align: right;">
                        <span style="font-size:12px; color:#666;">Bed: ${patient.bed_id}</span>
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
                <div><span>Patient Name</span><strong>${patient.first_name} ${patient.last_name || ''}</strong></div>
                <div><span>Admission ID</span><strong>${patient.admission_id}</strong></div>
                <div><span>Ward/Bed</span><strong>${patient.ward} / ${patient.bed_id}</strong></div>
                <div><span>Phone</span><strong>${patient.phone || 'N/A'}</strong></div>
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
                            tbody.innerHTML = '<tr><td colspan="5" style="text-align:center; padding:15px;">No past return requests</td></tr>';
                            return;
                        }
                        data.data.forEach(req => {
                            let statusColor = '#888';
                            if (req.status === 'ACCEPTED') statusColor = '#1F6B4A';
                            if (req.status === 'REJECTED') statusColor = '#ef4444';
                            if (req.status === 'PENDING') statusColor = '#f59e0b';
                            
                            tbody.innerHTML += `
                                <tr>
                                    <td>${req.requested_at}</td>
                                    <td><strong>${req.medicine_name}</strong><br><small>${req.batch_no || 'N/A'}</small></td>
                                    <td>${req.return_qty}</td>
                                    <td>₹${parseFloat(req.return_amount).toFixed(2)}</td>
                                    <td><span style="color:${statusColor}; font-weight:bold;">${req.status}</span></td>
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
                        alert('Cannot return more than available quantity (' + item.available_qty + ')');
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
                tbody.innerHTML = '<tr><td colspan="4" style="text-align:center; padding:15px;">No pharmacy items billed for this patient.</td></tr>';
                return;
            }

            currentCharges.forEach(item => {
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td><strong>${item.product_name}</strong><br><small style="color:#888;">Batch: ${item.batch_no || 'N/A'}</small></td>
                    <td><span style="color:#555;">₹${item.unit_price.toFixed(2)}</span></td>
                    <td style="font-weight:600;">${item.available_qty} <small style="color:#888;">(Total: ${item.quantity})</small></td>
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
                alert('Please enter a return quantity for at least one item.');
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
                    alert('Return request submitted for pharmacy verification!');
                    fetchCharges(currentAdmission.admission_id);
                    fetchPastReturns(currentAdmission.admission_id);
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(err => {
                console.error(err);
                alert('An error occurred while submitting the request.');
            });
        }
    </script>
</body>
</html>
