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
        body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: var(--bg-cream, #F3EFE6); margin: 0; color: #333; overflow-x: hidden; display: flex; }
        .main-layout { display: flex; width: 100%; }
        .content-wrapper { flex: 1; display: flex; flex-direction: column; overflow: hidden; min-height: 100vh; padding: 20px; }
        
        @media (min-width: 1024px) {
            .content-wrapper { margin-left: 185px; }
        }
        @media (max-width: 1023px) {
            .content-wrapper { margin-left: 0; padding: 10px; }
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
        
        <div class="glass-card">
            <div class="search-container">
                <i class="fas fa-search search-icon"></i>
                <input type="text" id="searchInput" class="search-box" placeholder="Search bill by Invoice No, Patient Name or Phone..." autocomplete="off">
                <div id="suggestions" class="suggestions-dropdown"></div>
            </div>
        </div>

        <div id="billSection" class="glass-card" style="display: none;">
            <div class="bill-info" id="billInfo">
                <!-- Populated dynamically -->
            </div>

            <h3 style="margin-top: 0; color: #444;">Return Items</h3>
            <table class="cart-table" id="cartTable">
                <thead>
                    <tr>
                        <th>Medicine</th>
                        <th>Batch</th>
                        <th>Purchased Qty</th>
                        <th>Rate (₹)</th>
                        <th>Return Qty</th>
                        <th>Return Amount (₹)</th>
                    </tr>
                </thead>
                <tbody id="cartBody">
                    <!-- Populated dynamically -->
                </tbody>
            </table>
            
            <div class="cart-footer">
                <div>
                    <span style="font-size: 1.1rem; color: #555; margin-right: 15px;">Total Refund Amount:</span>
                    <span class="total-amount" id="grandTotal">₹0.00</span>
                </div>
            </div>
        </div>
    </div>

    </div>
    </div>
    
    <script>
        let currentBill = null;
        
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
                fetch(`api/search_ph_bill.php?q=${encodeURIComponent(query)}`)
                    .then(res => res.json())
                    .then(data => {
                        if (data.success && data.data.length > 0) {
                            renderSuggestions(data.data);
                        } else {
                            suggestionsBox.innerHTML = '<div style="padding:15px; color:#888;">No bills found.</div>';
                            suggestionsBox.style.display = 'block';
                        }
                    })
                    .catch(err => console.error(err));
            }, 300);
        });
        
        function renderSuggestions(bills) {
            suggestionsBox.innerHTML = '';
            bills.forEach(bill => {
                const div = document.createElement('div');
                div.className = 'suggestion-item';
                div.innerHTML = `
                    <div class="suggestion-details">
                        <strong>Invoice: ${bill.invoice_no}</strong>
                        <span>Patient: ${bill.customer_name || 'N/A'} | Phone: ${bill.customer_phone || 'N/A'}</span>
                    </div>
                    <div style="text-align: right;">
                        <span style="font-size:12px; color:#666;">${bill.invoice_date}</span><br>
                        <strong style="color:#1F6B4A;">₹${parseFloat(bill.grand_total).toFixed(2)}</strong>
                    </div>
                `;
                div.onclick = () => loadBill(bill);
                suggestionsBox.appendChild(div);
            });
            suggestionsBox.style.display = 'block';
        }
        
        document.addEventListener('click', function(e) {
            if (!searchInput.contains(e.target) && !suggestionsBox.contains(e.target)) {
                suggestionsBox.style.display = 'none';
            }
        });
        
        function loadBill(bill) {
            suggestionsBox.style.display = 'none';
            searchInput.value = '';
            
            // Add return_qty to each item, initialized to 0
            if (bill.items) {
                bill.items = bill.items.map(item => ({
                    ...item,
                    return_qty: 0,
                    purchased_qty: parseInt(item.purchased_qty) || 0,
                    rate: parseFloat(item.rate) || 0
                }));
            }
            
            currentBill = bill;
            
            document.getElementById('billInfo').innerHTML = `
                <div><span>Invoice No</span><strong>${bill.invoice_no}</strong></div>
                <div><span>Date</span><strong>${bill.invoice_date}</strong></div>
                <div><span>Patient Name</span><strong>${bill.customer_name || 'N/A'}</strong></div>
                <div><span>Phone</span><strong>${bill.customer_phone || 'N/A'}</strong></div>
                <div><span>Bill Total</span><strong style="color:#1F6B4A;">₹${parseFloat(bill.grand_total).toFixed(2)}</strong></div>
            `;
            
            document.getElementById('billSection').style.display = 'block';
            renderCart();
        }
        
        function updateReturnQty(productId, batch, qty) {
            if (!currentBill) return;
            const item = currentBill.items.find(x => x.product_id === productId && x.batch_no === batch);
            if (item) {
                let newQty = parseInt(qty) || 0;
                if (newQty < 0) newQty = 0;
                if (newQty > item.purchased_qty) {
                    alert('Cannot return more than purchased quantity (' + item.purchased_qty + ')');
                    newQty = item.purchased_qty;
                }
                item.return_qty = newQty;
                renderCart();
            }
        }
        
        function renderCart() {
            const tbody = document.getElementById('cartBody');
            tbody.innerHTML = '';
            let grandTotal = 0;
            
            currentBill.items.forEach(item => {
                const refundAmount = item.rate * item.return_qty;
                grandTotal += refundAmount;
                
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td><strong>${item.product_name}</strong><br><small style="color:#888;">ID: ${item.product_id}</small></td>
                    <td><span style="color:#555;">${item.batch_no || 'N/A'}</span></td>
                    <td style="font-weight:600;">${item.purchased_qty}</td>
                    <td>₹${item.rate.toFixed(2)}</td>
                    <td>
                        <input type="number" class="qty-input" value="${item.return_qty}" min="0" max="${item.purchased_qty}" 
                            onchange="updateReturnQty('${item.product_id}', '${item.batch_no}', this.value)">
                    </td>
                    <td style="font-weight:600; color:#ef4444;">₹${refundAmount.toFixed(2)}</td>
                `;
                tbody.appendChild(tr);
            });
            
            document.getElementById('grandTotal').innerText = '₹' + grandTotal.toFixed(2);
        }
    </script>
</body>
</html>
