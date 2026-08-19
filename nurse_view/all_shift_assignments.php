<?php
session_start();
require_once __DIR__ . '/../core/Autoloader.php';
use GM_HMS\Database\SecureDatabase;

if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['Superintendent_Nurse', 'Superintendent Nurse', 'admin', 'Admin', 'Head Nurse'])) {
    header('Location: dashboard.php');
    exit();
}

$db = SecureDatabase::getInstance();
$conn = $db->getConnection();

$allShifts = [];
$floors = [];
$wards = [];
$roomTypes = [];

try {
    $stmt = $conn->query("SELECT floor_name, ward_name, room_type, room_name, start_date, end_date, shift_data FROM shift_schedules ORDER BY start_date DESC");
    if($stmt) {
        while($row = $stmt->fetch_assoc()) {
            $f = $row['floor_name'] ?: 'Unassigned';
            $w = $row['ward_name'] ?: 'Unassigned';
            $r = $row['room_type'] ?: 'Unassigned';

            if(!in_array($f, $floors)) $floors[] = $f;
            if(!in_array($w, $wards)) $wards[] = $w;
            if(!in_array($r, $roomTypes)) $roomTypes[] = $r;

            $jsonData = json_decode($row['shift_data'], true);
            if(is_array($jsonData)) {
                // Group by Nurse and Shift Type within this block
                $grouped = [];
                foreach($jsonData as $shift) {
                    $key = $shift['nurse_id'] . '_' . $shift['shift_type'];
                    if(!isset($grouped[$key])) {
                        $grouped[$key] = [
                            'nurse_name' => $shift['nurse_name'],
                            'shift_type' => $shift['shift_type'],
                            'days' => []
                        ];
                    }
                    $grouped[$key]['days'][] = $shift['shift_date'];
                }

                foreach($grouped as $g) {
                    $allShifts[] = [
                        'nurse_name' => $g['nurse_name'],
                        'shift_type' => $g['shift_type'],
                        'start_date' => $row['start_date'],
                        'end_date' => $row['end_date'],
                        'floor_name' => $f,
                        'ward_name' => $w,
                        'room_type' => $r,
                        'days_count' => count($g['days'])
                    ];
                }
            }
        }
    }
} catch (Exception $e) {
    // Handle quietly
}

sort($floors);
sort($wards);
sort($roomTypes);

usort($allShifts, function($a, $b) {
    $dateCmp = strcmp($b['start_date'], $a['start_date']);
    if($dateCmp !== 0) return $dateCmp;
    return strcmp($a['nurse_name'], $b['nurse_name']);
});

// Calculate Shift Statistics for Quick Badges
$totalAssignments = count($allShifts);
$morningCount = 0;
$eveningCount = 0;
$nightCount = 0;
$offCount = 0;

