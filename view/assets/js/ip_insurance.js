/**
 * IP Insurance Management Module Controller
 * GM Hospital Management System
 */

(function () {
    'use strict';

    const API_BASE = window.BILLING_API || '/GM_HMS/api/';

    const state = {
        currentPage: 1,
        perPage: 25,
        sortBy: 'insurance_id',
        sortDir: 'DESC',
        filterDebounce: null,
        currentRecord: null,
        filterPanelOpen: true
    };

    // Number formatter for Indian Rupee
    function formatINR(val) {
        const num = parseFloat(val) || 0;
        return '₹' + num.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    function formatDate(val) {
        if (!val || val === '0000-00-00' || val === '0000-00-00 00:00:00') return '—';
        const d = new Date(val);
        if (isNaN(d.getTime())) return val;
        return d.toLocaleDateString('en-IN', { day: '2-digit', month: 'short', year: 'numeric' });
    }

    // ── 1. INITIALIZATION ──
    document.addEventListener('DOMContentLoaded', () => {
        loadDistinctCompanies();
        loadData();
    });

    // ── 2. FETCH DISTINCT COMPANIES & TPAS ──
    async function loadDistinctCompanies() {
        try {
            const res = await fetch(`${API_BASE}ipd-insurance?action=companies`);
            const json = await res.json();
            if (json && json.success && json.data) {
                const compDatalist = document.getElementById('companyDatalist');
                if (compDatalist && json.data.companies) {
                    compDatalist.innerHTML = json.data.companies.map(c => `<option value="${escapeHtml(c)}">`).join('');
                }

                const tpaDatalist = document.getElementById('tpaDatalist');
                if (tpaDatalist && json.data.tpas) {
                    tpaDatalist.innerHTML = json.data.tpas.map(t => `<option value="${escapeHtml(t)}">`).join('');
                }
            }
        } catch (err) {
            console.error('Error fetching companies datalist:', err);
        }
    }

    // ── 3. MAIN DATA LOADER WITH SERVER-SIDE PAGINATION & FILTERS ──
    async function loadData(page = state.currentPage) {
        state.currentPage = page;
        const tbody = document.getElementById('insuranceTableBody');
        if (tbody) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="11" class="text-center py-10">
                        <div class="flex flex-col items-center justify-center gap-2">
                            <i data-lucide="loader-2" class="w-8 h-8 animate-spin" style="color: var(--green);"></i>
                            <span class="font-bold">Fetching insurance records...</span>
                        </div>
                    </td>
                </tr>`;
            lucide.createIcons();
        }

        const searchVal = getVal('fSearch');
        const clearBtn = document.getElementById('btnClearSearch');
        if (clearBtn) {
            clearBtn.classList.toggle('hidden', !searchVal);
        }

        const params = new URLSearchParams({
            action: 'list',
            page: state.currentPage,
            limit: state.perPage,
            sort_by: state.sortBy,
            sort_dir: state.sortDir,
            search: searchVal,
            insurance_type: getVal('fInsuranceType'),
            claim_status: getVal('fClaimStatus'),
            date_type: 'created_at',
            date_from: getVal('fDateFrom'),
            date_to: getVal('fDateTo')
        });

        try {
            const res = await fetch(`${API_BASE}ipd-insurance?${params.toString()}`);
            const json = await res.json();

            if (!json || !json.success || !json.data) {
                renderEmptyTable(json.message || 'Failed to load records');
                return;
            }

            const data = json.data;
            renderKPIs(data.stats || {});
            renderTable(data.records || []);
            renderPagination(data.pagination || {});
        } catch (err) {
            console.error('Error fetching insurance records:', err);
            renderEmptyTable('Network or server error loading insurance records');
        }
    }

    function getVal(id) {
        const el = document.getElementById(id);
        return el ? el.value.trim() : '';
    }

    // ── 4. RENDER KPI CARDS ──
    function renderKPIs(stats) {
        setElemText('kpiTotalCount', (parseInt(stats.total_count) || 0).toLocaleString('en-IN'));
        setElemText('kpiApproved', formatINR(stats.total_approved));
        setElemText('kpiReceived', formatINR(stats.total_received));
        setElemText('kpiPending', formatINR(stats.total_pending));
        setElemText('kpiPatientPayable', formatINR(stats.total_patient_payable));
    }

    function setElemText(id, text) {
        const el = document.getElementById(id);
        if (el) el.textContent = text;
    }

    // ── 5. RENDER PATIENTS TABLE ──
    function renderTable(records) {
        const tbody = document.getElementById('insuranceTableBody');
        if (!tbody) return;

        if (!records || records.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="11" class="text-center py-12">
                        <div class="flex flex-col items-center justify-center gap-2 opacity-75">
                            <i data-lucide="folder-search" class="w-12 h-12 stroke-[1.5]" style="color: var(--green);"></i>
                            <div class="font-extrabold text-base">No insurance records found</div>
                            <div class="text-xs">Try adjusting your search criteria or resetting filters</div>
                        </div>
                    </td>
                </tr>`;
            lucide.createIcons();
            return;
        }

        let html = '';
        records.forEach(r => {
            const patientName = ((r.first_name || '') + ' ' + (r.last_name || '')).trim() || 'Unknown Patient';
            const genderAge = [(r.patient_gender || ''), (r.patient_age ? r.patient_age + 'y' : '')].filter(Boolean).join(', ');
            const statusClass = `status-${r.claim_status || 'PENDING'}`;
            const typeLabel = r.insurance_type || 'INSURANCE';
            const sponsorName = r.company_name || r.tpa_name || '—';

            html += `
                <tr>
                    <!-- ID / Bill No -->
                    <td>
                        <div class="font-black text-xs" style="color: var(--green);">#INS-${r.insurance_id}</div>
                        ${r.bill_id ? `<div class="text-xs font-bold opacity-85 mt-0.5"><a href="ipd_billing.php" class="hover:underline flex items-center gap-1"><i data-lucide="receipt" class="w-3 h-3 inline"></i> ${escapeHtml(r.bill_id)}</a></div>` : '<div class="text-xs opacity-60">No Bill</div>'}
                        <div class="text-[11px] opacity-65 mt-0.5">${formatDate(r.created_at)}</div>
                    </td>

                    <!-- Patient Details -->
                    <td>
                        <div class="font-extrabold text-sm flex items-center gap-1.5" style="color: var(--green);">
                            <i data-lucide="user" class="w-3.5 h-3.5 inline"></i> ${escapeHtml(patientName)}
                        </div>
                        <div class="text-xs opacity-80 mt-0.5">
                            <span class="font-bold">${escapeHtml(r.patient_id || '—')}</span> ${genderAge ? `· ${escapeHtml(genderAge)}` : ''}
                        </div>
                        ${r.patient_phone ? `<div class="text-[11px] opacity-75 mt-0.5"><i data-lucide="phone" class="w-3 h-3 inline"></i> ${escapeHtml(r.patient_phone)}</div>` : ''}
                    </td>

                    <!-- Admission / Ward -->
                    <td>
                        <div class="font-bold text-xs" style="color: var(--green);">
                            <i data-lucide="bed" class="w-3.5 h-3.5 inline"></i> ${escapeHtml(r.admission_id || '—')}
                        </div>
                        <div class="text-xs opacity-80 mt-0.5">
                            ${escapeHtml(r.ward_name || r.room_type || 'General')} ${r.room_no ? `(Bed/Room ${escapeHtml(r.room_no)})` : ''}
                        </div>
                        <div class="text-[11px] opacity-65 mt-0.5">Adm: ${formatDate(r.admission_date)}</div>
                    </td>

                    <!-- Sponsor & TPA -->
                    <td>
                        <div class="flex items-center gap-1.5 flex-wrap mb-1">
                            <span class="type-pill">${escapeHtml(typeLabel)}</span>
                        </div>
                        <div class="font-extrabold text-xs" style="color: var(--green);" title="${escapeHtml(sponsorName)}">
                            ${escapeHtml(sponsorName)}
                        </div>
                        ${r.tpa_name && r.tpa_name !== r.company_name ? `<div class="text-[11px] opacity-75 mt-0.5">TPA: ${escapeHtml(r.tpa_name)}</div>` : ''}
                        ${r.tpa_reference_no ? `<div class="text-[11px] opacity-65">Ref: ${escapeHtml(r.tpa_reference_no)}</div>` : ''}
                    </td>

                    <!-- Policy & Claim -->
                    <td>
                        <div class="text-xs">
                            <span class="font-bold opacity-75">Pol:</span> <strong class="font-extrabold">${escapeHtml(r.policy_number || '—')}</strong>
                        </div>
                        ${r.claim_number ? `<div class="text-xs mt-0.5"><span class="font-bold opacity-75">Claim:</span> ${escapeHtml(r.claim_number)}</div>` : ''}
                        ${r.approval_number ? `<div class="text-[11px] opacity-75 mt-0.5"><span class="font-bold">Auth:</span> ${escapeHtml(r.approval_number)}</div>` : ''}
                    </td>

                    <!-- Approved Amount -->
                    <td class="text-right">
                        <div class="font-black text-xs" style="color: #15803d;">
                            ${formatINR(r.approved_amount)}
                        </div>
                    </td>

                    <!-- Settled Amount -->
                    <td class="text-right">
                        <div class="font-bold text-xs" style="color: #1d4ed8;">
                            ${formatINR(r.received_amount)}
                        </div>
                    </td>

                    <!-- Pending Amount -->
                    <td class="text-right">
                        <div class="font-black text-xs" style="color: ${parseFloat(r.pending_amount) > 0 ? '#b91c1c' : 'var(--green)'};">
                            ${formatINR(r.pending_amount)}
                        </div>
                    </td>

                    <!-- Claim Status -->
                    <td class="text-center">
                        <span class="status-pill ${statusClass}">${escapeHtml((r.claim_status || 'PENDING').replace('_', ' '))}</span>
                    </td>

                    <!-- Dates -->
                    <td>
                        ${r.submitted_date ? `<div class="text-[11px]"><span class="opacity-65">Sub:</span> ${formatDate(r.submitted_date)}</div>` : ''}
                        ${r.approved_date ? `<div class="text-[11px] text-green-700 font-semibold"><span class="opacity-65">App:</span> ${formatDate(r.approved_date)}</div>` : ''}
                        ${r.settled_date ? `<div class="text-[11px] text-blue-700 font-semibold"><span class="opacity-65">Set:</span> ${formatDate(r.settled_date)}</div>` : ''}
                        ${!r.submitted_date && !r.approved_date && !r.settled_date ? `<div class="text-[11px] opacity-60">Created ${formatDate(r.created_at)}</div>` : ''}
                    </td>

                    <!-- Actions -->
                    <td class="text-center">
                        <div class="flex items-center justify-center gap-1.5">
                            <button type="button" class="btn-ins-action btn-ins-edit" title="Edit Full Insurance Record" onclick="insManager.openEditModal(${r.insurance_id})">
                                <i data-lucide="edit-3" class="w-3.5 h-3.5"></i> Edit
                            </button>
                            <button type="button" class="btn-ins-action btn-ins-view" title="View Details" onclick="insManager.openViewModal(${r.insurance_id})">
                                <i data-lucide="eye" class="w-3.5 h-3.5"></i>
                            </button>
                        </div>
                    </td>
                </tr>`;
        });

        tbody.innerHTML = html;
        lucide.createIcons();
    }

    function renderEmptyTable(msg) {
        const tbody = document.getElementById('insuranceTableBody');
        if (tbody) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="11" class="text-center py-10">
                        <div class="text-red-700 font-bold text-sm">${escapeHtml(msg)}</div>
                    </td>
                </tr>`;
        }
    }

    // ── 6. RENDER PAGINATION CONTROLS ──
    function renderPagination(pg) {
        const infoText = document.getElementById('pageInfoText');
        if (infoText) {
            if (pg.total_records > 0) {
                infoText.textContent = `Showing ${pg.from_record} to ${pg.to_record} of ${pg.total_records} records`;
            } else {
                infoText.textContent = 'Showing 0 to 0 of 0 records';
            }
        }

        const container = document.getElementById('paginationControls');
        if (!container) return;

        const current = pg.current_page || 1;
        const total = pg.total_pages || 1;

        if (total <= 1) {
            container.innerHTML = '';
            return;
        }

        let html = '';

        // First button
        html += `<button class="page-btn" ${current === 1 ? 'disabled' : ''} onclick="insManager.goToPage(1)" title="First Page"><i data-lucide="chevrons-left" class="w-3.5 h-3.5"></i></button>`;
        // Prev button
        html += `<button class="page-btn" ${current === 1 ? 'disabled' : ''} onclick="insManager.goToPage(${current - 1})" title="Previous Page"><i data-lucide="chevron-left" class="w-3.5 h-3.5"></i></button>`;

        // Page numbers window (e.g. current +/- 2)
        const startPage = Math.max(1, current - 2);
        const endPage = Math.min(total, current + 2);

        if (startPage > 1) {
            html += `<button class="page-btn" onclick="insManager.goToPage(1)">1</button>`;
            if (startPage > 2) html += `<span class="px-1 text-xs font-bold">...</span>`;
        }

        for (let i = startPage; i <= endPage; i++) {
            html += `<button class="page-btn ${i === current ? 'active' : ''}" onclick="insManager.goToPage(${i})">${i}</button>`;
        }

        if (endPage < total) {
            if (endPage < total - 1) html += `<span class="px-1 text-xs font-bold">...</span>`;
            html += `<button class="page-btn" onclick="insManager.goToPage(${total})">${total}</button>`;
        }

        // Next button
        html += `<button class="page-btn" ${current === total ? 'disabled' : ''} onclick="insManager.goToPage(${current + 1})" title="Next Page"><i data-lucide="chevron-right" class="w-3.5 h-3.5"></i></button>`;
        // Last button
        html += `<button class="page-btn" ${current === total ? 'disabled' : ''} onclick="insManager.goToPage(${total})" title="Last Page"><i data-lucide="chevrons-right" class="w-3.5 h-3.5"></i></button>`;

        container.innerHTML = html;
        lucide.createIcons();
    }

    // ── 7. OPEN EDIT MODAL (POPULATES ALL 25 FIELDS) ──
    async function openEditModal(insuranceId) {
        try {
            const res = await fetch(`${API_BASE}ipd-insurance?insurance_id=${insuranceId}`);
            const json = await res.json();
            if (!json || !json.success || !json.data) {
                showToast(json.message || 'Failed to load insurance record', 'error');
                return;
            }

            const rec = json.data;
            state.currentRecord = rec;

            // Hidden Keys
            setVal('edit_insurance_id', rec.insurance_id);
            setVal('edit_bill_id', rec.bill_id || '');
            setVal('edit_admission_id', rec.admission_id || '');
            setVal('edit_patient_id', rec.patient_id || '');

            // Patient Banner
            const ptName = ((rec.first_name || '') + ' ' + (rec.last_name || '')).trim() || 'Unknown Patient';
            setElemText('editBannerPtName', ptName);
            setElemText('editBannerPtMeta', `PID: ${rec.patient_id || '—'} | Admission: ${rec.admission_id || '—'} | Bill: ${rec.bill_id || '—'}`);
            setElemText('editBannerType', rec.insurance_type || 'INSURANCE');
            setElemText('editBannerBed', `Ward: ${rec.ward_name || rec.room_type || 'General'} ${rec.room_no ? `(Bed ${rec.room_no})` : ''}`);

            // Form Fields
            setVal('edit_insurance_type', rec.insurance_type || 'INSURANCE');
            setVal('edit_company_name', rec.company_name || '');
            setVal('edit_insurance_company_id', rec.insurance_company_id || '');
            setVal('edit_tpa_name', rec.tpa_name || '');
            setVal('edit_tpa_reference_no', rec.tpa_reference_no || '');
            setVal('edit_policy_number', rec.policy_number || '');
            setVal('edit_claim_number', rec.claim_number || '');
            setVal('edit_approval_number', rec.approval_number || '');
            setVal('edit_approved_amount', parseFloat(rec.approved_amount) || 0);
            setVal('edit_received_amount', parseFloat(rec.received_amount) || 0);
            setVal('edit_pending_amount', parseFloat(rec.pending_amount) || 0);
            setVal('edit_patient_payable', parseFloat(rec.patient_payable) || 0);
            setVal('edit_claim_status', rec.claim_status || 'PENDING');
            setVal('edit_submitted_date', rec.submitted_date || '');
            setVal('edit_approved_date', rec.approved_date || '');
            setVal('edit_settled_date', rec.settled_date || '');
            setVal('edit_rejection_reason', rec.rejection_reason || '');
            setVal('edit_remarks', rec.remarks || '');

            // Audit
            setElemText('editAuditCreatedBy', rec.created_by || 'system');
            setElemText('editAuditCreatedAt', formatDate(rec.created_at));
            setElemText('editAuditUpdatedAt', formatDate(rec.updated_at));

            calcPendingAmount();

            openModal('modalEditInsurance');
        } catch (err) {
            console.error('Error opening edit modal:', err);
            showToast('Failed to load insurance details', 'error');
        }
    }

    function setVal(id, val) {
        const el = document.getElementById(id);
        if (el) el.value = val;
    }

    // ── 8. CALCULATE PENDING AMOUNT IN EDIT MODAL ──
    function calcPendingAmount() {
        const app = parseFloat(document.getElementById('edit_approved_amount')?.value) || 0;
        const rec = parseFloat(document.getElementById('edit_received_amount')?.value) || 0;
        const pending = Math.max(0, app - rec);
        setVal('edit_pending_amount', pending.toFixed(2));
    }

    function onStatusChange(status) {
        const rejGroup = document.getElementById('editRejectionGroup');
        if (rejGroup) {
            if (status === 'REJECTED' || status === 'DISPUTE') {
                rejGroup.style.display = 'flex';
            }
        }
    }

    // ── 9. SUBMIT UPDATE (API action: update_full) ──
    async function submitUpdate() {
        const insuranceId = parseInt(document.getElementById('edit_insurance_id')?.value) || 0;
        if (!insuranceId) {
            showToast('Invalid insurance record ID', 'error');
            return;
        }

        const companyName = getVal('edit_company_name');
        if (!companyName) {
            showToast('Insurance Company Name is required', 'error');
            document.getElementById('edit_company_name')?.focus();
            return;
        }

        const btn = document.getElementById('btnUpdateInsurance');
        if (btn) {
            btn.disabled = true;
            btn.innerHTML = `<i data-lucide="loader-2" class="w-4 h-4 animate-spin inline mr-1"></i> Saving...`;
            lucide.createIcons();
        }

        const payload = {
            action: 'update_full',
            insurance_id: insuranceId,
            bill_id: getVal('edit_bill_id'),
            admission_id: getVal('edit_admission_id'),
            patient_id: getVal('edit_patient_id'),
            insurance_type: getVal('edit_insurance_type'),
            company_name: companyName,
            insurance_company_id: getVal('edit_insurance_company_id'),
            tpa_name: getVal('edit_tpa_name'),
            tpa_reference_no: getVal('edit_tpa_reference_no'),
            policy_number: getVal('edit_policy_number'),
            claim_number: getVal('edit_claim_number'),
            approval_number: getVal('edit_approval_number'),
            approved_amount: parseFloat(getVal('edit_approved_amount')) || 0,
            received_amount: parseFloat(getVal('edit_received_amount')) || 0,
            pending_amount: parseFloat(getVal('edit_pending_amount')) || 0,
            patient_payable: parseFloat(getVal('edit_patient_payable')) || 0,
            claim_status: getVal('edit_claim_status'),
            submitted_date: getVal('edit_submitted_date') || null,
            approved_date: getVal('edit_approved_date') || null,
            settled_date: getVal('edit_settled_date') || null,
            rejection_reason: getVal('edit_rejection_reason') || null,
            remarks: getVal('edit_remarks') || null
        };

        try {
            const res = await fetch(`${API_BASE}ipd-insurance`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });
            const json = await res.json();

            if (json && json.success) {
                showToast('Insurance record updated successfully!', 'success');
                closeModal('modalEditInsurance');
                loadData(state.currentPage);
                loadDistinctCompanies();
            } else {
                showToast(json.message || 'Failed to update insurance record', 'error');
            }
        } catch (err) {
            console.error('Error updating insurance record:', err);
            showToast('Server error while updating insurance record', 'error');
        } finally {
            if (btn) {
                btn.disabled = false;
                btn.innerHTML = `<i data-lucide="check-circle" class="w-4 h-4 inline mr-1"></i> Update Record`;
                lucide.createIcons();
            }
        }
    }

    // ── 10. OPEN VIEW DETAILS MODAL ──
    async function openViewModal(insuranceId) {
        try {
            const res = await fetch(`${API_BASE}ipd-insurance?insurance_id=${insuranceId}`);
            const json = await res.json();
            if (!json || !json.success || !json.data) {
                showToast(json.message || 'Failed to load record details', 'error');
                return;
            }

            const rec = json.data;
            state.currentRecord = rec;

            const ptName = ((rec.first_name || '') + ' ' + (rec.last_name || '')).trim() || 'Unknown Patient';
            const genderAge = [(rec.patient_gender || ''), (rec.patient_age ? rec.patient_age + ' yrs' : '')].filter(Boolean).join(', ');

            const body = document.getElementById('viewModalBody');
            if (body) {
                body.innerHTML = `
                    <div class="p-3.5 rounded-xl border mb-4" style="background: rgba(31,107,74,0.06); border-color: var(--green);">
                        <div class="flex justify-between items-center flex-wrap gap-2">
                            <div>
                                <div class="text-base font-black" style="color: var(--green);">${escapeHtml(ptName)}</div>
                                <div class="text-xs opacity-80 mt-0.5">PID: ${escapeHtml(rec.patient_id || '—')} · ${escapeHtml(genderAge)} ${rec.patient_phone ? `· Phone: ${escapeHtml(rec.patient_phone)}` : ''}</div>
                            </div>
                            <span class="status-pill status-${rec.claim_status || 'PENDING'}">${escapeHtml((rec.claim_status || 'PENDING').replace('_', ' '))}</span>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3 text-xs mb-4">
                        <div class="p-3 bg-white rounded-lg border border-slate-200">
                            <div class="font-bold opacity-60 uppercase text-[10px]">Admission ID</div>
                            <div class="font-extrabold text-sm mt-0.5">${escapeHtml(rec.admission_id || '—')}</div>
                            <div class="opacity-75 mt-1">${escapeHtml(rec.ward_name || rec.room_type || 'General')} ${rec.room_no ? `(Bed ${rec.room_no})` : ''}</div>
                        </div>
                        <div class="p-3 bg-white rounded-lg border border-slate-200">
                            <div class="font-bold opacity-60 uppercase text-[10px]">IPD Bill Number</div>
                            <div class="font-extrabold text-sm mt-0.5">${escapeHtml(rec.bill_id || '—')}</div>
                            <div class="opacity-75 mt-1">Bill Status: <strong>${escapeHtml(rec.bill_status || 'OPEN')}</strong></div>
                        </div>
                    </div>

                    <div class="p-3.5 bg-white rounded-xl border border-slate-200 mb-4">
                        <div class="font-extrabold text-xs mb-2 text-emerald-900 uppercase">Sponsor & Policy Information</div>
                        <div class="grid grid-cols-2 gap-2.5 text-xs">
                            <div><span class="opacity-65">Type:</span> <strong>${escapeHtml(rec.insurance_type || 'INSURANCE')}</strong></div>
                            <div><span class="opacity-65">Company:</span> <strong>${escapeHtml(rec.company_name || '—')}</strong></div>
                            <div><span class="opacity-65">TPA Name:</span> <strong>${escapeHtml(rec.tpa_name || '—')}</strong></div>
                            <div><span class="opacity-65">TPA Ref No:</span> <strong>${escapeHtml(rec.tpa_reference_no || '—')}</strong></div>
                            <div><span class="opacity-65">Policy Number:</span> <strong>${escapeHtml(rec.policy_number || '—')}</strong></div>
                            <div><span class="opacity-65">Claim Number:</span> <strong>${escapeHtml(rec.claim_number || '—')}</strong></div>
                            <div class="col-span-2"><span class="opacity-65">Approval Number:</span> <strong>${escapeHtml(rec.approval_number || '—')}</strong></div>
                        </div>
                    </div>

                    <div class="p-3.5 rounded-xl border mb-4" style="background: var(--cream); border-color: var(--green);">
                        <div class="font-extrabold text-xs mb-2 uppercase" style="color: var(--green);">Financial Summary</div>
                        <div class="grid grid-cols-4 gap-2 text-center">
                            <div class="p-2 bg-white rounded-lg border">
                                <div class="text-[10px] font-bold opacity-75">APPROVED</div>
                                <div class="text-xs font-black text-green-700 mt-1">${formatINR(rec.approved_amount)}</div>
                            </div>
                            <div class="p-2 bg-white rounded-lg border">
                                <div class="text-[10px] font-bold opacity-75">SETTLED</div>
                                <div class="text-xs font-black text-blue-700 mt-1">${formatINR(rec.received_amount)}</div>
                            </div>
                            <div class="p-2 bg-white rounded-lg border">
                                <div class="text-[10px] font-bold opacity-75">PENDING</div>
                                <div class="text-xs font-black text-red-700 mt-1">${formatINR(rec.pending_amount)}</div>
                            </div>
                            <div class="p-2 bg-white rounded-lg border">
                                <div class="text-[10px] font-bold opacity-75">PATIENT PAYABLE</div>
                                <div class="text-xs font-black text-purple-700 mt-1">${formatINR(rec.patient_payable)}</div>
                            </div>
                        </div>
                    </div>

                    <div class="p-3.5 bg-white rounded-xl border border-slate-200 text-xs">
                        <div class="font-extrabold text-xs mb-2 text-emerald-900 uppercase">Dates & Notes</div>
                        <div class="grid grid-cols-3 gap-2 mb-2">
                            <div><span class="opacity-65">Submitted:</span> <strong>${formatDate(rec.submitted_date)}</strong></div>
                            <div><span class="opacity-65">Approved:</span> <strong>${formatDate(rec.approved_date)}</strong></div>
                            <div><span class="opacity-65">Settled:</span> <strong>${formatDate(rec.settled_date)}</strong></div>
                        </div>
                        ${rec.rejection_reason ? `<div class="p-2 bg-red-50 text-red-800 rounded border border-red-200 mt-2"><strong>Rejection/Dispute:</strong> ${escapeHtml(rec.rejection_reason)}</div>` : ''}
                        ${rec.remarks ? `<div class="mt-2 opacity-85"><strong>Remarks:</strong> ${escapeHtml(rec.remarks)}</div>` : ''}
                    </div>
                `;
            }

            openModal('modalViewInsurance');
        } catch (err) {
            console.error('Error loading view modal:', err);
            showToast('Failed to view record', 'error');
        }
    }

    function switchViewToEdit() {
        closeModal('modalViewInsurance');
        if (state.currentRecord && state.currentRecord.insurance_id) {
            openEditModal(state.currentRecord.insurance_id);
        }
    }

    // ── 11. MODAL HELPERS ──
    function openModal(id) {
        const el = document.getElementById(id);
        if (el) el.classList.add('active');
        lucide.createIcons();
    }

    function closeModal(id) {
        const el = document.getElementById(id);
        if (el) el.classList.remove('active');
    }

    // ── 12. FILTER & SORT CONTROLS ──
    function debouncedSearch() {
        clearTimeout(state.filterDebounce);
        state.filterDebounce = setTimeout(() => {
            loadData(1);
        }, 350);
    }

    function clearSearch() {
        setVal('fSearch', '');
        const clearBtn = document.getElementById('btnClearSearch');
        if (clearBtn) clearBtn.classList.add('hidden');
        loadData(1);
    }

    function resetFilters() {
        setVal('fSearch', '');
        setVal('fDateFrom', '');
        setVal('fDateTo', '');
        setVal('fInsuranceType', 'ALL');
        setVal('fClaimStatus', 'ALL');
        setVal('fPerPage', '25');
        state.perPage = 25;

        const clearBtn = document.getElementById('btnClearSearch');
        if (clearBtn) clearBtn.classList.add('hidden');

        loadData(1);
    }

    function goToPage(page) {
        if (page < 1 || page === state.currentPage) return;
        loadData(page);
    }

    function changePerPage(limit) {
        state.perPage = parseInt(limit) || 25;
        loadData(1);
    }

    function sortBy(col) {
        if (state.sortBy === col) {
            state.sortDir = state.sortDir === 'ASC' ? 'DESC' : 'ASC';
        } else {
            state.sortBy = col;
            state.sortDir = 'DESC';
        }
        loadData(1);
    }

    // ── 13. TOAST NOTIFICATIONS ──
    function showToast(msg, type = 'success') {
        const container = document.getElementById('toastContainer');
        if (!container) return;

        const toast = document.createElement('div');
        toast.className = 'toast-msg';
        if (type === 'error') {
            toast.style.background = '#991b1b';
            toast.style.borderColor = '#fca5a5';
        }

        toast.innerHTML = `
            <i data-lucide="${type === 'error' ? 'alert-octagon' : 'check-circle-2'}" class="w-5 h-5 flex-shrink-0"></i>
            <span>${escapeHtml(msg)}</span>
        `;
        container.appendChild(toast);
        lucide.createIcons();

        setTimeout(() => {
            toast.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
            toast.style.opacity = '0';
            toast.style.transform = 'translateX(100%)';
            setTimeout(() => toast.remove(), 300);
        }, 3500);
    }

    // ── 14. HTML ESCAPING ──
    function escapeHtml(str) {
        if (!str && str !== 0) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    // Expose controller globally
    window.insManager = {
        loadData,
        debouncedSearch,
        clearSearch,
        resetFilters,
        goToPage,
        changePerPage,
        sortBy,
        openEditModal,
        openViewModal,
        switchViewToEdit,
        closeModal,
        calcPendingAmount,
        onStatusChange,
        submitUpdate,
        showToast
    };

})();
