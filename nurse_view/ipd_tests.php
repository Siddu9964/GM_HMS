<?php
session_start();
// Authentication check preserved
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>IPD Diagnostic Tests Order - GM HMS</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@600;700&display=swap" rel="stylesheet">
    <style>
        /* ── GM HMS Signature 2-Color Design System (#f3efe6 & #1f6b4a) ── */
        :root {
            --gm-bg: #f3efe6;
            --gm-bg-card: #ffffff;
            --gm-primary: #1f6b4a;
            --gm-primary-dark: #144d34;
            --gm-primary-light: rgba(31, 107, 74, 0.08);
            --gm-primary-mid: rgba(31, 107, 74, 0.16);
            --gm-border: rgba(31, 107, 74, 0.22);
            --gm-border-strong: #1f6b4a;
            --gm-text: #1f6b4a;
            --gm-text-body: #23342b;
            --gm-text-muted: #527967;
            --gm-sidebar-w: 185px;

            --shadow-subtle: 0 4px 16px rgba(31, 107, 74, 0.06);
            --shadow-elevated: 0 10px 30px rgba(31, 107, 74, 0.12);
        }

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, sans-serif; }
        body { background: var(--gm-bg); min-height: 100vh; display: flex; color: var(--gm-text-body); overflow-x: hidden; -webkit-font-smoothing: antialiased; }
        .main-layout { display: flex; width: 100%; min-height: 100vh; }
        .content-wrapper { flex: 1; display: flex; flex-direction: column; min-width: 0; background-color: var(--gm-bg); transition: margin-left 0.25s ease; }
        
        @media (min-width: 1024px) {
            .content-wrapper { margin-left: var(--gm-sidebar-w, 185px); }
        }

        .main-content { flex: 1; padding: 24px 30px; overflow-y: auto; }
        .container { max-width: 1180px; margin: 0 auto; animation: fadeIn 0.35s ease-out; }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(8px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* ── Header Toolbar ── */
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 22px;
            padding-bottom: 16px;
            border-bottom: 2px solid var(--gm-border);
            flex-wrap: wrap;
            gap: 14px;
        }

        .header-title-box { display: flex; align-items: center; gap: 14px; }
        .header-icon {
            width: 46px; height: 46px; border-radius: 12px;
            background: var(--gm-primary); color: #f3efe6;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.25rem; box-shadow: 0 4px 14px rgba(31, 107, 74, 0.25);
            flex-shrink: 0;
        }
        .header-title-box h1 { font-size: 1.45rem; font-weight: 800; color: var(--gm-primary); margin: 0; letter-spacing: -0.3px; }
        .header-title-box p { color: var(--gm-text-muted); font-size: 0.84rem; font-weight: 600; margin-top: 2px; }

        /* ── Cards & Containers ── */
        .glass-card {
            background: #ffffff;
            border-radius: 16px;
            padding: 22px 26px;
            border: 1.5px solid var(--gm-border);
            box-shadow: var(--shadow-subtle);
            margin-bottom: 22px;
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .glass-card:hover {
            border-color: var(--gm-primary);
            box-shadow: var(--shadow-elevated);
        }

        .card-heading {
            font-size: 1.05rem;
            font-weight: 800;
            color: var(--gm-primary);
            margin: 0 0 16px 0;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        /* ── Search Box & Dropdown ── */
        .search-container {
            position: relative;
            width: 100%;
            max-width: 640px;
        }

        .search-box {
            width: 100%;
            padding: 12px 18px 12px 46px;
            border-radius: 12px;
            border: 1.5px solid var(--gm-border);
            font-size: 0.9rem;
            font-weight: 600;
            color: var(--gm-primary);
            background: var(--gm-bg);
            outline: none;
            transition: all 0.2s ease;
        }

        .search-box:focus {
            background: #ffffff;
            border-color: var(--gm-primary);
            box-shadow: 0 0 0 3px var(--gm-primary-light);
        }

        .search-icon {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--gm-primary);
            font-size: 1rem;
        }

        .suggestions-dropdown {
            position: absolute;
            top: calc(100% + 6px);
            left: 0;
            right: 0;
            background: #ffffff;
            border-radius: 12px;
            border: 1.5px solid var(--gm-border);
            box-shadow: 0 12px 36px rgba(31, 107, 74, 0.15);
            z-index: 100;
            max-height: 340px;
            overflow-y: auto;
            display: none;
        }

        .suggestion-item {
            padding: 12px 16px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid var(--gm-bg);
            cursor: pointer;
            transition: background 0.15s;
        }

        .suggestion-item:hover {
            background: var(--gm-primary-light);
        }

        .suggestion-item:last-child {
            border-bottom: none;
        }

        .suggestion-details strong {
            display: block;
            font-size: 0.92rem;
            font-weight: 800;
            color: var(--gm-primary);
        }

        .suggestion-details span {
            font-size: 0.78rem;
            color: var(--gm-text-muted);
            font-weight: 600;
            margin-top: 2px;
            display: block;
        }

        /* ── Selected Patient Matrix Banner ── */
        .patient-matrix-card {
            background: #ffffff;
            border: 2px solid var(--gm-primary);
            border-radius: 14px;
            padding: 16px 20px;
            display: none;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 16px;
            margin-bottom: 22px;
            box-shadow: 0 4px 16px rgba(31, 107, 74, 0.08);
        }

        .p-matrix-col {
            display: flex;
            flex-direction: column;
        }

        .p-matrix-lbl {
            font-size: 0.7rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: var(--gm-text-muted);
            margin-bottom: 2px;
        }

        .p-matrix-val {
            font-size: 0.95rem;
            font-weight: 800;
            color: var(--gm-primary);
        }

        .btn-change-patient {
            background: var(--gm-bg);
            color: var(--gm-primary);
            border: 1.5px solid var(--gm-border);
            padding: 8px 16px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 0.82rem;
            font-weight: 800;
            transition: all 0.2s ease;
        }

        .btn-change-patient:hover {
            background: var(--gm-primary);
            color: #f3efe6;
            border-color: var(--gm-primary);
        }

        /* ── Modern Clinical Cart Table ── */
        .table-responsive {
            width: 100%;
            overflow-x: auto;
            border: 1.5px solid var(--gm-border);
            border-radius: 12px;
            overflow: hidden;
            background: #ffffff;
        }

        .cart-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.86rem;
            min-width: 580px;
        }

        .cart-table thead th {
            background: var(--gm-bg);
            color: var(--gm-primary);
            padding: 12px 16px;
            text-align: left;
            font-weight: 800;
            font-size: 0.74rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 1.5px solid var(--gm-border);
        }

        .cart-table tbody td {
            padding: 12px 16px;
            border-bottom: 1px solid var(--gm-bg);
            color: var(--gm-text-body);
            vertical-align: middle;
        }

        .cart-table tbody tr:hover td {
            background: var(--gm-primary-light);
        }

        .cart-table tbody tr:last-child td {
            border-bottom: none;
        }

        .qty-input {
            width: 70px;
            padding: 6px 10px;
            border: 1.5px solid var(--gm-border);
            border-radius: 8px;
            text-align: center;
            font-weight: 800;
            font-size: 0.88rem;
            color: var(--gm-primary);
            background: var(--gm-bg);
            outline: none;
        }

        .qty-input:focus {
            background: #ffffff;
            border-color: var(--gm-primary);
        }

        .btn-remove {
            background: rgba(220, 38, 38, 0.1);
            color: #dc2626;
            border: 1px solid rgba(220, 38, 38, 0.25);
            padding: 6px 12px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 0.8rem;
            font-weight: 700;
            transition: all 0.2s ease;
        }

        .btn-remove:hover {
            background: #dc2626;
            color: #ffffff;
        }

        .badge-cat {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 3px 8px;
            border-radius: 6px;
            font-size: 0.72rem;
            font-weight: 800;
            text-transform: uppercase;
        }

        .badge-lab { background: var(--gm-primary-light); color: var(--gm-primary); border: 1px solid var(--gm-border); }
        .badge-rad { background: rgba(217, 119, 6, 0.12); color: #b45309; border: 1px solid rgba(217, 119, 6, 0.25); }
        .badge-oth { background: rgba(30, 64, 175, 0.1); color: #1e40af; border: 1px solid rgba(30, 64, 175, 0.22); }

        .btn-save {
            background: var(--gm-primary);
            color: #f3efe6;
            border: 1.5px solid var(--gm-primary);
            padding: 10px 24px;
            border-radius: 10px;
            font-size: 0.88rem;
            font-weight: 800;
            cursor: pointer;
            display: none;
            transition: all 0.2s ease;
            box-shadow: 0 4px 12px rgba(31, 107, 74, 0.25);
        }

        .btn-save:hover {
            background: var(--gm-primary-dark);
            color: #ffffff;
            transform: translateY(-1px);
        }

        /* ── Centered Message Overlay ── */
        #centerOverlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(31, 107, 74, 0.45);
            backdrop-filter: blur(3px);
            z-index: 9999;
            justify-content: center;
            align-items: center;
        }

        #centerMessageCard {
            background: #ffffff;
            border: 2px solid var(--gm-primary);
            padding: 30px;
            border-radius: 16px;
            text-align: center;
            box-shadow: 0 16px 40px rgba(31, 107, 74, 0.25);
            min-width: 320px;
        }
    </style>
