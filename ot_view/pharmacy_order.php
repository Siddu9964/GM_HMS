<?php
/**
 * ot_view/pharmacy_order.php
 * OT Module - Pharmacy Order & Reconciliation Page
 */
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}
// ── OT Module Access Guard ────────────────────────────────────────────────────
$_allowedOtRoles = ['Scrub_Nurse', 'admin', 'Admin', 'Administrator'];
$_currentRole = trim($_SESSION['role'] ?? '');
if (!in_array($_currentRole, $_allowedOtRoles)) {
    header('Location: ../login.php?error=' . urlencode('Access Denied: OT Module is restricted to Scrub Nurses only.'));
    exit;
}
$pageTitle = 'Pharmacy';
require_once 'includes/ot_head.php';
?>
<div class="ot-wrap">
  <!-- Navbar -->
  <div id="ot-content">
  <?php require_once 'includes/ot_navbar.php'; ?>

  <div class="d-flex">
    <?php require_once 'includes/ot_sidebar.php'; ?>

    <!-- Page Body -->
    <div class="ot-page-body w-100">

      <!-- Tabs Navigation -->
      <ul class="nav nav-tabs mb-4" id="pharmacyTabs" role="tablist">
        <li class="nav-item" role="presentation">
          <button class="nav-link active fw-bold px-4" id="order-tab" data-bs-toggle="tab" data-bs-target="#order-section" type="button" role="tab" style="font-size: 1.1rem;">
              <i class="fas fa-cart-plus me-2"></i> Create Order
          </button>
        </li>
        <li class="nav-item" role="presentation">
          <button class="nav-link fw-bold px-4" id="return-tab" data-bs-toggle="tab" data-bs-target="#return-section" type="button" role="tab" style="font-size: 1.1rem;">
              <i class="fas fa-undo me-2"></i> Post-Op Return & Bill
          </button>
        </li>
        <li class="nav-item" role="presentation">
          <button class="nav-link fw-bold px-4" id="history-tab" data-bs-toggle="tab" data-bs-target="#history-section" type="button" role="tab" style="font-size: 1.1rem;" onclick="loadPharmacyHistory()">
              <i class="fas fa-history me-2"></i> Order History
          </button>
        </li>
      </ul>

      <div class="tab-content" id="pharmacyTabsContent">
        
        <!-- TAB 1: CREATE ORDER -->
        <div class="tab-pane fade show active" id="order-section" role="tabpanel">
            
            <!-- Default List View -->
            <div id="order-list-view">
                <div class="card shadow-sm border-0 mb-4" style="border-radius: 12px;">
                    <div class="card-body p-4">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h5 class="fw-bold mb-0"><i class="fas fa-list text-primary"></i> Active OT Orders</h5>
                            <button class="ot-btn ot-btn-primary" onclick="toggleOrderView('form')">
                                <i class="fas fa-plus"></i> Create New Order
                            </button>
                        </div>
                        <div class="table-responsive">
                            <table class="ot-table">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Patient Name</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="activeOrdersTableBody">
                                    <tr><td colspan="4" class="text-center py-4"><i class="fas fa-spinner fa-spin"></i> Loading...</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Form View (Hidden by default) -->
            <div id="order-form-view" style="display:none;">
                <div class="mb-3 text-end">
                    <button class="btn btn-sm text-white px-3 fw-bold shadow-sm" style="background-color: #1f6b4a; border: none; border-radius: 6px;" onclick="toggleOrderView('list')">
                        <i class="fas fa-arrow-left"></i> Back to Orders List
                    </button>
                </div>
                <div class="row">
                <!-- Left Side: Order Form -->
                <div class="col-md-8">
                    <!-- Patient Selection -->
                    <div class="card shadow-sm border-0 mb-3" style="border-radius: 12px; z-index: 10;">
                        <div class="card-body p-4">
                            <h5 class="fw-bold mb-3"><i class="fas fa-user-injured text-primary"></i> 1. Select Patient</h5>
                            <div class="d-flex gap-3 align-items-end w-100">
                                <div class="position-relative w-50">
                                    <label class="ot-form-label text-uppercase mb-2">Search Patient <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-light border-end-0"><i class="fas fa-search text-muted"></i></span>
                                        <input type="text" id="orderPatientSearchInput" class="form-control ot-form-control border-start-0 ps-0" placeholder="ID, Name, Phone..." required onkeyup="searchOrderPatient(this.value)" autocomplete="off">
                                    </div>
                                    <div id="orderPatientDropdown" class="dropdown-menu w-100 shadow-lg border-0" style="display:none; position:absolute; top:100%; left:0; max-height: 350px; overflow-y: auto; z-index: 1050; border-radius: 8px;"></div>
                                </div>
                                <div class="w-50">
                                    <label class="ot-form-label text-uppercase mb-2">Patient Name</label>
                                    <input type="text" id="orderSelectedPatientName" class="ot-form-control bg-light fw-bold" readonly placeholder="Auto-filled">
                                </div>
                                
                                <!-- Hidden fields -->
                                <input type="hidden" id="orderSelectedPatientId">
                                <input type="hidden" id="orderSelectedAdmissionId">
                            </div>
                        </div>
                    </div>

                    <!-- Medicine Selection -->
                    <div class="card shadow-sm border-0 mb-3" style="border-radius: 12px; z-index: 9;">
                        <div class="card-body p-4">
                            <h5 class="fw-bold mb-3"><i class="fas fa-pills text-success"></i> 2. Search Medicine</h5>
                            <div class="position-relative">
                                <input type="text" id="medicineSearchInput" class="form-control ot-form-control" placeholder="Type medicine name..." onkeyup="searchMedicine(this.value)">
                                <div id="medicineDropdown" class="dropdown-menu w-100 shadow-sm" style="display:none; position:absolute; top:100%; left:0; max-height: 250px; overflow-y: auto; z-index: 1050;"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Cart Table -->
                    <div class="card shadow-sm border-0" style="border-radius: 12px;">
                        <div class="card-body p-4">
                            <h5 class="fw-bold mb-3"><i class="fas fa-shopping-cart text-info"></i> 3. Order Cart</h5>
                            <div class="table-responsive">
                                <table class="ot-table">
                                    <thead>
                                        <tr>
                                            <th>Medicine Name</th>
                                            <th>Stock</th>
                                            <th>Rate (₹)</th>
                                            <th style="width: 120px;">Qty</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody id="cartTableBody">
                                        <tr><td colspan="5" class="text-center text-muted py-3">Cart is empty</td></tr>
                                    </tbody>
                                </table>
                            </div>
                            <div class="text-end mt-4">
                                <button class="ot-btn ot-btn-primary mt-2" onclick="submitPharmacyOrder()">
                                    <i class="fas fa-paper-plane"></i> Send Order to Pharmacy
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right Side: Instructions -->
                <div class="col-md-4">
                    <div class="card shadow-sm border-0 bg-light" style="border-radius: 12px;">
                        <div class="card-body p-4">
                            <h5 class="fw-bold mb-3"><i class="fas fa-info-circle text-primary"></i> Instructions</h5>
                            <ul class="text-muted small ps-3" style="line-height: 1.8;">
                                <li>First, select the patient for whom the medicines are required.</li>
                                <li>Search for medicines in the pharmacy stock.</li>
                                <li>The system will show the available stock. You cannot order more than the available quantity.</li>
                                <li>Once submitted, the order is sent directly to the Pharmacy's IP Orders dashboard.</li>
                                <li>The pharmacy will dispense the medicines and the stock will be deducted at that time.</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
            </div> <!-- End order-form-view -->
        </div>
        
        <!-- TAB 2: POST-OP RETURN -->
        <div class="tab-pane fade" id="return-section" role="tabpanel">
            
            <!-- Default List View -->
            <div id="recon-list-view">
                <div class="card shadow-sm border-0 mb-4" style="border-radius: 12px;">
                    <div class="card-body p-4">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h5 class="fw-bold m-0"><i class="fas fa-procedures text-primary"></i> Patients with Pending Returns/Bills</h5>
                            <button class="ot-btn ot-btn-primary" onclick="toggleReconView('form')">
                                <i class="fas fa-search"></i> Search Patient to Return
                            </button>
                        </div>
                        <div class="table-responsive">
                            <table class="ot-table">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Patient Details</th>
                                        <th>Status</th>
                                        <th>Ordered / Processed</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="pendingReturnsTableBody">
                                    <tr><td colspan="5" class="text-center py-4"><i class="fas fa-spinner fa-spin"></i> Loading...</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Form View (Hidden by default) -->
            <div id="recon-form-view" style="display:none;">
                <div class="mb-3 text-end">
                    <button class="btn btn-sm text-white px-3 fw-bold shadow-sm" style="background-color: #1f6b4a; border: none; border-radius: 6px;" onclick="toggleReconView('list')">
                        <i class="fas fa-arrow-left"></i> Back to Pending List
                    </button>
                </div>
                <div class="row">
                <!-- Left Side: Order Form -->
                <div class="col-md-8">
                    <!-- Patient Selection -->
                    <div class="card shadow-sm border-0 mb-3" style="border-radius: 12px; z-index: 10;">
                        <div class="card-body p-4">
                            <h5 class="fw-bold mb-3"><i class="fas fa-procedures text-primary"></i> 1. Select Patient with OT Orders</h5>
                            <div class="d-flex gap-3 align-items-end w-100">
                                <div class="position-relative w-50">
                                    <label class="ot-form-label text-uppercase mb-2">Search Patient <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-light border-end-0"><i class="fas fa-search text-muted"></i></span>
                                        <input type="text" id="reconPatientSearchInput" class="form-control ot-form-control border-start-0 ps-0" placeholder="ID, Name, Phone..." required onkeyup="searchReconPatient(this.value)" autocomplete="off">
                                    </div>
                                    <div id="reconPatientDropdown" class="dropdown-menu w-100 shadow-lg border-0" style="display:none; position:absolute; top:100%; left:0; max-height: 350px; overflow-y: auto; z-index: 1050; border-radius: 8px;"></div>
                                </div>
                                <div class="w-50">
                                    <label class="ot-form-label text-uppercase mb-2">Patient Name</label>
                                    <input type="text" id="reconSelectedPatientName" class="ot-form-control bg-light fw-bold" readonly placeholder="Auto-filled">
                                </div>
                                
                                <!-- Hidden fields -->
                                <input type="hidden" id="reconSelectedPatientId">
                                <input type="hidden" id="reconSelectedAdmissionId">
                            </div>
                        </div>
                    </div>

                    <!-- Reconciliation Table -->
                    <div class="card shadow-sm border-0" style="border-radius: 12px;">
                        <div class="card-body p-4">
                            <h5 class="fw-bold mb-3"><i class="fas fa-clipboard-check text-ot-theme"></i> 2. Reconcile Medicines</h5>
                            <div class="table-responsive">
                                <table class="ot-table">
                                    <thead>
                                        <tr>
                                            <th>Medicine Name</th>
                                            <th>Pending Qty</th>
                                            <th>Returned Qty</th>
                                            <th class="text-center" style="width:100px;">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody id="reconTableBody">
                                        <tr><td colspan="4" class="text-center text-muted py-3">Select a patient to load their orders</td></tr>
                                    </tbody>
                                </table>
                            </div>
                            <div class="text-end mt-4">
                                <button class="ot-btn btn-ot-theme mt-2" id="submitReconBtn" onclick="submitReconciliation()" disabled>
                                    <i class="fas fa-check-circle"></i> Finalize & Bill Used Medicines
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right Side: Instructions -->
                <div class="col-md-4">
                    <div class="card shadow-sm border-0 bg-light" style="border-radius: 12px;">
                        <div class="card-body p-4">
                            <h5 class="fw-bold mb-3"><i class="fas fa-info-circle text-ot-theme"></i> Instructions</h5>
                            <ul class="text-muted small ps-3" style="line-height: 1.8;">
                                <li>Search for the patient whose operation has finished.</li>
                                <li>The system will display all medicines ordered for this patient today that are still <strong>Pending</strong>.</li>
                                <li>Input the exact quantity <strong>used</strong> and <strong>returned</strong>. You don't have to process the full quantity at once.</li>
                                <li>The <strong>returned quantity</strong> will automatically be sent back to pharmacy stock.</li>
                                <li>Only the used items will be added to the patient's final bill.</li>
                                <li>Once an item is completely processed, it will be marked as Completed and hidden from this list.</li>
                            </ul>
                        </div>
                    </div>
                </div>
                </div>
            </div> <!-- End recon-form-view -->
        </div>
        
        <!-- TAB 3: HISTORY -->
        <div class="tab-pane fade" id="history-section" role="tabpanel">
            <div class="card shadow-sm border-0" style="border-radius: 12px;">
                <div class="card-body p-4">
                    <h5 class="fw-bold mb-3"><i class="fas fa-list text-ot-theme"></i> All Pharmacy Orders</h5>
                    <div class="table-responsive">
                        <table class="ot-table">
                            <thead>
                                <tr>
                                    <th>Date & Time</th>
                                    <th>Patient Name</th>
                                    <th>Medicine Name</th>
                                    <th>Ordered Qty</th>
                                    <th>Used Qty</th>
                                    <th>Returned Qty</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody id="historyTableBody">
                                <tr><td colspan="7" class="text-center py-4"><i class="fas fa-spinner fa-spin"></i> Loading...</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

      </div>

    </div><!-- /ot-page-body -->
  </div><!-- /d-flex -->
  </div><!-- /ot-content -->
