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
    <title>Pharmacy Order - IPD</title>
    <link rel="stylesheet" href="/GM_HMS/assets/css/gm-theme.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/GM_HMS/assets/css/ipd_pharmacy_order.css?v=<?php echo time(); ?>">
</head>
<body>
<div class="main-layout">
    <?php include 'includes/nurse_sidebar.php'; ?>
    
    <div class="content-wrapper" style="flex: 1; display: block !important; overflow-x: hidden !important; overflow-y: auto !important; height: 100%;">
        <?php 
        $pageTitle = 'Pharmacy Order';
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
        
        <div class="glass-card" id="pharmacyOrderSection" style="display: none;">
            <div class="search-container">
                <i class="fas fa-search search-icon"></i>
                <input type="text" id="searchInput" class="search-box" placeholder="Search medicine by name, generic, batch or ID..." autocomplete="off">
                <div id="suggestions" class="suggestions-dropdown"></div>
            </div>
            
            <h3 style="margin-top: 0; color: #444;">Order Items</h3>
            <div style="overflow-x: hidden; border: 1px solid #eee; border-radius: 8px;">
                <table class="cart-table" id="cartTable" style="margin-top: 0;">
                    <thead style="position: sticky; top: 0; z-index: 10;">
                        <tr>
                            <th>Medicine</th>
                            <th>Batch / Stock</th>
                            <th>Qty</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody id="cartBody">
                        <tr><td colspan="4" style="text-align:center; color:#888;">No items added yet. Search and click to add.</td></tr>
                    </tbody>
                </table>
            </div>
            
            <div class="cart-footer" style="justify-content: flex-end; align-items: center; display: flex; margin-top: 20px; padding-top: 20px; border-top: 2px solid #eee;">
                <button class="btn-save" id="btnSave" onclick="saveOrder()" style="background: #1f6b4a !important; color: #ffffff !important; border: none; padding: 12px 24px; border-radius: 8px; font-weight: 700; text-shadow: none; cursor: pointer; display: none;">
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
            document.getElementById('pharmacyOrderSection').style.display = 'block';
        }
        
        function changePatient() {
            currentPatient = null;
            document.getElementById('patientSearchSection').style.display = 'block';
            document.getElementById('patientInfoCard').style.display = 'none';
            document.getElementById('pharmacyOrderSection').style.display = 'none';
            cart = [];
            renderCart();
        }
        
        // --- Pharmacy Search Logic ---
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
                fetch(`api/search_medicine.php?q=${encodeURIComponent(query)}`)
                    .then(res => res.json())
                    .then(data => {
                        if (data.success && data.data.length > 0) {
                            renderSuggestions(data.data);
                        } else {
                            suggestionsBox.innerHTML = '<div style="padding:15px; color:#888;">No medicine found.</div>';
                            suggestionsBox.style.display = 'block';
                        }
                    })
                    .catch(err => console.error(err));
            }, 300);
        });
        
        function renderSuggestions(items) {
            suggestionsBox.innerHTML = '';
            items.forEach(item => {
                const stock = parseInt(item.available_stock) || 0;
                const price = parseFloat(item.sales_price) || parseFloat(item.mrp) || 0;
                
                const div = document.createElement('div');
                div.className = 'suggestion-item';
                div.innerHTML = `
                    <div class="suggestion-details">
                        <strong>${item.product_name}</strong>
                        <span>Generic: ${item.generic_name || 'N/A'} | Batch: ${item.batch_number || 'N/A'}</span>
                    </div>
                    <div class="suggestion-price">
                        <span class="stock">Stock: ${stock} ${item.unit || ''}</span>
                    </div>
                `;
                if(stock > 0) {
                    div.onclick = () => addToCart(item, stock);
                } else {
                    div.style.opacity = '0.5';
                    div.style.cursor = 'not-allowed';
                    div.onclick = () => alert('Out of stock!');
                }
                suggestionsBox.appendChild(div);
            });
            suggestionsBox.style.display = 'block';
        }
        
        document.addEventListener('click', function(e) {
            if (!searchInput.contains(e.target) && !suggestionsBox.contains(e.target)) {
                suggestionsBox.style.display = 'none';
            }
            if (!pSearchInput.contains(e.target) && !pSuggestionsBox.contains(e.target)) {
                pSuggestionsBox.style.display = 'none';
            }
        });
        
        function addToCart(item, maxStock) {
            suggestionsBox.style.display = 'none';
            searchInput.value = '';
            
            const existing = cart.find(x => x.id === item.product_id && x.batch === item.batch_number);
            if (existing) {
                if (existing.qty < maxStock) {
                    existing.qty += 1;
                } else {
                    alert('Cannot exceed available stock!');
                }
            } else {
                cart.push({
                    id: item.product_id,
                    name: item.product_name,
                    batch: item.batch_number || 'N/A',
                    stock: maxStock,
                    qty: 1
                });
            }
            renderCart();
        }
        
        function updateQty(id, batch, qty) {
            const item = cart.find(x => x.id === id && x.batch === batch);
            if (item) {
                let newQty = parseInt(qty) || 1;
                if (newQty < 1) newQty = 1;
                if (newQty > item.stock) {
                    alert('Cannot exceed available stock of ' + item.stock);
                    newQty = item.stock;
                }
                item.qty = newQty;
                renderCart();
            }
        }
        
        function removeItem(id, batch) {
            cart = cart.filter(item => !(item.id === id && item.batch === batch));
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
                tbody.innerHTML = '<tr><td colspan="4" style="text-align:center; color:#888;">No items added yet. Search and click to add.</td></tr>';
                document.getElementById('btnSave').style.display = 'none';
                return;
            }
            
            document.getElementById('btnSave').style.display = 'block';
            tbody.innerHTML = '';
            
            cart.forEach(item => {
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td><strong>${item.name}</strong><br><small style="color:#888;">Generic: ${item.content || 'N/A'}</small></td>
                    <td>${item.batch}</td>
                    <td>
                        <input type="number" class="qty-input" value="${item.qty}" min="1" max="${item.maxStock}" onchange="updateQty('${item.id}', '${item.batch}', this.value)">
                        <span style="font-size: 11px; color:#888; display:block; margin-top:3px;">Max: ${item.maxStock}</span>
                    </td>
                    <td><button class="btn-remove" onclick="removeItem('${item.id}', '${item.batch}')"><i class="fas fa-trash"></i></button></td>
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
                showCenterMessage(false, 'Warning', 'Please add at least one medicine to the order.');
                return;
            }
            
            const btn = document.getElementById('btnSave');
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
            
            fetch('api/save_pharmacy_order.php', {
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
                    showCenterMessage(true, 'Order Saved', 'Pharmacy order saved successfully!');
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
