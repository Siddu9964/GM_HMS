<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="stylesheet" href="/GM_HMS/assets/css/gm-theme.css">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hospital Rooms & Bed Facility Manager - GM HMS</title>
    
    <!-- Fonts & Icons -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Common Admin CSS -->
    <link rel="stylesheet" href="assets/css/admin_common.css">

    <style>
        :root {
            --forest-primary: #1f6b4a;
            --forest-dark: #154c34;
            --forest-light: #e8f5ef;
            --cream-bg: #f8f6f0;
            --card-surface: #ffffff;
            --border-soft: #e2e8f0;
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: var(--cream-bg);
            color: #1e293b;
        }

        /* ── Visible Smooth Scrolling on Main Content ── */
        main#mainScrollArea {
            overflow-y: auto !important;
            overflow-x: hidden !important;
            height: calc(100vh - 4rem) !important;
            scrollbar-width: thin !important;
            -ms-overflow-style: auto !important;
            scroll-behavior: smooth;
        }
        main#mainScrollArea::-webkit-scrollbar {
            display: block !important;
            width: 8px !important;
        }
        main#mainScrollArea::-webkit-scrollbar-track {
            background: #f1f5f9 !important;
        }
        main#mainScrollArea::-webkit-scrollbar-thumb {
            background: #94a3b8 !important;
            border-radius: 9999px !important;
        }
        main#mainScrollArea::-webkit-scrollbar-thumb:hover {
            background: #64748b !important;
        }

        /* Drawer scroll */
        #bedInspectorPanel {
            max-height: calc(100vh - 6rem);
            overflow-y: auto !important;
            scrollbar-width: thin !important;
        }
        #bedInspectorPanel::-webkit-scrollbar {
            display: block !important;
            width: 5px !important;
        }
        #bedInspectorPanel::-webkit-scrollbar-thumb {
            background: #cbd5e1 !important;
            border-radius: 9999px !important;
        }

        /* Master Table Card Styling */
        .master-table-card {
            background: #ffffff;
            border-radius: 24px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 4px 20px -2px rgba(0, 0, 0, 0.03), 0 2px 6px -1px rgba(0, 0, 0, 0.02);
        }

        .category-filter-chip {
            transition: all 0.18s ease;
        }
        .category-filter-chip.active {
            background: #1f6b4a;
            color: #ffffff;
            border-color: #1f6b4a;
            box-shadow: 0 4px 10px rgba(31, 107, 74, 0.2);
        }

        /* Floor Navigation Pill Tabs */
        .floor-tab {
            transition: all 0.2s ease;
        }
        .floor-tab.active {
            background: #1f6b4a;
            color: #ffffff;
            box-shadow: 0 4px 12px rgba(31, 107, 74, 0.25);
        }

        /* Bed Tile Aesthetics */
        .bed-tile {
            transition: all 0.22s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
        }
        .bed-tile:hover {
            transform: translateY(-3px);
            box-shadow: 0 14px 28px -6px rgba(0, 0, 0, 0.08), 0 6px 12px -4px rgba(0, 0, 0, 0.04);
        }

        /* Available Bed Tile (Emerald) */
        .tile-available {
            background: linear-gradient(180deg, #ffffff 0%, #f0fdf4 100%);
            border: 1.5px solid #86efac;
        }
        .tile-available:hover {
            border-color: #22c55e;
            box-shadow: 0 14px 30px -6px rgba(34, 197, 94, 0.22);
        }

        /* Occupied Bed Tile (Rose) */
        .tile-occupied {
            background: linear-gradient(180deg, #ffffff 0%, #fff1f2 100%);
            border: 1.5px solid #fda4af;
        }
        .tile-occupied:hover {
            border-color: #f43f5e;
            box-shadow: 0 14px 30px -6px rgba(244, 63, 94, 0.22);
        }

        /* Maintenance / Cleaning Bed Tile (Amber) */
        .tile-maintenance, .tile-cleaning {
            background: linear-gradient(180deg, #ffffff 0%, #fffbeb 100%);
            border: 1.5px solid #fde68a;
        }
        .tile-maintenance:hover, .tile-cleaning:hover {
            border-color: #f59e0b;
            box-shadow: 0 14px 30px -6px rgba(245, 158, 11, 0.22);
        }

        .tile-selected {
            border-color: #1f6b4a !important;
            box-shadow: 0 0 0 3px rgba(31, 107, 74, 0.35), 0 16px 32px -8px rgba(31, 107, 74, 0.3) !important;
            transform: scale(1.02);
            z-index: 10;
        }

        /* Modals */
        .modal-overlay {
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(15, 23, 42, 0.6);
            backdrop-filter: blur(6px);
            z-index: 999;
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            pointer-events: none;
            transition: all 0.22s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .modal-overlay.active {
            opacity: 1;
            pointer-events: auto;
        }
        .modal-content {
            background: #ffffff;
            border-radius: 24px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
            transform: scale(0.96) translateY(8px);
            transition: all 0.22s cubic-bezier(0.4, 0, 0.2, 1);
            max-height: 90vh;
            overflow-y: auto;
        }
        .modal-overlay.active .modal-content {
            transform: scale(1) translateY(0);
        }

        /* Toast Container */
        .toast-container {
            position: fixed;
            bottom: 24px;
            right: 24px;
            z-index: 10000;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        .toast {
            animation: slideInUp 0.3s cubic-bezier(0.16, 1, 0.3, 1);
            transition: all 0.3s ease;
        }
        @keyframes slideInUp {
            from { transform: translateY(100%); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
    </style>
</head>
<body class="bg-[#f8f6f0] text-slate-800">
    <div class="flex h-screen overflow-hidden">
        <!-- Sidebar -->
        <?php include 'includes/sidebar.php'; ?>
        
        <div class="flex-1 flex flex-col overflow-hidden min-w-0">
            <!-- Navbar -->
            <?php include 'includes/navbar.php'; ?>
            
            <main id="mainScrollArea" class="flex-1 overflow-y-auto p-4 md:p-6 lg:p-7 space-y-6 pb-28">

                <!-- ─────────────────────────────────────────────────────────────
                     TOP BANNER: HEADER & MASTER CONTROLS
                     ───────────────────────────────────────────────────────────── -->
                <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 bg-white p-5 rounded-3xl border border-slate-200/80 shadow-sm">
                    <div class="flex items-center gap-4">
                        <div class="w-14 h-14 rounded-2xl flex items-center justify-center text-white shadow-md shadow-emerald-900/10 flex-shrink-0" style="background: linear-gradient(135deg, #1f6b4a 0%, #154c34 100%);">
                            <i class="fas fa-hospital-alt text-2xl"></i>
                        </div>
                        <div>
                            <div class="flex items-center gap-2.5">
                                <h1 class="text-2xl font-black tracking-tight text-[#1f6b4a]">Rooms & Bed Facility Manager</h1>
                                <span class="px-2.5 py-0.5 rounded-full text-[11px] font-extrabold uppercase bg-emerald-50 text-emerald-800 border border-emerald-200 flex items-center gap-1.5">
                                    <span class="w-2 h-2 rounded-full bg-emerald-500 animate-ping"></span> Live Database
                                </span>
                            </div>
                            <p class="text-xs text-slate-500 font-medium mt-0.5">Visual bed occupancy directory, live patient tracking, and master room configuration.</p>
                        </div>
                    </div>

                    <div class="flex flex-wrap items-center gap-2.5">
                        <!-- Primary View Mode Toggle -->
                        <div class="inline-flex bg-slate-100 p-1 rounded-2xl border border-slate-200/80">
                            <button id="viewBtnTiles" onclick="setViewMode('tiles')" class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition-all flex items-center gap-1.5 bg-white text-[#1f6b4a] shadow-sm">
                                <i class="fas fa-th-large"></i> <span>Visual Beds</span>
                            </button>
                            <button id="viewBtnMaster" onclick="setViewMode('master')" class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition-all flex items-center gap-1.5 text-slate-600 hover:text-slate-900">
                                <i class="fas fa-table-list"></i> <span>Room & Bed Master</span>
                            </button>
                        </div>

                        <!-- Open Master Manager Button -->
                        <button onclick="setViewMode('master')" class="px-3.5 py-2 bg-emerald-50 hover:bg-emerald-100 text-emerald-800 border border-emerald-200 font-bold rounded-2xl text-xs flex items-center gap-1.5 transition-all shadow-xs" title="Open tabular Room & Bed Master Inventory">
                            <i class="fas fa-sliders-h"></i> <span>Manage All Rooms</span>
                        </button>

                        <!-- Refresh -->
                        <button onclick="fetchBeds()" class="px-3.5 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold rounded-2xl text-xs flex items-center gap-1.5 transition-all">
                            <i class="fas fa-sync-alt" id="refreshIcon"></i> Refresh
                        </button>

                        <!-- Add Room & Bed CTA -->
                        <button onclick="openBedModal('create')" class="px-4 py-2 bg-[#1f6b4a] hover:bg-[#154c34] text-white font-bold rounded-2xl text-xs flex items-center gap-2 shadow-md shadow-emerald-900/15 transition-all transform active:scale-95">
                            <i class="fas fa-plus-circle text-sm"></i> Add Room / Bed
                        </button>
                    </div>
                </div>

                <!-- ─────────────────────────────────────────────────────────────
                     KPI SUMMARY METRICS BAR
                     ───────────────────────────────────────────────────────────── -->
                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3.5">
                    <!-- Total Rooms -->
                    <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-sm flex flex-col justify-between">
                        <div class="flex items-center justify-between text-slate-400 text-xs font-bold uppercase tracking-wider">
                            <span>Total Rooms</span>
                            <i class="fas fa-door-closed text-slate-300"></i>
                        </div>
                        <div class="mt-2 flex items-baseline justify-between">
                            <span class="text-2xl font-black text-slate-900" id="kpiTotalRooms">0</span>
                            <span class="text-[10px] font-bold text-slate-400" id="kpiTotalBedsLabel">0 Beds</span>
                        </div>
                    </div>

                    <!-- Available (Vacant) -->
                    <div class="bg-white p-4 rounded-2xl border border-emerald-200/80 shadow-sm flex flex-col justify-between" style="background: linear-gradient(to bottom, #ffffff, #f0fdf4);">
                        <div class="flex items-center justify-between text-emerald-700 text-xs font-bold uppercase tracking-wider">
                            <span>Available</span>
                            <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 animate-pulse"></span>
                        </div>
                        <div class="mt-2 flex items-baseline justify-between">
                            <span class="text-2xl font-black text-emerald-700" id="kpiAvailable">0</span>
                            <span class="text-[11px] font-extrabold text-emerald-600" id="kpiAvailablePct">0%</span>
                        </div>
                    </div>

                    <!-- Occupied -->
                    <div class="bg-white p-4 rounded-2xl border border-rose-200/80 shadow-sm flex flex-col justify-between" style="background: linear-gradient(to bottom, #ffffff, #fff1f2);">
                        <div class="flex items-center justify-between text-rose-700 text-xs font-bold uppercase tracking-wider">
                            <span>Occupied</span>
                            <span class="w-2.5 h-2.5 rounded-full bg-rose-500"></span>
                        </div>
                        <div class="mt-2 flex items-baseline justify-between">
                            <span class="text-2xl font-black text-rose-700" id="kpiOccupied">0</span>
                            <span class="text-[11px] font-extrabold text-rose-600" id="kpiOccupiedPct">0%</span>
                        </div>
                    </div>

                    <!-- Cleaning / Maintenance -->
                    <div class="bg-white p-4 rounded-2xl border border-amber-200/80 shadow-sm flex flex-col justify-between" style="background: linear-gradient(to bottom, #ffffff, #fffbeb);">
                        <div class="flex items-center justify-between text-amber-700 text-xs font-bold uppercase tracking-wider">
                            <span>Cleaning / Maint.</span>
                            <i class="fas fa-broom text-amber-400"></i>
                        </div>
                        <div class="mt-2 flex items-baseline justify-between">
                            <span class="text-2xl font-black text-amber-700" id="kpiMaintenance">0</span>
                            <span class="text-[11px] font-bold text-amber-600" id="kpiMaintenancePct">0%</span>
                        </div>
                    </div>

                    <!-- Reserved / Blocked -->
                    <div class="bg-white p-4 rounded-2xl border border-sky-200/80 shadow-sm flex flex-col justify-between" style="background: linear-gradient(to bottom, #ffffff, #f0f9ff);">
                        <div class="flex items-center justify-between text-sky-700 text-xs font-bold uppercase tracking-wider">
                            <span>Reserved / Hold</span>
                            <i class="fas fa-lock text-sky-400"></i>
                        </div>
                        <div class="mt-2 flex items-baseline justify-between">
                            <span class="text-2xl font-black text-sky-700" id="kpiReserved">0</span>
                            <span class="text-[11px] font-bold text-sky-600" id="kpiReservedPct">0%</span>
                        </div>
                    </div>

                    <!-- Occupancy Gauge -->
                    <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-sm flex flex-col justify-between">
                        <div class="flex items-center justify-between text-slate-500 text-xs font-bold uppercase tracking-wider">
                            <span>Occupancy Rate</span>
                            <i class="fas fa-chart-pie text-slate-400"></i>
                        </div>
                        <div class="mt-2">
                            <div class="flex justify-between text-xs font-black mb-1">
                                <span class="text-slate-900" id="occupancyRate">0%</span>
                                <span class="text-slate-400" id="occupancyStatus">Low</span>
                            </div>
                            <div class="w-full h-2.5 bg-slate-100 rounded-full overflow-hidden">
                                <div id="occupancyBar" class="h-full bg-[#1f6b4a] rounded-full transition-all duration-500" style="width: 0%;"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ─────────────────────────────────────────────────────────────
                     LOADING & ERROR STATES
                     ───────────────────────────────────────────────────────────── -->
                <div id="loadingState" class="flex flex-col items-center justify-center py-24 bg-white rounded-3xl border border-slate-200/80 shadow-sm">
                    <div class="w-12 h-12 border-4 border-slate-200 border-t-[#1f6b4a] rounded-full animate-spin mb-3"></div>
                    <p class="text-sm font-bold text-[#1f6b4a]">Loading hospital rooms and bed directory...</p>
                </div>

                <div id="errorState" class="hidden p-6 bg-red-50 text-red-700 rounded-3xl border border-red-200 text-center font-semibold">
                    <i class="fas fa-exclamation-triangle text-2xl text-red-500 mb-2 block"></i>
                    <span id="errorMessageText">Failed to load bed inventory.</span>
                </div>

                <!-- ─────────────────────────────────────────────────────────────
                     VIEW 1 (DEFAULT): VISUAL MAP VIEW (Floor Switcher, Bed Tiles & Drawer)
                     ───────────────────────────────────────────────────────────── -->
                <div id="visualMapViewContainer" class="space-y-4">
                    
                    <!-- Floor Switcher Tabs & Filters Bar -->
                    <div class="space-y-3">
                        <!-- Floor Tab Rail -->
                        <div class="flex items-center gap-2 overflow-x-auto pb-1" id="floorTabsRail">
                            <button onclick="setFloorFilter('ALL')" id="floorTab_ALL" class="floor-tab active px-4 py-2.5 rounded-2xl font-black text-xs flex items-center gap-2 bg-white text-slate-700 border border-slate-200/80 shadow-sm whitespace-nowrap">
                                <i class="fas fa-layer-group"></i> <span>All Floors</span>
                                <span class="px-2 py-0.5 rounded-full text-[10px] bg-slate-100 text-slate-800 font-extrabold" id="tabCount_ALL">0</span>
                            </button>
                        </div>

                        <!-- Filter & Search Controls -->
                        <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-sm flex flex-col md:flex-row gap-3 items-center justify-between">
                            <!-- Search Input -->
                            <div class="relative w-full md:w-96">
                                <i class="fas fa-search absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                                <input type="text" id="bedSearchInput" oninput="applyFilters()" placeholder="Search patient name, PID, bed #, room, ward..." class="w-full pl-9 pr-8 py-2.5 bg-slate-50 hover:bg-slate-100/80 focus:bg-white border border-slate-200 rounded-xl text-xs font-medium focus:ring-2 focus:ring-[#1f6b4a]/20 focus:border-[#1f6b4a] outline-none transition-all">
                                <button id="clearSearchBtn" onclick="clearSearch()" class="hidden absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600">
                                    <i class="fas fa-times-circle text-xs"></i>
                                </button>
                            </div>

                            <!-- Ward & Status Quick Filters -->
                            <div class="flex flex-wrap items-center gap-2 w-full md:w-auto justify-start md:justify-end">
                                <!-- Ward Category Selector -->
                                <div class="flex items-center gap-1.5">
                                    <span class="text-[11px] font-bold text-slate-400 uppercase">Ward:</span>
                                    <select id="wardFilterSelect" onchange="applyFilters()" class="px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold text-slate-700 outline-none focus:border-[#1f6b4a]">
                                        <option value="ALL">All Wards</option>
                                    </select>
                                </div>

                                <!-- Status Filter Tabs -->
                                <div class="inline-flex bg-slate-100 p-0.5 rounded-xl border border-slate-200/80 text-[11px] font-bold">
                                    <button onclick="setStatusFilter('ALL')" id="filterStatus_ALL" class="status-tab active px-3 py-1.5 rounded-lg bg-white text-slate-900 shadow-sm transition-all">All</button>
                                    <button onclick="setStatusFilter('Available')" id="filterStatus_Available" class="status-tab px-3 py-1.5 rounded-lg text-slate-600 hover:text-slate-900 transition-all flex items-center gap-1.5">
                                        <span class="w-2 h-2 rounded-full bg-emerald-500"></span> Available
                                    </button>
                                    <button onclick="setStatusFilter('Occupied')" id="filterStatus_Occupied" class="status-tab px-3 py-1.5 rounded-lg text-slate-600 hover:text-slate-900 transition-all flex items-center gap-1.5">
                                        <span class="w-2 h-2 rounded-full bg-rose-500"></span> Occupied
                                    </button>
                                    <button onclick="setStatusFilter('Maintenance')" id="filterStatus_Maintenance" class="status-tab px-3 py-1.5 rounded-lg text-slate-600 hover:text-slate-900 transition-all flex items-center gap-1.5">
                                        <span class="w-2 h-2 rounded-full bg-amber-500"></span> Maint.
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Ward Bays Grid & Slide-Over Inspector Layout -->
                    <div class="flex flex-col xl:flex-row gap-6 items-start">
                        <!-- Left: Ward Bays Container -->
                        <div class="flex-1 min-w-0 w-full space-y-6">
                            <div id="visualTilesView" class="space-y-6">
                                <!-- Injected dynamically: Wards -> Room Bays -> Rich Bed Tiles -->
                            </div>
                        </div>

                        <!-- Right: Slide-Over Inspector -->
                        <div class="w-full xl:w-96 flex-shrink-0 sticky top-4">
                            <div class="bg-white rounded-3xl border border-slate-200/90 shadow-xl overflow-hidden" id="bedInspectorPanel">
                                <div class="p-5 text-white relative overflow-hidden" style="background: linear-gradient(135deg, #1f6b4a 0%, #154c34 100%);">
                                    <div class="relative z-10">
                                        <div class="flex items-center justify-between mb-1 text-[10px] font-black uppercase tracking-widest text-emerald-200/90">
                                            <span id="panelHeaderCategory">Bed Operations</span>
                                            <span id="panelBedIdBadge" class="bg-emerald-800/80 px-2 py-0.5 rounded-full border border-emerald-600/40">#—</span>
                                        </div>
                                        <div class="flex items-center justify-between">
                                            <h2 id="panelBedTitle" class="text-xl font-black tracking-tight text-white">Select a Bed</h2>
                                            <div id="panelStatusPill"></div>
                                        </div>
                                        <div id="panelLocationBreadcrumb" class="text-xs text-emerald-100/80 font-semibold mt-1">Click any bed to inspect details</div>
                                    </div>
                                    <i class="fas fa-procedures absolute -right-3 -bottom-4 text-6xl text-white/10 pointer-events-none"></i>
                                </div>

                                <div id="panelEmptyState" class="p-12 text-center text-slate-400">
                                    <div class="w-16 h-16 rounded-3xl bg-slate-100 text-slate-400 flex items-center justify-center mx-auto mb-3 text-2xl">
                                        <i class="fas fa-hand-pointer"></i>
                                    </div>
                                    <p class="text-sm font-bold text-slate-700 mb-1">No Bed Selected</p>
                                    <p class="text-xs text-slate-400">Click any bed tile to view patient records, daily billing rates, or change status.</p>
                                </div>

                                <div id="panelDetailsContent" class="hidden p-5 space-y-5">
                                    <!-- Quick 1-Click Status Bar -->
                                    <div class="bg-slate-50 p-3.5 rounded-2xl border border-slate-200/80">
                                        <label class="text-[10px] font-black uppercase tracking-wider text-slate-400 block mb-2">Change Bed Status</label>
                                        <div class="grid grid-cols-3 gap-1.5 text-xs font-bold">
                                            <button onclick="quickUpdateStatus('Available')" class="py-1.5 px-2 rounded-xl bg-white hover:bg-emerald-50 hover:text-emerald-700 border border-slate-200 text-slate-700 transition-all text-center">Available</button>
                                            <button onclick="quickUpdateStatus('Cleaning')" class="py-1.5 px-2 rounded-xl bg-white hover:bg-amber-50 hover:text-amber-700 border border-slate-200 text-slate-700 transition-all text-center">Cleaning</button>
                                            <button onclick="quickUpdateStatus('Maintenance')" class="py-1.5 px-2 rounded-xl bg-white hover:bg-amber-50 hover:text-amber-700 border border-slate-200 text-slate-700 transition-all text-center">Maint.</button>
                                        </div>
                                    </div>

                                    <!-- Financial Breakdown -->
                                    <div class="bg-emerald-50/60 p-4 rounded-2xl border border-emerald-100">
                                        <div class="flex items-center justify-between mb-2.5">
                                            <span class="text-[10px] font-black uppercase tracking-wider text-emerald-800">Daily Billing Setup</span>
                                            <button onclick="editCurrentBed()" class="text-xs font-bold text-[#1f6b4a] hover:underline flex items-center gap-1">
                                                <i class="fas fa-pen text-[10px]"></i> Edit Rates
                                            </button>
                                        </div>
                                        <div class="space-y-1.5 text-xs">
                                            <div class="flex justify-between text-slate-600"><span>Base Room Rent:</span><span class="font-bold text-slate-800" id="panelPriceRent">₹0.00</span></div>
                                            <div class="flex justify-between text-slate-600"><span>Nursing Fee:</span><span class="font-bold text-slate-800" id="panelPriceNurse">₹0.00</span></div>
                                            <div class="flex justify-between text-slate-600"><span>Doctor Round:</span><span class="font-bold text-slate-800" id="panelPriceDr">₹0.00</span></div>
                                            <div class="flex justify-between text-slate-600"><span>Hospital Services:</span><span class="font-bold text-slate-800" id="panelPriceService">₹0.00</span></div>
                                            <div class="pt-2 border-t border-emerald-200 flex justify-between items-baseline font-black text-sm text-[#1f6b4a]">
                                                <span>Total Rate / Day:</span>
                                                <span class="text-base" id="panelPriceTotal">₹0.00</span>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Patient Context Card -->
                                    <div id="panelPatientContext" class="hidden bg-rose-50/80 p-4 rounded-2xl border border-rose-100 space-y-3">
                                        <div class="flex items-center justify-between text-[10px] font-black uppercase tracking-wider text-rose-800">
                                            <span class="flex items-center gap-1.5"><i class="fas fa-user-injured text-rose-500"></i> Admitted Patient</span>
                                            <span id="panelPatientPid" class="font-mono bg-white px-2 py-0.5 rounded-md border border-rose-200 text-rose-700">PID-—</span>
                                        </div>

                                        <div>
                                            <h3 id="panelPatientName" class="text-sm font-black text-slate-900">Patient Name</h3>
                                            <div class="text-xs text-slate-600 font-semibold mt-0.5 flex items-center gap-2" id="panelPatientDemographics">—</div>
                                        </div>

                                        <div class="grid grid-cols-2 gap-2 text-[11px] bg-white p-2.5 rounded-xl border border-rose-100 font-medium text-slate-600">
                                            <div><span class="text-[9px] uppercase tracking-wider font-extrabold text-slate-400 block">Admitted</span><span id="panelPatientAdmitDate" class="font-bold text-slate-800">—</span></div>
                                            <div><span class="text-[9px] uppercase tracking-wider font-extrabold text-slate-400 block">Phone</span><span id="panelPatientPhone" class="font-bold text-slate-800">—</span></div>
                                        </div>

                                        <!-- Quick Clinical Links -->
                                        <div class="grid grid-cols-2 gap-2 pt-1">
                                            <a id="panelLinkBilling" href="/GM_HMS/view/ipd_billing.php" class="py-2.5 px-3 bg-[#1f6b4a] hover:bg-[#154c34] text-white text-xs font-bold rounded-xl text-center flex items-center justify-center gap-1.5 transition-all shadow-sm">
                                                <i class="fas fa-file-invoice-dollar"></i> IPD Billing
                                            </a>
                                            <a id="panelLinkKsheet" href="/GM_HMS/nurse_view/k_sheet_view.php" class="py-2.5 px-3 bg-white hover:bg-slate-100 text-slate-700 text-xs font-bold rounded-xl border border-slate-200 text-center flex items-center justify-center gap-1.5 transition-all">
                                                <i class="fas fa-clipboard-list"></i> K-Sheet
                                            </a>
                                        </div>
                                    </div>

                                    <!-- Action Buttons -->
                                    <div class="space-y-2 pt-2">
                                        <button onclick="editCurrentBed()" class="w-full py-2.5 bg-slate-900 hover:bg-black text-white text-xs font-bold rounded-xl flex items-center justify-center gap-2 transition-all">
                                            <i class="fas fa-edit"></i> Edit Bed Pricing
                                        </button>
                                        <button onclick="deleteCurrentBed()" class="w-full py-2 bg-red-50 hover:bg-red-100 text-red-600 text-xs font-bold rounded-xl flex items-center justify-center gap-1.5 transition-all">
                                            <i class="fas fa-trash-alt"></i> Delete Bed
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ─────────────────────────────────────────────────────────────
                     VIEW 2: MASTER INVENTORY TABLE CARD (Opened via Manage All Rooms)
                     ───────────────────────────────────────────────────────────── -->
                <div id="masterViewContainer" class="hidden space-y-4">
                    <div class="master-table-card p-6 space-y-5">
                        
                        <!-- Header Bar with Back Button & Filters -->
                        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 border-b border-slate-100 pb-5">
                            <div class="flex items-center gap-3">
                                <button onclick="setViewMode('tiles')" class="px-3.5 py-2 rounded-2xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs flex items-center gap-1.5 transition-all" title="Return to Visual Map View">
                                    <i class="fas fa-arrow-left"></i> <span>Back to Visual Map</span>
                                </button>
                                <div>
                                    <h2 class="text-lg font-black text-slate-900 tracking-tight">Hospital Room & Bed Facility Matrix</h2>
                                    <p class="text-xs text-slate-500 font-medium">All room types, rooms, and individual beds with inline edit, add, and delete options.</p>
                                </div>
                            </div>

                            <!-- Right Controls: Search, Ward, Status -->
                            <div class="flex flex-wrap items-center gap-2.5">
                                <div class="relative w-full sm:w-64">
                                    <i class="fas fa-search absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                                    <input type="text" id="masterSearchInput" oninput="applyFilters()" placeholder="Search room, bed #, patient..." class="w-full pl-9 pr-7 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-medium focus:ring-2 focus:ring-[#1f6b4a]/20 outline-none">
                                    <button id="masterClearSearchBtn" onclick="clearMasterSearch()" class="hidden absolute right-2.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600">
                                        <i class="fas fa-times-circle text-xs"></i>
                                    </button>
                                </div>

                                <select id="masterFloorSelect" onchange="applyFilters()" class="px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold text-slate-700 outline-none">
                                    <option value="ALL">All Floors</option>
                                </select>

                                <select id="masterWardSelect" onchange="applyFilters()" class="px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold text-slate-700 outline-none">
                                    <option value="ALL">All Wards</option>
                                </select>

                                <select id="masterStatusSelect" onchange="applyFilters()" class="px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold text-slate-700 outline-none">
                                    <option value="ALL">All Statuses</option>
                                    <option value="Available">🟢 Available</option>
                                    <option value="Occupied">🔴 Occupied</option>
                                    <option value="Maintenance">🟡 Maintenance / Clean</option>
                                </select>
                            </div>
                        </div>

                        <!-- Room Category Filter Chips -->
                        <div class="flex items-center gap-2 overflow-x-auto pb-1" id="categoryFilterRail">
                            <button onclick="setCategoryFilter('ALL')" id="catChip_ALL" class="category-filter-chip active px-3.5 py-1.5 rounded-xl border border-slate-200 text-xs font-black bg-white text-slate-700 whitespace-nowrap">
                                All Categories (<span id="catCount_ALL">0</span>)
                            </button>
                        </div>

                        <!-- Master Table (1 Row per Bed) -->
                        <div class="overflow-x-auto rounded-2xl border border-slate-200/80">
                            <table class="w-full text-left text-xs">
                                <thead class="bg-slate-50/90 text-slate-600 font-black uppercase tracking-wider border-b border-slate-200/80 text-[10px]">
                                    <tr>
                                        <th class="py-3 px-4">Floor & Ward</th>
                                        <th class="py-3 px-4">Room Category</th>
                                        <th class="py-3 px-4">Room # & Name</th>
                                        <th class="py-3 px-4">Bed #</th>
                                        <th class="py-3 px-4 text-center">Status</th>
                                        <th class="py-3 px-4">Assigned Patient</th>
                                        <th class="py-3 px-4">Daily Charge Breakdown</th>
                                        <th class="py-3 px-4 text-right">Total Rate / Day</th>
                                        <th class="py-3 px-4 text-center">Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="masterTableBody" class="divide-y divide-slate-100 font-medium">
                                    <!-- Dynamic Bed Rows Injected Here -->
                                </tbody>
                            </table>
                        </div>

                        <!-- Empty State Notice -->
                        <div id="masterEmptyNotice" class="hidden text-center py-14 text-slate-400 font-medium">
                            <i class="fas fa-bed text-4xl mb-2 block opacity-30"></i>
                            No rooms or beds match your selected filters.
                        </div>

                        <!-- Table Footer Summary -->
                        <div class="flex items-center justify-between text-xs text-slate-500 font-semibold pt-2 border-t border-slate-100">
                            <div>Showing <span id="filteredBedCount" class="font-black text-slate-900">0</span> of <span id="totalBedCount" class="font-black text-slate-900">0</span> total beds</div>
                            <button onclick="openBedModal('create')" class="text-xs font-bold text-[#1f6b4a] hover:underline flex items-center gap-1.5">
                                <i class="fas fa-plus-circle"></i> + Add Another Room / Bed
                            </button>
                        </div>
                    </div>
                </div>

            </main>
        </div>
    </div>

    <!-- ─────────────────────────────────────────────────────────────
         MODAL: ADD / EDIT ROOM & BED SPECS WIZARD
         ───────────────────────────────────────────────────────────── -->
    <div id="bedModal" class="modal-overlay">
        <div class="modal-content w-full max-w-2xl p-6 sm:p-8">
            <div class="flex items-center justify-between border-b border-slate-100 pb-4 mb-5">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-emerald-50 text-[#1f6b4a] flex items-center justify-center text-lg font-black">
                        <i class="fas fa-sliders-h" id="modalIcon"></i>
                    </div>
                    <div>
                        <h2 class="text-xl font-black text-slate-900" id="modalTitle">Add Room & Bed</h2>
                        <p class="text-xs text-slate-500 font-medium" id="modalSubtitle">Configure location, classification, room category and daily billing rates.</p>
                    </div>
                </div>
                <button onclick="closeBedModal()" class="w-8 h-8 rounded-full bg-slate-100 hover:bg-slate-200 text-slate-500 flex items-center justify-center transition-all">
                    <i class="fas fa-times text-xs"></i>
                </button>
            </div>

            <form id="bedForm" onsubmit="event.preventDefault(); submitBedForm();" class="space-y-5">
                <input type="hidden" name="action" id="formAction" value="create">
                <input type="hidden" name="sl_no" id="formSlNo" value="">

                <!-- Price Presets -->
                <div id="ratePresetsSection" class="bg-slate-50 p-3.5 rounded-2xl border border-slate-200/80">
                    <div class="text-[10px] font-black uppercase tracking-wider text-slate-400 mb-2">⚡ 1-Click Rate Presets</div>
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
                        <button type="button" onclick="applyRatePreset(5000, 1500, 1500, 500, 'ICU')" class="p-2.5 bg-white hover:bg-emerald-50 hover:border-emerald-300 border border-slate-200 rounded-xl text-left transition-all">
                            <div class="text-[11px] font-extrabold text-slate-800">ICU / CCU</div>
                            <div class="text-[10px] text-emerald-700 font-black">₹8,500/day</div>
                        </button>
                        <button type="button" onclick="applyRatePreset(4000, 1000, 1000, 700, 'Deluxe Room')" class="p-2.5 bg-white hover:bg-emerald-50 hover:border-emerald-300 border border-slate-200 rounded-xl text-left transition-all">
                            <div class="text-[11px] font-extrabold text-slate-800">Deluxe Room</div>
                            <div class="text-[10px] text-emerald-700 font-black">₹6,700/day</div>
                        </button>
                        <button type="button" onclick="applyRatePreset(2500, 800, 800, 400, 'Semi-Private')" class="p-2.5 bg-white hover:bg-emerald-50 hover:border-emerald-300 border border-slate-200 rounded-xl text-left transition-all">
                            <div class="text-[11px] font-extrabold text-slate-800">Semi-Private</div>
                            <div class="text-[10px] text-emerald-700 font-black">₹4,500/day</div>
                        </button>
                        <button type="button" onclick="applyRatePreset(1500, 500, 500, 200, 'General Ward')" class="p-2.5 bg-white hover:bg-emerald-50 hover:border-emerald-300 border border-slate-200 rounded-xl text-left transition-all">
                            <div class="text-[11px] font-extrabold text-slate-800">General Ward</div>
                            <div class="text-[10px] text-emerald-700 font-black">₹2,700/day</div>
                        </button>
                    </div>
                </div>

                <!-- 1. Location Grid -->
                <div>
                    <h3 class="text-xs font-black uppercase tracking-wider text-[#1f6b4a] mb-2 flex items-center gap-1.5">
                        <i class="fas fa-map-marker-alt"></i> 1. Location & Ward
                    </h3>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <div>
                            <label class="block text-[11px] font-bold text-slate-600 mb-1">Floor Number *</label>
                            <select id="modalFloorNum" name="floor_number" onchange="checkCustomOption(this, 'modalFloorNumCustom')" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-medium focus:ring-2 focus:ring-[#1f6b4a]/20 outline-none" required>
                                <option value="" disabled selected>Select...</option>
                            </select>
                            <input type="number" id="modalFloorNumCustom" name="floor_number_custom" placeholder="Custom Floor #" class="hidden w-full mt-1.5 px-3 py-2 bg-white border border-slate-300 rounded-xl text-xs">
                        </div>

                        <div>
                            <label class="block text-[11px] font-bold text-slate-600 mb-1">Floor Name *</label>
                            <select id="modalFloorName" name="floor_name" onchange="checkCustomOption(this, 'modalFloorNameCustom')" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-medium focus:ring-2 focus:ring-[#1f6b4a]/20 outline-none" required>
                                <option value="" disabled selected>Select Floor...</option>
                            </select>
                            <input type="text" id="modalFloorNameCustom" name="floor_name_custom" placeholder="e.g. Floor 2" class="hidden w-full mt-1.5 px-3 py-2 bg-white border border-slate-300 rounded-xl text-xs">
                        </div>

                        <div>
                            <label class="block text-[11px] font-bold text-slate-600 mb-1">Ward Name *</label>
                            <select id="modalWardName" name="ward_name" onchange="checkCustomOption(this, 'modalWardNameCustom')" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-medium focus:ring-2 focus:ring-[#1f6b4a]/20 outline-none" required>
                                <option value="" disabled selected>Select Ward...</option>
                            </select>
                            <input type="text" id="modalWardNameCustom" name="ward_name_custom" placeholder="e.g. ICU / Male Medical" class="hidden w-full mt-1.5 px-3 py-2 bg-white border border-slate-300 rounded-xl text-xs">
                        </div>
                    </div>
                </div>

                <!-- 2. Room & Bed Specs -->
                <div>
                    <h3 class="text-xs font-black uppercase tracking-wider text-[#1f6b4a] mb-2 flex items-center gap-1.5">
                        <i class="fas fa-door-open"></i> 2. Room & Bed Specification
                    </h3>
                    <div class="grid grid-cols-1 sm:grid-cols-4 gap-3">
                        <div>
                            <label class="block text-[11px] font-bold text-slate-600 mb-1">Room Category *</label>
                            <select id="modalRoomType" name="room_type" onchange="checkCustomOption(this, 'modalRoomTypeCustom')" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-medium focus:ring-2 focus:ring-[#1f6b4a]/20 outline-none" required>
                                <option value="General Ward">General Ward</option>
                                <option value="Semi-Private">Semi-Private</option>
                                <option value="Private Room">Private Room</option>
                                <option value="Deluxe Room">Deluxe Room</option>
                                <option value="Double Sharing Room">Double Sharing Room</option>
                                <option value="ICU">ICU</option>
                                <option value="CCU">CCU</option>
                                <option value="NICU">NICU</option>
                                <option value="Emergency">Emergency</option>
                                <option value="ADD_NEW_CUSTOM">+ Add Custom...</option>
                            </select>
                            <input type="text" id="modalRoomTypeCustom" name="room_type_custom" placeholder="Custom Category" class="hidden w-full mt-1.5 px-3 py-2 bg-white border border-slate-300 rounded-xl text-xs">
                        </div>

                        <div>
                            <label class="block text-[11px] font-bold text-slate-600 mb-1">Room Number *</label>
                            <input type="text" id="modalRoomNumber" name="room_number" placeholder="e.g. 1101" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold focus:ring-2 focus:ring-[#1f6b4a]/20 outline-none" required>
                        </div>

                        <div>
                            <label class="block text-[11px] font-bold text-slate-600 mb-1">Room Name</label>
                            <input type="text" id="modalRoomName" name="room_name" placeholder="e.g. Deluxe Room 1101" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-medium focus:ring-2 focus:ring-[#1f6b4a]/20 outline-none">
                        </div>

                        <div>
                            <label class="block text-[11px] font-bold text-slate-600 mb-1">Bed Number *</label>
                            <input type="text" id="modalBedNumber" name="bed_number" placeholder="e.g. 1101-A" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold focus:ring-2 focus:ring-[#1f6b4a]/20 outline-none" required>
                        </div>
                    </div>

                    <!-- Batch create option -->
                    <div id="batchBedOption" class="mt-3 p-2.5 bg-slate-50 rounded-xl border border-slate-200/80 flex items-center justify-between text-xs">
                        <span class="font-bold text-slate-700">Add multiple beds in this room (A, B, C, D...):</span>
                        <div class="flex items-center gap-2">
                            <span class="text-slate-500 font-medium">Count:</span>
                            <select name="batch_count" id="modalBatchCount" class="px-2.5 py-1 bg-white border border-slate-300 rounded-lg font-bold text-xs">
                                <option value="1">1 Bed (Single)</option>
                                <option value="2">2 Beds (A, B)</option>
                                <option value="3">3 Beds (A, B, C)</option>
                                <option value="4">4 Beds (A, B, C, D)</option>
                                <option value="5">5 Beds</option>
                                <option value="6">6 Beds</option>
                                <option value="10">10 Beds</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- 3. Daily Pricing Structure -->
                <div>
                    <h3 class="text-xs font-black uppercase tracking-wider text-[#1f6b4a] mb-2 flex items-center gap-1.5">
                        <i class="fas fa-rupee-sign"></i> 3. Daily Rate Breakdown
                    </h3>
                    <div class="grid grid-cols-2 sm:grid-cols-5 gap-3 bg-emerald-50/40 p-3.5 rounded-2xl border border-emerald-100">
                        <div>
                            <label class="block text-[10px] font-extrabold text-slate-600 mb-1">Room Rent</label>
                            <input type="number" step="0.01" id="formRent" name="amount_per_day" value="0" oninput="calcModalTotal()" class="w-full px-3 py-1.5 bg-white border border-slate-200 rounded-xl text-xs font-bold">
                        </div>
                        <div>
                            <label class="block text-[10px] font-extrabold text-slate-600 mb-1">Nursing Fee</label>
                            <input type="number" step="0.01" id="formNurse" name="nursig_charge" value="0" oninput="calcModalTotal()" class="w-full px-3 py-1.5 bg-white border border-slate-200 rounded-xl text-xs font-bold">
                        </div>
                        <div>
                            <label class="block text-[10px] font-extrabold text-slate-600 mb-1">Doctor Round</label>
                            <input type="number" step="0.01" id="formDoctor" name="doctor_charge" value="0" oninput="calcModalTotal()" class="w-full px-3 py-1.5 bg-white border border-slate-200 rounded-xl text-xs font-bold">
                        </div>
                        <div>
                            <label class="block text-[10px] font-extrabold text-slate-600 mb-1">Service Fee</label>
                            <input type="number" step="0.01" id="formService" name="service_charge" value="0" oninput="calcModalTotal()" class="w-full px-3 py-1.5 bg-white border border-slate-200 rounded-xl text-xs font-bold">
                        </div>
                        <div class="col-span-2 sm:col-span-1">
                            <label class="block text-[10px] font-extrabold text-[#1f6b4a] mb-1">Total / Day</label>
                            <input type="text" id="formTotal" readonly value="₹0.00" class="w-full px-3 py-1.5 bg-emerald-100/70 border border-emerald-300 text-[#1f6b4a] rounded-xl text-xs font-black">
                        </div>
                    </div>
                </div>

                <!-- 4. Status & Submit -->
                <div class="flex items-center justify-between border-t border-slate-100 pt-4">
                    <div class="flex items-center gap-2">
                        <label class="text-xs font-bold text-slate-700">Initial Status:</label>
                        <select name="bed_status" id="modalBedStatus" class="px-3 py-1.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold">
                            <option value="Available" selected>Available</option>
                            <option value="Occupied">Occupied</option>
                            <option value="Cleaning">Cleaning</option>
                            <option value="Maintenance">Maintenance</option>
                            <option value="Reserved">Reserved</option>
                            <option value="Blocked">Blocked</option>
                        </select>
                    </div>

                    <div class="flex items-center gap-2">
                        <button type="button" onclick="closeBedModal()" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold rounded-xl transition-all">Cancel</button>
                        <button type="submit" id="btnSubmitModal" class="px-5 py-2 bg-[#1f6b4a] hover:bg-[#154c34] text-white text-xs font-black rounded-xl shadow-md shadow-emerald-900/15 transition-all">
                            Save Bed
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Toast Notifications Container -->
    <div id="toastContainer" class="toast-container"></div>

    <!-- ─────────────────────────────────────────────────────────────
         JAVASCRIPT CONTROLLER
         ───────────────────────────────────────────────────────────── -->
    <script>
        let allBeds = [];
        let filteredBeds = [];
        let currentBed = null;
        let activeFloorFilter = 'ALL';
        let activeWardFilter = 'ALL';
        let activeCategoryFilter = 'ALL';
        let activeStatusFilter = 'ALL';
        let currentViewMode = 'tiles'; // Default to Visual Beds view

        document.addEventListener('DOMContentLoaded', () => {
            fetchBeds();
        });

        // ── 1. DATA FETCHING ──
        async function fetchBeds() {
            const loading = document.getElementById('loadingState');
            const errorState = document.getElementById('errorState');
            const refreshIcon = document.getElementById('refreshIcon');

            refreshIcon?.classList.add('fa-spin');
            loading.classList.remove('hidden');
            errorState.classList.add('hidden');

            try {
                const response = await fetch('/GM_HMS/api/hospital-beds/');
                const json = await response.json();

                if (json.success && json.data) {
                    allBeds = json.data;
                    renderFloorTabs();
                    renderCategoryChips();
                    populateFilterDropdowns();
                    updateKPIStats();
                    applyFilters();
                    setViewMode(currentViewMode);
                } else {
                    throw new Error(json.error || 'Failed to load bed records');
                }
            } catch (err) {
                console.error(err);
                document.getElementById('errorMessageText').textContent = err.message;
                errorState.classList.remove('hidden');
            } finally {
                loading.classList.add('hidden');
                refreshIcon?.classList.remove('fa-spin');
            }
        }

        // ── 2. KPI CALCULATIONS ──
        function updateKPIStats() {
            const total = allBeds.length;
            const available = allBeds.filter(b => (b.bed_status || 'Available') === 'Available').length;
            const occupied = allBeds.filter(b => b.bed_status === 'Occupied').length;
            const maintenance = allBeds.filter(b => ['Maintenance', 'Cleaning'].includes(b.bed_status)).length;
            const reserved = allBeds.filter(b => ['Reserved', 'Blocked'].includes(b.bed_status)).length;

            const uniqueRooms = new Set(allBeds.map(b => `${b.room_number}_${b.ward_name}`)).size;

            const availPct = total > 0 ? Math.round((available / total) * 100) : 0;
            const occPct = total > 0 ? Math.round((occupied / total) * 100) : 0;
            const maintPct = total > 0 ? Math.round((maintenance / total) * 100) : 0;
            const resPct = total > 0 ? Math.round((reserved / total) * 100) : 0;

            document.getElementById('kpiTotalRooms').textContent = uniqueRooms;
            document.getElementById('kpiTotalBedsLabel').textContent = `${total} Beds Total`;
            document.getElementById('kpiAvailable').textContent = available;
            document.getElementById('kpiAvailablePct').textContent = availPct + '%';
            document.getElementById('kpiOccupied').textContent = occupied;
            document.getElementById('kpiOccupiedPct').textContent = occPct + '%';
            document.getElementById('kpiMaintenance').textContent = maintenance;
            document.getElementById('kpiMaintenancePct').textContent = maintPct + '%';
            document.getElementById('kpiReserved').textContent = reserved;
            document.getElementById('kpiReservedPct').textContent = resPct + '%';

            document.getElementById('occupancyRate').textContent = occPct + '%';
            document.getElementById('occupancyBar').style.width = occPct + '%';
            document.getElementById('occupancyStatus').textContent = occPct > 80 ? 'Critical' : (occPct > 50 ? 'Moderate' : 'Healthy');

            document.getElementById('totalBedCount').textContent = total;
            document.getElementById('catCount_ALL').textContent = total;
            document.getElementById('tabCount_ALL').textContent = total;
        }

        // ── 3. FLOOR TABS & CATEGORY CHIPS ──
        function renderFloorTabs() {
            const rail = document.getElementById('floorTabsRail');
            rail.innerHTML = `
                <button onclick="setFloorFilter('ALL')" id="floorTab_ALL" class="floor-tab ${activeFloorFilter === 'ALL' ? 'active' : ''} px-4 py-2.5 rounded-2xl font-black text-xs flex items-center gap-2 bg-white text-slate-700 border border-slate-200/80 shadow-sm whitespace-nowrap">
                    <i class="fas fa-layer-group"></i> <span>All Floors</span>
                    <span class="px-2 py-0.5 rounded-full text-[10px] bg-slate-100 text-slate-800 font-extrabold" id="tabCount_ALL">${allBeds.length}</span>
                </button>
            `;

            const uniqueFloors = [];
            const seen = new Set();
            for (const b of allBeds) {
                const name = b.floor_name || ('Floor ' + (b.floor_number || 0));
                if (!seen.has(name)) {
                    seen.add(name);
                    const bedsInFloor = allBeds.filter(x => (x.floor_name || ('Floor ' + (x.floor_number || 0))) === name);
                    const occ = bedsInFloor.filter(x => x.bed_status === 'Occupied').length;
                    uniqueFloors.push({
                        name: name,
                        num: Number(b.floor_number) || 0,
                        total: bedsInFloor.length,
                        occupied: occ
                    });
                }
            }
            uniqueFloors.sort((a, b) => a.num - b.num);

            uniqueFloors.forEach(f => {
                const btn = document.createElement('button');
                const isActive = activeFloorFilter === f.name;
                btn.id = 'floorTab_' + f.name.replace(/\s+/g, '_');
                btn.className = `floor-tab ${isActive ? 'active' : ''} px-4 py-2.5 rounded-2xl font-black text-xs flex items-center gap-2 bg-white text-slate-700 border border-slate-200/80 shadow-sm whitespace-nowrap`;
                btn.onclick = () => setFloorFilter(f.name);
                btn.innerHTML = `
                    <i class="fas fa-building"></i> <span>${f.name}</span>
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-extrabold ${isActive ? 'bg-emerald-800 text-white' : 'bg-slate-100 text-slate-700'}">
                        ${f.occupied}/${f.total}
                    </span>
                `;
                rail.appendChild(btn);
            });
        }

        function setFloorFilter(floorName) {
            activeFloorFilter = floorName;
            document.querySelectorAll('.floor-tab').forEach(t => t.classList.remove('active'));
            const activeTab = document.getElementById('floorTab_' + (floorName === 'ALL' ? 'ALL' : floorName.replace(/\s+/g, '_')));
            if (activeTab) activeTab.classList.add('active');
            applyFilters();
        }

        function renderCategoryChips() {
            const rail = document.getElementById('categoryFilterRail');
            rail.innerHTML = `
                <button onclick="setCategoryFilter('ALL')" id="catChip_ALL" class="category-filter-chip ${activeCategoryFilter === 'ALL' ? 'active' : ''} px-3.5 py-1.5 rounded-xl border border-slate-200 text-xs font-black bg-white text-slate-700 whitespace-nowrap">
                    All Categories (<span id="catCount_ALL">${allBeds.length}</span>)
                </button>
            `;

            const categories = [...new Set(allBeds.map(b => b.room_type || 'General Ward'))].filter(Boolean);
            categories.sort().forEach(cat => {
                const count = allBeds.filter(b => (b.room_type || 'General Ward') === cat).length;
                const btn = document.createElement('button');
                const isActive = activeCategoryFilter === cat;
                btn.id = 'catChip_' + cat.replace(/[^a-zA-Z0-9]/g, '_');
                btn.className = `category-filter-chip ${isActive ? 'active' : ''} px-3.5 py-1.5 rounded-xl border border-slate-200 text-xs font-bold bg-white text-slate-700 whitespace-nowrap`;
                btn.onclick = () => setCategoryFilter(cat);
                btn.innerHTML = `${cat} <span class="text-[10px] font-extrabold opacity-80">(${count})</span>`;
                rail.appendChild(btn);
            });
        }

        function setCategoryFilter(cat) {
            activeCategoryFilter = cat;
            document.querySelectorAll('.category-filter-chip').forEach(c => c.classList.remove('active'));
            const activeChip = document.getElementById('catChip_' + (cat === 'ALL' ? 'ALL' : cat.replace(/[^a-zA-Z0-9]/g, '_')));
            if (activeChip) activeChip.classList.add('active');
            applyFilters();
        }

        function populateFilterDropdowns() {
            const floors = [...new Set(allBeds.map(b => b.floor_name || ('Floor ' + (b.floor_number || 0))))].filter(Boolean);
            const floorSel = document.getElementById('masterFloorSelect');
            floorSel.innerHTML = '<option value="ALL">All Floors</option>' + floors.map(f => `<option value="${f}">${f}</option>`).join('');

            const wards = [...new Set(allBeds.map(b => b.ward_name))].filter(Boolean);
            const wardSel = document.getElementById('masterWardSelect');
            wardSel.innerHTML = '<option value="ALL">All Wards</option>' + wards.map(w => `<option value="${w}">${w}</option>`).join('');

            const visualWardSel = document.getElementById('wardFilterSelect');
            visualWardSel.innerHTML = '<option value="ALL">All Wards</option>' + wards.map(w => `<option value="${w}">${w}</option>`).join('');
        }

        function setStatusFilter(status) {
            activeStatusFilter = status;
            document.querySelectorAll('.status-tab').forEach(btn => {
                btn.classList.remove('bg-white', 'text-slate-900', 'shadow-sm');
                btn.classList.add('text-slate-600');
            });
            const activeBtn = document.getElementById('filterStatus_' + status);
            if (activeBtn) {
                activeBtn.classList.add('bg-white', 'text-slate-900', 'shadow-sm');
                activeBtn.classList.remove('text-slate-600');
            }
            applyFilters();
        }

        function clearSearch() {
            document.getElementById('bedSearchInput').value = '';
            document.getElementById('clearSearchBtn').classList.add('hidden');
            applyFilters();
        }

        function clearMasterSearch() {
            document.getElementById('masterSearchInput').value = '';
            document.getElementById('masterClearSearchBtn').classList.add('hidden');
            applyFilters();
        }

        function applyFilters() {
            const visualQuery = (document.getElementById('bedSearchInput')?.value || '').trim().toLowerCase();
            const masterQuery = (document.getElementById('masterSearchInput')?.value || '').trim().toLowerCase();
            const query = visualQuery || masterQuery;

            const visualWard = document.getElementById('wardFilterSelect')?.value || 'ALL';
            const masterWard = document.getElementById('masterWardSelect')?.value || 'ALL';
            const wardVal = (currentViewMode === 'master') ? masterWard : visualWard;

            const masterFloor = document.getElementById('masterFloorSelect')?.value || 'ALL';
            const floorVal = (currentViewMode === 'master') ? masterFloor : activeFloorFilter;

            const masterStatus = document.getElementById('masterStatusSelect')?.value || 'ALL';
            const statusVal = (currentViewMode === 'master') ? masterStatus : activeStatusFilter;

            const clearBtn = document.getElementById('clearSearchBtn');
            if (clearBtn) clearBtn.classList.toggle('hidden', visualQuery.length === 0);
            const masterClearBtn = document.getElementById('masterClearSearchBtn');
            if (masterClearBtn) masterClearBtn.classList.toggle('hidden', masterQuery.length === 0);

            filteredBeds = allBeds.filter(b => {
                const floorName = b.floor_name || ('Floor ' + (b.floor_number || 0));
                const status = b.bed_status || 'Available';
                const cat = b.room_type || 'General Ward';

                // Category Filter
                if (activeCategoryFilter !== 'ALL' && cat !== activeCategoryFilter) return false;
                // Floor Filter
                if (floorVal !== 'ALL' && floorName !== floorVal) return false;
                // Ward Filter
                if (wardVal !== 'ALL' && b.ward_name !== wardVal) return false;
                // Status Filter
                if (statusVal !== 'ALL') {
                    if (statusVal === 'Maintenance' && !['Maintenance', 'Cleaning'].includes(status)) return false;
                    else if (statusVal !== 'Maintenance' && status !== statusVal) return false;
                }
                // Text Query
                if (query) {
                    const matchBed = String(b.bed_number || '').toLowerCase().includes(query);
                    const matchRoom = String(b.room_name || b.room_number || '').toLowerCase().includes(query);
                    const matchWard = String(b.ward_name || '').toLowerCase().includes(query);
                    const matchType = String(b.room_type || '').toLowerCase().includes(query);
                    const matchPatient = String(b.patient_name || b.patient_id || b.patient_first_name || '').toLowerCase().includes(query);
                    if (!matchBed && !matchRoom && !matchWard && !matchType && !matchPatient) return false;
                }
                return true;
            });

            document.getElementById('filteredBedCount').textContent = filteredBeds.length;

            renderMasterBedTable();
            renderVisualBedsView();
        }

        function setViewMode(mode) {
            currentViewMode = mode;
            const masterBtn = document.getElementById('viewBtnMaster');
            const tilesBtn = document.getElementById('viewBtnTiles');
            const masterContainer = document.getElementById('masterViewContainer');
            const visualContainer = document.getElementById('visualMapViewContainer');

            if (mode === 'master') {
                masterBtn.classList.add('bg-white', 'text-[#1f6b4a]', 'shadow-sm');
                masterBtn.classList.remove('text-slate-600');
                tilesBtn.classList.remove('bg-white', 'text-[#1f6b4a]', 'shadow-sm');
                tilesBtn.classList.add('text-slate-600');

                masterContainer.classList.remove('hidden');
                visualContainer.classList.add('hidden');
            } else {
                tilesBtn.classList.add('bg-white', 'text-[#1f6b4a]', 'shadow-sm');
                tilesBtn.classList.remove('text-slate-600');
                masterBtn.classList.remove('bg-white', 'text-[#1f6b4a]', 'shadow-sm');
                masterBtn.classList.add('text-slate-600');

                masterContainer.classList.add('hidden');
                visualContainer.classList.remove('hidden');
            }
        }

        // ── 4. RENDER MASTER TABLE (INDIVIDUAL BED 1 ROW) ──
        function renderMasterBedTable() {
            const tbody = document.getElementById('masterTableBody');
            const emptyNotice = document.getElementById('masterEmptyNotice');
            tbody.innerHTML = '';

            if (filteredBeds.length === 0) {
                emptyNotice.classList.remove('hidden');
                return;
            }
            emptyNotice.classList.add('hidden');

            const sorted = [...filteredBeds].sort((a, b) => {
                const floorCompare = String(a.floor_name || a.floor_number).localeCompare(String(b.floor_name || b.floor_number));
                if (floorCompare !== 0) return floorCompare;
                const wardCompare = String(a.ward_name).localeCompare(String(b.ward_name));
                if (wardCompare !== 0) return wardCompare;
                const roomCompare = String(a.room_number).localeCompare(String(b.room_number), undefined, {numeric: true});
                if (roomCompare !== 0) return roomCompare;
                return String(a.bed_number).localeCompare(String(b.bed_number), undefined, {numeric: true});
            });

            sorted.forEach(bed => {
                const status = bed.bed_status || 'Available';
                let statusBadge = '';
                if (status === 'Occupied') {
                    statusBadge = `<span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-black uppercase bg-rose-100 text-rose-800 border border-rose-200"><span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span> Occupied</span>`;
                } else if (status === 'Available') {
                    statusBadge = `<span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-black uppercase bg-emerald-100 text-emerald-800 border border-emerald-200"><span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> Available</span>`;
                } else if (['Maintenance', 'Cleaning'].includes(status)) {
                    statusBadge = `<span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-black uppercase bg-amber-100 text-amber-800 border border-amber-200"><span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span> ${status}</span>`;
                } else {
                    statusBadge = `<span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-black uppercase bg-sky-100 text-sky-800 border border-sky-200">${status}</span>`;
                }

                let patientHtml = `<span class="text-slate-400 font-normal italic">Vacant (No Patient)</span>`;
                if (status === 'Occupied' && bed.patient_id) {
                    const name = bed.patient_name || bed.patient_first_name || 'Admitted Patient';
                    patientHtml = `
                        <div class="flex items-center gap-2">
                            <div class="w-7 h-7 rounded-lg bg-rose-100 text-rose-700 flex items-center justify-center font-bold text-xs flex-shrink-0">
                                <i class="fas fa-user-injured"></i>
                            </div>
                            <div>
                                <div class="font-extrabold text-slate-900 leading-tight">${name}</div>
                                <div class="text-[10px] font-mono text-rose-700 font-bold">${bed.patient_id}</div>
                            </div>
                        </div>
                    `;
                }

                const rent = Number(bed.amount_per_day || 0).toLocaleString('en-IN');
                const nurse = Number(bed.nursig_charge || 0).toLocaleString('en-IN');
                const dr = Number(bed.doctor_charge || 0).toLocaleString('en-IN');
                const service = Number(bed.service_charge || 0).toLocaleString('en-IN');
                const total = Number(bed.total_bed_amount || 0).toLocaleString('en-IN', {minimumFractionDigits: 2});

                const tr = document.createElement('tr');
                tr.className = 'hover:bg-slate-50/80 transition-all border-b border-slate-100 text-xs';
                tr.innerHTML = `
                    <td class="py-3 px-4 text-slate-700">
                        <div class="font-bold text-slate-900">${bed.floor_name || ('Floor ' + (bed.floor_number || 0))}</div>
                        <div class="text-[11px] text-slate-500 font-medium">${bed.ward_name}</div>
                    </td>

                    <td class="py-3 px-4">
                        <span class="inline-block px-2.5 py-1 rounded-xl bg-slate-100 text-slate-800 font-extrabold text-[11px] border border-slate-200/70">
                            ${bed.room_type || 'General Ward'}
                        </span>
                    </td>

                    <td class="py-3 px-4 text-slate-800">
                        <div class="font-black text-slate-900 flex items-center gap-1.5">
                            <i class="fas fa-door-open text-slate-400 text-xs"></i>
                            <span>${bed.room_name || ('Room ' + bed.room_number)}</span>
                        </div>
                        <div class="text-[10px] text-slate-400 font-mono">#${bed.room_number}</div>
                    </td>

                    <td class="py-3 px-4">
                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-xl font-mono font-black text-xs ${status === 'Occupied' ? 'bg-rose-50 text-rose-800 border border-rose-200' : 'bg-emerald-50 text-emerald-800 border border-emerald-200'}">
                            <i class="fas fa-bed text-[10px]"></i> ${bed.bed_number}
                        </span>
                    </td>

                    <td class="py-3 px-4 text-center">
                        ${statusBadge}
                    </td>

                    <td class="py-3 px-4">
                        ${patientHtml}
                    </td>

                    <td class="py-3 px-4 text-slate-600 text-[11px] font-medium leading-tight">
                        <div class="flex items-center gap-2">
                            <span>Rent: <b class="text-slate-800">₹${rent}</b></span>
                            <span>•</span>
                            <span>Nurse: <b class="text-slate-800">₹${nurse}</b></span>
                        </div>
                        <div class="flex items-center gap-2 text-[10px] text-slate-400 mt-0.5">
                            <span>Dr: ₹${dr}</span>
                            <span>•</span>
                            <span>Service: ₹${service}</span>
                        </div>
                    </td>

                    <td class="py-3 px-4 text-right">
                        <span class="font-black text-xs text-[#1f6b4a]">₹${total}</span>
                        <span class="block text-[9px] font-bold text-slate-400 uppercase">/ day</span>
                    </td>

                    <td class="py-3 px-4 text-center">
                        <div class="inline-flex items-center gap-1.5">
                            <button onclick="editBedRecord(${JSON.stringify(bed).replace(/"/g, '&quot;')})" class="p-1.5 bg-slate-100 hover:bg-[#1f6b4a] hover:text-white text-slate-700 rounded-lg transition-all" title="Edit Bed Specs & Pricing">
                                <i class="fas fa-edit text-xs"></i>
                            </button>

                            ${status !== 'Occupied' ? `
                                <button onclick="quickUpdateStatusOnTile(${bed.sl_no}, '${status === 'Available' ? 'Cleaning' : 'Available'}')" class="p-1.5 bg-amber-50 hover:bg-amber-100 text-amber-700 rounded-lg transition-all text-xs" title="Toggle Available / Cleaning">
                                    <i class="fas fa-broom"></i>
                                </button>
                            ` : `
                                <a href="/GM_HMS/view/ipd_billing.php?patient_id=${encodeURIComponent(bed.patient_id)}" class="p-1.5 bg-rose-50 hover:bg-rose-100 text-rose-700 rounded-lg transition-all text-xs font-bold" title="Open Billing">
                                    <i class="fas fa-file-invoice-dollar"></i>
                                </a>
                            `}

                            ${status !== 'Occupied' ? `
                                <button onclick="deleteBedById(${bed.sl_no}, '${bed.bed_number}')" class="p-1.5 bg-red-50 hover:bg-red-100 text-red-600 rounded-lg transition-all" title="Delete Bed">
                                    <i class="fas fa-trash-alt text-xs"></i>
                                </button>
                            ` : ''}
                        </div>
                    </td>
                `;

                tbody.appendChild(tr);
            });
        }

        // ── 5. RENDER VISUAL BED TILES VIEW ──
        function renderVisualBedsView() {
            const container = document.getElementById('visualTilesView');
            container.innerHTML = '';

            if (filteredBeds.length === 0) {
                container.innerHTML = `<div class="bg-white rounded-3xl p-14 text-center border border-slate-200/80 shadow-sm"><p class="text-xs text-slate-500">No beds found.</p></div>`;
                return;
            }

            const wardGroups = {};
            filteredBeds.forEach(bed => {
                const ward = bed.ward_name || 'General Ward';
                const roomKey = bed.room_name || bed.room_number || 'Room';
                if (!wardGroups[ward]) wardGroups[ward] = {};
                if (!wardGroups[ward][roomKey]) wardGroups[ward][roomKey] = [];
                wardGroups[ward][roomKey].push(bed);
            });

            Object.entries(wardGroups).forEach(([wardName, rooms]) => {
                const wardCard = document.createElement('div');
                wardCard.className = 'bg-white rounded-3xl p-6 border border-slate-200/80 shadow-sm space-y-6';

                const allWardBeds = Object.values(rooms).flat();
                const occupiedInWard = allWardBeds.filter(b => b.bed_status === 'Occupied').length;
                const wardPct = allWardBeds.length > 0 ? Math.round((occupiedInWard / allWardBeds.length) * 100) : 0;

                const wardHeader = document.createElement('div');
                wardHeader.className = 'flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-100 pb-4';
                wardHeader.innerHTML = `
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-2xl bg-emerald-50 text-[#1f6b4a] flex items-center justify-center font-black text-base shadow-xs">
                            <i class="fas fa-hospital"></i>
                        </div>
                        <div>
                            <div class="flex items-center gap-2">
                                <h2 class="text-lg font-black text-slate-900">${wardName}</h2>
                                <span class="text-[11px] font-bold text-slate-400">(${allWardBeds.length} Beds)</span>
                            </div>
                            <span class="text-xs font-semibold text-slate-500">${occupiedInWard} Occupied • ${allWardBeds.length - occupiedInWard} Vacant</span>
                        </div>
                    </div>
                    <div class="flex items-center gap-3">
                        <div class="text-right hidden sm:block">
                            <span class="text-[10px] font-extrabold uppercase text-slate-400 block">Ward Occupancy</span>
                            <span class="text-xs font-black ${wardPct > 75 ? 'text-rose-600' : 'text-[#1f6b4a]'}">${wardPct}% Occupied</span>
                        </div>
                        <div class="w-28 h-3 bg-slate-100 rounded-full overflow-hidden">
                            <div class="h-full ${wardPct > 75 ? 'bg-rose-500' : 'bg-[#1f6b4a]'} rounded-full transition-all" style="width: ${wardPct}%;"></div>
                        </div>
                    </div>
                `;
                wardCard.appendChild(wardHeader);

                const roomBaysContainer = document.createElement('div');
                roomBaysContainer.className = 'space-y-6';

                Object.entries(rooms).forEach(([roomName, beds]) => {
                    const roomBay = document.createElement('div');
                    roomBay.className = 'bg-[#fbfaf8] rounded-2xl p-5 border border-slate-200/90 space-y-4';

                    const firstBed = beds[0] || {};
                    const roomCategory = firstBed.room_type || 'General Room';
                    const roomOcc = beds.filter(b => b.bed_status === 'Occupied').length;

                    const roomBayHeader = document.createElement('div');
                    roomBayHeader.className = 'flex items-center justify-between border-b border-slate-200/70 pb-3';
                    roomBayHeader.innerHTML = `
                        <div class="flex items-center gap-2.5">
                            <div class="w-8 h-8 rounded-xl bg-white border border-slate-200 flex items-center justify-center text-xs text-[#1f6b4a] font-bold shadow-xs">
                                <i class="fas fa-door-open"></i>
                            </div>
                            <div>
                                <span class="font-extrabold text-sm text-slate-900">${roomName}</span>
                                <span class="text-[11px] block font-semibold text-slate-500">${roomCategory} • ${firstBed.floor_name || ('Floor ' + firstBed.floor_number)}</span>
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="text-[11px] font-bold px-2.5 py-1 rounded-full ${roomOcc === beds.length ? 'bg-rose-100 text-rose-800' : (roomOcc === 0 ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-700')}">
                                ${roomOcc}/${beds.length} Occupied
                            </span>
                        </div>
                    `;
                    roomBay.appendChild(roomBayHeader);

                    const bedsGrid = document.createElement('div');
                    bedsGrid.className = 'grid grid-cols-1 md:grid-cols-2 2xl:grid-cols-3 gap-4';

                    const sortedBeds = [...beds].sort((a, b) =>
                        String(a.bed_number || '').localeCompare(String(b.bed_number || ''), undefined, {numeric: true})
                    );

                    sortedBeds.forEach(bed => {
                        const status = bed.bed_status || 'Available';
                        const bedTile = document.createElement('div');
                        bedTile.id = `bedCard_${bed.sl_no}`;
                        bedTile.className = `bed-tile rounded-2xl p-4 cursor-pointer flex flex-col justify-between ${
                            status === 'Occupied' ? 'tile-occupied' :
                            (status === 'Available' ? 'tile-available' :
                            (['Maintenance', 'Cleaning'].includes(status) ? 'tile-maintenance' : 'tile-reserved'))
                        }`;
                        bedTile.onclick = (e) => {
                            if (e.target.closest('a') || e.target.closest('button')) return;
                            selectBed(bed);
                        };

                        if (status === 'Occupied') {
                            const patientName = bed.patient_name || (bed.patient_first_name ? `${bed.patient_first_name} ${bed.patient_last_name || ''}` : 'Admitted Patient');
                            const patientPid = bed.patient_id || 'PID-—';
                            const ageSex = bed.patient_age ? `${bed.patient_age}Y / ${bed.patient_sex || 'M'}` : '—';
                            const blood = bed.patient_blood_group ? `<span class="bg-rose-100 text-rose-800 font-extrabold text-[10px] px-1.5 py-0.5 rounded">${bed.patient_blood_group}</span>` : '';

                            bedTile.innerHTML = `
                                <div>
                                    <div class="flex items-center justify-between border-b border-rose-200/70 pb-2.5 mb-3">
                                        <div class="flex items-center gap-2">
                                            <span class="font-black text-sm text-slate-900 tracking-tight flex items-center gap-1.5">
                                                <i class="fas fa-procedures text-rose-600"></i> ${bed.bed_number}
                                            </span>
                                            <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-rose-100 text-rose-800 uppercase">Occupied</span>
                                        </div>
                                        <span class="text-[11px] font-bold text-slate-500">₹${Number(bed.total_bed_amount||0).toLocaleString('en-IN')}/day</span>
                                    </div>
                                    <div class="flex items-start gap-3 mb-3">
                                        <div class="w-10 h-10 rounded-xl bg-rose-100 text-rose-700 flex items-center justify-center font-bold text-base flex-shrink-0">
                                            <i class="fas fa-user-injured"></i>
                                        </div>
                                        <div class="min-w-0 flex-1">
                                            <h4 class="font-black text-sm text-slate-900 truncate" title="${patientName}">${patientName}</h4>
                                            <div class="text-[11px] font-mono text-rose-700 font-bold">${patientPid}</div>
                                            <div class="text-[11px] text-slate-500 font-semibold mt-0.5 flex items-center gap-2">
                                                <span>${ageSex}</span>
                                                ${blood}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="pt-3 border-t border-rose-200/70 flex items-center justify-between gap-2">
                                    <div class="flex items-center gap-1.5">
                                        <a href="/GM_HMS/view/ipd_billing.php?patient_id=${encodeURIComponent(bed.patient_id)}" class="px-2.5 py-1.5 bg-[#1f6b4a] hover:bg-[#154c34] text-white text-[11px] font-bold rounded-xl flex items-center gap-1 shadow-xs transition-all">
                                            <i class="fas fa-file-invoice-dollar"></i> Bill
                                        </a>
                                        <a href="/GM_HMS/nurse_view/k_sheet_view.php?patient_id=${encodeURIComponent(bed.patient_id)}" class="px-2.5 py-1.5 bg-white hover:bg-slate-100 text-slate-700 border border-slate-200 text-[11px] font-bold rounded-xl flex items-center gap-1 transition-all">
                                            <i class="fas fa-clipboard-list"></i> K-Sheet
                                        </a>
                                    </div>
                                    <button onclick="selectBed(${JSON.stringify(bed).replace(/"/g, '&quot;')})" class="p-1.5 text-slate-400 hover:text-slate-800" title="Inspect">
                                        <i class="fas fa-chevron-right text-xs"></i>
                                    </button>
                                </div>
                            `;
                        } else if (status === 'Available') {
                            bedTile.innerHTML = `
                                <div>
                                    <div class="flex items-center justify-between border-b border-emerald-200/70 pb-2.5 mb-3">
                                        <div class="flex items-center gap-2">
                                            <span class="font-black text-sm text-slate-900 tracking-tight flex items-center gap-1.5">
                                                <i class="fas fa-bed text-emerald-600"></i> ${bed.bed_number}
                                            </span>
                                            <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-800 uppercase">Available</span>
                                        </div>
                                        <span class="text-xs font-black text-emerald-800">₹${Number(bed.total_bed_amount||0).toLocaleString('en-IN')}/day</span>
                                    </div>
                                    <div class="flex items-center gap-3 mb-3 bg-white/70 p-3 rounded-xl border border-emerald-100">
                                        <div class="w-10 h-10 rounded-xl bg-emerald-100 text-emerald-700 flex items-center justify-center text-lg flex-shrink-0">
                                            <i class="fas fa-check-circle"></i>
                                        </div>
                                        <div>
                                            <div class="font-extrabold text-xs text-emerald-900">Vacant • Ready</div>
                                            <div class="text-[11px] text-slate-500 font-medium mt-0.5">Oxygen Point • Sanitized</div>
                                        </div>
                                    </div>
                                </div>
                                <div class="pt-3 border-t border-emerald-200/70 flex items-center justify-between gap-2">
                                    <button onclick="selectBed(${JSON.stringify(bed).replace(/"/g, '&quot;')})" class="w-full py-1.5 px-3 bg-emerald-600 hover:bg-emerald-700 text-white text-[11px] font-bold rounded-xl flex items-center justify-center gap-1.5 shadow-xs transition-all">
                                        <i class="fas fa-eye"></i> Inspect Bed
                                    </button>
                                    <button onclick="editBedRecord(${JSON.stringify(bed).replace(/"/g, '&quot;')})" class="p-1.5 bg-white hover:bg-slate-100 text-slate-600 border border-slate-200 rounded-xl" title="Edit Pricing">
                                        <i class="fas fa-pen text-xs"></i>
                                    </button>
                                </div>
                            `;
                        } else {
                            const isCleaning = status === 'Cleaning';
                            bedTile.innerHTML = `
                                <div>
                                    <div class="flex items-center justify-between border-b border-amber-200/70 pb-2.5 mb-3">
                                        <div class="flex items-center gap-2">
                                            <span class="font-black text-sm text-slate-900 tracking-tight flex items-center gap-1.5">
                                                <i class="fas fa-broom text-amber-600"></i> ${bed.bed_number}
                                            </span>
                                            <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-amber-100 text-amber-800 uppercase">${status}</span>
                                        </div>
                                        <span class="text-[11px] font-bold text-slate-500">₹${Number(bed.total_bed_amount||0).toLocaleString('en-IN')}/day</span>
                                    </div>
                                    <div class="flex items-center gap-3 mb-3 bg-white/70 p-3 rounded-xl border border-amber-100">
                                        <div class="w-10 h-10 rounded-xl bg-amber-100 text-amber-700 flex items-center justify-center text-lg flex-shrink-0">
                                            <i class="fas ${isCleaning ? 'fa-spray-can' : 'fa-tools'}"></i>
                                        </div>
                                        <div>
                                            <div class="font-extrabold text-xs text-amber-900">${isCleaning ? 'Sanitization in Progress' : 'Under Maintenance'}</div>
                                            <div class="text-[11px] text-slate-500 font-medium mt-0.5">Not ready for admission</div>
                                        </div>
                                    </div>
                                </div>
                                <div class="pt-3 border-t border-amber-200/70 flex items-center justify-between gap-2">
                                    <button onclick="quickUpdateStatusOnTile(${bed.sl_no}, 'Available')" class="w-full py-1.5 px-3 bg-amber-600 hover:bg-amber-700 text-white text-[11px] font-bold rounded-xl flex items-center justify-center gap-1.5 shadow-xs transition-all">
                                        <i class="fas fa-check"></i> Mark Clean & Ready
                                    </button>
                                </div>
                            `;
                        }

                        bedsGrid.appendChild(bedTile);
                    });

                    roomBay.appendChild(bedsGrid);
                    roomBaysContainer.appendChild(roomBay);
                });

                wardCard.appendChild(roomBaysContainer);
                container.appendChild(wardCard);
            });
        }

        // ── 6. SELECT BED (INSPECTOR) ──
        async function selectBed(bed) {
            currentBed = bed;

            document.querySelectorAll('[id^="bedCard_"]').forEach(el => el.classList.remove('tile-selected'));
            const cardEl = document.getElementById(`bedCard_${bed.sl_no}`);
            if (cardEl) cardEl.classList.add('tile-selected');

            document.getElementById('panelEmptyState').classList.add('hidden');
            document.getElementById('panelDetailsContent').classList.remove('hidden');

            const status = bed.bed_status || 'Available';
            let pillBg = 'bg-emerald-100 text-emerald-800';
            if (status === 'Occupied') pillBg = 'bg-rose-100 text-rose-800';
            else if (['Maintenance', 'Cleaning'].includes(status)) pillBg = 'bg-amber-100 text-amber-800';
            else if (['Reserved', 'Blocked'].includes(status)) pillBg = 'bg-sky-100 text-sky-800';

            document.getElementById('panelBedTitle').textContent = `Bed ${bed.bed_number}`;
            document.getElementById('panelHeaderCategory').textContent = `${bed.room_type || 'General'} Bed`;
            document.getElementById('panelBedIdBadge').textContent = `#${bed.sl_no}`;
            document.getElementById('panelStatusPill').innerHTML = `<span class="px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase ${pillBg}">${status}</span>`;
            document.getElementById('panelLocationBreadcrumb').textContent = `${bed.floor_name || ('Floor ' + bed.floor_number)} > ${bed.ward_name} > Room ${bed.room_name || bed.room_number}`;

            document.getElementById('panelPriceRent').textContent = `₹${Number(bed.amount_per_day||0).toLocaleString('en-IN', {minimumFractionDigits: 2})}`;
            document.getElementById('panelPriceNurse').textContent = `₹${Number(bed.nursig_charge||0).toLocaleString('en-IN', {minimumFractionDigits: 2})}`;
            document.getElementById('panelPriceDr').textContent = `₹${Number(bed.doctor_charge||0).toLocaleString('en-IN', {minimumFractionDigits: 2})}`;
            document.getElementById('panelPriceService').textContent = `₹${Number(bed.service_charge||0).toLocaleString('en-IN', {minimumFractionDigits: 2})}`;
            document.getElementById('panelPriceTotal').textContent = `₹${Number(bed.total_bed_amount||0).toLocaleString('en-IN', {minimumFractionDigits: 2})}`;

            const patientCtx = document.getElementById('panelPatientContext');
            if (status === 'Occupied' && bed.patient_id) {
                patientCtx.classList.remove('hidden');
                document.getElementById('panelPatientPid').textContent = bed.patient_id;
                document.getElementById('panelPatientName').textContent = bed.patient_name || 'Loading patient records...';

                document.getElementById('panelLinkBilling').href = `/GM_HMS/view/ipd_billing.php?patient_id=${encodeURIComponent(bed.patient_id)}`;
                document.getElementById('panelLinkKsheet').href = `/GM_HMS/nurse_view/k_sheet_view.php?patient_id=${encodeURIComponent(bed.patient_id)}`;

                try {
                    const res = await fetch(`/GM_HMS/api/get_patient_details_full.php?patient_id=${encodeURIComponent(bed.patient_id)}`);
                    const data = await res.json();
                    if (data.success && data.data) {
                        const p = data.data;
                        document.getElementById('panelPatientName').textContent = p.full_name || bed.patient_name || 'Admitted Patient';
                        document.getElementById('panelPatientDemographics').innerHTML = `<span>${p.age_years || bed.patient_age || '?'}Y / ${p.gender || bed.patient_sex || '?'}</span> <span class="text-rose-600 bg-white px-1.5 py-0.5 rounded font-extrabold border border-rose-200">${p.blood_group || bed.patient_blood_group || 'N/A'}</span>`;
                        document.getElementById('panelPatientPhone').textContent = p.phone_number || bed.patient_phone || 'N/A';
                        document.getElementById('panelPatientAdmitDate').textContent = p.admission_date ? new Date(p.admission_date).toLocaleDateString('en-GB') : '—';
                    }
                } catch (e) {
                    console.error(e);
                }
            } else {
                patientCtx.classList.add('hidden');
            }

            if (window.innerWidth < 1280) {
                document.getElementById('bedInspectorPanel').scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            }
        }

        // ── 7. QUICK STATUS UPDATER ──
        async function quickUpdateStatus(newStatus) {
            if (!currentBed) return;
            await executeStatusUpdate(currentBed.sl_no, currentBed.bed_number, newStatus, currentBed.bed_status === 'Occupied');
        }

        async function quickUpdateStatusOnTile(sl_no, newStatus) {
            const bed = allBeds.find(b => b.sl_no == sl_no);
            if (!bed) return;
            await executeStatusUpdate(sl_no, bed.bed_number, newStatus, bed.bed_status === 'Occupied');
        }

        async function executeStatusUpdate(sl_no, bedNum, newStatus, isOccupied) {
            if (isOccupied && newStatus === 'Available') {
                if (!confirm(`Bed ${bedNum} currently has an occupied patient. Are you sure you want to mark it Available without formal discharge?`)) {
                    return;
                }
            }

            try {
                const formData = new FormData();
                formData.append('action', 'update_status');
                formData.append('sl_no', sl_no);
                formData.append('bed_status', newStatus);

                const res = await fetch('/GM_HMS/api/save_bed.php', { method: 'POST', body: formData });
                const json = await res.json();

                if (json.status === 'success') {
                    showToast(`Bed ${bedNum} is now ${newStatus}`, 'success');
                    fetchBeds();
                } else {
                    showToast(json.message || 'Failed to change status', 'error');
                }
            } catch (e) {
                showToast('Network error updating status', 'error');
            }
        }

        // ── 8. BED MODAL (ADD / EDIT) ──
        function openBedModal(mode = 'create', bed = null) {
            document.getElementById('bedForm').reset();
            document.getElementById('formAction').value = mode;

            const floors = [...new Set(allBeds.map(b => b.floor_name))].filter(Boolean);
            const floorNums = [...new Set(allBeds.map(b => b.floor_number))].filter(Boolean);
            const wards = [...new Set(allBeds.map(b => b.ward_name))].filter(Boolean);

            const floorNumSel = document.getElementById('modalFloorNum');
            floorNumSel.innerHTML = '<option value="" disabled selected>Select...</option>' + 
                floorNums.map(n => `<option value="${n}">${n}</option>`).join('') + 
                '<option value="ADD_NEW_CUSTOM">+ Add Custom...</option>';

            const floorNameSel = document.getElementById('modalFloorName');
            floorNameSel.innerHTML = '<option value="" disabled selected>Select Floor...</option>' + 
                floors.map(f => `<option value="${f}">${f}</option>`).join('') + 
                '<option value="ADD_NEW_CUSTOM">+ Add Custom...</option>';

            const wardSel = document.getElementById('modalWardName');
            wardSel.innerHTML = '<option value="" disabled selected>Select Ward...</option>' + 
                wards.map(w => `<option value="${w}">${w}</option>`).join('') + 
                '<option value="ADD_NEW_CUSTOM">+ Add Custom...</option>';

            if (mode === 'edit' && bed) {
                document.getElementById('modalTitle').textContent = `Edit Bed ${bed.bed_number}`;
                document.getElementById('modalSubtitle').textContent = `Modifying room specs & pricing for record #${bed.sl_no}`;
                document.getElementById('formSlNo').value = bed.sl_no;
                document.getElementById('batchBedOption').classList.add('hidden');

                floorNumSel.value = bed.floor_number || '';
                floorNameSel.value = bed.floor_name || '';
                wardSel.value = bed.ward_name || '';
                document.getElementById('modalRoomType').value = bed.room_type || 'General Ward';
                document.getElementById('modalRoomNumber').value = bed.room_number || '';
                document.getElementById('modalRoomName').value = bed.room_name || '';
                document.getElementById('modalBedNumber').value = bed.bed_number || '';
                document.getElementById('formRent').value = bed.amount_per_day || 0;
                document.getElementById('formNurse').value = bed.nursig_charge || 0;
                document.getElementById('formDoctor').value = bed.doctor_charge || 0;
                document.getElementById('formService').value = bed.service_charge || 0;
                document.getElementById('modalBedStatus').value = bed.bed_status || 'Available';
                calcModalTotal();
            } else {
                document.getElementById('modalTitle').textContent = 'Add Room & Bed';
                document.getElementById('modalSubtitle').textContent = 'Configure location, classification, room category and daily billing rates.';
                document.getElementById('formSlNo').value = '';
                document.getElementById('batchBedOption').classList.remove('hidden');
                calcModalTotal();
            }

            document.getElementById('bedModal').classList.add('active');
        }

        function closeBedModal() {
            document.getElementById('bedModal').classList.remove('active');
            document.querySelectorAll('[id$="Custom"]').forEach(el => el.classList.add('hidden'));
        }

        function editBedRecord(bed) {
            openBedModal('edit', bed);
        }

        function editCurrentBed() {
            if (currentBed) openBedModal('edit', currentBed);
        }

        function applyRatePreset(rent, nurse, dr, service, type) {
            document.getElementById('formRent').value = rent;
            document.getElementById('formNurse').value = nurse;
            document.getElementById('formDoctor').value = dr;
            document.getElementById('formService').value = service;
            if (type) {
                const typeSelect = document.getElementById('modalRoomType');
                if ([...typeSelect.options].some(o => o.value === type)) {
                    typeSelect.value = type;
                }
            }
            calcModalTotal();
        }

        function calcModalTotal() {
            const rent = parseFloat(document.getElementById('formRent').value) || 0;
            const nurse = parseFloat(document.getElementById('formNurse').value) || 0;
            const dr = parseFloat(document.getElementById('formDoctor').value) || 0;
            const service = parseFloat(document.getElementById('formService').value) || 0;
            const total = rent + nurse + dr + service;
            document.getElementById('formTotal').value = `₹${total.toFixed(2)}`;
        }

        function checkCustomOption(selectEl, customInputId) {
            const customInput = document.getElementById(customInputId);
            if (selectEl.value === 'ADD_NEW_CUSTOM') {
                customInput.classList.remove('hidden');
                customInput.focus();
            } else {
                customInput.classList.add('hidden');
            }
        }

        async function submitBedForm() {
            const btn = document.getElementById('btnSubmitModal');
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';

            const form = document.getElementById('bedForm');
            const formData = new FormData(form);

            try {
                const res = await fetch('/GM_HMS/api/save_bed.php', {
                    method: 'POST',
                    body: formData
                });
                const text = await res.text();
                let json;
                try {
                    json = JSON.parse(text);
                } catch(pe) {
                    showToast('Server error: ' + text.substring(0, 120), 'error');
                    return;
                }

                if (json.status === 'success') {
                    showToast(json.message || 'Bed saved successfully', 'success');
                    closeBedModal();
                    fetchBeds();
                } else {
                    showToast(json.message || 'Error saving bed', 'error');
                }
            } catch (e) {
                showToast('Network error: ' + e.message, 'error');
            } finally {
                btn.disabled = false;
                btn.innerHTML = 'Save Bed';
            }
        }

        async function deleteCurrentBed() {
            if (!currentBed) return;
            if (currentBed.bed_status === 'Occupied') {
                showToast('Cannot delete an occupied bed.', 'warning');
                return;
            }
            await deleteBedById(currentBed.sl_no, currentBed.bed_number);
        }

        async function deleteBedById(sl_no, bedNumber) {
            if (!confirm(`Are you sure you want to permanently delete Bed ${bedNumber}?`)) {
                return;
            }

            try {
                const formData = new FormData();
                formData.append('action', 'delete');
                formData.append('sl_no', sl_no);

                const res = await fetch('/GM_HMS/api/save_bed.php', { method: 'POST', body: formData });
                const json = await res.json();

                if (json.status === 'success') {
                    showToast(`Bed ${bedNumber} deleted successfully`, 'success');
                    document.getElementById('panelEmptyState').classList.remove('hidden');
                    document.getElementById('panelDetailsContent').classList.add('hidden');
                    currentBed = null;
                    fetchBeds();
                } else {
                    showToast(json.message || 'Failed to delete bed', 'error');
                }
            } catch (e) {
                showToast('Network error deleting bed', 'error');
            }
        }

        // ── 9. TOAST NOTIFICATIONS ──
        function showToast(msg, type = 'info') {
            const container = document.getElementById('toastContainer');
            const toast = document.createElement('div');
            
            const colors = {
                success: 'bg-emerald-800 text-white border-emerald-700',
                error: 'bg-rose-800 text-white border-rose-700',
                warning: 'bg-amber-800 text-white border-amber-700',
                info: 'bg-slate-900 text-white border-slate-800'
            };
            const icons = {
                success: 'fa-check-circle text-emerald-300',
                error: 'fa-exclamation-circle text-rose-300',
                warning: 'fa-exclamation-triangle text-amber-300',
                info: 'fa-info-circle text-sky-300'
            };

            toast.className = `toast px-4 py-3 rounded-2xl shadow-xl border text-xs font-bold flex items-center gap-2.5 ${colors[type] || colors.info}`;
            toast.innerHTML = `<i class="fas ${icons[type] || icons.info}"></i> <span>${msg}</span>`;

            container.appendChild(toast);
            setTimeout(() => {
                toast.style.opacity = '0';
                toast.style.transform = 'translateY(10px)';
                setTimeout(() => toast.remove(), 300);
            }, 3500);
        }
    </script>
</body>
</html>