</div><!-- /ot-wrap -->

<!-- Order Details Modal -->
<div class="modal fade" id="orderDetailsModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content border-0 shadow-lg">
      <div class="modal-header border-0" style="background-color: #1f6b4a; color: white;">
        <h5 class="modal-title"><i class="fas fa-pills me-2"></i> Order Details - <span id="modalPatientName"></span></h5>
        <div>
            <button type="button" class="btn btn-sm btn-light text-success fw-bold me-3" onclick="addMoreMedicineToOrder()">
                <i class="fas fa-plus"></i> Add Medicine
            </button>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
      </div>
      <div class="modal-body p-4 bg-light">
        <div class="card border-0 shadow-sm rounded bg-white">
            <div class="card-body p-0">
                <table class="table table-sm table-borderless mb-0">
                    <thead style="border-bottom: 2px solid #e9ecef;">
                        <tr>
                            <th class="text-muted small fw-bold text-uppercase pb-2 pt-3 ps-3">Medicine Name</th>
                            <th class="text-center text-muted small fw-bold text-uppercase pb-2 pt-3">Ordered</th>
                            <th class="text-center text-muted small fw-bold text-uppercase pb-2 pt-3">Used</th>
                            <th class="text-center text-muted small fw-bold text-uppercase pb-2 pt-3">Returned</th>
                            <th class="text-center text-muted small fw-bold text-uppercase pb-2 pt-3 pe-3">Status</th>
                            <th class="text-center text-muted small fw-bold text-uppercase pb-2 pt-3 pe-3">Action</th>
                        </tr>
                    </thead>
                    <tbody id="modalOrderDetailsBody">
                        <!-- Dynamic items -->
                    </tbody>
                </table>
            </div>
        </div>
      </div>
    </div>
  </div>