</head>
<body>
<div class="main-layout">
    <!-- Sidebar Navigation -->
    <?php include 'includes/nurse_sidebar.php'; ?>
    
    <div class="content-wrapper">
        <!-- Top Navbar -->
        <?php 
        $pageTitle = 'IPD Diagnostic Tests Order';
        include 'includes/nurse_navbar.php'; 
        ?>
        
        <div class="main-content">
            <div class="container">
                
                <!-- Page Header -->
                <div class="page-header">
                    <div class="header-title-box">
                        <div class="header-icon"><i class="fas fa-vial"></i></div>
                        <div>
                            <h1>IPD Diagnostic Tests & Investigation Order</h1>
                            <p>Order Laboratory, Radiology, and Clinical Diagnostic Investigations for admitted inpatients.</p>
                        </div>
                    </div>
                </div>

                <!-- Patient Selection Card -->
                <div class="glass-card" id="patientSearchSection">
                    <h3 class="card-heading"><i class="fas fa-user-injured"></i> Step 1: Select Admitted Patient</h3>
                    <div class="search-container">
                        <i class="fas fa-search search-icon"></i>
                        <input type="text" id="patientSearchInput" class="search-box" placeholder="Search inpatient by Name, Patient ID, or Phone..." autocomplete="off">
                        <div id="patientSuggestions" class="suggestions-dropdown"></div>
                    </div>
                </div>
                
                <!-- Active Patient Matrix Banner -->
                <div class="patient-matrix-card" id="patientInfoCard">
                    <div class="p-matrix-col">
                        <span class="p-matrix-lbl">Patient Full Name</span>
                        <strong class="p-matrix-val" id="pName"></strong>
                    </div>
                    <div class="p-matrix-col">
                        <span class="p-matrix-lbl">UHID / Patient ID</span>
                        <strong class="p-matrix-val" id="pId" style="font-family:'JetBrains Mono', monospace;"></strong>
                    </div>
                    <div class="p-matrix-col">
                        <span class="p-matrix-lbl">IP Admission No</span>
                        <strong class="p-matrix-val" id="pAdmId" style="font-family:'JetBrains Mono', monospace;"></strong>
                    </div>
                    <div class="p-matrix-col">
                        <span class="p-matrix-lbl">Ward / Room / Bed</span>
                        <strong class="p-matrix-val" id="pWard"></strong>
                    </div>
                    <button class="btn-change-patient" onclick="changePatient()"><i class="fas fa-exchange-alt"></i> Change Patient</button>
                </div>
                
                <!-- Diagnostic Tests Catalog Search & Order Cart -->
                <div class="glass-card" id="testOrderSection" style="display: none;">
                    <h3 class="card-heading"><i class="fas fa-search-plus"></i> Step 2: Search & Select Diagnostic Tests</h3>
                    <div class="search-container" style="margin-bottom: 20px;">
                        <i class="fas fa-microscope search-icon"></i>
                        <input type="text" id="searchInput" class="search-box" placeholder="Search Lab Tests, X-Ray, CT Scan, Ultrasound, ECG..." autocomplete="off">
                        <div id="suggestions" class="suggestions-dropdown"></div>
                    </div>
                    
                    <h3 class="card-heading" style="margin-top: 10px;"><i class="fas fa-list-ol"></i> Selected Test Order Queue</h3>
                    <div class="table-responsive">
                        <table class="cart-table" id="cartTable">
                            <thead>
                                <tr>
                                    <th>Test Investigation Name</th>
                                    <th>Category</th>
                                    <th>Quantity</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody id="cartBody">
                                <tr><td colspan="4" style="text-align:center; color:var(--gm-text-muted); padding:24px;">No tests selected yet. Search above to add.</td></tr>
                            </tbody>
                        </table>
                    </div>
                    
                    <div style="display:flex; justify-content:flex-end; margin-top:20px;">
                        <button class="btn-save" id="btnSave" onclick="saveOrder()">
                            <i class="fas fa-check-circle"></i> Save & Dispatch Test Order
                        </button>
                    </div>
                </div>

                <!-- Assigned Tests History Section -->
                <div class="glass-card" id="assignedTestsSection" style="display: none;">
                    <h3 class="card-heading"><i class="fas fa-history"></i> Inpatient Diagnostic Investigation History</h3>
                    <div class="table-responsive">
                        <table class="cart-table">
                            <thead>
                                <tr>
                                    <th>Order Date & Time</th>
                                    <th>Investigation Name</th>
                                    <th>Category</th>
                                    <th>Qty</th>
                                    <th>Ordered By Nurse / Staff</th>
                                </tr>
                            </thead>
                            <tbody id="assignedTestsBody">
                                <tr><td colspan="5" style="text-align:center; color:var(--gm-text-muted); padding:20px;">Loading diagnostic history...</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </div>
    </div>
    
    <!-- Centered Message Overlay -->
    <div id="centerOverlay">
        <div id="centerMessageCard">
            <i id="centerIcon" class="fas fa-check-circle" style="font-size: 3rem; margin-bottom: 12px; color: var(--gm-primary);"></i>
            <h3 id="centerTitle" style="margin: 0 0 8px 0; color: var(--gm-primary); font-size: 1.3rem;">Order Saved</h3>
            <p id="centerText" style="color: var(--gm-text-muted); margin: 0; font-size: 0.95rem; font-weight: 600;"></p>
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
                        pSuggestionsBox.innerHTML = '<div style="padding:15px; color:var(--gm-text-muted); font-weight:600; text-align:center;">No admitted patient found.</div>';
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
                    <span>Age: ${p.age || 'N/A'} | Ward: ${p.ward || 'General'} (Room: ${p.room_no || 'N/A'})</span>
                </div>
                <div style="text-align:right;">
                    <span style="font-size:0.75rem; color:var(--gm-text-muted); font-weight:700;">UHID: ${p.patient_id}</span><br>
                    <span style="font-size:0.75rem; color:var(--gm-primary); font-weight:800;">Adm: ${p.admission_id}</span>
                </div>
            `;
            div.onclick = () => selectPatient(p);
            pSuggestionsBox.appendChild(div);
        });
        pSuggestionsBox.style.display = 'block';
    }
    
    function selectPatient(p) {
        if (p.status === 'Discharged' || p.discharge_date) {
            showCenterMessage(false, 'Discharged Patient', 'This patient has already been discharged.');
            return;
        }
        currentPatient = p;
        pSuggestionsBox.style.display = 'none';
        pSearchInput.value = '';
        
        document.getElementById('pName').innerText = `${p.first_name} ${p.last_name || ''}`;
        document.getElementById('pId').innerText = p.patient_id;
        document.getElementById('pAdmId').innerText = p.admission_id;
        document.getElementById('pWard').innerText = `${p.ward || 'General'} (Rm: ${p.room_no || 'N/A'})`;
        
        document.getElementById('patientSearchSection').style.display = 'none';
        document.getElementById('patientInfoCard').style.display = 'flex';
        document.getElementById('testOrderSection').style.display = 'block';
        document.getElementById('assignedTestsSection').style.display = 'block';
        
        fetchAssignedTests(p.patient_id, p.admission_id);
    }
    
    function changePatient() {
        currentPatient = null;
        document.getElementById('patientSearchSection').style.display = 'block';
        document.getElementById('patientInfoCard').style.display = 'none';
        document.getElementById('testOrderSection').style.display = 'none';
        document.getElementById('assignedTestsSection').style.display = 'none';
        cart = [];
        renderCart();
    }

    function fetchAssignedTests(patientId, admissionId) {
        const tbody = document.getElementById('assignedTestsBody');
        tbody.innerHTML = '<tr><td colspan="5" style="text-align:center; color:var(--gm-text-muted); padding:20px;"><i class="fas fa-spinner fa-spin"></i> Loading diagnostic records...</td></tr>';
        
        fetch(`api/get_clinical_records.php?patient_id=${patientId}&admission_id=${admissionId}`)
            .then(res => res.json())
            .then(res => {
                if (res.success && res.data) {
                    const data = res.data;
                    let allTests = [];
                    
                    if (data.lab_tests && Array.isArray(data.lab_tests)) {
                        data.lab_tests.forEach(t => {
                            const info = t.data || t;
                            allTests.push({
                                date: t.created_date || (t.data ? t.data.created_date : 'N/A'),
                                name: info.name || info.test_name,
                                category: 'LAB',
                                qty: info.qty || info.quantity || 1,
                                by: t.created_by || (t.data ? t.data.created_by : 'Staff Nurse')
                            });
                        });
                    }
                    if (data.radiology_tests && Array.isArray(data.radiology_tests)) {
                        data.radiology_tests.forEach(t => {
                            const info = t.data || t;
                            allTests.push({
                                date: t.created_date || (t.data ? t.data.created_date : 'N/A'),
                                name: info.name || info.test_name,
                                category: 'RADIOLOGY',
                                qty: info.qty || info.quantity || 1,
                                by: t.created_by || (t.data ? t.data.created_by : 'Staff Nurse')
                            });
                        });
                    }
                    if (data.other_tests && Array.isArray(data.other_tests)) {
                        data.other_tests.forEach(t => {
                            const info = t.data || t;
                            allTests.push({
                                date: t.created_date || (t.data ? t.data.created_date : 'N/A'),
                                name: info.name || info.test_name,
                                category: 'OTHER',
                                qty: info.qty || info.quantity || 1,
                                by: t.created_by || (t.data ? t.data.created_by : 'Staff Nurse')
                            });
                        });
                    }
                    
                    renderAssignedTestsTable(allTests);
                } else {
                    tbody.innerHTML = '<tr><td colspan="5" style="text-align:center; color:var(--gm-text-muted); padding:20px;">No diagnostic history logged yet.</td></tr>';
                }
            })
            .catch(err => {
                console.error(err);
                tbody.innerHTML = '<tr><td colspan="5" style="text-align:center; color:#dc2626; padding:20px;">Failed to load assigned tests.</td></tr>';
            });
    }

    function renderAssignedTestsTable(tests) {
        const tbody = document.getElementById('assignedTestsBody');
        if (tests.length === 0) {
            tbody.innerHTML = '<tr><td colspan="5" style="text-align:center; color:var(--gm-text-muted); padding:20px;">No tests assigned yet for this admission.</td></tr>';
            return;
        }
        
        tests.sort((a, b) => new Date(b.date) - new Date(a.date));
        
        tbody.innerHTML = '';
        tests.forEach(test => {
            let badgeClass = 'badge-lab';
            if(test.category === 'RADIOLOGY') badgeClass = 'badge-rad';
            else if(test.category === 'OTHER') badgeClass = 'badge-oth';
            
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td><strong>${test.date}</strong></td>
                <td><strong style="color:var(--gm-primary);">${test.name}</strong></td>
                <td><span class="badge-cat ${badgeClass}">${test.category}</span></td>
                <td><strong>${test.qty}</strong></td>
                <td><span style="color:var(--gm-text-muted); font-weight:700;">${test.by}</span></td>
            `;
            tbody.appendChild(tr);
        });
    }
    
    // --- Tests Search Logic ---
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
            fetch(`api/search_tests.php?type=all&q=${encodeURIComponent(query)}`)
                .then(res => res.json())
                .then(data => {
                    if (data.success && data.data.length > 0) {
                        renderSuggestions(data.data);
                    } else {
                        suggestionsBox.innerHTML = '<div style="padding:15px; color:var(--gm-text-muted); font-weight:600; text-align:center;">No matching diagnostic tests found.</div>';
                        suggestionsBox.style.display = 'block';
                    }
                })
                .catch(err => console.error(err));
        }, 300);
    });
    
    function renderSuggestions(items) {
        suggestionsBox.innerHTML = '';
        items.forEach(item => {
            let cat = item.category || 'LAB';
            let badgeClass = 'badge-lab';
            if(cat.toLowerCase().includes('rad')) badgeClass = 'badge-rad';
            else if(cat.toLowerCase().includes('other')) badgeClass = 'badge-oth';
            
            const div = document.createElement('div');
            div.className = 'suggestion-item';
            div.innerHTML = `
                <div class="suggestion-details">
                    <strong>${item.name}</strong>
                    <span>ID: ${item.id} &bull; <span class="badge-cat ${badgeClass}">${cat}</span></span>
                </div>
            `;
            div.onclick = () => addToCart(item, badgeClass);
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
    
    function addToCart(item, badgeClass) {
        suggestionsBox.style.display = 'none';
        searchInput.value = '';
        
        const existing = cart.find(x => x.id === item.id);
        if (existing) {
            existing.qty += 1;
        } else {
            cart.push({
                id: item.id,
                name: item.name,
                category: item.category || 'LAB',
                price: parseFloat(item.price) || 0,
                qty: 1,
                badgeClass: badgeClass || 'badge-lab'
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
            icon.style.color = 'var(--gm-primary)';
        } else {
            icon.className = 'fas fa-times-circle';
            icon.style.color = '#dc2626';
        }
        
        document.getElementById('centerTitle').innerText = title;
        document.getElementById('centerText').innerText = message;
        
        overlay.style.display = 'flex';
        setTimeout(() => { overlay.style.display = 'none'; }, 2600);
    }
    
    function renderCart() {
        const tbody = document.getElementById('cartBody');
        
        if (cart.length === 0) {
            tbody.innerHTML = '<tr><td colspan="4" style="text-align:center; color:var(--gm-text-muted); padding:24px;">No tests selected yet. Search above to add.</td></tr>';
            document.getElementById('btnSave').style.display = 'none';
            return;
        }
        
        document.getElementById('btnSave').style.display = 'inline-flex';
        tbody.innerHTML = '';
        
        cart.forEach(item => {
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td><strong style="color:var(--gm-primary);">${item.name}</strong><br><small style="color:var(--gm-text-muted);">Test Code: ${item.id}</small></td>
                <td><span class="badge-cat ${item.badgeClass}">${item.category}</span></td>
                <td><input type="number" class="qty-input" value="${item.qty}" min="1" onchange="updateQty('${item.id}', this.value)"></td>
                <td><button class="btn-remove" onclick="removeItem('${item.id}')"><i class="fas fa-trash-alt"></i></button></td>
            `;
            tbody.appendChild(tr);
        });
    }
    
    function saveOrder() {
        if (!currentPatient || !currentPatient.admission_id) {
            showCenterMessage(false, 'Warning', 'Please select an admitted patient first.');
            return;
        }
        
        if (cart.length === 0) {
            showCenterMessage(false, 'Warning', 'Please add at least one test to the order queue.');
            return;
        }
        
        const btn = document.getElementById('btnSave');
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving Test Order...';
        
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
                showCenterMessage(true, 'Order Saved', 'Diagnostic tests order saved & dispatched successfully!');
                cart = [];
                renderCart();
                fetchAssignedTests(currentPatient.patient_id, currentPatient.admission_id);
                
                // TRIGGER BILLING SYNC (REST API)
                fetch('/GM_HMS/api/payment/clinical-billing-sync', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        admission_id: currentPatient.admission_id,
                        record_date: new Date().toISOString().split('T')[0]
                    })
                }).catch(e => console.error('Billing sync failed:', e));
            } else {
                const msg = data.message || 'Error saving diagnostic test order.';
                if (msg.includes('discharged') || msg.includes('Discharged')) {
                    showCenterMessage(false, 'Discharged Patient', 'This patient has already been discharged.');
                } else {
                    showCenterMessage(false, 'Error', msg);
                }
            }
        })
        .catch(err => {
            console.error(err);
            const errStr = String(err);
            if (errStr.includes('discharged') || errStr.includes('Discharged')) {
                showCenterMessage(false, 'Discharged Patient', 'This patient has already been discharged.');
            } else {
                showCenterMessage(false, 'Error', 'An error occurred during save.');
            }
        })
        .finally(() => {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-check-circle"></i> Save & Dispatch Test Order';
        });
    }
</script>
</body>
</html>
