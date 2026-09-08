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
  <div class="modal-dialog modal-xl modal-dialog-centered">
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

function escapeHtml(str) {
    if (!str) return '';
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

function renderOrders() {
    let html = '';
    if (allOrders.length === 0) {
        html = '<tr><td colspan="7" class="text-center py-5 text-muted"><i class="fas fa-inbox fa-3x mb-3 text-light"></i><br>No IP orders found.</td></tr>';
    } else {
        allOrders.forEach(o => {
            const itemCnt = o.orders ? o.orders.length : 0;
            const completedCnt = (o.orders || []).filter(item => item.status === 'Completed').length;
            const location = `${o.ward || '-'} / Room ${o.room || '-'} / Bed ${o.bed || '-'}`;
            const isCompleted = itemCnt > 0 && completedCnt === itemCnt;
            
            let badgeHtml = '';
            if (isCompleted) {
                badgeHtml = `<span class="badge bg-success rounded-pill"><i class="fas fa-check-circle"></i> Completed</span>`;
            } else if (completedCnt > 0) {
                badgeHtml = `<span class="badge bg-info text-dark rounded-pill"><i class="fas fa-tasks"></i> Partial (${completedCnt}/${itemCnt})</span>`;
            } else {
                badgeHtml = `<span class="badge bg-warning text-dark rounded-pill"><i class="fas fa-clock"></i> Active</span>`;
            }
                
            const countBadge = (completedCnt > 0 && !isCompleted)
                ? `<span class="badge bg-secondary rounded-pill">${completedCnt}/${itemCnt} Done</span>`
                : `<span class="badge bg-secondary rounded-pill">${itemCnt} Items</span>`;

            html += `
                <tr>
                    <td><div class="fw-bold">${formatDateStr(o.date)}</div></td>
                    <td><div class="fw-bold text-dark">${escapeHtml(o.patient_name)}</div><div class="text-muted" style="font-size:0.7rem;">${escapeHtml(o.patient_id)}</div></td>
                    <td><span class="order-badge">${escapeHtml(o.admission_id)}</span></td>
                    <td>${escapeHtml(location)}</td>
                    <td>${countBadge}</td>
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
    const modalEl = document.getElementById('detailModal');
    if (!modalEl) return;
    if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
        detailModal = bootstrap.Modal.getOrCreateInstance(modalEl, { backdrop: 'static' });
    }
    const order = allOrders.find(o => o.id == id);
    if (!order) {
        console.warn('Order not found for id:', id);
        return;
    }
    
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
                <div style="margin-bottom: 6px;"><span style="color: #64748b; font-weight: 600;">PATIENT NAME:</span> <span style="font-weight: 700; color: #334155;">${escapeHtml(order.patient_name || '-')}</span></div>
                <div><span style="color: #64748b; font-weight: 600;">PATIENT ID:</span> <span style="font-weight: 700; color: #334155;">${escapeHtml(order.patient_id || '-')}</span></div>
            </div>
            <div style="flex: 1; min-width: 33%;">
                <div style="margin-bottom: 6px;"><span style="color: #64748b; font-weight: 600;">ORDER ID:</span> <span style="font-weight: 700; color: #334155;">IPO-${order.id}</span></div>
                <div><span style="color: #64748b; font-weight: 600;">LOCATION:</span> <span style="font-weight: 700; color: #334155;">${escapeHtml(order.ward || '-')} / Room ${escapeHtml(order.room || '-')} / Bed ${escapeHtml(order.bed || '-')}</span></div>
            </div>
            <div style="flex: 1; min-width: 33%;">
                <div style="margin-bottom: 6px;"><span style="color: #64748b; font-weight: 600;">DATE:</span> <span style="font-weight: 700; color: #334155;">${formatDateStr(order.date)}</span></div>
                <div><span style="color: #64748b; font-weight: 600;">ADMISSION ID:</span> <span style="font-weight: 700; color: #334155;">${escapeHtml(order.admission_id || '-')}</span></div>
            </div>
        </div>

        <div class="d-flex justify-content-between align-items-center mb-2">
            <div style="font-size: 0.75rem; font-weight: 700; color: #64748b; letter-spacing: 1px;">ITEM DETAILS &amp; DISPENSING</div>
            <div style="font-size: 0.75rem; color: #64748b;">
                <span class="badge bg-success-subtle text-success border border-success-subtle me-1"><i class="fas fa-check-circle"></i> In Stock</span>
                <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle me-1"><i class="fas fa-exclamation-triangle"></i> Insufficient</span>
                <span class="badge bg-danger-subtle text-danger border border-danger-subtle"><i class="fas fa-ban"></i> Out of Stock</span>
            </div>
        </div>
        <table style="width: 100%; border-collapse: collapse; margin-bottom: 20px; font-size: 0.8rem;">
            <thead>
                <tr style="border-top: 1px solid #cbd5e1; border-bottom: 1px solid #cbd5e1; background: #f8fafc; color: #334155;">
                    <th style="padding: 8px 4px; text-align: center;">SL</th>
                    <th style="padding: 8px 6px; text-align: left;">DESCRIPTION</th>
                    <th style="padding: 8px 4px; text-align: center;">HSN</th>
                    <th style="padding: 8px 4px; text-align: center;">BATCH</th>
                    <th style="padding: 8px 4px; text-align: center;">EXPIRY</th>
                    <th style="padding: 8px 4px; text-align: center;">REQ QTY</th>
                    <th style="padding: 8px 4px; text-align: center;">STOCK</th>
                    <th style="padding: 8px 4px; text-align: right;">MRP (₹)</th>
                    <th style="padding: 8px 4px; text-align: center;">DISC%</th>
                    <th style="padding: 8px 4px; text-align: center;">GST%</th>
                    <th style="padding: 8px 4px; text-align: right;">TOTAL(₹)</th>
                    <th style="padding: 8px 6px; text-align: center;">ACTION</th>
                </tr>
            </thead>
            <tbody>
    `;

    let subTotal = 0;
    let totalDiscount = order.global_discount || 0; 
    let eligibleCount = 0;
    let hasInsufficient = false;
    let hasUncompleted = false;
    
    if (!order.orders || order.orders.length === 0) {
        html += `<tr><td colspan="12" style="text-align:center; padding: 20px; color: #94a3b8;">No items in this order</td></tr>`;
    } else {
        order.orders.forEach((item, idx) => {
            const data = item.data || {};
            const isItemCompleted = item.status === 'Completed';
            
            const rate = parseFloat(data.mrp || data.sales_price || 0);
            const qty = parseInt(data.qty || data.quantity || 0, 10);
            const rawTotal = rate * qty;
            
            const discPercent = parseFloat(data.disc_percent || 0);
            const discAmount = rawTotal * (discPercent / 100);
            const discountedTotal = rawTotal - discAmount;
            
            const gstVal = (data.GST_price !== undefined && data.GST_price !== null && data.GST_price !== '')
                ? (data.GST_price.toString().includes('%') ? data.GST_price : parseFloat(data.GST_price) + '%')
                : (data.tax_percent ? parseFloat(data.tax_percent) + '%' : '-');
            
            const availStock = (data.available_stock !== undefined && data.available_stock !== null) 
                ? parseInt(data.available_stock, 10) 
                : (data.stock !== undefined ? parseInt(data.stock, 10) : 0);
            const isInactive = data.is_active !== undefined && parseInt(data.is_active, 10) === 0;

            if (!isItemCompleted) {
                hasUncompleted = true;
                subTotal += discountedTotal;
                if (!isInactive && availStock >= qty) {
                    eligibleCount++;
                } else if (!isInactive && availStock < qty) {
                    hasInsufficient = true;
                }
            }

            // Determine stock badge & action button
            let stockHtml = '';
            let actionHtml = '';

            if (isItemCompleted) {
                stockHtml = `<span class="text-muted" style="font-size:0.75rem;">—</span>`;
                actionHtml = `
                    <span class="badge bg-success text-white py-1 px-2" style="font-size: 0.75rem;">
                        <i class="fas fa-check-circle me-1"></i> Completed
                    </span>
                `;
            } else if (isInactive) {
                stockHtml = `<span class="badge bg-secondary" style="font-size:0.7rem;">Inactive</span>`;
                actionHtml = `
                    <span class="badge bg-secondary py-1 px-2" style="font-size: 0.75rem;" title="Product is inactive in inventory">
                        <i class="fas fa-ban me-1"></i> Unavailable
                    </span>
                `;
            } else if (availStock <= 0) {
                stockHtml = `<span class="badge bg-danger text-white" style="font-size:0.7rem;">0 in Stock</span>`;
                actionHtml = `
                    <button type="button" class="btn btn-sm btn-outline-danger py-1 px-2 fw-semibold" style="font-size:0.75rem;" onclick="showInsufficientStockAlert(${order.id}, ${idx})" title="Click to view stock notice">
                        <i class="fas fa-times-circle me-1"></i> Out of Stock
                    </button>
                `;
            } else if (availStock < qty) {
                stockHtml = `<span class="badge bg-warning text-dark border border-warning" style="font-size:0.7rem; font-weight:700;">${availStock} in Stock</span>`;
                actionHtml = `
                    <button type="button" class="btn btn-sm btn-outline-warning text-dark py-1 px-2 fw-bold" style="font-size:0.75rem; background:#fffbeb;" onclick="showInsufficientStockAlert(${order.id}, ${idx})" title="Click to view stock notice">
                        <i class="fas fa-exclamation-triangle text-danger me-1"></i> Insufficient
                    </button>
                `;
            } else {
                stockHtml = `<span class="badge bg-success-subtle text-success border border-success-subtle fw-bold" style="font-size:0.75rem;">${availStock} in Stock</span>`;
                actionHtml = `
                    <button type="button" class="btn btn-sm btn-success py-1 px-3 fw-bold" id="btn-complete-${order.id}-${idx}" onclick="completeSingleItem(${order.id}, ${idx})">
                        <i class="fas fa-check me-1"></i> Complete
                    </button>
                `;
            }

            const rowStyle = isItemCompleted 
                ? "background-color: #f8fafc; opacity: 0.7; border-bottom: 1px dotted #e2e8f0;" 
                : "border-bottom: 1px dotted #e2e8f0;";
            const inputHtml = isItemCompleted ? 
                `<span style="font-size: 0.75rem;">${discPercent}%</span>` : 
                `<input type="number" class="form-control form-control-sm text-center" style="width: 55px; padding: 2px; font-size: 0.75rem; display: inline-block;" value="${discPercent || ''}" placeholder="0" min="0" max="100" step="0.1" oninput="updateItemDisc(${order.id}, ${idx}, this.value)">`;

            html += `
                <tr style="${rowStyle}" id="row-${order.id}-${idx}" data-rate="${rate}" data-qty="${qty}">
                    <td style="padding: 8px 4px; text-align: center; color: #64748b;">
                        ${isItemCompleted ? '<i class="fas fa-check text-success" title="Already Completed"></i>' : (idx + 1)}
                    </td>
                    <td style="padding: 8px 6px;">
                        <div style="font-weight: 700; color: #334155;">${escapeHtml(data.name || 'Unknown Item')}</div>
                    </td>
                    <td style="padding: 8px 4px; text-align: center; color: #64748b;">${escapeHtml(data.hsn_code || '-')}</td>
                    <td style="padding: 8px 4px; text-align: center; color: #64748b;">${escapeHtml(data.batch || '-')}</td>
                    <td style="padding: 8px 4px; text-align: center; color: #64748b;">${data.expiry_date ? escapeHtml(data.expiry_date.substring(0,7)) : '-'}</td>
                    <td style="padding: 8px 4px; text-align: center;">
                        <span class="badge bg-light text-dark border px-2 py-1" style="font-size:0.8rem; font-weight:700;">${qty}</span>
                    </td>
                    <td style="padding: 8px 4px; text-align: center;">
                        ${stockHtml}
                    </td>
                    <td style="padding: 8px 4px; text-align: right; color: #64748b;">${rate.toFixed(2)}</td>
                    <td style="padding: 8px 4px; text-align: center;">
                        ${inputHtml}
                    </td>
                    <td style="padding: 8px 4px; text-align: center; color: #64748b; font-weight: 600;">${escapeHtml(gstVal)}</td>
                    <td style="padding: 8px 4px; text-align: right; font-weight: 700; color: ${isItemCompleted ? '#64748b' : '#166534'};" id="total-${order.id}-${idx}">${discountedTotal.toFixed(2)}</td>
                    <td style="padding: 8px 6px; text-align: center; white-space: nowrap;">
                        ${actionHtml}
                    </td>
                </tr>
            `;
        });
    }

    const netPayable = Math.max(0, subTotal - totalDiscount);
    const allCompleted = !hasUncompleted;

    html += `
            </tbody>
        </table>

        <div style="display: flex; justify-content: flex-end; margin-bottom: 20px;">
            <div style="width: 320px; font-size: 0.85rem;">
                <div style="display: flex; justify-content: space-between; padding: 6px 0; color: #64748b; font-weight: 600;">
                    <span>Pending Sub Total</span>
                    <span style="color: #334155;" id="subtotal-${order.id}">₹ ${subTotal.toFixed(2)}</span>
                </div>
                <div style="display: flex; justify-content: space-between; align-items: center; padding: 6px 0; color: #ef4444; font-weight: 600;">
                    <span>Global Discount (₹)</span>
                    <input type="number" class="form-control form-control-sm text-end text-danger" style="width: 100px; padding: 2px 5px; font-size: 0.8rem; font-weight: bold;" value="${totalDiscount || ''}" placeholder="0" min="0" step="1" oninput="updateGlobalDisc(${order.id}, this.value)">
                </div>
                <div style="display: flex; justify-content: space-between; padding: 10px 12px; background: #ecfdf5; color: #065f46; font-weight: 700; font-size: 1rem; margin-top: 5px; border-radius: 4px;">
                    <span>Pending Net Payable</span>
                    <span id="netpayable-${order.id}">₹ ${netPayable.toFixed(2)}</span>
                </div>
            </div>
        </div>

        <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; padding: 10px 15px; margin-bottom: 15px; font-size: 0.8rem; display: flex; justify-content: space-between; align-items: center; color: #64748b;">
            <div><span style="font-weight: 600;">ORDER ID:</span> <span style="font-weight: 700; color: #334155;">IPO-${order.id}</span></div>
            <div><span style="font-weight: 600;">DATE:</span> <span style="font-weight: 700; color: #334155;">${formatDateStr(order.date)}</span></div>
            <div><span style="font-weight: 600;">PHARMACIST:</span> <span style="font-weight: 700; color: #334155; text-transform: uppercase;"><?= htmlspecialchars($_SESSION['username'] ?? 'System') ?></span></div>
        </div>
        
        <div style="border: 1px dashed #0d9488; background: #f0fdfa; color: #0f766e; padding: 10px 15px; border-radius: 6px; font-size: 0.8rem; font-weight: 500; margin-bottom: 15px;">
            <i class="fas fa-info-circle me-1"></i> Note: Rates shown are retail MRP (inclusive of GST). Stock will be deducted automatically from inventory when each item is completed.
        </div>
    `;
    
    // Bottom Bar (Item-by-item dispensing status)
    html += `
        <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 15px; border-top: 1px solid #e2e8f0; padding-top: 15px;">
            <div>
                ${hasInsufficient ? `
                    <div class="text-warning-emphasis small fw-semibold">
                        <i class="fas fa-exclamation-triangle text-warning me-1"></i> Some items cannot be completed due to insufficient stock.
                    </div>
                ` : `
                    <div class="text-muted small">
                        <i class="fas fa-info-circle me-1 text-primary"></i> Complete medicines individually using the row <strong>Complete</strong> buttons above.
                    </div>
                `}
            </div>
            <div>
                ${allCompleted ? `
                    <span class="badge bg-success py-2 px-3 fs-6">
                        <i class="fas fa-check-circle me-1"></i> All Items Completed
                    </span>
                ` : ''}
            </div>
        </div>
    </div>
    `;

    document.getElementById('invoiceContainer').innerHTML = html;
    detailModal.show();
}

document.addEventListener('DOMContentLoaded', () => {
    loadOrders();
});

// Display a professional popup when an item cannot be completed due to insufficient stock
function showInsufficientStockAlert(orderId, idx) {
    const order = allOrders.find(o => o.id == orderId);
    if (!order || !order.orders || !order.orders[idx]) return;
    const item = order.orders[idx];
    const data = item.data || {};
    const prodName = data.name || data.product_name || 'Item';
    const avail = (data.available_stock !== undefined && data.available_stock !== null) 
        ? parseInt(data.available_stock, 10) 
        : (data.stock !== undefined ? parseInt(data.stock, 10) : 0);
    const req = parseInt(data.qty || data.quantity || 0, 10);
    
    if (typeof Swal !== 'undefined') {
        Swal.fire({
            icon: 'warning',
            title: '<span style="color:#b45309; font-weight:700;">Insufficient Stock</span>',
            html: `
                <div style="text-align: left; font-size: 0.95rem; color: #334155; line-height: 1.6;">
                    <div style="padding: 10px 14px; background: #fffbeb; border: 1px solid #fde68a; border-radius: 6px; margin-bottom: 12px;">
                        <strong style="color: #92400e; font-size: 1.05rem;">${escapeHtml(prodName)}</strong>
                    </div>
                    <p style="margin-bottom: 8px;">
                        Only <strong style="color: #dc2626; font-size: 1.1rem;">${avail} unit${avail === 1 ? '' : 's'}</strong> ${avail === 1 ? 'is' : 'are'} currently available, but the requested quantity is <strong style="color: #1e293b; font-size: 1.1rem;">${req}</strong>.
                    </p>
                    <p style="margin-bottom: 0; color: #64748b; font-size: 0.85rem;">
                        Please reduce the requested quantity or update the stock before completing the order.
                    </p>
                </div>
            `,
            confirmButtonText: '<i class="fas fa-check me-1"></i> Understood',
            confirmButtonColor: '#0d9488',
            customClass: {
                popup: 'rounded-4'
            }
        });
    } else {
        alert(`Insufficient Stock for ${prodName}:\nOnly ${avail} units are currently available, but requested quantity is ${req}.\nPlease reduce the requested quantity or update the stock before completing the order.`);
    }
}

// Complete a single item from the requisition
async function completeSingleItem(orderId, idx) {
    const order = allOrders.find(o => o.id == orderId);
    if (!order || !order.orders || !order.orders[idx]) return;
    
    const item = order.orders[idx];
    const data = item.data || {};
    const prodName = data.name || data.product_name || 'Item';
    const discPercent = parseFloat(data.disc_percent || 0);
    const reqQty = parseInt(data.qty || data.quantity || 0, 10);
    const availStock = (data.available_stock !== undefined && data.available_stock !== null) 
        ? parseInt(data.available_stock, 10) 
        : (data.stock !== undefined ? parseInt(data.stock, 10) : 0);
    
    // Front-end pre-validation check
    if (availStock < reqQty) {
        showInsufficientStockAlert(orderId, idx);
        return;
    }
    
    const btn = document.getElementById(`btn-complete-${orderId}-${idx}`);
    if (btn) {
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
    }
    
    try {
        const res = await phPost(API_BASE + 'pharmacy/ip-orders/complete', {
            order_id: orderId,
            item_index: idx,
            disc_percent: discPercent,
            global_discount: order.global_discount || 0
        });
        
        if (res.success) {
            PH.toast('success', `${prodName} completed! Stock deducted.`);
            await loadOrders(true);
            renderOrders();
            viewOrder(orderId);
            if (typeof loadNotifCount === 'function') {
                loadNotifCount();
            }
        } else {
            const err = res.error || res.message || 'Failed to complete item';
            if (err.includes('Insufficient stock') || err.includes('Insufficient Stock') || err.includes('out of stock')) {
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'error',
                        title: '<span style="color:#b91c1c; font-weight:700;">Insufficient Stock</span>',
                        text: err,
                        confirmButtonColor: '#0d9488'
                    });
                } else {
                    alert(err);
                }
            } else if (err.includes('discharged') || err.includes('Discharged')) {
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
            if (btn) {
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-check me-1"></i> Complete';
            }
        }
    } catch (e) {
        console.error(e);
        PH.error('Network error while completing item');
        if (btn) {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-check me-1"></i> Complete';
        }
    }
}

// Complete all available items in the order
async function completeEligibleItems(orderId) {
    const order = allOrders.find(o => o.id == orderId);
    if (!order || !order.orders) return;
    
    const eligibleItems = order.orders.filter((item) => {
        if (item.status === 'Completed') return false;
        const data = item.data || {};
        const avail = (data.available_stock !== undefined && data.available_stock !== null) 
            ? parseInt(data.available_stock, 10) 
            : (data.stock !== undefined ? parseInt(data.stock, 10) : 0);
        const req = parseInt(data.qty || data.quantity || 0, 10);
        const isInactive = data.is_active !== undefined && parseInt(data.is_active, 10) === 0;
        return !isInactive && avail >= req;
    });
    
    if (eligibleItems.length === 0) {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: 'info',
                title: 'No Available Stock',
                text: 'None of the remaining items have sufficient stock to be completed.',
                confirmButtonColor: '#0d9488'
            });
        } else {
            alert('None of the remaining items have sufficient stock to be completed.');
        }
        return;
    }
    
    const itemsData = order.orders.map(item => ({
        disc_percent: parseFloat(item.data.disc_percent || 0)
    }));
    
    const bulkBtn = document.getElementById(`btn-bulk-complete-${orderId}`);
    if (bulkBtn) {
        bulkBtn.disabled = true;
        bulkBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Completing...';
    }
    
    try {
        const res = await phPost(API_BASE + 'pharmacy/ip-orders/complete', {
            order_id: orderId,
            items: itemsData,
            global_discount: order.global_discount || 0
        });
        
        if (res.success) {
            let msg = `${res.completed_count || eligibleItems.length} item(s) completed successfully!`;
            if (res.errors && res.errors.length > 0) {
                msg += ' Some items could not be completed due to insufficient stock.';
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Partially Completed',
                        html: `<div>${msg}</div><ul class="text-start mt-2 text-danger small">${res.errors.map(e => `<li>${e}</li>`).join('')}</ul>`,
                        confirmButtonColor: '#0d9488'
                    });
                } else {
                    alert(msg);
                }
            } else {
                PH.toast('success', msg);
            }
            await loadOrders(true);
            renderOrders();
            viewOrder(orderId);
            if (typeof loadNotifCount === 'function') {
                loadNotifCount();
            }
        } else {
            const err = res.error || res.message || 'Failed to complete orders';
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'error',
                    title: 'Stock Issue',
                    text: err,
                    confirmButtonColor: '#0d9488'
                });
            } else {
                PH.error(err);
            }
            if (bulkBtn) {
                bulkBtn.disabled = false;
                bulkBtn.innerHTML = '<i class="fas fa-check-double me-1"></i> Complete All Available Items';
            }
        }
    } catch (e) {
        console.error(e);
        PH.error('Network error while completing orders');
        if (bulkBtn) {
            bulkBtn.disabled = false;
            bulkBtn.innerHTML = '<i class="fas fa-check-double me-1"></i> Complete All Available Items';
        }
    }
}

