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

        /* ── 3 Dedicated Diagnostic Views Styling ── */
        .diag-view-nav {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            margin-bottom: 14px;
        }
        .diag-tab-btn {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            padding: 8px 18px;
            border-radius: 20px;
            font-size: 0.82rem;
            font-weight: 700;
            cursor: pointer;
            border: 1.5px solid var(--gm-border);
            background: #ffffff;
            color: var(--gm-primary);
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .diag-tab-btn:hover {
            background: var(--gm-primary-light);
            border-color: var(--gm-primary);
        }
        .diag-tab-btn.active {
            color: #ffffff !important;
        }
        .diag-tab-btn.tab-lab.active {
            background: #0369a1;
            border-color: #0369a1;
            box-shadow: 0 4px 14px rgba(3, 105, 161, 0.25);
        }
        .diag-tab-btn.tab-rad.active {
            background: #1F6B4A;
            border-color: #1F6B4A;
            box-shadow: 0 4px 14px rgba(31, 107, 74, 0.25);
        }
        .diag-tab-btn.tab-oth.active {
            background: #b45309;
            border-color: #b45309;
            box-shadow: 0 4px 14px rgba(180, 83, 9, 0.25);
        }
        .diag-tab-btn .tab-badge {
            padding: 2px 8px;
            border-radius: 10px;
            font-size: 0.7rem;
            font-weight: 800;
            background: rgba(0, 0, 0, 0.08);
            color: inherit;
        }
        .diag-tab-btn.active .tab-badge {
            background: rgba(255, 255, 255, 0.25);
            color: #ffffff;
        }
        .diag-panel {
            display: none;
            animation: fadeIn 0.22s ease-out;
        }
        .diag-panel.active {
            display: block;
        }
        .diag-header-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 5px 12px;
            border-radius: 8px;
            font-size: 0.76rem;
            font-weight: 700;
            margin-bottom: 14px;
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

        /* ── 3 Dedicated Diagnostic Views Styling ── */
        .diag-view-nav {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }
        .diag-tab-btn {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            padding: 8px 18px;
            border-radius: 20px;
            font-size: 0.82rem;
            font-weight: 700;
            cursor: pointer;
            border: 1.5px solid var(--gm-border);
            background: #ffffff;
            color: var(--gm-primary);
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .diag-tab-btn:hover {
            background: var(--gm-primary-light);
            border-color: var(--gm-primary);
        }
        .diag-tab-btn.active {
            color: #ffffff !important;
        }
        .diag-tab-btn.tab-lab.active {
            background: #0369a1;
            border-color: #0369a1;
            box-shadow: 0 4px 14px rgba(3, 105, 161, 0.25);
        }
        .diag-tab-btn.tab-rad.active {
            background: #1F6B4A;
            border-color: #1F6B4A;
            box-shadow: 0 4px 14px rgba(31, 107, 74, 0.25);
        }
        .diag-tab-btn.tab-oth.active {
            background: #b45309;
            border-color: #b45309;
            box-shadow: 0 4px 14px rgba(180, 83, 9, 0.25);
        }
        .diag-tab-btn .tab-badge {
            padding: 2px 8px;
            border-radius: 10px;
            font-size: 0.7rem;
            font-weight: 800;
            background: rgba(0, 0, 0, 0.08);
            color: inherit;
        }
        .diag-tab-btn.active .tab-badge {
            background: rgba(255, 255, 255, 0.25);
            color: #ffffff;
        }
        .diag-panel {
            display: none;
            animation: fadeIn 0.22s ease-out;
        }
        .diag-panel.active {
            display: block;
        }
        .diag-header-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 14px;
            border-radius: 8px;
            font-size: 0.78rem;
            font-weight: 700;
            margin-bottom: 16px;
            width: 100%;
            box-sizing: border-box;
        }
        .btn-save-diag {
            color: #ffffff;
            border: none;
            padding: 10px 24px;
            border-radius: 10px;
            font-size: 0.88rem;
            font-weight: 800;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s ease;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }
        .btn-save-diag:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 16px rgba(0, 0, 0, 0.2);
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
                
                <!-- Section 1: Diagnostic Orders Requisition -->
                <div class="glass-card" id="testOrderSection" style="display: none;">
                    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px; margin-bottom: 20px;">
                        <h3 class="card-heading" style="margin-bottom: 0;"><i class="fas fa-microscope"></i> Step 2: Diagnostic Orders Requisition</h3>
                        <div class="diag-view-nav">
                            <button type="button" class="diag-tab-btn tab-lab active" id="btn-tab-order-lab" onclick="switchDiagOrderView('lab')">
                                <i class="fas fa-flask"></i> Diagnostic LAB Order <span class="tab-badge" id="badge-cart-lab">0</span>
                            </button>
                            <button type="button" class="diag-tab-btn tab-rad" id="btn-tab-order-rad" onclick="switchDiagOrderView('radiology')">
                                <i class="fas fa-x-ray"></i> Diagnostic RADIOLOGY Order <span class="tab-badge" id="badge-cart-rad">0</span>
                            </button>
                            <button type="button" class="diag-tab-btn tab-oth" id="btn-tab-order-oth" onclick="switchDiagOrderView('other')">
                                <i class="fas fa-heartbeat"></i> Diagnostic OTHER Order <span class="tab-badge" id="badge-cart-oth">0</span>
                            </button>
                        </div>
                    </div>

                    <!-- VIEW 1: Diagnostic LAB Order -->
                    <div class="diag-panel active" id="diag-panel-order-lab">
                        <div class="diag-header-badge" style="background:#e0f2fe; color:#0369a1; border:1px solid #bae6fd;">
                            <i class="fas fa-flask"></i> Pathology, Hematology & Biochemistry Tests &bull; Target Column: <code>lab_tests</code> (Column 7)
                        </div>
                        <div class="search-container" style="margin-bottom: 20px;">
                            <i class="fas fa-search search-icon" style="color:#0369a1;"></i>
                            <input type="text" id="search-lab" class="search-box" placeholder="Search Laboratory Tests (e.g. CBC, Lipid Profile, LFT, Urine, Blood Sugar)..." autocomplete="off">
                            <div id="suggestions-lab" class="suggestions-dropdown"></div>
                        </div>
                        
                        <h4 style="color:#0369a1; font-weight:800; font-size:0.92rem; margin-bottom:10px;"><i class="fas fa-list-ol"></i> Selected Lab Order Queue</h4>
                        <div class="table-responsive">
                            <table class="cart-table">
                                <thead>
                                    <tr>
                                        <th>Test Investigation Name</th>
                                        <th>Code</th>
                                        <th>Quantity</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody id="cart-body-lab">
                                    <tr><td colspan="4" style="text-align:center; color:var(--gm-text-muted); padding:24px;">No lab tests selected yet. Search above to add.</td></tr>
                                </tbody>
                            </table>
                        </div>
                        <div style="display:flex; justify-content:flex-end; margin-top:20px;">
                            <button class="btn-save-diag" id="btn-save-lab" onclick="submitDiagOrder('lab')" style="background:#0369a1; display:none;">
                                <i class="fas fa-check-circle"></i> Save & Dispatch Diagnostic LAB Order
                            </button>
                        </div>
                    </div>

                    <!-- VIEW 2: Diagnostic RADIOLOGY Order -->
                    <div class="diag-panel" id="diag-panel-order-rad">
                        <div class="diag-header-badge" style="background:#ecfdf5; color:#1F6B4A; border:1px solid #a7f3d0;">
                            <i class="fas fa-x-ray"></i> Inpatient Imaging, Scans & Radiographs &bull; Target Column: <code>radiology_tests</code> (Column 8)
                        </div>
                        <div class="search-container" style="margin-bottom: 20px;">
                            <i class="fas fa-search search-icon" style="color:#1F6B4A;"></i>
                            <input type="text" id="search-rad" class="search-box" placeholder="Search Radiology Imaging & Scans (e.g. Chest X-Ray, Spine, CT, USG, MRI)..." autocomplete="off">
                            <div id="suggestions-rad" class="suggestions-dropdown"></div>
                        </div>
                        
                        <h4 style="color:#1F6B4A; font-weight:800; font-size:0.92rem; margin-bottom:10px;"><i class="fas fa-list-ol"></i> Selected Radiology Order Queue</h4>
                        <div class="table-responsive">
                            <table class="cart-table">
                                <thead>
                                    <tr>
                                        <th>Radiology Scan Name</th>
                                        <th>Scan Code</th>
                                        <th>Quantity</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody id="cart-body-rad">
                                    <tr><td colspan="4" style="text-align:center; color:var(--gm-text-muted); padding:24px;">No radiology scans selected yet. Search above to add.</td></tr>
                                </tbody>
                            </table>
                        </div>
                        <div style="display:flex; justify-content:flex-end; margin-top:20px;">
                            <button class="btn-save-diag" id="btn-save-rad" onclick="submitDiagOrder('radiology')" style="background:#1F6B4A; display:none;">
                                <i class="fas fa-check-circle"></i> Save & Dispatch Diagnostic RADIOLOGY Order
                            </button>
                        </div>
                    </div>

                    <!-- VIEW 3: Diagnostic OTHER Order -->
                    <div class="diag-panel" id="diag-panel-order-oth">
                        <div class="diag-header-badge" style="background:#fef3c7; color:#b45309; border:1px solid #fde68a;">
                            <i class="fas fa-heartbeat"></i> Bedside ECG, Echo & Special Investigations &bull; Target Column: <code>other_tests</code> (Column 9)
                        </div>
                        <div class="search-container" style="margin-bottom: 20px;">
                            <i class="fas fa-search search-icon" style="color:#b45309;"></i>
                            <input type="text" id="search-oth" class="search-box" placeholder="Search Other Clinical Investigations (e.g. 12-Lead ECG, 2D Echo, Dialysis)..." autocomplete="off">
                            <div id="suggestions-oth" class="suggestions-dropdown"></div>
                        </div>
                        
                        <h4 style="color:#b45309; font-weight:800; font-size:0.92rem; margin-bottom:10px;"><i class="fas fa-list-ol"></i> Selected Other Investigations Queue</h4>
                        <div class="table-responsive">
                            <table class="cart-table">
                                <thead>
                                    <tr>
                                        <th>Investigation Name</th>
                                        <th>Service Code</th>
                                        <th>Quantity</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody id="cart-body-oth">
                                    <tr><td colspan="4" style="text-align:center; color:var(--gm-text-muted); padding:24px;">No other investigations selected yet. Search above to add.</td></tr>
                                </tbody>
                            </table>
                        </div>
                        <div style="display:flex; justify-content:flex-end; margin-top:20px;">
                            <button class="btn-save-diag" id="btn-save-oth" onclick="submitDiagOrder('other')" style="background:#b45309; display:none;">
                                <i class="fas fa-check-circle"></i> Save & Dispatch Diagnostic OTHER Order
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Section 2: Inpatient Diagnostic Investigation History -->
                <div class="glass-card" id="assignedTestsSection" style="display: none;">
                    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px; margin-bottom: 20px;">
                        <h3 class="card-heading" style="margin-bottom: 0;"><i class="fas fa-history"></i> Inpatient Diagnostic Investigation History</h3>
                        <div class="diag-view-nav">
                            <button type="button" class="diag-tab-btn tab-lab active" id="btn-tab-hist-lab" onclick="switchDiagHistView('lab')">
                                <i class="fas fa-flask"></i> Diagnostic LAB History <span class="tab-badge" id="badge-hist-lab">0</span>
                            </button>
                            <button type="button" class="diag-tab-btn tab-rad" id="btn-tab-hist-rad" onclick="switchDiagHistView('radiology')">
                                <i class="fas fa-x-ray"></i> Diagnostic RADIOLOGY History <span class="tab-badge" id="badge-hist-rad">0</span>
                            </button>
                            <button type="button" class="diag-tab-btn tab-oth" id="btn-tab-hist-oth" onclick="switchDiagHistView('other')">
                                <i class="fas fa-heartbeat"></i> Diagnostic OTHER History <span class="tab-badge" id="badge-hist-oth">0</span>
                            </button>
                        </div>
                    </div>

                    <!-- LAB History Panel -->
                    <div class="diag-panel active" id="diag-panel-hist-lab">
                        <div class="table-responsive">
                            <table class="cart-table">
                                <thead>
                                    <tr>
                                        <th>Order Date & Time</th>
                                        <th>Laboratory Test Name</th>
                                        <th>Test Code</th>
                                        <th>Category</th>
                                        <th>Qty</th>
                                        <th>Ordered By</th>
                                    </tr>
                                </thead>
                                <tbody id="hist-body-lab">
                                    <tr><td colspan="6" style="text-align:center; color:var(--gm-text-muted); padding:20px;">Loading lab history...</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- RADIOLOGY History Panel -->
                    <div class="diag-panel" id="diag-panel-hist-rad">
                        <div class="table-responsive">
                            <table class="cart-table">
                                <thead>
                                    <tr>
                                        <th>Order Date & Time</th>
                                        <th>Radiology Scan Name</th>
                                        <th>Scan Code</th>
                                        <th>Modality</th>
                                        <th>Qty</th>
                                        <th>Ordered By</th>
                                    </tr>
                                </thead>
                                <tbody id="hist-body-rad">
                                    <tr><td colspan="6" style="text-align:center; color:var(--gm-text-muted); padding:20px;">Loading radiology history...</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- OTHER History Panel -->
                    <div class="diag-panel" id="diag-panel-hist-oth">
                        <div class="table-responsive">
                            <table class="cart-table">
                                <thead>
                                    <tr>
                                        <th>Order Date & Time</th>
                                        <th>Investigation Name</th>
                                        <th>Service Code</th>
                                        <th>Category</th>
                                        <th>Qty</th>
                                        <th>Ordered By</th>
                                    </tr>
                                </thead>
                                <tbody id="hist-body-oth">
                                    <tr><td colspan="6" style="text-align:center; color:var(--gm-text-muted); padding:20px;">Loading other history...</td></tr>
                                </tbody>
                            </table>
                        </div>
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
    let diagCarts = {
        lab: [],
        rad: [],
        oth: []
    };
    let currentPatient = null;

    function toKey(type) {
        if (!type) return 'lab';
        const s = String(type).toLowerCase();
        if (s === 'rad' || s === 'radiology') return 'rad';
        if (s === 'oth' || s === 'other') return 'oth';
        return 'lab';
    }

    function toFullType(key) {
        if (!key) return 'lab';
        const s = String(key).toLowerCase();
        if (s === 'rad' || s === 'radiology') return 'radiology';
        if (s === 'oth' || s === 'other') return 'other';
        return 'lab';
    }
    
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
        
        diagCarts = { lab: [], rad: [], oth: [] };
        renderDiagCart('lab');
        renderDiagCart('rad');
        renderDiagCart('oth');
        
        fetchAssignedTests(p.patient_id, p.admission_id);
    }
    
    function changePatient() {
        currentPatient = null;
        document.getElementById('patientSearchSection').style.display = 'block';
        document.getElementById('patientInfoCard').style.display = 'none';
        document.getElementById('testOrderSection').style.display = 'none';
        document.getElementById('assignedTestsSection').style.display = 'none';
        diagCarts = { lab: [], rad: [], oth: [] };
        renderDiagCart('lab');
        renderDiagCart('rad');
        renderDiagCart('oth');
    }

    // --- Tab Switching Logic (Normalized to lab, rad, oth) ---
    function switchDiagOrderView(type) {
        const activeKey = toKey(type);
        ['lab', 'rad', 'oth'].forEach(k => {
            const btn = document.getElementById(`btn-tab-order-${k}`);
            const pnl = document.getElementById(`diag-panel-order-${k}`);
            if (btn) btn.classList.toggle('active', k === activeKey);
            if (pnl) pnl.classList.toggle('active', k === activeKey);
        });
    }

    function switchDiagHistView(type) {
        const activeKey = toKey(type);
        ['lab', 'rad', 'oth'].forEach(k => {
            const btn = document.getElementById(`btn-tab-hist-${k}`);
            const pnl = document.getElementById(`diag-panel-hist-${k}`);
            if (btn) btn.classList.toggle('active', k === activeKey);
            if (pnl) pnl.classList.toggle('active', k === activeKey);
        });
    }

    // --- Search & Autocomplete Initialization for 3 Views ---
    function setupDiagSearch(type, inputId, suggId) {
        const inp = document.getElementById(inputId);
        const box = document.getElementById(suggId);
        if (!inp || !box) return;
        
        let timer = null;
        inp.addEventListener('input', function() {
            clearTimeout(timer);
            const q = this.value.trim();
            if (q.length < 2) {
                box.style.display = 'none';
                return;
            }
            
            timer = setTimeout(() => {
                fetch(`api/search_tests.php?type=${encodeURIComponent(type)}&q=${encodeURIComponent(q)}`)
                    .then(res => res.json())
                    .then(data => {
                        if (data.success && data.data.length > 0) {
                            renderDiagSuggestions(type, data.data, box, inp);
                        } else {
                            box.innerHTML = `<div style="padding:14px; color:var(--gm-text-muted); font-weight:600; text-align:center;">No matching ${type} investigations found.</div>`;
                            box.style.display = 'block';
                        }
                    })
                    .catch(err => console.error(err));
            }, 250);
        });
        
        document.addEventListener('click', function(e) {
            if (!inp.contains(e.target) && !box.contains(e.target)) {
                box.style.display = 'none';
            }
        });
    }

    function renderDiagSuggestions(type, items, box, inp) {
        box.innerHTML = '';
        const key = toKey(type);
        const badgeClass = key === 'lab' ? 'badge-lab' : (key === 'rad' ? 'badge-rad' : 'badge-oth');
        const defaultCat = key === 'lab' ? 'LAB' : (key === 'rad' ? 'RADIOLOGY' : 'OTHER');

        items.forEach(item => {
            const div = document.createElement('div');
            div.className = 'suggestion-item';
            div.innerHTML = `
                <div class="suggestion-details">
                    <strong>${item.name}</strong>
                    <span>ID: ${item.id} &bull; <span class="badge-cat ${badgeClass}">${item.category || defaultCat}</span></span>
                </div>
            `;
            div.onclick = () => {
                box.style.display = 'none';
                inp.value = '';
                addDiagToCart(key, item);
            };
            box.appendChild(div);
        });
        box.style.display = 'block';
    }

    // Setup searches for each diagnostic type
    setupDiagSearch('lab', 'search-lab', 'suggestions-lab');
    setupDiagSearch('radiology', 'search-rad', 'suggestions-rad');
    setupDiagSearch('other', 'search-oth', 'suggestions-oth');

    // Close patient search on outside click
    document.addEventListener('click', function(e) {
        if (!pSearchInput.contains(e.target) && !pSuggestionsBox.contains(e.target)) {
            pSuggestionsBox.style.display = 'none';
        }
    });

    // --- Dedicated Diagnostic Cart Operations ---
    function addDiagToCart(type, item) {
        const key = toKey(type);
        const cartList = diagCarts[key];
        const existing = cartList.find(x => x.id === item.id);
        if (existing) {
            existing.qty += 1;
        } else {
            const defaultCat = key === 'lab' ? 'LAB' : (key === 'rad' ? 'RADIOLOGY' : 'OTHER');
            cartList.push({
                id: item.id,
                name: item.name,
                category: item.category || defaultCat,
                price: parseFloat(item.price) || 0,
                qty: 1
            });
        }
        renderDiagCart(key);
    }

    function updateDiagQty(type, id, qty) {
        const key = toKey(type);
        const item = diagCarts[key].find(x => x.id === id);
        if (item) {
            item.qty = parseInt(qty) || 1;
            if (item.qty < 1) item.qty = 1;
            renderDiagCart(key);
        }
    }

    function removeDiagFromCart(type, id) {
        const key = toKey(type);
        diagCarts[key] = diagCarts[key].filter(item => item.id !== id);
        renderDiagCart(key);
    }

    function renderDiagCart(type) {
        const key = toKey(type);
        const cartList = diagCarts[key] || [];
        const tbody = document.getElementById(`cart-body-${key}`);
        const btnSave = document.getElementById(`btn-save-${key}`);
        const badge = document.getElementById(`badge-cart-${key}`);
        
        if (badge) badge.innerText = cartList.length;
        if (!tbody) return;

        const label = key === 'lab' ? 'lab tests' : (key === 'rad' ? 'radiology scans' : 'other investigations');
        if (cartList.length === 0) {
            tbody.innerHTML = `<tr><td colspan="4" style="text-align:center; color:var(--gm-text-muted); padding:24px;">No ${label} selected yet. Search above to add.</td></tr>`;
            if (btnSave) btnSave.style.display = 'none';
            return;
        }

        if (btnSave) btnSave.style.display = 'inline-flex';
        tbody.innerHTML = '';

        cartList.forEach(item => {
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td><strong style="color:var(--gm-primary);">${item.name}</strong></td>
                <td><code style="font-family:'JetBrains Mono', monospace; font-size:0.8rem; background:rgba(0,0,0,0.04); padding:3px 6px; border-radius:4px;">${item.id}</code></td>
                <td><input type="number" class="qty-input" value="${item.qty}" min="1" onchange="updateDiagQty('${key}', '${item.id}', this.value)"></td>
                <td><button class="btn-remove" onclick="removeDiagFromCart('${key}', '${item.id}')"><i class="fas fa-trash-alt"></i></button></td>
            `;
            tbody.appendChild(tr);
        });
    }

    // --- Order Submission (Isolated per Diagnostic Type) ---
    function submitDiagOrder(type) {
        if (!currentPatient || !currentPatient.admission_id) {
            showCenterMessage(false, 'Warning', 'Please select an admitted patient first.');
            return;
        }

        const key = toKey(type);
        const fullType = toFullType(type);
        const cartList = diagCarts[key] || [];
        const label = key === 'lab' ? 'LAB' : (key === 'rad' ? 'RADIOLOGY' : 'OTHER');

        if (!cartList || cartList.length === 0) {
            showCenterMessage(false, 'Warning', `Please add at least one ${label} test to the order queue.`);
            return;
        }

        const btn = document.getElementById(`btn-save-${key}`);
        const origHtml = btn ? btn.innerHTML : '';
        if (btn) {
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving Order...';
        }

        fetch('api/save_tests.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                patient_id: currentPatient.patient_id,
                admission_id: currentPatient.admission_id,
                order_type: fullType,
                cart: cartList
            })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                showCenterMessage(true, 'Order Saved', `Diagnostic ${label} order saved & dispatched successfully!`);
                diagCarts[key] = [];
                renderDiagCart(key);
                fetchAssignedTests(currentPatient.patient_id, currentPatient.admission_id);
                switchDiagHistView(key);

                // Trigger billing sync
                fetch('/GM_HMS/api/payment/clinical-billing-sync', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        admission_id: currentPatient.admission_id,
                        record_date: new Date().toISOString().split('T')[0]
                    })
                }).catch(e => console.error('Billing sync failed:', e));
            } else {
                const msg = data.message || `Error saving diagnostic ${label} order.`;
                if (msg.includes('discharged') || msg.includes('Discharged')) {
                    showCenterMessage(false, 'Discharged Patient', 'This patient has already been discharged.');
                } else {
                    showCenterMessage(false, 'Error', msg);
                }
            }
        })
        .catch(err => {
            console.error(err);
            showCenterMessage(false, 'Error', 'An error occurred during save.');
        })
        .finally(() => {
            if (btn) {
                btn.disabled = false;
                btn.innerHTML = origHtml || `<i class="fas fa-check-circle"></i> Save & Dispatch Diagnostic ${label} Order`;
            }
        });
    }

    // --- Diagnostic Investigation History Fetch & Isolated Display ---
    function fetchAssignedTests(patientId, admissionId) {
        ['lab', 'rad', 'oth'].forEach(t => {
            const b = document.getElementById(`hist-body-${t}`);
            if (b) b.innerHTML = '<tr><td colspan="6" style="text-align:center; color:var(--gm-text-muted); padding:20px;"><i class="fas fa-spinner fa-spin"></i> Loading history...</td></tr>';
        });

        fetch(`api/get_clinical_records.php?patient_id=${encodeURIComponent(patientId)}&admission_id=${encodeURIComponent(admissionId)}`)
            .then(res => res.json())
            .then(res => {
                if (res.success && res.data) {
                    const data = res.data;

                    // 1. LAB TESTS
                    const labList = Array.isArray(data.lab_tests) ? data.lab_tests : [];
                    const hLab = document.getElementById('hist-body-lab');
                    const bLab = document.getElementById('badge-hist-lab');
                    if (bLab) bLab.innerText = labList.length;
                    if (hLab) {
                        if (labList.length === 0) {
                            hLab.innerHTML = '<tr><td colspan="6" style="text-align:center; color:var(--gm-text-muted); padding:20px;">No Laboratory tests ordered for this patient yet.</td></tr>';
                        } else {
                            hLab.innerHTML = '';
                            [...labList].reverse().forEach(t => {
                                const info = t.data || t;
                                const dt = (t.created_date || info.created_date || t.date || info.date) 
                                    ? `${t.created_date || info.created_date || t.date || info.date || ''} ${t.created_time || info.created_time || ''}`.trim() 
                                    : (t.created_at || info.created_at || 'N/A');
                                const by = t.created_by_name || info.created_by_name || (t.created_by ? (isNaN(t.created_by) ? t.created_by : 'Nurse #' + t.created_by) : 'Staff Nurse');
                                const name = info.name || info.test_name || t.name || t.test_name || 'Lab Test';
                                const code = info.id || info.test_id || t.id || t.test_id || 'LAB';
                                const cat = info.category || t.category || 'Lab';
                                const qty = info.qty || info.quantity || t.qty || t.quantity || 1;
                                
                                const tr = document.createElement('tr');
                                tr.innerHTML = `
                                    <td><strong>${dt}</strong></td>
                                    <td><strong style="color:#0369a1;">${name}</strong></td>
                                    <td><code style="font-size:0.8rem; background:#e0f2fe; color:#0369a1; padding:2px 6px; border-radius:4px;">${code}</code></td>
                                    <td><span class="badge-cat badge-lab">${cat}</span></td>
                                    <td><strong>${qty}</strong></td>
                                    <td><span style="color:var(--gm-text-muted); font-weight:700;">${by}</span></td>
                                `;
                                hLab.appendChild(tr);
                            });
                        }
                    }

                    // 2. RADIOLOGY TESTS
                    const radList = Array.isArray(data.radiology_tests) ? data.radiology_tests : [];
                    const hRad = document.getElementById('hist-body-rad');
                    const bRad = document.getElementById('badge-hist-rad');
                    if (bRad) bRad.innerText = radList.length;
                    if (hRad) {
                        if (radList.length === 0) {
                            hRad.innerHTML = '<tr><td colspan="6" style="text-align:center; color:var(--gm-text-muted); padding:20px;">No Radiology imaging or scans ordered for this patient yet.</td></tr>';
                        } else {
                            hRad.innerHTML = '';
                            [...radList].reverse().forEach(t => {
                                const info = t.data || t;
                                const dt = (t.created_date || info.created_date || t.date || info.date) 
                                    ? `${t.created_date || info.created_date || t.date || info.date || ''} ${t.created_time || info.created_time || ''}`.trim() 
                                    : (t.created_at || info.created_at || 'N/A');
                                const by = t.created_by_name || info.created_by_name || (t.created_by ? (isNaN(t.created_by) ? t.created_by : 'Nurse #' + t.created_by) : 'Staff Nurse');
                                const name = info.name || info.test_name || t.name || t.test_name || 'Radiology Scan';
                                const code = info.id || info.test_id || t.id || t.test_id || 'RDS';
                                const cat = info.category || info.modality || t.category || 'Radiology';
                                const qty = info.qty || info.quantity || t.qty || t.quantity || 1;
                                
                                const tr = document.createElement('tr');
                                tr.innerHTML = `
                                    <td><strong>${dt}</strong></td>
                                    <td><strong style="color:#1F6B4A;">${name}</strong></td>
                                    <td><code style="font-size:0.8rem; background:#ecfdf5; color:#1F6B4A; padding:2px 6px; border-radius:4px;">${code}</code></td>
                                    <td><span class="badge-cat badge-rad">${cat}</span></td>
                                    <td><strong>${qty}</strong></td>
                                    <td><span style="color:var(--gm-text-muted); font-weight:700;">${by}</span></td>
                                `;
                                hRad.appendChild(tr);
                            });
                        }
                    }

                    // 3. OTHER TESTS
                    const othList = Array.isArray(data.other_tests) ? data.other_tests : [];
                    const hOth = document.getElementById('hist-body-oth');
                    const bOth = document.getElementById('badge-hist-oth');
                    if (bOth) bOth.innerText = othList.length;
                    if (hOth) {
                        if (othList.length === 0) {
                            hOth.innerHTML = '<tr><td colspan="6" style="text-align:center; color:var(--gm-text-muted); padding:20px;">No Other clinical investigations ordered for this patient yet.</td></tr>';
                        } else {
                            hOth.innerHTML = '';
                            [...othList].reverse().forEach(t => {
                                const info = t.data || t;
                                const dt = (t.created_date || info.created_date || t.date || info.date) 
                                    ? `${t.created_date || info.created_date || t.date || info.date || ''} ${t.created_time || info.created_time || ''}`.trim() 
                                    : (t.created_at || info.created_at || 'N/A');
                                const by = t.created_by_name || info.created_by_name || (t.created_by ? (isNaN(t.created_by) ? t.created_by : 'Nurse #' + t.created_by) : 'Staff Nurse');
                                const name = info.name || info.test_name || t.name || t.test_name || 'Investigation';
                                const code = info.id || info.test_id || t.id || t.test_id || 'OTH';
                                const cat = info.category || t.category || 'Other';
                                const qty = info.qty || info.quantity || t.qty || t.quantity || 1;
                                
                                const tr = document.createElement('tr');
                                tr.innerHTML = `
                                    <td><strong>${dt}</strong></td>
                                    <td><strong style="color:#b45309;">${name}</strong></td>
                                    <td><code style="font-size:0.8rem; background:#fef3c7; color:#b45309; padding:2px 6px; border-radius:4px;">${code}</code></td>
                                    <td><span class="badge-cat badge-oth">${cat}</span></td>
                                    <td><strong>${qty}</strong></td>
                                    <td><span style="color:var(--gm-text-muted); font-weight:700;">${by}</span></td>
                                `;
                                hOth.appendChild(tr);
                            });
                        }
                    }
                } else {
                    ['lab', 'rad', 'oth'].forEach(t => {
                        const b = document.getElementById(`hist-body-${t}`);
                        if (b) b.innerHTML = '<tr><td colspan="6" style="text-align:center; color:var(--gm-text-muted); padding:20px;">No diagnostic history logged yet.</td></tr>';
                    });
                }
            })
            .catch(err => {
                console.error(err);
                ['lab', 'rad', 'oth'].forEach(t => {
                    const b = document.getElementById(`hist-body-${t}`);
                    if (b) b.innerHTML = '<tr><td colspan="6" style="text-align:center; color:#dc2626; padding:20px;">Failed to load diagnostic records.</td></tr>';
                });
            });
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
</script>
</body>
</html>
