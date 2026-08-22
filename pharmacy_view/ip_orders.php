<?php
// Extend session to 8 hours for full-shift use
ini_set('session.gc_maxlifetime', 28800);
ini_set('session.cookie_lifetime', 28800);
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: ../login.php"); exit; }
require_once 'includes/db.php';
$pageTitle = 'IP Patient Orders';
include 'includes/ph_head.php';
?>
<style>
/* Compress table to prevent horizontal scrolling */
.ph-table th, .ph-table td {
    padding: 0.75rem 1rem !important;
    font-size: 0.85rem !important;
}
.actions-cell {
    white-space: nowrap;
    width: 80px;
}
.order-badge {
    background: #e0f2fe;
    color: #0284c7;
    padding: 2px 8px;
    border-radius: 12px;
    font-size: 0.7rem;
    font-weight: 700;
}
</style>
<div class="ph-wrap">
<?php include 'includes/pharmacy_sidebar.php'; ?>
<div id="ph-content">
<?php include 'includes/pharmacy_navbar.php'; ?>
<div class="ph-page-body">

<div class="d-flex justify-content-between align-items-center mb-4">
  <div>
    <h1 class="ph-page-title">IP Patient Orders</h1>
    <p class="ph-page-subtitle">View pharmacy requests from inpatient wards</p>
  </div>
</div>

<!-- Orders Table -->
<div class="ph-card">
  <div class="ph-table-wrap">
    <table class="ph-table">
      <thead>
        <tr>
          <th>Date</th>
          <th>Patient Name</th>
          <th>Admission ID</th>
          <th>Location (Ward/Room/Bed)</th>
          <th>Items Ordered</th>
          <th>Status</th>
          <th class="text-end actions-cell">Actions</th>
        </tr>
      </thead>
      <tbody id="tableBody">
        <tr><td colspan="7" class="text-center py-4"><div class="spinner-border text-primary" role="status"></div></td></tr>
      </tbody>
    </table>
  </div>
</div>

</div></div></div>

<!-- Order Detail Modal -->
<div class="modal fade" id="detailModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content" style="border-radius: 12px; border: none; box-shadow: 0 10px 30px rgba(0,0,0,0.15);">
      <div class="modal-header" style="border-bottom: 1px solid rgba(0,0,0,0.05);">
        <h5 class="modal-title" style="color: #1e293b; font-weight: 900;"><i class="fas fa-list-alt me-2 text-primary"></i> Order Details</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body p-0" id="invoiceContainer" style="background:#fff;">
        <!-- Dynamic invoice content injected here -->
      </div>
      <div class="modal-footer d-flex justify-content-between" style="border-top: 1px solid #e2e8f0; background: #f8fafc;">
        <span class="text-muted" style="font-size:0.85rem;"><i class="fas fa-info-circle me-1"></i> Preview Mode (No Print)</span>
        <button class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<?php include 'includes/ph_foot.php'; ?>
<script>
let allOrders = [];

let lastActiveOrderCount = null;

async function loadOrders(silent = false) {
    try {
        const res = await phGet(API_BASE + 'pharmacy/ip-orders');
        if (res.success) {
            allOrders = res.data;
            
            // Count total active (uncompleted) items across all orders
            const activeCount = allOrders.reduce((total, o) => {
                const uncompletedItems = (o.orders || []).filter(item => item.status !== 'Completed').length;
                return total + uncompletedItems;
            }, 0);
            
            if (lastActiveOrderCount !== null && activeCount > lastActiveOrderCount) {
                PH.toast('info', 'New IP Order Received!', 5000);
            }
            lastActiveOrderCount = activeCount;
            
            // Only re-render if the modal is not currently open to prevent disrupting the user
            if (!document.getElementById('detailModal').classList.contains('show')) {
                renderOrders();
            }
        } else {
            if (!silent) PH.error(res.error || 'Failed to load IP orders');
        }
    } catch (e) {
        console.error(e);
        if (!silent) PH.error('Error loading orders: ' + e.message);
    }
}

// Poll for new orders every 10 seconds
setInterval(() => loadOrders(true), 10000);

function formatDateStr(dateStr) {
    if (!dateStr) return '-';
    const d = new Date(dateStr);
    if (isNaN(d)) return dateStr;
    return d.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
}