// Backward compatibility
function completeOrder(orderId) {
    completeEligibleItems(orderId);
}

function updateItemDisc(orderId, idx, val) {
    const order = allOrders.find(o => o.id == orderId);
    if (!order || !order.orders || !order.orders[idx]) return;
    let discPercent = parseFloat(val) || 0;
    if (discPercent < 0) discPercent = 0;
    if (discPercent > 100) discPercent = 100;
    
    order.orders[idx].data.disc_percent = discPercent;
    
    // Recalculate this row's values
    const data = order.orders[idx].data;
    const rate = parseFloat(data.mrp || data.sales_price || 0);
    const qty = parseInt(data.qty || data.quantity || 0, 10);
    const rawTotal = rate * qty;
    const discAmount = rawTotal * (discPercent / 100);
    const discountedTotal = rawTotal - discAmount;
    
    const totalEl = document.getElementById(`total-${orderId}-${idx}`);
    if (totalEl) totalEl.innerText = discountedTotal.toFixed(2);
    
    updateOrderTotals(orderId);
}

function updateGlobalDisc(orderId, val) {
    const order = allOrders.find(o => o.id == orderId);
    if (!order) return;
    let globDisc = parseFloat(val) || 0;
    if (globDisc < 0) globDisc = 0;
    
    order.global_discount = globDisc;
    updateOrderTotals(orderId);
}

function updateOrderTotals(orderId) {
    const order = allOrders.find(o => o.id == orderId);
    if (!order) return;
    
    let subTotal = 0;
    order.orders.forEach(item => {
        if (item.status === 'Completed') return;
        const data = item.data || {};
        const rate = parseFloat(data.mrp || data.sales_price || 0);
        const qty = parseInt(data.qty || data.quantity || 0, 10);
        const discPercent = parseFloat(data.disc_percent || 0);
        const rawTotal = rate * qty;
        const discountedTotal = rawTotal - (rawTotal * (discPercent / 100));
        subTotal += discountedTotal;
    });
    
    const globDisc = parseFloat(order.global_discount || 0);
    const netPayable = Math.max(0, subTotal - globDisc);
    
    const subEl = document.getElementById(`subtotal-${orderId}`);
    if (subEl) subEl.innerText = '₹ ' + subTotal.toFixed(2);
    const netEl = document.getElementById(`netpayable-${orderId}`);
    if (netEl) netEl.innerText = '₹ ' + netPayable.toFixed(2);
}

</script>
</body>
</html>
