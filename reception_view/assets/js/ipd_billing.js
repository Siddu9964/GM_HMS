/**
 * IPD Billing Terminal — Main JavaScript Application
 * Handles all UI interactions, API calls, and live calculations.
 */

const billing = (function() {
    'use strict';

    // State
    let currentAdmissionId = null;
    let currentBillId = null;
    let currentPatientId = null;
    let currentMaster = null;
    let currentBedInfo = null;

    const API_URL = window.BILLING_API || '/GM_HMS/api/';
    const USER_ROLE = window.USER_ROLE || 'Receptionist';

    // ─────────────────────────────────────────────────────────────
    // INIT
    // ─────────────────────────────────────────────────────────────
    function init() {
        initSearch();
        initShortcuts();
        initCloseClick();
        loadDashboardStats();
    }

    // ─────────────────────────────────────────────────────────────
    // SEARCH & LOAD
    // ─────────────────────────────────────────────────────────────
    let searchTimeout;
    function initSearch() {
        const input = document.getElementById('admissionSearchInput');
        if (!input) return;

        input.addEventListener('input', function(e) {
            clearTimeout(searchTimeout);
            const val = e.target.value.trim();
            if (val.length < 2) {
                document.getElementById('admissionSearchDropdown').classList.remove('open');
                return;
            }
            searchTimeout = setTimeout(() => doSearch(val), 300);
        });

        // Close dropdown when clicking outside
        document.addEventListener('click', function(e) {
            if (!e.target.closest('.search-zone-input-wrap')) {
                const dd = document.getElementById('admissionSearchDropdown');
                if(dd) dd.classList.remove('open');
            }
        });
    }

    async function doSearch(query) {
        try {
            const res = await fetch(`${API_URL}ipd-billing-master?action=search_admissions&q=${encodeURIComponent(query)}`);
            const json = await res.json();
            const dd = document.getElementById('admissionSearchDropdown');
            
            if (!json.success || !json.data.length) {
                dd.innerHTML = `<div class="asd-empty"><i class="fas fa-search"></i> No admitted patients found</div>`;
            } else {
                let html = '';
                json.data.forEach(p => {
                    const statusClass = p.payment_status === 'Paid' ? 'paid' : (p.payment_status === 'Partial' ? 'partial' : 'pending');
                    html += `
                        <div class="asd-item" onclick="billing.loadAdmission('${p.admission_id}', '${p.patient_id}')">
                            <div class="asd-icon">${p.patient_name.charAt(0)}</div>
                            <div style="flex:1;">
                                <div class="asd-name">${p.patient_name} <span style="font-weight:normal; font-size:0.8rem; color:var(--slate);">(${p.age}/${p.sex.charAt(0)})</span></div>
                                <div class="asd-meta">${p.admission_id} · Bed: ${p.ward_name} ${p.bed_number}</div>
                            </div>
                            ${p.bill_id ? `<div class="asd-badge ${statusClass}">${p.payment_status}</div>` : `<div class="asd-badge pending">NEW</div>`}
                        </div>
                    `;
                });
                dd.innerHTML = html;
            }
            dd.classList.add('open');
        } catch (e) {
            console.error('Search error', e);
        }
    }

    async function loadAdmission(admissionId, patientId) {
        document.getElementById('admissionSearchDropdown').classList.remove('open');
        document.getElementById('admissionSearchInput').value = '';
        
        currentAdmissionId = admissionId;
        currentPatientId = patientId;
        
        showToast('Loading patient details...', 'info');

        try {
            // This endpoint creates the master if it doesn't exist
            const res = await fetch(`${API_URL}ipd-billing-master`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    action: 'create',
                    admission_id: admissionId,
                    patient_id: patientId
                })
            });
            const json = await res.json();
            
            if (json.success && json.data) {
                currentMaster = json.data;
                currentBillId = json.data.bill_id;
                
                // Set bed info for room rent modal
                currentBedInfo = {
                    ward: json.data.ward_name,
                    room: json.data.room_name,
                    bed: json.data.bed_number,
                    bed_rent: json.data.amount_per_day,
                    nursing: json.data.nursig_charge,
                    duty_dr: json.data.doctor_charge,
                    total_per_day: json.data.total_bed_amount
                };

                // Hide empty state, show workspace
                document.getElementById('billingEmptyState').style.display = 'none';
                document.getElementById('billingWorkspace').style.display = 'block';
                
                if (json.message === 'Billing master created') {
                    showToast('New billing record created', 'success');
                }

                updateWorkspaceUI();
                await Promise.all([
                    loadItems(),
                    loadPayments()
                ]);
            } else {
                showToast(json.message || 'Failed to load patient', 'error');
            }
        } catch (e) {
            console.error(e);
            showToast('Network error while loading patient', 'error');
        }
    }

    // ─────────────────────────────────────────────────────────────
    // UI UPDATES
    // ─────────────────────────────────────────────────────────────
    function updateWorkspaceUI() {
        if (!currentMaster) return;
        const m = currentMaster;

        // Header Card
        document.getElementById('phcAvatar').textContent = m.patient_name.substring(0, 2).toUpperCase();
        document.getElementById('phcName').textContent = m.patient_name;
        document.getElementById('phcAge').textContent = `${m.age} / ${m.sex}`;
        document.getElementById('phcAdmId').textContent = m.admission_id;
        document.getElementById('phcDoctor').textContent = `Dr. ${m.doctor_name || 'N/A'}`;
        
        document.getElementById('phcBed').textContent = `${m.ward_name} · ${m.bed_number}`;
        
        const admDateStr = new Date(m.admission_date).toLocaleDateString('en-GB', {day:'numeric', month:'short', year:'numeric'});
        let datesStr = admDateStr + ' → ';
        datesStr += m.discharge_date ? new Date(m.discharge_date).toLocaleDateString('en-GB', {day:'numeric', month:'short'}) : 'Present';
        document.getElementById('phcDates').textContent = datesStr;
        document.getElementById('phcDays').textContent = `${m.total_days} Days`;
        
        const bal = parseFloat(m.balance_due);
        const balEl = document.getElementById('phcBalanceDue');
        animateValue(balEl, bal, true);
        balEl.className = 'phc-balance-val ' + (bal <= 0 ? 'is-paid' : (parseFloat(m.amount_paid) > 0 ? 'is-partial' : 'is-pending'));
        
        document.getElementById('phcPayStatus').textContent = m.payment_status.toUpperCase();
        
        document.getElementById('phcBillNo').textContent = m.bill_id;
        const bStatus = document.getElementById('phcBillingStatus');
        bStatus.textContent = m.billing_status.replace('_', ' ');
        bStatus.style.color = m.billing_status === 'FINALIZED' ? '#166534' : (m.billing_status === 'CANCELLED' ? '#991b1b' : '#0369a1');

        // Toggle Insurance Button
        const btnIns = document.getElementById('btnInsuranceInfo');
        if (m.bill_type === 'SELF') {
            btnIns.innerHTML = '<i class="fas fa-plus"></i> Set Insurance';
        } else {
            btnIns.innerHTML = '<i class="fas fa-shield-alt"></i> ' + m.bill_type;
        }

        // Print buttons
        document.getElementById('btnPrintFinal').disabled = m.billing_status !== 'FINALIZED';

        // Financial Summary Panel
        animateValue(document.getElementById('fsVal_ROOM_RENT'), parseFloat(m.room_charges), true);
        animateValue(document.getElementById('fsVal_DOCTOR_VISIT'), parseFloat(m.doctor_charges), true);
        animateValue(document.getElementById('fsVal_LAB'), parseFloat(m.lab_charges), true);
        animateValue(document.getElementById('fsVal_RADIOLOGY'), parseFloat(m.radiology_charges), true);
        animateValue(document.getElementById('fsVal_PHARMACY'), parseFloat(m.pharmacy_charges), true);
        animateValue(document.getElementById('fsVal_OT'), parseFloat(m.ot_charges), true);
        animateValue(document.getElementById('fsVal_PROCEDURE'), parseFloat(m.procedure_charges), true);
        animateValue(document.getElementById('fsVal_CONSUMABLE'), parseFloat(m.consumable_charges), true);
        animateValue(document.getElementById('fsVal_OTHER'), parseFloat(m.other_charges), true);

        // Dim zero values
        ['ROOM_RENT','DOCTOR_VISIT','LAB','RADIOLOGY','PHARMACY','OT','PROCEDURE','CONSUMABLE','OTHER'].forEach(type => {
            const row = document.getElementById(`fsCat_${type}`);
            if (row) {
                const val = parseFloat(m[type === 'ROOM_RENT' ? 'room_charges' : type.toLowerCase() + '_charges'] || m[type === 'DOCTOR_VISIT' ? 'doctor_charges' : '']);
                // Note: The mapping needs to exactly match the DB column names from IpdBillingMaster.php
                let colName = 'other_charges';
                if(type === 'ROOM_RENT') colName = 'room_charges';
                if(type === 'DOCTOR_VISIT') colName = 'doctor_charges';
                if(type === 'LAB') colName = 'lab_charges';
                if(type === 'RADIOLOGY') colName = 'radiology_charges';
                if(type === 'PHARMACY') colName = 'pharmacy_charges';
                if(type === 'OT') colName = 'ot_charges';
                if(type === 'PROCEDURE') colName = 'procedure_charges';
                if(type === 'CONSUMABLE') colName = 'consumable_charges';
                if(row.dataset.col) colName = row.dataset.col;

                if (parseFloat(m[colName]) === 0) row.classList.add('zero');
                else row.classList.remove('zero');
            }
        });

        animateValue(document.getElementById('fsSubtotal'), parseFloat(m.subtotal), true);
        
        const disc = parseFloat(m.discount_amount);
        document.getElementById('fsDiscount').textContent = `-₹${disc.toLocaleString('en-IN', {minimumFractionDigits:2, maximumFractionDigits:2})}`;
        if(disc === 0) document.getElementById('fsDiscount').closest('.fs-row').style.opacity = '0.5';
        else document.getElementById('fsDiscount').closest('.fs-row').style.opacity = '1';

        animateValue(document.getElementById('fsGrandTotal'), parseFloat(m.grand_total), true);
        animateValue(document.getElementById('qsGrandTotal'), parseFloat(m.grand_total), true);

        // Insurance Block
        const insBlock = document.getElementById('fsInsuranceBlock');
        if (m.bill_type === 'INSURANCE' || m.bill_type === 'CORPORATE') {
            insBlock.style.display = 'block';
            animateValue(document.getElementById('fsInsApproved'), parseFloat(m.insurance_approved_amount), true);
            animateValue(document.getElementById('fsInsReceived'), parseFloat(m.insurance_received_amount), true);
            animateValue(document.getElementById('fsPatientPayable'), parseFloat(m.patient_payable), true);
            document.getElementById('btnInsReceipt').style.display = 'inline-flex';
        } else {
            insBlock.style.display = 'none';
            document.getElementById('btnInsReceipt').style.display = 'none';
        }

        animateValue(document.getElementById('fsAdvance'), parseFloat(m.advance_amount), true);
        animateValue(document.getElementById('fsAmountPaid'), parseFloat(m.amount_paid), true);
        animateValue(document.getElementById('qsAmountPaid'), parseFloat(m.amount_paid), true);

        const balBox = document.getElementById('fsBalanceBox');
        animateValue(document.getElementById('fsBalanceDue'), bal, true);
        animateValue(document.getElementById('qsBalanceDue'), bal, true);
        
        balBox.className = 'fs-balance-box ' + (bal <= 0 ? 'is-paid' : (parseFloat(m.amount_paid) > 0 ? 'is-partial' : ''));

        document.getElementById('fsUpdated').textContent = 'Live · ' + new Date().toLocaleTimeString('en-US', {hour:'2-digit', minute:'2-digit', second:'2-digit'});
        
        // Disable actions if finalized/cancelled
        const isLocked = m.billing_status === 'FINALIZED' || m.billing_status === 'CANCELLED';
        document.getElementById('btnAddCharge').disabled = isLocked;
        document.querySelector('.btn-room-rent').disabled = isLocked;
        document.querySelector('.fs-btn-discount').disabled = isLocked;
    }

    // ─────────────────────────────────────────────────────────────
    // LOAD LISTS
    // ─────────────────────────────────────────────────────────────
    async function loadItems(type = '') {
        if (!currentBillId) return;
        try {
            const res = await fetch(`${API_URL}ipd-billing-items?bill_id=${currentBillId}&charge_type=${type}`);
            const json = await res.json();
            
            if (json.success) {
                renderItemsTable(json.data.items);
                document.getElementById('itemCountBadge').textContent = json.data.count;
                if (!type) document.getElementById('qsItemCount').textContent = json.data.count;
            }
        } catch (e) {
            console.error('Error loading items', e);
        }
    }

    function renderItemsTable(items) {
        const tbody = document.getElementById('itemsTableBody');
        if (!items || items.length === 0) {
            tbody.innerHTML = `
                <tr class="empty-row">
                    <td colspan="9">
                        <div class="table-empty-state">
                            <i class="fas fa-clipboard-list"></i>
                            <p>No charges found</p>
                            <small>Click "Add Charge" or "Room Rent" to add items</small>
                        </div>
                    </td>
                </tr>`;
            return;
        }

        let html = '';
        items.forEach((item, index) => {
            const isCancelled = item.status === 'CANCELLED';
            const dateStr = new Date(item.charge_date).toLocaleDateString('en-GB', {day:'numeric', month:'short'});
            const qty = parseFloat(item.quantity).toString();
            const rate = parseFloat(item.unit_price).toLocaleString('en-IN', {minimumFractionDigits:2});
            const total = parseFloat(item.total_amount).toLocaleString('en-IN', {minimumFractionDigits:2});
            
            let badgeClass = 'badge-OTHER';
            let icon = 'fa-ellipsis-h';
            let catName = item.charge_type.replace('_', ' ');
            
            if (item.charge_type === 'ROOM_RENT')    { badgeClass = 'badge-ROOM_RENT'; icon = 'fa-bed'; }
            if (item.charge_type === 'DOCTOR_VISIT') { badgeClass = 'badge-DOCTOR_VISIT'; icon = 'fa-user-md'; }
            if (item.charge_type === 'LAB')          { badgeClass = 'badge-LAB'; icon = 'fa-flask'; }
            if (item.charge_type === 'RADIOLOGY')    { badgeClass = 'badge-RADIOLOGY'; icon = 'fa-x-ray'; }
            if (item.charge_type === 'PHARMACY')     { badgeClass = 'badge-PHARMACY'; icon = 'fa-pills'; }
            if (item.charge_type === 'OT')           { badgeClass = 'badge-OT'; icon = 'fa-syringe'; }
            if (item.charge_type === 'PROCEDURE')    { badgeClass = 'badge-PROCEDURE'; icon = 'fa-stethoscope'; }

            let subDesc = '';
            if (item.charge_type === 'ROOM_RENT') {
                const bedR = parseFloat(item.bed_rent||0).toLocaleString('en-IN');
                const nurR = parseFloat(item.nursing_charge||0).toLocaleString('en-IN');
                const drR = parseFloat(item.duty_dr_charge||0).toLocaleString('en-IN');
                subDesc = `<div class="item-sub">Bed:₹${bedR} + Nurse:₹${nurR} + DutyDr:₹${drR}</div>`;
            }

            const canCancel = !isCancelled && currentMaster && currentMaster.billing_status !== 'FINALIZED' && currentMaster.billing_status !== 'CANCELLED';
            const sourceIcon = item.source !== 'MANUAL' ? `<i class="fas fa-link" title="Source: ${item.source}" style="color:var(--blue);margin-left:4px;"></i>` : '';

            html += `
                <tr class="${isCancelled ? 'cancelled-row' : ''}">
                    <td>${index + 1}</td>
                    <td>${dateStr}</td>
                    <td><div class="charge-type-badge ${badgeClass}"><i class="fas ${icon}"></i> ${catName}</div></td>
                    <td>
                        <div class="item-desc">${item.description} ${sourceIcon}</div>
                        ${subDesc}
                    </td>
                    <td class="tbl-num">${qty}</td>
                    <td class="tbl-num">${rate}</td>
                    <td class="tbl-amt">₹${total}</td>
                    <td>
                        <div class="item-status-badge status-${item.status}">${item.status}</div>
                    </td>
                    <td>
                        ${canCancel ? `<button class="btn-tbl-cancel" onclick="billing.openCancelChargeModal(${item.item_id}, '${item.charge_type}', '${item.description}', '${dateStr}', '${total}')" title="Cancel Charge"><i class="fas fa-times"></i></button>` : ''}
                    </td>
                </tr>
            `;
        });
        tbody.innerHTML = html;
    }

    async function loadPayments() {
        if (!currentBillId) return;
        try {
            const res = await fetch(`${API_URL}ipd-payment?bill_id=${currentBillId}`);
            const json = await res.json();
            
            if (json.success) {
                renderPaymentsTable(json.data.payments);
                document.getElementById('payCountBadge').textContent = json.data.count;
            }
        } catch (e) {
            console.error('Error loading payments', e);
        }
    }

    function renderPaymentsTable(payments) {
        const tbody = document.getElementById('paymentsTableBody');
        if (!payments || payments.length === 0) {
            tbody.innerHTML = `
                <tr class="empty-row">
                    <td colspan="7">
                        <div class="table-empty-state">
                            <i class="fas fa-wallet"></i>
                            <p>No payments recorded yet</p>
                            <small>Record an advance or partial payment</small>
                        </div>
                    </td>
                </tr>`;
            return;
        }

        let html = '';
        payments.forEach((pay, index) => {
            const dateStr = new Date(pay.payment_date).toLocaleDateString('en-GB', {day:'numeric', month:'short'});
            const amount = parseFloat(pay.amount).toLocaleString('en-IN', {minimumFractionDigits:2});
            
            let modeIcon = 'fa-money-bill-wave';
            if (pay.payment_mode === 'UPI') modeIcon = 'fa-mobile-alt';
            if (pay.payment_mode === 'CARD') modeIcon = 'fa-credit-card';
            if (pay.payment_mode === 'BANK' || pay.payment_mode === 'CHEQUE') modeIcon = 'fa-university';
            if (pay.payment_mode === 'INSURANCE') modeIcon = 'fa-shield-alt';

            const isRefund = pay.payment_type === 'REFUND';
            
            html += `
                <tr style="${pay.is_insurance == 1 ? 'background:var(--blue-light);' : ''}">
                    <td>${index + 1}</td>
                    <td>${dateStr}</td>
                    <td><div class="pay-type-badge pay-${pay.payment_type}">${pay.payment_type}</div></td>
                    <td><i class="fas ${modeIcon} pay-mode-icon"></i> ${pay.payment_mode}</td>
                    <td class="tbl-amt" style="${isRefund ? 'color:var(--red);' : ''}">${isRefund ? '-' : ''}₹${amount}</td>
                    <td>${pay.reference_no || '—'}</td>
                    <td><div class="verified-chip v-${pay.verified_status}">${pay.verified_status === 'VERIFIED' ? '✅' : '⏳'} ${pay.verified_status}</div></td>
                </tr>
            `;
        });
        tbody.innerHTML = html;
    }

    async function loadDashboardStats() {
        try {
            const res = await fetch(`${API_URL}ipd-billing-master?action=stats`);
            const json = await res.json();
            if (json.success && json.data) {
                document.getElementById('estTotalBills').textContent = json.data.total_bills || 0;
                document.getElementById('estTotalCollected').textContent = '₹' + parseFloat(json.data.total_collected || 0).toLocaleString('en-IN');
                document.getElementById('estPending').textContent = '₹' + parseFloat(json.data.total_pending || 0).toLocaleString('en-IN');
            }
        } catch (e) { console.error(e); }
    }

    // ─────────────────────────────────────────────────────────────
    // TABS & MENUS
    // ─────────────────────────────────────────────────────────────
    window.filterItems = function(btn, type) {
        document.querySelectorAll('.cat-tab').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        loadItems(type);
    };

    window.toggleChargeMenu = function() {
        if(currentMaster && (currentMaster.billing_status === 'FINALIZED' || currentMaster.billing_status === 'CANCELLED')) {
            showToast('Cannot add charges to finalized/cancelled bill', 'error');
            return;
        }
        const menu = document.getElementById('chargeMenu');
        const arrow = document.getElementById('chargeArrow');
        if (menu.classList.contains('open')) {
            menu.classList.remove('open');
            arrow.classList.remove('open');
        } else {
            menu.classList.add('open');
            arrow.classList.add('open');
        }
    };

    window.closeChargeMenu = function() {
        document.getElementById('chargeMenu').classList.remove('open');
        document.getElementById('chargeArrow').classList.remove('open');
    };

    function initCloseClick() {
        document.addEventListener('click', function(e) {
            if (!e.target.closest('.add-charge-wrap')) {
                closeChargeMenu();
            }
        });
    }

    // ─────────────────────────────────────────────────────────────
    // MODALS: OPEN / CLOSE
    // ─────────────────────────────────────────────────────────────
    window.openModal = function(id) {
        const el = document.getElementById(id);
        if(el) {
            el.classList.add('open');
            // focus first input
            const firstInput = el.querySelector('input:not([type="hidden"]), select, textarea');
            if (firstInput) setTimeout(() => firstInput.focus(), 100);
        }
    };
    
    window.closeModal = function(id) {
        const el = document.getElementById(id);
        if(el) el.classList.remove('open');
    };

    // ── 1. ADD CHARGE ──
    let addChargeForce = false;
    window.openAddChargeModal = function(type) {
        document.getElementById('chargeType').value = type;
        document.getElementById('chargeDate').value = new Date().toISOString().split('T')[0];
        document.getElementById('chargeDept').value = '';
        document.getElementById('chargeDesc').value = '';
        document.getElementById('chargeQty').value = '1';
        document.getElementById('chargeUnitPrice').value = '';
        document.getElementById('chargeDiscount').value = '0';
        document.getElementById('chargeNotes').value = '';
        document.getElementById('chargeDupWarning').style.display = 'none';
        addChargeForce = false;
        
        onChargeTypeChange();
        calcChargeTotal();
        openModal('modalAddCharge');
    };

    window.onChargeTypeChange = function() {
        const type = document.getElementById('chargeType').value;
        if (type === 'ROOM_RENT') {
            closeModal('modalAddCharge');
            openRoomRentModal();
        }
        // Could implement smart auto-suggest placeholder changes based on type
    };

    window.calcChargeTotal = function() {
        const qty = parseFloat(document.getElementById('chargeQty').value) || 0;
        const price = parseFloat(document.getElementById('chargeUnitPrice').value) || 0;
        const disc = parseFloat(document.getElementById('chargeDiscount').value) || 0;
        const total = (qty * price) - disc;
        document.getElementById('chargeTotalPreview').textContent = `₹ ${Math.max(0, total).toLocaleString('en-IN', {minimumFractionDigits:2})}`;
    };

    window.saveCharge = async function() {
        const btn = document.getElementById('btnSaveCharge');
        
        const data = {
            action: 'add',
            bill_id: currentBillId,
            admission_id: currentAdmissionId,
            patient_id: currentPatientId,
            charge_date: document.getElementById('chargeDate').value,
            charge_type: document.getElementById('chargeType').value,
            department: document.getElementById('chargeDept').value,
            description: document.getElementById('chargeDesc').value,
            quantity: document.getElementById('chargeQty').value,
            unit_price: document.getElementById('chargeUnitPrice').value,
            discount_amt: document.getElementById('chargeDiscount').value,
            reference_id: document.getElementById('chargeNotes').value,
            force: addChargeForce
        };

        if(!data.charge_type || !data.description || !data.unit_price) {
            showToast('Please fill all required fields', 'warning');
            return;
        }

        btn.classList.add('loading');
        try {
            const res = await fetch(`${API_URL}ipd-billing-items`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            });
            const json = await res.json();
            
            if (json.success) {
                showToast('Charge added successfully', 'success');
                closeModal('modalAddCharge');
                
                // Update UI state
                currentMaster = json.data.financial;
                updateWorkspaceUI();
                
                // Refresh list if viewing all or same category
                const activeTab = document.querySelector('.cat-tab.active').dataset.type;
                if (!activeTab || activeTab === data.charge_type) {
                    loadItems(activeTab);
                }
            } else if (res.status === 409) { // Duplicate warning
                const warn = document.getElementById('chargeDupWarning');
                document.getElementById('chargeDupMsg').textContent = json.message;
                warn.style.display = 'flex';
            } else {
                showToast(json.message, 'error');
            }
        } catch (e) {
            console.error(e);
            showToast('Error saving charge', 'error');
        } finally {
            btn.classList.remove('loading');
        }
    };

    window.forceAddCharge = function() {
        addChargeForce = true;
        document.getElementById('chargeDupWarning').style.display = 'none';
        saveCharge();
    };

    // ── 2. ROOM RENT GENERATOR ──
    window.openRoomRentModal = function() {
        if (!currentBedInfo) {
            showToast('Bed information not found', 'error');
            return;
        }
        
        document.getElementById('rrBedName').textContent = `${currentBedInfo.ward} · ${currentBedInfo.room} · Bed ${currentBedInfo.bed}`;
        document.getElementById('rrBedRate').textContent = `₹${parseFloat(currentBedInfo.total_per_day).toLocaleString('en-IN')}/day`;
        
        const b = parseFloat(currentBedInfo.bed_rent).toLocaleString('en-IN');
        const n = parseFloat(currentBedInfo.nursing).toLocaleString('en-IN');
        const d = parseFloat(currentBedInfo.duty_dr).toLocaleString('en-IN');
        document.getElementById('rrRateBreakdown').innerHTML = `<span>Bed: ₹${b}</span><span>Nurse: ₹${n}</span><span>Duty Dr: ₹${d}</span>`;

        // Default dates: From = admission date or last room rent date, To = today
        document.getElementById('rrFromDate').value = currentMaster.admission_date;
        document.getElementById('rrToDate').value = new Date().toISOString().split('T')[0];
        
        document.getElementById('rrPreviewSummary').style.display = 'none';
        document.getElementById('btnConfirmRoomRent').disabled = true;
        
        loadRoomRentPreview();
        openModal('modalRoomRent');
    };

    let rrPreviewTimeout;
    window.loadRoomRentPreview = function() {
        clearTimeout(rrPreviewTimeout);
        document.getElementById('rrPreview').innerHTML = `<div class="rr-preview-loading"><i class="fas fa-spinner fa-spin"></i> Generating preview...</div>`;
        document.getElementById('rrPreviewSummary').style.display = 'none';
        document.getElementById('btnConfirmRoomRent').disabled = true;
        
        rrPreviewTimeout = setTimeout(async () => {
            const f = document.getElementById('rrFromDate').value;
            const t = document.getElementById('rrToDate').value;
            if(!f || !t || f > t) {
                document.getElementById('rrPreview').innerHTML = `<div class="rr-preview-loading" style="color:var(--red);">Invalid date range</div>`;
                return;
            }

            try {
                const res = await fetch(`${API_URL}ipd-billing-items?action=room_rent_preview&bill_id=${currentBillId}&admission_id=${currentAdmissionId}&from_date=${f}&to_date=${t}`);
                const json = await res.json();
                
                if (json.success) {
                    let html = `<table class="rr-preview-table">
                        <thead><tr><th>Date</th><th>Bed Rent</th><th>Nursing</th><th>Duty Dr</th><th>Total</th><th>Status</th></tr></thead>
                        <tbody>`;
                    
                    json.data.rows.forEach(r => {
                        const trClass = r.already_exists ? 'skip-row' : 'new-row';
                        const status = r.already_exists ? '⚠️ ALREADY CHARGED' : '✅ NEW';
                        html += `<tr class="${trClass}">
                            <td>${r.display_date}</td>
                            <td class="tbl-num">${r.bed_rent}</td>
                            <td class="tbl-num">${r.nursing}</td>
                            <td class="tbl-num">${r.duty_dr}</td>
                            <td class="tbl-num" style="font-weight:700;">${r.total}</td>
                            <td style="font-weight:600;">${status}</td>
                        </tr>`;
                    });
                    html += `</tbody></table>`;
                    
                    document.getElementById('rrPreview').innerHTML = html;
                    
                    document.getElementById('rrNewCount').textContent = json.data.new_count;
                    document.getElementById('rrSkipCount').textContent = json.data.skip_count;
                    document.getElementById('rrNewTotal').textContent = '₹' + json.data.new_total.toLocaleString('en-IN');
                    document.getElementById('rrPreviewSummary').style.display = 'flex';
                    
                    if (json.data.new_count > 0) {
                        const btn = document.getElementById('btnConfirmRoomRent');
                        btn.disabled = false;
                        document.getElementById('rrConfirmBtnLabel').textContent = `Generate ${json.data.new_count} Days`;
                    }
                }
            } catch (e) {
                console.error(e);
            }
        }, 300);
    };

    window.confirmRoomRent = async function() {
        const btn = document.getElementById('btnConfirmRoomRent');
        btn.classList.add('loading');
        
        try {
            const res = await fetch(`${API_URL}ipd-billing-items`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    action: 'room_rent',
                    bill_id: currentBillId,
                    admission_id: currentAdmissionId,
                    patient_id: currentPatientId,
                    from_date: document.getElementById('rrFromDate').value,
                    to_date: document.getElementById('rrToDate').value
                })
            });
            const json = await res.json();
            
            if (json.success) {
                showToast(`Generated ${json.data.added} days of room rent`, 'success');
                closeModal('modalRoomRent');
                
                currentMaster = json.data.financial;
                updateWorkspaceUI();
                
                const activeTab = document.querySelector('.cat-tab.active').dataset.type;
                if (!activeTab || activeTab === 'ROOM_RENT') {
                    loadItems(activeTab);
                }
            } else {
                showToast(json.message, 'error');
            }
        } catch (e) {
            showToast('Error generating room rent', 'error');
        } finally {
            btn.classList.remove('loading');
        }
    };

    // ── 3. PAYMENT MODAL ──
    let currentPayMode = 'CASH';
    let currentPayType = 'PARTIAL';
    
    window.openPaymentModal = function(suggestedType = 'PARTIAL') {
        if (!currentMaster) return;
        
        document.getElementById('payDate').value = new Date().toISOString().split('T')[0];
        document.getElementById('payAmount').value = '';
        document.getElementById('payRef').value = '';
        document.getElementById('payRemarks').value = '';
        
        const bal = parseFloat(currentMaster.balance_due);
        document.getElementById('payBalanceVal').textContent = `₹${bal.toLocaleString('en-IN', {minimumFractionDigits:2})}`;
        
        // Smart Type Suggestion
        if(parseFloat(currentMaster.amount_paid) === 0) suggestedType = 'ADVANCE';
        
        document.querySelectorAll('.pay-type-btn').forEach(b => {
            b.classList.remove('active');
            if(b.dataset.type === suggestedType) b.classList.add('active');
        });
        currentPayType = suggestedType;
        
        document.querySelectorAll('.pay-mode-btn').forEach(b => {
            b.classList.remove('active');
            if(b.dataset.mode === 'CASH') b.classList.add('active');
        });
        currentPayMode = 'CASH';
        
        togglePaymentFields();
        updatePayPreview();
        openModal('modalPayment');
    };

    window.selectPayType = function(btn) {
        document.querySelectorAll('.pay-type-btn').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        currentPayType = btn.dataset.type;
        togglePaymentFields();
    };

    window.selectPayMode = function(btn) {
        document.querySelectorAll('.pay-mode-btn').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        currentPayMode = btn.dataset.mode;
        togglePaymentFields();
    };

    function togglePaymentFields() {
        const refGrp = document.getElementById('payRefGroup');
        const refInp = document.getElementById('payRef');
        
        if (currentPayMode === 'CASH') {
            refGrp.style.display = 'none';
        } else {
            refGrp.style.display = 'block';
            if(currentPayMode === 'UPI') refInp.placeholder = 'UPI Txn ID (e.g. PhonePe)';
            if(currentPayMode === 'CARD') refInp.placeholder = 'Card Auth Code / Last 4 digits';
            if(currentPayMode === 'BANK') refInp.placeholder = 'NEFT/RTGS UTR';
            if(currentPayMode === 'CHEQUE') refInp.placeholder = 'Cheque No. & Bank Name';
        }

        const refExtra = document.getElementById('refundExtraFields');
        if (currentPayType === 'REFUND') {
            refExtra.style.display = 'block';
        } else {
            refExtra.style.display = 'none';
        }
    }

    window.fillFullAmount = function() {
        if (!currentMaster) return;
        document.getElementById('payAmount').value = currentMaster.balance_due;
        if(currentPayType === 'PARTIAL') {
            document.querySelectorAll('.pay-type-btn').forEach(b => {
                b.classList.remove('active');
                if(b.dataset.type === 'FINAL') { b.classList.add('active'); currentPayType = 'FINAL'; }
            });
        }
        updatePayPreview();
    };

    window.updatePayPreview = function() {
        const amt = parseFloat(document.getElementById('payAmount').value) || 0;
        const bal = parseFloat(currentMaster.balance_due);
        const preview = document.getElementById('payAfterVal');
        
        if (currentPayType === 'REFUND') {
            preview.textContent = `₹${(bal + amt).toLocaleString('en-IN', {minimumFractionDigits:2})}`;
            preview.style.color = 'var(--red)';
        } else {
            const newBal = bal - amt;
            preview.textContent = `₹${Math.max(0, newBal).toLocaleString('en-IN', {minimumFractionDigits:2})}`;
            if (newBal < 0) {
                preview.textContent += ` (Overpayment: ₹${Math.abs(newBal).toFixed(2)})`;
                preview.style.color = 'var(--amber)';
            } else if (newBal === 0) {
                preview.textContent += ' ✅';
                preview.style.color = 'var(--green)';
            } else {
                preview.style.color = 'var(--navy)';
            }
        }
    };

    window.savePayment = async function() {
        const amt = parseFloat(document.getElementById('payAmount').value) || 0;
        if (amt <= 0) { showToast('Enter valid amount', 'warning'); return; }
        
        if (currentPayMode !== 'CASH' && !document.getElementById('payRef').value.trim()) {
            showToast('Reference No. is required for non-cash modes', 'warning'); return;
        }

        let refundReason = null;
        let approvedBy = null;
        if (currentPayType === 'REFUND') {
            refundReason = document.getElementById('refundReason').value.trim();
            approvedBy = document.getElementById('refundApprovedBy').value.trim();
            if (!refundReason || !approvedBy) {
                showToast('Refund reason and approval auth required', 'warning'); return;
            }
        }

        const btn = document.getElementById('btnSavePayment');
        btn.classList.add('loading');
        
        try {
            const action = currentPayType === 'REFUND' ? 'refund' : 'pay';
            const res = await fetch(`${API_URL}ipd-payment`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    action: action,
                    bill_id: currentBillId,
                    admission_id: currentAdmissionId,
                    patient_id: currentPatientId,
                    payment_date: document.getElementById('payDate').value,
                    payment_type: currentPayType,
                    payment_mode: currentPayMode,
                    amount: amt,
                    reference_no: document.getElementById('payRef').value,
                    remarks: document.getElementById('payRemarks').value,
                    refund_reason: refundReason,
                    approved_by: approvedBy
                })
            });
            const json = await res.json();
            
            if (json.success) {
                showToast('Payment recorded successfully', 'success');
                closeModal('modalPayment');
                
                currentMaster = json.data.financial;
                updateWorkspaceUI();
                loadPayments();
            } else {
                showToast(json.message, 'error');
            }
        } catch (e) {
            showToast('Error saving payment', 'error');
        } finally {
            btn.classList.remove('loading');
        }
    };

    // ── 4. INSURANCE RECEIPT ──
    window.openInsuranceReceiptModal = async function() {
        if (!currentMaster) return;
        
        try {
            const res = await fetch(`${API_URL}ipd-insurance?bill_id=${currentBillId}`);
            const json = await res.json();
            const ins = json.data;
            
            if(!ins) { showToast('No insurance data found', 'warning'); return; }
            
            document.getElementById('insRcptCompany').textContent = ins.company_name || '—';
            document.getElementById('insRcptApproved').textContent = `₹${parseFloat(ins.approved_amount).toLocaleString('en-IN')}`;
            document.getElementById('insRcptReceived').textContent = `₹${parseFloat(ins.received_amount).toLocaleString('en-IN')}`;
            document.getElementById('insRcptPending').textContent = `₹${parseFloat(ins.pending_amount).toLocaleString('en-IN')}`;
            
            // store for autofill
            document.getElementById('insRcptAmount').dataset.pending = ins.pending_amount;
            
            document.getElementById('insRcptDate').value = new Date().toISOString().split('T')[0];
            document.getElementById('insRcptAmount').value = '';
            document.getElementById('insRcptRef').value = '';
            document.getElementById('insRcptRemarks').value = '';
            
            openModal('modalInsReceipt');
        } catch (e) {
            console.error(e);
        }
    };

    window.fillInsFullAmount = function() {
        const amt = document.getElementById('insRcptAmount');
        amt.value = amt.dataset.pending || 0;
    };

    window.saveInsuranceReceipt = async function() {
        const amt = parseFloat(document.getElementById('insRcptAmount').value) || 0;
        const ref = document.getElementById('insRcptRef').value.trim();
        if(amt <= 0) { showToast('Enter valid amount', 'warning'); return; }
        if(!ref) { showToast('Settlement reference required', 'warning'); return; }
        
        try {
            const res = await fetch(`${API_URL}ipd-payment`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    action: 'insurance_receipt',
                    bill_id: currentBillId,
                    admission_id: currentAdmissionId,
                    patient_id: currentPatientId,
                    payment_date: document.getElementById('insRcptDate').value,
                    amount: amt,
                    reference_no: ref,
                    remarks: document.getElementById('insRcptRemarks').value
                })
            });
            const json = await res.json();
            
            if (json.success) {
                showToast('Insurance receipt saved', 'success');
                closeModal('modalInsReceipt');
                currentMaster = json.data.financial;
                updateWorkspaceUI();
                loadPayments();
            } else {
                showToast(json.message, 'error');
            }
        } catch (e) {
            showToast('Error saving receipt', 'error');
        }
    };

    // ── 5. APPLY DISCOUNT ──
    window.openDiscountModal = function() {
        if (!currentMaster) return;
        if (USER_ROLE === 'Receptionist') {
            showToast('Only admins or finance managers can modify discounts', 'warning');
            return;
        }
        
        const sub = parseFloat(currentMaster.subtotal);
        document.getElementById('discSubtotalDisplay').textContent = `₹${sub.toLocaleString('en-IN', {minimumFractionDigits:2})}`;
        
        document.getElementById('discAmount').value = parseFloat(currentMaster.discount_amount) || '';
        document.getElementById('discPct').value = parseFloat(currentMaster.discount_percentage) || '';
        document.getElementById('discReason').value = currentMaster.notes || '';
        
        updateDiscountPreview();
        openModal('modalDiscount');
    };

    window.calcDiscountPct = function() {
        const sub = parseFloat(currentMaster.subtotal) || 0;
        const amt = parseFloat(document.getElementById('discAmount').value) || 0;
        if (sub > 0 && amt <= sub) {
            document.getElementById('discPct').value = ((amt / sub) * 100).toFixed(2);
        } else if (amt > sub) {
            document.getElementById('discAmount').value = sub;
            document.getElementById('discPct').value = 100;
        }
        updateDiscountPreview();
    };

    window.calcDiscountAmt = function() {
        const sub = parseFloat(currentMaster.subtotal) || 0;
        const pct = parseFloat(document.getElementById('discPct').value) || 0;
        if (pct >= 0 && pct <= 100) {
            document.getElementById('discAmount').value = (sub * pct / 100).toFixed(2);
        } else if (pct > 100) {
            document.getElementById('discPct').value = 100;
            document.getElementById('discAmount').value = sub;
        }
        updateDiscountPreview();
    };

    function updateDiscountPreview() {
        const sub = parseFloat(currentMaster.subtotal) || 0;
        const amt = parseFloat(document.getElementById('discAmount').value) || 0;
        const total = Math.max(0, sub - amt);
        
        document.getElementById('dapSubtotal').textContent = `₹${sub.toLocaleString('en-IN', {minimumFractionDigits:2})}`;
        document.getElementById('dapDiscount').textContent = `-₹${amt.toLocaleString('en-IN', {minimumFractionDigits:2})}`;
        document.getElementById('dapGrandTotal').textContent = `₹${total.toLocaleString('en-IN', {minimumFractionDigits:2})}`;
    }

    window.saveDiscount = async function() {
        const amt = parseFloat(document.getElementById('discAmount').value) || 0;
        const pct = parseFloat(document.getElementById('discPct').value) || 0;
        const reason = document.getElementById('discReason').value;
        
        if (amt > 0 && !reason) {
            showToast('Reason is required when applying discount', 'warning'); return;
        }
        
        try {
            const res = await fetch(`${API_URL}ipd-billing-master`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    action: 'discount',
                    bill_id: currentBillId,
                    discount_amount: amt,
                    discount_percentage: pct,
                    reason: reason
                })
            });
            const json = await res.json();
            
            if (json.success) {
                showToast('Discount applied', 'success');
                closeModal('modalDiscount');
                currentMaster = json.data;
                updateWorkspaceUI();
            } else {
                showToast(json.message, 'error');
            }
        } catch (e) {
            showToast('Error applying discount', 'error');
        }
    };

    // ── 6. CHANGE STATUS ──
    let selectedStatus = 'OPEN';
    window.openStatusModal = function() {
        if (!currentMaster) return;
        
        const current = currentMaster.billing_status;
        document.getElementById('statusCurrentDisplay').textContent = current.replace('_', ' ');
        document.getElementById('statusReason').value = '';
        
        document.querySelectorAll('.status-option-btn').forEach(b => {
            b.classList.remove('selected');
            b.disabled = false;
            
            // Logic to disable backward steps
            const s = b.dataset.status;
            if (current === 'FINALIZED' && s !== 'OPEN' && USER_ROLE !== 'admin' && USER_ROLE !== 'Admin') {
                b.disabled = true; // Only admin can reopen
            }
            if (current === 'CANCELLED') b.disabled = true;
        });
        
        // Auto-select next logical
        let next = 'OPEN';
        if (current === 'OPEN') next = 'UNDER_TREATMENT';
        else if (current === 'UNDER_TREATMENT') next = 'DISCHARGE_PENDING';
        else if (current === 'DISCHARGE_PENDING') next = 'FINALIZED';
        else if (current === 'FINALIZED') next = 'FINALIZED';
        
        selectedStatus = next;
        document.querySelector(`.status-option-btn[data-status="${next}"]`).classList.add('selected');
        
        openModal('modalStatus');
    };

    // Attached via onclick inline in HTML isn't there, so we delegate
    document.getElementById('statusOptions').addEventListener('click', function(e) {
        if (e.target.classList.contains('status-option-btn')) {
            if (e.target.disabled) return;
            document.querySelectorAll('.status-option-btn').forEach(b => b.classList.remove('selected'));
            e.target.classList.add('selected');
            selectedStatus = e.target.dataset.status;
        }
    });

    window.saveStatus = async function() {
        if (selectedStatus === currentMaster.billing_status) {
            closeModal('modalStatus'); return;
        }
        
        if (selectedStatus === 'FINALIZED') {
            const bal = parseFloat(currentMaster.balance_due);
            if (bal > 0) {
                if(!confirm(`Balance of ₹${bal} is still pending. Are you sure you want to finalize?`)) return;
            }
        }
        
        try {
            let dischargeDate = null;
            if (selectedStatus === 'FINALIZED') {
                dischargeDate = new Date().toISOString().split('T')[0];
            }
            
            const res = await fetch(`${API_URL}ipd-billing-master`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    action: 'status',
                    bill_id: currentBillId,
                    billing_status: selectedStatus,
                    discharge_date: dischargeDate,
                    reason: document.getElementById('statusReason').value
                })
            });
            const json = await res.json();
            
            if (json.success) {
                showToast('Status updated to ' + selectedStatus.replace('_', ' '), 'success');
                closeModal('modalStatus');
                // Reload patient fully to get all changes (like dates)
                loadAdmission(currentAdmissionId, currentPatientId);
            } else {
                showToast(json.message, 'error');
            }
        } catch (e) {
            showToast('Error updating status', 'error');
        }
    };

    // ── 7. CANCEL CHARGE ──
    let chargeToCancel = null;
    window.openCancelChargeModal = function(itemId, category, desc, date, amount) {
        chargeToCancel = itemId;
        document.getElementById('cciCategory').textContent = category.replace('_', ' ');
        document.getElementById('cciDesc').textContent = desc;
        document.getElementById('cciDate').textContent = date;
        document.getElementById('cciAmount').textContent = amount;
        
        openModal('modalCancelCharge');
    };

    window.confirmCancelCharge = async function() {
        if (!chargeToCancel) return;
        
        const btn = document.getElementById('btnConfirmCancelCharge');
        btn.classList.add('loading');
        
        try {
            const res = await fetch(`${API_URL}ipd-billing-items`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    action: 'cancel',
                    item_id: chargeToCancel
                })
            });
            const json = await res.json();
            
            if (json.success) {
                showToast('Charge cancelled', 'success');
                closeModal('modalCancelCharge');
                
                currentMaster = json.data.financial;
                updateWorkspaceUI();
                
                const activeTab = document.querySelector('.cat-tab.active').dataset.type;
                loadItems(activeTab);
            } else {
                showToast(json.message, 'error');
            }
        } catch (e) {
            showToast('Error cancelling charge', 'error');
        } finally {
            btn.classList.remove('loading');
        }
    };

    // ── 8. INSURANCE DETAILS ──
    let currentBillType = 'SELF';
    window.openInsuranceModal = async function() {
        if (!currentMaster) return;
        
        currentBillType = currentMaster.bill_type || 'SELF';
        document.querySelectorAll('.bill-type-btn').forEach(b => {
            b.classList.remove('active');
            if(b.dataset.type === currentBillType) b.classList.add('active');
        });
        
        toggleInsFields();
        
        // Reset fields
        document.getElementById('insCompanyName').value = currentMaster.insurance_company_id || '';
        document.getElementById('insTpaName').value = '';
        document.getElementById('insPolicyNo').value = currentMaster.policy_number || '';
        document.getElementById('insClaimNo').value = '';
        document.getElementById('insApprovalNo').value = currentMaster.approval_number || '';
        document.getElementById('insApprovedAmt').value = parseFloat(currentMaster.insurance_approved_amount) || '';
        document.getElementById('insClaimStatus').value = 'PENDING';
        
        // Try fetch existing full record
        try {
            const res = await fetch(`${API_URL}ipd-insurance?bill_id=${currentBillId}`);
            const json = await res.json();
            if (json.success && json.data) {
                const ins = json.data;
                document.getElementById('insCompanyName').value = ins.company_name || '';
                document.getElementById('insTpaName').value = ins.tpa_name || '';
                document.getElementById('insPolicyNo').value = ins.policy_number || '';
                document.getElementById('insClaimNo').value = ins.claim_number || '';
                document.getElementById('insApprovalNo').value = ins.approval_number || '';
                document.getElementById('insApprovedAmt').value = ins.approved_amount || '';
                document.getElementById('insClaimStatus').value = ins.claim_status || 'PENDING';
            }
        } catch(e) {}
        
        calcInsPatientPayable();
        openModal('modalInsurance');
    };

    window.selectBillType = function(btn) {
        document.querySelectorAll('.bill-type-btn').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        currentBillType = btn.dataset.type;
        toggleInsFields();
    };

    function toggleInsFields() {
        const fields = document.getElementById('insFormFields');
        if (currentBillType === 'SELF') {
            fields.style.display = 'none';
        } else {
            fields.style.display = 'block';
        }
    }

    window.calcInsPatientPayable = function() {
        const gt = parseFloat(currentMaster.grand_total) || 0;
        const app = parseFloat(document.getElementById('insApprovedAmt').value) || 0;
        const pp = Math.max(0, gt - app);
        
        document.getElementById('insGtDisplay').textContent = `₹${gt.toLocaleString('en-IN', {minimumFractionDigits:2})}`;
        document.getElementById('insApprDisplay').textContent = `₹${app.toLocaleString('en-IN', {minimumFractionDigits:2})}`;
        document.getElementById('insPpDisplay').textContent = `₹${pp.toLocaleString('en-IN', {minimumFractionDigits:2})}`;
    };

    window.saveInsuranceDetails = async function() {
        if (currentBillType !== 'SELF') {
            if(!document.getElementById('insCompanyName').value.trim()) {
                showToast('Company name is required', 'warning'); return;
            }
        }
        
        try {
            // First save bill type on master
            await fetch(`${API_URL}ipd-billing-master`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    action: 'bill_type',
                    bill_id: currentBillId,
                    bill_type: currentBillType
                })
            });
            
            // If insurance, save details
            if (currentBillType !== 'SELF') {
                const res = await fetch(`${API_URL}ipd-insurance`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        action: 'save',
                        bill_id: currentBillId,
                        admission_id: currentAdmissionId,
                        patient_id: currentPatientId,
                        insurance_type: currentBillType,
                        company_name: document.getElementById('insCompanyName').value,
                        tpa_name: document.getElementById('insTpaName').value,
                        policy_number: document.getElementById('insPolicyNo').value,
                        claim_number: document.getElementById('insClaimNo').value,
                        approval_number: document.getElementById('insApprovalNo').value,
                        approved_amount: document.getElementById('insApprovedAmt').value,
                        claim_status: document.getElementById('insClaimStatus').value
                    })
                });
                const json = await res.json();
                if(json.success) {
                    currentMaster = json.data.financial;
                }
            } else {
                // Self - reload master to get updated bill_type and zeroed insurance values
                const res = await fetch(`${API_URL}ipd-billing-master?bill_id=${currentBillId}`);
                const json = await res.json();
                if(json.success) currentMaster = json.data;
            }
            
            showToast('Insurance details updated', 'success');
            closeModal('modalInsurance');
            updateWorkspaceUI();
            
        } catch (e) {
            showToast('Error saving insurance details', 'error');
        }
    };

    // ─────────────────────────────────────────────────────────────
    // UTILS
    // ─────────────────────────────────────────────────────────────
    function showToast(message, type = 'info') {
        const container = document.getElementById('billingToastContainer');
        const toast = document.createElement('div');
        toast.className = `billing-toast toast-${type}`;
        
        let icon = 'fa-info-circle';
        if(type === 'success') icon = 'fa-check-circle';
        if(type === 'error') icon = 'fa-times-circle';
        if(type === 'warning') icon = 'fa-exclamation-triangle';
        
        toast.innerHTML = `<i class="fas ${icon}"></i> <span>${message}</span>`;
        
        toast.onclick = () => {
            toast.classList.add('hide');
            setTimeout(() => toast.remove(), 300);
        };
        
        container.appendChild(toast);
        
        setTimeout(() => {
            if(toast.parentNode) {
                toast.classList.add('hide');
                setTimeout(() => { if(toast.parentNode) toast.remove(); }, 300);
            }
        }, 4000);
    }

    function animateValue(obj, end, isCurrency = false) {
        if(!obj) return;
        // Parse current value
        let currentText = obj.textContent.replace(/[^0-9.-]+/g,"");
        let start = parseFloat(currentText) || 0;
        if (start === end) return;
        
        obj.classList.add('animating');
        
        const duration = 400; // ms
        let startTimestamp = null;
        
        const step = (timestamp) => {
            if (!startTimestamp) startTimestamp = timestamp;
            const progress = Math.min((timestamp - startTimestamp) / duration, 1);
            // Ease out cubic
            const ease = 1 - Math.pow(1 - progress, 3);
            const current = start + (end - start) * ease;
            
            if(isCurrency) {
                obj.textContent = '₹' + current.toLocaleString('en-IN', {minimumFractionDigits:2, maximumFractionDigits:2});
            } else {
                obj.textContent = Math.round(current);
            }
            
            if (progress < 1) {
                window.requestAnimationFrame(step);
            } else {
                if(isCurrency) {
                    obj.textContent = '₹' + end.toLocaleString('en-IN', {minimumFractionDigits:2, maximumFractionDigits:2});
                }
                obj.classList.remove('animating');
            }
        };
        window.requestAnimationFrame(step);
    }

    function initShortcuts() {
        document.addEventListener('keydown', function(e) {
            // Don't trigger if inside an input/textarea
            if(e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA' || e.target.tagName === 'SELECT') return;
            
            if (!currentBillId) return;
            
            // Prevent default for our shortcuts to avoid double triggering
            if (e.key.toLowerCase() === 'p') { e.preventDefault(); openPaymentModal('PARTIAL'); }
            if (e.key.toLowerCase() === 'c') { e.preventDefault(); toggleChargeMenu(); }
            if (e.key.toLowerCase() === 'r') { e.preventDefault(); openRoomRentModal(); }
            
            // Close modals on Escape
            if (e.key === 'Escape') {
                document.querySelectorAll('.billing-modal-overlay.open').forEach(el => {
                    el.classList.remove('open');
                });
                closeChargeMenu();
            }
        });
    }

    // ── PRINTING ──
    window.printInterim = function() {
        if(!currentBillId) return;
        window.open(`../views/billing/print_interim.php?bill_id=${currentBillId}`, '_blank');
    };
    
    window.printFinal = function() {
        if(!currentBillId) return;
        if(currentMaster.billing_status !== 'FINALIZED') {
            showToast('Final bill can only be printed after status is Finalized', 'warning');
            return;
        }
        window.open(`../views/billing/print_final.php?bill_id=${currentBillId}`, '_blank');
    };
    
    window.printReceipt = function() {
        if(!currentBillId) return;
        window.open(`../views/billing/print_receipt.php?bill_id=${currentBillId}`, '_blank');
    };

    // Export exposed functions
    return {
        init,
        loadAdmission,
        filterItems,
        toggleChargeMenu,
        closeChargeMenu,
        openModal,
        closeModal,
        // Add Charge
        openAddChargeModal,
        onChargeTypeChange,
        calcChargeTotal,
        saveCharge,
        forceAddCharge,
        // Room Rent
        openRoomRentModal,
        loadRoomRentPreview,
        confirmRoomRent,
        // Payment
        openPaymentModal,
        selectPayType,
        selectPayMode,
        fillFullAmount,
        updatePayPreview,
        savePayment,
        // Ins Receipt
        openInsuranceReceiptModal,
        fillInsFullAmount,
        saveInsuranceReceipt,
        // Discount
        openDiscountModal,
        calcDiscountPct,
        calcDiscountAmt,
        saveDiscount,
        // Status
        openStatusModal,
        saveStatus,
        // Cancel Charge
        openCancelChargeModal,
        confirmCancelCharge,
        // Insurance
        openInsuranceModal,
        selectBillType,
        calcInsPatientPayable,
        saveInsuranceDetails,
        // Print
        printInterim,
        printFinal,
        printReceipt
    };

})();

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', billing.init);
