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
    <title>Tests Order - IPD</title>
    <link rel="stylesheet" href="/GM_HMS/assets/css/gm-theme.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: var(--bg-cream, #F3EFE6); margin: 0; color: #333; overflow-x: hidden; display: flex; width: 100%; }
        .main-layout { display: flex; width: 100%; }
        .content-wrapper { flex: 1; padding: 20px; min-height: 100vh; display: flex; flex-direction: column; overflow: hidden; }
        .top-navbar { padding: 15px 30px; display: flex; justify-content: space-between; align-items: center; background: rgba(255,255,255,0.7); backdrop-filter: blur(20px); border-radius: 15px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); margin-bottom: 20px; }
        .top-navbar h2 { margin: 0; font-size: 1.5rem; color: #1F6B4A; display: flex; align-items: center; gap: 10px; }
        
        .glass-card { background: white; border-radius: 15px; padding: 25px; box-shadow: 0 4px 20px rgba(0,0,0,0.05); }
        
        .search-container { position: relative; max-width: 600px; margin-bottom: 30px; }
        .search-box { width: 100%; padding: 14px 20px 14px 45px; border-radius: 30px; border: 1px solid #ddd; font-size: 15px; box-sizing: border-box; outline: none; transition: border 0.3s, box-shadow 0.3s; }
        .search-box:focus { border-color: #1F6B4A; box-shadow: 0 0 0 3px rgba(31,107,74,0.1); }
        .search-icon { position: absolute; left: 18px; top: 16px; color: #888; font-size: 16px; }
        
        .suggestions-dropdown { position: absolute; top: 100%; left: 0; right: 0; background: white; border-radius: 10px; border: 1px solid #eee; box-shadow: 0 10px 25px rgba(0,0,0,0.1); margin-top: 5px; z-index: 100; max-height: 350px; overflow-y: auto; display: none; }
        .suggestion-item { padding: 12px 20px; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #f5f5f5; cursor: pointer; transition: background 0.2s; }
        .suggestion-item:last-child { border-bottom: none; }
        .suggestion-item:hover { background: #f0f7f4; }
        .suggestion-details strong { display: block; font-size: 15px; color: #222; }
        .suggestion-details span { font-size: 12px; color: #666; }
        .suggestion-price { font-weight: 600; color: #1F6B4A; }

        .cart-table { width: 100%; border-collapse: separate; border-spacing: 0; margin-top: 20px; }
        .cart-table th { background: #f8f9fa; color: #555; padding: 12px 15px; text-align: left; font-weight: 600; font-size: 13px; text-transform: uppercase; border-bottom: 2px solid #ddd; }
        .cart-table td { padding: 15px; border-bottom: 1px solid #eee; vertical-align: middle; }
        .cart-table tr:hover td { background: #fafafa; }
        
        .qty-input { width: 60px; padding: 6px; border: 1px solid #ddd; border-radius: 5px; text-align: center; }
        .btn-remove { background: #fee2e2; color: #ef4444; border: none; padding: 8px 12px; border-radius: 6px; cursor: pointer; font-size: 13px; font-weight: 600; transition: background 0.2s; }
        .btn-remove:hover { background: #fca5a5; color: white; }
        
        .cart-footer { display: flex; justify-content: flex-end; margin-top: 20px; padding-top: 20px; border-top: 2px solid #eee; }
        .total-amount { font-size: 1.5rem; font-weight: 700; color: #1F6B4A; }
        .patient-card { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 20px; display: flex; gap: 20px; margin-bottom: 25px; display: none; }
        .patient-card div { flex: 1; }
        .patient-card span { display: block; font-size: 11px; color: #64748b; text-transform: uppercase; font-weight: 700; margin-bottom: 4px; letter-spacing: 0.5px; }
        .patient-card strong { font-size: 16px; color: #0f172a; }
        .btn-change-patient { background: #e2e8f0; color: #475569; border: none; padding: 8px 15px; border-radius: 8px; cursor: pointer; font-size: 13px; font-weight: 600; height: fit-content; align-self: center; }
        .btn-change-patient:hover { background: #cbd5e1; }
    </style>
</head>
<body>
<div class="main-layout">
    <?php include 'includes/nurse_sidebar.php'; ?>
    
    <div class="content-wrapper">
        <?php 
        $pageTitle = 'IPD Tests';
        include 'includes/nurse_navbar.php'; 
        ?>
        
        <div class="glass-card" style="margin-bottom: 20px;" id="patientSearchSection">
            <h3 style="margin-top: 0; color: #444; font-size: 1.1rem; margin-bottom: 15px;">Select Patient</h3>
            <div class="search-container" style="margin-bottom: 0;">
                <i class="fas fa-user-injured search-icon"></i>
                <input type="text" id="patientSearchInput" class="search-box" placeholder="Search admitted patient by Name, ID, or Phone..." autocomplete="off">
                <div id="patientSuggestions" class="suggestions-dropdown"></div>
            </div>
        </div>
        
        <div class="glass-card patient-card" id="patientInfoCard">
            <div><span>Patient Name</span><strong id="pName"></strong></div>
            <div><span>Patient ID</span><strong id="pId"></strong></div>
            <div><span>Admission ID</span><strong id="pAdmId"></strong></div>
            <div><span>Ward / Room</span><strong id="pWard"></strong></div>
            <button class="btn-change-patient" onclick="changePatient()">Change</button>
        </div>
        
        <div class="glass-card" id="testOrderSection" style="display: none;">
            <div class="search-container">
                <i class="fas fa-search search-icon"></i>
                <input type="text" id="searchInput" class="search-box" placeholder="Search Lab, Radiology or Other Tests..." autocomplete="off">
                <div id="suggestions" class="suggestions-dropdown"></div>
            </div>
            
            <h3 style="margin-top: 0; color: #444;">Selected Tests</h3>
            <table class="cart-table" id="cartTable">
                <thead>
                    <tr>
                        <th>Test Name</th>
                        <th>Category</th>
                        <th>Qty</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody id="cartBody">
                    <tr><td colspan="4" style="text-align:center; color:#888;">No tests selected yet. Search and click to add.</td></tr>
                </tbody>
            </table>
            
            <div class="cart-footer" style="justify-content: flex-end; align-items: center; display: flex; margin-top: 20px; padding-top: 20px; border-top: 2px solid #eee;">
                <button class="btn-save" id="btnSave" onclick="saveOrder()" style="background: #1F6B4A; color: white; border: none; padding: 10px 20px; border-radius: 8px; font-weight: 600; cursor: pointer; display: none;">
                    <i class="fas fa-save" style="margin-right: 5px;"></i> Save Order
                </button>
            </div>
        </div>
    </div>
    
    <!-- Centered Message Overlay -->
    <div id="centerOverlay" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 9999; justify-content: center; align-items: center;">
        <div id="centerMessageCard" style="background: white; padding: 30px; border-radius: 12px; text-align: center; box-shadow: 0 10px 25px rgba(0,0,0,0.2); min-width: 300px; transform: translateY(-20px); transition: all 0.3s ease;">
            <i id="centerIcon" class="fas fa-check-circle" style="font-size: 3.5rem; margin-bottom: 15px;"></i>
            <h3 id="centerTitle" style="margin: 0 0 10px 0; color: #1e293b; font-size: 1.5rem;">Success</h3>
            <p id="centerText" style="color: #64748b; margin: 0; font-size: 1.1rem;"></p>
        </div>
    </div>
</div>

    <script>
        let cart = [];
        let currentPatient = null;
        
        // --- Patient Search Logic ---
        const pSearchInput = document.getElementById('patientSearchInput');
        const pSuggestionsBox = document.getElementById('patientSuggestions');
        let pTimeout = null;
        
        pSearchInput.addEventListener('input', function() {
            clearTimeout(pTimeout);
            const query = this.value.trim();
            
            if (query.length < 2) {
                pSuggestionsBox.style.display = 'none';
                return;
            }
            
            pTimeout = setTimeout(() => {
                fetch(`api/search_ipd_patient.php?q=${encodeURIComponent(query)}`)
                    .then(res => res.json())
                    .then(data => {
                        if (data.success && data.data.length > 0) {
                            renderPatientSuggestions(data.data);
                        } else {
                            pSuggestionsBox.innerHTML = '<div style="padding:15px; color:#888;">No admitted patient found.</div>';
                            pSuggestionsBox.style.display = 'block';
                        }
                    })
                    .catch(err => console.error(err));
            }, 300);
        });
        
        function renderPatientSuggestions(patients) {
            pSuggestionsBox.innerHTML = '';
            patients.forEach(p => {
                const div = document.createElement('div');
                div.className = 'suggestion-item';
                div.innerHTML = `
                    <div class="suggestion-details">
                        <strong>${p.first_name} ${p.last_name || ''}</strong>
                        <span>Age: ${p.age || 'N/A'} | Ward: ${p.ward || 'N/A'} (Room: ${p.room_no || 'N/A'})</span>
                    </div>
                    <div style="text-align:right;">
                        <span style="font-size:12px; color:#666;">ID: ${p.patient_id}</span><br>
                        <span style="font-size:12px; color:#1F6B4A; font-weight:600;">Adm: ${p.admission_id}</span>
                    </div>
                `;
                div.onclick = () => selectPatient(p);
                pSuggestionsBox.appendChild(div);
            });
            pSuggestionsBox.style.display = 'block';
        }
        
        function selectPatient(p) {
            currentPatient = p;
            pSuggestionsBox.style.display = 'none';
            pSearchInput.value = '';
            
            document.getElementById('pName').innerText = `${p.first_name} ${p.last_name || ''}`;
            document.getElementById('pId').innerText = p.patient_id;
            document.getElementById('pAdmId').innerText = p.admission_id;
            document.getElementById('pWard').innerText = `${p.ward || 'N/A'} (Rm: ${p.room_no || 'N/A'})`;
            
            document.getElementById('patientSearchSection').style.display = 'none';
            document.getElementById('patientInfoCard').style.display = 'flex';
            document.getElementById('testOrderSection').style.display = 'block';
        }
        
        function changePatient() {
            currentPatient = null;
            document.getElementById('patientSearchSection').style.display = 'block';
            document.getElementById('patientInfoCard').style.display = 'none';
            document.getElementById('testOrderSection').style.display = 'none';
            // Optional: clear cart when changing patient?
            cart = [];
            renderCart();
        }
        
        // --- Tests Search Logic ---
        const searchInput = document.getElementById('searchInput');
        const suggestionsBox = document.getElementById('suggestions');
        
        // Debounce timer
        let timeout = null;
        
        searchInput.addEventListener('input', function() {
            clearTimeout(timeout);
            const query = this.value.trim();
            
            if (query.length < 2) {
                suggestionsBox.style.display = 'none';
                return;
            }
            
            timeout = setTimeout(() => {
                fetch(`api/search_tests.php?type=all&q=${encodeURIComponent(query)}`)
                    .then(res => res.json())
                    .then(data => {
                        if (data.success && data.data.length > 0) {
                            renderSuggestions(data.data);
                        } else {
                            suggestionsBox.innerHTML = '<div style="padding:15px; color:#888;">No tests found.</div>';
                            suggestionsBox.style.display = 'block';
                        }
                    })
                    .catch(err => console.error(err));
            }, 300);
        });
        
        function renderSuggestions(items) {
            suggestionsBox.innerHTML = '';
            items.forEach(item => {
                let badgeColor = '#0284c7';
                let badgeBg = '#e0f2fe';
                
                if(item.category && item.category.toLowerCase().includes('lab')) {
                    badgeColor = '#0284c7'; badgeBg = '#e0f2fe';
                } else if(item.category === 'Other') {
                    badgeColor = '#9333ea'; badgeBg = '#f3e8ff';
                } else {
                    badgeColor = '#d97706'; badgeBg = '#fef3c7';
                }
                
                const div = document.createElement('div');
                div.className = 'suggestion-item';
                div.innerHTML = `
                    <div class="suggestion-details">
                        <strong>${item.name}</strong>
                        <span>ID: ${item.id} | <span style="background:${badgeBg}; color:${badgeColor}; padding:2px 4px; border-radius:3px;">${item.category}</span></span>
                    </div>
                `;
                div.onclick = () => addToCart(item, badgeBg, badgeColor);
                suggestionsBox.appendChild(div);
            });
            suggestionsBox.style.display = 'block';
        }
        
        // Close dropdowns when clicking outside
        document.addEventListener('click', function(e) {
            if (!searchInput.contains(e.target) && !suggestionsBox.contains(e.target)) {
                suggestionsBox.style.display = 'none';
            }
            if (!pSearchInput.contains(e.target) && !pSuggestionsBox.contains(e.target)) {
                pSuggestionsBox.style.display = 'none';
            }
        });
        
        function addToCart(item, badgeBg, badgeColor) {
            suggestionsBox.style.display = 'none';
            searchInput.value = '';
            
            // Check if already in cart
            const existing = cart.find(x => x.id === item.id);
            if (existing) {
                existing.qty += 1;
            } else {
                cart.push({
                    id: item.id,
                    name: item.name,
                    category: item.category,
                    price: parseFloat(item.price) || 0,
                    qty: 1,
                    badgeBg: badgeBg,
                    badgeColor: badgeColor
                });
            }
            renderCart();
        }
        
        function updateQty(id, qty) {
            const item = cart.find(x => x.id === id);
            if (item) {
                item.qty = parseInt(qty) || 1;
                if (item.qty < 1) item.qty = 1;
                renderCart();
            }
        }
        
        function removeItem(id) {
            cart = cart.filter(item => item.id !== id);
            renderCart();
        }
        
        function showCenterMessage(isSuccess, title, message) {
            const overlay = document.getElementById('centerOverlay');
            const icon = document.getElementById('centerIcon');
            
            if (isSuccess) {
                icon.className = 'fas fa-check-circle';
                icon.style.color = '#10b981';
            } else {
                icon.className = 'fas fa-times-circle';
                icon.style.color = '#ef4444';
            }
            
            document.getElementById('centerTitle').innerText = title;
            document.getElementById('centerText').innerText = message;
            
            overlay.style.display = 'flex';
            
            setTimeout(() => {
                overlay.style.display = 'none';
            }, 3000);
        }
        
        function renderCart() {
            const tbody = document.getElementById('cartBody');
            
            if (cart.length === 0) {
                tbody.innerHTML = '<tr><td colspan="4" style="text-align:center; color:#888;">No tests selected yet. Search and click to add.</td></tr>';
                document.getElementById('btnSave').style.display = 'none';
                return;
            }
            
            document.getElementById('btnSave').style.display = 'block';
            tbody.innerHTML = '';
            
            cart.forEach(item => {
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td><strong>${item.name}</strong><br><small style="color:#888;">ID: ${item.id}</small></td>
                    <td><span style="background:${item.badgeBg}; color:${item.badgeColor}; padding:4px 8px; border-radius:4px; font-size:12px;">${item.category}</span></td>
                    <td><input type="number" class="qty-input" value="${item.qty}" min="1" onchange="updateQty('${item.id}', this.value)"></td>
                    <td><button class="btn-remove" onclick="removeItem('${item.id}')"><i class="fas fa-trash"></i></button></td>
                `;
                tbody.appendChild(tr);
            });
        }
        
        function saveOrder() {
            if (!currentPatient || !currentPatient.admission_id) {
                showCenterMessage(false, 'Warning', 'Please select a patient first.');
                return;
            }
            
            if (cart.length === 0) {
                showCenterMessage(false, 'Warning', 'Please add at least one test to the order.');
                return;
            }
            
            const btn = document.getElementById('btnSave');
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
            
            fetch('api/save_tests.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    patient_id: currentPatient.patient_id,
                    admission_id: currentPatient.admission_id,
                    cart: cart
                })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    showCenterMessage(true, 'Order Saved', 'Tests order saved successfully!');
                    cart = [];
                    renderCart();
                } else {
                    showCenterMessage(false, 'Error', data.message || 'Error saving order.');
                }
            })
            .catch(err => {
                console.error(err);
                showCenterMessage(false, 'Error', 'An error occurred during save.');
            })
            .finally(() => {
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-save" style="margin-right: 5px;"></i> Save Order';
            });
        }
    </script>
</body>
</html>