</div>

<style>
    .patient-search-item:hover, .patient-search-item:focus {
        background-color: var(--bs-light) !important;
        cursor: pointer;
    }
    .nav-tabs .nav-link {
        color: #6c757d;
        border: none;
        border-bottom: 3px solid transparent;
        transition: all 0.3s;
    }
    .nav-tabs .nav-link:hover {
        color: #1f6b4a;
        border-color: transparent;
    }
    .nav-tabs .nav-link.active {
        color: #1f6b4a;
        border: none;
        border-bottom: 3px solid #1f6b4a;
        background-color: transparent;
    }
</style>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    let searchTimeout = null;
    let reconSearchTimeout = null;
    let medSearchTimeout = null;
    let cart = [];
    let currentOrders = [];

    // ==========================================
    // TAB 1: ORDER LOGIC
    // ==========================================

    function searchOrderPatient(query) {
        clearTimeout(searchTimeout);
        const dropdown = document.getElementById('orderPatientDropdown');
        
        if (query.trim().length < 2) {
            dropdown.style.display = 'none';
            return;
        }

        searchTimeout = setTimeout(async () => {
            try {
                const res = await fetch(`${API_BASE}ot/patient/search-list?q=${encodeURIComponent(query)}`);
                const json = await res.json();
                
                if (json.success && json.data.length > 0) {
                    let html = '';
                    json.data.forEach(p => {
                        let isAdmitted = p.ipd_status === 'Admitted';
                        let badge = isAdmitted ? `<span class="badge bg-success-subtle text-success px-2 py-1 rounded-pill" style="font-size: 0.75rem;"><i class="fas fa-bed me-1"></i>IPD</span>` : `<span class="badge bg-secondary-subtle text-secondary px-2 py-1 rounded-pill" style="font-size: 0.75rem;">OPD</span>`;
                        let safeName = p.patient_name ? p.patient_name : 'Unknown Patient';
                        let initials = p.patient_name ? p.patient_name.substring(0, 2).toUpperCase() : 'PT';
                        
                        html += `
                            <button class="dropdown-item py-3 px-3 border-bottom d-flex align-items-center gap-3 patient-search-item" type="button" onclick="selectOrderPatient('${p.patient_id}', '${safeName.replace(/'/g, "\\'")}', '${p.admission_id || ''}')" style="transition: all 0.2s; white-space: normal;">
                                <div class="avatar bg-primary-subtle text-primary fw-bold rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" style="width: 45px; height: 45px; font-size: 1.1rem;">
                                    ${initials}
                                </div>
                                <div class="flex-grow-1 min-w-0">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <h6 class="mb-0 fw-bold text-truncate" style="color: var(--ot-text-main);">${safeName}</h6>
                                        ${badge}
                                    </div>
                                    <div class="text-muted small d-flex flex-wrap gap-2">
                                        <span><i class="fas fa-id-card me-1 opacity-75"></i>${p.patient_id}</span>
                                        ${p.age ? `<span><i class="fas fa-calendar-alt me-1 opacity-75"></i>${p.age}</span>` : ''}
                                        ${p.sex ? `<span><i class="fas fa-venus-mars me-1 opacity-75"></i>${p.sex}</span>` : ''}
                                        ${p.phone ? `<span><i class="fas fa-phone-alt me-1 opacity-75"></i>${p.phone}</span>` : ''}
                                    </div>
                                </div>
                            </button>
                        `;
                    });
                    
                    html = `<div class="bg-light px-3 py-2 border-bottom text-muted small fw-bold">Search Results (${json.data.length})</div>` + html;
                    
                    dropdown.innerHTML = html;
                    dropdown.style.display = 'block';
                } else {
                    dropdown.innerHTML = '<div class="p-2 text-muted text-center">No patients found</div>';
                    dropdown.style.display = 'block';
                }
            } catch (err) {
                console.error('Patient search error:', err);
            }
        }, 400);
    }

    function selectOrderPatient(id, name, admissionId) {
        document.getElementById('orderSelectedPatientId').value = id;
        document.getElementById('orderSelectedPatientName').value = name;
        document.getElementById('orderSelectedAdmissionId').value = admissionId;
        document.getElementById('orderPatientSearchInput').value = id;
        document.getElementById('orderPatientDropdown').style.display = 'none';
    }

    function searchMedicine(query) {
        clearTimeout(medSearchTimeout);
        const dropdown = document.getElementById('medicineDropdown');
        
        if (query.trim().length < 2) {
            dropdown.style.display = 'none';
            return;
        }

        medSearchTimeout = setTimeout(async () => {
            try {
                const res = await fetch(`${API_BASE}ot/pharmacy/search?q=${encodeURIComponent(query)}`);
                const json = await res.json();
                
                if (json.success && json.data.length > 0) {
                    let html = '';
                    json.data.forEach(m => {
                        const stock = parseInt(m.stock || 0);
                        const price = parseFloat(m.price || 0).toFixed(2);
                        const outOfStock = stock <= 0;
                        const color = outOfStock ? 'text-danger' : 'text-success';
                        
                        html += `
                            <button class="dropdown-item py-2 border-bottom d-flex justify-content-between align-items-center" type="button" 
                                onclick="addToCart('${m.id}', '${m.name.replace(/'/g, "\\'")}', ${price}, ${stock})">
                                <span><strong>${m.name}</strong> <small class="text-muted ms-2">₹${price}</small></span>
                                <span class="badge bg-light ${color}">Stock: ${stock}</span>
                            </button>
                        `;
                    });
                    dropdown.innerHTML = html;
                    dropdown.style.display = 'block';
                } else {
                    dropdown.innerHTML = '<div class="p-2 text-muted text-center">No active medicines found</div>';
                    dropdown.style.display = 'block';
                }
            } catch (err) {
                console.error('Medicine search error:', err);
            }
        }, 400);
    }

    function addToCart(id, name, price, maxStock) {
        document.getElementById('medicineSearchInput').value = '';
        document.getElementById('medicineDropdown').style.display = 'none';

        const existing = cart.find(c => c.id === id);
        if (existing) {
            existing.qty++;
        } else {
            cart.push({
                id: id,
                name: name,
                mrp: price,
                maxStock: maxStock,
                qty: 1
            });
        }
        renderCart();
    }

    function updateQty(id, qty) {
        qty = parseInt(qty);
        if (isNaN(qty) || qty < 1) qty = 1;
        
        const item = cart.find(c => c.id === id);
        if (item) {
            item.qty = qty;
        }
        renderCart();
    }

    function removeFromCart(id) {
        cart = cart.filter(c => c.id !== id);
        renderCart();
    }

    function renderCart() {
        const tbody = document.getElementById('cartTableBody');
        let html = '';

        if (cart.length === 0) {
            tbody.innerHTML = '<tr><td colspan="5" class="text-center text-muted py-3">Cart is empty</td></tr>';
            return;
        }

        cart.forEach(item => {
            if (item.isExisting) {
                html += `
                    <tr style="background-color: #f8f9fa;">
                        <td class="fw-bold text-muted" style="color: #6c757d;">
                            ${item.name} 
                            <span class="badge bg-secondary ms-2 px-2 py-1" style="font-size:0.7rem;">Already Ordered</span>
                        </td>
                        <td><span class="badge bg-light text-dark">-</span></td>
                        <td class="text-muted">₹${item.mrp.toFixed(2)}</td>
                        <td>
                            <input type="number" class="form-control form-control-sm text-center bg-light text-muted" 
                                value="${item.qty}" disabled>
                        </td>
                        <td>
                            <span class="text-muted small"><i class="fas fa-lock"></i> Locked</span>
                        </td>
                    </tr>
                `;
            } else {
                html += `
                    <tr>
                        <td class="fw-bold text-dark">${item.name}</td>
                        <td><span class="badge bg-light text-dark">${item.maxStock}</span></td>
                        <td>₹${item.mrp.toFixed(2)}</td>
                        <td>
                            <input type="number" class="form-control form-control-sm text-center" 
                                value="${item.qty}" min="1" 
                                onchange="updateQty('${item.id}', this.value)">
                        </td>
                        <td>
                            <button class="btn btn-sm btn-outline-danger" onclick="removeFromCart('${item.id}')">
                                <i class="fas fa-times"></i>
                            </button>
                        </td>
                    </tr>
                `;
            }
        });

        tbody.innerHTML = html;
    }

    async function submitPharmacyOrder() {
        const patientId = document.getElementById('orderSelectedPatientId').value;
        const admissionId = document.getElementById('orderSelectedAdmissionId').value;

        if (!patientId) {
            Swal.fire('Required', 'Please select a patient first.', 'warning');
            return;
        }
        const newItems = cart.filter(c => !c.isExisting);
        if (newItems.length === 0) {
            Swal.fire('No New Items', 'Please add at least one NEW medicine to order.', 'warning');
            return;
        }

        const btn = event.currentTarget;
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sending...';

        try {
            const res = await fetch(`${API_BASE}ot/pharmacy-order`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    patient_id: patientId,
                    admission_id: admissionId,
                    items: newItems
                })
            });

            const json = await res.json();
            
            if (json.success) {
                Swal.fire({
                    icon: 'success',
                    title: 'Order Sent!',
                    text: 'The pharmacy order has been submitted successfully.',
                    timer: 2000,
                    showConfirmButton: false
                });
                cart = [];
                renderCart();
                document.getElementById('orderSelectedPatientId').value = '';
                document.getElementById('orderSelectedPatientName').value = '';
                document.getElementById('orderSelectedAdmissionId').value = '';
                document.getElementById('orderPatientSearchInput').value = '';
                
                // Refresh list and toggle view
                loadActiveOrders();
                toggleOrderView('list');
            } else {
                Swal.fire('Error', json.message || 'Failed to submit order.', 'error');
            }
        } catch (err) {
            console.error('Submit error:', err);
            Swal.fire('Error', 'A network error occurred.', 'error');
        } finally {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-paper-plane"></i> Send Order to Pharmacy';
        }
    }

    // ==========================================
    // TAB 2: RECONCILIATION LOGIC
    // ==========================================

    function searchReconPatient(query) {
        clearTimeout(reconSearchTimeout);
        const dropdown = document.getElementById('reconPatientDropdown');
        
        if (query.trim().length < 2) {
            dropdown.style.display = 'none';
            return;
        }

        reconSearchTimeout = setTimeout(async () => {
            try {
                const res = await fetch(`${API_BASE}ot/patient/search-list?q=${encodeURIComponent(query)}`);
                const json = await res.json();
                
                if (json.success && json.data.length > 0) {
                    let html = '';
                    json.data.forEach(p => {
                        let isAdmitted = p.ipd_status === 'Admitted';
                        let badge = isAdmitted ? `<span class="badge bg-success-subtle text-success px-2 py-1 rounded-pill" style="font-size: 0.75rem;"><i class="fas fa-bed me-1"></i>IPD</span>` : `<span class="badge bg-secondary-subtle text-secondary px-2 py-1 rounded-pill" style="font-size: 0.75rem;">OPD</span>`;
                        let safeName = p.patient_name ? p.patient_name : 'Unknown Patient';
                        let initials = p.patient_name ? p.patient_name.substring(0, 2).toUpperCase() : 'PT';
                        
                        html += `
                            <button class="dropdown-item py-3 px-3 border-bottom d-flex align-items-center gap-3 patient-search-item" type="button" onclick="selectReconPatient('${p.patient_id}', '${safeName.replace(/'/g, "\\'")}', '${p.admission_id || ''}')" style="transition: all 0.2s; white-space: normal;">
                                <div class="avatar bg-primary-subtle text-primary fw-bold rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" style="width: 45px; height: 45px; font-size: 1.1rem;">
                                    ${initials}
                                </div>
                                <div class="flex-grow-1 min-w-0">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <h6 class="mb-0 fw-bold text-truncate" style="color: var(--ot-text-main);">${safeName}</h6>
                                        ${badge}
                                    </div>
                                    <div class="text-muted small d-flex flex-wrap gap-2">
                                        <span><i class="fas fa-id-card me-1 opacity-75"></i>${p.patient_id}</span>
                                    </div>
                                </div>
                            </button>
                        `;
                    });
                    
                    html = `<div class="bg-light px-3 py-2 border-bottom text-muted small fw-bold">Search Results (${json.data.length})</div>` + html;
                    dropdown.innerHTML = html;
                    dropdown.style.display = 'block';
                } else {
                    dropdown.innerHTML = '<div class="p-2 text-muted text-center">No patients found</div>';
                    dropdown.style.display = 'block';
                }
            } catch (err) {
                console.error('Patient search error:', err);
            }
        }, 400);
    }

    async function selectReconPatient(id, name, admissionId) {
        document.getElementById('reconSelectedPatientId').value = id;
        document.getElementById('reconSelectedPatientName').value = name;
        document.getElementById('reconSelectedAdmissionId').value = admissionId;
        document.getElementById('reconPatientSearchInput').value = id;
        document.getElementById('reconPatientDropdown').style.display = 'none';

        await loadPatientOrders(id, admissionId);
    }

    async function loadPatientOrders(patientId, admissionId) {
        const tbody = document.getElementById('reconTableBody');
        tbody.innerHTML = '<tr><td colspan="4" class="text-center py-4"><i class="fas fa-spinner fa-spin"></i> Loading orders...</td></tr>';
        
        try {
            const res = await fetch(`${API_BASE}ot/pharmacy-order/pending?patient_id=${patientId}&admission_id=${admissionId}`);
            const json = await res.json();

            if (json.success && json.data.length > 0) {
                currentOrders = json.data;
                renderReconTable();
                document.getElementById('submitReconBtn').disabled = false;
            } else {
                tbody.innerHTML = '<tr><td colspan="4" class="text-center text-muted py-3">No pending pharmacy orders found for this patient today.</td></tr>';
                document.getElementById('submitReconBtn').disabled = true;
                currentOrders = [];
                // If it's empty (e.g. just finished reconciling everything), go back to list
                Swal.fire({
                    icon: 'success',
                    title: 'All Done!',
                    text: 'All orders for this patient have been completed.',
                    timer: 2000,
                    showConfirmButton: false
                });
                setTimeout(() => {
                    toggleReconView('list');
                    loadActiveOrders();
                    loadPharmacyHistory(); // Refresh history tab
                }, 2000);
            }
        } catch (err) {
            console.error(err);
            tbody.innerHTML = '<tr><td colspan="4" class="text-center text-danger py-3">Error loading orders.</td></tr>';
        }
    }

    function renderReconTable() {
        const tbody = document.getElementById('reconTableBody');
        let html = '';

        currentOrders.forEach((item, index) => {
            html += `
                <tr>
                    <td class="fw-bold text-dark">
                        ${item.name}
                        <div class="text-muted small">Total Ordered: <span id="ord-qty-${item.unique_id}">${item.ordered_qty}</span> | Processed: ${parseInt(item.used_qty_so_far || 0) + parseInt(item.returned_qty_so_far || 0)}</div>
                    </td>
                    <td><span class="badge bg-secondary" id="pend-qty-${item.unique_id}">${item.pending_qty}</span></td>
                    <td>
                        <input type="number" class="form-control text-center mx-auto" style="width:80px;" 
                            value="0" min="0" max="${item.pending_qty}" 
                            oninput="updateReconQty(${index})">
                    </td>
                    <td class="text-center">
                        <button class="btn btn-sm btn-outline-primary mb-1" onclick="editOrderItem('${item.unique_id}', ${item.ordered_qty})" title="Edit Ordered Quantity">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn btn-sm btn-outline-danger mb-1" onclick="deleteOrderItem('${item.unique_id}')" title="Delete Medicine from Order">
                            <i class="fas fa-trash-alt"></i>
                        </button>
                    </td>
                </tr>
            `;
            item.used_qty = 0;
            item.returned_qty = 0;
        });

        tbody.innerHTML = html;
    }

    async function editOrderItem(uniqueId, currentQty) {
        const { value: newQty } = await Swal.fire({
            title: 'Edit Requested Quantity',
            input: 'number',
            inputLabel: 'New Quantity',
            inputValue: currentQty,
            showCancelButton: true,
            inputValidator: (value) => {
                if (!value || value <= 0) return 'You need to write a valid quantity greater than zero!';
            }
        });

        if (newQty && newQty != currentQty) {
            try {
                const res = await fetch(`${API_BASE}ot/pharmacy-order/edit`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        patient_id: document.getElementById('reconSelectedPatientId').value,
                        admission_id: document.getElementById('reconSelectedAdmissionId').value,
                        unique_id: uniqueId,
                        qty: newQty
                    })
                });
                const json = await res.json();
                if (json.success) {
                    PH.toast('success', 'Quantity updated successfully.');
                    await loadPatientOrders(document.getElementById('reconSelectedPatientId').value, document.getElementById('reconSelectedAdmissionId').value);
                    loadActiveOrders();
                } else {
                    Swal.fire('Error', json.message || 'Failed to update item.', 'error');
                }
            } catch (err) {
                Swal.fire('Error', 'Network error.', 'error');
            }
        }
    }

    function deleteOrderItem(uniqueId) {
        Swal.fire({
            title: 'Delete Order Item?',
            text: "This will remove the medicine from the order and refund the pharmacy stock immediately.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, delete it!'
        }).then(async (result) => {
            if (result.isConfirmed) {
                try {
                    const res = await fetch(`${API_BASE}ot/pharmacy-order/delete`, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            patient_id: document.getElementById('reconSelectedPatientId').value,
                            admission_id: document.getElementById('reconSelectedAdmissionId').value,
                            unique_id: uniqueId
                        })
                    });
                    const json = await res.json();
                    if (json.success) {
                        PH.toast('success', 'Item deleted from order.');
                        await loadPatientOrders(document.getElementById('reconSelectedPatientId').value, document.getElementById('reconSelectedAdmissionId').value);
                        loadActiveOrders();
                    } else {
                        Swal.fire('Error', json.message || 'Failed to delete item.', 'error');
                    }
                } catch (err) {
                    Swal.fire('Error', 'Network error.', 'error');
                }
            }
        });
    }

    function updateReconQty(index) {
        const tr = document.getElementById('reconTableBody').children[index];
        const retInput = tr.cells[2].querySelector('input');
        
        let ret = parseInt(retInput.value) || 0;
        const max = parseInt(currentOrders[index].pending_qty);
        
        if (ret < 0) ret = 0;

        if (ret > max) {
            retInput.classList.add('border-danger', 'text-danger');
            document.getElementById('submitReconBtn').disabled = true;
        } else {
            retInput.classList.remove('border-danger', 'text-danger');
            document.getElementById('submitReconBtn').disabled = false;
        }

        currentOrders[index].returned_qty = ret;
        currentOrders[index].used_qty = max - ret; // Automatically assume the rest is used/billed
    }

    async function submitReconciliation() {
        if (currentOrders.length === 0) return;

        // Validation check
        let hasError = false;
        let hasProcessing = false;
        
        currentOrders.forEach(o => {
            if (o.used_qty + o.returned_qty > o.pending_qty) {
                hasError = true;
            }
            if (o.used_qty > 0 || o.returned_qty > 0) {
                hasProcessing = true;
            }
        });

        if (hasError) {
            Swal.fire('Error', 'Quantities exceed the pending amounts.', 'error');
            return;
        }
        
        if (!hasProcessing) {
            Swal.fire('Warning', 'No quantities entered to process.', 'warning');
            return;
        }

        const patientId = document.getElementById('reconSelectedPatientId').value;
        const admissionId = document.getElementById('reconSelectedAdmissionId').value;

        const btn = document.getElementById('submitReconBtn');
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Finalizing...';

        try {
            const res = await fetch(`${API_BASE}ot/pharmacy-reconcile`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    patient_id: patientId,
                    admission_id: admissionId,
                    items: currentOrders
                })
            });

            const json = await res.json();
            
            if (json.success) {
                Swal.fire({
                    icon: 'success',
                    title: 'Reconciliation Complete!',
                    text: 'Quantities processed successfully.',
                    timer: 2500,
                    showConfirmButton: false
                });
                
                // Reload patient orders to reflect new pending status
                await loadPatientOrders(patientId, admissionId);
                
            } else {
                Swal.fire('Error', json.message || json.error || 'Failed to submit reconciliation.', 'error');
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-check-circle"></i> Finalize & Bill Used Medicines';
            }
        } catch (err) {
            console.error('Recon error:', err);
            Swal.fire('Error', 'A network error occurred.', 'error');
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-check-circle"></i> Finalize & Bill Used Medicines';
        }
    }

    // Hide dropdowns when clicking outside
    document.addEventListener('click', function(e) {
        if (!e.target.closest('#orderPatientSearchInput') && !e.target.closest('#orderPatientDropdown')) {
            document.getElementById('orderPatientDropdown').style.display = 'none';
        }
        if (!e.target.closest('#medicineSearchInput') && !e.target.closest('#medicineDropdown')) {
            document.getElementById('medicineDropdown').style.display = 'none';
        }
        if (!e.target.closest('#reconPatientSearchInput') && !e.target.closest('#reconPatientDropdown')) {
            document.getElementById('reconPatientDropdown').style.display = 'none';
        }
    });

    let pharmacyHistoryData = [];

    async function loadPharmacyHistory() {
        const tbody = document.getElementById('historyTableBody');
        tbody.innerHTML = '<tr><td colspan="7" class="text-center py-4"><i class="fas fa-spinner fa-spin"></i> Loading...</td></tr>';
        
        try {
            const res = await fetch(`${API_BASE}ot/pharmacy-orders/history`);
            const json = await res.json();
            
            if (json.success && json.data.length > 0) {
                pharmacyHistoryData = json.data;
                let html = '';
                json.data.forEach((o, idx) => {
                    let badgeClass = 'bg-secondary';
                    if (o.status === 'Completed') badgeClass = 'bg-success';
                    if (o.status === 'Pending') badgeClass = 'bg-warning text-dark';
                    if (o.status === 'Partial') badgeClass = 'bg-info text-dark';
                    
                    html += `
                        <tr style="transition: background 0.2s;" onmouseover="this.style.backgroundColor='#f1f3f5'" onmouseout="this.style.backgroundColor=''">
                            <td>${new Date(o.ordered_at).toLocaleString()}</td>
                            <td class="fw-bold" style="cursor:pointer;" onclick="openOrderModal(${idx})">
                                <span style="color: #1f6b4a;"><i class="fas fa-search-plus me-1"></i> ${o.patient_name}</span><br>
                                <small class="text-muted ms-4">${o.patient_id}</small>
                            </td>
                            <td class="text-muted">
                                <span class="badge rounded-pill px-3 py-2" style="background-color: #e8f0ec; color: #1f6b4a;"><i class="fas fa-box-open me-1"></i> ${o.total_items} Medicines</span> 
                            </td>
                            <td class="fw-bold" style="color: #1f6b4a;">${o.total_ordered_qty}</td>
                            <td class="text-primary fw-bold">${o.total_used_qty}</td>
                            <td class="text-success fw-bold">${o.total_returned_qty}</td>
                            <td><span class="badge ${badgeClass}">${o.status}</span></td>
                        </tr>
                    `;
                });
                tbody.innerHTML = html;
            } else {
                tbody.innerHTML = '<tr><td colspan="7" class="text-center text-muted py-4">No pharmacy orders found.</td></tr>';
            }
        } catch (err) {
            console.error('Failed to load history:', err);
            tbody.innerHTML = '<tr><td colspan="7" class="text-center text-danger py-4">Failed to load history.</td></tr>';
        }
    }

    function openOrderModal(idx) {
        const order = pharmacyHistoryData[idx];
        document.getElementById('modalPatientName').innerText = order.patient_name;
        
        let html = '';
        order.items.forEach(item => {
            let iBadge = 'bg-secondary';
            if (item.status === 'Completed') iBadge = 'bg-success';
            if (item.status === 'Pending') iBadge = 'bg-warning text-dark';
            if (item.status === 'Partial') iBadge = 'bg-info text-dark';
            
            html += `
                <tr style="border-bottom: 1px solid #f1f3f5;">
                    <td class="fw-bold ps-3" style="color: #1f6b4a; padding-top: 12px; padding-bottom: 12px;">${item.medicine_name} <small class="text-muted ms-2">(₹${item.mrp})</small></td>
                    <td class="text-center fw-bold" style="color: #1f6b4a; padding-top: 12px; padding-bottom: 12px;">${item.ordered_qty}</td>
                    <td class="text-center text-primary fw-bold" style="padding-top: 12px; padding-bottom: 12px;">${item.used_qty}</td>
                    <td class="text-center text-success fw-bold" style="padding-top: 12px; padding-bottom: 12px;">${item.returned_qty}</td>
                    <td class="text-center pe-3" style="padding-top: 12px; padding-bottom: 12px;"><span class="badge ${iBadge}">${item.status}</span></td>
                    <td class="text-center pe-3" style="padding-top: 12px; padding-bottom: 12px;"></td>
                </tr>
            `;
        });
        
        document.getElementById('modalOrderDetailsBody').innerHTML = html;
        const modal = new bootstrap.Modal(document.getElementById('orderDetailsModal'));
        modal.show();
    }

    // Toggle Views
    function toggleOrderView(view) {
        if (view === 'list') {
            document.getElementById('order-list-view').style.display = 'block';
            document.getElementById('order-form-view').style.display = 'none';
        } else {
            document.getElementById('order-list-view').style.display = 'none';
            document.getElementById('order-form-view').style.display = 'block';
        }
    }

    function toggleReconView(view) {
        if (view === 'list') {
            document.getElementById('recon-list-view').style.display = 'block';
            document.getElementById('recon-form-view').style.display = 'none';
        } else {
            document.getElementById('recon-list-view').style.display = 'none';
            document.getElementById('recon-form-view').style.display = 'block';
        }
    }

    // Load active orders (Pending / Partial) to display in the grids
    async function loadActiveOrders() {
        const orderTbody = document.getElementById('activeOrdersTableBody');
        const reconTbody = document.getElementById('pendingReturnsTableBody');
        
        try {
            const res = await fetch(`${API_BASE}ot/pharmacy-orders/history`);
            const json = await res.json();
            
            if (json.success && json.data.length > 0) {
                // Filter only pending/partial for Active Orders tab
                const active = json.data.filter(o => o.status === 'Pending' || o.status === 'Partial');
                
                // Filter orders that still need reconciliation (qty > used + returned)
                const pendingRecon = json.data.filter(o => {
                    return o.items.some(item => {
                        const qty = parseInt(item.ordered_qty) || 0;
                        const used = parseInt(item.used_qty) || 0;
                        const ret = parseInt(item.returned_qty) || 0;
                        return (qty - used - ret) > 0;
                    });
                });
                
                // 1. Fill Create Order active table
                if (active.length > 0) {
                    let orderHtml = '';
                    
                    active.forEach((o, idx) => {
                        let badgeClass = o.status === 'Pending' ? 'bg-warning text-dark' : 'bg-info text-dark';
                        
                        // Table row for Create Order default view
                        orderHtml += `
                            <tr>
                                <td>${new Date(o.ordered_at).toLocaleString()}</td>
                                <td class="fw-bold" style="color:#1f6b4a;">${o.patient_name} <span class="text-muted small ms-1">(${o.patient_id})</span></td>
                                <td><span class="badge ${badgeClass}">${o.status}</span></td>
                                <td>
                                    <button class="btn btn-sm btn-outline-primary" onclick="openOrderModalFromActive(${idx})">View Details</button>
                                </td>
                            </tr>
                        `;
                    });
                    
                    orderTbody.innerHTML = orderHtml;
                    // Expose the filtered active data for the modal
                    window.activeOrdersData = active;
                } else {
                    orderTbody.innerHTML = '<tr><td colspan="4" class="text-center text-muted py-4">No active orders found.</td></tr>';
                    window.activeOrdersData = [];
                }

                // 2. Fill Pending Returns/Bills table
                if (pendingRecon.length > 0) {
                    let reconHtml = '';
                    pendingRecon.forEach((o, idx) => {
                        let badgeClass = o.status === 'Completed' ? 'bg-success' : (o.status === 'Pending' ? 'bg-warning text-dark' : 'bg-info text-dark');
                        reconHtml += `
                            <tr>
                                <td>${new Date(o.ordered_at).toLocaleString()}</td>
                                <td class="fw-bold" style="color:#1f6b4a;">${o.patient_name} <span class="text-muted small ms-1">(${o.patient_id})</span></td>
                                <td><span class="badge ${badgeClass}">${o.status}</span></td>
                                <td>
                                    <span class="text-muted small">Ordered:</span> <b>${o.total_ordered_qty}</b> &nbsp;|&nbsp; 
                                    <span class="text-muted small">Processed:</span> <b class="text-success">${parseInt(o.total_used_qty) + parseInt(o.total_returned_qty)}</b>
                                </td>
                                <td>
                                    <button class="btn btn-sm btn-outline-primary" onclick="openReconForPatient('${o.patient_id}', '${o.patient_name.replace(/'/g, "\\'")}', '${o.admission_id || ''}')">Process Bill / Return</button>
                                </td>
                            </tr>
                        `;
                    });
                    reconTbody.innerHTML = reconHtml;
                } else {
                    reconTbody.innerHTML = '<tr><td colspan="5" class="text-center text-muted py-4">No pending returns/bills found.</td></tr>';
                }

            } else {
                orderTbody.innerHTML = '<tr><td colspan="4" class="text-center text-muted py-4">No active orders found.</td></tr>';
                reconTbody.innerHTML = '<tr><td colspan="5" class="text-center text-muted py-4">No pending returns/bills found.</td></tr>';
                window.activeOrdersData = [];
            }
        } catch (err) {
            console.error('Failed to load active orders:', err);
        }
    }

    async function openOrderModalFromActive(idx) {
        if (!window.activeOrdersData) return;
        const order = window.activeOrdersData[idx];
        window.currentModalOrderIdx = idx; // Store for Add Medicine button
        document.getElementById('modalPatientName').innerText = order.patient_name;
        
        let html = '';
        order.items.forEach(item => {
            let iBadge = 'bg-secondary';
            if (item.status === 'Completed') iBadge = 'bg-success';
            if (item.status === 'Pending') iBadge = 'bg-warning text-dark';
            if (item.status === 'Partial') iBadge = 'bg-info text-dark';
            
            let actionHtml = '';
            if (item.status === 'Pending' || item.status === 'Partial') {
                actionHtml = `
                    <button class="btn btn-sm btn-outline-primary me-1" onclick="editModalOrderItem('${item.unique_id}', ${item.ordered_qty}, '${order.patient_id}', '${order.admission_id || ''}', ${idx})" title="Edit Ordered Quantity">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="btn btn-sm btn-outline-danger" onclick="deleteModalOrderItem('${item.unique_id}', '${order.patient_id}', '${order.admission_id || ''}', ${idx})" title="Delete Medicine from Order">
                        <i class="fas fa-trash-alt"></i>
                    </button>
                `;
            }

            html += `
                <tr style="border-bottom: 1px solid #f1f3f5;">
                    <td class="fw-bold ps-3" style="color: #1f6b4a; padding-top: 12px; padding-bottom: 12px;">${item.medicine_name} <small class="text-muted ms-2">(₹${item.mrp})</small></td>
                    <td class="text-center fw-bold" style="color: #1f6b4a; padding-top: 12px; padding-bottom: 12px;">${item.ordered_qty}</td>
                    <td class="text-center text-primary fw-bold" style="padding-top: 12px; padding-bottom: 12px;">${item.used_qty}</td>
                    <td class="text-center text-success fw-bold" style="padding-top: 12px; padding-bottom: 12px;">${item.returned_qty}</td>
                    <td class="text-center pe-3" style="padding-top: 12px; padding-bottom: 12px;"><span class="badge ${iBadge}">${item.status}</span></td>
                    <td class="text-center pe-3" style="padding-top: 12px; padding-bottom: 12px;">${actionHtml}</td>
                </tr>
            `;
        });
        
        document.getElementById('modalOrderDetailsBody').innerHTML = html;
        const modalEl = document.getElementById('orderDetailsModal');
        if (!modalEl.classList.contains('show')) {
            const modal = new bootstrap.Modal(modalEl);
            modal.show();
        }
    }

    async function editModalOrderItem(uniqueId, currentQty, patientId, admissionId, idx) {
        const { value: newQty } = await Swal.fire({
            title: 'Edit Requested Quantity',
            input: 'number',
            inputLabel: 'New Quantity',
            inputValue: currentQty,
            showCancelButton: true,
            inputValidator: (value) => {
                if (!value || value <= 0) return 'You need to write a valid quantity greater than zero!';
            }
        });

        if (newQty && newQty != currentQty) {
            try {
                const res = await fetch(`${API_BASE}ot/pharmacy-order/edit`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ patient_id: patientId, admission_id: admissionId, unique_id: uniqueId, qty: newQty })
                });
                const json = await res.json();
                if (json.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Updated',
                        text: 'Quantity updated successfully.',
                        timer: 2000,
                        showConfirmButton: false
                    });
                    await loadActiveOrders(); // Refreshes window.activeOrdersData
                    // Check if order still exists in active list
                    if (window.activeOrdersData && window.activeOrdersData[idx] && window.activeOrdersData[idx].patient_id === patientId) {
                        openOrderModalFromActive(idx);
                    } else {
                        // Order is fully completed/removed
                        bootstrap.Modal.getInstance(document.getElementById('orderDetailsModal')).hide();
                    }
                } else {
                    Swal.fire('Error', json.error || json.message || 'Failed to update item.', 'error');
                }
            } catch (err) {
                console.error(err);
                Swal.fire('Error', 'Network error. Please check console.', 'error');
            }
        }
    }

    function deleteModalOrderItem(uniqueId, patientId, admissionId, idx) {
        Swal.fire({
            title: 'Delete Order Item?',
            text: "This will remove the medicine from the order and refund stock.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, delete it!'
        }).then(async (result) => {
            if (result.isConfirmed) {
                try {
                    const res = await fetch(`${API_BASE}ot/pharmacy-order/delete`, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ patient_id: patientId, admission_id: admissionId, unique_id: uniqueId })
                    });
                    const json = await res.json();
                    if (json.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Deleted',
                            text: 'Item deleted successfully.',
                            timer: 2000,
                            showConfirmButton: false
                        });
                        await loadActiveOrders(); // Refreshes window.activeOrdersData
                        // Check if order still exists
                        if (window.activeOrdersData && window.activeOrdersData[idx] && window.activeOrdersData[idx].patient_id === patientId) {
                            openOrderModalFromActive(idx);
                        } else {
                            // Order is fully completed/removed
                            bootstrap.Modal.getInstance(document.getElementById('orderDetailsModal')).hide();
                        }
                    } else {
                        Swal.fire('Error', json.error || json.message || 'Failed to delete item.', 'error');
                    }
                } catch (err) {
                    console.error(err);
                    Swal.fire('Error', 'Network error. Please check console.', 'error');
                }
            }
        });
    }

    async function openReconForPatient(id, name, admissionId) {
        toggleReconView('form');
        document.getElementById('reconSelectedPatientId').value = id;
        document.getElementById('reconSelectedPatientName').value = name;
        document.getElementById('reconSelectedAdmissionId').value = admissionId;
        document.getElementById('reconPatientSearchInput').value = id;
        await loadPatientOrders(id, admissionId);
    }

    function addMoreMedicineToOrder() {
        if (typeof window.currentModalOrderIdx !== 'undefined' && window.activeOrdersData) {
            const order = window.activeOrdersData[window.currentModalOrderIdx];
            
            // Hide modal
            bootstrap.Modal.getInstance(document.getElementById('orderDetailsModal')).hide();
            
            // Make sure we are on the Create Order tab
            const orderTab = new bootstrap.Tab(document.querySelector('button[data-bs-target="#order-section"]'));
            orderTab.show();
            
            // Switch to Form view
            toggleOrderView('form');
            
            // Select the patient
            selectOrderPatient(order.patient_id, order.patient_name, order.admission_id || '');
            
            // Load existing items into cart for context
            cart = order.items.map(i => ({
                id: i.unique_id,
                name: i.medicine_name,
                mrp: i.mrp,
                qty: i.ordered_qty,
                maxStock: '-',
                isExisting: true
            }));
            renderCart();
            
            // Focus medicine search
            setTimeout(() => {
                document.getElementById('medicineSearchInput').focus();
            }, 300);
        }
    }

    // Call on load
    document.addEventListener('DOMContentLoaded', () => {
        loadActiveOrders();
    });
</script>

<?php require_once 'includes/ot_foot.php'; ?>