function renderOrders() {
    let html = '';
    if (allOrders.length === 0) {
        html = '<tr><td colspan="7" class="text-center py-5 text-muted"><i class="fas fa-inbox fa-3x mb-3 text-light"></i><br>No IP orders found.</td></tr>';
    } else {
        allOrders.forEach(o => {
            const itemCnt = o.orders ? o.orders.length : 0;
            const location = `${o.ward || '-'} / Room ${o.room || '-'} / Bed ${o.bed || '-'}`;
            const isCompleted = o.orders && o.orders.length > 0 && o.orders.every(item => item.status === 'Completed');
            const badgeHtml = isCompleted 
                ? `<span class="badge bg-success rounded-pill"><i class="fas fa-check-circle"></i> Completed</span>` 
                : `<span class="badge bg-warning text-dark rounded-pill"><i class="fas fa-clock"></i> Active</span>`;
                
            html += `
                <tr>
                    <td><div class="fw-bold">${formatDateStr(o.date)}</div></td>
                    <td><div class="fw-bold text-dark">${o.patient_name}</div><div class="text-muted" style="font-size:0.7rem;">${o.patient_id}</div></td>
                    <td><span class="order-badge">${o.admission_id}</span></td>
                    <td>${location}</td>
                    <td><span class="badge bg-secondary rounded-pill">${itemCnt} Items</span></td>
                    <td>${badgeHtml}</td>
                    <td class="text-end">
                        <button class="ph-action-btn view-btn" onclick="viewOrder(${o.id})" title="View Details">
                            <i class="fas fa-eye"></i>
                        </button>
                    </td>
                </tr>
            `;
        });
    }
    document.getElementById('tableBody').innerHTML = html;
}

let detailModal;