foreach($allShifts as $s) {
    $t = strtolower($s['shift_type'] ?? '');
    if(strpos($t, 'morning') !== false) $morningCount++;
    elseif(strpos($t, 'evening') !== false) $eveningCount++;
    elseif(strpos($t, 'night') !== false) $nightCount++;
    elseif(strpos($t, 'off') !== false) $offCount++;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>All Shift Assignments - GM HMS</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@600;700&display=swap" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <style>
        /* ── GM HMS Signature 2-Color Theme (#f3efe6 & #1f6b4a) ── */
        :root {
            --gm-bg: #f3efe6;
            --gm-bg-card: #ffffff;
            --gm-primary: #1f6b4a;
            --gm-primary-dark: #144d34;
            --gm-primary-light: rgba(31, 107, 74, 0.08);
            --gm-primary-mid: rgba(31, 107, 74, 0.15);
            --gm-border: rgba(31, 107, 74, 0.22);
            --gm-border-strong: #1f6b4a;
            --gm-text: #1f6b4a;
            --gm-text-body: #2c3e35;
            --gm-text-muted: #527967;
            --gm-sidebar-w: 185px;
            --shadow-sm: 0 4px 16px rgba(31, 107, 74, 0.06);
            --shadow-md: 0 8px 24px rgba(31, 107, 74, 0.12);
        }

        * { 
            margin: 0; 
            padding: 0; 
            box-sizing: border-box; 
            font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, sans-serif; 
        }

        body { 
            background: var(--gm-bg); 
            min-height: 100vh; 
            display: flex; 
            color: var(--gm-text-body); 
            overflow-x: hidden; 
            -webkit-font-smoothing: antialiased;
        }

        .main-layout { 
            display: flex; 
            width: 100%; 
            min-height: 100vh;
        }

        .content-wrapper { 
            flex: 1; 
            display: flex; 
            flex-direction: column; 
            min-width: 0;
            background-color: var(--gm-bg);
            transition: margin-left 0.25s ease;
        }
        
        @media (min-width: 1024px) {
            .content-wrapper { margin-left: var(--gm-sidebar-w, 185px); }
        }
        
        .main-content { 
            flex: 1; 
            padding: 24px; 
            overflow-y: auto; 
        }

        .container { 
            max-width: 1400px; 
            margin: 0 auto; 
            animation: fadeIn 0.35s ease-out; 
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(8px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* ── Header Toolbar ── */
        .header-toolbar {
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            flex-wrap: wrap;
            gap: 16px;
            margin-bottom: 20px;
            padding-bottom: 16px;
            border-bottom: 2px solid var(--gm-border);
        }

        .header-title {
            display: flex; 
            align-items: center; 
            gap: 14px;
        }

        .header-title h1 { 
            font-size: 1.45rem; 
            font-weight: 800; 
            color: var(--gm-primary); 
            margin: 0;
            letter-spacing: -0.3px;
        }

        .header-title p {
            color: var(--gm-text-muted); 
            font-size: 0.84rem; 
            font-weight: 600; 
            margin-top: 2px;
        }

        .header-title .icon-box { 
            background: var(--gm-primary); 
            color: #f3efe6; 
            width: 46px; 
            height: 46px; 
            border-radius: 12px; 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            font-size: 1.25rem;
            box-shadow: 0 4px 12px rgba(31, 107, 74, 0.22);
            flex-shrink: 0;
        }

        .header-actions {
            display: flex;
            gap: 10px;
            align-items: center;
            flex-wrap: wrap;
        }

        .btn {
            padding: 9px 18px; 
            border-radius: 9px; 
            font-size: 0.84rem; 
            font-weight: 700; 
            cursor: pointer; 
            border: 1.5px solid var(--gm-border);
            display: inline-flex; 
            align-items: center; 
            justify-content: center;
            gap: 8px; 
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
            background: #ffffff;
            color: var(--gm-primary);
            min-height: 38px;
            white-space: nowrap;
        }

        .btn:hover {
            background: var(--gm-primary-light);
            border-color: var(--gm-primary);
            transform: translateY(-1px);
        }

        .btn-primary {
            background: var(--gm-primary);
            color: #f3efe6;
            border-color: var(--gm-primary);
            box-shadow: 0 4px 12px rgba(31, 107, 74, 0.25);
        }

        .btn-primary:hover {
            background: var(--gm-primary-dark);
            color: #ffffff;
        }

        /* ── Quick KPI Summary Cards ── */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 14px;
            margin-bottom: 22px;
        }

        .stat-card {
            background: #ffffff;
            border: 1.5px solid var(--gm-border);
            border-radius: 12px;
            padding: 14px 18px;
            display: flex;
            align-items: center;
            gap: 12px;
            box-shadow: var(--shadow-sm);
            transition: transform 0.2s ease;
        }

        .stat-card:hover {
            transform: translateY(-2px);
            border-color: var(--gm-primary);
        }

        .stat-icon {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            background: var(--gm-primary-light);
            color: var(--gm-primary);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.1rem;
            flex-shrink: 0;
        }

        .stat-info {
            display: flex;
            flex-direction: column;
        }

        .stat-num {
            font-size: 1.25rem;
            font-weight: 800;
            color: var(--gm-primary);
            line-height: 1.2;
        }

        .stat-label {
            font-size: 0.74rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: var(--gm-text-muted);
        }

        /* ── Filters & Search Panel ── */
        .filters-panel {
            background: #ffffff; 
            padding: 18px 20px; 
            border-radius: 14px; 
            margin-bottom: 22px;
            border: 1.5px solid var(--gm-border);
            box-shadow: var(--shadow-sm); 
            display: flex; 
            gap: 14px; 
            flex-wrap: wrap; 
            align-items: flex-end;
        }

        .filter-group { 
            display: flex; 
            flex-direction: column; 
            gap: 5px; 
            flex: 1; 
            min-width: 160px; 
        }

        .filter-group label { 
            font-size: 0.72rem; 
            font-weight: 800; 
            color: var(--gm-primary); 
            text-transform: uppercase; 
            letter-spacing: 0.5px; 
        }

        .filter-group select, .filter-group input {
            padding: 9px 12px; 
            border: 1.5px solid var(--gm-border); 
            border-radius: 9px;
            font-size: 0.86rem; 
            font-weight: 600; 
            color: var(--gm-primary); 
            outline: none; 
            background: var(--gm-bg); 
            cursor: pointer;
            transition: all 0.2s ease;
            width: 100%;
        }

        .filter-group select:focus, .filter-group input:focus { 
            background: #ffffff;
            border-color: var(--gm-primary); 
            box-shadow: 0 0 0 3px var(--gm-primary-light);
        }

        .btn-reset {
            background: var(--gm-primary-light);
            color: var(--gm-primary);
            border: 1.5px solid var(--gm-border);
            height: 38px;
            padding: 0 16px;
        }

        .btn-reset:hover {
            background: var(--gm-primary);
            color: #f3efe6;
            border-color: var(--gm-primary);
        }

        /* ── Main Schedule Table Card ── */
        .card {
            background: #ffffff; 
            border-radius: 14px; 
            padding: 0; 
            border: 1.5px solid var(--gm-border);
            box-shadow: var(--shadow-sm); 
            overflow: hidden;
            margin-bottom: 30px;
        }

        .card-header-bar {
            padding: 14px 20px;
            background: var(--gm-primary);
            color: #f3efe6;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-weight: 800;
            font-size: 0.92rem;
        }

        .table-responsive { 
            overflow-x: auto; 
            -webkit-overflow-scrolling: touch;
        }

        table { 
            width: 100%; 
            border-collapse: collapse; 
            white-space: nowrap; 
            font-size: 0.84rem;
        }

        th { 
            background: var(--gm-bg); 
            padding: 13px 18px; 
            text-align: left;
            font-size: 0.72rem; 
            font-weight: 800; 
            color: var(--gm-primary); 
            text-transform: uppercase; 
            letter-spacing: 0.6px;
            border-bottom: 1.5px solid var(--gm-border);
        }

        td { 
            padding: 13px 18px; 
            border-bottom: 1px solid var(--gm-bg);
            font-size: 0.84rem; 
            font-weight: 600; 
            color: var(--gm-text-body);
            vertical-align: middle;
        }

        tr:nth-child(even) td {
            background-color: rgba(31, 107, 74, 0.02);
        }

        tr:hover td { 
            background: var(--gm-primary-light); 
        }

        tr:last-child td { 
            border-bottom: none; 
        }

        .nurse-info { 
            display: flex; 
            align-items: center; 
            gap: 12px; 
        }

        .nurse-avatar { 
            width: 38px; 
            height: 38px; 
            border-radius: 9px; 
            background: var(--gm-primary);
            color: #f3efe6; 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            font-size: 0.85rem; 
            font-weight: 800;
            flex-shrink: 0;
        }

        .badge { 
            padding: 4px 11px; 
            border-radius: 6px; 
            font-size: 0.75rem; 
            font-weight: 800; 
            text-transform: uppercase; 
            display: inline-flex;
            align-items: center;
            gap: 5px;
            letter-spacing: 0.4px;
        }

        .badge-morning { 
            background: var(--gm-primary-light); 
            color: var(--gm-primary); 
            border: 1px solid var(--gm-border); 
        }

        .badge-evening { 
            background: rgba(217, 119, 6, 0.12); 
            color: #b45309; 
            border: 1px solid rgba(217, 119, 6, 0.25); 
        }

        .badge-night { 
            background: rgba(30, 64, 175, 0.1); 
            color: #1e40af; 
            border: 1px solid rgba(30, 64, 175, 0.22); 
        }

        .badge-weekoff { 
            background: rgba(220, 38, 38, 0.1); 
            color: #dc2626; 
            border: 1px solid rgba(220, 38, 38, 0.22); 
        }

        .date-range { 
            display: flex; 
            align-items: center; 
            gap: 6px; 
            font-size: 0.82rem; 
            color: var(--gm-primary); 
            font-weight: 700; 
        }

        .date-range i { 
            color: var(--gm-text-muted); 
            font-size: 0.85rem;
        }

        .chip-days {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 3px 8px;
            background: var(--gm-bg);
            border: 1px solid var(--gm-border);
            border-radius: 6px;
            font-weight: 800;
            color: var(--gm-primary);
            font-size: 0.78rem;
        }

        /* ── Responsive Rules ── */
        @media (max-width: 1023px) {
            .main-content { padding: 16px; }
            .header-toolbar { flex-direction: column; align-items: stretch; gap: 12px; }
            .header-actions { width: 100%; display: grid; grid-template-columns: 1fr 1fr; }
            .header-actions .btn { width: 100%; }
        }

        @media (max-width: 767px) {
            .main-content { padding: 12px; }
            .header-title h1 { font-size: 1.2rem; }
            .filters-panel { padding: 14px; gap: 10px; }
            .filter-group { min-width: 100%; }
            .stats-grid { grid-template-columns: repeat(2, 1fr); }
            th, td { padding: 10px 12px; font-size: 0.78rem; }
        }

        /* ── Print Specific CSS ── */
        @media print {
            body * { visibility: hidden; }
            #printableArea, #printableArea * { visibility: visible; }
            #printableArea { 
                position: absolute; 
                left: 0; 
                top: 0; 
                width: 100%; 
                box-shadow: none; 
                border: 1px solid #000;
            }
            .nurse-sidebar, .top-navbar, .header-toolbar, .filters-panel, .stats-grid { display: none !important; }
            .content-wrapper { padding: 0 !important; margin: 0 !important; }
            th { background: #f3efe6 !important; -webkit-print-color-adjust: exact; }
            .badge { border: 1px solid #ccc; background: transparent !important; color: #000 !important; }
        }
    </style>
</head>
<body>
    <div class="main-layout">
        <!-- Sidebar Navigation -->
        <?php include 'includes/nurse_sidebar.php'; ?>

        <div class="content-wrapper">
            <!-- Navbar -->
            <?php include 'includes/nurse_navbar.php'; ?>
            
            <div class="main-content">
                <div class="container">
                    
                    <!-- Header Toolbar -->
                    <div class="header-toolbar">
                        <div class="header-title">
                            <div class="icon-box"><i class="fas fa-calendar-check"></i></div>
                            <div>
                                <h1>All Shift Assignments</h1>
                                <p>Master hospital duty schedule & nurse shift distribution directory.</p>
                            </div>
                        </div>
                        
                        <div class="header-actions">
                            <button class="btn" onclick="window.print()">
                                <i class="fas fa-print"></i> Print Schedule
                            </button>
                            <button class="btn btn-primary btn-pdf" onclick="exportPDF()">
                                <i class="fas fa-file-pdf"></i> Export PDF
                            </button>
                        </div>
                    </div>

                    <!-- Quick Shift Summary KPIs -->
                    <div class="stats-grid">
                        <div class="stat-card">
                            <div class="stat-icon"><i class="fas fa-users-cog"></i></div>
                            <div class="stat-info">
                                <span class="stat-num" id="stat-total"><?php echo $totalAssignments; ?></span>
                                <span class="stat-label">Total Shifts</span>
                            </div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon"><i class="fas fa-sun"></i></div>
                            <div class="stat-info">
                                <span class="stat-num" id="stat-morning"><?php echo $morningCount; ?></span>
                                <span class="stat-label">Morning (M)</span>
                            </div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon"><i class="fas fa-cloud-sun"></i></div>
                            <div class="stat-info">
                                <span class="stat-num" id="stat-evening"><?php echo $eveningCount; ?></span>
                                <span class="stat-label">Evening (E)</span>
                            </div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon"><i class="fas fa-moon"></i></div>
                            <div class="stat-info">
                                <span class="stat-num" id="stat-night"><?php echo $nightCount; ?></span>
                                <span class="stat-label">Night (N)</span>
                            </div>
                        </div>
                    </div>

                    <!-- Interactive Filters & Search Panel -->
                    <div class="filters-panel">
                        <div class="filter-group" style="flex: 1.5; min-width: 200px;">
                            <label><i class="fas fa-search"></i> Search Nurse</label>
                            <input type="text" id="filterNurse" placeholder="Type nurse name..." onkeyup="applyFilters()">
                        </div>
                        <div class="filter-group">
                            <label><i class="fas fa-layer-group"></i> Floor</label>
                            <select id="filterFloor" onchange="applyFilters()">
                                <option value="">All Floors</option>
                                <?php foreach($floors as $f): ?>
                                    <option value="<?php echo htmlspecialchars($f); ?>"><?php echo htmlspecialchars($f); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="filter-group">
                            <label><i class="fas fa-hospital-alt"></i> Ward</label>
                            <select id="filterWard" onchange="applyFilters()">
                                <option value="">All Wards</option>
                                <?php foreach($wards as $w): ?>
                                    <option value="<?php echo htmlspecialchars($w); ?>"><?php echo htmlspecialchars($w); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="filter-group">
                            <label><i class="fas fa-bed"></i> Room Type</label>
                            <select id="filterRoom" onchange="applyFilters()">
                                <option value="">All Room Types</option>
                                <?php foreach($roomTypes as $r): ?>
                                    <option value="<?php echo htmlspecialchars($r); ?>"><?php echo htmlspecialchars($r); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="filter-group">
                            <label><i class="fas fa-clock"></i> Shift Type</label>
                            <select id="filterShiftType" onchange="applyFilters()">
                                <option value="">All Shifts</option>
                                <option value="morning">Morning</option>
                                <option value="evening">Evening</option>
                                <option value="night">Night</option>
                                <option value="week off">Week Off</option>
                            </select>
                        </div>
                        <div>
                            <button type="button" class="btn btn-reset" onclick="resetFilters()" title="Reset All Filters">
                                <i class="fas fa-undo"></i> Reset
                            </button>
                        </div>
                    </div>

                    <!-- Master Table Card -->
                    <div class="card" id="printableArea">
                        <div class="card-header-bar">
                            <span><i class="fas fa-clipboard-list"></i> Duty Shift Rosters & Allocation Feed</span>
                            <span id="record-count" style="font-size: 0.8rem; background: rgba(243, 239, 230, 0.2); padding: 2px 8px; border-radius: 10px; border: 1px solid rgba(243, 239, 230, 0.3);">
                                Showing <?php echo count($allShifts); ?> Assignments
                            </span>
                        </div>
                        <div class="table-responsive">
                            <table id="shiftTable">
                                <thead>
                                    <tr>
                                        <th>Staff Nurse</th>
                                        <th>Schedule Date Range</th>
                                        <th>Shift Type</th>
                                        <th>Floor Allocation</th>
                                        <th>Ward Name</th>
                                        <th>Room / Bed Type</th>
                                        <th>Days Assigned</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if(empty($allShifts)): ?>
                                        <tr id="empty-row"><td colspan="7" style="text-align:center; padding:50px 20px; color:var(--gm-text-muted);">
                                            <i class="fas fa-calendar-times" style="font-size: 2.5rem; opacity: 0.4; margin-bottom: 12px; display: block;"></i>
                                            No active shift assignments found.
                                        </td></tr>
                                    <?php else: ?>
                                        <?php foreach($allShifts as $shift): 
                                            $type = strtolower($shift['shift_type'] ?? '');
                                            $badgeClass = 'badge-morning';
                                            $shiftIcon = 'fa-sun';
                                            if(strpos($type, 'evening') !== false) {
                                                $badgeClass = 'badge-evening';
                                                $shiftIcon = 'fa-cloud-sun';
                                            } elseif(strpos($type, 'night') !== false) {
                                                $badgeClass = 'badge-night';
                                                $shiftIcon = 'fa-moon';
                                            } elseif(strpos($type, 'week off') !== false || strpos($type, 'off') !== false) {
                                                $badgeClass = 'badge-weekoff';
                                                $shiftIcon = 'fa-coffee';
                                            }
                                            
                                            $initials = strtoupper(substr($shift['nurse_name'], 0, 2));
                                        ?>
                                            <tr class="shift-row"
                                                data-nurse="<?php echo htmlspecialchars(strtolower($shift['nurse_name'])); ?>"
                                                data-floor="<?php echo htmlspecialchars($shift['floor_name']); ?>"
                                                data-ward="<?php echo htmlspecialchars($shift['ward_name']); ?>"
                                                data-room="<?php echo htmlspecialchars($shift['room_type']); ?>"
                                                data-type="<?php echo htmlspecialchars($type); ?>">
                                                
                                                <td>
                                                    <div class="nurse-info">
                                                        <div class="nurse-avatar"><?php echo $initials; ?></div>
                                                        <div>
                                                            <div style="font-weight: 800; color: var(--gm-primary); font-size: 0.88rem;"><?php echo htmlspecialchars($shift['nurse_name']); ?></div>
                                                            <small style="color: var(--gm-text-muted); font-size: 0.74rem;">Duty Nurse</small>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="date-range">
                                                        <i class="far fa-calendar-alt"></i>
                                                        <span><?php echo date('d M', strtotime($shift['start_date'])); ?></span>
                                                        <i class="fas fa-arrow-right" style="font-size:10px; margin:0 3px; opacity:0.6;"></i> 
                                                        <span><?php echo date('d M, Y', strtotime($shift['end_date'])); ?></span>
                                                    </div>
                                                </td>
                                                <td><span class="badge <?php echo $badgeClass; ?>"><i class="fas <?php echo $shiftIcon; ?>"></i> <?php echo htmlspecialchars($shift['shift_type']); ?></span></td>
                                                <td><strong style="color: var(--gm-primary);"><?php echo htmlspecialchars($shift['floor_name']); ?></strong></td>
                                                <td><?php echo htmlspecialchars($shift['ward_name']); ?></td>
                                                <td><?php echo htmlspecialchars($shift['room_type']); ?></td>
                                                <td><span class="chip-days"><i class="fas fa-clock"></i> <?php echo $shift['days_count']; ?> days</span></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>

    <script>
        function applyFilters() {
            let fNurse = (document.getElementById('filterNurse')?.value || '').toLowerCase().trim();
            let fFloor = (document.getElementById('filterFloor')?.value || '').toLowerCase().trim();
            let fWard = (document.getElementById('filterWard')?.value || '').toLowerCase().trim();
            let fRoom = (document.getElementById('filterRoom')?.value || '').toLowerCase().trim();
            let fType = (document.getElementById('filterShiftType')?.value || '').toLowerCase().trim();
            
            let rows = document.querySelectorAll("#shiftTable tbody .shift-row");
            let visibleCount = 0;

            rows.forEach(tr => {
                let rNurse = (tr.getAttribute('data-nurse') || '').toLowerCase();
                let rFloor = (tr.getAttribute('data-floor') || '').toLowerCase();
                let rWard = (tr.getAttribute('data-ward') || '').toLowerCase();
                let rRoom = (tr.getAttribute('data-room') || '').toLowerCase();
                let rType = (tr.getAttribute('data-type') || '').toLowerCase();

                let show = true;
                if(fNurse && !rNurse.includes(fNurse)) show = false;
                if(fFloor && rFloor !== fFloor) show = false;
                if(fWard && rWard !== fWard) show = false;
                if(fRoom && rRoom !== fRoom) show = false;
                if(fType && !rType.includes(fType)) show = false;
                
                tr.style.display = show ? "" : "none";
                if(show) visibleCount++;
            });

            const countBadge = document.getElementById('record-count');
            if(countBadge) {
                countBadge.textContent = `Showing ${visibleCount} Assignments`;
            }
        }

        function resetFilters() {
            document.getElementById('filterNurse').value = '';
            document.getElementById('filterFloor').value = '';
            document.getElementById('filterWard').value = '';
            document.getElementById('filterRoom').value = '';
            document.getElementById('filterShiftType').value = '';
            applyFilters();
        }

        function exportPDF() {
            const element = document.getElementById('printableArea');
            
            const opt = {
                margin:       0.4,
                filename:     'Hospital_Nurse_Shift_Assignments.pdf',
                image:        { type: 'jpeg', quality: 0.98 },
                html2canvas:  { scale: 2, useCORS: true },
                jsPDF:        { unit: 'in', format: 'a4', orientation: 'landscape' }
            };
            
            const btn = document.querySelector('.btn-pdf');
            const originalHTML = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Generating PDF...';
            btn.disabled = true;
            
            html2pdf().set(opt).from(element).save().then(() => {
                btn.innerHTML = originalHTML;
                btn.disabled = false;
            }).catch(() => {
                btn.innerHTML = originalHTML;
                btn.disabled = false;
            });
        }
    </script>
</body>
</html>
