<?php
session_start();
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['Receptionist', 'admin', 'Admin'])) {
    header("Location: ../../../../receptionist_login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IPD Bed Allocation – GM HMS</title>

    <!-- External CSS -->
    <link rel="stylesheet" href="/GM_HMS/assets/css/gm-theme.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">
    <link rel="stylesheet" href="../../../assets/css/reception_dashboard.css">
    <link rel="stylesheet" href="../../public/assets/css/ipd_main.css">

    <style>
        /* ============================================================
           MASTER VARIABLES – Strict Color Theme
        ============================================================ */
        :root {
            --green:   #1F6B4A;
            --green-d: #185a3e;
            --green-l: #e8f4ef;
            --green-xl:#f0f8f4;
            --bg:      #F3EFE6;
            --card:    #FFFFFF;
            --border:  #E8E8E8;
            --text:    #2F2F2F;
            --text-lt: #777777;
            --red:     #C0392B;
            --red-l:   #fdecea;
            --amber:   #D68910;
            --amber-l: #fef9ec;
            --grey:    #8e9aaf;
            --grey-l:  #f2f4f7;
            --shadow:  0 1px 4px rgba(0,0,0,0.07);
            --shadow-hover: 0 4px 16px rgba(0,0,0,0.11);
            --radius:  12px;
        }

        /* ============================================================
           RESET & BASE
        ============================================================ */
        *, *::before, *::after { box-sizing: border-box; }

        body {
            font-family: 'Inter', sans-serif;
            background: var(--bg) !important;
            color: var(--text);
            font-size: 14px;
        }

        /* ============================================================
           MAIN LAYOUT – fits inside reception-layout wrapper
        ============================================================ */
        .ipd-wrapper {
            background: var(--bg);
            min-height: calc(100vh - 68px);
            display: flex;
            flex-direction: column;
        }

        /* ============================================================
           FILTER BAR
        ============================================================ */
        .filter-bar {
            background: var(--card);
            border-bottom: 1px solid var(--border);
            padding: 14px 24px;
            display: flex;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
        }

        .filter-bar label {
            font-size: 12px;
            font-weight: 600;
            color: var(--text-lt);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            white-space: nowrap;
            margin-bottom: 0;
        }

        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 3px;
            min-width: 140px;
        }

        .filter-select,
        .filter-input {
            padding: 7px 12px;
            border: 1.5px solid var(--border);
            border-radius: 8px;
            font-size: 13px;
            font-family: inherit;
            color: var(--text);
            background: var(--card);
            outline: none;
            transition: border-color 0.18s;
            height: 36px;
        }

        .filter-select:focus,
        .filter-input:focus {
            border-color: var(--green);
        }

        .filter-search {
            position: relative;
            flex: 1;
            min-width: 180px;
        }

        .filter-search .fa-search {
            position: absolute;
            left: 10px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-lt);
            font-size: 12px;
        }

        .filter-search input {
            padding-left: 30px;
            width: 100%;
        }

        .filter-divider {
            width: 1px;
            height: 32px;
            background: var(--border);
            flex-shrink: 0;
        }

        /* ============================================================
           STAT CARDS ROW
        ============================================================ */
        .stat-row {
            display: flex;
            gap: 16px;
            padding: 16px 24px 0;
            flex-wrap: wrap;
        }

        .stat-item {
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 14px 20px;
            display: flex;
            align-items: center;
            gap: 14px;
            flex: 1;
            min-width: 150px;
            box-shadow: var(--shadow);
            transition: box-shadow 0.18s;
        }

        .stat-item:hover { box-shadow: var(--shadow-hover); }

        .stat-item .si-icon {
            width: 40px; height: 40px;
            border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            font-size: 16px;
            flex-shrink: 0;
        }

        .stat-item .si-num {
            font-size: 26px;
            font-weight: 800;
            line-height: 1;
            color: var(--text);
        }

        .stat-item .si-label {
            font-size: 12px;
            color: var(--text-lt);
            font-weight: 500;
            margin-top: 2px;
        }

        .si-total  .si-icon { background: var(--green-l); color: var(--green); }
        .si-avail  .si-icon { background: #e8f8f1; color: #1a9a5f; }
        .si-occ    .si-icon { background: var(--red-l); color: var(--red); }
        .si-maint  .si-icon { background: var(--amber-l); color: var(--amber); }

        /* ============================================================
           MAIN CONTENT AREA (Sidebar + Dynamic View)
        ============================================================ */
        .ipd-body {
            display: flex;
            flex: 1;
            overflow: hidden;
            margin-top: 16px;
            gap: 0;
        }

        /* ── Floor Sidebar ── */
        .floor-nav {
            width: 240px;
            min-width: 240px;
            background: var(--card);
            border-right: 1px solid var(--border);
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }

        .floor-nav-header {
            padding: 14px 16px 10px;
            border-bottom: 1px solid var(--border);
        }

        .floor-nav-header h3 {
            font-size: 13px;
            font-weight: 700;
            color: var(--text-lt);
            text-transform: uppercase;
            letter-spacing: 0.6px;
            margin: 0;
        }

        .floor-nav-list {
            flex: 1;
            overflow-y: auto;
            padding: 8px;
        }

        .floor-nav-list::-webkit-scrollbar { width: 3px; }
        .floor-nav-list::-webkit-scrollbar-thumb { background: var(--border); border-radius: 10px; }

        .floor-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 12px;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.18s;
            border: 1.5px solid transparent;
            margin-bottom: 3px;
        }

        .floor-item:hover {
            background: var(--green-xl);
            border-color: var(--green-l);
        }

        .floor-item.active {
            background: var(--green);
            border-color: var(--green);
        }

        .floor-item .icon {
            width: 30px; height: 30px;
            border-radius: 7px;
            background: var(--green-l);
            display: flex; align-items: center; justify-content: center;
            font-size: 13px;
            color: var(--green);
            flex-shrink: 0;
            transition: all 0.18s;
        }

        .floor-item.active .icon {
            background: rgba(255,255,255,0.2);
            color: #fff;
        }

        .floor-item-content { flex: 1; min-width: 0; }

        .floor-item-title {
            font-size: 13px;
            font-weight: 600;
            color: var(--text);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            transition: color 0.18s;
        }

        .floor-item-meta {
            font-size: 11px;
            color: var(--text-lt);
            margin-top: 1px;
        }

        .floor-item.active .floor-item-title,
        .floor-item.active .floor-item-meta { color: rgba(255,255,255,0.9); }

        .floor-item .arrow {
            font-size: 10px;
            color: var(--border);
            transition: all 0.18s;
            flex-shrink: 0;
        }
        .floor-item.active .arrow { color: rgba(255,255,255,0.6); }
        .floor-item:hover .arrow { color: var(--green); }

        /* ── Content Panel ── */
        .content-panel {
            flex: 1;
            display: flex;
            flex-direction: column;
            overflow: hidden;
            background: var(--bg);
        }

        /* ── Breadcrumb ── */
        .breadcrumb-bar {
            padding: 10px 24px;
            background: var(--card);
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 13px;
            font-weight: 500;
            color: var(--text-lt);
            flex-wrap: wrap;
        }

        .breadcrumb-item {
            display: flex;
            align-items: center;
            gap: 5px;
            cursor: pointer;
            padding: 3px 8px;
            border-radius: 6px;
            transition: all 0.15s;
            white-space: nowrap;
        }

        .breadcrumb-item:hover { background: var(--green-l); color: var(--green); }
        .breadcrumb-item.active { color: var(--green); font-weight: 600; pointer-events: none; }
        .breadcrumb-separator { font-size: 9px; color: var(--border); }

        /* ── Dynamic View ── */
        .dynamic-view {
            flex: 1;
            overflow-y: auto;
            padding: 20px 24px;
        }

        .dynamic-view::-webkit-scrollbar { width: 4px; }
        .dynamic-view::-webkit-scrollbar-thumb { background: var(--border); border-radius: 10px; }

        /* ── Section label ── */
        .view-section-title {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 16px;
        }

        .view-section-title h2 {
            font-size: 18px;
            font-weight: 700;
            color: var(--text);
            margin: 0;
        }

        .view-section-title .legend {
            display: flex;
            align-items: center;
            gap: 14px;
            font-size: 12px;
            color: var(--text-lt);
            font-weight: 500;
        }

        .legend-dot {
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .legend-dot::before {
            content: '';
            display: inline-block;
            width: 10px; height: 10px;
            border-radius: 3px;
        }

        .legend-dot.l-avail::before { background: var(--green); }
        .legend-dot.l-occ::before   { background: var(--red); }
        .legend-dot.l-maint::before { background: var(--amber); }
        .legend-dot.l-block::before { background: var(--grey); }

        /* ============================================================
           NAVIGATION CARDS (Ward / Room Type / Room)
        ============================================================ */
        .nav-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
            gap: 16px;
        }

        .nav-card {
            background: var(--card);
            border: 1.5px solid var(--border);
            border-radius: var(--radius);
            padding: 20px;
            cursor: pointer;
            transition: all 0.2s;
            position: relative;
            overflow: hidden;
            box-shadow: var(--shadow);
        }

        /* Green left border accent – unique per card type */
        .nav-card::before {
            content: '';
            position: absolute;
            left: 0; top: 0; bottom: 0;
            width: 4px;
            background: var(--green);
            border-radius: 12px 0 0 12px;
            opacity: 0;
            transition: opacity 0.2s;
        }

        .nav-card:hover {
            border-color: var(--green);
            box-shadow: var(--shadow-hover);
            transform: translateY(-2px);
        }

        .nav-card:hover::before { opacity: 1; }

        .nav-card-icon {
            width: 40px; height: 40px;
            border-radius: 9px;
            background: var(--green-l);
            display: flex; align-items: center; justify-content: center;
            color: var(--green);
            font-size: 16px;
            margin-bottom: 12px;
        }

        .nav-card-title {
            font-size: 15px;
            font-weight: 700;
            color: var(--text);
            margin-bottom: 4px;
        }

        .nav-card-sub {
            font-size: 12px;
            color: var(--text-lt);
            margin-bottom: 14px;
        }

        .nav-card-stats {
            display: flex;
            gap: 10px;
        }

        .nav-stat {
            flex: 1;
            background: var(--bg);
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: 8px;
            text-align: center;
        }

        .nav-stat-num {
            font-size: 20px;
            font-weight: 800;
            line-height: 1;
        }

        .nav-stat-num.occ { color: var(--red); }
        .nav-stat-num.ava { color: var(--green); }
        .nav-stat-num.tot { color: var(--text); }

        .nav-stat-label {
            font-size: 10px;
            font-weight: 500;
            color: var(--text-lt);
            text-transform: uppercase;
            letter-spacing: 0.4px;
            margin-top: 3px;
        }

        .nav-card-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 12px;
            font-size: 12px;
            color: var(--text-lt);
            font-weight: 500;
        }

        .nav-prog-bar {
            width: 100%;
            height: 4px;
            background: var(--bg);
            border-radius: 10px;
            overflow: hidden;
            margin-top: 8px;
        }

        .nav-prog-fill {
            height: 100%;
            background: var(--green);
            border-radius: 10px;
            transition: width 0.5s ease;
        }

        .nav-prog-fill.full { background: var(--red); }

        /* ============================================================
           BED GRID
        ============================================================ */
        .bed-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 16px;
        }

        /* ── Individual Bed Card ── */
        .bed-card {
            background: var(--card);
            border: 1.5px solid var(--border);
            border-radius: var(--radius);
            padding: 0;
            overflow: hidden;
            box-shadow: var(--shadow);
            transition: all 0.2s;
            cursor: default;
            display: flex;
            flex-direction: column;
            position: relative;
        }

        .bed-card:hover {
            box-shadow: var(--shadow-hover);
            transform: translateY(-2px);
        }

        /* Colored left border per status */
        .bed-card::before {
            content: '';
            position: absolute;
            left: 0; top: 0; bottom: 0;
            width: 4px;
        }

        .bed-card.status-available::before   { background: var(--green); }
        .bed-card.status-occupied::before    { background: var(--red); }
        .bed-card.status-blocked::before     { background: var(--grey); }
        .bed-card.status-maintenance::before { background: var(--amber); }

        .bed-card:hover.status-available  { border-color: var(--green); }
        .bed-card:hover.status-occupied   { border-color: var(--red); }
        .bed-card:hover.status-blocked    { border-color: var(--grey); }
        .bed-card:hover.status-maintenance{ border-color: var(--amber); }

        .bed-card-inner {
            padding: 16px 16px 12px 20px;
            flex: 1;
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .bed-card-head {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
        }

        .bed-number {
            font-size: 16px;
            font-weight: 800;
            color: var(--text);
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .bed-number i {
            font-size: 14px;
            color: var(--green);
        }

        .bed-status-badge {
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 3px 8px;
            border-radius: 50px;
            white-space: nowrap;
        }

        .status-available   .bed-status-badge { background: #e6f7ef; color: #1a7a4a; }
        .status-occupied    .bed-status-badge { background: var(--red-l); color: var(--red); }
        .status-blocked     .bed-status-badge { background: var(--grey-l); color: #555; }
        .status-maintenance .bed-status-badge { background: var(--amber-l); color: var(--amber); }

        /* Patient info inside occupied card */
        .bed-patient {
            background: #fafafa;
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: 9px 10px;
            font-size: 12px;
            color: var(--text-lt);
            line-height: 1.7;
        }

        .bed-patient-name {
            font-size: 13px;
            font-weight: 700;
            color: var(--text);
            margin-bottom: 2px;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        /* Available / other status center display */
        .bed-status-center {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 12px 0 6px;
            gap: 5px;
            text-align: center;
        }

        .bed-status-center i {
            font-size: 22px;
        }

        .bed-status-center span {
            font-size: 11px;
            font-weight: 600;
            letter-spacing: 0.3px;
        }

        .status-available   .bed-status-center i,
        .status-available   .bed-status-center span { color: var(--green); }
        .status-maintenance .bed-status-center i,
        .status-maintenance .bed-status-center span { color: var(--amber); }
        .status-blocked     .bed-status-center i,
        .status-blocked     .bed-status-center span { color: var(--grey); }

        /* Action button at bottom of bed card */
        .bed-card-action {
            padding: 0 12px 12px 16px;
        }

        .btn-bed {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            width: 100%;
            padding: 7px 10px;
            border-radius: 7px;
            border: 1.5px solid transparent;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.18s;
            font-family: inherit;
        }

        .btn-bed-release {
            background: var(--red-l);
            color: var(--red);
            border-color: #f5c6c4;
        }

        .btn-bed-release:hover {
            background: var(--red);
            color: #fff;
            border-color: var(--red);
        }

        .btn-bed-manage {
            background: var(--green-l);
            color: var(--green);
            border-color: #c3e0d4;
        }

        .btn-bed-manage:hover {
            background: var(--green);
            color: #fff;
            border-color: var(--green);
        }

        /* ============================================================
           EMPTY STATE
        ============================================================ */
        .empty-state {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 60px 24px;
            text-align: center;
            gap: 10px;
            grid-column: 1 / -1;
        }

        .empty-icon {
            width: 64px; height: 64px;
            border-radius: 50%;
            background: var(--green-l);
            display: flex; align-items: center; justify-content: center;
            font-size: 24px;
            color: var(--green);
            margin-bottom: 6px;
        }

        .empty-state h3 { font-size: 17px; font-weight: 700; color: var(--text); margin: 0; }
        .empty-state p  { font-size: 13px; color: var(--text-lt); margin: 0; max-width: 280px; }

        /* ============================================================
           SKELETON LOADERS
        ============================================================ */
        .skeleton {
            background: linear-gradient(90deg, #eee 25%, #e0e0e0 50%, #eee 75%);
            background-size: 200% 100%;
            animation: shimmer 1.4s ease-in-out infinite;
            border-radius: 8px;
        }

        @keyframes shimmer {
            0%   { background-position: 200% 0; }
            100% { background-position: -200% 0; }
        }

        /* ============================================================
           PROGRESS BAR
        ============================================================ */
        .prog-bar-wrap {
            width: 100%;
            height: 4px;
            background: var(--bg);
            border-radius: 10px;
            overflow: hidden;
            margin-top: 10px;
        }

        .prog-bar-fill {
            height: 100%;
            background: var(--green);
            border-radius: 10px;
            transition: width 0.5s ease;
        }

        .prog-bar-fill.danger { background: var(--red); }

        /* ============================================================
           CUSTOM MODAL
        ============================================================ */
        .hms-modal-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.45);
            z-index: 99999;
            align-items: center;
            justify-content: center;
        }

        .hms-modal-overlay.open {
            display: flex;
        }

        .hms-modal {
            background: #fff;
            border-radius: 14px;
            padding: 32px 28px 24px;
            width: 100%;
            max-width: 420px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.18);
            position: relative;
            animation: modalIn 0.22s cubic-bezier(0.4,0,0.2,1) both;
            border-top: 4px solid var(--green);
        }

        @keyframes modalIn {
            from { opacity: 0; transform: scale(0.93) translateY(-10px); }
            to   { opacity: 1; transform: scale(1) translateY(0); }
        }

        .hms-modal-icon {
            width: 48px; height: 48px;
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-size: 20px;
            margin: 0 auto 16px;
        }

        .hms-modal-icon.danger { background: #fdecea; color: var(--red); }
        .hms-modal-icon.info   { background: var(--green-l); color: var(--green); }

        .hms-modal-title {
            font-size: 17px;
            font-weight: 700;
            color: var(--text);
            text-align: center;
            margin-bottom: 6px;
        }

        .hms-modal-body {
            font-size: 13px;
            color: var(--text-lt);
            text-align: center;
            margin-bottom: 20px;
            line-height: 1.6;
        }

        .hms-modal-select-row {
            display: flex;
            flex-direction: column;
            gap: 6px;
            margin-bottom: 20px;
        }

        .hms-modal-select-row label {
            font-size: 12px;
            font-weight: 600;
            color: var(--text-lt);
            text-transform: uppercase;
            letter-spacing: 0.4px;
        }

        .hms-modal-select {
            padding: 9px 14px;
            border: 1.5px solid var(--border);
            border-radius: 8px;
            font-size: 14px;
            font-family: 'Inter', sans-serif;
            color: var(--text);
            background: #fff;
            outline: none;
            transition: border-color 0.18s;
            cursor: pointer;
        }

        .hms-modal-select:focus { border-color: var(--green); }

        .hms-modal-actions {
            display: flex;
            gap: 10px;
            justify-content: center;
        }

        .hms-modal-btn {
            padding: 9px 24px;
            border-radius: 8px;
            border: 1.5px solid transparent;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.18s;
            font-family: 'Inter', sans-serif;
            min-width: 100px;
        }

        .hms-modal-btn.cancel {
            background: #f5f5f5;
            color: var(--text-lt);
            border-color: var(--border);
        }
        .hms-modal-btn.cancel:hover {
            background: #e8e8e8;
            color: var(--text);
        }

        .hms-modal-btn.confirm-green {
            background: var(--green);
            color: #fff;
            border-color: var(--green);
        }
        .hms-modal-btn.confirm-green:hover {
            background: var(--green-d);
        }

        .hms-modal-btn.confirm-red {
            background: var(--red);
            color: #fff;
            border-color: var(--red);
        }
        .hms-modal-btn.confirm-red:hover {
            background: #a93226;
        }

        /* ============================================================
           RESPONSIVE
        ============================================================ */
        @media (max-width: 1100px) {
            .stat-row { gap: 10px; }
            .stat-item { min-width: 130px; }
        }

        @media (max-width: 900px) {
            .ipd-body { flex-direction: column; height: auto; }
            .floor-nav { width: 100%; min-width: 0; border-right: none; border-bottom: 1px solid var(--border); height: auto; }
            .floor-nav-list { display: flex; flex-direction: row; overflow-x: auto; flex-wrap: nowrap; padding: 8px; gap: 6px; }
            .floor-item { min-width: 160px; margin-bottom: 0; }
            .bed-grid { grid-template-columns: repeat(auto-fill, minmax(170px, 1fr)); }
            .stat-item .si-num { font-size: 22px; }
        }

        @media (max-width: 600px) {
            .filter-bar { gap: 8px; }
            .filter-group { min-width: 120px; }
            .stat-row { flex-direction: row; }
            .stat-item { padding: 10px 12px; gap: 10px; }
            .bed-grid { grid-template-columns: repeat(2, 1fr); }
            .nav-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
<div class="reception-layout">
    <!-- Sidebar -->
    <?php include '../../../includes/reception_sidebar.php'; ?>

    <!-- Main Content -->
    <div class="reception-main-content">
        <!-- Top Navbar -->
        <?php
        $pageTitle = 'IPD Bed Allocation';
        include '../../../includes/reception_navbar.php';
        ?>

        <div class="reception-content" style="padding:0; overflow:hidden;">
            <div class="ipd-wrapper">

                <!-- ── FILTER BAR ── -->
                <div class="filter-bar">
                    <div class="filter-group">
                        <label>Floor</label>
                        <select class="filter-select" id="floorFilter" onchange="onFilterChange()">
                            <option value="">All Floors</option>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label>Ward</label>
                        <select class="filter-select" id="wardFilter" onchange="onFilterChange()">
                            <option value="">All Wards</option>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label>Room Type</label>
                        <select class="filter-select" id="roomTypeFilter" onchange="onFilterChange()">
                            <option value="">All Types</option>
                        </select>
                    </div>
                    <div class="filter-divider"></div>
                    <div class="filter-group filter-search">
                        <label>Search Bed</label>
                        <div style="position:relative;">
                            <i class="fas fa-search" style="position:absolute;left:10px;top:50%;transform:translateY(-50%);color:var(--text-lt);font-size:12px;pointer-events:none;"></i>
                            <input type="text" class="filter-input filter-select" id="globalSearch"
                                   placeholder="Search bed, patient..."
                                   onkeyup="filterView()"
                                   style="padding-left:30px; min-width:180px;">
                        </div>
                    </div>
                    <div class="filter-group">
                        <label>Status</label>
                        <select class="filter-select" id="statusFilter" onchange="filterView()">
                            <option value="">All Status</option>
                            <option value="Available">Available</option>
                            <option value="Occupied">Occupied</option>
                            <option value="Blocked">Blocked</option>
                            <option value="Maintenance">Maintenance</option>
                        </select>
                    </div>
                    
                    <div style="flex-grow: 1;"></div>
                    <!-- Navigation Buttons -->
                    <div class="d-flex gap-2" style="margin-top: 20px;">
                        <a href="/GM_HMS/reception_view/ipd_management/public/index.php" class="btn btn-light border shadow-sm px-3" style="font-weight: 600; border-radius: 8px; color: #475569; font-size: 12px; height: 36px; display: flex; align-items: center;" title="Back to IPD Dashboard">
                            <i class="fas fa-chart-pie me-2"></i> IPD Dashboard
                        </a>
                        <a href="/GM_HMS/reception_view/index.php" class="btn btn-primary shadow-sm px-3" style="font-weight: 600; border-radius: 8px; background: linear-gradient(135deg, #1f6b4a 0%, #144d34 100%); border: none; font-size: 12px; height: 36px; display: flex; align-items: center;" title="Back to Main Dashboard">
                            <i class="fas fa-home me-2"></i> Main Dashboard
                        </a>
                    </div>
                </div>

                <!-- ── STAT CARDS ── -->
                <div class="stat-row" id="statRow">
                    <div class="stat-item si-total">
                        <div class="si-icon"><i class="fas fa-bed"></i></div>
                        <div>
                            <div class="si-num skeleton" style="width:40px;height:28px;border-radius:6px;">&nbsp;</div>
                            <div class="si-label">Total Beds</div>
                        </div>
                    </div>
                    <div class="stat-item si-avail">
                        <div class="si-icon"><i class="fas fa-check-circle"></i></div>
                        <div>
                            <div class="si-num skeleton" style="width:40px;height:28px;border-radius:6px;">&nbsp;</div>
                            <div class="si-label">Available</div>
                        </div>
                    </div>
                    <div class="stat-item si-occ">
                        <div class="si-icon"><i class="fas fa-user-injured"></i></div>
                        <div>
                            <div class="si-num skeleton" style="width:40px;height:28px;border-radius:6px;">&nbsp;</div>
                            <div class="si-label">Occupied</div>
                        </div>
                    </div>
                    <div class="stat-item si-maint">
                        <div class="si-icon"><i class="fas fa-tools"></i></div>
                        <div>
                            <div class="si-num skeleton" style="width:40px;height:28px;border-radius:6px;">&nbsp;</div>
                            <div class="si-label">Maintenance</div>
                        </div>
                    </div>
                </div>

                <!-- ── MAIN BODY ── -->
                <div class="ipd-body">

                    <!-- Floor Navigation -->
                    <div class="floor-nav">
                        <div class="floor-nav-header">
                            <h3><i class="fas fa-building" style="color:var(--green);margin-right:6px;"></i>Floors</h3>
                        </div>
                        <div class="floor-nav-list" id="floorList">
                            <div class="skeleton" style="height:48px;border-radius:8px;margin-bottom:4px;"></div>
                            <div class="skeleton" style="height:48px;border-radius:8px;margin-bottom:4px;"></div>
                            <div class="skeleton" style="height:48px;border-radius:8px;"></div>
                        </div>
                    </div>

                    <!-- Content Panel -->
                    <div class="content-panel">

                        <!-- Breadcrumb -->
                        <div class="breadcrumb-bar" id="breadcrumbBar">
                            <div class="breadcrumb-item active">
                                <i class="fas fa-hospital" style="font-size:12px;"></i> Hospital
                            </div>
                        </div>

                        <!-- Dynamic View -->
                        <div class="dynamic-view" id="dynamicView">
                            <div class="empty-state">
                                <div class="empty-icon"><i class="fas fa-bed"></i></div>
                                <h3>Select a Floor</h3>
                                <p>Choose a floor from the panel on the left to view wards and manage bed allocation.</p>
                            </div>
                        </div>

                    </div><!-- /content-panel -->
                </div><!-- /ipd-body -->
            </div><!-- /ipd-wrapper -->
        </div>
    </div><!-- /reception-main-content -->
</div><!-- /reception-layout -->

<!-- Scripts -->
<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/toastify-js"></script>
<script src="../../public/assets/js/ipd_main.js"></script>

<script>
    /* ============================================================
       DATA STORE & STATE
    ============================================================ */
    let hospitalData = {
        floors: {},
        stats: { totalFloors:0, totalWards:0, totalRoomTypes:0, totalRooms:0, totalBeds:0, occupied:0, available:0, maintenance:0, blocked:0 }
    };

    let currentView = { level: 'hospital', floor: null, ward: null, roomType: null, room: null };

    /* ============================================================
       INIT
    ============================================================ */
    $(document).ready(function () {
        loadBedData();
        setInterval(() => loadBedData(true), 30000);
    });

    function loadBedData(isRefresh = false) {
        IPD.ajax('beds', 'GET')
            .then(response => {
                const beds = response.data.beds || [];
                buildHierarchy(beds);
                if (!isRefresh) {
                    renderFloorSidebar();
                    renderTopStats();
                    populateFilterDropdowns();
                    const floorKeys = Object.keys(hospitalData.floors);
                    if (floorKeys.length > 0) {
                        setTimeout(() => { resetView(); }, 100);
                    }
                } else {
                    renderTopStats();
                    refreshCurrentView();
                    updateFloorMeta();
                }
            })
            .catch(err => IPD.toast(err.message || 'Failed to load bed data', 'error'));
    }

    /* ============================================================
       BUILD HIERARCHY
    ============================================================ */
    function buildHierarchy(beds) {
        hospitalData = { floors: {}, stats: { totalFloors:0, totalWards:0, totalRoomTypes:0, totalRooms:0, totalBeds:0, occupied:0, available:0, maintenance:0, blocked:0 } };
        let uFloors = new Set(), uWards = new Set(), uTypes = new Set(), uRooms = new Set();

        beds.forEach(bed => {
            const fName = bed.floor_name || 'Unassigned';
            const wName = bed.ward_name  || 'Unassigned Ward';
            const rType = bed.room_type  || 'General';
            const rNum  = bed.room_number|| '0';

            let status = (bed.bed_status || 'Available').toLowerCase();
            if (status === 'occupied' && !bed.patient_id) status = 'available';
            let norm = 'Available';
            if (status === 'occupied') norm = 'Occupied';
            if (status === 'blocked')  norm = 'Blocked';
            if (status === 'maintenance' || status === 'maintainance') norm = 'Maintenance';

            if (!hospitalData.floors[fName]) { hospitalData.floors[fName] = { name:fName, number:bed.floor_number||0, wards:{}, stats:{total:0,occ:0,avail:0} }; uFloors.add(fName); }
            const fl = hospitalData.floors[fName];

            if (!fl.wards[wName]) { fl.wards[wName] = { name:wName, roomTypes:{}, stats:{total:0,occ:0,avail:0} }; uWards.add(fName+'_'+wName); }
            const w = fl.wards[wName];

            if (!w.roomTypes[rType]) { 
                w.roomTypes[rType] = { 
                    name:rType, 
                    rooms:{}, 
                    stats:{total:0,occ:0,avail:0},
                    charges: {
                        bed: bed.amount_per_day || 0,
                        nursing: bed.nursig_charge || 0,
                        doctor: bed.doctor_charge || 0,
                        service: bed.service_charge || 0,
                        total: bed.total_bed_amount || 0
                    }
                }; 
                uTypes.add(fName+'_'+wName+'_'+rType); 
            }
            const rt = w.roomTypes[rType];

            if (!rt.rooms[rNum]) { rt.rooms[rNum] = { number:rNum, name:bed.room_name, type:rType, beds:[], stats:{total:0,occ:0,avail:0} }; uRooms.add(fName+'_'+wName+'_'+rType+'_'+rNum); }
            const room = rt.rooms[rNum];

            room.beds.push({ ...bed, normalized_status: norm });

            hospitalData.stats.totalBeds++;
            fl.stats.total++; w.stats.total++; rt.stats.total++; room.stats.total++;

            if (norm === 'Occupied')     { hospitalData.stats.occupied++;    fl.stats.occ++;  w.stats.occ++;  rt.stats.occ++;  room.stats.occ++;  }
            else if (norm === 'Available'){ hospitalData.stats.available++;   fl.stats.avail++;w.stats.avail++;rt.stats.avail++;room.stats.avail++;}
            else if (norm === 'Blocked')  { hospitalData.stats.blocked++;   }
            else if (norm === 'Maintenance'){ hospitalData.stats.maintenance++; }
        });

        hospitalData.stats.totalFloors    = uFloors.size;
        hospitalData.stats.totalWards     = uWards.size;
        hospitalData.stats.totalRoomTypes = uTypes.size;
        hospitalData.stats.totalRooms     = uRooms.size;
    }

    /* ============================================================
       TOP STATS
    ============================================================ */
    function renderTopStats() {
        const s = hospitalData.stats;
        $('#statRow').html(`
            <div class="stat-item si-total">
                <div class="si-icon"><i class="fas fa-bed"></i></div>
                <div><div class="si-num">${s.totalBeds}</div><div class="si-label">Total Beds</div></div>
            </div>
            <div class="stat-item si-avail">
                <div class="si-icon"><i class="fas fa-check-circle"></i></div>
                <div><div class="si-num">${s.available}</div><div class="si-label">Available</div></div>
            </div>
            <div class="stat-item si-occ">
                <div class="si-icon"><i class="fas fa-user-injured"></i></div>
                <div><div class="si-num">${s.occupied}</div><div class="si-label">Occupied</div></div>
            </div>
            <div class="stat-item si-maint">
                <div class="si-icon"><i class="fas fa-tools"></i></div>
                <div><div class="si-num">${s.maintenance + s.blocked}</div><div class="si-label">Maintenance</div></div>
            </div>
        `);
    }

    /* ============================================================
       FLOOR SIDEBAR
    ============================================================ */
    function renderFloorSidebar() {
        const list = $('#floorList');
        list.empty();
        Object.values(hospitalData.floors).forEach(floor => {
            const wardsCount = Object.keys(floor.wards).length;
            const isActive   = currentView.floor === floor.name ? 'active' : '';
            const item = $(`
                <div class="floor-item ${isActive}" data-floor="${floor.name}">
                    <div class="icon"><i class="fas fa-layer-group"></i></div>
                    <div class="floor-item-content">
                        <div class="floor-item-title">${floor.name}</div>
                        <div class="floor-item-meta">${wardsCount} Ward${wardsCount!==1?'s':''} &bull; ${floor.stats.total} Beds</div>
                    </div>
                    <i class="fas fa-chevron-right arrow"></i>
                </div>
            `);
            item.on('click', function () {
                $('.floor-item').removeClass('active');
                $(this).addClass('active');
                navigateTo('floor', floor.name);
            });
            list.append(item);
        });
    }

    function updateFloorMeta() {
        Object.values(hospitalData.floors).forEach(floor => {
            const el = $(`.floor-item[data-floor="${floor.name}"]`);
            if (el.length) {
                const wardsCount = Object.keys(floor.wards).length;
                el.find('.floor-item-meta').text(`${wardsCount} Ward${wardsCount!==1?'s':''} • ${floor.stats.total} Beds`);
            }
        });
    }

    /* ============================================================
       FILTER DROPDOWNS POPULATION
    ============================================================ */
    function populateFilterDropdowns() {
        const floorSel    = $('#floorFilter');
        const wardSel     = $('#wardFilter');
        const roomTypeSel = $('#roomTypeFilter');

        const floors    = new Set();
        const wards     = new Set();
        const roomTypes = new Set();

        Object.values(hospitalData.floors).forEach(f => {
            floors.add(f.name);
            Object.values(f.wards).forEach(w => {
                wards.add(w.name);
                Object.keys(w.roomTypes).forEach(rt => roomTypes.add(rt));
            });
        });

        floorSel.html('<option value="">All Floors</option>');
        floors.forEach(f => floorSel.append(`<option value="${f}">${f}</option>`));

        wardSel.html('<option value="">All Wards</option>');
        wards.forEach(w => wardSel.append(`<option value="${w}">${w}</option>`));

        roomTypeSel.html('<option value="">All Types</option>');
        roomTypes.forEach(rt => roomTypeSel.append(`<option value="${rt}">${rt}</option>`));
    }

    function onFilterChange() {
        const fFloor    = $('#floorFilter').val();
        const fWard     = $('#wardFilter').val();
        const fRoomType = $('#roomTypeFilter').val();

        if (fFloor) {
            // Jump to floor
            $('.floor-item').removeClass('active');
            $(`.floor-item[data-floor="${fFloor}"]`).addClass('active');
            navigateTo('floor', fFloor);
            if (fWard) {
                setTimeout(() => navigateTo('ward', fFloor, fWard), 50);
                if (fRoomType) {
                    setTimeout(() => navigateTo('roomType', fFloor, fWard, fRoomType), 100);
                }
            }
        }
    }

    /* ============================================================
       NAVIGATION ENGINE
    ============================================================ */
    function navigateTo(level, fName, wName=null, rType=null, rNum=null) {
        currentView = { level, floor:fName, ward:wName, roomType:rType, room:rNum };
        renderBreadcrumbs();
        $('#globalSearch').val('');
        $('#statusFilter').val('');

        if (level === 'floor')    renderWards(fName);
        else if (level === 'ward')     renderRoomTypes(fName, wName);
        else if (level === 'roomType') renderRooms(fName, wName, rType);
        else if (level === 'room')     renderBeds(fName, wName, rType, rNum);
    }

    function refreshCurrentView() {
        if (!currentView.floor) return;
        if (currentView.level === 'floor')    renderWards(currentView.floor);
        else if (currentView.level === 'ward')     renderRoomTypes(currentView.floor, currentView.ward);
        else if (currentView.level === 'roomType') renderRooms(currentView.floor, currentView.ward, currentView.roomType);
        else if (currentView.level === 'room')     renderBeds(currentView.floor, currentView.ward, currentView.roomType, currentView.room);
    }

    /* ============================================================
       BREADCRUMBS
    ============================================================ */
    function renderBreadcrumbs() {
        let html = `<div class="breadcrumb-item" onclick="resetView()"><i class="fas fa-hospital" style="font-size:11px;"></i> Hospital</div>`;
        if (currentView.floor) {
            html += `<i class="fas fa-chevron-right breadcrumb-separator"></i>`;
            const a = currentView.level==='floor' ? 'active' : '';
            html += `<div class="breadcrumb-item ${a}" onclick="navigateTo('floor','${currentView.floor}')">${currentView.floor}</div>`;
        }
        if (currentView.ward) {
            html += `<i class="fas fa-chevron-right breadcrumb-separator"></i>`;
            const a = currentView.level==='ward' ? 'active' : '';
            html += `<div class="breadcrumb-item ${a}" onclick="navigateTo('ward','${currentView.floor}','${currentView.ward}')">${currentView.ward}</div>`;
        }
        if (currentView.roomType) {
            html += `<i class="fas fa-chevron-right breadcrumb-separator"></i>`;
            const a = currentView.level==='roomType' ? 'active' : '';
            html += `<div class="breadcrumb-item ${a}" onclick="navigateTo('roomType','${currentView.floor}','${currentView.ward}','${currentView.roomType}')">${currentView.roomType}</div>`;
        }
        if (currentView.room) {
            html += `<i class="fas fa-chevron-right breadcrumb-separator"></i>`;
            const a = currentView.level==='room' ? 'active' : '';
            html += `<div class="breadcrumb-item ${a}">Room ${currentView.room}</div>`;
        }
        $('#breadcrumbBar').html(html);
    }

    function resetView() {
        currentView = { level:'hospital', floor:null, ward:null, roomType:null, room:null };
        $('.floor-item').removeClass('active');
        renderBreadcrumbs();
        renderHospitalNoticeBoard();
    }

    function renderHospitalNoticeBoard() {
        let html = sectionTitle('fa-chalkboard', 'Hospital Notice Board — Floor Overview');
        html += `<div class="nav-grid">`;
        
        Object.values(hospitalData.floors).forEach(floor => {
            const pct = floor.stats.total > 0 ? Math.round((floor.stats.occ / floor.stats.total) * 100) : 0;
            const wardsCount = Object.keys(floor.wards).length;
            html += `
                <div class="nav-card searchable-card" data-search="${floor.name.toLowerCase()}"
                     onclick="navigateTo('floor','${floor.name}')">
                    <div class="nav-card-icon"><i class="fas fa-building"></i></div>
                    <div class="nav-card-title">${floor.name}</div>
                    <div class="nav-card-sub">${wardsCount} Ward${wardsCount!==1?'s':''}</div>
                    <div class="nav-card-stats">
                        <div class="nav-stat"><div class="nav-stat-num occ">${floor.stats.occ}</div><div class="nav-stat-label">Occupied</div></div>
                        <div class="nav-stat"><div class="nav-stat-num ava">${floor.stats.avail}</div><div class="nav-stat-label">Available</div></div>
                        <div class="nav-stat"><div class="nav-stat-num tot">${floor.stats.total}</div><div class="nav-stat-label">Total</div></div>
                    </div>
                    <div class="nav-card-footer"><span>${pct}% Occupied</span><span>${floor.stats.total} Beds</span></div>
                    <div class="prog-bar-wrap"><div class="prog-bar-fill ${pct>80?'danger':''}" style="width:${pct}%"></div></div>
                </div>`;
        });
        
        html += `</div>`;
        $('#dynamicView').html(html);
    }

    /* ============================================================
       SECTION TITLE HELPER
    ============================================================ */
    function sectionTitle(icon, title, showLegend = false) {
        const legend = showLegend ? `
            <div class="legend">
                <span class="legend-dot l-avail">Available</span>
                <span class="legend-dot l-occ">Occupied</span>
                <span class="legend-dot l-maint">Maintenance</span>
                <span class="legend-dot l-block">Blocked</span>
            </div>` : '';
        return `<div class="view-section-title"><h2><i class="fas ${icon}" style="color:var(--green);margin-right:8px;font-size:16px;"></i>${title}</h2>${legend}</div>`;
    }

    /* ============================================================
       RENDER WARDS
    ============================================================ */
    function renderWards(floorName) {
        const floor = hospitalData.floors[floorName];
        if (!floor) return;
        let html = sectionTitle('fa-hospital-alt', `Wards on ${floorName}`);
        html += `<div class="nav-grid">`;
        Object.values(floor.wards).forEach(ward => {
            const pct = ward.stats.total > 0 ? Math.round((ward.stats.occ / ward.stats.total) * 100) : 0;
            let totalRooms = 0;
            Object.values(ward.roomTypes).forEach(rt => totalRooms += Object.keys(rt.rooms).length);
            html += `
                <div class="nav-card searchable-card" data-search="${ward.name.toLowerCase()}"
                     onclick="navigateTo('ward','${floorName}','${ward.name}')">
                    <div class="nav-card-icon"><i class="fas fa-hospital-alt"></i></div>
                    <div class="nav-card-title">${ward.name}</div>
                    <div class="nav-card-sub">${totalRooms} Room${totalRooms!==1?'s':''}</div>
                    <div class="nav-card-stats">
                        <div class="nav-stat"><div class="nav-stat-num occ">${ward.stats.occ}</div><div class="nav-stat-label">Occupied</div></div>
                        <div class="nav-stat"><div class="nav-stat-num ava">${ward.stats.avail}</div><div class="nav-stat-label">Available</div></div>
                        <div class="nav-stat"><div class="nav-stat-num tot">${ward.stats.total}</div><div class="nav-stat-label">Total</div></div>
                    </div>
                    <div class="nav-card-footer"><span>${pct}% Occupied</span><span>${ward.stats.total} Beds</span></div>
                    <div class="prog-bar-wrap"><div class="prog-bar-fill ${pct>80?'danger':''}" style="width:${pct}%"></div></div>
                </div>`;
        });
        html += `</div>`;
        $('#dynamicView').html(html);
    }

    /* ============================================================
       RENDER ROOM TYPES
    ============================================================ */
    function renderRoomTypes(floorName, wardName) {
        const ward = hospitalData.floors[floorName].wards[wardName];
        if (!ward) return;
        let html = sectionTitle('fa-th-large', `Room Types — ${wardName}`);
        html += `<div class="nav-grid">`;
        Object.values(ward.roomTypes).forEach(rt => {
            const pct = rt.stats.total > 0 ? Math.round((rt.stats.occ / rt.stats.total) * 100) : 0;
            const roomsCount = Object.keys(rt.rooms).length;
            html += `
                <div class="nav-card searchable-card" data-search="${rt.name.toLowerCase()}"
                     onclick="navigateTo('roomType','${floorName}','${wardName}','${rt.name}')">
                    <div class="nav-card-icon"><i class="fas fa-bed"></i></div>
                    <div class="nav-card-title">${rt.name}</div>
                    <div class="nav-card-sub">${roomsCount} Room${roomsCount!==1?'s':''}</div>
                    <div class="nav-card-stats">
                        <div class="nav-stat"><div class="nav-stat-num occ">${rt.stats.occ}</div><div class="nav-stat-label">Occupied</div></div>
                        <div class="nav-stat"><div class="nav-stat-num ava">${rt.stats.avail}</div><div class="nav-stat-label">Available</div></div>
                        <div class="nav-stat"><div class="nav-stat-num tot">${rt.stats.total}</div><div class="nav-stat-label">Total</div></div>
                    </div>
                    
                    <div class="mt-3 pt-2" style="border-top: 1px dashed var(--border); font-size: 11px;">
                        <div class="d-flex justify-content-between mb-1">
                            <span class="text-muted">Bed Rate:</span> <strong class="text-dark">₹${rt.charges.bed}</strong>
                        </div>
                        <div class="d-flex justify-content-between mb-1">
                            <span class="text-muted">Nursing & Doctor:</span> <strong class="text-dark">₹${rt.charges.nursing} + ₹${rt.charges.doctor}</strong>
                        </div>
                        <div class="d-flex justify-content-between mb-1">
                            <span class="text-muted">Service Charge:</span> <strong class="text-dark">₹${rt.charges.service}</strong>
                        </div>
                        <div class="d-flex justify-content-between mt-1 pt-1 border-top">
                            <span class="text-success fw-bold">Total Amount:</span> <strong class="text-success fs-6">₹${rt.charges.total}</strong>
                        </div>
                    </div>

                    <div class="nav-card-footer mt-2"><span>${pct}% Occupied</span><span>${rt.stats.total} Beds</span></div>
                    <div class="prog-bar-wrap"><div class="prog-bar-fill ${pct>80?'danger':''}" style="width:${pct}%"></div></div>
                </div>`;
        });
        html += `</div>`;
        $('#dynamicView').html(html);
    }

    /* ============================================================
       RENDER ROOMS
    ============================================================ */
    function renderRooms(floorName, wardName, roomTypeName) {
        const rt = hospitalData.floors[floorName].wards[wardName].roomTypes[roomTypeName];
        if (!rt) return;
        let html = sectionTitle('fa-door-open', `Rooms — ${roomTypeName}`);
        html += `<div class="nav-grid">`;
        Object.values(rt.rooms).forEach(room => {
            const pct = room.stats.total > 0 ? Math.round((room.stats.occ / room.stats.total) * 100) : 0;
            html += `
                <div class="nav-card searchable-card" data-search="room ${room.number.toLowerCase()} ${room.name ? room.name.toLowerCase() : ''}"
                     onclick="navigateTo('room','${floorName}','${wardName}','${roomTypeName}','${room.number}')">
                    <div class="nav-card-icon"><i class="fas fa-door-open"></i></div>
                    <div class="nav-card-title">${room.name || 'Room ' + room.number}</div>
                    <div class="nav-card-sub">Room No. ${room.number}</div>
                    <div class="nav-card-stats">
                        <div class="nav-stat"><div class="nav-stat-num occ">${room.stats.occ}</div><div class="nav-stat-label">Occupied</div></div>
                        <div class="nav-stat"><div class="nav-stat-num ava">${room.stats.avail}</div><div class="nav-stat-label">Available</div></div>
                        <div class="nav-stat"><div class="nav-stat-num tot">${room.stats.total}</div><div class="nav-stat-label">Total</div></div>
                    </div>
                    <div class="nav-card-footer"><span>${pct}% Full</span><span>${room.stats.total} Beds</span></div>
                    <div class="prog-bar-wrap"><div class="prog-bar-fill ${pct===100?'danger':''}" style="width:${pct}%"></div></div>
                </div>`;
        });
        html += `</div>`;
        $('#dynamicView').html(html);
    }

    /* ============================================================
       RENDER BEDS (THE MAIN GRID)
    ============================================================ */
    function renderBeds(floorName, wardName, roomTypeName, roomNum) {
        const room = hospitalData.floors[floorName].wards[wardName].roomTypes[roomTypeName].rooms[roomNum];
        if (!room) return;

        let html = sectionTitle('fa-bed', `Beds in Room ${roomNum}`, true);
        html += `<div class="bed-grid">`;

        room.beds.forEach(bed => {
            const st = bed.normalized_status.toLowerCase();
            let bodyHtml = '';
            let actionHtml = '';

            if (st === 'occupied') {
                bodyHtml = `
                    <div class="bed-patient">
                        <div class="bed-patient-name"><i class="fas fa-user-circle" style="color:var(--green);"></i> ${bed.patient_name || 'Unknown Patient'}</div>
                        <div><b>PID:</b> ${bed.patient_id || '—'}</div>
                        <div><b>Admitted:</b> ${IPD.formatDate(bed.admission_date)}</div>
                    </div>`;
                actionHtml = `<button class="btn-bed btn-bed-release" onclick="event.stopPropagation(); handleAction('${bed.bed_id}','release')"><i class="fas fa-sign-out-alt"></i> Release Bed</button>`;
            } else if (st === 'available') {
                bodyHtml = `
                    <div class="bed-status-center">
                        <i class="fas fa-check-circle"></i>
                        <span>Ready for Patient</span>
                    </div>`;
                actionHtml = `<button class="btn-bed btn-bed-manage" onclick="event.stopPropagation(); handleAction('${bed.bed_id}','manage')"><i class="fas fa-sliders-h"></i> Change Status</button>`;
            } else {
                const icons = { maintenance:'fa-tools', blocked:'fa-ban', reserved:'fa-bookmark', cleaning:'fa-broom' };
                const icon = icons[st] || 'fa-ban';
                bodyHtml = `
                    <div class="bed-status-center">
                        <i class="fas ${icon}"></i>
                        <span>${bed.normalized_status}</span>
                    </div>`;
                actionHtml = `<button class="btn-bed btn-bed-manage" onclick="event.stopPropagation(); handleAction('${bed.bed_id}','manage')"><i class="fas fa-sliders-h"></i> Change Status</button>`;
            }

            html += `
                <div class="bed-card status-${st} searchable-card"
                     data-search="${bed.bed_number.toLowerCase()} ${bed.patient_name ? bed.patient_name.toLowerCase() : ''}"
                     data-status="${bed.normalized_status}">
                    <div class="bed-card-inner">
                        <div class="bed-card-head">
                            <div class="bed-number"><i class="fas fa-bed"></i> ${bed.bed_number}</div>
                            <span class="bed-status-badge">${bed.normalized_status}</span>
                        </div>
                        <div class="bed-body-content">${bodyHtml}</div>
                    </div>
                    <div class="bed-card-action">${actionHtml}</div>
                </div>`;
        });

        html += `</div>`;
        $('#dynamicView').html(html);
    }

    /* ============================================================
       CUSTOM MODAL ENGINE
    ============================================================ */
    const Modal = {
        overlay: null,
        init() {
            this.overlay = document.getElementById('hmsModalOverlay');
        },

        // Confirm dialog (Release Bed)
        confirm({ title, body, confirmText = 'Confirm', cancelText = 'Cancel', type = 'danger', onConfirm }) {
            const iconClass  = type === 'danger' ? 'danger' : 'info';
            const iconSymbol = type === 'danger' ? 'fa-sign-out-alt' : 'fa-check-circle';
            const btnClass   = type === 'danger' ? 'confirm-red' : 'confirm-green';

            document.getElementById('hmsModalContent').innerHTML = `
                <div class="hms-modal-icon ${iconClass}"><i class="fas ${iconSymbol}"></i></div>
                <div class="hms-modal-title">${title}</div>
                <div class="hms-modal-body">${body}</div>
                <div class="hms-modal-actions">
                    <button class="hms-modal-btn cancel" id="hmsBtnCancel">${cancelText}</button>
                    <button class="hms-modal-btn ${btnClass}" id="hmsBtnConfirm">${confirmText}</button>
                </div>
            `;

            this.overlay.classList.add('open');

            document.getElementById('hmsBtnCancel').onclick  = () => this.close();
            document.getElementById('hmsBtnConfirm').onclick = () => { this.close(); onConfirm(); };
        },

        // Select/prompt dialog (Change Status)
        prompt({ title, body, options, defaultVal, confirmText = 'Update', cancelText = 'Cancel', onConfirm }) {
            const opts = options.map(o =>
                `<option value="${o}" ${o === defaultVal ? 'selected' : ''}>${o}</option>`
            ).join('');

            document.getElementById('hmsModalContent').innerHTML = `
                <div class="hms-modal-icon info"><i class="fas fa-sliders-h"></i></div>
                <div class="hms-modal-title">${title}</div>
                <div class="hms-modal-body">${body}</div>
                <div class="hms-modal-select-row">
                    <label>Select New Status</label>
                    <select class="hms-modal-select" id="hmsStatusSelect">${opts}</select>
                </div>
                <div class="hms-modal-actions">
                    <button class="hms-modal-btn cancel" id="hmsBtnCancel">${cancelText}</button>
                    <button class="hms-modal-btn confirm-green" id="hmsBtnConfirm">${confirmText}</button>
                </div>
            `;

            this.overlay.classList.add('open');

            document.getElementById('hmsBtnCancel').onclick  = () => this.close();
            document.getElementById('hmsBtnConfirm').onclick = () => {
                const val = document.getElementById('hmsStatusSelect').value;
                this.close();
                onConfirm(val);
            };
        },

        close() {
            this.overlay.classList.remove('open');
        }
    };

    // Close on overlay click
    document.addEventListener('DOMContentLoaded', () => {
        Modal.init();
        document.getElementById('hmsModalOverlay').addEventListener('click', function(e) {
            if (e.target === this) Modal.close();
        });
    });

    /* ============================================================
       ACTIONS
    ============================================================ */
    function handleAction(bedId, action) {
        if (action === 'release') {
            Modal.confirm({
                title: 'Release Bed',
                body: 'Are you sure you want to release this bed?<br>The patient will be marked as discharged.',
                confirmText: '<i class="fas fa-sign-out-alt"></i>&nbsp; Release',
                cancelText: 'Cancel',
                type: 'danger',
                onConfirm: () => {
                    IPD.ajax('beds?action=release', 'POST', { bed_id: bedId })
                        .then(() => { IPD.toast('Bed released successfully', 'success'); loadBedData(); })
                        .catch(err => IPD.toast(err.message, 'error'));
                }
            });
        } else if (action === 'manage') {
            Modal.prompt({
                title: 'Change Bed Status',
                body: 'Select the new status for this bed.',
                options: ['Available', 'Blocked', 'Maintenance'],
                defaultVal: 'Available',
                confirmText: '<i class="fas fa-check"></i>&nbsp; Update Status',
                onConfirm: (newStatus) => {
                    IPD.ajax('beds?id=' + bedId, 'PUT', { status: newStatus })
                        .then(() => { IPD.toast('Bed status updated to ' + newStatus, 'success'); loadBedData(); })
                        .catch(err => IPD.toast(err.message, 'error'));
                }
            });
        }
    }

    /* ============================================================
       SEARCH & FILTER
    ============================================================ */
    function filterView() {
        const query = $('#globalSearch').val().toLowerCase().trim();
        const statusFilter = $('#statusFilter').val();

        $('.searchable-card').each(function () {
            let show = true;
            const txt = $(this).data('search') || '';
            const st  = $(this).data('status') || '';

            if (query && !txt.includes(query)) show = false;
            if (statusFilter && currentView.level === 'room' && st !== statusFilter) show = false;
            $(this).toggle(show);
        });
    }
</script>

<!-- ═══════════════════════════════════════════════════════════
     CUSTOM CENTER MODAL – replaces browser prompt/confirm
═══════════════════════════════════════════════════════════ -->
<div id="hmsModalOverlay" class="hms-modal-overlay">
    <div class="hms-modal" id="hmsModalContent">
        <!-- Injected dynamically by Modal.confirm() / Modal.prompt() -->
    </div>
</div>

</body>

</html>