function viewOrder(id) {
    if (!detailModal) {
        detailModal = new bootstrap.Modal(document.getElementById('detailModal'), { backdrop: 'static' });
    }
    const order = allOrders.find(o => o.id === id);
    if (!order) return;
    
    // Header
    let html = `
    <div style="padding: 20px 30px; font-family: 'Inter', sans-serif;">
        <div class="text-center mb-4">
            <h2 style="color: #0d9488; font-weight: 800; margin-bottom: 5px; letter-spacing: 1px;">GM HOSPITALS</h2>
            <div style="color: #64748b; font-size: 0.95rem; font-weight: 600; margin-bottom: 8px;">Nagarabhavi | Basaveshwaranagar</div>
            <div style="color: #94a3b8; font-size: 0.8rem; margin-bottom: 3px;">No. 335, 3rd Stage, 4th Block, Siddaiah Puranik Road, Basaveshwaranagar, Bengaluru 560079</div>
            <div style="color: #94a3b8; font-size: 0.8rem; margin-bottom: 10px;">D.L. No. KA20-804-103813 / KA21-804-103814</div>
            <span style="border: 1px solid #cbd5e1; border-radius: 20px; padding: 3px 12px; font-size: 0.75rem; font-weight: 700; color: #334155; display: inline-block;">GSTIN: 29AAFC P8756N3ZE</span>
        </div>

        <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; padding: 12px 15px; margin-bottom: 20px; display: flex; flex-wrap: wrap; font-size: 0.85rem;">
            <div style="flex: 1; min-width: 33%;">
                <div style="margin-bottom: 6px;"><span style="color: #64748b; font-weight: 600;">PATIENT NAME:</span> <span style="font-weight: 700; color: #334155;">${order.patient_name || '-'}</span></div>
                <div><span style="color: #64748b; font-weight: 600;">PATIENT ID:</span> <span style="font-weight: 700; color: #334155;">${order.patient_id || '-'}</span></div>
            </div>
            <div style="flex: 1; min-width: 33%;">
                <div style="margin-bottom: 6px;"><span style="color: #64748b; font-weight: 600;">ORDER ID:</span> <span style="font-weight: 700; color: #334155;">IPO-${order.id}</span></div>
                <div><span style="color: #64748b; font-weight: 600;">LOCATION:</span> <span style="font-weight: 700; color: #334155;">${order.ward || '-'} / Room ${order.room || '-'} / Bed ${order.bed || '-'}</span></div>
            </div>
            <div style="flex: 1; min-width: 33%;">
                <div style="margin-bottom: 6px;"><span style="color: #64748b; font-weight: 600;">DATE:</span> <span style="font-weight: 700; color: #334155;">${formatDateStr(order.date)}</span></div>
                <div><span style="color: #64748b; font-weight: 600;">ADMISSION ID:</span> <span style="font-weight: 700; color: #334155;">${order.admission_id || '-'}</span></div>
            </div>
        </div>

        <div style="font-size: 0.75rem; font-weight: 700; color: #64748b; letter-spacing: 1px; margin-bottom: 8px;">ITEM DETAILS</div>
        <table style="width: 100%; border-collapse: collapse; margin-bottom: 20px; font-size: 0.8rem;">
            <thead>
                <tr style="border-top: 1px solid #cbd5e1; border-bottom: 1px solid #cbd5e1; background: #f8fafc; color: #334155;">
                    <th style="padding: 8px 4px; text-align: center;">SL</th>
                    <th style="padding: 8px 4px; text-align: left;">DESCRIPTION</th>
                    <th style="padding: 8px 4px; text-align: center;">HSN</th>
                    <th style="padding: 8px 4px; text-align: center;">BATCH</th>
                    <th style="padding: 8px 4px; text-align: center;">EXPIRY</th>
                    <th style="padding: 8px 4px; text-align: center;">QTY</th>
                    <th style="padding: 8px 4px; text-align: right;">RATE</th>
                    <th style="padding: 8px 4px; text-align: center;">DISC%</th>
                    <th style="padding: 8px 4px; text-align: center;">CGST%</th>
                    <th style="padding: 8px 4px; text-align: right;">CGST(₹)</th>
                    <th style="padding: 8px 4px; text-align: center;">SGST%</th>
                    <th style="padding: 8px 4px; text-align: right;">SGST(₹)</th>
                    <th style="padding: 8px 4px; text-align: right;">TOTAL(₹)</th>
                </tr>
            </thead>
            <tbody>
    `;

    let subTotal = 0;
    let totalTax = 0;
    let totalDiscount = order.global_discount || 0; 
    
    if (!order.orders || order.orders.length === 0) {
        html += `<tr><td colspan="13" style="text-align:center; padding: 20px; color: #94a3b8;">No items in this order</td></tr>`;
    } else {
        order.orders.forEach((item, idx) => {
            const data = item.data || {};
            const isItemCompleted = item.status === 'Completed';
            
            const rate = parseFloat(data.sales_price || data.mrp || 0);
            const qty = parseInt(data.qty || 0, 10);
            const rawTotal = rate * qty;
            
            const discPercent = parseFloat(data.disc_percent || 0);
            const discAmount = rawTotal * (discPercent / 100);
            const discountedTotal = rawTotal - discAmount;
            
            const taxPercent = parseFloat(data.tax_percent || 0);
            const halfTax = taxPercent / 2;
            
            // Back-calculate tax from inclusive rate
            const baseAmount = discountedTotal / (1 + (taxPercent / 100));
            const taxAmt = discountedTotal - baseAmount;
            const cgstAmt = taxAmt / 2;
            const sgstAmt = taxAmt / 2;
            
            if (!isItemCompleted) {
                subTotal += discountedTotal;
                totalTax += taxAmt;
            }

            const rowStyle = isItemCompleted ? "background-color: #f1f5f9; opacity: 0.6; border-bottom: 1px dotted #e2e8f0;" : "border-bottom: 1px dotted #e2e8f0;";
            const inputHtml = isItemCompleted ? 
                `<span style="font-size: 0.75rem;">${discPercent}%</span>` : 
                `<input type="number" class="form-control form-control-sm text-center" style="width: 60px; padding: 2px; font-size: 0.75rem; display: inline-block;" value="${discPercent || ''}" placeholder="0" min="0" max="100" step="0.1" oninput="updateItemDisc(${order.id}, ${idx}, this.value)">`;

            html += `
                <tr style="${rowStyle}" id="row-${order.id}-${idx}" data-rate="${rate}" data-qty="${qty}" data-tax="${taxPercent}">
                    <td style="padding: 8px 4px; text-align: center; color: #64748b;">
                        ${isItemCompleted ? '<i class="fas fa-check text-success" title="Already Completed"></i>' : (idx + 1)}
                    </td>
                    <td style="padding: 8px 4px;">
                        <div style="font-weight: 700; color: #334155;">${data.name || 'Unknown Item'}</div>
                    </td>
                    <td style="padding: 8px 4px; text-align: center; color: #64748b;">${data.hsn_code || '-'}</td>
                    <td style="padding: 8px 4px; text-align: center; color: #64748b;">${data.batch || '-'}</td>
                    <td style="padding: 8px 4px; text-align: center; color: #64748b;">${data.expiry_date ? data.expiry_date.substring(0,7) : '-'}</td>
                    <td style="padding: 8px 4px; text-align: center; font-weight: 700; color: #334155;">${qty}</td>
                    <td style="padding: 8px 4px; text-align: right; color: #64748b;">${rate.toFixed(2)}</td>
                    <td style="padding: 8px 4px; text-align: center;">
                        ${inputHtml}
                    </td>
                    <td style="padding: 8px 4px; text-align: center; color: #64748b;">${halfTax.toFixed(1)}%</td>
                    <td style="padding: 8px 4px; text-align: right; color: #94a3b8;" id="cgst-${order.id}-${idx}">${cgstAmt.toFixed(2)}</td>
                    <td style="padding: 8px 4px; text-align: center; color: #64748b;">${halfTax.toFixed(1)}%</td>
                    <td style="padding: 8px 4px; text-align: right; color: #94a3b8;" id="sgst-${order.id}-${idx}">${sgstAmt.toFixed(2)}</td>
                    <td style="padding: 8px 4px; text-align: right; font-weight: 700; color: ${isItemCompleted ? '#64748b' : '#166534'};" id="total-${order.id}-${idx}">${discountedTotal.toFixed(2)}</td>
                </tr>
            `;
        });
    }

    const netPayable = subTotal - totalDiscount;

    html += `
            </tbody>
        </table>

        <div style="display: flex; justify-content: flex-end; margin-bottom: 20px;">
            <div style="width: 300px; font-size: 0.85rem;">
                <div style="display: flex; justify-content: space-between; padding: 6px 0; color: #64748b; font-weight: 600;">
                    <span>Sub Total</span>
                    <span style="color: #334155;" id="subtotal-${order.id}">₹ ${subTotal.toFixed(2)}</span>
                </div>
                <div style="display: flex; justify-content: space-between; align-items: center; padding: 6px 0; color: #ef4444; font-weight: 600;">
                    <span>Global Discount (₹)</span>
                    <input type="number" class="form-control form-control-sm text-end text-danger" style="width: 100px; padding: 2px 5px; font-size: 0.8rem; font-weight: bold;" value="${totalDiscount || ''}" placeholder="0" min="0" step="1" oninput="updateGlobalDisc(${order.id}, this.value)">
                </div>
                <div style="display: flex; justify-content: space-between; padding: 10px 12px; background: #ecfdf5; color: #065f46; font-weight: 700; font-size: 1rem; margin-top: 5px; border-radius: 4px;">
                    <span>Net Payable</span>
                    <span id="netpayable-${order.id}">₹ ${netPayable.toFixed(2)}</span>
                </div>
            </div>
        </div>

        <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; padding: 10px 15px; margin-bottom: 15px; font-size: 0.8rem; display: flex; justify-content: space-between; align-items: center; color: #64748b;">
            <div><span style="font-weight: 600;">ORDER ID:</span> <span style="font-weight: 700; color: #334155;">IPO-${order.id}</span></div>
            <div><span style="font-weight: 600;">DATE:</span> <span style="font-weight: 700; color: #334155;">${formatDateStr(order.date)}</span></div>
            <div><span style="font-weight: 600;">PHARMACIST:</span> <span style="font-weight: 700; color: #334155; text-transform: uppercase;"><?= htmlspecialchars($_SESSION['username'] ?? 'System') ?></span></div>
        </div>
        
        <div style="border: 1px dashed #ef4444; background: #fef2f2; color: #b91c1c; padding: 10px 15px; border-radius: 6px; font-size: 0.8rem; font-weight: 500; margin-bottom: 20px;">
            <i class="fas fa-info-circle me-1"></i> Note: This is an IP Order preview. Taxes are back-calculated from inclusive rates for reference.
        </div>
    `;
    
    const isCompleted = order.orders && order.orders.length > 0 && order.orders.every(item => item.status === 'Completed');
    
    if (!isCompleted) {
        html += `
            <div style="text-align: right; margin-top: 15px; border-top: 1px solid #e2e8f0; padding-top: 15px;">
                <button type="button" class="btn btn-success" onclick="completeOrder(${order.id})">
                    <i class="fas fa-check-circle me-1"></i> Complete Order
                </button>
            </div>
        `;
    }

    document.getElementById('invoiceContainer').innerHTML = html;
    detailModal.show();
}

