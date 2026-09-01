<?php
session_start();
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'Admin', 'Accountant', 'Receptionist'])) {
    // Standard Receptionists not allowed unless specifically permitted. The plan approved Admin/Accountant only.
    header("Location: index.php");
    exit();
}
$pageTitle = 'OPD Billing Report';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="stylesheet" href="/GM_HMS/assets/css/gm-theme.css">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OPD Billing Report - GM HMS</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/reception_dashboard.css">
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.dataTables.min.css">
    <style>
        .report-page-wrapper { padding: 20px; width: 100%; box-sizing: border-box; }
        .report-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; }
        .report-title h2 { margin: 0; font-size: 1.5rem; color: var(--gm-text); font-weight: 700; }
        .report-title p { margin: 4px 0 0; color: #64748b; font-size: 0.9rem; }
        
        .filter-bar { display: flex; flex-wrap: wrap; gap: 16px; background: white; padding: 16px 20px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.03); margin-bottom: 24px; border: 1px solid #e2e8f0; }
        .filter-group { display: flex; flex-direction: column; gap: 6px; }
        .filter-group label { font-size: 0.8rem; font-weight: 600; color: #475569; text-transform: uppercase; letter-spacing: 0.5px; }
        .filter-group select, .filter-group input { padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 8px; outline: none; font-family: inherit; font-size: 0.9rem; min-width: 160px; }
        .filter-group select:focus, .filter-group input:focus { border-color: #1f6b4a; box-shadow: 0 0 0 3px rgba(31,107,74,0.1); }
        .filter-btn { margin-top: auto; padding: 9px 20px; background: #1f6b4a; color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; display: flex; align-items: center; gap: 8px; transition: 0.2s; }
        .filter-btn:hover { background: #144d34; }

        .kpi-row { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 20px; margin-bottom: 24px; }
        .kpi-card-r { background: #f3efe6; padding: 20px; border-radius: 12px; border: 1px solid #e2e8f0; box-shadow: 0 4px 15px rgba(0,0,0,0.03); position: relative; overflow: hidden; }
        .kpi-card-r::before { content: ''; position: absolute; left: 0; top: 0; bottom: 0; width: 4px; background: #1f6b4a; }
        .kpi-title { font-size: 0.85rem; color: #1f6b4a; font-weight: 700; margin-bottom: 8px; text-transform: uppercase; letter-spacing: 0.5px; }
        .kpi-value { font-size: 1.8rem; font-weight: 700; color: #1f6b4a; display: flex; align-items: center; gap: 10px; }
        .kpi-icon { padding: 10px; border-radius: 10px; font-size: 1.2rem; display: flex; align-items: center; justify-content: center; background: white; color: #1f6b4a; }
        
        .tabs-container { background: white; border-radius: 12px; border: 1px solid #e2e8f0; box-shadow: 0 4px 15px rgba(0,0,0,0.03); margin-bottom: 24px; overflow: hidden; }
        .tabs-header { display: flex; border-bottom: 1px solid #e2e8f0; background: #f3efe6; }
        .tab-btn { padding: 16px 24px; font-weight: 600; color: #475569; border: none; background: none; cursor: pointer; border-bottom: 3px solid transparent; transition: 0.2s; font-size: 0.95rem; display: flex; align-items: center; gap: 8px; }
        .tab-btn:hover { color: #1f6b4a; background: white; }
        .tab-btn.active { color: #1f6b4a; border-bottom-color: #1f6b4a; background: white; }
        .tab-content { padding: 24px; display: none; }
        .tab-content.active { display: block; }

        .dataTable-wrapper { width: 100%; overflow-x: auto; }
        table.dataTable thead th { background: #f3efe6; border-bottom: 1px solid #1f6b4a; color: #1f6b4a; font-weight: 700; font-size: 0.85rem; text-transform: uppercase; padding: 12px 10px; }
        table.dataTable tbody td { border-bottom: 1px solid #f1f5f9; padding: 12px 10px; font-size: 0.9rem; color: #334155; vertical-align: middle; }
        
        .badge { padding: 4px 8px; border-radius: 6px; font-size: 0.75rem; font-weight: 600; border: 1px solid #1f6b4a; }
        .badge.bg-success { background: #f3efe6; color: #1f6b4a; }
        .badge.bg-warning { background: #f3efe6; color: #1f6b4a; }
        .badge.bg-danger { background: #1f6b4a; color: #f3efe6; }

        .charts-grid { display: grid; grid-template-columns: 2fr 1fr; gap: 20px; }
        .chart-card { background: white; border: 1px solid #e2e8f0; border-radius: 12px; padding: 20px; }
        .chart-card h3 { margin: 0 0 16px 0; font-size: 1.1rem; color: #1e293b; }
        
        .dt-buttons .dt-button { background: #f1f5f9; border: 1px solid #cbd5e1; border-radius: 6px; color: #475569; font-weight: 600; padding: 6px 12px; }
        .dt-buttons .dt-button:hover { background: #e2e8f0; }

        /* Premium Payment Modes Styling */
        .paymodes-overview-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 18px 22px;
            margin-bottom: 22px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.02);
        }
        .paymodes-ratio-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 12px;
            margin-bottom: 12px;
        }
        .paymodes-ratio-title {
            font-size: 0.95rem;
            font-weight: 700;
            color: #1e293b;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .paymodes-ratio-labels {
            display: flex;
            gap: 20px;
            font-size: 0.85rem;
            font-weight: 600;
        }
        .paymodes-ratio-bar {
            height: 12px;
            background: #f1f5f9;
            border-radius: 6px;
            overflow: hidden;
            display: flex;
            width: 100%;
            border: 1px solid #e2e8f0;
        }
        .paymodes-ratio-seg-cash {
            background: linear-gradient(90deg, #10b981, #059669);
            height: 100%;
            transition: width 0.6s ease;
        }
        .paymodes-ratio-seg-digital {
            background: linear-gradient(90deg, #3b82f6, #1d4ed8);
            height: 100%;
            transition: width 0.6s ease;
        }
        .paymodes-cards-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(230px, 1fr));
            gap: 16px;
            margin-bottom: 22px;
        }
        .pm-card {
            background: #ffffff;
            border: 1.5px solid #e2e8f0;
            border-radius: 12px;
            padding: 18px;
            transition: all 0.2s ease;
            box-shadow: 0 4px 12px rgba(0,0,0,0.02);
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            cursor: pointer;
            position: relative;
        }
        .pm-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.06);
            border-color: #cbd5e1;
        }
        .pm-card-top {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 12px;
        }
        .pm-icon-wrap {
            width: 42px;
            height: 42px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
        }
        .pm-share-badge {
            font-size: 0.75rem;
            font-weight: 700;
            padding: 3px 8px;
            border-radius: 12px;
        }
        .pm-mode-name {
            font-size: 0.85rem;
            font-weight: 700;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 4px;
        }
        .pm-mode-amount {
            font-size: 1.55rem;
            font-weight: 800;
            color: #0f172a;
            letter-spacing: -0.5px;
            margin-bottom: 10px;
            line-height: 1.2;
        }
        .pm-progress-track {
            height: 6px;
            background: #f1f5f9;
            border-radius: 3px;
            overflow: hidden;
            margin-bottom: 12px;
        }
        .pm-progress-fill {
            height: 100%;
            border-radius: 3px;
            transition: width 0.5s ease;
        }
        .pm-meta-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 0.78rem;
            color: #64748b;
            padding-top: 10px;
            border-top: 1px dashed #e2e8f0;
        }
        .pm-meta-footer strong {
            color: #334155;
        }
        .pm-table-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.02);
        }
        .pm-table-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 16px;
            flex-wrap: wrap;
            gap: 10px;
        }
        .pm-table-header h3 {
            margin: 0;
            font-size: 1.05rem;
            font-weight: 700;
            color: #1e293b;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .pm-contrib-bar-wrap {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .pm-contrib-bar {
            flex: 1;
            height: 8px;
            background: #f1f5f9;
            border-radius: 4px;
            overflow: hidden;
        }
        .pm-contrib-fill {
            height: 100%;
            border-radius: 4px;
        }
        .pm-filter-chip {
            padding: 4px 10px;
            background: #f8fafc;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            font-size: 0.78rem;
            font-weight: 600;
            color: #475569;
            cursor: pointer;
            transition: 0.2s;
        }
        .pm-filter-chip:hover {
            background: #1f6b4a;
            color: white;
            border-color: #1f6b4a;
        }
        
        /* Modal */
        .modal-overlay { z-index: 1000; backdrop-filter: blur(4px); background: rgba(15, 23, 42, 0.4); position: fixed; top: 0; left: 0; right: 0; bottom: 0; display: none; justify-content: center; align-items: center; }
        .modal-content-lg { width: 90%; max-width: 900px; max-height: 90vh; background: white; border-radius: 12px; overflow: hidden; display: flex; flex-direction: column; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1); }
        .modal-header { padding: 16px 24px; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center; background: #f8fafc; }
        .modal-body { padding: 24px; overflow-y: auto; }
    </style>
</head>
<body>
<div class="reception-layout">
    <?php include 'includes/reception_sidebar.php'; ?>
    <div class="reception-main-content">
        <?php include 'includes/reception_navbar.php'; ?>
        <main class="reception-content">

        <div class="report-page-wrapper">
            <div class="report-header">
                <div class="report-title">
                    <h2>OPD Billing & Collection Report</h2>
                    <p>Comprehensive overview of billing performance, collections, and staff accountability.</p>
                </div>
            </div>

            <!-- Filters -->
            <div class="filter-bar">
                <div class="filter-group">
                    <label>Date Range Preset</label>
                    <select id="datePreset" onchange="updateDatePreset()">
                        <option value="last30" selected>Last 30 Days</option>
                        <option value="thismonth">This Month</option>
                        <option value="lastmonth">Last Month</option>
                        <option value="last7">Last 7 Days</option>
                        <option value="today">Today</option>
                        <option value="yesterday">Yesterday</option>
                        <option value="all">All Time</option>
                        <option value="custom">Custom Range</option>
                    </select>
                </div>
                <div class="filter-group">
                    <label>Start Date</label>
                    <input type="date" id="startDate">
                </div>
                <div class="filter-group">
                    <label>End Date</label>
                    <input type="date" id="endDate">
                </div>
                <div class="filter-group">
                    <label>Receptionist/User</label>
                    <select id="userFilter">
                        <option value="">All Users</option>
                        <!-- Populated via JS -->
                    </select>
                </div>
                <div class="filter-group">
                    <label>Payment Mode</label>
                    <select id="paymentModeFilter">
                        <option value="">All Modes</option>
                        <option value="Cash">Cash</option>
                        <option value="UPI">UPI</option>
                        <option value="Card">Card</option>
                        <option value="Bank Transfer">Bank Transfer</option>
                    </select>
                </div>
                <button class="filter-btn" onclick="fetchReportData()">
                    <i class="fas fa-filter"></i> Apply Filters
                </button>
            </div>

            <!-- KPI Cards -->
            <div class="kpi-row">
                <div class="kpi-card-r kpi-blue">
                    <div class="kpi-title">Total Billed Amount</div>
                    <div class="kpi-value">
                        <div class="kpi-icon"><i class="fas fa-file-invoice"></i></div>
                        ₹<span id="kpiTotalBilled">0.00</span>
                    </div>
                </div>
                <div class="kpi-card-r">
                    <div class="kpi-title">Net Collection (Paid)</div>
                    <div class="kpi-value">
                        <div class="kpi-icon"><i class="fas fa-rupee-sign"></i></div>
                        ₹<span id="kpiTotalCollected">0.00</span>
                    </div>
                </div>
                <div class="kpi-card-r kpi-red">
                    <div class="kpi-title">Total Due / Pending</div>
                    <div class="kpi-value">
                        <div class="kpi-icon"><i class="fas fa-exclamation-circle"></i></div>
                        ₹<span id="kpiTotalDue">0.00</span>
                    </div>
                </div>
                <div class="kpi-card-r kpi-purple">
                    <div class="kpi-title">Total Bills Generated</div>
                    <div class="kpi-value">
                        <div class="kpi-icon"><i class="fas fa-receipt"></i></div>
                        <span id="kpiTotalBills">0</span>
                    </div>
                </div>
            </div>

            <!-- Tabs Section -->
            <div class="tabs-container">
                <div class="tabs-header">
                    <button class="tab-btn active" onclick="switchTab('tab-all-bills')"><i class="fas fa-list"></i> All Bills</button>
                    <button class="tab-btn" onclick="switchTab('tab-users')"><i class="fas fa-users-cog"></i> User Accountability</button>
                    <button class="tab-btn" onclick="switchTab('tab-timeline')"><i class="fas fa-chart-line"></i> Timeline Analysis</button>
                    <button class="tab-btn" onclick="switchTab('tab-payment-modes')"><i class="fas fa-wallet"></i> Payment Modes</button>
                </div>

                <!-- Tab 1: All Bills -->
                <div id="tab-all-bills" class="tab-content active">
                    <div class="dataTable-wrapper">
                        <table id="billsTable" class="display" style="width:100%">
                            <thead>
                                <tr>
                                    <th>Bill No</th>
                                    <th>Date</th>
                                    <th>Patient</th>
                                    <th>Generated By</th>
                                    <th>Pay Mode</th>
                                    <th>Total</th>
                                    <th>Paid</th>
                                    <th>Due</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Loaded via DataTables AJAX -->
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Tab 2: User Accountability -->
                <div id="tab-users" class="tab-content">
                    <div class="dataTable-wrapper">
                        <table id="usersTable" class="display" style="width:100%">
                            <thead>
                                <tr>
                                    <th>Receptionist / User</th>
                                    <th>Bills Generated</th>
                                    <th>Total Billed (₹)</th>
                                    <th>Total Collected (₹)</th>
                                    <th>Total Pending Due (₹)</th>
                                </tr>
                            </thead>
                            <tbody id="usersTbody">
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Tab 3: Timeline Analysis -->
                <div id="tab-timeline" class="tab-content">
                    <div class="chart-card" style="width: 100%;">
                        <h3>Collection Timeline</h3>
                        <canvas id="timelineChart" height="100"></canvas>
                    </div>
                </div>

                <!-- Tab 4: Payment Modes (No Graph - Modern Cards & Matrix Grid) -->
                <div id="tab-payment-modes" class="tab-content">
                    <!-- Cash vs Digital Ratio Banner -->
                    <div class="paymodes-overview-card">
                        <div class="paymodes-ratio-header">
                            <div class="paymodes-ratio-title">
                                <i class="fas fa-layer-group" style="color: #1f6b4a;"></i> Collections Mix & Settlement Ratio
                            </div>
                            <div class="paymodes-ratio-labels">
                                <span style="color: #059669; display: flex; align-items: center; gap: 6px;">
                                    <i class="fas fa-money-bill-wave"></i> Cash: <strong id="pmRatioCashVal">₹0.00 (0%)</strong>
                                </span>
                                <span style="color: #2563eb; display: flex; align-items: center; gap: 6px;">
                                    <i class="fas fa-bolt"></i> Digital & Online: <strong id="pmRatioDigitalVal">₹0.00 (0%)</strong>
                                </span>
                            </div>
                        </div>
                        <div class="paymodes-ratio-bar">
                            <div class="paymodes-ratio-seg-cash" id="pmRatioCashBar" style="width: 0%;" title="Cash"></div>
                            <div class="paymodes-ratio-seg-digital" id="pmRatioDigitalBar" style="width: 0%;" title="Digital"></div>
                        </div>
                    </div>

                    <!-- Payment Mode Dynamic Cards Grid -->
                    <div class="paymodes-cards-grid" id="paymentModeCardsGrid">
                        <!-- Populated dynamically via JS -->
                    </div>

                    <!-- Payment Mode Detailed Settlement Matrix Table -->
                    <div class="pm-table-card">
                        <div class="pm-table-header">
                            <h3><i class="fas fa-table-list" style="color: #1f6b4a;"></i> Channel Performance & Settlement Matrix</h3>
                            <span style="font-size: 0.85rem; color: #64748b; font-weight: 600;" id="pmTotalSummaryLabel">Total Collection: ₹0.00</span>
                        </div>
                        <div class="dataTable-wrapper">
                            <table class="display" style="width:100%" id="paymentModeTable">
                                <thead>
                                    <tr>
                                        <th>Channel / Mode</th>
                                        <th>Settlement Type</th>
                                        <th style="text-align:center;">Transactions</th>
                                        <th style="text-align:right;">Avg Ticket (₹)</th>
                                        <th style="width: 25%;">Contribution Share</th>
                                        <th style="text-align:right;">Total Collected (₹)</th>
                                        <th style="text-align:center; width: 80px;">Action</th>
                                    </tr>
                                </thead>
                                <tbody id="paymentModeTbody">
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

        </div>
        </main>
    </div>
</div>

<!-- Bill Details Modal -->
<div id="billModal" class="modal-overlay">
    <div class="modal-content-lg">
        <div class="modal-header">
            <h3 style="margin:0; font-size:1.2rem;">Bill Details: <span id="modalBillNo"></span></h3>
            <div>
                <button onclick="printBill()" style="padding: 6px 12px; background: #1f6b4a; color: white; border: none; border-radius: 6px; cursor: pointer; margin-right: 8px;"><i class="fas fa-print"></i> Print</button>
                <button onclick="closeBillModal()" style="background: none; border: none; font-size: 1.2rem; cursor: pointer; color: #64748b;"><i class="fas fa-times"></i></button>
            </div>
        </div>
        <div class="modal-body" id="modalBillContent">
            Loading...
        </div>
    </div>
</div>

<!-- Scripts -->
<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.print.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
    let billsDataTable;
    let timelineChartInstance = null;

    $(document).ready(function() {
        // Init dates
        updateDatePreset();
        
        // Load users for filter dropdown
        fetchUsersList();
        
        // Init DataTable for All Bills
        initDataTable();
        
        // Initial Fetch
        fetchReportData();
    });

    function switchTab(tabId) {
        document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
        document.querySelectorAll('.tab-content').forEach(content => content.classList.remove('active'));
        
        event.currentTarget.classList.add('active');
        document.getElementById(tabId).classList.add('active');
    }

    function updateDatePreset() {
        const preset = document.getElementById('datePreset').value;
        const today = new Date();
        const startInput = document.getElementById('startDate');
        const endInput = document.getElementById('endDate');
        
        let start = new Date();
        let end = new Date();

        if (preset === 'all') {
            startInput.value = '';
            endInput.value = '';
            return;
        } else if (preset === 'last30') {
            start.setDate(start.getDate() - 30);
        } else if (preset === 'lastmonth') {
            // First day of previous month to last day of previous month
            start = new Date(today.getFullYear(), today.getMonth() - 1, 1);
            end = new Date(today.getFullYear(), today.getMonth(), 0);
        } else if (preset === 'thismonth') {
            start = new Date(today.getFullYear(), today.getMonth(), 1);
        } else if (preset === 'last7') {
            start.setDate(start.getDate() - 7);
        } else if (preset === 'today') {
            // keep today
        } else if (preset === 'yesterday') {
            start.setDate(start.getDate() - 1);
            end.setDate(end.getDate() - 1);
        } else if (preset === 'custom') {
            return; // Don't auto-set
        }

        // Format dates as YYYY-MM-DD using local time
        const pad = n => String(n).padStart(2, '0');
        startInput.value = `${start.getFullYear()}-${pad(start.getMonth() + 1)}-${pad(start.getDate())}`;
        endInput.value = `${end.getFullYear()}-${pad(end.getMonth() + 1)}-${pad(end.getDate())}`;
    }

    function fetchUsersList() {
        // Fetch all distinct users who generated bills (can be extracted from analytics without filters initially)
        fetch('/GM_HMS/api/billing/opd/analytics?start_date=2000-01-01&end_date=2099-12-31')
            .then(r => r.json())
            .then(res => {
                if (res.status === 'success' && res.data.receptionist_performance) {
                    const select = document.getElementById('userFilter');
                    res.data.receptionist_performance.forEach(u => {
                        let opt = document.createElement('option');
                        opt.value = u.receptionist;
                        opt.textContent = u.receptionist;
                        select.appendChild(opt);
                    });
                }
            });
    }

    function getFilters() {
        const sVal = document.getElementById('startDate').value;
        const eVal = document.getElementById('endDate').value;
        return {
            date_from: sVal,
            date_to: eVal,
            start_date: sVal || '2000-01-01', // For analytics API fallback
            end_date: eVal || '2099-12-31',     // For analytics API fallback
            created_by: document.getElementById('userFilter').value,
            receptionist: document.getElementById('userFilter').value, // For analytics API
            payment_mode: document.getElementById('paymentModeFilter').value
        };
    }

    function fetchReportData() {
        const filters = getFilters();
        
        // 1. Reload DataTable
        billsDataTable.ajax.reload();
        
        // 2. Fetch Analytics for KPIs, Users, Timeline, Payments
        const qs = new URLSearchParams(filters).toString();
        fetch(`/GM_HMS/api/billing/opd/analytics?${qs}`)
            .then(r => r.json())
            .then(res => {
                if (res.status === 'success') {
                    renderAnalytics(res.data);
                }
            });
    }

    function initDataTable() {
        billsDataTable = $('#billsTable').DataTable({
            processing: true,
            serverSide: false, // For simplicity and export support, we fetch all filtered and client-paginate
            ajax: {
                url: '/GM_HMS/api/billing/opd',
                data: function(d) {
                    const f = getFilters();
                    return {
                        date_from: f.date_from,
                        date_to: f.date_to,
                        created_by: f.created_by,
                        payment_mode: f.payment_mode
                    };
                },
                dataSrc: function(json) {
                    return json.data || [];
                }
            },
            columns: [
                { data: 'bill_id' },
                { data: 'bill_date', render: function(data) { return new Date(data).toLocaleDateString(); } },
                { data: 'patient_name' },
                { data: 'created_by' },
                { data: 'payment_mode' },
                { data: 'grand_total', render: $.fn.dataTable.render.number(',', '.', 2, '₹') },
                { data: 'amount_paid', render: $.fn.dataTable.render.number(',', '.', 2, '₹') },
                { data: 'balance_due', render: $.fn.dataTable.render.number(',', '.', 2, '₹') },
                { data: 'payment_status', render: function(data) {
                    let cls = data==='Paid'?'bg-success':(data==='Pending'?'bg-danger':'bg-warning');
                    return `<span class="badge ${cls}">${data}</span>`;
                }},
                { data: 'bill_id', orderable: false, render: function(data) {
                    return `<button onclick="viewBillDetails('${data}')" style="padding:4px 8px; background:#f1f5f9; border:1px solid #cbd5e1; border-radius:4px; cursor:pointer;"><i class="fas fa-eye"></i></button>`;
                }}
            ],
            dom: 'Bfrtip',
            buttons: ['copy', 'csv', 'excel', 'print'],
            pageLength: 25,
            order: [[1, 'desc']]
        });
    }

    function renderAnalytics(data) {
        // KPIs
        const m = data.metrics;
        document.getElementById('kpiTotalBilled').innerText = parseFloat(m.total_billing_amount||0).toFixed(2);
        document.getElementById('kpiTotalCollected').innerText = parseFloat(m.total_collected||0).toFixed(2);
        document.getElementById('kpiTotalDue').innerText = parseFloat(m.total_pending||0).toFixed(2);
        document.getElementById('kpiTotalBills').innerText = m.total_bills||0;

        // User Accountability
        let userHtml = '';
        data.receptionist_performance.forEach(u => {
            userHtml += `<tr>
                <td>${u.receptionist}</td>
                <td>${u.bills_generated}</td>
                <td>₹${parseFloat(u.total_billing).toFixed(2)}</td>
                <td>₹${parseFloat(u.collected).toFixed(2)}</td>
                <td style="color:#ef4444;">₹${parseFloat(u.pending).toFixed(2)}</td>
            </tr>`;
        });
        document.getElementById('usersTbody').innerHTML = userHtml;

        // Payment Modes (Modern Cards & Settlement Matrix without Graph)
        let payCardsHtml = '';
        let payTableHtml = '';
        let totalCollectedAll = 0;
        let cashTotal = 0;
        let digitalTotal = 0;

        // Calculate grand sum of collected payments
        const paymentList = data.payment_methods || [];
        paymentList.forEach(p => {
            const val = parseFloat(p.total || 0);
            totalCollectedAll += val;
            const modeClean = (p.method || '').trim().toLowerCase();
            if (modeClean === 'cash') {
                cashTotal += val;
            } else {
                digitalTotal += val;
            }
        });

        // Config map for payment mode theming
        const modeThemeMap = {
            'cash': {
                icon: 'fas fa-money-bill-wave',
                name: 'Cash',
                category: 'Physical Cash',
                color: '#10b981',
                bg: '#ecfdf5',
                border: '#a7f3d0'
            },
            'upi': {
                icon: 'fas fa-qrcode',
                name: 'UPI / QR Code',
                category: 'Instant Digital',
                color: '#6366f1',
                bg: '#eef2ff',
                border: '#c7d2fe'
            },
            'card': {
                icon: 'fas fa-credit-card',
                name: 'Card (Debit/Credit)',
                category: 'Card POS',
                color: '#0284c7',
                bg: '#f0f9ff',
                border: '#bae6fd'
            },
            'bank transfer': {
                icon: 'fas fa-university',
                name: 'Bank Transfer / NEFT',
                category: 'Direct Bank',
                color: '#0d9488',
                bg: '#f0fdfa',
                border: '#99f6e4'
            },
            'cheque': {
                icon: 'fas fa-money-check-alt',
                name: 'Cheque / DD',
                category: 'Clearing Instrument',
                color: '#d97706',
                bg: '#fffbeb',
                border: '#fde68a'
            }
        };

        const defaultTheme = {
            icon: 'fas fa-wallet',
            name: 'Other',
            category: 'Settlement',
            color: '#7c3aed',
            bg: '#f5f3ff',
            border: '#ddd6fe'
        };

        // Update Ratio bar
        const cashPct = totalCollectedAll > 0 ? ((cashTotal / totalCollectedAll) * 100).toFixed(1) : '0.0';
        const digitalPct = totalCollectedAll > 0 ? ((digitalTotal / totalCollectedAll) * 100).toFixed(1) : '0.0';
        
        const cashRatioValEl = document.getElementById('pmRatioCashVal');
        const digitalRatioValEl = document.getElementById('pmRatioDigitalVal');
        const cashBarEl = document.getElementById('pmRatioCashBar');
        const digitalBarEl = document.getElementById('pmRatioDigitalBar');
        const totalSummaryEl = document.getElementById('pmTotalSummaryLabel');

        if (cashRatioValEl) cashRatioValEl.textContent = `₹${cashTotal.toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2})} (${cashPct}%)`;
        if (digitalRatioValEl) digitalRatioValEl.textContent = `₹${digitalTotal.toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2})} (${digitalPct}%)`;
        if (cashBarEl) cashBarEl.style.width = `${cashPct}%`;
        if (digitalBarEl) digitalBarEl.style.width = `${digitalPct}%`;
        if (totalSummaryEl) totalSummaryEl.textContent = `Total Collection: ₹${totalCollectedAll.toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2})}`;

        if (paymentList.length === 0) {
            payCardsHtml = '<div style="grid-column: 1/-1; padding: 30px; text-align: center; color: #94a3b8; background: #f8fafc; border-radius: 12px; border: 1px dashed #cbd5e1;"><i class="fas fa-info-circle" style="font-size: 1.5rem; margin-bottom: 8px; display:block;"></i> No payment transactions recorded for the selected period.</div>';
            payTableHtml = '<tr><td colspan="7" style="text-align: center; padding: 25px; color: #94a3b8;"><i class="fas fa-inbox" style="font-size: 1.5rem; margin-bottom: 6px; display:block;"></i>No payment records found.</td></tr>';
        } else {
            paymentList.forEach(p => {
                const rawMethod = (p.method || 'Unspecified').trim();
                const key = rawMethod.toLowerCase();
                const theme = modeThemeMap[key] || { ...defaultTheme, name: rawMethod };
                const amount = parseFloat(p.total || 0);
                const count = parseInt(p.count || 0) || 1;
                const sharePct = totalCollectedAll > 0 ? ((amount / totalCollectedAll) * 100).toFixed(1) : '0.0';
                const avgTicket = count > 0 ? (amount / count) : amount;

                // Card HTML
                payCardsHtml += `
                    <div class="pm-card" style="border-left: 4px solid ${theme.color};" onclick="filterByPaymentMode('${rawMethod}')" title="Click to filter by ${rawMethod}">
                        <div>
                            <div class="pm-card-top">
                                <div class="pm-icon-wrap" style="background: ${theme.bg}; color: ${theme.color}; border: 1px solid ${theme.border};">
                                    <i class="${theme.icon}"></i>
                                </div>
                                <span class="pm-share-badge" style="background: ${theme.bg}; color: ${theme.color}; border: 1px solid ${theme.border};">
                                    ${sharePct}%
                                </span>
                            </div>
                            <div class="pm-mode-name">${theme.name}</div>
                            <div class="pm-mode-amount">₹${amount.toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</div>
                            <div class="pm-progress-track">
                                <div class="pm-progress-fill" style="width: ${sharePct}%; background: ${theme.color};"></div>
                            </div>
                        </div>
                        <div class="pm-meta-footer">
                            <span><i class="fas fa-receipt"></i> <strong>${count}</strong> ${count === 1 ? 'Receipt' : 'Receipts'}</span>
                            <span>Avg: <strong>₹${parseFloat(avgTicket).toLocaleString('en-IN', {minimumFractionDigits: 0, maximumFractionDigits: 0})}</strong></span>
                        </div>
                    </div>
                `;

                // Table Row HTML
                payTableHtml += `
                    <tr>
                        <td>
                            <div style="display: flex; align-items: center; gap: 10px;">
                                <div style="width: 34px; height: 34px; border-radius: 8px; background: ${theme.bg}; color: ${theme.color}; display: flex; align-items: center; justify-content: center; font-size: 1rem; border: 1px solid ${theme.border};">
                                    <i class="${theme.icon}"></i>
                                </div>
                                <div>
                                    <strong style="color: #1e293b;">${rawMethod}</strong>
                                </div>
                            </div>
                        </td>
                        <td><span style="font-size: 0.82rem; color: #475569; background: #f1f5f9; padding: 3px 8px; border-radius: 4px; font-weight: 500;">${theme.category}</span></td>
                        <td style="text-align: center; font-weight: 700; color: #334155;">${count}</td>
                        <td style="text-align: right; color: #475569; font-weight: 500;">₹${parseFloat(avgTicket).toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</td>
                        <td>
                            <div class="pm-contrib-bar-wrap">
                                <div class="pm-contrib-bar">
                                    <div class="pm-contrib-fill" style="width: ${sharePct}%; background: ${theme.color};"></div>
                                </div>
                                <span style="font-size: 0.8rem; font-weight: 700; color: #334155; min-width: 42px;">${sharePct}%</span>
                            </div>
                        </td>
                        <td style="text-align: right; font-weight: 700; font-size: 0.98rem; color: #1e293b;">
                            ₹${amount.toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2})}
                        </td>
                        <td style="text-align: center;">
                            <button class="pm-filter-chip" onclick="filterByPaymentMode('${rawMethod}')" title="Filter report by ${rawMethod}">
                                <i class="fas fa-filter"></i> View
                            </button>
                        </td>
                    </tr>
                `;
            });
        }

        const cardsGridEl = document.getElementById('paymentModeCardsGrid');
        if (cardsGridEl) cardsGridEl.innerHTML = payCardsHtml;
        const tbodyEl = document.getElementById('paymentModeTbody');
        if (tbodyEl) tbodyEl.innerHTML = payTableHtml;

        // Charts
        renderTimelineChart(data.trends);
    }

    function filterByPaymentMode(mode) {
        const sel = document.getElementById('paymentModeFilter');
        if (sel) {
            let found = false;
            for (let i = 0; i < sel.options.length; i++) {
                if (sel.options[i].value.toLowerCase() === mode.toLowerCase()) {
                    sel.selectedIndex = i;
                    found = true;
                    break;
                }
            }
            if (!found && mode && mode !== 'Unspecified') {
                const opt = document.createElement('option');
                opt.value = mode;
                opt.textContent = mode;
                sel.appendChild(opt);
                sel.value = mode;
            }
        }
        // Switch to All Bills tab
        document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
        document.querySelectorAll('.tab-content').forEach(content => content.classList.remove('active'));
        const allBillsBtn = document.querySelector('.tab-btn[onclick*="tab-all-bills"]');
        if (allBillsBtn) allBillsBtn.classList.add('active');
        const allBillsTab = document.getElementById('tab-all-bills');
        if (allBillsTab) allBillsTab.classList.add('active');

        fetchReportData();
    }

    function renderTimelineChart(trends) {
        const labels = (trends || []).map(t => t.trend_date);
        const collections = (trends || []).map(t => t.collections);
        const revenue = (trends || []).map(t => t.revenue);

        const ctx = document.getElementById('timelineChart').getContext('2d');
        if (timelineChartInstance) timelineChartInstance.destroy();
        
        timelineChartInstance = new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [
                    { label: 'Collection (₹)', data: collections, borderColor: '#1f6b4a', backgroundColor: 'rgba(31, 107, 74, 0.1)', fill: true, tension: 0.3 },
                    { label: 'Billed Amount (₹)', data: revenue, borderColor: '#12442e', backgroundColor: 'transparent', borderDash: [5, 5], tension: 0.3 }
                ]
            },
            options: { responsive: true, scales: { y: { beginAtZero: true } } }
        });
    }

    function viewBillDetails(billId) {
        document.getElementById('modalBillNo').innerText = billId;
        document.getElementById('modalBillContent').innerHTML = '<div style="text-align:center; padding: 40px;"><i class="fas fa-spinner fa-spin fa-2x"></i> Loading...</div>';
        document.getElementById('billModal').style.display = 'flex';
        
        fetch(`/GM_HMS/api/billing/opd/${billId}`)
            .then(r => r.json())
            .then(res => {
                if (res.status === 'success' && res.data) {
                    const b = res.data;
                    let itemsHtml = b.items.map(i => `
                        <tr>
                            <td style="padding:8px; border-bottom:1px solid #e2e8f0;">${i.item_name}</td>
                            <td style="padding:8px; border-bottom:1px solid #e2e8f0;">${i.quantity}</td>
                            <td style="padding:8px; border-bottom:1px solid #e2e8f0;">₹${parseFloat(i.unit_price).toFixed(2)}</td>
                            <td style="padding:8px; border-bottom:1px solid #e2e8f0; text-align:right;">₹${parseFloat(i.total_price).toFixed(2)}</td>
                        </tr>
                    `).join('');
                    
                    let html = `
                        <div style="display:flex; justify-content:space-between; margin-bottom: 20px; padding-bottom:20px; border-bottom:1px solid #e2e8f0;">
                            <div>
                                <strong>Patient:</strong> ${b.patient_name || b.name}<br>
                                <strong>Phone:</strong> ${b.mobile || 'N/A'}<br>
                                <strong>Doctor:</strong> ${b.doctor_name || 'Walking'}<br>
                            </div>
                            <div style="text-align:right;">
                                <strong>Date:</strong> ${new Date(b.bill_date).toLocaleDateString()}<br>
                                <strong>Generated By:</strong> ${b.created_by}<br>
                                <strong>Status:</strong> <span class="badge ${b.payment_status==='Paid'?'bg-success':'bg-danger'}">${b.payment_status}</span>
                            </div>
                        </div>
                        <table style="width:100%; border-collapse: collapse; margin-bottom: 20px;">
                            <thead style="background:#f8fafc;">
                                <tr><th style="padding:8px; text-align:left;">Item</th><th style="padding:8px; text-align:left;">Qty</th><th style="padding:8px; text-align:left;">Price</th><th style="padding:8px; text-align:right;">Total</th></tr>
                            </thead>
                            <tbody>${itemsHtml}</tbody>
                        </table>
                        <div style="text-align:right; font-size:1.1rem; line-height: 1.6;">
                            <div>Subtotal: ₹${parseFloat(parseFloat(b.grand_total) + parseFloat(b.discount_amount)).toFixed(2)}</div>
                            <div style="color:#ef4444;">Discount: -₹${parseFloat(b.discount_amount).toFixed(2)}</div>
                            <div style="font-weight:bold; font-size:1.3rem;">Grand Total: ₹${parseFloat(b.grand_total).toFixed(2)}</div>
                            <div style="color:#16a34a;">Paid: ₹${parseFloat(b.amount_paid).toFixed(2)} (${b.payment_mode})</div>
                            ${b.balance_due > 0 ? `<div style="color:#ef4444;">Due: ₹${parseFloat(b.balance_due).toFixed(2)}</div>` : ''}
                        </div>
                    `;
                    document.getElementById('modalBillContent').innerHTML = html;
                } else {
                    document.getElementById('modalBillContent').innerHTML = '<div style="color:red; text-align:center;">Failed to load bill details.</div>';
                }
            })
            .catch(err => {
                document.getElementById('modalBillContent').innerHTML = '<div style="color:red; text-align:center;">Error loading details.</div>';
            });
    }

    function closeBillModal() {
        document.getElementById('billModal').style.display = 'none';
    }
    
    function printBill() {
        const billId = document.getElementById('modalBillNo').innerText;
        window.open(`/GM_HMS/reception_view/print_opd_bill.php?bill_id=${billId}`, '_blank'); 
    }
</script>
</body>
</html>
