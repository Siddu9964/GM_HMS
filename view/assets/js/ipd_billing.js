/**
 * IPD Billing Terminal — Main JavaScript Application
 * Handles all UI interactions, API calls, and live calculations.
 */

const billing = (function () {
    'use strict';

    // State
    let currentAdmissionId = null;
    let currentBillId = null;
    let currentPatientId = null;
    let currentMaster = null;
    let currentBedInfo = null;
    let phCart = [];
    let tsCart = [];
    let addChargeForce = false;
    window.allAdmittedPatientsList = [];
    window.currentPatientSort = { col: 'admission_date', asc: false };

    let currentInlinePayType = 'PARTIAL';
    let currentInlinePayMode = 'CASH';
    let currentInlineSponsorType = 'INSURANCE';
    let currentModalSponsorType = 'INSURANCE';
    let inlineSponsorSearchDebounce = null;
    let modalSponsorSearchDebounce = null;
    let isSavingInlinePayment = false;
    let isSavingModalPayment = false;

    const API_URL = window.BILLING_API || '/GM_HMS/api/';
    const USER_ROLE = window.USER_ROLE || 'Receptionist';

    // ─────────────────────────────────────────────────────────────
    // INIT
    // ─────────────────────────────────────────────────────────────
    function init() {
        initSearch();
        initShortcuts();
        initCloseClick();
        initInlinePayment();
        initSponsorSearch();
        loadAllAdmittedPatients();

        // Check if admission_id or patient_id passed in sessionStorage or URL
        const urlParams = new URLSearchParams(window.location.search);
        const urlAdm = urlParams.get('admission_id');
        const urlPat = urlParams.get('patient_id');

        const sessionAdm = sessionStorage.getItem('currentAdmissionId');
        const sessionPat = sessionStorage.getItem('currentPatientId');

        const targetAdm = sessionAdm || urlAdm;
        const targetPat = sessionPat || urlPat || '';

        // If URL contained query parameters, clean the URL immediately so variables are not exposed in address bar
        if (urlAdm || urlPat) {
            try {
                window.history.replaceState({}, document.title, window.location.pathname);
            } catch (e) {}
        }

        if (targetAdm) {
            loadAdmission(targetAdm, targetPat);
        }
    }

    // ─────────────────────────────────────────────────────────────
    // SEARCH & LOAD
    // ─────────────────────────────────────────────────────────────
    let searchTimeout;
    function initSearch() {
        const input = document.getElementById('admissionSearchInput');
        if (!input) return;

        input.addEventListener('input', function (e) {
            clearTimeout(searchTimeout);
            const val = e.target.value.trim();
            if (val.length < 2) {
                document.getElementById('admissionSearchDropdown').classList.remove('open');
                return;
            }
            searchTimeout = setTimeout(() => doSearch(val), 300);
        });

        // Close dropdown when clicking outside
        document.addEventListener('click', function (e) {
            if (!e.target.closest('.search-zone-input-wrap')) {
                const dd = document.getElementById('admissionSearchDropdown');
                if (dd) dd.classList.remove('open');
            }
        });
    }

    async function doSearch(query) {
        try {
            const res = await fetch(`${API_URL}ipd-billing-master?action=search_admissions&q=${encodeURIComponent(query)}`);
            const json = await res.json();
            const dd = document.getElementById('admissionSearchDropdown');

            if (!json.success || !json.data.length) {
                dd.innerHTML = `<div class="asd-empty"><i data-lucide="search"></i> No admitted patients found</div>`;
            } else {
                let html = '';
                json.data.forEach(p => {
                    const pName = p.patient_name || [p.first_name, p.last_name].filter(Boolean).join(' ') || 'Patient';
                    const statusClass = p.payment_status === 'Paid' ? 'paid' : (p.payment_status === 'Partial' ? 'partial' : 'pending');
                    html += `
                        <div class="asd-item" onclick="billing.loadAdmission('${p.admission_id}', '${p.patient_id}')">
                            <div class="asd-icon">${pName.charAt(0).toUpperCase()}</div>
                            <div style="flex:1;">
                                <div class="asd-name">${pName} <span style="font-weight:normal; font-size:0.8rem; color:var(--slate);">(${p.age || '-'}/${(p.sex || '-').charAt(0)})</span></div>
                                <div class="asd-meta">${p.admission_id} · Bed: ${p.ward_name || ''} ${p.bed_number || ''}</div>
                            </div>
                            ${p.bill_id ? `<div class="asd-badge ${statusClass}">${p.payment_status}</div>` : `<div class="asd-badge pending">NEW</div>`}
                        </div>
                    `;
                });
                dd.innerHTML = html;
            if(window.lucide) lucide.createIcons();
            }
            dd.classList.add('open');
        } catch (e) {
            console.error('Search error', e);
        }
    }

    async function loadAdmission(admissionId, patientId) {
        const dropdown = document.getElementById('admissionSearchDropdown');
        if (dropdown) dropdown.classList.remove('open');
        const searchInput = document.getElementById('admissionSearchInput');
        if (searchInput) searchInput.value = '';

        currentAdmissionId = admissionId;
        currentPatientId = patientId;

        // Keep sessionStorage updated so reloads keep active patient without URL variables
        if (admissionId) sessionStorage.setItem('currentAdmissionId', admissionId);
        if (patientId) sessionStorage.setItem('currentPatientId', patientId);

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
        const searchZone = document.getElementById('billingSearchZone');
        if (searchZone) searchZone.style.display = 'none';
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
        const pName = m.patient_name || [m.first_name, m.last_name].filter(Boolean).join(' ') || 'Patient';
        document.getElementById('phcAvatar').textContent = pName.substring(0, 2).toUpperCase();
        document.getElementById('phcName').textContent = pName;
        document.getElementById('phcAge').textContent = `${m.age || '-'} / ${m.sex || '-'}`;
        document.getElementById('phcAdmId').textContent = m.admission_id;
        document.getElementById('phcDoctor').textContent = `Dr. ${m.doctor_name || 'N/A'}`;

        document.getElementById('phcBed').textContent = `${m.ward_name} · ${m.bed_number}`;

        const admDateStr = new Date(m.admission_date).toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' });
        let datesStr = admDateStr + ' → ';
        datesStr += m.discharge_date ? new Date(m.discharge_date).toLocaleDateString('en-GB', { day: 'numeric', month: 'short' }) : 'Present';
        document.getElementById('phcDates').textContent = datesStr;
        document.getElementById('phcDays').textContent = `${m.total_days} Days`;

        const bal = parseFloat(m.balance_due);

        const extraInfoEl = document.getElementById('phcExtraInfo');
        let extraParts = [];
        
        if (m.referral_type && m.referral_name) {
            extraParts.push(`<i data-lucide="user-plus" style="width:14px;height:14px;margin-right:4px;"></i> Referral: ${m.referral_type} - ${m.referral_name}`);
        }
        if (m.insurance_company_name || m.tpa_name) {
            let insStr = m.insurance_company_name || '';
            if (m.tpa_name) insStr += (insStr ? ` (TPA: ${m.tpa_name})` : `TPA: ${m.tpa_name}`);
            extraParts.push(`<i data-lucide="shield" style="width:14px;height:14px;margin-right:4px;"></i> Insurance: ${insStr}`);
        }
        
        if (m.sponsor) {
            let spText = m.sponsor;
            if (m.credit_type) {
                spText += ` - ${m.credit_type}`;
            }
            extraParts.push(`<span style="background:#fffbeb; color:#d97706; padding:4px 8px; border-radius:6px; font-weight:600; display:inline-flex; align-items:center; border:1px solid #fde68a;"><i data-lucide="building" style="width:14px;height:14px;margin-right:4px;"></i> Sponsor: ${spText}</span>`);
        }
        
        if (extraParts.length > 0) {
            extraInfoEl.innerHTML = extraParts.join(' <span class="phc-dot" style="margin: 0 8px;">·</span> ');
            extraInfoEl.style.display = 'flex';
            if(window.lucide) lucide.createIcons();
        } else {
            extraInfoEl.style.display = 'none';
        }

        document.getElementById('phcBillNo').textContent = m.bill_id || '—';
        const bStatus = document.getElementById('phcBillingStatus');
        const stStr = m.billing_status || 'DRAFT';
        bStatus.textContent = stStr.replace('_', ' ');
        bStatus.style.color = stStr === 'FINALIZED' ? '#166534' : (stStr === 'CANCELLED' ? '#991b1b' : '#0369a1');

        // Load Multi-Module Discharge Clearance Status
        loadPatientClearanceStatus(m.admission_id, m.patient_id);

        // Toggle Insurance Button
        const btnIns = document.getElementById('btnInsuranceInfo');
        if (m.bill_type === 'SELF') {
            btnIns.innerHTML = '<i data-lucide="plus"></i> Set Insurance';
        if(window.lucide) lucide.createIcons();
        } else {
            btnIns.innerHTML = '<i data-lucide="shield"></i> ' + m.bill_type;
        if(window.lucide) lucide.createIcons();
        }

        // Print buttons
        document.getElementById('btnPrintFinal').disabled = false;

        // Financial Summary Panel
        animateValue(document.getElementById('fsVal_ROOM_RENT'), parseFloat(m.room_charges || 0), true);
        animateValue(document.getElementById('fsVal_DOCTOR_VISIT'), parseFloat(m.doctor_charges || 0), true);
        animateValue(document.getElementById('fsVal_LAB'), parseFloat(m.lab_charges || 0), true);
        animateValue(document.getElementById('fsVal_RADIOLOGY'), parseFloat(m.radiology_charges || 0), true);
        animateValue(document.getElementById('fsVal_PHARMACY'), parseFloat(m.pharmacy_charges || 0), true);
        animateValue(document.getElementById('fsVal_OT'), parseFloat(m.ot_charges || 0), true);
        animateValue(document.getElementById('fsVal_PROCEDURE'), parseFloat(m.procedure_charges || 0), true);
        animateValue(document.getElementById('fsVal_CONSUMABLE'), parseFloat(m.consumable_charges || 0), true);
        animateValue(document.getElementById('fsVal_MISC') || document.getElementById('fsVal_OTHER'), parseFloat(m.other_charges || 0), true);

        // Dim zero values
        ['ROOM_RENT', 'DOCTOR_VISIT', 'LAB', 'RADIOLOGY', 'PHARMACY', 'OT', 'PROCEDURE', 'CONSUMABLE', 'MISC', 'OTHER'].forEach(type => {
            const row = document.getElementById(`fsCat_${type}`);
            if (row) {
                let colName = 'other_charges';
                if (type === 'ROOM_RENT') colName = 'room_charges';
                if (type === 'DOCTOR_VISIT') colName = 'doctor_charges';
                if (type === 'LAB') colName = 'lab_charges';
                if (type === 'RADIOLOGY') colName = 'radiology_charges';
                if (type === 'PHARMACY') colName = 'pharmacy_charges';
                if (type === 'OT') colName = 'ot_charges';
                if (type === 'PROCEDURE') colName = 'procedure_charges';
                if (type === 'CONSUMABLE') colName = 'consumable_charges';
                if (type === 'MISC' || type === 'OTHER') colName = 'other_charges';
                if (row.dataset.col) colName = row.dataset.col;

                if (parseFloat(m[colName] || 0) === 0) row.classList.add('zero');
                else row.classList.remove('zero');
            }
        });

        const disc = parseFloat(m.discount_amount);
        document.getElementById('fsDiscount').textContent = `-₹${disc.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
        if (disc === 0) document.getElementById('fsDiscount').closest('.fs-row').style.opacity = '0.5';
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

        animateValue(document.getElementById('fsAmountPaid'), parseFloat(m.amount_paid), true);
        animateValue(document.getElementById('qsAmountPaid'), parseFloat(m.amount_paid), true);

        const balBox = document.getElementById('fsBalanceBox');
        animateValue(document.getElementById('fsBalanceDue'), bal, true);
        animateValue(document.getElementById('qsBalanceDue'), bal, true);

        balBox.className = 'fs-balance-box ' + (bal <= 0 ? 'is-paid' : (parseFloat(m.amount_paid) > 0 ? 'is-partial' : ''));

        // Disable actions if finalized/cancelled
        const isLocked = m.billing_status === 'FINALIZED' || m.billing_status === 'CANCELLED';
        document.getElementById('btnAddCharge').disabled = isLocked;
        document.querySelector('.btn-room-rent').disabled = isLocked;
        document.querySelector('.fs-btn-discount').disabled = isLocked;

        // Update Inline Payment Form
        updateInlinePaymentUI();
    }

    // ─────────────────────────────────────────────────────────────
    // LOAD LISTS
    // ─────────────────────────────────────────────────────────────
    async function loadItems(type = '') {
        if (!currentBillId) return;
        try {
            const res = await fetch(`${API_URL}ipd-billing-items?bill_id=${currentBillId}&charge_type=${type}&_t=${Date.now()}`, { cache: 'no-store' });
            const json = await res.json();

            if (json.success) {
                renderItemsTable(json.data.items);
                const activeCount = json.data.items.filter(it => it.status !== 'CANCELLED').length;
                document.getElementById('itemCountBadge').textContent = activeCount;
                if (!type) document.getElementById('qsItemCount').textContent = activeCount;
            }
        } catch (e) {
            console.error('Error loading items', e);
        }
    }

    function toggleGroup(groupId) {
        const safeId = String(groupId || '').replace(/[^a-zA-Z0-9_-]/g, '_');
        const rows = document.querySelectorAll(`tr.child-row[data-group-id="${safeId}"]`);
        const icon = document.querySelector(`.group-icon-${safeId}`);
        const hint = document.querySelector(`.group-status-hint-${safeId}`);
        const header = document.querySelector(`tr.group-header[data-group-id="${safeId}"]`);
        
        if (!rows || rows.length === 0) return;

        // Determine hidden state purely from class (most reliable)
        const firstRow = rows[0];
        const isHidden = firstRow.classList.contains('is-hidden');

        rows.forEach(row => {
            if (isHidden) {
                // Remove all display overrides, let CSS class control it
                row.style.removeProperty('display');
                row.classList.remove('is-hidden');
                row.classList.add('is-visible');
            } else {
                row.style.removeProperty('display');
                row.classList.remove('is-visible');
                row.classList.add('is-hidden');
            }
        });

        if (icon) {
            icon.style.transform = isHidden ? 'rotate(90deg)' : 'rotate(0deg)';
        }
        if (hint) {
            hint.innerHTML = isHidden 
                ? `<i class="fas fa-chevron-up" style="font-size: 9px; margin-right: 4px;"></i> Hide Details` 
                : `<i class="fas fa-chevron-down" style="font-size: 9px; margin-right: 4px;"></i> View Details`;
            hint.style.background = isHidden ? '#1f6b4a' : 'rgba(31,107,74,0.12)';
            hint.style.color = isHidden ? '#f3efe6' : '#1f6b4a';
        }
        if (header) {
            header.style.background = isHidden ? '#e2eee6' : '#f3efe6';
        }
    }
    window.toggleGroup = toggleGroup;

    function expandAllGroups() {
        document.querySelectorAll('tr.group-header').forEach(hdr => {
            const gid = hdr.dataset.groupId;
            const rows = document.querySelectorAll(`tr.child-row[data-group-id="${gid}"]`);
            const icon = document.querySelector(`.group-icon-${gid}`);
            const hint = document.querySelector(`.group-status-hint-${gid}`);
            rows.forEach(r => {
                r.style.removeProperty('display');
                r.classList.remove('is-hidden');
                r.classList.add('is-visible');
            });
            if (icon) icon.style.transform = 'rotate(90deg)';
            if (hint) {
                hint.innerHTML = `<i class="fas fa-chevron-up" style="font-size: 9px; margin-right: 4px;"></i> Hide Details`;
                hint.style.background = '#1f6b4a';
                hint.style.color = '#f3efe6';
            }
            hdr.style.background = '#e2eee6';
        });
    }

    function collapseAllGroups() {
        document.querySelectorAll('tr.group-header').forEach(hdr => {
            const gid = hdr.dataset.groupId;
            const rows = document.querySelectorAll(`tr.child-row[data-group-id="${gid}"]`);
            const icon = document.querySelector(`.group-icon-${gid}`);
            const hint = document.querySelector(`.group-status-hint-${gid}`);
            rows.forEach(r => {
                r.style.removeProperty('display');
                r.classList.remove('is-visible');
                r.classList.add('is-hidden');
            });
            if (icon) icon.style.transform = 'rotate(0deg)';
            if (hint) {
                hint.innerHTML = `<i class="fas fa-chevron-down" style="font-size: 9px; margin-right: 4px;"></i> View Details`;
                hint.style.background = 'rgba(31,107,74,0.12)';
                hint.style.color = '#1f6b4a';
            }
            hdr.style.background = '#f3efe6';
        });
    }

    function renderItemsTable(items) {
        const tbody = document.getElementById('itemsTableBody');
        if (!items || items.length === 0) {
            tbody.innerHTML = `
                <tr class="empty-row">
                    <td colspan="9">
                        <div class="table-empty-state">
                            <i data-lucide="clipboard-list"></i>
                            <p>No charges found</p>
                            <small>Click "Add Charge" or "Room Rent" to add items</small>
                        </div>
                    </td>
                </tr>`;
            return;
        }

        // Group items by charge_type
        const grouped = {};
        items.forEach(item => {
            const cType = item.charge_type || 'OTHER';
            if (!grouped[cType]) {
                grouped[cType] = {
                    charge_type: cType,
                    total_amount: 0,
                    count: 0,
                    active_count: 0,
                    items: []
                };
            }
            grouped[cType].items.push(item);
            
            if (item.status !== 'CANCELLED') {
                grouped[cType].total_amount += parseFloat(item.total_amount || 0);
                grouped[cType].active_count++;
            }
            grouped[cType].count++;
        });

        let html = '';
        
        Object.values(grouped).forEach(group => {
            const rawType = String(group.charge_type || 'MISC');
            const safeId = rawType.replace(/[^a-zA-Z0-9_-]/g, '_');

            let badgeClass = 'badge-MISC';
            let icon = 'more-horizontal';
            let catName = rawType.replace(/_/g, ' ');
            if (rawType === 'MISC') { catName = 'MISCELLANEOUS'; }
            if (rawType === 'ROOM_RENT') { badgeClass = 'badge-ROOM_RENT'; icon = 'bed-double'; catName = 'Room Rent & Nursing'; }
            if (rawType === 'DOCTOR_VISIT') { badgeClass = 'badge-DOCTOR_VISIT'; icon = 'stethoscope'; catName = 'Doctor Consultation'; }
            if (rawType === 'LAB') { badgeClass = 'badge-LAB'; icon = 'flask-conical'; catName = 'Laboratory'; }
            if (rawType === 'RADIOLOGY') { badgeClass = 'badge-RADIOLOGY'; icon = 'radio'; catName = 'Radiology'; }
            if (rawType === 'PHARMACY') { badgeClass = 'badge-PHARMACY'; icon = 'pill'; catName = 'Pharmacy Medicines'; }
            if (rawType === 'OT') { badgeClass = 'badge-OT'; icon = 'syringe'; catName = 'Operation Theatre'; }
            if (rawType === 'PROCEDURE') { badgeClass = 'badge-PROCEDURE'; icon = 'activity'; catName = 'Procedure & Nursing'; }
            if (rawType === 'DIALYSIS') { badgeClass = 'badge-DIALYSIS'; icon = 'filter'; catName = 'Dialysis'; }
            if (rawType === 'OXYGEN') { badgeClass = 'badge-OXYGEN'; icon = 'wind'; catName = 'Oxygen Therapy'; }
            if (rawType === 'VENTILATION') { badgeClass = 'badge-VENTILATION'; icon = 'activity'; catName = 'Ventilator Support'; }
            if (rawType === 'BLOOD_TRANSFUSION') { badgeClass = 'badge-BLOOD_TRANSFUSION'; icon = 'droplet'; catName = 'Blood Transfusion'; }
            if (rawType === 'WARD_TRANSFER') { badgeClass = 'badge-WARD_TRANSFER'; icon = 'arrow-right-left'; catName = 'Ward Transfer'; }
            if (rawType === 'CONSUMABLE') { badgeClass = 'badge-CONSUMABLE'; icon = 'bandage'; catName = 'Consumables'; }
            if (rawType === 'OTHER') { badgeClass = 'badge-OTHER'; icon = 'layers'; catName = 'Other Charges'; }

            const groupTotal = group.total_amount.toLocaleString('en-IN', { minimumFractionDigits: 2 });
            const countLabel = group.count > group.active_count
                ? `${group.active_count} active${group.active_count === 0 ? '' : `, ${group.count - group.active_count} cancelled`}`
                : `${group.active_count} item${group.active_count === 1 ? '' : 's'}`;
            
            // Parent Row (Summary View by default, click to expand/collapse full details)
            html += `
                <tr class="group-header" data-group-id="${safeId}" onclick="window.toggleGroup('${safeId}')" style="cursor: pointer; background: #f3efe6; border-bottom: 1.5px solid rgba(31, 107, 74, 0.25); color: #1f6b4a; user-select: none; transition: background 0.15s ease;" title="Click to view full details">
                    <td colspan="4" style="font-weight: 700; padding: 12px 16px;">
                        <i data-lucide="chevron-right" class="group-icon-${safeId}" style="transform: rotate(0deg); transition: transform 0.25s cubic-bezier(0.4, 0, 0.2, 1); width: 16px; height: 16px; vertical-align: text-bottom; margin-right: 8px; color: #1f6b4a; display: inline-block;"></i>
                        <div class="charge-type-badge" style="display:inline-flex; background: #1f6b4a; color: #f3efe6; padding: 4px 10px; border-radius: 12px; font-weight: 700; gap: 6px; font-size: 0.8rem;">
                            <i data-lucide="${icon}" style="width: 14px; height: 14px;"></i> ${catName} (${countLabel})
                        </div>
                        <button type="button" class="group-status-hint-${safeId} btn-group-toggle" onclick="event.stopPropagation(); window.toggleGroup('${safeId}');" style="font-size: 11px; font-weight: 800; color: #1f6b4a; background: rgba(31,107,74,0.12); padding: 3px 10px; border-radius: 6px; border: 1.5px solid #1f6b4a; margin-left: 10px; cursor: pointer; display: inline-flex; align-items: center; gap: 4px; transition: all 0.15s ease;" title="Click to view details">
                            <i class="fas fa-chevron-down" style="font-size: 9px;"></i> View Details
                        </button>
                    </td>
                    <td colspan="2"></td>
                    <td class="tbl-amt" style="font-weight: 800; color: #1f6b4a; font-size: 0.95rem;">₹${groupTotal}</td>
                    <td colspan="2" style="text-align: right; padding-right: 14px;">
                        <span style="font-size: 11px; font-weight: 600; opacity: 0.75;"><i class="fas fa-layer-group" style="margin-right: 3px;"></i> Summary</span>
                    </td>
                </tr>
            `;

            // Child Rows (Hidden by default, shown upon expanding accordion)
            group.items.forEach((item, index) => {
                const isCancelled = item.status === 'CANCELLED';
                const dateStr = new Date(item.charge_date).toLocaleDateString('en-GB', { day: 'numeric', month: 'short' });
                const qtyFloat = parseFloat(item.quantity);
                const qty = isNaN(qtyFloat) ? '—' : qtyFloat.toString();
                const rateFloat = parseFloat(item.unit_price);
                const rate = isNaN(rateFloat) ? '—' : rateFloat.toLocaleString('en-IN', { minimumFractionDigits: 2 });
                const totalFloat = parseFloat(item.total_amount);
                const total = isNaN(totalFloat) ? '0.00' : totalFloat.toLocaleString('en-IN', { minimumFractionDigits: 2 });

                // Try to parse items_json if available to show breakdown in subDesc
                let subDesc = '';
                if (item.items_json) {
                    try {
                        const subItems = JSON.parse(item.items_json);
                        if (Array.isArray(subItems) && subItems.length > 0) {
                            subDesc = '<div class="item-sub" style="color: #1f6b4a; opacity: 0.85; font-size: 12px; margin-top: 4px; line-height: 1.4;">' + 
                                subItems.map(si => {
                                    const n = si.name || si.test_name || si.item_name || 'Item';
                                    const t = si.total || si.amount || 0;
                                    return `${n}: ₹${parseFloat(t).toLocaleString('en-IN')}`;
                                }).join(' <span style="opacity: 0.5;">|</span> ') + 
                                '</div>';
                        } else if (typeof subItems === 'object' && subItems !== null) {
                            if (parseFloat(subItems.returned_qty) > 0) {
                                subDesc = `<div class="item-sub" style="color: #1f6b4a; font-weight: 700; font-size: 12px; margin-top: 4px; line-height: 1.4;">
                                    <i data-lucide="undo-2" style="width:12px; height:12px; display:inline-block; margin-right:2px; vertical-align:text-bottom;"></i>
                                    Returned: ${subItems.returned_qty} (Refund: ₹${parseFloat(subItems.returned_amount || 0).toLocaleString('en-IN', {minimumFractionDigits:2})})
                                </div>`;
                            }
                        }
                    } catch (e) {}
                } else if (item.charge_type === 'ROOM_RENT') {
                    // Fallback for old items without items_json
                    const bedR = parseFloat(item.bed_rent || 0).toLocaleString('en-IN');
                    const nurR = parseFloat(item.nursing_charge || 0).toLocaleString('en-IN');
                    const drR = parseFloat(item.duty_dr_charge || 0).toLocaleString('en-IN');
                    if (parseFloat(item.bed_rent || 0) > 0) {
                        subDesc = `<div class="item-sub" style="color: #1f6b4a; opacity: 0.85;">Bed:₹${bedR} + Nurse:₹${nurR} + DutyDr:₹${drR}</div>`;
                    }
                }

                const canCancel = !isCancelled && currentMaster && currentMaster.billing_status !== 'FINALIZED' && currentMaster.billing_status !== 'CANCELLED';
                const sourceIcon = item.source !== 'MANUAL' ? `<i data-lucide="link" title="Source: ${item.source}" style="color:#1f6b4a; margin-left:4px;"></i>` : '';

                html += `
                    <tr class="child-row is-hidden group-${safeId} ${isCancelled ? 'cancelled-row' : ''}" data-group-id="${safeId}" style="background: #faf8f5; border-bottom: 1px solid rgba(31, 107, 74, 0.15); color: #1f6b4a;">
                        <td style="padding: 10px 14px 10px 2.5rem; font-weight: 600;">${index + 1}</td>
                        <td style="padding: 10px 14px; font-weight: 600;">${dateStr}</td>
                        <td style="padding: 10px 14px;"><span style="font-size: 12px; opacity: 0.85; font-weight: 600;">${catName}</span></td>
                        <td style="padding: 10px 14px;">
                            <div class="item-desc" style="font-weight: 700;">${item.description} ${sourceIcon}</div>
                            ${subDesc}
                        </td>
                        <td class="tbl-num" style="padding: 10px 14px; font-weight: 600;">${qty}</td>
                        <td class="tbl-num" style="padding: 10px 14px; font-weight: 600;">${rate}</td>
                        <td class="tbl-amt" id="total-cell-${item.item_id}" style="padding: 10px 14px; font-weight: 800;">
                            ${canCancel ? `<span style="cursor:pointer; color:#1f6b4a; text-decoration:underline;" onclick="billing.startInlineEdit(${item.item_id}, ${totalFloat})" title="Click to edit total">₹${total}</span>` : `₹${total}`}
                        </td>
                        <td style="padding: 10px 14px;">
                            <div class="item-status-badge" style="background: #1f6b4a; color: #f3efe6; border: 1px solid #1f6b4a; padding: 2px 8px; border-radius: 12px; font-size: 0.72rem; font-weight: 700;">${item.status}</div>
                        </td>
                        <td style="padding: 10px 14px;">
                            ${canCancel ? `<button class="btn-tbl-cancel" style="background: transparent; border: 1px solid #1f6b4a; color: #1f6b4a; border-radius: 4px; padding: 4px 6px; cursor: pointer;" onclick="billing.openCancelChargeModal(${item.item_id}, '${item.charge_type}', '${item.description}', '${dateStr}', '${total}')" title="Cancel Charge"><i data-lucide="x" style="width:12px;height:12px;"></i></button>` : ''}
                        </td>
                    </tr>
                `;
            });
        });
        
        tbody.innerHTML = html;
        if(window.lucide) lucide.createIcons();

        // Synchronize live category totals directly to Financial Summary panel
        const liveBreakdown = {
            ROOM_RENT: 0, DOCTOR_VISIT: 0, LAB: 0, RADIOLOGY: 0,
            PHARMACY: 0, OT: 0, PROCEDURE: 0, CONSUMABLE: 0, MISC: 0
        };
        items.forEach(it => {
            if (it.status !== 'CANCELLED') {
                const amt = parseFloat(it.total_amount || 0);
                const t = String(it.charge_type || '').toUpperCase();
                if (t === 'ROOM_RENT') liveBreakdown.ROOM_RENT += amt;
                else if (t === 'DOCTOR_VISIT') liveBreakdown.DOCTOR_VISIT += amt;
                else if (t === 'LAB') liveBreakdown.LAB += amt;
                else if (t === 'RADIOLOGY') liveBreakdown.RADIOLOGY += amt;
                else if (t === 'PHARMACY') liveBreakdown.PHARMACY += amt;
                else if (t === 'OT') liveBreakdown.OT += amt;
                else if (t === 'PROCEDURE') liveBreakdown.PROCEDURE += amt;
                else if (t === 'CONSUMABLE') liveBreakdown.CONSUMABLE += amt;
                else liveBreakdown.MISC += amt;
            }
        });

        Object.keys(liveBreakdown).forEach(k => {
            const el = document.getElementById(`fsVal_${k}`) || (k === 'MISC' ? document.getElementById('fsVal_OTHER') : null);
            const row = document.getElementById(`fsCat_${k}`) || (k === 'MISC' ? document.getElementById('fsCat_OTHER') : null);
            if (el) {
                el.textContent = '₹' + liveBreakdown[k].toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            }
            if (row) {
                if (liveBreakdown[k] === 0) row.classList.add('zero');
                else row.classList.remove('zero');
            }
        });
    }

    async function loadPayments() {
        if (!currentBillId) return;
        try {
            const res = await fetch(`${API_URL}ipd-payment?bill_id=${currentBillId}&_t=${Date.now()}`, { cache: 'no-store' });
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
                            <i data-lucide="wallet"></i>
                            <p>No payments recorded yet</p>
                            <small>Record an advance or partial payment</small>
                        </div>
                    </td>
                </tr>`;
            return;
        }

        let html = '';
        payments.forEach((pay, index) => {
            const dateStr = new Date(pay.payment_date).toLocaleDateString('en-GB', { day: 'numeric', month: 'short' });
            const amount = parseFloat(pay.amount).toLocaleString('en-IN', { minimumFractionDigits: 2 });

            let modeIcon = 'banknote';
            if (pay.payment_mode === 'UPI') modeIcon = 'smartphone';
            if (pay.payment_mode === 'CARD') modeIcon = 'credit-card';
            if (pay.payment_mode === 'BANK' || pay.payment_mode === 'CHEQUE') modeIcon = 'landmark';
            if (pay.payment_mode === 'INSURANCE') modeIcon = 'shield';

            const isRefund = pay.payment_type === 'REFUND';
            const status = pay.verified_status || 'VERIFIED';
            const isVerified = (status === 'VERIFIED');
            const verifiedBadge = isVerified 
                ? `<span class="badge-payment-verified" style="background: #dcfce7 !important; color: #14532d !important; border: 1.5px solid #4ade80 !important; padding: 4px 10px !important; border-radius: 20px !important; font-weight: 800 !important; font-size: 0.75rem !important; display: inline-flex !important; align-items: center !important; gap: 5px !important; box-shadow: 0 1px 3px rgba(0,0,0,0.05) !important;"><span style="color: #16a34a !important; font-weight: 900 !important;">✓</span> <span style="color: #14532d !important; font-weight: 800 !important;">VERIFIED</span></span>`
                : `<span class="badge-payment-pending" style="background: #fef3c7 !important; color: #92400e !important; border: 1.5px solid #fcd34d !important; padding: 4px 10px !important; border-radius: 20px !important; font-weight: 800 !important; font-size: 0.75rem !important; display: inline-flex !important; align-items: center !important; gap: 5px !important;"><span style="color: #d97706 !important;">⏳</span> <span style="color: #92400e !important; font-weight: 800 !important;">${status}</span></span>`;

            html += `
                <tr style="border-bottom: 1px solid rgba(31, 107, 74, 0.2); color: #1f6b4a;">
                    <td style="padding: 10px 14px; font-weight: 700; text-align: center; width: 50px;">${index + 1}</td>
                    <td style="padding: 10px 14px; font-weight: 600;">${dateStr}</td>
                    <td style="padding: 10px 14px;"><span style="background: #f3efe6; color: #1f6b4a; border: 1px solid #1f6b4a; padding: 2px 8px; border-radius: 12px; font-weight: 700; font-size: 0.75rem;">${pay.payment_type}</span></td>
                    <td style="padding: 10px 14px; font-weight: 600;"><i class="fas ${modeIcon} pay-mode-icon" style="color: #1f6b4a; margin-right: 4px;"></i> ${pay.payment_mode}</td>
                    <td style="padding: 10px 14px; font-weight: 800; color: #1f6b4a;">${isRefund ? '-' : ''}₹${amount}</td>
                    <td style="padding: 10px 14px; opacity: 0.85;">${pay.reference_no || '—'}</td>
                    <td style="padding: 10px 14px;">${verifiedBadge}</td>
                </tr>
            `;
        });
        tbody.innerHTML = html;
        if(window.lucide) lucide.createIcons();
    }

    async function loadAllAdmittedPatients() {
        try {
            const res = await fetch(`${API_URL}ipd-billing-master?action=search_admissions&q=`);
            const json = await res.json();
            if (json.success && json.data) {
                window.allAdmittedPatientsList = json.data;
                billing.filterPatientsTable(); // This will trigger rendering based on active filters
            } else {
                document.getElementById('admittedPatientsList').innerHTML = `<tr><td colspan="8" style="text-align:center; padding:30px; color: #666;">No admitted patients found.</td></tr>`;
            }
        } catch (e) {
            console.error(e);
            document.getElementById('admittedPatientsList').innerHTML = `<tr><td colspan="8" style="text-align:center; padding:30px; color: red;">Failed to load patients.</td></tr>`;
        }
    }

    function escapeHtml(str) {
        if (str === null || str === undefined) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function filterPatientsTable() {
        const tbody = document.getElementById('admittedPatientsList');
        if (!tbody || !window.allAdmittedPatientsList) return;

        const isReception = Boolean(window.IS_RECEPTION_VIEW) || 
                            (!['admin', 'accountant'].includes((window.USER_ROLE || '').toLowerCase()) && window.IS_RECEPTION_VIEW !== false);

        const statusFilter = isReception ? 'ACTIVE' : (document.getElementById('patientStatusFilter')?.value || 'ALL');
        const searchQuery = (document.getElementById('patientTableSearch')?.value || '').toLowerCase();
        
        // Filtering
        let filtered = window.allAdmittedPatientsList.filter(p => {
            const rawStatus = (p.status || '').toString().trim();
            const lowerStatus = rawStatus.toLowerCase();
            const isActive = (lowerStatus === 'admitted' || lowerStatus === 'active');
            const isDischarged = (lowerStatus === 'discharged' || (!isActive && Boolean(p.discharge_date)));
            
            // In Reception View: STRICTLY show Active/Admitted patients only (never show Discharged)
            if (isReception) {
                if (!isActive) return false;
            } else {
                // In Admin Mode: apply dropdown filter (All, Active, Discharged)
                if (statusFilter === 'ACTIVE' && !isActive) return false;
                if (statusFilter === 'DISCHARGED' && !isDischarged) return false;
            }
            
            // Search Check
            if (searchQuery) {
                const searchStr = `${p.admission_id || ''} ${p.patient_name || ''} ${p.phone || ''} ${p.doctor_name || ''} ${p.ward_name || ''} ${p.room_name || ''} ${rawStatus}`.toLowerCase();
                if (!searchStr.includes(searchQuery)) return false;
            }
            return true;
        });

        // Sorting
        filtered.sort((a, b) => {
            let valA = a[window.currentPatientSort?.col || 'admission_id'] || '';
            let valB = b[window.currentPatientSort?.col || 'admission_id'] || '';
            
            // Special handling for computed status
            if (window.currentPatientSort?.col === 'status') {
                const stA = (a.status || '').toString().trim().toLowerCase();
                const stB = (b.status || '').toString().trim().toLowerCase();
                valA = (stA === 'admitted' || stA === 'active') ? 'Active' : (stA === 'discharged' || a.discharge_date ? 'Discharged' : a.status || '');
                valB = (stB === 'admitted' || stB === 'active') ? 'Active' : (stB === 'discharged' || b.discharge_date ? 'Discharged' : b.status || '');
            }

            if (valA < valB) return window.currentPatientSort?.asc ? -1 : 1;
            if (valA > valB) return window.currentPatientSort?.asc ? 1 : -1;
            return 0;
        });

        // Rendering
        if (filtered.length === 0) {
            tbody.innerHTML = `<tr><td colspan="8" style="text-align:center; padding:30px; color: #666;">No matching patients found.</td></tr>`;
            return;
        }

        let html = '';
        filtered.forEach(p => {
            const pName = p.patient_name || [p.first_name, p.last_name].filter(Boolean).join(' ') || 'Patient';
            const rawStatus = (p.status || '').toString().trim();
            const lowerStatus = rawStatus.toLowerCase();
            const isActive = (lowerStatus === 'admitted' || lowerStatus === 'active');
            
            let statusLabel = '';
            if (isActive) {
                statusLabel = `<span class="badge-patient-active" style="background: #dcfce7 !important; color: #14532d !important; border: 1.5px solid #4ade80 !important; padding: 4px 12px !important; border-radius: 20px !important; font-size: 0.8rem !important; font-weight: 800 !important; display: inline-flex !important; align-items: center !important; gap: 6px !important; box-shadow: 0 1px 3px rgba(0,0,0,0.05) !important;"><span class="dot" style="width: 8px; height: 8px; border-radius: 50%; background: #16a34a !important; display: inline-block !important;"></span> Active</span>`;
            } else if (lowerStatus === 'discharged' || (!isActive && p.discharge_date)) {
                statusLabel = `<span class="badge-patient-discharged" style="background: #f1f5f9 !important; color: #334155 !important; border: 1.5px solid #94a3b8 !important; padding: 4px 12px !important; border-radius: 20px !important; font-size: 0.8rem !important; font-weight: 800 !important; display: inline-flex !important; align-items: center !important; gap: 6px !important;"><span class="dot" style="width: 8px; height: 8px; border-radius: 50%; background: #64748b !important; display: inline-block !important;"></span> Discharged</span>`;
            } else {
                statusLabel = `<span style="background: #fef3c7 !important; color: #92400e !important; border: 1.5px solid #fcd34d !important; padding: 4px 12px !important; border-radius: 20px !important; font-size: 0.8rem !important; font-weight: 800 !important; display: inline-flex !important; align-items: center !important; gap: 6px !important;"><span class="dot" style="width: 8px; height: 8px; border-radius: 50%; background: #d97706 !important; display: inline-block !important;"></span> ${escapeHtml(rawStatus || 'Unknown')}</span>`;
            }

            html += `
                <tr style="border-bottom: 1px solid rgba(31, 107, 74, 0.2); transition: background 0.2s; color: #1f6b4a;" onmouseover="this.style.background='rgba(31, 107, 74, 0.08)'" onmouseout="this.style.background='transparent'">
                    <td style="padding: 12px; font-weight: 700;">${escapeHtml(p.admission_id)}</td>
                    <td style="padding: 12px;">
                        <div style="font-weight: 800; color: #1f6b4a;">${escapeHtml(pName)}</div>
                    </td>
                    <td style="padding: 12px; font-weight: 600;">${p.age ? escapeHtml(p.age) : '-'} / ${p.sex ? escapeHtml(p.sex.charAt(0)) : '-'}</td>
                    <td style="padding: 12px; font-weight: 600;">${escapeHtml(p.phone || '-')}</td>
                    <td style="padding: 12px;">
                        <div style="font-weight: 700;">${escapeHtml(p.ward_name || '-')}</div>
                        <div style="font-size: 0.85em; opacity: 0.8;">${escapeHtml(p.room_name || '-')} (${escapeHtml(p.bed_number || '-')})</div>
                    </td>
                    <td style="padding: 12px; font-weight: 600;">${escapeHtml(p.doctor_name || '-')}</td>
                    <td style="padding: 12px;">${statusLabel}</td>
                    <td style="padding: 12px;">
                        <button onclick="billing.loadAdmission('${escapeHtml(p.admission_id)}', '${escapeHtml(p.patient_id)}')" 
                                style="background: #1f6b4a; color: #f3efe6; border: 1.5px solid #1f6b4a; padding: 6px 14px; border-radius: 6px; cursor: pointer; display: inline-flex; align-items: center; gap: 5px; font-weight: 700; transition: all 0.2s;">
                            <i data-lucide="external-link" style="width: 14px; height: 14px;"></i> Open
                        </button>
                    </td>
                </tr>
            `;
        });
        tbody.innerHTML = html;
        if(window.lucide) lucide.createIcons();
    }
    window.filterPatientsTable = filterPatientsTable;

    function sortPatientsTable(col) {
        if (!window.currentPatientSort) window.currentPatientSort = { col: 'admission_id', asc: true };
        if (window.currentPatientSort.col === col) {
            window.currentPatientSort.asc = !window.currentPatientSort.asc;
        } else {
            window.currentPatientSort.col = col;
            window.currentPatientSort.asc = true;
        }
        filterPatientsTable();
    }
    window.sortPatientsTable = sortPatientsTable;

    // ─────────────────────────────────────────────────────────────
    // TABS & MENUS
    // ─────────────────────────────────────────────────────────────
    function filterItems(btn, type) {
        document.querySelectorAll('.cat-tab').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        loadItems(type);
    }

    function toggleChargeMenu() {
        if (currentMaster && (currentMaster.billing_status === 'FINALIZED' || currentMaster.billing_status === 'CANCELLED')) {
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
    }

    function closeChargeMenu() {
        document.getElementById('chargeMenu').classList.remove('open');
        document.getElementById('chargeArrow').classList.remove('open');
    }

    function initCloseClick() {
        document.addEventListener('click', function (e) {
            if (!e.target.closest('.add-charge-wrap')) {
                closeChargeMenu();
            }
        });
    }

    // ─────────────────────────────────────────────────────────────
    // MODALS: OPEN / CLOSE
    // ─────────────────────────────────────────────────────────────
    function openModal(id) {
        const el = document.getElementById(id);
        if (el) {
            el.classList.add('active');
            // focus first input
            const firstInput = el.querySelector('input:not([type="hidden"]), select, textarea');
            if (firstInput) setTimeout(() => firstInput.focus(), 100);
        }
    }

    function closeModal(id) {
        const el = document.getElementById(id);
        if (el) el.classList.remove('active');
    }

    // ── 1. ADD CHARGE (NURSE WORKSPACE PATTERN) ──
    let labSearchDebounce = null;
    let radSearchDebounce = null;
    let otherSearchDebounce = null;
    let phSearchDebounce = null;
    let docSearchDebounce = null;

    function openAddChargeModal(type = 'tab-doctor') {
        if (!currentBillId || !currentAdmissionId) {
            showToast('Please select an admitted patient first', 'warning');
            return;
        }

        if (currentMaster && (currentMaster.billing_status === 'FINALIZED' || currentMaster.billing_status === 'CANCELLED' || currentMaster.status === 'Discharged' || currentMaster.discharge_date)) {
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
            return;
        }

        let tabId = 'tab-doctor';
        if (type === 'LAB' || type === 'tab-lab') tabId = 'tab-lab';
        else if (type === 'RADIOLOGY' || type === 'tab-radiology') tabId = 'tab-radiology';
        else if (type === 'PROCEDURE' || type === 'OT' || type === 'OTHER' || type === 'tab-other-services') tabId = 'tab-other-services';
        else if (type === 'PHARMACY' || type === 'tab-pharmacy') tabId = 'tab-pharmacy';
        else if (type === 'DOCTOR_VISIT' || type === 'tab-doctor') tabId = 'tab-doctor';
        else if (type === 'DIALYSIS' || type === 'tab-dialysis') tabId = 'tab-dialysis';
        else if (type === 'OXYGEN' || type === 'tab-oxygen') tabId = 'tab-oxygen';
        else if (type === 'VENTILATION' || type === 'VENTILATOR' || type === 'tab-ventilator') tabId = 'tab-ventilator';
        else if (type === 'BLOOD_TRANSFUSION' || type === 'TRANSFUSION' || type === 'tab-transfusion') tabId = 'tab-transfusion';
        else if (type === 'WARD_TRANSFER' || type === 'TRANSFER' || type === 'tab-ward-transfer') tabId = 'tab-ward-transfer';
        else if (type === 'CONSUMABLE' || type === 'MISC' || type === 'tab-consumables') tabId = 'tab-consumables';
        else if (type.startsWith('tab-')) tabId = type;
        else tabId = 'tab-doctor';

        const today = new Date().toISOString().split('T')[0];
        const nowTime = new Date().toTimeString().slice(0, 5);

        // 1. Doctor Visit
        const docDate = document.getElementById('doc-date'); if (docDate) docDate.value = today;
        const docTime = document.getElementById('doc-time'); if (docTime) docTime.value = nowTime;
        const docSearch = document.getElementById('doc-search-input'); if (docSearch) docSearch.value = '';
        const docName = document.getElementById('doc-name'); if (docName) docName.value = '';
        const docShift = document.getElementById('doc-shift'); if (docShift) docShift.value = 'Morning';
        const docFee = document.getElementById('doc-fee'); if (docFee) docFee.value = '500';
        const docDisc = document.getElementById('doc-discount'); if (docDisc) docDisc.value = '0';
        const docNotes = document.getElementById('doc-notes'); if (docNotes) docNotes.value = '';
        calcDoctorTotal();

        // 2. Lab Test (Table: lab_services)
        const labDate = document.getElementById('lab-date'); if (labDate) labDate.value = today;
        const labInp = document.getElementById('lab-input'); if (labInp) labInp.value = '';
        const labName = document.getElementById('lab-name'); if (labName) labName.value = '';
        const labCode = document.getElementById('lab-code'); if (labCode) labCode.value = '';
        const labTier = document.getElementById('lab-tier'); if (labTier) labTier.value = '';
        const labFee = document.getElementById('lab-fee'); if (labFee) labFee.value = '0';
        const labDisc = document.getElementById('lab-discount'); if (labDisc) labDisc.value = '0';
        const labNotes = document.getElementById('lab-notes'); if (labNotes) labNotes.value = '';
        calcLabTotal();

        // 3. Radiology Test (Table: radiology_services)
        const radDate = document.getElementById('rad-date'); if (radDate) radDate.value = today;
        const radInp = document.getElementById('rad-input'); if (radInp) radInp.value = '';
        const radName = document.getElementById('rad-name'); if (radName) radName.value = '';
        const radCode = document.getElementById('rad-code'); if (radCode) radCode.value = '';
        const radTier = document.getElementById('rad-tier'); if (radTier) radTier.value = '';
        const radFee = document.getElementById('rad-fee'); if (radFee) radFee.value = '0';
        const radDisc = document.getElementById('rad-discount'); if (radDisc) radDisc.value = '0';
        const radNotes = document.getElementById('rad-notes'); if (radNotes) radNotes.value = '';
        calcRadTotal();

        // 4. Other Services (Table: other_services)
        const otherDate = document.getElementById('other-date'); if (otherDate) otherDate.value = today;
        const otherInp = document.getElementById('other-input'); if (otherInp) otherInp.value = '';
        const otherName = document.getElementById('other-name'); if (otherName) otherName.value = '';
        const procDocSearch = document.getElementById('proc-doc-search'); if (procDocSearch) procDocSearch.value = '';
        const procDoc = document.getElementById('proc-doctor'); if (procDoc) procDoc.value = '';
        const otherTier = document.getElementById('other-tier'); if (otherTier) otherTier.value = '';
        const otherQty = document.getElementById('other-qty'); if (otherQty) otherQty.value = '1';
        const otherFee = document.getElementById('other-fee'); if (otherFee) otherFee.value = '0';
        const otherDisc = document.getElementById('other-discount'); if (otherDisc) otherDisc.value = '0';
        const otherNotes = document.getElementById('other-notes'); if (otherNotes) otherNotes.value = '';
        calcOtherTotal();

        // 5. Pharmacy
        const phDate = document.getElementById('ph-date'); if (phDate) phDate.value = today;
        const phInp = document.getElementById('ph-input'); if (phInp) phInp.value = '';
        const phNotes = document.getElementById('ph-notes'); if (phNotes) phNotes.value = '';
        phCart = [];
        renderPhCart();

        // 6. Dialysis (14. dialysis_chart)
        const diaDocSearch = document.getElementById('dia-doc-search'); if (diaDocSearch) diaDocSearch.value = '';
        const diaDoc = document.getElementById('dia-doctor'); if (diaDoc) diaDoc.value = '';
        const diaDate = document.getElementById('dia-date'); if (diaDate) diaDate.value = today;
        const diaStart = document.getElementById('dia-start'); if (diaStart) diaStart.value = '09:00';
        const diaEnd = document.getElementById('dia-end'); if (diaEnd) diaEnd.value = '13:00';
        const diaDur = document.getElementById('dia-dur'); if (diaDur) diaDur.value = '4h';
        const diaFee = document.getElementById('dia-fee'); if (diaFee) diaFee.value = '2500';
        const diaDisc = document.getElementById('dia-discount'); if (diaDisc) diaDisc.value = '0';
        calcDiaTotal();

        // 7. Oxygen Therapy (15. oxygen_chart)
        const oxyDocSearch = document.getElementById('oxy-doc-search'); if (oxyDocSearch) oxyDocSearch.value = '';
        const oxyDoc = document.getElementById('oxy-doctor'); if (oxyDoc) oxyDoc.value = '';
        const oxyDate = document.getElementById('oxy-date'); if (oxyDate) oxyDate.value = today;
        const oxyFlow = document.getElementById('oxy-flow'); if (oxyFlow) oxyFlow.value = '2 L/min';
        const oxyStart = document.getElementById('oxy-start'); if (oxyStart) oxyStart.value = nowTime;
        const oxyEnd = document.getElementById('oxy-end'); if (oxyEnd) oxyEnd.value = '';
        const oxyDur = document.getElementById('oxy-dur'); if (oxyDur) oxyDur.value = '2h';
        const oxyFee = document.getElementById('oxy-fee'); if (oxyFee) oxyFee.value = '500';
        const oxyDisc = document.getElementById('oxy-discount'); if (oxyDisc) oxyDisc.value = '0';
        calcOxyTotal();

        // 8. Ventilator Support (16. ventilation_chart)
        const ventDocSearch = document.getElementById('vent-doc-search'); if (ventDocSearch) ventDocSearch.value = '';
        const ventDoc = document.getElementById('vent-doctor'); if (ventDoc) ventDoc.value = '';
        const ventDate = document.getElementById('vent-date'); if (ventDate) ventDate.value = today;
        const ventMode = document.getElementById('vent-mode'); if (ventMode) ventMode.value = 'CPAP';
        const ventStart = document.getElementById('vent-start'); if (ventStart) ventStart.value = nowTime;
        const ventEnd = document.getElementById('vent-end'); if (ventEnd) ventEnd.value = '';
        const ventDur = document.getElementById('vent-dur'); if (ventDur) ventDur.value = '6h';
        const ventFee = document.getElementById('vent-fee'); if (ventFee) ventFee.value = '2000';
        const ventDisc = document.getElementById('vent-discount'); if (ventDisc) ventDisc.value = '0';
        calcVentTotal();

        // 9. Blood Transfusion (17. blood_transfusion_chart)
        const btDocSearch = document.getElementById('bt-doc-search'); if (btDocSearch) btDocSearch.value = '';
        const btDoc = document.getElementById('bt-doctor'); if (btDoc) btDoc.value = '';
        const btDate = document.getElementById('bt-date'); if (btDate) btDate.value = today;
        const btGroup = document.getElementById('blood-group'); if (btGroup) btGroup.value = 'O+';
        const btBag = document.getElementById('bag-number'); if (btBag) btBag.value = '';
        const btQty = document.getElementById('trans-qty'); if (btQty) btQty.value = '350';
        const btVitals = document.getElementById('vitals-during'); if (btVitals) btVitals.value = '';
        const btFee = document.getElementById('bt-fee'); if (btFee) btFee.value = '1200';
        const btDisc = document.getElementById('bt-discount'); if (btDisc) btDisc.value = '0';
        calcBtTotal();

        // 10. Ward Transfer (18. ward_transfer)
        const wtDocSearch = document.getElementById('wt-doc-search'); if (wtDocSearch) wtDocSearch.value = '';
        const wtDoc = document.getElementById('wt-doctor'); if (wtDoc) wtDoc.value = '';
        const wtDate = document.getElementById('wt-date'); if (wtDate) wtDate.value = today;
        const wtTime = document.getElementById('wt-time'); if (wtTime) wtTime.value = nowTime;
        const wtFrom = document.getElementById('wt-from'); if (wtFrom) wtFrom.value = currentMaster?.ward_name || currentMaster?.ward || '';
        const wtTo = document.getElementById('wt-to'); if (wtTo) wtTo.value = '';
        const wtFee = document.getElementById('wt-fee'); if (wtFee) wtFee.value = '0';
        const wtDisc = document.getElementById('wt-discount'); if (wtDisc) wtDisc.value = '0';
        const wtReason = document.getElementById('wt-reason'); if (wtReason) wtReason.value = '';
        calcWtTotal();

        // 11. Consumables
        const miscDate = document.getElementById('misc-date'); if (miscDate) miscDate.value = today;
        const miscType = document.getElementById('misc-type'); if (miscType) miscType.value = 'CONSUMABLE';
        const miscDesc = document.getElementById('misc-desc'); if (miscDesc) miscDesc.value = '';
        const miscDept = document.getElementById('misc-dept'); if (miscDept) miscDept.value = 'General';
        const miscQty = document.getElementById('misc-qty'); if (miscQty) miscQty.value = '1';
        const miscFee = document.getElementById('misc-fee'); if (miscFee) miscFee.value = '0';
        const miscDisc = document.getElementById('misc-discount'); if (miscDisc) miscDisc.value = '0';
        const miscNotes = document.getElementById('misc-notes'); if (miscNotes) miscNotes.value = '';
        calcConsumableTotal();

        // Close search dropdowns
        document.querySelectorAll('#doc-results, #lab-results, #rad-results, #other-results, #ph-results, #proc-doc-results, #dia-doc-results, #oxy-doc-results, #vent-doc-results, #bt-doc-results, #wt-doc-results').forEach(el => el.style.display = 'none');

        // Select initial tab
        const targetBtn = document.querySelector(`.t-tab[data-tab="${tabId}"]`) || document.querySelector('.t-tab');
        selectSubTab(tabId, targetBtn);

        openModal('modalAddCharge');
    }

    function selectSubTab(tabId, btn) {
        document.querySelectorAll('.t-tab').forEach(b => b.classList.remove('active'));
        if (btn) btn.classList.add('active');

        document.querySelectorAll('.t-panel').forEach(p => p.classList.remove('active'));
        const panel = document.getElementById(tabId);
        if (panel) panel.classList.add('active');
    }

    // Cached search results to prevent attribute quoting/escaping issues
    window._labResults = [];
    window._radResults = [];
    window._otherResults = [];
    window._phResults = [];
    window._docResults = [];

    // ── LIVE CATALOG & DOCTOR SEARCH LISTENERS ──
    document.addEventListener('input', function(e) {
        if (!e.target) return;

        // ── 1. Lab Test Search (Table: lab_services with Room-Tier Pricing) ──
        if (e.target.id === 'lab-input') {
            clearTimeout(labSearchDebounce);
            const q = e.target.value.trim();
            const res = document.getElementById('lab-results');
            if (q.length < 1) { if(res) res.style.display = 'none'; return; }

            const roomType = currentMaster?.room_type || currentMaster?.ward_name || currentMaster?.ward || '';

            labSearchDebounce = setTimeout(async () => {
                try {
                    const r = await fetch(`${API_URL}ipd-catalog-search?type=LAB&q=${encodeURIComponent(q)}&room_type=${encodeURIComponent(roomType)}&admission_id=${encodeURIComponent(currentAdmissionId || '')}`);
                    const json = await r.json();
                    const items = json.data || [];
                    window._labResults = items;

                    if (items.length > 0) {
                        let html = '';
                        items.forEach((item, idx) => {
                            const tierBadge = item.room_tier ? `<span class="badge" style="font-size:0.68rem;opacity:0.9;">${escapeHtml(item.room_tier)}</span>` : '';
                            const price = parseFloat(item.price || 0);
                            html += `
                                <div class="ts-item" onclick="billing.selectLabItemByIndex(${idx})">
                                    <div>
                                        <strong style="color:#1f6b4a"><i class="fas fa-flask"></i> ${escapeHtml(item.name)}</strong><br>
                                        <small style="color:#1f6b4a; opacity:0.85;">Code: ${item.id || '-'} ${tierBadge}</small>
                                    </div>
                                    <div style="display:flex; align-items:center; gap:8px;">
                                        <strong style="color:#1f6b4a;">₹${price.toLocaleString('en-IN', {minimumFractionDigits:2})}</strong>
                                        <span class="badge">Select</span>
                                    </div>
                                </div>
                            `;
                        });
                        res.innerHTML = html;
                        res.style.display = 'block';
                    } else {
                        res.innerHTML = '<div style="padding:12px;text-align:center;color:#1f6b4a;opacity:0.8;font-size:.82rem">No matching lab tests found.</div>';
                        res.style.display = 'block';
                    }
                } catch (err) {
                    console.error("Lab search error:", err);
                }
            }, 200);
        }

        // ── 2. Radiology Test Search (Table: radiology_services with Room-Tier Pricing) ──
        if (e.target.id === 'rad-input') {
            clearTimeout(radSearchDebounce);
            const q = e.target.value.trim();
            const res = document.getElementById('rad-results');
            if (q.length < 1) { if(res) res.style.display = 'none'; return; }

            const roomType = currentMaster?.room_type || currentMaster?.ward_name || currentMaster?.ward || '';

            radSearchDebounce = setTimeout(async () => {
                try {
                    const r = await fetch(`${API_URL}ipd-catalog-search?type=RADIOLOGY&q=${encodeURIComponent(q)}&room_type=${encodeURIComponent(roomType)}&admission_id=${encodeURIComponent(currentAdmissionId || '')}`);
                    const json = await r.json();
                    const items = json.data || [];
                    window._radResults = items;

                    if (items.length > 0) {
                        let html = '';
                        items.forEach((item, idx) => {
                            const tierBadge = item.room_tier ? `<span class="badge" style="font-size:0.68rem;opacity:0.9;">${escapeHtml(item.room_tier)}</span>` : '';
                            const price = parseFloat(item.price || 0);
                            html += `
                                <div class="ts-item" onclick="billing.selectRadItemByIndex(${idx})">
                                    <div>
                                        <strong style="color:#1f6b4a"><i class="fas fa-radiation"></i> ${escapeHtml(item.name)}</strong><br>
                                        <small style="color:#1f6b4a; opacity:0.85;">Modality/Code: ${item.id || '-'} ${tierBadge}</small>
                                    </div>
                                    <div style="display:flex; align-items:center; gap:8px;">
                                        <strong style="color:#1f6b4a;">₹${price.toLocaleString('en-IN', {minimumFractionDigits:2})}</strong>
                                        <span class="badge">Select</span>
                                    </div>
                                </div>
                            `;
                        });
                        res.innerHTML = html;
                        res.style.display = 'block';
                    } else {
                        res.innerHTML = '<div style="padding:12px;text-align:center;color:#1f6b4a;opacity:0.8;font-size:.82rem">No matching radiology tests found.</div>';
                        res.style.display = 'block';
                    }
                } catch (err) {
                    console.error("Radiology search error:", err);
                }
            }, 200);
        }

        // ── 3. Other Services Search (Table: other_services with Room-Tier Pricing) ──
        if (e.target.id === 'other-input') {
            clearTimeout(otherSearchDebounce);
            const q = e.target.value.trim();
            const res = document.getElementById('other-results');
            if (q.length < 1) { if(res) res.style.display = 'none'; return; }

            const roomType = currentMaster?.room_type || currentMaster?.ward_name || currentMaster?.ward || '';

            otherSearchDebounce = setTimeout(async () => {
                try {
                    const r = await fetch(`${API_URL}ipd-catalog-search?type=PROCEDURE&q=${encodeURIComponent(q)}&room_type=${encodeURIComponent(roomType)}&admission_id=${encodeURIComponent(currentAdmissionId || '')}`);
                    const json = await r.json();
                    const items = json.data || [];
                    window._otherResults = items;

                    if (items.length > 0) {
                        let html = '';
                        items.forEach((item, idx) => {
                            const price = parseFloat(item.price || 0);
                            const tierBadge = item.room_tier ? `<span class="badge" style="font-size:0.68rem;opacity:0.9;">${escapeHtml(item.room_tier)}</span>` : '';
                            html += `
                                <div class="ts-item" onclick="billing.selectOtherItemByIndex(${idx})">
                                    <div>
                                        <strong style="color:#1f6b4a"><i class="fas fa-stethoscope"></i> ${escapeHtml(item.name)}</strong><br>
                                        <small style="color:#1f6b4a; opacity:0.85;">Code: ${item.id || '-'} ${tierBadge}</small>
                                    </div>
                                    <div style="display:flex; align-items:center; gap:8px;">
                                        <strong style="color:#1f6b4a;">₹${price.toLocaleString('en-IN', {minimumFractionDigits:2})}</strong>
                                        <span class="badge">Select</span>
                                    </div>
                                </div>
                            `;
                        });
                        res.innerHTML = html;
                        res.style.display = 'block';
                    } else {
                        res.innerHTML = '<div style="padding:12px;text-align:center;color:#1f6b4a;opacity:0.8;font-size:.82rem">No services found.</div>';
                        res.style.display = 'block';
                    }
                } catch (err) {
                    console.error("Other services search error:", err);
                }
            }, 200);
        }

        // ── 4. Pharmacy Order Search (Referencing nurse_workspace.php) ──
        if (e.target.id === 'ph-input') {
            clearTimeout(phSearchDebounce);
            const q = e.target.value.trim();
            const res = document.getElementById('ph-results');
            if (q.length < 1) { if(res) res.style.display = 'none'; return; }
            phSearchDebounce = setTimeout(async () => {
                try {
                    const r = await fetch(`${API_URL}ipd-catalog-search?type=PHARMACY&q=${encodeURIComponent(q)}`);
                    const json = await r.json();
                    const items = json.data || [];
                    window._phResults = items;

                    if (items.length > 0) {
                        let html = '';
                        items.forEach((item, idx) => {
                            const price = parseFloat(item.price || 0);
                            html += `
                                <div class="ph-item" onclick="billing.selectPhItemByIndex(${idx})">
                                    <div>
                                        <strong style="color:#1f6b4a"><i class="fas fa-pills"></i> ${escapeHtml(item.name)}</strong><br>
                                        <small style="color:#1f6b4a; opacity:0.85;">Batch: ${item.batch || 'N/A'} | Stock: ${item.stock ?? '?'}</small>
                                    </div>
                                    <div style="display:flex; align-items:center; gap:8px;">
                                        <strong style="color:#1f6b4a;">₹${price.toLocaleString('en-IN', {minimumFractionDigits:2})}</strong>
                                        <span class="badge"><i class="fas fa-plus"></i> Add</span>
                                    </div>
                                </div>
                            `;
                        });
                        res.innerHTML = html;
                        res.style.display = 'block';
                    } else {
                        res.innerHTML = '<div style="padding:12px;text-align:center;color:#1f6b4a;opacity:0.8;font-size:.82rem">No medicines found.</div>';
                        res.style.display = 'block';
                    }
                } catch (err) {
                    console.error("Pharmacy search error:", err);
                }
            }, 200);
        }

        // ── 5. Advance Search Doctor (All Doctor Input Fields) ──
        const docInputsMap = [
            { inp: 'doc-search-input', res: 'doc-results', isMainDoc: true },
            { inp: 'proc-doc-search', res: 'proc-doc-results', target: 'proc-doctor' },
            { inp: 'dia-doc-search', res: 'dia-doc-results', target: 'dia-doctor' },
            { inp: 'oxy-doc-search', res: 'oxy-doc-results', target: 'oxy-doctor' },
            { inp: 'vent-doc-search', res: 'vent-doc-results', target: 'vent-doctor' },
            { inp: 'bt-doc-search', res: 'bt-doc-results', target: 'bt-doctor' },
            { inp: 'wt-doc-search', res: 'wt-doc-results', target: 'wt-doctor' }
        ];

        const matchedDoc = docInputsMap.find(d => e.target.id === d.inp);
        if (matchedDoc) {
            clearTimeout(docSearchDebounce);
            const q = e.target.value.trim();
            const res = document.getElementById(matchedDoc.res);
            if (q.length < 1) { if(res) res.style.display = 'none'; return; }
            docSearchDebounce = setTimeout(async () => {
                try {
                    const r = await fetch(`${API_URL}ipd-catalog-search?type=DOCTOR&q=${encodeURIComponent(q)}`);
                    const json = await r.json();
                    const items = json.data || [];
                    window._docResults = items;

                    if (items.length > 0) {
                        let html = '';
                        items.forEach((doc, idx) => {
                            const fee = parseFloat(doc.price || 500);
                            const clickAction = matchedDoc.isMainDoc 
                                ? `billing.selectDocItemByIndex(${idx})`
                                : `billing.selectGenericDocByIndex('${matchedDoc.inp}', '${matchedDoc.res}', '${matchedDoc.target}', ${idx})`;

                            html += `
                                <div class="ts-item" onclick="${clickAction}">
                                    <div>
                                        <strong style="color:#1f6b4a"><i class="fas fa-user-md"></i> ${escapeHtml(doc.name)}</strong><br>
                                        <small style="color:#1f6b4a; opacity:0.85;">${escapeHtml(doc.department || doc.designation || 'Consultant')} | ID: ${doc.id || '-'}</small>
                                    </div>
                                    <div style="display:flex; align-items:center; gap:8px;">
                                        <strong style="color:#1f6b4a;">₹${fee.toLocaleString('en-IN', {minimumFractionDigits:2})}</strong>
                                        <span class="badge">Select</span>
                                    </div>
                                </div>
                            `;
                        });
                        res.innerHTML = html;
                        res.style.display = 'block';
                    } else {
                        res.innerHTML = '<div style="padding:12px;text-align:center;color:#1f6b4a;opacity:0.8;font-size:.82rem">No matching doctors found.</div>';
                        res.style.display = 'block';
                    }
                } catch (err) {
                    console.error("Doctor search error:", err);
                }
            }, 200);
        }
    });

    // Index-based selection helpers
    function selectLabItemByIndex(idx) {
        const item = window._labResults ? window._labResults[idx] : null;
        if (item) selectLabItem(item);
    }

    function selectRadItemByIndex(idx) {
        const item = window._radResults ? window._radResults[idx] : null;
        if (item) selectRadItem(item);
    }

    function selectOtherItemByIndex(idx) {
        const item = window._otherResults ? window._otherResults[idx] : null;
        if (item) selectOtherItem(item);
    }

    function selectPhItemByIndex(idx) {
        const item = window._phResults ? window._phResults[idx] : null;
        if (item) addToPhCart(item);
    }

    function selectDocItemByIndex(idx) {
        const doc = window._docResults ? window._docResults[idx] : null;
        if (doc) selectDoctorItem(doc);
    }

    function selectGenericDocByIndex(inputId, resultsId, targetInputId, idx) {
        const doc = window._docResults ? window._docResults[idx] : null;
        if (doc) selectGenericDoctor(inputId, resultsId, targetInputId, doc);
    }

    // Close search dropdowns on outside click
    document.addEventListener('click', function(e) {
        const dropdownPairs = [
            { inp: '#doc-search-input', res: '#doc-results' },
            { inp: '#lab-input', res: '#lab-results' },
            { inp: '#rad-input', res: '#rad-results' },
            { inp: '#other-input', res: '#other-results' },
            { inp: '#ph-input', res: '#ph-results' },
            { inp: '#proc-doc-search', res: '#proc-doc-results' },
            { inp: '#dia-doc-search', res: '#dia-doc-results' },
            { inp: '#oxy-doc-search', res: '#oxy-doc-results' },
            { inp: '#vent-doc-search', res: '#vent-doc-results' },
            { inp: '#bt-doc-search', res: '#bt-doc-results' },
            { inp: '#wt-doc-search', res: '#wt-doc-results' }
        ];

        dropdownPairs.forEach(pair => {
            if (!e.target.closest(pair.inp) && !e.target.closest(pair.res)) {
                const el = document.querySelector(pair.res);
                if (el) el.style.display = 'none';
            }
        });
    });

    function selectGenericDoctor(inputId, resultsId, targetInputId, doc) {
        if (!doc) return;
        const res = document.getElementById(resultsId); if (res) res.style.display = 'none';
        const inp = document.getElementById(inputId); if (inp) inp.value = doc.name;
        const target = document.getElementById(targetInputId); if (target) target.value = doc.name;
    }

    function selectDoctorItem(doc) {
        if (!doc) return;
        const res = document.getElementById('doc-results'); if (res) res.style.display = 'none';
        const inp = document.getElementById('doc-search-input'); if (inp) inp.value = doc.name;

        const docNameEl = document.getElementById('doc-name'); if (docNameEl) docNameEl.value = doc.name;
        const docDeptEl = document.getElementById('doc-dept'); if (docDeptEl) docDeptEl.value = doc.department || doc.designation || 'General Medicine';
        const docIdEl = document.getElementById('doc-id'); if (docIdEl) docIdEl.value = doc.id || '';
        const docFeeEl = document.getElementById('doc-fee'); if (docFeeEl) docFeeEl.value = parseFloat(doc.price || 500);

        // Also select in dropdown if exists
        const select = document.getElementById('doc-select');
        if (select) {
            let found = false;
            for (let i = 0; i < select.options.length; i++) {
                if (select.options[i].value === doc.name || (select.options[i].dataset && select.options[i].dataset.id === doc.id)) {
                    select.selectedIndex = i;
                    found = true;
                    break;
                }
            }
            if (!found) select.selectedIndex = 0;
        }

        calcDoctorTotal();
    }

    function onDoctorSelect(selectEl) {
        const opt = selectEl.options[selectEl.selectedIndex];
        if (opt && opt.value) {
            const docNameEl = document.getElementById('doc-name'); if (docNameEl) docNameEl.value = opt.value;
            const docDeptEl = document.getElementById('doc-dept'); if (docDeptEl) docDeptEl.value = opt.dataset.dept || 'General Medicine';
            const docIdEl = document.getElementById('doc-id'); if (docIdEl) docIdEl.value = opt.dataset.id || '';
            const docFeeEl = document.getElementById('doc-fee'); if (docFeeEl) docFeeEl.value = parseFloat(opt.dataset.fee || 500);
            const searchInp = document.getElementById('doc-search-input'); if (searchInp) searchInp.value = opt.value;
            calcDoctorTotal();
        }
    }

    // ── 1. Lab Test Methods (Table: lab_services) ──
    function selectLabItem(item) {
        if (!item) return;
        const res = document.getElementById('lab-results'); if(res) res.style.display = 'none';
        const inp = document.getElementById('lab-input'); if(inp) inp.value = item.name || '';

        const nameEl = document.getElementById('lab-name'); if (nameEl) nameEl.value = item.name || '';
        const codeEl = document.getElementById('lab-code'); if (codeEl) codeEl.value = item.id || '';
        const tierEl = document.getElementById('lab-tier'); if (tierEl) tierEl.value = item.room_tier ? `${item.room_tier} (₹${item.price})` : `Rate: ₹${item.price}`;
        const feeEl = document.getElementById('lab-fee'); if (feeEl) feeEl.value = parseFloat(item.price || 0);

        calcLabTotal();
    }

    function calcLabTotal() {
        const fee = parseFloat(document.getElementById('lab-fee')?.value) || 0;
        const disc = parseFloat(document.getElementById('lab-discount')?.value) || 0;
        const total = Math.max(0, fee - disc);
        const el = document.getElementById('lab-total-preview');
        if (el) el.textContent = `₹ ${total.toLocaleString('en-IN', { minimumFractionDigits: 2 })}`;
    }

    async function saveLabCharge() {
        if (!currentBillId || !currentAdmissionId) {
            showToast('Please select a patient bill first', 'warning'); return;
        }

        const date = document.getElementById('lab-date')?.value || new Date().toISOString().split('T')[0];
        const labName = (document.getElementById('lab-name')?.value || document.getElementById('lab-input')?.value || '').trim();
        const labCode = (document.getElementById('lab-code')?.value || '').trim();
        const fee = parseFloat(document.getElementById('lab-fee')?.value) || 0;
        const discount = parseFloat(document.getElementById('lab-discount')?.value) || 0;
        const notes = (document.getElementById('lab-notes')?.value || '').trim();

        if (!labName) {
            showToast('Please search and select a laboratory test', 'warning');
            const searchEl = document.getElementById('lab-input');
            if (searchEl) searchEl.focus();
            return;
        }

        const btn = document.getElementById('lab-save-btn');
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';

        try {
            const res = await fetch(`${API_URL}ipd-billing-items`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    action: 'add',
                    bill_id: currentBillId,
                    admission_id: currentAdmissionId,
                    patient_id: currentPatientId,
                    charge_date: date,
                    charge_type: 'LAB',
                    department: 'Laboratory',
                    description: labName,
                    item_code: labCode,
                    quantity: 1,
                    unit_price: fee,
                    discount_amt: discount,
                    reference_id: notes,
                    notes: notes,
                    force: true
                })
            });
            const json = await res.json();
            if (json.success) {
                showToast('Lab test charge added & synced to K-Sheet!', 'success');
                closeModal('modalAddCharge');
                loadAdmission(currentAdmissionId, currentPatientId);
                loadItems();
            } else {
                showToast(json.message || 'Failed to add lab test charge', 'error');
            }
        } catch (e) {
            console.error(e);
            showToast('Error saving lab test charge', 'error');
        } finally {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-plus"></i> Add Lab Test Charge';
        }
    }

    // ── 2. Radiology Methods (Table: radiology_services) ──
    function selectRadItem(item) {
        if (!item) return;
        const res = document.getElementById('rad-results'); if(res) res.style.display = 'none';
        const inp = document.getElementById('rad-input'); if(inp) inp.value = item.name || '';

        const nameEl = document.getElementById('rad-name'); if (nameEl) nameEl.value = item.name || '';
        const codeEl = document.getElementById('rad-code'); if (codeEl) codeEl.value = item.id || '';
        const tierEl = document.getElementById('rad-tier'); if (tierEl) tierEl.value = item.room_tier ? `${item.room_tier} (₹${item.price})` : `Rate: ₹${item.price}`;
        const feeEl = document.getElementById('rad-fee'); if (feeEl) feeEl.value = parseFloat(item.price || 0);

        calcRadTotal();
    }

    function calcRadTotal() {
        const fee = parseFloat(document.getElementById('rad-fee')?.value) || 0;
        const disc = parseFloat(document.getElementById('rad-discount')?.value) || 0;
        const total = Math.max(0, fee - disc);
        const el = document.getElementById('rad-total-preview');
        if (el) el.textContent = `₹ ${total.toLocaleString('en-IN', { minimumFractionDigits: 2 })}`;
    }

    async function saveRadCharge() {
        if (!currentBillId || !currentAdmissionId) {
            showToast('Please select a patient bill first', 'warning'); return;
        }

        const date = document.getElementById('rad-date')?.value || new Date().toISOString().split('T')[0];
        const radName = (document.getElementById('rad-name')?.value || document.getElementById('rad-input')?.value || '').trim();
        const radCode = (document.getElementById('rad-code')?.value || '').trim();
        const fee = parseFloat(document.getElementById('rad-fee')?.value) || 0;
        const discount = parseFloat(document.getElementById('rad-discount')?.value) || 0;
        const notes = (document.getElementById('rad-notes')?.value || '').trim();

        if (!radName) {
            showToast('Please search and select a radiology investigation', 'warning');
            const searchEl = document.getElementById('rad-input');
            if (searchEl) searchEl.focus();
            return;
        }

        const btn = document.getElementById('rad-save-btn');
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';

        try {
            const res = await fetch(`${API_URL}ipd-billing-items`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    action: 'add',
                    bill_id: currentBillId,
                    admission_id: currentAdmissionId,
                    patient_id: currentPatientId,
                    charge_date: date,
                    charge_type: 'RADIOLOGY',
                    department: 'Radiology',
                    description: radName,
                    item_code: radCode,
                    quantity: 1,
                    unit_price: fee,
                    discount_amt: discount,
                    reference_id: notes,
                    notes: notes,
                    force: true
                })
            });
            const json = await res.json();
            if (json.success) {
                showToast('Radiology charge added & synced to K-Sheet!', 'success');
                closeModal('modalAddCharge');
                loadAdmission(currentAdmissionId, currentPatientId);
                loadItems();
            } else {
                showToast(json.message || 'Failed to add radiology charge', 'error');
            }
        } catch (e) {
            console.error(e);
            showToast('Error saving radiology charge', 'error');
        } finally {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-plus"></i> Add Radiology Charge';
        }
    }

    // ── 3. Other Services Methods (Table: other_services) ──
    function selectOtherItem(item) {
        if (!item) return;
        const res = document.getElementById('other-results'); if(res) res.style.display = 'none';
        const inp = document.getElementById('other-input'); if(inp) inp.value = item.name || '';

        const nameEl = document.getElementById('other-name'); if (nameEl) nameEl.value = item.name || '';
        const tierEl = document.getElementById('other-tier'); if (tierEl) tierEl.value = item.room_tier ? `${item.room_tier} (₹${item.price})` : `Rate: ₹${item.price}`;
        const feeEl = document.getElementById('other-fee'); if (feeEl) feeEl.value = parseFloat(item.price || 0);

        calcOtherTotal();
    }

    function calcOtherTotal() {
        const qty = parseFloat(document.getElementById('other-qty')?.value) || 1;
        const fee = parseFloat(document.getElementById('other-fee')?.value) || 0;
        const disc = parseFloat(document.getElementById('other-discount')?.value) || 0;
        const total = Math.max(0, (qty * fee) - disc);
        const el = document.getElementById('other-total-preview');
        if (el) el.textContent = `₹ ${total.toLocaleString('en-IN', { minimumFractionDigits: 2 })}`;
    }

    async function saveOtherCharge() {
        if (!currentBillId || !currentAdmissionId) {
            showToast('Please select a patient bill first', 'warning'); return;
        }

        const date = document.getElementById('other-date')?.value || new Date().toISOString().split('T')[0];
        const otherName = (document.getElementById('other-name')?.value || document.getElementById('other-input')?.value || '').trim();
        const docName = (document.getElementById('proc-doctor')?.value || '').trim();
        const qty = parseFloat(document.getElementById('other-qty')?.value) || 1;
        const fee = parseFloat(document.getElementById('other-fee')?.value) || 0;
        const discount = parseFloat(document.getElementById('other-discount')?.value) || 0;
        const notes = (document.getElementById('other-notes')?.value || '').trim();

        if (!otherName) {
            showToast('Please search and select a hospital service', 'warning');
            const searchEl = document.getElementById('other-input');
            if (searchEl) searchEl.focus();
            return;
        }

        const btn = document.getElementById('other-save-btn');
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';

        try {
            const res = await fetch(`${API_URL}ipd-billing-items`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    action: 'add',
                    bill_id: currentBillId,
                    admission_id: currentAdmissionId,
                    patient_id: currentPatientId,
                    charge_date: date,
                    charge_type: 'PROCEDURE',
                    department: 'Other Services',
                    description: otherName,
                    doctor_name: docName,
                    quantity: qty,
                    unit_price: fee,
                    discount_amt: discount,
                    reference_id: notes,
                    notes: notes,
                    force: true
                })
            });
            const json = await res.json();
            if (json.success) {
                showToast('Service charge added & synced to K-Sheet!', 'success');
                closeModal('modalAddCharge');
                loadAdmission(currentAdmissionId, currentPatientId);
                loadItems();
            } else {
                showToast(json.message || 'Failed to add service charge', 'error');
            }
        } catch (e) {
            console.error(e);
            showToast('Error saving service charge', 'error');
        } finally {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-plus"></i> Add Service Charge';
        }
    }

    // ── Pharmacy Order Methods ──
    function addToPhCart(item) {
        const res = document.getElementById('ph-results'); if(res) res.style.display = 'none';
        const inp = document.getElementById('ph-input'); if(inp) inp.value = '';

        const price = parseFloat(item.price || 0);
        const ex = phCart.find(x => x.id === item.id);
        if (ex) {
            ex.qty++;
        } else {
            phCart.push({
                id: item.id || '',
                name: item.name || '',
                batch: item.batch || 'N/A',
                qty: 1,
                price: price
            });
        }
        renderPhCart();
    }

    function renderPhCart() {
        const ca = document.getElementById('ph-cart');
        if (!ca) return;
        if (!phCart.length) {
            ca.innerHTML = '<div style="padding:12px;text-align:center;color:#1f6b4a;opacity:0.7;font-size:.82rem;border:1.5px dashed #1f6b4a;border-radius:8px;">No medicines added to cart yet. Search above to add.</div>';
            calcPhCartTotal();
            return;
        }

        ca.innerHTML = phCart.map((m, idx) => `
            <div class="cart-row">
                <div class="cart-row-n">
                    ${escapeHtml(m.name)} 
                    <span class="badge">Batch: ${escapeHtml(m.batch)}</span>
                </div>
                <div style="display:flex; align-items:center; gap:8px;">
                    <div style="font-size:0.75rem; color:#1f6b4a; font-weight:700;">Qty:</div>
                    <input type="number" value="${m.qty}" min="0.01" step="0.01" onchange="billing.updatePhQty(${idx}, this.value)">
                    <div style="font-size:0.75rem; color:#1f6b4a; font-weight:700;">Rate (₹):</div>
                    <input type="number" value="${m.price}" min="0" step="0.01" style="width:85px;" onchange="billing.updatePhPrice(${idx}, this.value)">
                    <strong style="color:#1f6b4a; min-width:65px; text-align:right;">₹${(m.qty * m.price).toFixed(2)}</strong>
                    <button type="button" class="rm-btn" onclick="billing.removePhCartItem(${idx})" title="Remove"><i class="fas fa-trash-alt"></i></button>
                </div>
            </div>
        `).join('');

        calcPhCartTotal();
    }

    function updatePhQty(idx, val) {
        if (phCart[idx]) {
            phCart[idx].qty = Math.max(0.01, parseFloat(val) || 1);
            renderPhCart();
        }
    }

    function updatePhPrice(idx, val) {
        if (phCart[idx]) {
            phCart[idx].price = Math.max(0, parseFloat(val) || 0);
            renderPhCart();
        }
    }

    function removePhCartItem(idx) {
        phCart.splice(idx, 1);
        renderPhCart();
    }

    function calcPhCartTotal() {
        const total = phCart.reduce((sum, item) => sum + (item.qty * item.price), 0);
        const el = document.getElementById('ph-total-preview');
        if (el) el.textContent = `₹ ${total.toLocaleString('en-IN', { minimumFractionDigits: 2 })}`;
    }

    async function savePharmacyOrder() {
        if (!currentBillId || !currentAdmissionId) {
            showToast('Please select a patient bill first', 'warning'); return;
        }
        if (!phCart.length) {
            showToast('Please add at least one medicine to order.', 'warning'); return;
        }

        const date = document.getElementById('ph-date').value || new Date().toISOString().split('T')[0];
        const notes = document.getElementById('ph-notes').value.trim();
        const btn = document.getElementById('ph-save-btn');

        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Submitting...';

        try {
            for (const item of phCart) {
                await fetch(`${API_URL}ipd-billing-items`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        action: 'add',
                        bill_id: currentBillId,
                        admission_id: currentAdmissionId,
                        patient_id: currentPatientId,
                        charge_date: date,
                        charge_type: 'PHARMACY',
                        department: 'Pharmacy',
                        description: item.name,
                        item_code: item.id,
                        batch_number: item.batch,
                        quantity: item.qty,
                        unit_price: item.price,
                        discount_amt: 0,
                        reference_id: notes,
                        notes: notes,
                        force: true
                    })
                });
            }

            showToast('Pharmacy Order submitted & synced to K-Sheet!', 'success');
            phCart = [];
            renderPhCart();
            closeModal('modalAddCharge');
            loadAdmission(currentAdmissionId, currentPatientId);
            loadItems();
        } catch (e) {
            console.error(e);
            showToast('Error submitting pharmacy order', 'error');
        } finally {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-paper-plane"></i> Submit Pharmacy Order';
        }
    }

    // ── Doctor Visit Methods ──
    function onDoctorSelect(selectEl) {
        const opt = selectEl.options[selectEl.selectedIndex];
        if (opt && opt.dataset && opt.dataset.fee) {
            document.getElementById('doc-fee').value = parseFloat(opt.dataset.fee || 500);
            calcDoctorTotal();
        }
    }

    function calcDoctorTotal() {
        const fee = parseFloat(document.getElementById('doc-fee').value) || 0;
        const disc = parseFloat(document.getElementById('doc-discount').value) || 0;
        const total = Math.max(0, fee - disc);
        const el = document.getElementById('doc-total-preview');
        if (el) el.textContent = `₹ ${total.toLocaleString('en-IN', { minimumFractionDigits: 2 })}`;
    }

    async function saveDoctorVisitCharge() {
        if (!currentBillId || !currentAdmissionId) {
            showToast('Please select a patient bill first', 'warning'); return;
        }

        const date = document.getElementById('doc-date').value || new Date().toISOString().split('T')[0];
        const time = document.getElementById('doc-time').value || new Date().toTimeString().slice(0, 5);
        const doctorName = (document.getElementById('doc-name')?.value || document.getElementById('doc-select')?.value || document.getElementById('doc-search-input')?.value || '').trim();
        const docDept = (document.getElementById('doc-dept')?.value || 'Consultant Round').trim();
        const docId = (document.getElementById('doc-id')?.value || '').trim();
        const shift = document.getElementById('doc-shift').value;
        const fee = parseFloat(document.getElementById('doc-fee').value) || 0;
        const discount = parseFloat(document.getElementById('doc-discount').value) || 0;
        const notes = document.getElementById('doc-notes').value.trim();

        if (!doctorName) {
            showToast('Please search or select an attending doctor', 'warning');
            const searchEl = document.getElementById('doc-search-input');
            if (searchEl) searchEl.focus();
            return;
        }

        const btn = document.getElementById('doc-save-btn');
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';

        try {
            const res = await fetch(`${API_URL}ipd-billing-items`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    action: 'add',
                    bill_id: currentBillId,
                    admission_id: currentAdmissionId,
                    patient_id: currentPatientId,
                    charge_date: date,
                    charge_type: 'DOCTOR_VISIT',
                    department: docDept || 'Consultant Round',
                    description: doctorName,
                    doctor_name: doctorName,
                    item_code: docId,
                    visit_time: time,
                    shift_type: shift,
                    quantity: 1,
                    unit_price: fee,
                    discount_amt: discount,
                    reference_id: notes,
                    notes: notes,
                    force: true
                })
            });
            const json = await res.json();
            if (json.success) {
                showToast('Doctor round visit added & synced to K-Sheet!', 'success');
                closeModal('modalAddCharge');
                loadAdmission(currentAdmissionId, currentPatientId);
                loadItems();
            } else {
                showToast(json.message || 'Failed to add doctor visit', 'error');
            }
        } catch (e) {
            console.error(e);
            showToast('Error saving doctor visit', 'error');
        } finally {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-plus"></i> Add Round Visit Charge';
        }
    }

    // ── Procedure & OT Methods ──
    function selectProcItem(item) {
        const res = document.getElementById('proc-results'); if(res) res.style.display = 'none';
        document.getElementById('proc-input').value = item.name || '';
        document.getElementById('proc-fee').value = parseFloat(item.price || 0);
        calcProcedureTotal();
    }

    function calcProcedureTotal() {
        const qty = parseFloat(document.getElementById('proc-qty').value) || 0;
        const fee = parseFloat(document.getElementById('proc-fee').value) || 0;
        const disc = parseFloat(document.getElementById('proc-discount').value) || 0;
        const total = Math.max(0, (qty * fee) - disc);
        const el = document.getElementById('proc-total-preview');
        if (el) el.textContent = `₹ ${total.toLocaleString('en-IN', { minimumFractionDigits: 2 })}`;
    }

    async function saveProcedureCharge() {
        if (!currentBillId || !currentAdmissionId) {
            showToast('Please select a patient bill first', 'warning'); return;
        }

        const date = document.getElementById('proc-date').value || new Date().toISOString().split('T')[0];
        const procName = document.getElementById('proc-input').value.trim();
        const doctorName = document.getElementById('proc-doctor').value.trim();
        const setting = document.getElementById('proc-setting').value;
        const qty = parseFloat(document.getElementById('proc-qty').value) || 1;
        const fee = parseFloat(document.getElementById('proc-fee').value) || 0;
        const discount = parseFloat(document.getElementById('proc-discount').value) || 0;
        const notes = document.getElementById('proc-notes').value.trim();

        if (!procName) {
            showToast('Please enter procedure name', 'warning');
            document.getElementById('proc-input').focus();
            return;
        }

        const btn = document.getElementById('proc-save-btn');
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';

        try {
            const res = await fetch(`${API_URL}ipd-billing-items`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    action: 'add',
                    bill_id: currentBillId,
                    admission_id: currentAdmissionId,
                    patient_id: currentPatientId,
                    charge_date: date,
                    charge_type: 'PROCEDURE',
                    department: setting,
                    description: procName,
                    doctor_name: doctorName,
                    quantity: qty,
                    unit_price: fee,
                    discount_amt: discount,
                    reference_id: notes,
                    notes: notes,
                    force: true
                })
            });
            const json = await res.json();
            if (json.success) {
                showToast('Procedure charge added & synced to K-Sheet!', 'success');
                closeModal('modalAddCharge');
                loadAdmission(currentAdmissionId, currentPatientId);
                loadItems();
            } else {
                showToast(json.message || 'Failed to add procedure charge', 'error');
            }
        } catch (e) {
            console.error(e);
            showToast('Error saving procedure charge', 'error');
        } finally {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-plus"></i> Add Procedure Charge';
        }
    }

    // ── Helper Time Duration Calculator ──
    function diffHours(startTime, endTime) {
        if (!startTime || !endTime) return '';
        const [sh, sm] = startTime.split(':').map(Number);
        const [eh, em] = endTime.split(':').map(Number);
        let sMin = sh * 60 + sm;
        let eMin = eh * 60 + em;
        if (eMin < sMin) eMin += 24 * 60; // Next day
        const diff = eMin - sMin;
        const hrs = Math.floor(diff / 60);
        const mins = diff % 60;
        if (mins === 0) return `${hrs}h`;
        return `${hrs}h ${mins}m`;
    }

    // ── 5. Dialysis Methods (14. dialysis_chart) ──
    function calcDiaDuration() {
        const s = document.getElementById('dia-start')?.value;
        const e = document.getElementById('dia-end')?.value;
        const dur = diffHours(s, e);
        if (dur) {
            const el = document.getElementById('dia-dur');
            if (el) el.value = dur;
        }
    }

    function calcDiaTotal() {
        const fee = parseFloat(document.getElementById('dia-fee')?.value) || 0;
        const disc = parseFloat(document.getElementById('dia-discount')?.value) || 0;
        const total = Math.max(0, fee - disc);
        const el = document.getElementById('dia-total-preview');
        if (el) el.textContent = `₹ ${total.toLocaleString('en-IN', { minimumFractionDigits: 2 })}`;
    }

    async function saveDialysisCharge() {
        if (!currentBillId || !currentAdmissionId) {
            showToast('Please select a patient bill first', 'warning'); return;
        }
        const doctorName = (document.getElementById('dia-doctor')?.value || document.getElementById('dia-doc-search')?.value || '').trim();
        const date = document.getElementById('dia-date')?.value || new Date().toISOString().split('T')[0];
        const startTime = document.getElementById('dia-start')?.value || '';
        const endTime = document.getElementById('dia-end')?.value || '';
        const duration = document.getElementById('dia-dur')?.value.trim() || '4h';
        const diaType = document.getElementById('dia-type')?.value || 'Hemodialysis';
        const fee = parseFloat(document.getElementById('dia-fee')?.value) || 0;
        const discount = parseFloat(document.getElementById('dia-discount')?.value) || 0;
        const notes = document.getElementById('dia-notes')?.value.trim() || '';

        if (!doctorName) {
            showToast('Please search or select an attending nephrologist/doctor', 'warning');
            const el = document.getElementById('dia-doc-search'); if (el) el.focus();
            return;
        }

        const btn = document.getElementById('dia-save-btn');
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';

        try {
            const res = await fetch(`${API_URL}ipd-billing-items`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    action: 'add',
                    bill_id: currentBillId,
                    admission_id: currentAdmissionId,
                    patient_id: currentPatientId,
                    charge_date: date,
                    charge_type: 'DIALYSIS',
                    department: 'Dialysis Unit',
                    description: diaType,
                    doctor_name: doctorName,
                    start_time: startTime,
                    end_time: endTime,
                    duration: duration,
                    quantity: 1,
                    unit_price: fee,
                    discount_amt: discount,
                    reference_id: notes,
                    notes: notes,
                    force: true
                })
            });
            const json = await res.json();
            if (json.success) {
                showToast('Dialysis record added & synced to K-Sheet!', 'success');
                closeModal('modalAddCharge');
                loadAdmission(currentAdmissionId, currentPatientId);
                loadItems();
            } else {
                showToast(json.message || 'Failed to add dialysis charge', 'error');
            }
        } catch (e) {
            console.error(e);
            showToast('Error saving dialysis charge', 'error');
        } finally {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-plus"></i> Add Dialysis Charge';
        }
    }

    // ── 6. Oxygen Therapy Methods (15. oxygen_chart) ──
    function calcOxyDuration() {
        const s = document.getElementById('oxy-start')?.value;
        const e = document.getElementById('oxy-end')?.value;
        const dur = diffHours(s, e);
        if (dur) {
            const el = document.getElementById('oxy-dur');
            if (el) el.value = dur;
        }
    }

    function calcOxyTotal() {
        const fee = parseFloat(document.getElementById('oxy-fee')?.value) || 0;
        const disc = parseFloat(document.getElementById('oxy-discount')?.value) || 0;
        const total = Math.max(0, fee - disc);
        const el = document.getElementById('oxy-total-preview');
        if (el) el.textContent = `₹ ${total.toLocaleString('en-IN', { minimumFractionDigits: 2 })}`;
    }

    async function saveOxygenCharge() {
        if (!currentBillId || !currentAdmissionId) {
            showToast('Please select a patient bill first', 'warning'); return;
        }
        const doctorName = (document.getElementById('oxy-doctor')?.value || document.getElementById('oxy-doc-search')?.value || '').trim();
        const date = document.getElementById('oxy-date')?.value || new Date().toISOString().split('T')[0];
        const flowRate = document.getElementById('oxy-flow')?.value.trim() || '2 L/min';
        const device = document.getElementById('oxy-device')?.value || 'Nasal Cannula / Prongs';
        const startTime = document.getElementById('oxy-start')?.value || '';
        const endTime = document.getElementById('oxy-end')?.value || '';
        const duration = document.getElementById('oxy-dur')?.value.trim() || '2h';
        const fee = parseFloat(document.getElementById('oxy-fee')?.value) || 0;
        const discount = parseFloat(document.getElementById('oxy-discount')?.value) || 0;
        const notes = document.getElementById('oxy-notes')?.value.trim() || '';

        if (!doctorName) {
            showToast('Please search or select an attending doctor', 'warning');
            const el = document.getElementById('oxy-doc-search'); if (el) el.focus();
            return;
        }

        const btn = document.getElementById('oxy-save-btn');
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';

        try {
            const res = await fetch(`${API_URL}ipd-billing-items`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    action: 'add',
                    bill_id: currentBillId,
                    admission_id: currentAdmissionId,
                    patient_id: currentPatientId,
                    charge_date: date,
                    charge_type: 'OXYGEN',
                    department: 'Respiratory Care',
                    description: `Oxygen Therapy - ${device} (${flowRate})`,
                    doctor_name: doctorName,
                    flow_rate: flowRate,
                    start_time: startTime,
                    end_time: endTime,
                    duration: duration,
                    quantity: 1,
                    unit_price: fee,
                    discount_amt: discount,
                    reference_id: notes,
                    notes: notes,
                    force: true
                })
            });
            const json = await res.json();
            if (json.success) {
                showToast('Oxygen therapy added & synced to K-Sheet!', 'success');
                closeModal('modalAddCharge');
                loadAdmission(currentAdmissionId, currentPatientId);
                loadItems();
            } else {
                showToast(json.message || 'Failed to add oxygen charge', 'error');
            }
        } catch (e) {
            console.error(e);
            showToast('Error saving oxygen charge', 'error');
        } finally {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-plus"></i> Add Oxygen Charge';
        }
    }

    // ── 7. Ventilator Support Methods (16. ventilation_chart) ──
    function calcVentDuration() {
        const s = document.getElementById('vent-start')?.value;
        const e = document.getElementById('vent-end')?.value;
        const dur = diffHours(s, e);
        if (dur) {
            const el = document.getElementById('vent-dur');
            if (el) el.value = dur;
        }
    }

    function calcVentTotal() {
        const fee = parseFloat(document.getElementById('vent-fee')?.value) || 0;
        const disc = parseFloat(document.getElementById('vent-discount')?.value) || 0;
        const total = Math.max(0, fee - disc);
        const el = document.getElementById('vent-total-preview');
        if (el) el.textContent = `₹ ${total.toLocaleString('en-IN', { minimumFractionDigits: 2 })}`;
    }

    async function saveVentilatorCharge() {
        if (!currentBillId || !currentAdmissionId) {
            showToast('Please select a patient bill first', 'warning'); return;
        }
        const doctorName = (document.getElementById('vent-doctor')?.value || document.getElementById('vent-doc-search')?.value || '').trim();
        const date = document.getElementById('vent-date')?.value || new Date().toISOString().split('T')[0];
        const mode = document.getElementById('vent-mode')?.value || 'CPAP';
        const startTime = document.getElementById('vent-start')?.value || '';
        const endTime = document.getElementById('vent-end')?.value || '';
        const duration = document.getElementById('vent-dur')?.value.trim() || '6h';
        const fee = parseFloat(document.getElementById('vent-fee')?.value) || 0;
        const discount = parseFloat(document.getElementById('vent-discount')?.value) || 0;
        const notes = document.getElementById('vent-notes')?.value.trim() || '';

        if (!doctorName) {
            showToast('Please search or select an attending intensivist/doctor', 'warning');
            const el = document.getElementById('vent-doc-search'); if (el) el.focus();
            return;
        }

        const btn = document.getElementById('vent-save-btn');
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';

        try {
            const res = await fetch(`${API_URL}ipd-billing-items`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    action: 'add',
                    bill_id: currentBillId,
                    admission_id: currentAdmissionId,
                    patient_id: currentPatientId,
                    charge_date: date,
                    charge_type: 'VENTILATION',
                    department: 'Critical Care / ICU',
                    description: `Ventilator Support (${mode})`,
                    doctor_name: doctorName,
                    vent_mode: mode,
                    start_time: startTime,
                    end_time: endTime,
                    duration: duration,
                    quantity: 1,
                    unit_price: fee,
                    discount_amt: discount,
                    reference_id: notes,
                    notes: notes,
                    force: true
                })
            });
            const json = await res.json();
            if (json.success) {
                showToast('Ventilator support added & synced to K-Sheet!', 'success');
                closeModal('modalAddCharge');
                loadAdmission(currentAdmissionId, currentPatientId);
                loadItems();
            } else {
                showToast(json.message || 'Failed to add ventilator charge', 'error');
            }
        } catch (e) {
            console.error(e);
            showToast('Error saving ventilator charge', 'error');
        } finally {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-plus"></i> Add Ventilator Charge';
        }
    }

    // ── 8. Blood Transfusion Methods (17. blood_transfusion_chart) ──
    function calcBtTotal() {
        const fee = parseFloat(document.getElementById('bt-fee')?.value) || 0;
        const disc = parseFloat(document.getElementById('bt-discount')?.value) || 0;
        const total = Math.max(0, fee - disc);
        const el = document.getElementById('bt-total-preview');
        if (el) el.textContent = `₹ ${total.toLocaleString('en-IN', { minimumFractionDigits: 2 })}`;
    }

    async function saveTransfusionCharge() {
        if (!currentBillId || !currentAdmissionId) {
            showToast('Please select a patient bill first', 'warning'); return;
        }
        const doctorName = (document.getElementById('bt-doctor')?.value || document.getElementById('bt-doc-search')?.value || '').trim();
        const date = document.getElementById('bt-date')?.value || new Date().toISOString().split('T')[0];
        const group = document.getElementById('bt-group')?.value || 'O+';
        const component = document.getElementById('bt-comp')?.value || 'Packed Red Blood Cells (PRBC)';
        const bag = document.getElementById('bt-bag')?.value.trim() || '';
        const qty = parseFloat(document.getElementById('bt-qty')?.value) || 350;
        const vitals = document.getElementById('bt-vitals')?.value.trim() || '';
        const fee = parseFloat(document.getElementById('bt-fee')?.value) || 0;
        const discount = parseFloat(document.getElementById('bt-discount')?.value) || 0;
        const notes = document.getElementById('bt-notes')?.value.trim() || '';

        if (!doctorName) {
            showToast('Please search or select prescribing doctor', 'warning');
            const el = document.getElementById('bt-doc-search'); if (el) el.focus();
            return;
        }

        const btn = document.getElementById('bt-save-btn');
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';

        try {
            const res = await fetch(`${API_URL}ipd-billing-items`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    action: 'add',
                    bill_id: currentBillId,
                    admission_id: currentAdmissionId,
                    patient_id: currentPatientId,
                    charge_date: date,
                    charge_type: 'BLOOD_TRANSFUSION',
                    department: 'Blood Bank',
                    description: `Blood Transfusion - ${component} (${group}) [Bag: ${bag || 'N/A'}]`,
                    doctor_name: doctorName,
                    blood_group: group,
                    bag_number: bag,
                    trans_qty: qty,
                    vitals_during: vitals,
                    quantity: 1,
                    unit_price: fee,
                    discount_amt: discount,
                    reference_id: notes,
                    notes: notes,
                    force: true
                })
            });
            const json = await res.json();
            if (json.success) {
                showToast('Blood transfusion added & synced to K-Sheet!', 'success');
                closeModal('modalAddCharge');
                loadAdmission(currentAdmissionId, currentPatientId);
                loadItems();
            } else {
                showToast(json.message || 'Failed to add transfusion charge', 'error');
            }
        } catch (e) {
            console.error(e);
            showToast('Error saving transfusion charge', 'error');
        } finally {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-plus"></i> Add Transfusion Charge';
        }
    }

    // ── 9. Ward Transfer Methods (18. ward_transfer) ──
    function calcWtTotal() {
        const fee = parseFloat(document.getElementById('wt-fee')?.value) || 0;
        const disc = parseFloat(document.getElementById('wt-discount')?.value) || 0;
        const total = Math.max(0, fee - disc);
        const el = document.getElementById('wt-total-preview');
        if (el) el.textContent = `₹ ${total.toLocaleString('en-IN', { minimumFractionDigits: 2 })}`;
    }

    async function saveWardTransferCharge() {
        if (!currentBillId || !currentAdmissionId) {
            showToast('Please select a patient bill first', 'warning'); return;
        }
        const doctorName = (document.getElementById('wt-doctor')?.value || document.getElementById('wt-doc-search')?.value || '').trim();
        const date = document.getElementById('wt-date')?.value || new Date().toISOString().split('T')[0];
        const time = document.getElementById('wt-time')?.value || new Date().toTimeString().slice(0, 5);
        const fromWard = document.getElementById('wt-from')?.value.trim() || '';
        const toWard = document.getElementById('wt-to')?.value.trim() || '';
        const fee = parseFloat(document.getElementById('wt-fee')?.value) || 0;
        const discount = parseFloat(document.getElementById('wt-discount')?.value) || 0;
        const reason = document.getElementById('wt-reason')?.value.trim() || '';

        if (!doctorName) {
            showToast('Please search or select authorising doctor', 'warning');
            const el = document.getElementById('wt-doc-search'); if (el) el.focus();
            return;
        }

        const btn = document.getElementById('wt-save-btn');
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';

        try {
            const res = await fetch(`${API_URL}ipd-billing-items`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    action: 'add',
                    bill_id: currentBillId,
                    admission_id: currentAdmissionId,
                    patient_id: currentPatientId,
                    charge_date: date,
                    charge_type: 'WARD_TRANSFER',
                    department: 'Nursing Administration',
                    description: `Ward Transfer: ${fromWard || 'Ward'} → ${toWard || 'Ward'}`,
                    doctor_name: doctorName,
                    from_ward: fromWard,
                    to_ward: toWard,
                    visit_time: time,
                    quantity: 1,
                    unit_price: fee,
                    discount_amt: discount,
                    reference_id: reason,
                    notes: reason,
                    force: true
                })
            });
            const json = await res.json();
            if (json.success) {
                showToast('Ward transfer recorded & synced!', 'success');
                closeModal('modalAddCharge');
                loadAdmission(currentAdmissionId, currentPatientId);
                loadItems();
            } else {
                showToast(json.message || 'Failed to add ward transfer charge', 'error');
            }
        } catch (e) {
            console.error(e);
            showToast('Error saving ward transfer charge', 'error');
        } finally {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-plus"></i> Add Transfer Charge';
        }
    }

    // ── Consumables & Other Methods ──
    function calcConsumableTotal() {
        const qty = parseFloat(document.getElementById('misc-qty').value) || 0;
        const fee = parseFloat(document.getElementById('misc-fee').value) || 0;
        const disc = parseFloat(document.getElementById('misc-discount').value) || 0;
        const total = Math.max(0, (qty * fee) - disc);
        const el = document.getElementById('misc-total-preview');
        if (el) el.textContent = `₹ ${total.toLocaleString('en-IN', { minimumFractionDigits: 2 })}`;
    }

    async function saveConsumableCharge() {
        if (!currentBillId || !currentAdmissionId) {
            showToast('Please select a patient bill first', 'warning'); return;
        }

        const date = document.getElementById('misc-date').value || new Date().toISOString().split('T')[0];
        const type = document.getElementById('misc-type').value;
        const desc = document.getElementById('misc-desc').value.trim();
        const dept = document.getElementById('misc-dept').value.trim() || 'General';
        const qty = parseFloat(document.getElementById('misc-qty').value) || 1;
        const fee = parseFloat(document.getElementById('misc-fee').value) || 0;
        const discount = parseFloat(document.getElementById('misc-discount').value) || 0;
        const notes = document.getElementById('misc-notes').value.trim();

        if (!desc) {
            showToast('Please enter consumable / item description', 'warning');
            document.getElementById('misc-desc').focus();
            return;
        }

        const btn = document.getElementById('misc-save-btn');
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';

        try {
            const res = await fetch(`${API_URL}ipd-billing-items`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    action: 'add',
                    bill_id: currentBillId,
                    admission_id: currentAdmissionId,
                    patient_id: currentPatientId,
                    charge_date: date,
                    charge_type: type,
                    department: dept,
                    description: desc,
                    quantity: qty,
                    unit_price: fee,
                    discount_amt: discount,
                    reference_id: notes,
                    notes: notes,
                    force: true
                })
            });
            const json = await res.json();
            if (json.success) {
                showToast('Consumable charge added successfully!', 'success');
                closeModal('modalAddCharge');
                loadAdmission(currentAdmissionId, currentPatientId);
                loadItems();
            } else {
                showToast(json.message || 'Failed to add charge', 'error');
            }
        } catch (e) {
            console.error(e);
            showToast('Error saving consumable charge', 'error');
        } finally {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-plus"></i> Add Consumable Charge';
        }
    }

    // Close search dropdowns on global click
    document.addEventListener('click', function (e) {
        if (!e.target.closest('.catalog-search-wrap')) {
            document.querySelectorAll('.catalog-search-results').forEach(el => el.classList.remove('open'));
        }
    });

    function calcChargeTotal() {
        const catType = document.getElementById('chargeType') ? document.getElementById('chargeType').value : 'LAB';
        let qty = 1;
        let price = 0;
        let disc = 0;

        if (catType === 'LAB') {
            qty = parseFloat(document.getElementById('labQty').value) || 0;
            price = parseFloat(document.getElementById('labUnitPrice').value) || 0;
            disc = parseFloat(document.getElementById('labDiscount').value) || 0;
        } else if (catType === 'RADIOLOGY') {
            qty = parseFloat(document.getElementById('radQty').value) || 0;
            price = parseFloat(document.getElementById('radUnitPrice').value) || 0;
            disc = parseFloat(document.getElementById('radDiscount').value) || 0;
        } else if (catType === 'PHARMACY') {
            qty = parseFloat(document.getElementById('phQty').value) || 0;
            price = parseFloat(document.getElementById('phUnitPrice').value) || 0;
            disc = parseFloat(document.getElementById('phDiscount').value) || 0;
        } else if (catType === 'DOCTOR_VISIT') {
            qty = 1;
            price = parseFloat(document.getElementById('docUnitPrice').value) || 0;
            disc = parseFloat(document.getElementById('docDiscount').value) || 0;
        } else if (catType === 'OT' || catType === 'PROCEDURE') {
            qty = parseFloat(document.getElementById('procQty').value) || 0;
            price = parseFloat(document.getElementById('procUnitPrice').value) || 0;
            disc = parseFloat(document.getElementById('procDiscount').value) || 0;
        } else {
            qty = parseFloat(document.getElementById('miscQty').value) || 0;
            price = parseFloat(document.getElementById('miscUnitPrice').value) || 0;
            disc = parseFloat(document.getElementById('miscDiscount').value) || 0;
        }

        const total = Math.max(0, (qty * price) - disc);
        const preview = document.getElementById('chargeTotalPreview');
        if (preview) {
            preview.textContent = `₹ ${total.toLocaleString('en-IN', { minimumFractionDigits: 2 })}`;
        }
    }

    async function saveCharge() {
        if (!currentBillId || !currentAdmissionId) {
            showToast('Please select a patient bill first', 'warning');
            return;
        }

        const btn = document.getElementById('btnSaveCharge');
        const chargeType = document.getElementById('chargeType').value;
        const chargeDate = document.getElementById('chargeDate').value || new Date().toISOString().split('T')[0];

        let description = '';
        let itemCode = '';
        let department = '';
        let batchNumber = '';
        let doctorName = '';
        let shiftType = '';
        let quantity = 1;
        let unitPrice = 0;
        let discountAmt = 0;
        let notes = '';

        if (chargeType === 'LAB') {
            description = document.getElementById('labSearchInput').value.trim();
            itemCode = document.getElementById('labCode').value.trim();
            department = document.getElementById('labDept').value.trim() || 'Laboratory';
            quantity = parseFloat(document.getElementById('labQty').value) || 1;
            unitPrice = parseFloat(document.getElementById('labUnitPrice').value);
            discountAmt = parseFloat(document.getElementById('labDiscount').value) || 0;
            notes = document.getElementById('labNotes').value.trim();

            if (!description) {
                showToast('Please select or enter a Lab Test', 'warning');
                document.getElementById('labSearchInput').focus();
                return;
            }
        } else if (chargeType === 'RADIOLOGY') {
            description = document.getElementById('radSearchInput').value.trim();
            itemCode = document.getElementById('radCode').value.trim();
            department = document.getElementById('radDept').value.trim() || 'Radiology';
            quantity = parseFloat(document.getElementById('radQty').value) || 1;
            unitPrice = parseFloat(document.getElementById('radUnitPrice').value);
            discountAmt = parseFloat(document.getElementById('radDiscount').value) || 0;
            notes = document.getElementById('radNotes').value.trim();

            if (!description) {
                showToast('Please select or enter a Radiology Investigation', 'warning');
                document.getElementById('radSearchInput').focus();
                return;
            }
        } else if (chargeType === 'PHARMACY') {
            description = document.getElementById('phSearchInput').value.trim();
            itemCode = document.getElementById('phCode').value.trim();
            batchNumber = document.getElementById('phBatch').value.trim() || 'N/A';
            department = 'Pharmacy';
            quantity = parseFloat(document.getElementById('phQty').value) || 1;
            unitPrice = parseFloat(document.getElementById('phUnitPrice').value);
            discountAmt = parseFloat(document.getElementById('phDiscount').value) || 0;
            notes = document.getElementById('phNotes').value.trim();

            if (!description) {
                showToast('Please select or enter a Medication Name', 'warning');
                document.getElementById('phSearchInput').focus();
                return;
            }
        } else if (chargeType === 'DOCTOR_VISIT') {
            description = document.getElementById('docSearchInput').value.trim();
            doctorName = description;
            department = document.getElementById('docDept').value.trim() || 'General Medicine';
            shiftType = document.getElementById('docShift').value;
            quantity = 1;
            unitPrice = parseFloat(document.getElementById('docUnitPrice').value);
            discountAmt = parseFloat(document.getElementById('docDiscount').value) || 0;
            notes = document.getElementById('docNotes').value.trim();

            if (!description) {
                showToast('Please enter or select Visiting Doctor name', 'warning');
                document.getElementById('docSearchInput').focus();
                return;
            }
        } else if (chargeType === 'OT' || chargeType === 'PROCEDURE') {
            description = document.getElementById('procSearchInput').value.trim();
            doctorName = document.getElementById('procDoctor').value.trim();
            department = document.getElementById('procDept').value.trim() || (chargeType === 'OT' ? 'Operation Theatre' : 'Procedure');
            quantity = parseFloat(document.getElementById('procQty').value) || 1;
            unitPrice = parseFloat(document.getElementById('procUnitPrice').value);
            discountAmt = parseFloat(document.getElementById('procDiscount').value) || 0;
            notes = document.getElementById('procNotes').value.trim();

            if (!description) {
                showToast('Please enter Procedure name', 'warning');
                document.getElementById('procSearchInput').focus();
                return;
            }
        } else {
            // Consumables / Misc / Other
            description = document.getElementById('miscDesc').value.trim();
            department = document.getElementById('miscDept').value.trim() || ucfirst(chargeType.toLowerCase());
            quantity = parseFloat(document.getElementById('miscQty').value) || 1;
            unitPrice = parseFloat(document.getElementById('miscUnitPrice').value);
            discountAmt = parseFloat(document.getElementById('miscDiscount').value) || 0;
            notes = document.getElementById('miscNotes').value.trim();

            if (!description) {
                showToast('Please enter Item / Service Description', 'warning');
                document.getElementById('miscDesc').focus();
                return;
            }
        }

        if (isNaN(unitPrice) || unitPrice < 0) {
            showToast('Please enter a valid Rate / Unit Price', 'warning');
            return;
        }

        const data = {
            action: 'add',
            bill_id: currentBillId,
            admission_id: currentAdmissionId,
            patient_id: currentPatientId,
            charge_date: chargeDate,
            charge_type: chargeType,
            department: department,
            description: description,
            item_code: itemCode,
            batch_number: batchNumber,
            doctor_name: doctorName,
            shift_type: shiftType,
            quantity: quantity,
            unit_price: unitPrice,
            discount_amt: discountAmt,
            reference_id: notes,
            notes: notes,
            force: addChargeForce
        };

        btn.classList.add('loading');
        try {
            const res = await fetch(`${API_URL}ipd-billing-items`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            });
            const json = await res.json();

            if (json.success) {
                showToast('Charge added & synchronized to Kardex successfully', 'success');
                closeModal('modalAddCharge');

                // Update UI state
                if (json.data && json.data.financial) {
                    currentMaster = { ...currentMaster, ...json.data.financial };
                    updateWorkspaceUI();
                } else {
                    loadAdmission(currentAdmissionId, currentPatientId);
                }

                // Refresh list if viewing all or same category
                const activeTabEl = document.querySelector('.cat-tab.active');
                const activeTab = activeTabEl ? activeTabEl.dataset.type : '';
                if (!activeTab || activeTab === data.charge_type) {
                    loadItems(activeTab);
                }
            } else if (res.status === 409) { // Duplicate warning
                const warn = document.getElementById('chargeDupWarning');
                document.getElementById('chargeDupMsg').textContent = json.message;
                warn.style.display = 'flex';
            } else {
                showToast(json.message || 'Failed to add charge', 'error');
            }
        } catch (e) {
            console.error(e);
            showToast('Error saving charge', 'error');
        } finally {
            btn.classList.remove('loading');
        }
    }

    function forceAddCharge() {
        addChargeForce = true;
        document.getElementById('chargeDupWarning').style.display = 'none';
        saveCharge();
    }

    // ── 2. ROOM RENT GENERATOR ──
    function openRoomRentModal() {
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
    }

    let rrPreviewTimeout;
    function loadRoomRentPreview() {
        clearTimeout(rrPreviewTimeout);
        document.getElementById('rrPreview').innerHTML = `<div class="rr-preview-loading"><i data-lucide="loader-2" class="lucide-spin"></i> Generating preview...</div>`;
        document.getElementById('rrPreviewSummary').style.display = 'none';
        document.getElementById('btnConfirmRoomRent').disabled = true;

        rrPreviewTimeout = setTimeout(async () => {
            const f = document.getElementById('rrFromDate').value;
            const t = document.getElementById('rrToDate').value;
            if (!f || !t || f > t) {
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
                    if(window.lucide) lucide.createIcons();

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
    }

    async function confirmRoomRent() {
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

                currentMaster = { ...currentMaster, ...json.data.financial };
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
    }

    // ── 3. PAYMENT MODAL ──
    let currentPayMode = 'CASH';
    let currentPayType = 'PARTIAL';

    function openPaymentModal(suggestedType = 'PARTIAL') {
        if (!currentMaster) return;

        document.getElementById('payDate').value = new Date().toISOString().split('T')[0];
        const bal = parseFloat(currentMaster.balance_due) || 0;
        document.getElementById('payAmount').value = bal > 0 ? bal.toFixed(2) : '';
        document.getElementById('payRef').value = '';
        document.getElementById('payRemarks').value = '';

        document.getElementById('payBalanceVal').textContent = `₹${bal.toLocaleString('en-IN', { minimumFractionDigits: 2 })}`;

        // Smart Type Suggestion
        if (parseFloat(currentMaster.amount_paid) === 0) {
            suggestedType = 'ADVANCE';
        } else if (bal <= 0) {
            suggestedType = 'FINAL';
        }

        const ptGroup = document.getElementById('payTypeGroup');
        if (ptGroup) {
            ptGroup.querySelectorAll('.pay-type-btn').forEach(b => {
                b.classList.remove('active');
                if (b.dataset.type === suggestedType) b.classList.add('active');
            });
        }
        currentPayType = suggestedType;

        const pmGroup = document.getElementById('payModeGroup');
        if (pmGroup) {
            pmGroup.querySelectorAll('.pay-mode-btn').forEach(b => {
                b.classList.remove('active');
                if (b.dataset.mode === 'CASH') b.classList.add('active');
            });
        }
        currentPayMode = 'CASH';

        const curDiscAmt = parseFloat(currentMaster.discount_amount) || 0;
        const curDiscPct = parseFloat(currentMaster.discount_percentage) || 0;
        const curNotes = currentMaster.notes || '';
        if (document.getElementById('modalPayDiscount')) document.getElementById('modalPayDiscount').value = curDiscAmt > 0 ? curDiscAmt.toFixed(2) : '';
        if (document.getElementById('modalPayDiscountPct')) document.getElementById('modalPayDiscountPct').value = curDiscPct > 0 ? curDiscPct.toFixed(1) : '';
        if (document.getElementById('modalPayDiscountReason')) document.getElementById('modalPayDiscountReason').value = curNotes;

        togglePaymentFields();
        updatePayPreview();
        openModal('modalPayment');
    }

    function calcModalDiscountAmt() {
        if (!currentMaster) return;
        const sub = parseFloat(currentMaster.subtotal) || 0;
        const amt = parseFloat(document.getElementById('modalPayDiscount').value) || 0;
        if (sub > 0 && amt <= sub) {
            document.getElementById('modalPayDiscountPct').value = ((amt / sub) * 100).toFixed(1);
        } else if (amt > sub) {
            document.getElementById('modalPayDiscount').value = sub.toFixed(2);
            document.getElementById('modalPayDiscountPct').value = '100';
        } else {
            document.getElementById('modalPayDiscountPct').value = '';
        }
        updatePayPreview();
    }

    function calcModalDiscountPct() {
        if (!currentMaster) return;
        const sub = parseFloat(currentMaster.subtotal) || 0;
        const pct = parseFloat(document.getElementById('modalPayDiscountPct').value) || 0;
        if (pct >= 0 && pct <= 100) {
            document.getElementById('modalPayDiscount').value = (sub * pct / 100).toFixed(2);
        } else if (pct > 100) {
            document.getElementById('modalPayDiscountPct').value = '100';
            document.getElementById('modalPayDiscount').value = sub.toFixed(2);
        } else {
            document.getElementById('modalPayDiscount').value = '';
        }
        updatePayPreview();
    }

    // Event delegation for Pay Type
    document.getElementById('payTypeGroup').addEventListener('click', function (e) {
        const btn = e.target.closest('.pay-type-btn');
        if (btn) {
            document.getElementById('payTypeGroup').querySelectorAll('.pay-type-btn').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            currentPayType = btn.dataset.type;
            togglePaymentFields();
        }
    });

    // Event delegation for Pay Mode
    document.getElementById('payModeGroup').addEventListener('click', function (e) {
        const btn = e.target.closest('.pay-mode-btn');
        if (btn) {
            document.getElementById('payModeGroup').querySelectorAll('.pay-mode-btn').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            currentPayMode = btn.dataset.mode;
            togglePaymentFields();
        }
    });

    function togglePaymentFields() {
        const refGrp = document.getElementById('payRefGroup');
        const refInp = document.getElementById('payRef');
        const insBlock = document.getElementById('modalInsuranceBlock');

        if (currentPayMode === 'CASH' || currentPayMode === 'INSURANCE') {
            refGrp.style.display = 'none';
        } else {
            refGrp.style.display = 'block';
            if (currentPayMode === 'UPI') refInp.placeholder = 'UPI Txn ID (e.g. PhonePe)';
            if (currentPayMode === 'CARD') refInp.placeholder = 'Card Auth Code / Last 4 digits';
            if (currentPayMode === 'BANK') refInp.placeholder = 'NEFT/RTGS UTR';
            if (currentPayMode === 'CHEQUE') refInp.placeholder = 'Cheque No. & Bank Name';
        }

        if (insBlock) {
            if (currentPayMode === 'INSURANCE') {
                insBlock.style.display = 'block';
                fetchSponsors('', currentModalSponsorType, 'modalSponsorResults', 'modalSponsorSearchInput', 'modalSelectedSponsorName');
            } else {
                insBlock.style.display = 'none';
            }
        }

        const refExtra = document.getElementById('refundExtraFields');
        if (currentPayType === 'REFUND') {
            refExtra.style.display = 'block';
        } else {
            refExtra.style.display = 'none';
        }
    }

    function fillFullAmount() {
        if (!currentMaster) return;
        const sub = parseFloat(currentMaster.subtotal) || 0;
        const disc = parseFloat(document.getElementById('modalPayDiscount')?.value) || parseFloat(currentMaster.discount_amount) || 0;
        const effectiveGrand = Math.max(0, sub - disc);
        const paid = parseFloat(currentMaster.amount_paid) || 0;
        const insRcvd = parseFloat(currentMaster.insurance_received_amount) || 0;
        const bal = Math.max(0, effectiveGrand - paid - insRcvd);

        document.getElementById('payAmount').value = bal > 0 ? bal.toFixed(2) : '0.00';
        if (currentPayType === 'PARTIAL') {
            document.querySelectorAll('.pay-type-btn').forEach(b => {
                b.classList.remove('active');
                if (b.dataset.type === 'FINAL') { b.classList.add('active'); currentPayType = 'FINAL'; }
            });
        }
        updatePayPreview();
    }
    window.fillFullAmount = fillFullAmount;

    function updatePayPreview() {
        if (!currentMaster) return;
        const sub = parseFloat(currentMaster.subtotal) || 0;
        const disc = parseFloat(document.getElementById('modalPayDiscount')?.value) || 0;
        const effectiveGrand = Math.max(0, sub - disc);
        const paid = parseFloat(currentMaster.amount_paid) || 0;
        const insRcvd = parseFloat(currentMaster.insurance_received_amount) || 0;
        const bal = Math.max(0, effectiveGrand - paid - insRcvd);

        const amt = parseFloat(document.getElementById('payAmount').value) || 0;
        const preview = document.getElementById('payAfterVal');
        if (!preview) return;

        if (currentPayType === 'REFUND') {
            preview.textContent = `₹${(bal + amt).toLocaleString('en-IN', { minimumFractionDigits: 2 })}`;
            preview.style.color = 'var(--red)';
        } else {
            const newBal = bal - amt;
            preview.textContent = `₹${Math.max(0, newBal).toLocaleString('en-IN', { minimumFractionDigits: 2 })}`;
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
    }

    async function savePayment() {
        if (isSavingModalPayment) return;

        const amt = parseFloat(document.getElementById('payAmount').value) || 0;
        const discAmt = parseFloat(document.getElementById('modalPayDiscount')?.value) || 0;
        const discPct = parseFloat(document.getElementById('modalPayDiscountPct')?.value) || 0;
        const discReason = document.getElementById('modalPayDiscountReason')?.value.trim() || '';

        const curMasterDiscAmt = parseFloat(currentMaster.discount_amount) || 0;
        const isDiscountChanged = (discAmt !== curMasterDiscAmt);

        if (amt <= 0 && !isDiscountChanged) {
            showToast('Enter valid amount or discount', 'warning'); return;
        }

        if (isDiscountChanged && discAmt > 0 && !discReason) {
            showToast('Discount reason is required', 'warning'); return;
        }

        if (amt > 0 && currentPayMode !== 'CASH' && currentPayMode !== 'INSURANCE' && !document.getElementById('payRef').value.trim()) {
            showToast('Reference No. is required for non-cash modes', 'warning'); return;
        }

        let sponsorName = null;
        if (amt > 0 && currentPayMode === 'INSURANCE') {
            sponsorName = document.getElementById('modalSelectedSponsorName')?.value || document.getElementById('modalSponsorSearchInput')?.value.trim();
            if (!sponsorName) {
                showToast('Please select or search an Insurance / TPA sponsor', 'warning');
                return;
            }
        }

        let refundReason = null;
        let approvedBy = null;
        if (amt > 0 && currentPayType === 'REFUND') {
            refundReason = document.getElementById('refundReason').value.trim();
            approvedBy = document.getElementById('refundApprovedBy').value.trim();
            if (!refundReason || !approvedBy) {
                showToast('Refund reason and approval auth required', 'warning'); return;
            }
        }

        isSavingModalPayment = true;
        const btn = document.getElementById('btnSavePayment');
        if (btn) {
            btn.disabled = true;
            btn.classList.add('loading');
            btn.style.pointerEvents = 'none';
            btn.style.opacity = '0.7';
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
        }

        try {
            // Apply discount if changed
            if (isDiscountChanged) {
                const discRes = await fetch(`${API_URL}ipd-billing-master`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        action: 'discount',
                        bill_id: currentBillId,
                        discount_amount: discAmt,
                        discount_percentage: discPct,
                        reason: discReason
                    })
                });
                const discJson = await discRes.json();
                if (discJson.success) {
                    currentMaster = discJson.data;
                } else {
                    showToast(discJson.message || 'Failed to update discount', 'error');
                    return;
                }
            }

            if (amt > 0) {
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
                        remarks: sponsorName ? `Sponsor: ${sponsorName} (${currentModalSponsorType})` + (document.getElementById('payRemarks').value ? ` | ${document.getElementById('payRemarks').value}` : '') : document.getElementById('payRemarks').value,
                        refund_reason: refundReason,
                        approved_by: approvedBy
                    })
                });
                const json = await res.json();

                if (json.success) {
                    showToast('Payment recorded successfully', 'success');
                    closeModal('modalPayment');

                    currentMaster = { ...currentMaster, ...json.data.financial };
                } else {
                    showToast(json.message, 'error');
                }
            } else if (isDiscountChanged) {
                showToast('Discount updated successfully', 'success');
                closeModal('modalPayment');
            }

            updateWorkspaceUI();
            loadPayments();
        } catch (e) {
            showToast('Error saving payment/discount', 'error');
        } finally {
            isSavingModalPayment = false;
            if (btn) {
                btn.disabled = false;
                btn.classList.remove('loading');
                btn.style.pointerEvents = 'auto';
                btn.style.opacity = '1';
                btn.innerHTML = '<i data-lucide="check-circle-2"></i> Record Payment';
                if (window.lucide) lucide.createIcons();
            }
        }
    };

    // ─────────────────────────────────────────────────────────────
    // INLINE PAYMENT (ALWAYS VISIBLE BY DEFAULT IN WORKSPACE)
    // ─────────────────────────────────────────────────────────────
    function initInlinePayment() {
        const typeGrp = document.getElementById('inlinePayTypeGroup');
        if (typeGrp) {
            typeGrp.addEventListener('click', function (e) {
                const btn = e.target.closest('.pay-type-btn');
                if (btn) {
                    typeGrp.querySelectorAll('.pay-type-btn').forEach(b => b.classList.remove('active'));
                    btn.classList.add('active');
                    currentInlinePayType = btn.dataset.type;

                    // If user selects Advance and amount is empty/zero, suggest initial deposit
                    if (currentInlinePayType === 'ADVANCE') {
                        const amtInp = document.getElementById('inlinePayAmount');
                        if (amtInp && (!amtInp.value || parseFloat(amtInp.value) <= 0)) {
                            const bal = parseFloat(currentMaster?.balance_due) || 0;
                            amtInp.value = bal > 0 ? bal.toFixed(2) : '5000.00';
                            amtInp.select();
                        }
                    }

                    toggleInlinePaymentFields();
                    updateInlinePayPreview();
                }
            });
        }

        const modeGrp = document.getElementById('inlinePayModeGroup');
        if (modeGrp) {
            modeGrp.addEventListener('click', function (e) {
                const btn = e.target.closest('.pay-mode-btn');
                if (btn) {
                    modeGrp.querySelectorAll('.pay-mode-btn').forEach(b => b.classList.remove('active'));
                    btn.classList.add('active');
                    currentInlinePayMode = btn.dataset.mode;

                    toggleInlinePaymentFields();
                    if (currentInlinePayMode !== 'INSURANCE') {
                        updateInlinePayPreview();
                    }
                }
            });
        }
    }

    function setQuickAdvance(amt) {
        const amtInp = document.getElementById('inlinePayAmount');
        if (amtInp) {
            amtInp.value = parseFloat(amt).toFixed(2);
        }
        const typeGrp = document.getElementById('inlinePayTypeGroup');
        if (typeGrp) {
            typeGrp.querySelectorAll('.pay-type-btn').forEach(b => {
                b.classList.remove('active');
                if (b.dataset.type === 'ADVANCE') b.classList.add('active');
            });
            currentInlinePayType = 'ADVANCE';
        }
        updateInlinePayPreview();
        showToast(`Advance deposit set to ₹${parseFloat(amt).toLocaleString('en-IN')}`, 'info');
    }

    function syncInlineApprovedAmount(val) {}
    function applyApprovedToPayment() {}

    function toggleInlinePaymentFields() {
        const refGrp = document.getElementById('inlinePayRefGroup');
        const refInp = document.getElementById('inlinePayRef');
        const insBlock = document.getElementById('inlineInsuranceBlock');
        const payTypeWrap = document.getElementById('inlinePayTypeGroupWrap') || document.getElementById('inlinePayTypeGroup')?.closest('.bm-form-group');
        const payAmtRow = document.getElementById('inlinePayAmountRow') || document.getElementById('inlinePayAmount')?.closest('.bm-form-row');
        const payAfterWrap = document.getElementById('inlinePayAfterWrap') || document.getElementById('inlinePayAfterVal')?.parentElement;
        const amtInp = document.getElementById('inlinePayAmount');
        const saveBtn = document.getElementById('btnSaveInlinePayment');
        const activeCard = document.getElementById('inlineActiveSponsorCard');
        const activeText = document.getElementById('inlineActiveSponsorText');

        if (currentInlinePayMode === 'INSURANCE') {
            // 1. Show Insurance details block
            if (insBlock) insBlock.style.display = 'block';

            // 2. Hide payment transaction fields (payment type, payment amount, ref, balance preview)
            if (payTypeWrap) payTypeWrap.style.display = 'none';
            if (payAmtRow) payAmtRow.style.display = 'none';
            if (payAfterWrap) payAfterWrap.style.display = 'none';
            if (refGrp) refGrp.style.display = 'none';
            if (amtInp) amtInp.value = '';

            // 3. Check if patient has existing active sponsor attached
            const existingName = currentMaster ? (currentMaster.insurance_company_name || currentMaster.tpa_name || currentMaster.sponsor || '') : '';
            const hasActiveSponsor = Boolean(existingName && existingName !== 'SELF' && currentMaster?.bill_type === 'INSURANCE');

            if (activeCard) {
                if (hasActiveSponsor) {
                    activeCard.style.display = 'flex';
                    if (activeText) activeText.textContent = `${existingName} (Type: ${currentMaster.credit_type || currentMaster.insurance_type || currentInlineSponsorType})`;
                } else {
                    activeCard.style.display = 'none';
                }
            }

            // 4. Set button label
            if (saveBtn) {
                saveBtn.innerHTML = hasActiveSponsor 
                    ? '<i class="fas fa-shield-alt" style="margin-right: 6px;"></i> Update Insurance Details' 
                    : '<i class="fas fa-shield-alt" style="margin-right: 6px;"></i> Save Insurance Details';
            }

            // 5. Pre-fill existing patient insurance sponsor name & policy details if available
            if (currentMaster) {
                const spInp = document.getElementById('inlineSponsorSearchInput');
                const spHid = document.getElementById('inlineSelectedSponsorName');
                const polInp = document.getElementById('inlinePolicyNumber');
                const clmInp = document.getElementById('inlineClaimNumber');

                if (spInp && !spInp.value && existingName && existingName !== 'SELF') {
                    spInp.value = existingName;
                    if (spHid) spHid.value = existingName;
                }
                if (polInp && !polInp.value && currentMaster.policy_number) {
                    polInp.value = currentMaster.policy_number;
                }
                if (clmInp && !clmInp.value && (currentMaster.claim_number || currentMaster.approval_number)) {
                    clmInp.value = currentMaster.claim_number || currentMaster.approval_number;
                }
            }

            fetchSponsors('', currentInlineSponsorType, 'inlineSponsorResults', 'inlineSponsorSearchInput', 'inlineSelectedSponsorName');
        } else {
            // Non-Insurance modes (CASH, UPI, CARD, BANK, CHEQUE)
            if (insBlock) insBlock.style.display = 'none';
            if (payTypeWrap) payTypeWrap.style.display = 'block';
            if (payAmtRow) payAmtRow.style.display = 'flex';
            if (payAfterWrap) payAfterWrap.style.display = 'block';

            if (currentInlinePayMode === 'CASH') {
                if (refGrp) refGrp.style.display = 'none';
            } else {
                if (refGrp) {
                    refGrp.style.display = 'block';
                    if (currentInlinePayMode === 'UPI') refInp.placeholder = 'UPI Txn ID (e.g. PhonePe)';
                    if (currentInlinePayMode === 'CARD') refInp.placeholder = 'Card Auth Code / Last 4 digits';
                    if (currentInlinePayMode === 'BANK') refInp.placeholder = 'NEFT/RTGS UTR';
                    if (currentInlinePayMode === 'CHEQUE') refInp.placeholder = 'Cheque No. & Bank Name';
                }
            }

            const amtLabel = document.getElementById('inlineAmountLabel');
            if (amtLabel) amtLabel.innerHTML = 'Amount (₹) <span class="req">*</span>';

            if (saveBtn) {
                saveBtn.innerHTML = '<i data-lucide="check-circle-2" style="width: 18px; height: 18px;"></i> Record Payment';
                if (window.lucide) lucide.createIcons();
            }
        }

        const refExtra = document.getElementById('inlineRefundExtraFields');
        if (refExtra) {
            refExtra.style.display = (currentInlinePayType === 'REFUND' && currentInlinePayMode !== 'INSURANCE') ? 'block' : 'none';
        }
    }

    function focusChangeInsurance() {
        const spInp = document.getElementById('inlineSponsorSearchInput');
        const spHid = document.getElementById('inlineSelectedSponsorName');
        if (spInp) {
            spInp.value = '';
            if (spHid) spHid.value = '';
            spInp.focus();
            fetchSponsors('', currentInlineSponsorType, 'inlineSponsorResults', 'inlineSponsorSearchInput', 'inlineSelectedSponsorName');
        }
        showToast('Type to search and select a new Insurance Company or TPA', 'info');
    }

    function cancelInsurance() {
        if (!currentMaster || !currentBillId) return;

        const sponsorName = currentMaster.sponsor || currentMaster.company_name || 'Insurance';
        const elName = document.getElementById('cancelInsSponsorName');
        const elPrompt = document.getElementById('cancelInsSponsorPromptName');
        if (elName) elName.textContent = sponsorName;
        if (elPrompt) elPrompt.textContent = sponsorName;

        openModal('modalCancelInsurance');
    }

    async function confirmCancelInsurance() {
        if (!currentMaster || !currentBillId) return;

        const btn = document.getElementById('btnConfirmCancelInsurance');
        if (btn) {
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin" style="margin-right: 4px;"></i> Cancelling...';
        }

        try {
            const res = await fetch(`${API_URL}ipd-insurance`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    action: 'cancel',
                    bill_id: currentBillId
                })
            });
            const json = await res.json();
            if (json.success) {
                showToast('Insurance cancelled successfully. Bill reverted to Self-Pay / Cash.', 'success');
                closeModal('modalCancelInsurance');

                // Clear sponsor fields in DOM
                const spInp = document.getElementById('inlineSponsorSearchInput');
                const spHid = document.getElementById('inlineSelectedSponsorName');
                const polInp = document.getElementById('inlinePolicyNumber');
                const clmInp = document.getElementById('inlineClaimNumber');
                if (spInp) spInp.value = '';
                if (spHid) spHid.value = '';
                if (polInp) polInp.value = '';
                if (clmInp) clmInp.value = '';

                // Switch UI back to Cash mode
                currentInlinePayMode = 'CASH';
                const modeGrp = document.getElementById('inlinePayModeGroup');
                if (modeGrp) {
                    modeGrp.querySelectorAll('.pay-mode-btn').forEach(b => {
                        b.classList.remove('active');
                        if (b.dataset.mode === 'CASH') b.classList.add('active');
                    });
                }

                // Reload fresh master record
                const mRes = await fetch(`${API_URL}ipd-billing-master?bill_id=${currentBillId}&_t=${Date.now()}`);
                const mJson = await mRes.json();
                if (mJson.success) currentMaster = mJson.data;

                toggleInlinePaymentFields();
                updateWorkspaceUI();
                loadPayments();
            } else {
                showToast(json.message || 'Failed to cancel insurance', 'error');
            }
        } catch (e) {
            console.error(e);
            showToast('Error cancelling insurance', 'error');
        } finally {
            if (btn) {
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-check-circle" style="margin-right: 4px;"></i> Yes, Cancel & Revert';
            }
        }
    }

    function calcInlineDiscountAmt() {
        if (!currentMaster) return;
        const sub = parseFloat(currentMaster.subtotal) || 0;
        const amt = parseFloat(document.getElementById('inlinePayDiscount').value) || 0;
        if (sub > 0 && amt <= sub) {
            document.getElementById('inlinePayDiscountPct').value = ((amt / sub) * 100).toFixed(1);
        } else if (amt > sub) {
            document.getElementById('inlinePayDiscount').value = sub.toFixed(2);
            document.getElementById('inlinePayDiscountPct').value = '100';
        } else {
            document.getElementById('inlinePayDiscountPct').value = '';
        }
        updateInlinePayPreview();
    }

    function calcInlineDiscountPct() {
        if (!currentMaster) return;
        const sub = parseFloat(currentMaster.subtotal) || 0;
        const pct = parseFloat(document.getElementById('inlinePayDiscountPct').value) || 0;
        if (pct >= 0 && pct <= 100) {
            document.getElementById('inlinePayDiscount').value = (sub * pct / 100).toFixed(2);
        } else if (pct > 100) {
            document.getElementById('inlinePayDiscountPct').value = '100';
            document.getElementById('inlinePayDiscount').value = sub.toFixed(2);
        } else {
            document.getElementById('inlinePayDiscount').value = '';
        }
        updateInlinePayPreview();
    }

    function fillInlineFullAmount() {
        if (!currentMaster) return;
        const sub = parseFloat(currentMaster.subtotal) || 0;
        const disc = parseFloat(document.getElementById('inlinePayDiscount')?.value) || parseFloat(currentMaster.discount_amount) || 0;
        const effectiveGrand = Math.max(0, sub - disc);
        const paid = parseFloat(currentMaster.amount_paid) || 0;
        const insRcvd = parseFloat(currentMaster.insurance_received_amount) || 0;
        const bal = Math.max(0, effectiveGrand - paid - insRcvd);

        document.getElementById('inlinePayAmount').value = bal > 0 ? bal.toFixed(2) : '0.00';
        if (currentInlinePayType === 'PARTIAL') {
            const typeGrp = document.getElementById('inlinePayTypeGroup');
            if (typeGrp) {
                typeGrp.querySelectorAll('.pay-type-btn').forEach(b => {
                    b.classList.remove('active');
                    if (b.dataset.type === 'FINAL') { b.classList.add('active'); currentInlinePayType = 'FINAL'; }
                });
            }
        }
        updateInlinePayPreview();
    }

    function updateInlinePayPreview() {
        if (!currentMaster) return;
        const sub = parseFloat(currentMaster.subtotal) || 0;
        const disc = parseFloat(document.getElementById('inlinePayDiscount')?.value) || 0;
        const effectiveGrand = Math.max(0, sub - disc);
        const paid = parseFloat(currentMaster.amount_paid) || 0;
        const insRcvd = parseFloat(currentMaster.insurance_received_amount) || 0;
        const bal = Math.max(0, effectiveGrand - paid - insRcvd);

        const amt = parseFloat(document.getElementById('inlinePayAmount')?.value) || 0;
        const preview = document.getElementById('inlinePayAfterVal');
        if (preview) {
            if (currentInlinePayType === 'REFUND') {
                preview.textContent = `₹${(bal + amt).toLocaleString('en-IN', { minimumFractionDigits: 2 })}`;
                preview.style.color = '#dc2626';
            } else {
                const newBal = bal - amt;
                preview.textContent = `₹${Math.max(0, newBal).toLocaleString('en-IN', { minimumFractionDigits: 2 })}`;
                if (newBal < 0) {
                    preview.textContent += ` (Overpayment: ₹${Math.abs(newBal).toFixed(2)})`;
                    preview.style.color = '#d97706';
                } else if (newBal === 0) {
                    preview.textContent += ' ✅';
                    preview.style.color = '#166534';
                } else {
                    preview.style.color = '#1f6b4a';
                }
            }
        }

        const saveBtn = document.getElementById('btnSaveInlinePayment');
        if (saveBtn) {
            if (currentInlinePayMode === 'INSURANCE') {
                saveBtn.innerHTML = '<i class="fas fa-shield-alt" style="margin-right: 6px;"></i> Save Insurance Details';
            } else {
                saveBtn.innerHTML = '<i data-lucide="check-circle-2" style="width: 18px; height: 18px;"></i> Record Payment';
                if (window.lucide) lucide.createIcons();
            }
        }
    }

    function updateInlinePaymentUI() {
        if (!currentMaster) return;
        const balEl = document.getElementById('inlinePayBalanceVal');
        const bal = parseFloat(currentMaster.balance_due) || 0;
        if (balEl) balEl.textContent = `₹${bal.toLocaleString('en-IN', { minimumFractionDigits: 2 })}`;

        const dateInp = document.getElementById('inlinePayDate');
        if (dateInp && !dateInp.value) {
            dateInp.value = new Date().toISOString().split('T')[0];
        }

        // Populate Discount fields
        const curDiscAmt = parseFloat(currentMaster.discount_amount) || 0;
        const curDiscPct = parseFloat(currentMaster.discount_percentage) || 0;
        const curNotes = currentMaster.notes || '';
        if (document.getElementById('inlinePayDiscount')) document.getElementById('inlinePayDiscount').value = curDiscAmt > 0 ? curDiscAmt.toFixed(2) : '';
        if (document.getElementById('inlinePayDiscountPct')) document.getElementById('inlinePayDiscountPct').value = curDiscPct > 0 ? curDiscPct.toFixed(1) : '';
        if (document.getElementById('inlinePayDiscountReason')) document.getElementById('inlinePayDiscountReason').value = curNotes;

        // Smart Suggestion
        let suggested = 'PARTIAL';
        if (parseFloat(currentMaster.amount_paid) === 0) suggested = 'ADVANCE';
        else if (bal <= 0) suggested = 'FINAL';

        const typeGrp = document.getElementById('inlinePayTypeGroup');
        if (typeGrp) {
            typeGrp.querySelectorAll('.pay-type-btn').forEach(b => {
                b.classList.remove('active');
                if (b.dataset.type === suggested) b.classList.add('active');
            });
            currentInlinePayType = suggested;
        }

        toggleInlinePaymentFields();
        if (currentInlinePayMode !== 'INSURANCE') {
            updateInlinePayPreview();
        }
    }

    async function saveInlinePayment() {
        if (!currentMaster || isSavingInlinePayment) return;

        const discAmt = parseFloat(document.getElementById('inlinePayDiscount')?.value) || 0;
        const discPct = parseFloat(document.getElementById('inlinePayDiscountPct')?.value) || 0;
        const discReason = document.getElementById('inlinePayDiscountReason')?.value.trim() || '';

        const curMasterDiscAmt = parseFloat(currentMaster.discount_amount) || 0;
        const isDiscountChanged = (discAmt !== curMasterDiscAmt);

        if (isDiscountChanged && discAmt > 0 && !discReason) {
            showToast('Discount reason is required', 'warning');
            return;
        }

        // ══════════════════════════════════════════════════════════════
        // CASE A: INSURANCE MODE (Save Insurance Details ONLY)
        // ══════════════════════════════════════════════════════════════
        if (currentInlinePayMode === 'INSURANCE') {
            const sponsorName = document.getElementById('inlineSelectedSponsorName')?.value.trim() || document.getElementById('inlineSponsorSearchInput')?.value.trim();
            if (!sponsorName) {
                showToast('Please enter or select an Insurance / TPA company name', 'warning');
                document.getElementById('inlineSponsorSearchInput')?.focus();
                return;
            }

            const policyNo = document.getElementById('inlinePolicyNumber')?.value.trim() || '';
            const claimNo = document.getElementById('inlineClaimNumber')?.value.trim() || '';

            isSavingInlinePayment = true;
            const btn = document.getElementById('btnSaveInlinePayment');
            if (btn) {
                btn.disabled = true;
                btn.classList.add('loading');
                btn.style.pointerEvents = 'none';
                btn.style.opacity = '0.7';
                btn.innerHTML = '<i class="fas fa-spinner fa-spin" style="margin-right: 6px;"></i> Saving Insurance...';
            }

            try {
                // 1. If discount changed, apply it
                if (isDiscountChanged) {
                    const discRes = await fetch(`${API_URL}ipd-billing-master`, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            action: 'discount',
                            bill_id: currentBillId,
                            discount_amount: discAmt,
                            discount_percentage: discPct,
                            reason: discReason
                        })
                    });
                    const discJson = await discRes.json();
                    if (discJson.success) {
                        currentMaster = discJson.data;
                    }
                }

                // 2. Update master bill type & sponsor
                await fetch(`${API_URL}ipd-billing-master`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        action: 'bill_type',
                        bill_id: currentBillId,
                        bill_type: 'INSURANCE',
                        company_name: sponsorName,
                        sponsor: sponsorName,
                        policy_number: policyNo,
                        approval_number: claimNo
                    })
                });

                // 3. Save to ipd_insurance table (PENDING claim, no dummy receipt)
                await fetch(`${API_URL}ipd-insurance`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        action: 'save',
                        bill_id: currentBillId,
                        admission_id: currentAdmissionId,
                        patient_id: currentPatientId,
                        insurance_type: currentInlineSponsorType,
                        company_name: sponsorName,
                        tpa_name: (currentInlineSponsorType === 'TPA' ? sponsorName : ''),
                        policy_number: policyNo,
                        claim_number: claimNo,
                        approval_number: claimNo,
                        claim_status: 'PENDING'
                    })
                });

                // 4. Reload fresh master record
                const mRes = await fetch(`${API_URL}ipd-billing-master?bill_id=${currentBillId}&_t=${Date.now()}`);
                const mJson = await mRes.json();
                if (mJson.success) currentMaster = mJson.data;

                showToast(`Insurance / TPA (${sponsorName}) saved successfully`, 'success');
                updateWorkspaceUI();
                loadPayments();
            } catch (e) {
                console.error(e);
                showToast('Error saving insurance details', 'error');
            } finally {
                isSavingInlinePayment = false;
                if (btn) {
                    btn.disabled = false;
                    btn.classList.remove('loading');
                    btn.style.pointerEvents = 'auto';
                    btn.style.opacity = '1';
                    btn.innerHTML = '<i class="fas fa-shield-alt" style="margin-right: 6px;"></i> Update Insurance Details';
                }
            }
            return;
        }

        // ══════════════════════════════════════════════════════════════
        // CASE B: PAYMENT MODE (CASH / UPI / CARD / BANK / CHEQUE)
        // ══════════════════════════════════════════════════════════════
        let amt = parseFloat(document.getElementById('inlinePayAmount')?.value) || 0;
        if (amt <= 0 && !isDiscountChanged) {
            showToast('Please enter a valid payment / deposit amount', 'warning');
            document.getElementById('inlinePayAmount')?.focus();
            return;
        }

        if (amt > 0 && currentInlinePayMode !== 'CASH' && !document.getElementById('inlinePayRef')?.value.trim()) {
            showToast('Reference No. is required for non-cash modes', 'warning');
            return;
        }

        let refundReason = null;
        let approvedBy = null;
        if (amt > 0 && currentInlinePayType === 'REFUND') {
            refundReason = document.getElementById('inlineRefundReason').value.trim();
            approvedBy = document.getElementById('inlineRefundApprovedBy').value.trim();
            if (!refundReason || !approvedBy) {
                showToast('Refund reason and approval auth required', 'warning');
                return;
            }
        }

        isSavingInlinePayment = true;
        const btn = document.getElementById('btnSaveInlinePayment');
        if (btn) {
            btn.disabled = true;
            btn.classList.add('loading');
            btn.style.pointerEvents = 'none';
            btn.style.opacity = '0.7';
            btn.innerHTML = '<i class="fas fa-spinner fa-spin" style="margin-right: 6px;"></i> Processing...';
        }

        try {
            // 1. If discount changed, apply it
            if (isDiscountChanged) {
                const discRes = await fetch(`${API_URL}ipd-billing-master`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        action: 'discount',
                        bill_id: currentBillId,
                        discount_amount: discAmt,
                        discount_percentage: discPct,
                        reason: discReason
                    })
                });
                const discJson = await discRes.json();
                if (discJson.success) {
                    currentMaster = discJson.data;
                } else {
                    showToast(discJson.message || 'Failed to update discount', 'error');
                    return;
                }
            }

            // 2. Record payment in ipd_payment
            if (amt > 0) {
                const action = currentInlinePayType === 'REFUND' ? 'refund' : 'pay';
                const remarks = document.getElementById('inlinePayRemarks').value.trim();

                const res = await fetch(`${API_URL}ipd-payment`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        action: action,
                        bill_id: currentBillId,
                        admission_id: currentAdmissionId,
                        patient_id: currentPatientId,
                        payment_date: document.getElementById('inlinePayDate').value,
                        payment_type: currentInlinePayType,
                        payment_mode: currentInlinePayMode,
                        amount: amt,
                        reference_no: document.getElementById('inlinePayRef')?.value || null,
                        remarks: remarks,
                        refund_reason: refundReason,
                        approved_by: approvedBy
                    })
                });
                const json = await res.json();

                if (json.success) {
                    showToast('Payment recorded successfully', 'success');
                    if (document.getElementById('inlinePayAmount')) document.getElementById('inlinePayAmount').value = '';
                    if (document.getElementById('inlinePayRef')) document.getElementById('inlinePayRef').value = '';
                    if (document.getElementById('inlinePayRemarks')) document.getElementById('inlinePayRemarks').value = '';
                    if (document.getElementById('inlineRefundReason')) document.getElementById('inlineRefundReason').value = '';
                    if (document.getElementById('inlineRefundApprovedBy')) document.getElementById('inlineRefundApprovedBy').value = '';

                    if (json.data && json.data.financial) {
                        currentMaster = { ...currentMaster, ...json.data.financial };
                    }
                } else {
                    showToast(json.message || 'Failed to record payment', 'error');
                }
            } else if (isDiscountChanged) {
                showToast('Discount applied successfully', 'success');
            }

            updateWorkspaceUI();
            loadPayments();
        } catch (e) {
            console.error(e);
            showToast('Network error while processing', 'error');
        } finally {
            isSavingInlinePayment = false;
            if (btn) {
                btn.disabled = false;
                btn.classList.remove('loading');
                btn.style.pointerEvents = 'auto';
                btn.style.opacity = '1';
                btn.innerHTML = '<i data-lucide="check-circle-2" style="width: 18px; height: 18px;"></i> Record Payment';
                if (window.lucide) lucide.createIcons();
            }
        }
    }

    // ─────────────────────────────────────────────────────────────
    // SPONSOR / INSURANCE / TPA ADVANCE SEARCH
    // ─────────────────────────────────────────────────────────────
    function initSponsorSearch() {
        // Inline Sponsor Type Selection
        const inTypeGrp = document.getElementById('inlineSponsorTypeGroup');
        if (inTypeGrp) {
            inTypeGrp.addEventListener('click', function(e) {
                const btn = e.target.closest('.pay-type-btn');
                if (btn) {
                    inTypeGrp.querySelectorAll('.pay-type-btn').forEach(b => b.classList.remove('active'));
                    btn.classList.add('active');
                    currentInlineSponsorType = btn.dataset.sponsorType;
                    const lbl = document.getElementById('inlineSponsorLabel');
                    const inp = document.getElementById('inlineSponsorSearchInput');
                    if (currentInlineSponsorType === 'TPA') {
                        if (lbl) lbl.textContent = 'TPA Provider Name';
                        if (inp) inp.placeholder = 'Type to search TPA provider (e.g. Medi Assist, Vidal Health...)';
                    } else {
                        if (lbl) lbl.textContent = 'Insurance Company Name';
                        if (inp) inp.placeholder = 'Type to search Insurance company (e.g. Star Health, HDFC ERGO...)';
                    }
                    if (inp) inp.value = '';
                    const hid = document.getElementById('inlineSelectedSponsorName');
                    if (hid) hid.value = '';
                    fetchSponsors('', currentInlineSponsorType, 'inlineSponsorResults', 'inlineSponsorSearchInput', 'inlineSelectedSponsorName');
                }
            });
        }

        // Modal Sponsor Type Selection
        const modTypeGrp = document.getElementById('modalSponsorTypeGroup');
        if (modTypeGrp) {
            modTypeGrp.addEventListener('click', function(e) {
                const btn = e.target.closest('.pay-type-btn');
                if (btn) {
                    modTypeGrp.querySelectorAll('.pay-type-btn').forEach(b => b.classList.remove('active'));
                    btn.classList.add('active');
                    currentModalSponsorType = btn.dataset.sponsorType;
                    const lbl = document.getElementById('modalSponsorLabel');
                    const inp = document.getElementById('modalSponsorSearchInput');
                    if (currentModalSponsorType === 'TPA') {
                        if (lbl) lbl.textContent = 'TPA Provider Name';
                        if (inp) inp.placeholder = 'Type to search TPA provider (e.g. Medi Assist, Vidal Health...)';
                    } else {
                        if (lbl) lbl.textContent = 'Insurance Company Name';
                        if (inp) inp.placeholder = 'Type to search Insurance company (e.g. Star Health, HDFC ERGO...)';
                    }
                    if (inp) inp.value = '';
                    const hid = document.getElementById('modalSelectedSponsorName');
                    if (hid) hid.value = '';
                    fetchSponsors('', currentModalSponsorType, 'modalSponsorResults', 'modalSponsorSearchInput', 'modalSelectedSponsorName');
                }
            });
        }

        // Inline Sponsor Search Input
        const inInp = document.getElementById('inlineSponsorSearchInput');
        if (inInp) {
            inInp.addEventListener('focus', function() {
                fetchSponsors(this.value.trim(), currentInlineSponsorType, 'inlineSponsorResults', 'inlineSponsorSearchInput', 'inlineSelectedSponsorName');
            });
            inInp.addEventListener('input', function() {
                const q = this.value.trim();
                const hid = document.getElementById('inlineSelectedSponsorName');
                if (hid) hid.value = q;
                clearTimeout(inlineSponsorSearchDebounce);
                inlineSponsorSearchDebounce = setTimeout(() => {
                    fetchSponsors(q, currentInlineSponsorType, 'inlineSponsorResults', 'inlineSponsorSearchInput', 'inlineSelectedSponsorName');
                }, 150);
            });
        }

        // Modal Sponsor Search Input
        const modInp = document.getElementById('modalSponsorSearchInput');
        if (modInp) {
            modInp.addEventListener('focus', function() {
                fetchSponsors(this.value.trim(), currentModalSponsorType, 'modalSponsorResults', 'modalSponsorSearchInput', 'modalSelectedSponsorName');
            });
            modInp.addEventListener('input', function() {
                const q = this.value.trim();
                const hid = document.getElementById('modalSelectedSponsorName');
                if (hid) hid.value = q;
                clearTimeout(modalSponsorSearchDebounce);
                modalSponsorSearchDebounce = setTimeout(() => {
                    fetchSponsors(q, currentModalSponsorType, 'modalSponsorResults', 'modalSponsorSearchInput', 'modalSelectedSponsorName');
                }, 150);
            });
        }

        // Close dropdown when clicking outside search inputs or search results
        document.addEventListener('click', function(e) {
            if (!e.target.closest('#inlineSponsorSearchInput') && !e.target.closest('#inlineSponsorResults')) {
                const res = document.getElementById('inlineSponsorResults');
                if (res) res.style.display = 'none';
            }
            if (!e.target.closest('#modalSponsorSearchInput') && !e.target.closest('#modalSponsorResults')) {
                const res = document.getElementById('modalSponsorResults');
                if (res) res.style.display = 'none';
            }
        });
    }

    async function fetchSponsors(query, type, resultsContainerId, inputId, hiddenId) {
        const container = document.getElementById(resultsContainerId);
        if (!container) return;

        try {
            const res = await fetch(`${API_URL}ipd-catalog-search?type=${encodeURIComponent(type)}&q=${encodeURIComponent(query)}`);
            const json = await res.json();
            const items = json.data || [];

            if (items.length > 0) {
                let html = '';
                items.forEach(item => {
                    const safeName = (item.name || '').replace(/'/g, "\\'");
                    const typeLabel = item.sponsor_type || type;
                    html += `
                        <div class="sponsor-result-item" onclick="billing.selectSponsor('${safeName}', '${inputId}', '${hiddenId}', '${resultsContainerId}')" style="padding: 10px 12px; border-bottom: 1px solid rgba(31,107,74,0.1); cursor: pointer; display: flex; justify-content: space-between; align-items: center; transition: background 0.15s;" onmouseover="this.style.background='rgba(31,107,74,0.08)'" onmouseout="this.style.background='transparent'">
                            <div>
                                <strong style="color: #1f6b4a; font-size: 13px;"><i class="fas ${typeLabel === 'TPA' ? 'fa-building' : 'fa-shield-alt'}" style="margin-right: 6px;"></i>${item.name}</strong>
                                ${item.tpa_name ? `<br><small style="color: #64748b; font-size: 11px;">TPA: ${item.tpa_name}</small>` : ''}
                            </div>
                            <span style="background: #e6f0eb; color: #1f6b4a; font-size: 10px; font-weight: 700; padding: 3px 8px; border-radius: 10px; text-transform: uppercase;">${typeLabel}</span>
                        </div>
                    `;
                });
                container.innerHTML = html;
                container.style.display = 'block';
            } else {
                const safeQ = (query || '').replace(/'/g, "\\'");
                container.innerHTML = `
                    <div style="padding: 12px; text-align: center; color: #64748b; font-size: 12px;">
                        No exact match for "<strong>${query}</strong>".<br>
                        <button type="button" onclick="billing.selectSponsor('${safeQ}', '${inputId}', '${hiddenId}', '${resultsContainerId}')" style="margin-top: 6px; padding: 4px 12px; background: #1f6b4a; color: #fff; border: none; border-radius: 4px; font-size: 11px; font-weight: 700; cursor: pointer;">
                            <i class="fas fa-plus"></i> Use "${query}" as Custom ${type}
                        </button>
                    </div>
                `;
                container.style.display = 'block';
            }
        } catch (e) {
            console.error('Error fetching sponsors:', e);
        }
    }

    function selectSponsor(name, inputId, hiddenId, resultsId) {
        const inp = document.getElementById(inputId);
        const hid = document.getElementById(hiddenId);
        const res = document.getElementById(resultsId);

        if (inp) {
            inp.value = name;
            inp.blur();
        }
        if (hid) hid.value = name;
        if (res) {
            res.innerHTML = '';
            res.style.display = 'none';
        }
    }

    // ── 4. INSURANCE RECEIPT ──
    async function openInsuranceReceiptModal() {
        if (!currentMaster) return;

        try {
            const res = await fetch(`${API_URL}ipd-insurance?bill_id=${currentBillId}`);
            const json = await res.json();
            const ins = json.data;

            if (!ins) { showToast('No insurance data found', 'warning'); return; }

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
    }

    function fillInsFullAmount() {
        const amt = document.getElementById('insRcptAmount');
        amt.value = amt.dataset.pending || 0;
    }

    async function saveInsuranceReceipt() {
        const amt = parseFloat(document.getElementById('insRcptAmount').value) || 0;
        const ref = document.getElementById('insRcptRef').value.trim();
        if (amt <= 0) { showToast('Enter valid amount', 'warning'); return; }
        if (!ref) { showToast('Settlement reference required', 'warning'); return; }

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
                currentMaster = { ...currentMaster, ...json.data.financial };
                updateWorkspaceUI();
                loadPayments();
            } else {
                showToast(json.message, 'error');
            }
        } catch (e) {
            showToast('Error saving receipt', 'error');
        }
    }

    // ── 5. APPLY DISCOUNT ──
    function openDiscountModal() {
        if (!currentMaster) return;

        const sub = parseFloat(currentMaster.subtotal);
        document.getElementById('discSubtotalDisplay').textContent = `₹${sub.toLocaleString('en-IN', { minimumFractionDigits: 2 })}`;

        document.getElementById('discAmount').value = parseFloat(currentMaster.discount_amount) || '';
        document.getElementById('discPct').value = parseFloat(currentMaster.discount_percentage) || '';
        document.getElementById('discReason').value = currentMaster.notes || '';

        updateDiscountPreview();
        openModal('modalDiscount');
    }

    function calcDiscountPct() {
        const sub = parseFloat(currentMaster.subtotal) || 0;
        const amt = parseFloat(document.getElementById('discAmount').value) || 0;
        if (sub > 0 && amt <= sub) {
            document.getElementById('discPct').value = ((amt / sub) * 100).toFixed(2);
        } else if (amt > sub) {
            document.getElementById('discAmount').value = sub;
            document.getElementById('discPct').value = 100;
        }
        updateDiscountPreview();
    }

    function calcDiscountAmt() {
        const sub = parseFloat(currentMaster.subtotal) || 0;
        const pct = parseFloat(document.getElementById('discPct').value) || 0;
        if (pct >= 0 && pct <= 100) {
            document.getElementById('discAmount').value = (sub * pct / 100).toFixed(2);
        } else if (pct > 100) {
            document.getElementById('discPct').value = 100;
            document.getElementById('discAmount').value = sub;
        }
        updateDiscountPreview();
    }

    function updateDiscountPreview() {
        const sub = parseFloat(currentMaster.subtotal) || 0;
        const amt = parseFloat(document.getElementById('discAmount').value) || 0;
        const total = Math.max(0, sub - amt);

        document.getElementById('dapSubtotal').textContent = `₹${sub.toLocaleString('en-IN', { minimumFractionDigits: 2 })}`;
        document.getElementById('dapDiscount').textContent = `-₹${amt.toLocaleString('en-IN', { minimumFractionDigits: 2 })}`;
        document.getElementById('dapGrandTotal').textContent = `₹${total.toLocaleString('en-IN', { minimumFractionDigits: 2 })}`;
    }

    async function saveDiscount() {
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
    function openStatusModal() {
        if (!currentMaster) return;

        const current = currentMaster.billing_status;
        document.getElementById('statusCurrentDisplay').textContent = current.replace('_', ' ');
        document.getElementById('statusReason').value = '';

        document.querySelectorAll('.status-option-btn').forEach(b => {
            b.classList.remove('selected');
            b.disabled = false;

            // Logic to disable backward steps
            const s = b.dataset.status;
            if (current === 'FINALIZED' && s !== 'OPEN' && window.USER_ROLE !== 'admin' && window.USER_ROLE !== 'Admin') {
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
        const nextStatusBtn = document.querySelector(`.status-option-btn[data-status="${next}"]`);
        if (nextStatusBtn) nextStatusBtn.classList.add('selected');

        const newStatusSelect = document.getElementById('newBillingStatus');
        if (newStatusSelect) newStatusSelect.value = next;

        openModal('modalStatus');
    };

    // Attached via onclick inline in HTML isn't there, so we delegate safely
    const statusOptionsContainer = document.getElementById('statusOptions');
    if (statusOptionsContainer) {
        statusOptionsContainer.addEventListener('click', function (e) {
            const btn = e.target.closest('.status-option-btn');
            if (btn) {
                if (btn.disabled) return;
                document.querySelectorAll('.status-option-btn').forEach(b => b.classList.remove('selected'));
                btn.classList.add('selected');
                selectedStatus = btn.dataset.status;
            }
        });
    }

    async function saveStatus() {
        if (selectedStatus === currentMaster.billing_status) {
            closeModal('modalStatus'); return;
        }

        if (selectedStatus === 'FINALIZED') {
            const bal = parseFloat(currentMaster.balance_due);
            if (bal > 0) {
                if (!confirm(`Balance of ₹${bal} is still pending. Are you sure you want to finalize?`)) return;
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

    function dischargePatient() {
        if (!currentAdmissionId) {
            showToast('Please select a patient first', 'warning');
            return;
        }

        // Set default date to now (local timezone string)
        const now = new Date();
        const year = now.getFullYear();
        const month = String(now.getMonth() + 1).padStart(2, '0');
        const day = String(now.getDate()).padStart(2, '0');
        const hours = String(now.getHours()).padStart(2, '0');
        const mins = String(now.getMinutes()).padStart(2, '0');
        
        const dsDateInput = document.getElementById('dsDate');
        if (dsDateInput) {
            dsDateInput.value = `${year}-${month}-${day}T${hours}:${mins}`;
        }
        
        // Reset form
        const dsType = document.getElementById('dsType'); if (dsType) dsType.value = 'Normal';
        const dsFollowup = document.getElementById('dsFollowup'); if (dsFollowup) dsFollowup.value = '';
        const dsDiagnosis = document.getElementById('dsDiagnosis'); if (dsDiagnosis) dsDiagnosis.value = '';
        const dsSummary = document.getElementById('dsSummary'); if (dsSummary) dsSummary.value = '';
        const dsMeds = document.getElementById('dsMeds'); if (dsMeds) dsMeds.value = '';

        openModal('modalDischarge');
    }

    async function submitDischarge() {
        const btn = document.getElementById('btnSubmitDischarge');
        const dsDate = document.getElementById('dsDate')?.value;
        if (!dsDate) {
            showToast('Discharge Date & Time is required', 'warning');
            return;
        }

        if (!currentAdmissionId) {
            showToast('No active admission selected', 'warning');
            return;
        }

        if (btn) {
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Discharging...';
        }

        // Safe extraction of date and time without timezone skew
        const parts = dsDate.split('T');
        const dateStr = parts[0] || (new Date().toISOString().split('T')[0]);
        let timeStr = (parts[1] || '12:00:00');
        if (timeStr.length === 5) timeStr += ':00';

        const followUp = document.getElementById('dsFollowup')?.value;
        const payload = {
            admission_id: currentAdmissionId,
            discharge_date: dateStr,
            discharge_time: timeStr,
            discharge_type: document.getElementById('dsType')?.value || 'Normal',
            follow_up_date: followUp ? followUp : null,
            final_diagnosis: (document.getElementById('dsDiagnosis')?.value || '').trim(),
            discharge_summary: (document.getElementById('dsSummary')?.value || '').trim(),
            medications_prescribed: (document.getElementById('dsMeds')?.value || '').trim(),
            discharged_by_doctor_id: (currentMaster && (currentMaster.doctor_id || currentMaster.admitting_doctor_id)) || 1 
        };

        try {
            // 1. Create discharge summary record in discharge_details
            try {
                await fetch('/GM_HMS/reception_view/ipd_management/public/api.php/api/discharge', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                });
            } catch (errRec) {
                console.warn('Discharge record logging warning:', errRec);
            }
            
            // 2. Discharge admission and auto-release bed in hospital_beds
            const resAdmit = await fetch('/GM_HMS/reception_view/ipd_management/public/api.php/api/admissions?action=discharge', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    admission_id: currentAdmissionId,
                    discharge_date: dateStr,
                    discharge_time: timeStr
                })
            });
            const dataAdmit = await resAdmit.json();

            if (dataAdmit.success || (dataAdmit.status === 'success')) {
                showToast('Patient Discharged & Bed Released Successfully', 'success');
                closeModal('modalDischarge');
                setTimeout(() => {
                    loadAdmission(currentAdmissionId, currentPatientId);
                }, 800);
            } else {
                showToast(dataAdmit.message || dataAdmit.error || 'Failed to discharge patient', 'error');
            }
        } catch (e) {
            console.error(e);
            showToast('An error occurred during discharge submission', 'error');
        } finally {
            if (btn) {
                btn.disabled = false;
                btn.innerHTML = '<i data-lucide="check-circle-2"></i> Complete Discharge';
                if (window.lucide) lucide.createIcons();
            }
        }
    }

    async function openDischargeHistory() {
        openModal('modalDischargeHistory');
        const tbody = document.getElementById('dhTableBody');
        tbody.innerHTML = '<tr><td colspan="5" class="text-center">Loading...</td></tr>';
        
        try {
            // Fetch recent bills
            const res = await fetch(`${API_URL}ipd-billing-master`);
            const json = await res.json();
            if (json.success && json.data && json.data.rows) {
                // Filter by discharge_date
                const bills = json.data.rows.filter(b => b.discharge_date !== null && b.discharge_date !== '');
                
                if (!bills || bills.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="5" class="text-center">No discharge history found.</td></tr>';
                    return;
                }
                
                let html = '';
                bills.forEach(b => {
                    const dt = b.discharge_date ? new Date(b.discharge_date).toLocaleDateString() : 'N/A';
                    html += `
                        <tr>
                            <td>${b.patient_name || 'Unknown'}</td>
                            <td>${b.admission_id}</td>
                            <td>${dt}</td>
                            <td>₹${parseFloat(b.grand_total).toFixed(2)}</td>
                            <td>
                                <button class="bm-btn" style="padding:4px 8px; font-size:12px;" onclick="billing.closeModal('modalDischargeHistory'); billing.loadAdmission('${b.admission_id}', '${b.patient_id}');">
                                    View Bill
                                </button>
                            </td>
                        </tr>
                    `;
                });
                tbody.innerHTML = html;
            } else {
                tbody.innerHTML = '<tr><td colspan="5" class="text-center text-danger">Failed to load history.</td></tr>';
            }
        } catch (e) {
            console.error(e);
            tbody.innerHTML = '<tr><td colspan="5" class="text-center text-danger">Error loading history.</td></tr>';
        }
    };

    // ── DISCHARGE CLEARANCE STATUS & MODAL ──
    async function loadPatientClearanceStatus(admissionId, patientId) {
        const container = document.getElementById('phcClearanceContainer');
        if (!container) return;
        container.innerHTML = '<span style="font-size:0.7rem; color:#64748b;"><i class="fas fa-spinner fa-spin"></i> Checking clearances...</span>';

        try {
            const res = await fetch(`/GM_HMS/api/discharge_clearance.php?action=status&admission_id=${encodeURIComponent(admissionId || '')}&patient_id=${encodeURIComponent(patientId || '')}`);
            const json = await res.json();

            if (json.success && json.has_clearance && json.data) {
                const d = json.data;
                window.currentClearanceData = d;
                window.currentClearanceQueries = json.queries || [];

                let overallBg = '#fef3c7', overallCol = '#92400e', overallBorder = '#fde68a', overallIcon = 'fa-clock';
                if (d.overall_status === 'All Cleared') {
                    overallBg = '#dcfce7'; overallCol = '#15803d'; overallBorder = '#86efac'; overallIcon = 'fa-check-double';
                } else if (d.overall_status === 'Queries Raised') {
                    overallBg = '#fee2e2'; overallCol = '#991b1b'; overallBorder = '#fca5a5'; overallIcon = 'fa-exclamation-triangle';
                } else if (d.overall_status === 'Completed') {
                    overallBg = '#dcfce7'; overallCol = '#15803d'; overallBorder = '#86efac'; overallIcon = 'fa-check-circle';
                }

                const getDeptConfig = (status, deptName, iconClass, queryText, clearedBy) => {
                    let bg = '#fffbeb', col = '#b45309', border = '#fde68a', label = status || 'Pending';
                    if (status === 'Approved') {
                        bg = '#dcfce7'; col = '#15803d'; border = '#86efac';
                    } else if (status === 'Query') {
                        bg = '#fee2e2'; col = '#b91c1c'; border = '#fca5a5';
                    }
                    const tooltip = `${deptName}: ${label}${clearedBy ? ' (by ' + clearedBy + ')' : ''}${queryText ? ' - Query: ' + queryText : ''}`;
                    return `
                        <span class="dept-badge-btn" onclick="billing.openClearanceDetailModal()" style="background:${bg}; color:${col}; border-color:${border};" title="${tooltip}">
                            <i class="fas ${iconClass}"></i> ${deptName}: <strong style="font-weight:800;">${label}</strong>
                        </span>
                    `;
                };

                const rHtml = getDeptConfig(d.reception_status, 'Reception', 'fa-file-invoice-dollar', d.reception_query, d.reception_by);
                const pHtml = getDeptConfig(d.pharmacy_status, 'Pharmacy', 'fa-pills', d.pharmacy_query, d.pharmacy_by);
                const lHtml = getDeptConfig(d.lab_status, 'Lab', 'fa-microscope', d.lab_query, d.lab_by);

                container.innerHTML = `
                    <span class="clearance-pill-btn" onclick="billing.openClearanceDetailModal()" style="background:${overallBg}; color:${overallCol}; border-color:${overallBorder};" title="Click to view full clearance breakdown">
                        <i class="fas ${overallIcon}"></i> ${d.overall_status || 'Pending Clearance'}
                    </span>
                    ${rHtml}
                    ${pHtml}
                    ${lHtml}
                `;
            } else {
                window.currentClearanceData = null;
                window.currentClearanceQueries = [];
                container.innerHTML = `
                    <span class="dept-badge-btn" style="background:#f1f5f9; color:#64748b; border-color:#cbd5e1;" title="Discharge clearance not yet requested by Nursing Station">
                        <i class="fas fa-info-circle"></i> Clearance: Not Initiated
                    </span>
                `;
            }
        } catch (e) {
            console.error('Error fetching clearance status:', e);
            container.innerHTML = '';
        }
    }

    function openClearanceDetailModal() {
        const d = window.currentClearanceData;
        if (!d) {
            showToast('No active discharge clearance record for this patient.', 'info');
            return;
        }

        document.getElementById('cdPtName').textContent = d.patient_name || (currentMaster ? currentMaster.patient_name : 'Patient');
        document.getElementById('cdPtDetails').textContent = `PID: ${d.patient_id || (currentMaster ? currentMaster.patient_id : '-')} | Admission: ${d.admission_id || (currentMaster ? currentMaster.admission_id : '-')} | ${d.bed_info || (currentMaster ? currentMaster.ward_name : '')}`;

        // Overall status badge
        const badgeEl = document.getElementById('cdOverallStatusBadge');
        let overallBg = '#fef3c7', overallCol = '#92400e', overallBorder = '#fde68a';
        if (d.overall_status === 'All Cleared' || d.overall_status === 'Completed') {
            overallBg = '#dcfce7'; overallCol = '#15803d'; overallBorder = '#86efac';
        } else if (d.overall_status === 'Queries Raised') {
            overallBg = '#fee2e2'; overallCol = '#991b1b'; overallBorder = '#fca5a5';
        }
        badgeEl.innerHTML = `<span style="padding: 4px 12px; border-radius: 20px; font-weight: 800; font-size: 0.8rem; background: ${overallBg}; color: ${overallCol}; border: 1.5px solid ${overallBorder};">${d.overall_status}</span>`;

        // Nurse info
        document.getElementById('cdNurseName').textContent = d.nurse_name || 'Nursing Station';
        document.getElementById('cdInitiatedAt').textContent = d.created_at ? new Date(d.created_at).toLocaleString() : '';
        const notesWrap = document.getElementById('cdNurseNotesWrap');
        const notesEl = document.getElementById('cdNurseNotes');
        if (d.nurse_notes) {
            notesEl.textContent = d.nurse_notes;
            notesWrap.style.display = 'block';
        } else {
            notesWrap.style.display = 'none';
        }

        // Department cards helper
        const renderDeptCard = (deptKey, deptName, status, clearedBy, clearedAt, notes, query) => {
            const statusEl = document.getElementById(`cd${deptKey}Status`);
            const byEl = document.getElementById(`cd${deptKey}By`);
            const atEl = document.getElementById(`cd${deptKey}At`);
            const notesEl = document.getElementById(`cd${deptKey}Notes`);
            const queryEl = document.getElementById(`cd${deptKey}Query`);
            const cardEl = document.getElementById(`cd${deptKey}Card`);

            statusEl.textContent = status || 'Pending';
            if (status === 'Approved') {
                statusEl.style.color = '#15803d';
                cardEl.style.borderColor = '#86efac';
                cardEl.style.background = '#f0fdf4';
            } else if (status === 'Query') {
                statusEl.style.color = '#b91c1c';
                cardEl.style.borderColor = '#fca5a5';
                cardEl.style.background = '#fef2f2';
            } else {
                statusEl.style.color = '#b45309';
                cardEl.style.borderColor = '#fde68a';
                cardEl.style.background = '#fffbeb';
            }

            byEl.textContent = clearedBy ? `By: ${clearedBy}` : 'By: Pending action';
            atEl.textContent = clearedAt ? new Date(clearedAt).toLocaleString() : 'Time: -';

            if (notes) {
                notesEl.textContent = `Notes: ${notes}`;
                notesEl.style.display = 'block';
            } else {
                notesEl.style.display = 'none';
            }

            if (query) {
                queryEl.textContent = `Query: ${query}`;
                queryEl.style.display = 'block';
            } else {
                queryEl.style.display = 'none';
            }
        };

        renderDeptCard('Rec', 'Reception', d.reception_status, d.reception_by, d.reception_at, d.reception_notes, d.reception_query);
        renderDeptCard('Ph', 'Pharmacy', d.pharmacy_status, d.pharmacy_by, d.pharmacy_at, d.pharmacy_notes, d.pharmacy_query);
        renderDeptCard('Lab', 'Laboratory', d.lab_status, d.lab_by, d.lab_at, d.lab_notes, d.lab_query);

        // Admin action section
        const adminSec = document.getElementById('cdAdminActionSection');
        if (d.overall_status === 'All Cleared' && d.admin_status !== 'Confirmed') {
            adminSec.style.display = 'block';
        } else {
            adminSec.style.display = 'none';
        }

        openModal('modalClearanceDetail');
    }

    async function confirmAdminDischargeFromModal() {
        const d = window.currentClearanceData;
        if (!d) return;

        if (!confirm('Confirm final discharge clearance for this patient? All department approvals will be finalized.')) return;

        try {
            const res = await fetch('/GM_HMS/api/discharge_clearance.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    action: 'admin_confirm',
                    clearance_id: d.clearance_id,
                    admission_id: d.admission_id
                })
            });
            const json = await res.json();
            if (json.success) {
                showToast(json.message || 'Discharge finalized by Admin!', 'success');
                closeModal('modalClearanceDetail');
                loadPatientClearanceStatus(d.admission_id, d.patient_id);
            } else {
                showToast(json.message || 'Failed to finalize discharge', 'error');
            }
        } catch (e) {
            console.error(e);
            showToast('Network error finalizing discharge.', 'error');
        }
    }

    // ── 7. CANCEL CHARGE ──
    let chargeToCancel = null;
    function openCancelChargeModal(itemId, category, desc, date, amount) {
        chargeToCancel = itemId;
        document.getElementById('cciCategory').textContent = category.replace('_', ' ');
        document.getElementById('cciDesc').textContent = desc;
        document.getElementById('cciDate').textContent = date;
        document.getElementById('cciAmount').textContent = amount;

        openModal('modalCancelCharge');
    };

    async function confirmCancelCharge() {
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

                currentMaster = { ...currentMaster, ...json.data.financial };
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

    function startInlineEdit(itemId, currentTotal) {
        const cell = document.getElementById(`total-cell-${itemId}`);
        if (!cell) return;
        cell.innerHTML = `
            <input type="text" id="total-input-${itemId}" value="${currentTotal}" class="bm-input" 
                   style="width: 80px; padding: 2px 4px; height: 26px; text-align:right; font-family: inherit; font-size: inherit; outline: 2px solid var(--primary); border: none; border-radius: 4px; box-shadow: 0 0 5px rgba(31, 107, 74, 0.3);" 
                   autocomplete="off">
        `;
        
        const input = document.getElementById(`total-input-${itemId}`);
        if (input) {
            input.focus();
            input.select();
            
            let isHandling = false;
            
            input.addEventListener('keydown', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    if(!isHandling) { isHandling = true; billing.saveInlineEdit(itemId, currentTotal); }
                }
                if (e.key === 'Escape') {
                    e.preventDefault();
                    if(!isHandling) { isHandling = true; billing.cancelInlineEdit(itemId, currentTotal); }
                }
            });
            
            input.addEventListener('blur', function() {
                if(!isHandling) {
                    isHandling = true;
                    const val = parseFloat(input.value);
                    if (isNaN(val) || val === currentTotal) {
                        billing.cancelInlineEdit(itemId, currentTotal);
                    } else {
                        billing.saveInlineEdit(itemId, currentTotal);
                    }
                }
            });
        }
    }

    function cancelInlineEdit(itemId, originalTotal) {
        const cell = document.getElementById(`total-cell-${itemId}`);
        if (!cell) return;
        const formattedTotal = originalTotal.toLocaleString('en-IN', { minimumFractionDigits: 2 });
        cell.innerHTML = `<span style="cursor:pointer; color:var(--blue); text-decoration:underline;" onclick="billing.startInlineEdit(${itemId}, ${originalTotal})" title="Click to edit total">₹${formattedTotal}</span>`;
    }

    async function saveInlineEdit(itemId, originalTotal) {
        const input = document.getElementById(`total-input-${itemId}`);
        if (!input) return;
        const newTotalStr = input.value;
        const newTotal = parseFloat(newTotalStr);
        
        if (isNaN(newTotal) || newTotal < 0) {
            showToast('Invalid amount entered', 'warning');
            input.focus();
            return;
        }

        if (newTotal === originalTotal) {
            billing.cancelInlineEdit(itemId, originalTotal);
            return;
        }
        
        const cell = document.getElementById(`total-cell-${itemId}`);
        if(cell) {
            cell.innerHTML = `<i data-lucide="loader" class="spin" style="width:14px; height:14px;"></i>`;
            if(window.lucide) lucide.createIcons();
        }
        
        try {
            const res = await fetch(`${API_URL}ipd-billing-items`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    action: 'update_total',
                    item_id: itemId,
                    total_amount: newTotal
                })
            });
            const json = await res.json();
            
            if (json.success) {
                showToast('Total updated successfully', 'success');
                currentMaster = { ...currentMaster, ...json.data.financial };
                updateWorkspaceUI();
                const activeTab = document.querySelector('.cat-tab.active').dataset.type;
                loadItems(activeTab);
            } else {
                showToast(json.message, 'error');
                billing.cancelInlineEdit(itemId, originalTotal);
            }
        } catch (e) {
            showToast('Error updating total', 'error');
            billing.cancelInlineEdit(itemId, originalTotal);
        }
    }

    // ── 8. INSURANCE DETAILS ──
    let currentBillType = 'SELF';
    async function openInsuranceModal() {
        if (!currentMaster) return;

        currentBillType = currentMaster.bill_type || 'SELF';
        document.querySelectorAll('.bill-type-btn').forEach(b => {
            b.classList.remove('active');
            if (b.dataset.type === currentBillType) b.classList.add('active');
        });

        toggleInsFields();

        // Reset fields
        const existingName = currentMaster.sponsor || currentMaster.insurance_company_name || '';
        if (document.getElementById('insCompanyName')) {
            document.getElementById('insCompanyName').value = (existingName !== 'SELF' ? existingName : '');
        }
        if (document.getElementById('modalInsSponsorType')) {
            document.getElementById('modalInsSponsorType').value = (currentMaster.credit_type === 'TPA' ? 'TPA' : 'INSURANCE');
        }

        // Try fetch existing full record
        try {
            const res = await fetch(`${API_URL}ipd-insurance?bill_id=${currentBillId}`);
            const json = await res.json();
            if (json.success && json.data) {
                const ins = json.data;
                if (document.getElementById('insCompanyName') && ins.company_name) {
                    document.getElementById('insCompanyName').value = ins.company_name;
                }
                if (document.getElementById('modalInsSponsorType') && ins.insurance_type) {
                    document.getElementById('modalInsSponsorType').value = ins.insurance_type;
                }
            }
        } catch (e) { }

        openModal('modalInsurance');
    }

    function selectBillType(btn) {
        document.querySelectorAll('.bill-type-btn').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        currentBillType = btn.dataset.type;
        toggleInsFields();
    }

    function toggleInsFields() {
        const fields = document.getElementById('insFormFields');
        if (currentBillType === 'SELF') {
            fields.style.display = 'none';
        } else {
            fields.style.display = 'block';
        }
    }

    function calcInsPatientPayable() {}

    async function saveInsuranceDetails() {
        if (currentBillType !== 'SELF') {
            if (!document.getElementById('insCompanyName').value.trim()) {
                showToast('Company name is required', 'warning'); return;
            }
        }

        try {
            const compName = document.getElementById('insCompanyName')?.value.trim() || '';
            const sponsorType = document.getElementById('modalInsSponsorType')?.value || currentBillType || 'INSURANCE';

            // First save bill type on master
            await fetch(`${API_URL}ipd-billing-master`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    action: 'bill_type',
                    bill_id: currentBillId,
                    bill_type: currentBillType,
                    company_name: compName,
                    sponsor: compName
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
                        insurance_type: sponsorType,
                        company_name: compName,
                        tpa_name: (sponsorType === 'TPA' ? compName : ''),
                        claim_status: 'PENDING'
                    })
                });
                const json = await res.json();
                if (json.success && json.data && json.data.financial) {
                    currentMaster = { ...currentMaster, ...json.data.financial };
                }
            }

            // Always reload master to get fresh joined insurance, sponsor, and admission data
            const mRes = await fetch(`${API_URL}ipd-billing-master?bill_id=${currentBillId}&_t=${Date.now()}`);
            const mJson = await mRes.json();
            if (mJson.success) currentMaster = mJson.data;

            showToast('Insurance details updated', 'success');
            closeModal('modalInsurance');
            updateWorkspaceUI();

        } catch (e) {
            showToast('Error saving insurance details', 'error');
        }
    }

    // ─────────────────────────────────────────────────────────────
    // UTILS
    // ─────────────────────────────────────────────────────────────
    function showToast(message, type = 'info') {
        const container = document.getElementById('billingToastContainer');
        const toast = document.createElement('div');
        toast.className = `billing-toast toast-${type}`;

        let icon = 'info';
        if (type === 'success') icon = 'check-circle-2';
        if (type === 'error') icon = 'x-circle';
        if (type === 'warning') icon = 'alert-triangle';

        toast.innerHTML = `<i data-lucide="${icon}"></i> <span>${message}</span>`;

        toast.onclick = () => {
            toast.classList.add('hide');
            setTimeout(() => toast.remove(), 300);
        };

        container.appendChild(toast);
        if(window.lucide) lucide.createIcons();

        setTimeout(() => {
            if (toast.parentNode) {
                toast.classList.add('hide');
                setTimeout(() => { if (toast.parentNode) toast.remove(); }, 300);
            }
        }, 4000);
    }

    function animateValue(obj, end, isCurrency = false) {
        if (!obj) return;
        // Parse current value
        let currentText = obj.textContent.replace(/[^0-9.-]+/g, "");
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

            if (isCurrency) {
                obj.textContent = '₹' + current.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            } else {
                obj.textContent = Math.round(current);
            }

            if (progress < 1) {
                window.requestAnimationFrame(step);
            } else {
                if (isCurrency) {
                    obj.textContent = '₹' + end.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                }
                obj.classList.remove('animating');
            }
        };
        window.requestAnimationFrame(step);
    }

    function initShortcuts() {
        document.addEventListener('keydown', function (e) {
            // Don't trigger if inside an input/textarea
            if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA' || e.target.tagName === 'SELECT') return;

            if (!currentBillId) return;

            // Prevent default for our shortcuts to avoid double triggering
            if (e.key.toLowerCase() === 'p') { e.preventDefault(); openPaymentModal('PARTIAL'); }
            if (e.key.toLowerCase() === 'a') { e.preventDefault(); toggleChargeMenu(); }
            if (e.key.toLowerCase() === 'b') { e.preventDefault(); openRoomRentModal(); }

            // Close modals on Escape
            if (e.key === 'Escape') {
                const openModals = document.querySelectorAll('.billing-modal-overlay.open');
                if (openModals.length > 0) {
                    openModals.forEach(el => el.classList.remove('open'));
                } else if (document.getElementById('chargeMenuDropdown') && document.getElementById('chargeMenuDropdown').classList.contains('active')) {
                    closeChargeMenu();
                } else {
                    // Close workspace if no modals are open
                    closeWorkspace();
                }
            }
        });
    }

    // ── CLOSE WORKSPACE ──
    function closeWorkspace() {
        document.getElementById('billingWorkspace').style.display = 'none';
        document.getElementById('billingEmptyState').style.display = 'flex';
        const searchZone = document.getElementById('billingSearchZone');
        if (searchZone) searchZone.style.display = 'flex';
        
        currentMaster = null;
        currentBillId = null;
        currentPatientId = null;
        currentBedInfo = null;

        // Clear stored admission from sessionStorage
        sessionStorage.removeItem('currentAdmissionId');
        sessionStorage.removeItem('currentPatientId');

        // optionally refresh patients list to get latest status
        loadAllAdmittedPatients();
    }

    // ── PRINTING ──
    function openPrintPage(url, data) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = url;
        form.target = '_blank';
        for (const key in data) {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = key;
            input.value = data[key];
            form.appendChild(input);
        }
        document.body.appendChild(form);
        form.submit();
        document.body.removeChild(form);
    }

    function printInterim() {
        if (!currentBillId) return;
        openPrintPage('/GM_HMS/reception_view/print_interim.php', { bill_id: currentBillId });
    }

    function printFinal() {
        if (!currentBillId) return;
        openPrintPage('/GM_HMS/reception_view/print_final.php', { bill_id: currentBillId });
    }

    function printReceipt() {
        if (!currentBillId) return;
        openPrintPage('/GM_HMS/reception_view/print_receipt.php', { bill_id: currentBillId });
    }



    // Export exposed functions
    return {
        init,
        loadAdmission,
        filterItems,
        toggleChargeMenu,
        closeChargeMenu,
        openModal,
        closeModal,
        // Add Charge (Nurse Workspace Pattern)
        openAddChargeModal,
        selectSubTab,
        // Lab Test
        selectLabItem,
        selectLabItemByIndex,
        calcLabTotal,
        saveLabCharge,
        // Radiology
        selectRadItem,
        selectRadItemByIndex,
        calcRadTotal,
        saveRadCharge,
        // Other Services
        selectOtherItem,
        selectOtherItemByIndex,
        calcOtherTotal,
        saveOtherCharge,
        // Pharmacy
        addToPhCart,
        selectPhItemByIndex,
        updatePhQty,
        updatePhPrice,
        removePhCartItem,
        savePharmacyOrder,
        // Doctor Visit
        onDoctorSelect,
        selectDoctorItem,
        selectDocItemByIndex,
        selectGenericDoctor,
        selectGenericDocByIndex,
        calcDoctorTotal,
        saveDoctorVisitCharge,
        // Procedure
        selectProcItem,
        calcProcedureTotal,
        saveProcedureCharge,
        // Dialysis (14)
        calcDiaDuration,
        calcDiaTotal,
        saveDialysisCharge,
        // Oxygen (15)
        calcOxyDuration,
        calcOxyTotal,
        saveOxygenCharge,
        // Ventilator (16)
        calcVentDuration,
        calcVentTotal,
        saveVentilatorCharge,
        // Blood Transfusion (17)
        calcBtTotal,
        saveTransfusionCharge,
        // Ward Transfer (18)
        calcWtTotal,
        saveWardTransferCharge,
        calcConsumableTotal,
        saveConsumableCharge,
        // Room Rent
        openRoomRentModal,
        loadRoomRentPreview,
        confirmRoomRent,
        // Payment
        openPaymentModal,
        fillFullAmount,
        updatePayPreview,
        savePayment,
        // Inline Payment
        fillInlineFullAmount,
        updateInlinePayPreview,
        saveInlinePayment,
        setQuickAdvance,
        syncInlineApprovedAmount,
        applyApprovedToPayment,
        // Sponsor Search
        selectSponsor,
        fetchSponsors,
        // Ins Receipt
        openInsuranceReceiptModal,
        fillInsFullAmount,
        saveInsuranceReceipt,
        // Discount
        openDiscountModal,
        calcDiscountPct,
        calcDiscountAmt,
        saveDiscount,
        calcInlineDiscountAmt,
        calcInlineDiscountPct,
        calcModalDiscountAmt,
        calcModalDiscountPct,
        // Status
        openStatusModal,
        saveStatus,
        // Cancel Charge
        openCancelChargeModal,
        confirmCancelCharge,
        startInlineEdit,
        cancelInlineEdit,
        saveInlineEdit,
        // Insurance
        openInsuranceModal,
        selectBillType,
        calcInsPatientPayable,
        saveInsuranceDetails,
        focusChangeInsurance,
        cancelInsurance,
        confirmCancelInsurance,
        // Grouping
        toggleGroup,
        expandAllGroups,
        collapseAllGroups,
        // Print
        printInterim,
        printFinal,
        printReceipt,
        // Discharge & Clearances
        dischargePatient,
        submitDischarge,
        openDischargeHistory,
        loadPatientClearanceStatus,
        openClearanceDetailModal,
        confirmAdminDischargeFromModal,
        filterPatientsTable,
        sortPatientsTable,
        closeWorkspace,
        toggleDetailedCharges
    };

    function toggleDetailedCharges() {
        const card = document.getElementById('billingItemsCard');
        const btn = document.getElementById('btnToggleItems');
        if (!card) return;
        if (card.style.display === 'none' || getComputedStyle(card).display === 'none') {
            card.style.display = 'block';
            if (btn) btn.innerHTML = `<i data-lucide="eye-off"></i> Hide Charges Breakdown`;
        } else {
            card.style.display = 'none';
            if (btn) btn.innerHTML = `<i data-lucide="list"></i> View Charges Breakdown`;
        }
        if (window.lucide) lucide.createIcons();
    }

})();

window.billing = billing;
window.toggleGroup = billing.toggleGroup;
window.expandAllGroups = billing.expandAllGroups;
window.collapseAllGroups = billing.collapseAllGroups;

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', billing.init);