document.addEventListener('DOMContentLoaded', () => {
    loadOrders();
});

async function completeOrder(orderId) {
    const order = allOrders.find(o => o.id === orderId);
    if (!order) return;
    
    // Collect the potentially edited discount data
    const itemsData = order.orders.map(item => ({
        disc_percent: parseFloat(item.data.disc_percent || 0)
    }));
    
    try {
        const res = await phPost(API_BASE + 'pharmacy/ip-orders/complete', {
            order_id: orderId,
            items: itemsData,
            global_discount: order.global_discount || 0
        });
        
        if (res.success) {
            detailModal.hide();
            PH.success('Order completed and Nurse notified!');
            loadOrders(); // Refresh the list
        } else {
            const err = res.error || res.message || 'Failed to complete order';
            if (err.includes('discharged') || err.includes('Discharged')) {
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Discharged Patient',
                        text: 'This patient has already been discharged.',
                        confirmButtonColor: '#1f6b4a'
                    });
                } else {
                    alert('This patient has already been discharged.');
                }
            } else {
                PH.error(err);
            }
        }
    } catch (e) {
        PH.error('Network error while completing order');
    }
}

function updateItemDisc(orderId, idx, val) {
    const order = allOrders.find(o => o.id === orderId);
    if (!order) return;
    let discPercent = parseFloat(val) || 0;
    if (discPercent < 0) discPercent = 0;
    if (discPercent > 100) discPercent = 100;
    
    order.orders[idx].data.disc_percent = discPercent;
    
    // Recalculate this row's values
    const data = order.orders[idx].data;
    const rate = parseFloat(data.sales_price || data.mrp || 0);
    const qty = parseInt(data.qty || 0, 10);
    const rawTotal = rate * qty;
    const discAmount = rawTotal * (discPercent / 100);
    const discountedTotal = rawTotal - discAmount;
    
    const taxPercent = parseFloat(data.tax_percent || 0);
    const baseAmount = discountedTotal / (1 + (taxPercent / 100));
    const taxAmt = discountedTotal - baseAmount;
    
    document.getElementById(`cgst-${orderId}-${idx}`).innerText = (taxAmt / 2).toFixed(2);
    document.getElementById(`sgst-${orderId}-${idx}`).innerText = (taxAmt / 2).toFixed(2);
    document.getElementById(`total-${orderId}-${idx}`).innerText = discountedTotal.toFixed(2);
    
    updateOrderTotals(orderId);
}

function updateGlobalDisc(orderId, val) {
    const order = allOrders.find(o => o.id === orderId);
    if (!order) return;
    let globDisc = parseFloat(val) || 0;
    if (globDisc < 0) globDisc = 0;
    
    order.global_discount = globDisc;
    updateOrderTotals(orderId);
}

function updateOrderTotals(orderId) {
    const order = allOrders.find(o => o.id === orderId);
    if (!order) return;
    
    let subTotal = 0;
    order.orders.forEach(item => {
        const data = item.data || {};
        const rate = parseFloat(data.sales_price || data.mrp || 0);
        const qty = parseInt(data.qty || 0, 10);
        const discPercent = parseFloat(data.disc_percent || 0);
        const rawTotal = rate * qty;
        const discountedTotal = rawTotal - (rawTotal * (discPercent / 100));
        subTotal += discountedTotal;
    });
    
    const globDisc = parseFloat(order.global_discount || 0);
    const netPayable = subTotal - globDisc;
    
    document.getElementById(`subtotal-${orderId}`).innerText = '₹ ' + subTotal.toFixed(2);
    document.getElementById(`netpayable-${orderId}`).innerText = '₹ ' + netPayable.toFixed(2);
}

</script>
</body>
</html>
