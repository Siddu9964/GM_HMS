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
$allNursesList = [];
$wardHierarchy = [];

try {
    // 1. Fetch Staff Nurses List
    $stmtNurses = $conn->query("
        SELECT sl_no as staff_id, full_name as name, designation as role, status 
        FROM staff 
        WHERE designation IN ('Nurse', 'Superintendent_Nurse', 'Nursing_Superintendent', 'Head Nurse', 'Staff Nurse')
        ORDER BY full_name ASC
    ");
    if ($stmtNurses) {
        while ($r = $stmtNurses->fetch_assoc()) {
            $allNursesList[] = [
                'id'     => (string)$r['staff_id'],
                'name'   => $r['name'],
                'role'   => $r['role'] ?: 'Staff Nurse',
                'status' => $r['status']
            ];
        }
    }

    // 2. Fetch Ward Hierarchy & Rooms
    $stmtWards = $conn->query("
        SELECT floor_name, ward_name, room_type, GROUP_CONCAT(DISTINCT room_name SEPARATOR ', ') as room_names, COUNT(bed_number) as total_beds
        FROM hospital_beds
        WHERE floor_name IS NOT NULL AND ward_name IS NOT NULL
        GROUP BY floor_name, ward_name, room_type
        ORDER BY floor_name, ward_name, room_type
    ");
    if ($stmtWards) {
        while ($r = $stmtWards->fetch_assoc()) {
            $wardHierarchy[] = $r;
        }
    }

    // 3. Fetch Shift Schedules & Group into Clean Shift Blocks
    $stmt = $conn->query("SELECT sl_no, floor_name, ward_name, room_type, room_name, start_date, end_date, shift_data FROM shift_schedules ORDER BY start_date DESC, sl_no DESC");
    if ($stmt) {
        while ($row = $stmt->fetch_assoc()) {
            $f = $row['floor_name'] ?: 'Unassigned';
            $w = $row['ward_name'] ?: 'Unassigned';
            $r = $row['room_type'] ?: 'Unassigned';

            if (!in_array($f, $floors) && $f !== 'Unassigned') $floors[] = $f;
            if (!in_array($w, $wards) && $w !== 'Unassigned') $wards[] = $w;
            if (!in_array($r, $roomTypes) && $r !== 'Unassigned') $roomTypes[] = $r;

            $jsonData = json_decode($row['shift_data'], true);
            if (is_array($jsonData)) {
                $grouped = [];
                foreach ($jsonData as $shift) {
                    $nurseId = (string)($shift['nurse_id'] ?? '');
                    $shiftType = $shift['shift_type'] ?? 'General';
                    $key = $nurseId . '|' . $shiftType;

                    if (!isset($grouped[$key])) {
                        $grouped[$key] = [
                            'nurse_id'   => $nurseId,
                            'nurse_name' => $shift['nurse_name'] ?? 'Nurse',
                            'shift_type' => $shiftType,
                            'days'       => []
                        ];
                    }
                    $grouped[$key]['days'][] = $shift['shift_date'];
                }

                foreach ($grouped as $g) {
                    $allShifts[] = [
                        'schedule_id' => (int)$row['sl_no'],
                        'nurse_id'    => (string)$g['nurse_id'],
                        'nurse_name'  => $g['nurse_name'],
                        'shift_type'  => $g['shift_type'],
                        'start_date'  => $row['start_date'],
                        'end_date'    => $row['end_date'],
                        'floor_name'  => $f,
                        'ward_name'   => $w,
                        'room_type'   => $r,
                        'room_name'   => $row['room_name'] ?: '',
                        'days'        => $g['days'],
                        'days_count'  => count($g['days'])
                    ];
                }
            }
        }
    }
} catch (Exception $e) {
    // Handle gracefully
}

// Floor sorting
$floorOrder = [
    'Basement' => -1, 'Ground Floor' => 0, 'First Floor' => 1, 'Second Floor' => 2,
    'Third Floor' => 3, 'Fourth Floor' => 4, 'Fifth Floor' => 5, 'Sixth Floor' => 6,
    'Seventh Floor' => 7, 'Eighth Floor' => 8, 'Ninth Floor' => 9, 'Tenth Floor' => 10
];
usort($floors, function($a, $b) use ($floorOrder) {
    $valA = $floorOrder[$a] ?? 99;
    $valB = $floorOrder[$b] ?? 99;
    return $valA <=> $valB;
});
sort($wards);
sort($roomTypes);

usort($allShifts, function($a, $b) {
    $dateCmp = strcmp($b['start_date'], $a['start_date']);
    if ($dateCmp !== 0) return $dateCmp;
    return strcmp($a['nurse_name'], $b['nurse_name']);
});

// Shift Statistics for Quick Badges
$totalAssignments = count($allShifts);
$morningCount = 0;
$eveningCount = 0;
$nightCount = 0;
$offCount = 0;

foreach ($allShifts as $s) {
    $t = strtolower($s['shift_type'] ?? '');
    if (strpos($t, 'morning') !== false) $morningCount++;
    elseif (strpos($t, 'evening') !== false) $eveningCount++;
    elseif (strpos($t, 'night') !== false) $nightCount++;
    elseif (strpos($t, 'off') !== false) $offCount++;
}

$allShiftsJson = json_encode($allShifts);
$allNursesJson = json_encode($allNursesList);
$wardHierarchyJson = json_encode($wardHierarchy);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Duty Shift Rosters & Allocation Feed - GM HMS</title>
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
            --gm-border: rgba(31, 107, 74, 0.2);
            --gm-border-strong: #1f6b4a;
            --gm-text: #1f6b4a;
            --gm-text-body: #2c3e35;
            --gm-text-muted: #527967;
            --gm-sidebar-w: 185px;
            --shadow-sm: 0 3px 12px rgba(31, 107, 74, 0.06);
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
            padding: 20px 24px; 
            overflow-y: auto; 
        }

        .container { 
            max-width: 1380px; 
            margin: 0 auto; 
            animation: fadeIn 0.3s ease-out; 
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(6px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* ── Header Toolbar ── */
        .header-toolbar {
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            flex-wrap: wrap;
            gap: 14px;
            margin-bottom: 18px;
            padding-bottom: 14px;
            border-bottom: 2px solid var(--gm-border);
        }

        .header-title {
            display: flex; 
            align-items: center; 
            gap: 12px;
        }

        .header-title h1 { 
            font-size: 1.35rem; 
            font-weight: 800; 
            color: var(--gm-primary); 
            margin: 0;
            letter-spacing: -0.3px;
        }

        .header-title p {
            color: var(--gm-text-muted); 
            font-size: 0.82rem; 
            font-weight: 600; 
            margin-top: 2px;
        }

        .header-title .icon-box { 
            background: var(--gm-primary); 
            color: #f3efe6; 
            width: 42px; 
            height: 42px; 
            border-radius: 10px; 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            font-size: 1.15rem;
            box-shadow: 0 4px 10px rgba(31, 107, 74, 0.22);
            flex-shrink: 0;
        }

        .header-actions {
            display: flex;
            gap: 8px;
            align-items: center;
            flex-wrap: wrap;
        }

        .btn {
            padding: 8px 14px; 
            border-radius: 8px; 
            font-size: 0.82rem; 
            font-weight: 700; 
            cursor: pointer; 
            border: 1.5px solid var(--gm-border);
            display: inline-flex; 
            align-items: center; 
            justify-content: center;
            gap: 6px; 
            transition: all 0.18s ease;
            background: #ffffff;
            color: var(--gm-primary);
            min-height: 36px;
            white-space: nowrap;
            text-decoration: none;
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
            box-shadow: 0 3px 10px rgba(31, 107, 74, 0.22);
        }

        .btn-primary:hover {
            background: var(--gm-primary-dark);
            color: #ffffff;
        }

        .btn-swap-main {
            background: #b45309;
            color: #ffffff;
            border-color: #92400e;
            box-shadow: 0 3px 10px rgba(180, 83, 9, 0.2);
        }
        .btn-swap-main:hover {
            background: #92400e;
            color: #ffffff;
        }

        /* ── Compact Filter & Segmented Tabs Bar ── */
        .controls-card {
            background: #ffffff;
            border: 1.5px solid var(--gm-border);
            border-radius: 12px;
            padding: 12px 16px;
            margin-bottom: 16px;
            box-shadow: var(--shadow-sm);
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .tabs-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 10px;
            padding-bottom: 10px;
            border-bottom: 1px solid rgba(31, 107, 74, 0.1);
        }

        .segmented-tabs {
            display: inline-flex;
            background: var(--gm-bg);
            border: 1px solid var(--gm-border);
            border-radius: 8px;
            padding: 3px;
            gap: 3px;
            flex-wrap: wrap;
        }

        .tab-btn {
            border: none;
            background: transparent;
            color: var(--gm-primary);
            font-size: 0.78rem;
            font-weight: 700;
            padding: 5px 12px;
            border-radius: 6px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all 0.15s ease;
        }

        .tab-btn:hover {
            background: var(--gm-primary-light);
        }

        .tab-btn.active {
            background: var(--gm-primary);
            color: #f3efe6;
            box-shadow: 0 2px 6px rgba(31, 107, 74, 0.2);
        }

        .tab-btn .tab-count {
            background: rgba(31, 107, 74, 0.12);
            color: var(--gm-primary);
            font-size: 0.7rem;
            padding: 1px 6px;
            border-radius: 10px;
            font-weight: 800;
        }

        .tab-btn.active .tab-count {
            background: rgba(243, 239, 230, 0.25);
            color: #ffffff;
        }

        .filters-inline {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }

        .search-box-wrap {
            position: relative;
            flex: 1.5;
            min-width: 220px;
        }

        .search-box-wrap i {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--gm-text-muted);
            font-size: 0.85rem;
        }

        .search-input {
            width: 100%;
            padding: 8px 12px 8px 34px;
            border: 1.5px solid var(--gm-border);
            border-radius: 8px;
            font-size: 0.84rem;
            font-weight: 600;
            color: var(--gm-primary);
            background: var(--gm-bg);
            outline: none;
            transition: all 0.18s ease;
        }

        .search-input:focus {
            background: #ffffff;
            border-color: var(--gm-primary);
            box-shadow: 0 0 0 3px var(--gm-primary-light);
        }

        .filter-select {
            padding: 8px 10px;
            border: 1.5px solid var(--gm-border);
            border-radius: 8px;
            font-size: 0.82rem;
            font-weight: 600;
            color: var(--gm-primary);
            background: var(--gm-bg);
            outline: none;
            cursor: pointer;
            min-width: 140px;
        }

        .filter-select:focus {
            background: #ffffff;
            border-color: var(--gm-primary);
        }

        /* ── Master Rosters Feed Table (Compact & Zero-Scroll) ── */
        .roster-card {
            background: #ffffff;
            border: 1.5px solid var(--gm-border);
            border-radius: 12px;
            box-shadow: var(--shadow-sm);
            overflow: hidden;
            margin-bottom: 24px;
        }

        .roster-header {
            padding: 12px 18px;
            background: var(--gm-primary);
            color: #f3efe6;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-weight: 800;
            font-size: 0.88rem;
        }

        .roster-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.84rem;
        }

        .roster-table th {
            background: var(--gm-bg);
            padding: 10px 14px;
            text-align: left;
            font-size: 0.72rem;
            font-weight: 800;
            color: var(--gm-primary);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 1.5px solid var(--gm-border);
        }

        .roster-table td {
            padding: 11px 14px;
            border-bottom: 1px solid rgba(31, 107, 74, 0.08);
            vertical-align: middle;
            color: var(--gm-text-body);
        }

        .roster-table tr:nth-child(even) td {
            background-color: rgba(31, 107, 74, 0.015);
        }

        .roster-table tr:hover td {
            background-color: var(--gm-primary-light);
        }

        .roster-table tr:last-child td {
            border-bottom: none;
        }

        /* ── Formatted Data Cells ── */
        .nurse-cell {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .nurse-avatar {
            width: 34px;
            height: 34px;
            border-radius: 8px;
            background: var(--gm-primary);
            color: #f3efe6;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.8rem;
            font-weight: 800;
            flex-shrink: 0;
        }

        .nurse-name {
            font-weight: 800;
            color: var(--gm-primary);
            font-size: 0.86rem;
            line-height: 1.2;
        }

        .nurse-sub {
            font-size: 0.72rem;
            color: var(--gm-text-muted);
            font-weight: 600;
            margin-top: 1px;
        }

        .location-cell {
            display: flex;
            flex-direction: column;
            gap: 2px;
        }

        .location-floor {
            font-weight: 800;
            color: var(--gm-primary);
            font-size: 0.84rem;
        }

        .location-ward {
            font-size: 0.74rem;
            color: var(--gm-text-muted);
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .shift-cell {
            display: flex;
            flex-direction: column;
            gap: 3px;
            align-items: flex-start;
        }

        .badge-shift {
            padding: 3px 8px;
            border-radius: 6px;
            font-size: 0.72rem;
            font-weight: 800;
            text-transform: uppercase;
            display: inline-flex;
            align-items: center;
            gap: 4px;
            letter-spacing: 0.3px;
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

        .shift-timing {
            font-size: 0.7rem;
            color: var(--gm-text-muted);
            font-weight: 600;
        }

        .date-cell {
            display: flex;
            flex-direction: column;
            gap: 2px;
        }

        .date-main {
            font-size: 0.8rem;
            font-weight: 700;
            color: var(--gm-primary);
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .date-chip {
            display: inline-flex;
            align-items: center;
            gap: 3px;
            font-size: 0.7rem;
            font-weight: 700;
            color: var(--gm-text-muted);
        }

        /* ── Action Buttons ── */
        .actions-group {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            background: var(--gm-bg);
            border: 1px solid var(--gm-border);
            padding: 2px 4px;
            border-radius: 7px;
        }

        .btn-act {
            border: none;
            background: #ffffff;
            color: var(--gm-primary);
            width: 28px;
            height: 28px;
            border-radius: 5px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            font-size: 0.78rem;
            transition: all 0.15s ease;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        }

        .btn-act:hover {
            transform: scale(1.08);
        }

        .btn-act-edit:hover {
            background: var(--gm-primary);
            color: #f3efe6;
        }

        .btn-act-swap {
            color: #b45309;
        }
        .btn-act-swap:hover {
            background: #b45309;
            color: #ffffff;
        }

        .btn-act-del {
            color: #dc2626;
        }
        .btn-act-del:hover {
            background: #dc2626;
            color: #ffffff;
        }

        /* ── Modals & Popups ── */
        .modal-overlay {
            position: fixed;
            inset: 0;
            background: rgba(31, 107, 74, 0.45);
            backdrop-filter: blur(4px);
            z-index: 1000;
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.22s ease;
            padding: 16px;
        }

        .modal-overlay.open {
            opacity: 1;
            pointer-events: all;
        }

        .modal-card {
            background: #ffffff;
            border: 2px solid var(--gm-primary);
            border-radius: 14px;
            width: 100%;
            max-width: 600px;
            max-height: 90vh;
            display: flex;
            flex-direction: column;
            overflow: hidden;
            box-shadow: 0 20px 50px rgba(31, 107, 74, 0.25);
            animation: modalPop 0.22s cubic-bezier(0.34, 1.56, 0.64, 1);
        }

        .modal-card-large { max-width: 800px; }

        @keyframes modalPop {
            from { transform: scale(0.95); opacity: 0; }
            to { transform: scale(1); opacity: 1; }
        }

        .modal-head {
            background: var(--gm-primary);
            color: #f3efe6;
            padding: 14px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-shrink: 0;
        }

        .modal-head h3 {
            font-size: 1.05rem;
            font-weight: 800;
            color: #f3efe6;
            display: flex;
            align-items: center;
            gap: 8px;
            margin: 0;
        }

        .modal-close {
            background: transparent;
            border: none;
            color: #f3efe6;
            font-size: 1.2rem;
            cursor: pointer;
            opacity: 0.85;
        }
        .modal-close:hover { opacity: 1; }

        .modal-body {
            padding: 20px;
            overflow-y: auto;
            flex: 1;
            display: flex;
            flex-direction: column;
            gap: 14px;
        }

        .modal-footer {
            padding: 12px 20px;
            background: var(--gm-bg);
            border-top: 1.5px solid var(--gm-border);
            display: flex;
            justify-content: flex-end;
            align-items: center;
            gap: 10px;
            flex-shrink: 0;
        }

        .form-grid-2 {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }

        .form-group label {
            font-size: 0.74rem;
            font-weight: 800;
            color: var(--gm-primary);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .form-control {
            padding: 9px 12px;
            border: 1.5px solid var(--gm-border);
            border-radius: 8px;
            font-size: 0.86rem;
            font-weight: 600;
            color: var(--gm-primary);
            background: var(--gm-bg);
            outline: none;
        }

        .form-control:focus {
            background: #ffffff;
            border-color: var(--gm-primary);
            box-shadow: 0 0 0 3px var(--gm-primary-light);
        }

        /* Shift Option Pills */
        .shift-opt-group {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 8px;
        }

        .shift-opt-btn {
            padding: 8px 4px;
            border-radius: 8px;
            border: 1.5px solid var(--gm-border);
            background: #ffffff;
            color: var(--gm-primary);
            font-size: 0.78rem;
            font-weight: 800;
            cursor: pointer;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 2px;
            transition: all 0.15s ease;
        }

        .shift-opt-btn:hover {
            background: var(--gm-primary-light);
            border-color: var(--gm-primary);
        }

        .shift-opt-btn.active {
            background: var(--gm-primary);
            color: #f3efe6;
            border-color: var(--gm-primary);
        }

        .shift-opt-time {
            font-size: 0.64rem;
            opacity: 0.85;
            font-weight: 600;
        }

        /* Days Checklist */
        .days-checklist {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
            background: var(--gm-bg);
            padding: 8px 10px;
            border-radius: 8px;
            border: 1px solid var(--gm-border);
        }

        .day-check-item {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            font-size: 0.76rem;
            font-weight: 700;
            color: var(--gm-primary);
            background: #ffffff;
            padding: 3px 8px;
            border-radius: 5px;
            border: 1px solid var(--gm-border);
            cursor: pointer;
        }

        .day-check-item input { accent-color: var(--gm-primary); }

        /* ── Side-by-Side Swap Matrix ── */
        .swap-matrix-container {
            display: grid;
            grid-template-columns: 1fr auto 1fr;
            gap: 14px;
            align-items: center;
        }

        @media (max-width: 768px) {
            .swap-matrix-container { grid-template-columns: 1fr; }
        }

        .swap-card {
            background: var(--gm-bg);
            border: 2px solid var(--gm-border);
            border-radius: 12px;
            padding: 14px;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .swap-card.source-card {
            border-color: var(--gm-primary);
            background: #ffffff;
        }

        .swap-card.target-card {
            border-color: #b45309;
            background: #ffffff;
        }

        .swap-card-title {
            font-size: 0.76rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .swap-divider-icon {
            width: 38px;
            height: 38px;
            border-radius: 50%;
            background: var(--gm-primary);
            color: #f3efe6;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.1rem;
            margin: 0 auto;
            box-shadow: 0 4px 10px rgba(31, 107, 74, 0.25);
            flex-shrink: 0;
        }

        .alert-box {
            padding: 10px 14px;
            border-radius: 8px;
            font-size: 0.82rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .alert-warning {
            background: #fffbeb;
            color: #92400e;
            border: 1.5px solid #fde68a;
        }
        .alert-danger {
            background: #fef2f2;
            color: #991b1b;
            border: 1.5px solid #fecaca;
        }

        /* Toast */
        #toast-box {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
            background: #ffffff;
            border: 2px solid var(--gm-primary);
            border-radius: 10px;
            padding: 12px 18px;
            box-shadow: 0 10px 30px rgba(31, 107, 74, 0.2);
            display: none;
            align-items: center;
            gap: 10px;
            font-size: 0.88rem;
            font-weight: 800;
            color: var(--gm-primary);
            animation: fadeIn 0.2s ease;
        }
        #toast-box.error {
            border-color: #dc2626;
            color: #dc2626;
        }

        /* Print Specific CSS */
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
            .nurse-sidebar, .top-navbar, .header-toolbar, .controls-card, .actions-group, th:last-child { display: none !important; }
            .content-wrapper { padding: 0 !important; margin: 0 !important; }
            th { background: #f3efe6 !important; -webkit-print-color-adjust: exact; }
            .badge-shift { border: 1px solid #ccc; background: transparent !important; color: #000 !important; }
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
                                <h1>Duty Shift Rosters & Allocation Feed</h1>
                                <p>Interactive nurse duty directory with 1-click filters, live search & duty management.</p>
                            </div>
                        </div>
                        
                        <div class="header-actions">
                            <button class="btn btn-swap-main" onclick="openSwapModal()">
                                <i class="fas fa-exchange-alt"></i> Quick Swap Matrix
                            </button>
                            <a href="shift_assignment.php" class="btn btn-primary">
                                <i class="fas fa-plus-circle"></i> Assign New Shift
                            </a>
                            <button class="btn" onclick="window.print()">
                                <i class="fas fa-print"></i> Print
                            </button>
                            <button class="btn btn-primary btn-pdf" onclick="exportPDF()">
                                <i class="fas fa-file-pdf"></i> Export PDF
                            </button>
                        </div>
                    </div>

                    <!-- ── Interactive Control Strip (Segmented Tabs + Quick Filters) ── -->
                    <div class="controls-card">
                        <!-- Shift Type Segmented Tabs -->
                        <div class="tabs-row">
                            <div class="segmented-tabs">
                                <button type="button" class="tab-btn active" data-shift-tab="" onclick="setShiftTab('')">
                                    <i class="fas fa-layer-group"></i> All Rosters 
                                    <span class="tab-count"><?php echo $totalAssignments; ?></span>
                                </button>
                                <button type="button" class="tab-btn" data-shift-tab="morning" onclick="setShiftTab('morning')">
                                    <i class="fas fa-sun"></i> Morning (M) 
                                    <span class="tab-count"><?php echo $morningCount; ?></span>
                                </button>
                                <button type="button" class="tab-btn" data-shift-tab="evening" onclick="setShiftTab('evening')">
                                    <i class="fas fa-cloud-sun"></i> Evening (E) 
                                    <span class="tab-count"><?php echo $eveningCount; ?></span>
                                </button>
                                <button type="button" class="tab-btn" data-shift-tab="night" onclick="setShiftTab('night')">
                                    <i class="fas fa-moon"></i> Night (N) 
                                    <span class="tab-count"><?php echo $nightCount; ?></span>
                                </button>
                                <button type="button" class="tab-btn" data-shift-tab="week off" onclick="setShiftTab('week off')">
                                    <i class="fas fa-coffee"></i> Week Off 
                                    <span class="tab-count"><?php echo $offCount; ?></span>
                                </button>
                            </div>

                            <span id="record-count" style="font-size:0.8rem; font-weight:700; color:var(--gm-primary); background:var(--gm-bg); padding:4px 10px; border-radius:8px; border:1px solid var(--gm-border);">
                                Showing <?php echo count($allShifts); ?> Assignments
                            </span>
                        </div>

                        <!-- Live Filters Inline -->
                        <div class="filters-inline">
                            <div class="search-box-wrap">
                                <i class="fas fa-search"></i>
                                <input type="text" id="filterNurse" class="search-input" placeholder="Search nurse name, ward, room..." onkeyup="applyFilters()">
                            </div>

                            <select id="filterFloor" class="filter-select" onchange="applyFilters()">
                                <option value="">All Floors</option>
                                <?php foreach($floors as $f): ?>
                                    <option value="<?php echo htmlspecialchars($f); ?>"><?php echo htmlspecialchars($f); ?></option>
                                <?php endforeach; ?>
                            </select>

                            <select id="filterWard" class="filter-select" onchange="applyFilters()">
                                <option value="">All Wards</option>
                                <?php foreach($wards as $w): ?>
                                    <option value="<?php echo htmlspecialchars($w); ?>"><?php echo htmlspecialchars($w); ?></option>
                                <?php endforeach; ?>
                            </select>

                            <button type="button" class="btn" onclick="resetFilters()" title="Reset All Filters" style="padding:6px 12px; height:36px;">
                                <i class="fas fa-undo"></i> Reset
                            </button>
                        </div>
                    </div>

                    <!-- ── Master Rosters Feed Table (Clean, Compact, No-Scroll) ── -->
                    <div class="roster-card" id="printableArea">
                        <div class="roster-header">
                            <span><i class="fas fa-clipboard-list"></i> Shift Assignments Roster Feed</span>
                            <span style="font-size:0.75rem; opacity:0.9;"><i class="fas fa-shield-alt"></i> Active Clinical Roster</span>
                        </div>
                        
                        <table class="roster-table" id="shiftTable">
                            <thead>
                                <tr>
                                    <th style="width: 25%;">Duty Staff Nurse</th>
                                    <th style="width: 25%;">Floor & Station Location</th>
                                    <th style="width: 20%;">Shift Duty & Timings</th>
                                    <th style="width: 18%;">Schedule Date Range</th>
                                    <th style="width: 12%; text-align:center;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if(empty($allShifts)): ?>
                                    <tr id="empty-row">
                                        <td colspan="5" style="text-align:center; padding:45px 20px; color:var(--gm-text-muted);">
                                            <i class="fas fa-calendar-times" style="font-size: 2.2rem; opacity: 0.35; margin-bottom: 10px; display: block;"></i>
                                            No active shift assignments found matching criteria.
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach($allShifts as $idx => $shift): 
                                        $type = strtolower($shift['shift_type'] ?? '');
                                        $badgeClass = 'badge-morning';
                                        $shiftIcon = 'fa-sun';
                                        $shiftTime = '06:00 AM - 02:00 PM';

                                        if(strpos($type, 'evening') !== false) {
                                            $badgeClass = 'badge-evening';
                                            $shiftIcon = 'fa-cloud-sun';
                                            $shiftTime = '02:00 PM - 10:00 PM';
                                        } elseif(strpos($type, 'night') !== false) {
                                            $badgeClass = 'badge-night';
                                            $shiftIcon = 'fa-moon';
                                            $shiftTime = '10:00 PM - 06:00 AM';
                                        } elseif(strpos($type, 'week off') !== false || strpos($type, 'off') !== false) {
                                            $badgeClass = 'badge-weekoff';
                                            $shiftIcon = 'fa-coffee';
                                            $shiftTime = 'Weekly Rest Day';
                                        }
                                        
                                        $initials = strtoupper(substr($shift['nurse_name'], 0, 2));
                                    ?>
                                        <tr class="shift-row" id="row-assign-<?php echo $idx; ?>"
                                            data-index="<?php echo $idx; ?>"
                                            data-nurse="<?php echo htmlspecialchars(strtolower($shift['nurse_name'])); ?>"
                                            data-floor="<?php echo htmlspecialchars($shift['floor_name']); ?>"
                                            data-ward="<?php echo htmlspecialchars($shift['ward_name']); ?>"
                                            data-room="<?php echo htmlspecialchars($shift['room_type']); ?>"
                                            data-type="<?php echo htmlspecialchars($type); ?>">
                                            
                                            <!-- Nurse Profile -->
                                            <td>
                                                <div class="nurse-cell">
                                                    <div class="nurse-avatar"><?php echo $initials; ?></div>
                                                    <div>
                                                        <div class="nurse-name"><?php echo htmlspecialchars($shift['nurse_name']); ?></div>
                                                        <div class="nurse-sub"><i class="fas fa-user-nurse" style="font-size:10px;"></i> Staff Nurse</div>
                                                    </div>
                                                </div>
                                            </td>

                                            <!-- Location & Station -->
                                            <td>
                                                <div class="location-cell">
                                                    <div class="location-floor"><?php echo htmlspecialchars($shift['floor_name']); ?></div>
                                                    <div class="location-ward">
                                                        <i class="fas fa-hospital-alt" style="font-size:10px; opacity:0.7;"></i> 
                                                        <span><?php echo htmlspecialchars($shift['ward_name']); ?> &bull; <strong><?php echo htmlspecialchars($shift['room_type']); ?></strong></span>
                                                    </div>
                                                </div>
                                            </td>

                                            <!-- Shift & Timing -->
                                            <td>
                                                <div class="shift-cell">
                                                    <span class="badge-shift <?php echo $badgeClass; ?>">
                                                        <i class="fas <?php echo $shiftIcon; ?>"></i> <?php echo htmlspecialchars($shift['shift_type']); ?>
                                                    </span>
                                                    <span class="shift-timing"><?php echo $shiftTime; ?></span>
                                                </div>
                                            </td>

                                            <!-- Date Range -->
                                            <td>
                                                <div class="date-cell">
                                                    <div class="date-main">
                                                        <i class="far fa-calendar-alt" style="font-size:11px; color:var(--gm-text-muted);"></i>
                                                        <span><?php echo date('d M', strtotime($shift['start_date'])); ?> &ndash; <?php echo date('d M, Y', strtotime($shift['end_date'])); ?></span>
                                                    </div>
                                                    <div class="date-chip">
                                                        <i class="fas fa-clock" style="font-size:9px;"></i> <?php echo $shift['days_count']; ?> days rotation
                                                    </div>
                                                </div>
                                            </td>

                                            <!-- Actions -->
                                            <td style="text-align:center;">
                                                <div class="actions-group">
                                                    <button type="button" class="btn-act btn-act-edit" title="Edit / Reassign Shift" onclick="openEditModal(<?php echo $idx; ?>)">
                                                        <i class="fas fa-pen"></i>
                                                    </button>
                                                    <button type="button" class="btn-act btn-act-swap" title="Swap Nurse Duty" onclick="openSwapModal(<?php echo $idx; ?>)">
                                                        <i class="fas fa-exchange-alt"></i>
                                                    </button>
                                                    <button type="button" class="btn-act btn-act-del" title="Delete Assignment" onclick="openDeleteModal(<?php echo $idx; ?>)">
                                                        <i class="fas fa-trash-alt"></i>
                                                    </button>
                                                </div>
                                            </td>
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

    <!-- ══════════════════════════════════════════════════════════
         MODAL 1: EDIT & REASSIGN SHIFT ASSIGNMENT
         ══════════════════════════════════════════════════════════ -->
    <div class="modal-overlay" id="modalEdit">
        <div class="modal-card">
            <div class="modal-head">
                <h3><i class="fas fa-edit"></i> Edit & Reassign Shift Duty</h3>
                <button type="button" class="modal-close" onclick="closeModal('modalEdit')">&times;</button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="editScheduleId">
                <input type="hidden" id="editOldNurseId">
                <input type="hidden" id="editOldShiftType">

                <!-- Nurse selection -->
                <div class="form-group">
                    <label><i class="fas fa-user-nurse"></i> Assigned Staff Nurse <span style="color:#dc2626;">*</span></label>
                    <select id="editNurseSelect" class="form-control">
                        <!-- Populated via JS -->
                    </select>
                </div>

                <!-- Shift Type & Duty Timings -->
                <div class="form-group">
                    <label><i class="fas fa-clock"></i> Duty Shift & Timings <span style="color:#dc2626;">*</span></label>
                    <div class="shift-opt-group">
                        <button type="button" class="shift-opt-btn" data-shift="Morning" onclick="selectEditShift('Morning')">
                            <i class="fas fa-sun"></i> Morning
                            <span class="shift-opt-time">06:00 AM - 02:00 PM</span>
                        </button>
                        <button type="button" class="shift-opt-btn" data-shift="Evening" onclick="selectEditShift('Evening')">
                            <i class="fas fa-cloud-sun"></i> Evening
                            <span class="shift-opt-time">02:00 PM - 10:00 PM</span>
                        </button>
                        <button type="button" class="shift-opt-btn" data-shift="Night" onclick="selectEditShift('Night')">
                            <i class="fas fa-moon"></i> Night
                            <span class="shift-opt-time">10:00 PM - 06:00 AM</span>
                        </button>
                        <button type="button" class="shift-opt-btn" data-shift="Week Off" onclick="selectEditShift('Week Off')">
                            <i class="fas fa-coffee"></i> Week Off
                            <span class="shift-opt-time">Weekly Rest Day</span>
                        </button>
                    </div>
                    <input type="hidden" id="editSelectedShiftType" value="Morning">
                </div>

                <!-- Location / Ward Tree -->
                <div class="form-grid-2">
                    <div class="form-group">
                        <label><i class="fas fa-layer-group"></i> Hospital Floor <span style="color:#dc2626;">*</span></label>
                        <select id="editFloorSelect" class="form-control" onchange="onEditFloorChange()">
                            <!-- Populated via JS -->
                        </select>
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-hospital-alt"></i> Ward Name <span style="color:#dc2626;">*</span></label>
                        <select id="editWardSelect" class="form-control" onchange="onEditWardChange()">
                            <!-- Populated via JS -->
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label><i class="fas fa-bed"></i> Room Type / Clinical Area <span style="color:#dc2626;">*</span></label>
                    <select id="editRoomSelect" class="form-control">
                        <!-- Populated via JS -->
                    </select>
                </div>

                <!-- Active Dates Checklist -->
                <div class="form-group">
                    <div style="display:flex; justify-content:space-between; align-items:center;">
                        <label><i class="far fa-calendar-check"></i> Assigned Dates</label>
                        <button type="button" class="btn" style="padding:2px 8px; min-height:22px; font-size:0.7rem;" onclick="toggleAllEditDays()">Toggle All</button>
                    </div>
                    <div class="days-checklist" id="editDaysContainer">
                        <!-- Checkboxes populated via JS -->
                    </div>
                </div>

                <div id="editAlertBox" class="alert-box alert-danger" style="display:none;"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn" onclick="closeModal('modalEdit')">Cancel</button>
                <button type="button" class="btn btn-primary" id="btnSaveEdit" onclick="submitEditAssignment()">
                    <i class="fas fa-save"></i> Save Changes
                </button>
            </div>
        </div>
    </div>

    <!-- ══════════════════════════════════════════════════════════
         MODAL 2: ADVANCED NURSE SWAP MATRIX
         ══════════════════════════════════════════════════════════ -->
    <div class="modal-overlay" id="modalSwap">
        <div class="modal-card modal-card-large">
            <div class="modal-head" style="background:#b45309;">
                <h3><i class="fas fa-exchange-alt"></i> Advanced Nurse Duty Swap Matrix</h3>
                <button type="button" class="modal-close" onclick="closeModal('modalSwap')">&times;</button>
            </div>
            <div class="modal-body">
                <p style="font-size:0.84rem; color:var(--gm-text-muted); font-weight:600;">
                    Easily swap nurse assignments between different shifts, floors, or wards with 1-click atomic database synchronization.
                </p>

                <!-- Side by Side Swap Cards -->
                <div class="swap-matrix-container">
                    <!-- Source Card (Nurse A) -->
                    <div class="swap-card source-card" id="swapSourceCard">
                        <div class="swap-card-title" style="color:var(--gm-primary);">
                            <span><i class="fas fa-user-nurse"></i> Assignment A (Source)</span>
                            <span class="badge-shift badge-morning" id="srcShiftBadge">Morning</span>
                        </div>
                        <div style="display:flex; align-items:center; gap:10px;">
                            <div class="nurse-avatar" id="srcAvatar">NA</div>
                            <div>
                                <h4 id="srcNurseName" style="color:var(--gm-primary); font-size:0.95rem; font-weight:800;">Nurse Name</h4>
                                <span id="srcNurseRole" style="font-size:0.74rem; color:var(--gm-text-muted); font-weight:600;">Staff Nurse</span>
                            </div>
                        </div>
                        <div style="font-size:0.78rem; line-height:1.5; border-top:1px solid var(--gm-border); padding-top:6px;">
                            <div><strong style="color:var(--gm-primary);">Floor:</strong> <span id="srcFloor">Floor</span></div>
                            <div><strong style="color:var(--gm-primary);">Ward:</strong> <span id="srcWard">Ward</span> &bull; <span id="srcRoom">Room</span></div>
                            <div><strong style="color:var(--gm-primary);">Dates:</strong> <span id="srcDates">Range</span></div>
                        </div>
                        <input type="hidden" id="swapSrcScheduleId">
                        <input type="hidden" id="swapSrcNurseId">
                        <input type="hidden" id="swapSrcShiftType">
                    </div>

                    <!-- Swap Arrow Divider -->
                    <div class="swap-divider-icon">
                        <i class="fas fa-exchange-alt"></i>
                    </div>

                    <!-- Target Card (Nurse B) -->
                    <div class="swap-card target-card">
                        <div class="swap-card-title" style="color:#b45309;">
                            <span><i class="fas fa-user-nurse"></i> Assignment B (Target)</span>
                            <span class="badge-shift badge-evening" id="tgtShiftBadge">Select</span>
                        </div>
                        
                        <div class="form-group" style="margin:0;">
                            <label style="font-size:0.7rem; color:#b45309;">Select Assignment to Swap With:</label>
                            <select id="swapTargetSelect" class="form-control" onchange="onSwapTargetChange()" style="border-color:#b45309; font-size:0.8rem;">
                                <option value="">-- Choose Nurse / Shift --</option>
                                <!-- Populated dynamically -->
                            </select>
                        </div>

                        <div id="tgtPreviewDetails" style="font-size:0.78rem; line-height:1.5; border-top:1px solid var(--gm-border); padding-top:6px; display:none;">
                            <div><strong style="color:#b45309;">Floor:</strong> <span id="tgtFloor">-</span></div>
                            <div><strong style="color:#b45309;">Ward:</strong> <span id="tgtWard">-</span> &bull; <span id="tgtRoom">-</span></div>
                            <div><strong style="color:#b45309;">Dates:</strong> <span id="tgtDates">-</span></div>
                        </div>

                        <input type="hidden" id="swapTgtScheduleId">
                        <input type="hidden" id="swapTgtNurseId">
                        <input type="hidden" id="swapTgtShiftType">
                    </div>
                </div>

                <div class="alert-box alert-warning" id="swapSummaryAlert">
                    <i class="fas fa-info-circle"></i>
                    <span id="swapSummaryText">Select a target nurse assignment on the right to preview the swap.</span>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn" onclick="closeModal('modalSwap')">Cancel</button>
                <button type="button" class="btn btn-swap-main" id="btnConfirmSwap" onclick="submitSwapAssignments()" disabled>
                    <i class="fas fa-check-double"></i> Confirm & Execute Swap
                </button>
            </div>
        </div>
    </div>

    <!-- ══════════════════════════════════════════════════════════
         MODAL 3: DELETE SHIFT ASSIGNMENT CONFIRMATION
         ══════════════════════════════════════════════════════════ -->
    <div class="modal-overlay" id="modalDelete">
        <div class="modal-card" style="max-width:460px; border-color:#dc2626;">
            <div class="modal-head" style="background:#dc2626;">
                <h3><i class="fas fa-trash-alt"></i> Delete Shift Assignment</h3>
                <button type="button" class="modal-close" onclick="closeModal('modalDelete')">&times;</button>
            </div>
            <div class="modal-body" style="text-align:center; padding:24px 20px;">
                <div style="width:50px; height:50px; border-radius:50%; background:rgba(220,38,38,0.12); color:#dc2626; display:flex; align-items:center; justify-content:center; font-size:1.5rem; margin:0 auto 12px auto;">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>

                <h3 style="color:#dc2626; font-weight:800; font-size:1.1rem; margin-bottom:6px;">Are you sure?</h3>
                <p style="color:var(--gm-text-body); font-size:0.86rem; font-weight:600; line-height:1.5; margin-bottom:14px;" id="delConfirmText">
                    This will permanently remove the shift assignment.
                </p>

                <div style="background:var(--gm-bg); border:1.5px solid var(--gm-border); border-radius:8px; padding:10px 12px; text-align:left; font-size:0.8rem; font-weight:700; color:var(--gm-primary); line-height:1.5;" id="delDetailsBox">
                </div>

                <input type="hidden" id="delScheduleId">
                <input type="hidden" id="delNurseId">
                <input type="hidden" id="delShiftType">
            </div>
            <div class="modal-footer" style="justify-content:center; gap:10px;">
                <button type="button" class="btn" onclick="closeModal('modalDelete')" style="min-width:100px;">Cancel</button>
                <button type="button" class="btn" style="background:#dc2626; color:#ffffff; border-color:#dc2626; min-width:130px;" id="btnConfirmDelete" onclick="submitDeleteAssignment()">
                    <i class="fas fa-trash-alt"></i> Yes, Delete
                </button>
            </div>
        </div>
    </div>

    <!-- Toast Notification -->
    <div id="toast-box">
        <i class="fas fa-check-circle"></i>
        <span id="toast-msg">Action completed successfully</span>
    </div>

    <script>
        const ALL_SHIFTS = <?php echo $allShiftsJson; ?>;
        const ALL_NURSES = <?php echo $allNursesJson; ?>;
        const WARD_TREE  = <?php echo $wardHierarchyJson; ?>;
        const API_URL    = 'api/manage_shift_assignment.php';

        let currentAssignments = [...ALL_SHIFTS];
        let currentShiftTab = '';

        // ── TAB SWITCHER ──
        function setShiftTab(shiftType) {
            currentShiftTab = shiftType.toLowerCase().trim();
            document.querySelectorAll('.tab-btn').forEach(btn => {
                const btnTab = (btn.getAttribute('data-shift-tab') || '').toLowerCase().trim();
                btn.classList.toggle('active', btnTab === currentShiftTab);
            });
            applyFilters();
        }

        // ── LIVE FILTERS ──
        function applyFilters() {
            let fNurse = (document.getElementById('filterNurse')?.value || '').toLowerCase().trim();
            let fFloor = (document.getElementById('filterFloor')?.value || '').toLowerCase().trim();
            let fWard  = (document.getElementById('filterWard')?.value || '').toLowerCase().trim();
            
            let rows = document.querySelectorAll("#shiftTable tbody .shift-row");
            let visibleCount = 0;

            rows.forEach(tr => {
                let rNurse = (tr.getAttribute('data-nurse') || '').toLowerCase();
                let rFloor = (tr.getAttribute('data-floor') || '').toLowerCase();
                let rWard  = (tr.getAttribute('data-ward') || '').toLowerCase();
                let rRoom  = (tr.getAttribute('data-room') || '').toLowerCase();
                let rType  = (tr.getAttribute('data-type') || '').toLowerCase();

                let show = true;
                
                // Segment tab filter
                if (currentShiftTab && !rType.includes(currentShiftTab)) show = false;

                // Search query filter (matches nurse, ward, floor, or room)
                if (fNurse && !(rNurse.includes(fNurse) || rWard.includes(fNurse) || rFloor.includes(fNurse) || rRoom.includes(fNurse))) {
                    show = false;
                }

                // Dropdown filters
                if (fFloor && rFloor !== fFloor) show = false;
                if (fWard && rWard !== fWard) show = false;
                
                tr.style.display = show ? "" : "none";
                if(show) visibleCount++;
            });

            const countBadge = document.getElementById('record-count');
            if(countBadge) {
                countBadge.textContent = `Showing ${visibleCount} Assignments`;
            }

            const emptyRow = document.getElementById('empty-row');
            if (emptyRow) {
                emptyRow.style.display = (visibleCount === 0) ? "" : "none";
            }
        }

        function resetFilters() {
            document.getElementById('filterNurse').value = '';
            document.getElementById('filterFloor').value = '';
            document.getElementById('filterWard').value = '';
            setShiftTab('');
        }

        // ── MODAL HELPERS ──
        function openModal(id) {
            const el = document.getElementById(id);
            if(el) el.classList.add('open');
        }

        function closeModal(id) {
            const el = document.getElementById(id);
            if(el) el.classList.remove('open');
        }

        function showToast(msg, isErr=false) {
            const t = document.getElementById('toast-box');
            const icon = t.querySelector('i');
            const txt = document.getElementById('toast-msg');
            
            t.className = isErr ? 'error' : '';
            icon.className = isErr ? 'fas fa-exclamation-circle' : 'fas fa-check-circle';
            txt.textContent = msg;
            
            t.style.display = 'flex';
            clearTimeout(t._timer);
            t._timer = setTimeout(() => { t.style.display = 'none'; }, 3500);
        }

        // ─────────────────────────────────────────────────────────────
        // 1. EDIT / REASSIGN MODAL LOGIC
        // ─────────────────────────────────────────────────────────────
        function openEditModal(idx) {
            const item = currentAssignments[idx];
            if (!item) return;

            document.getElementById('editScheduleId').value = item.schedule_id;
            document.getElementById('editOldNurseId').value = item.nurse_id;
            document.getElementById('editOldShiftType').value = item.shift_type;
            document.getElementById('editAlertBox').style.display = 'none';

            // Populate Nurse Dropdown
            const nurseSelect = document.getElementById('editNurseSelect');
            nurseSelect.innerHTML = ALL_NURSES.map(n => 
                `<option value="${n.id}" ${n.id == item.nurse_id ? 'selected' : ''}>${n.name} (${n.role}) ${n.status === 'On Leave' ? '— [ON LEAVE]' : ''}</option>`
            ).join('');

            // Shift Type
            selectEditShift(item.shift_type);

            // Populate Floors
            const floorSelect = document.getElementById('editFloorSelect');
            const uniqueFloors = [...new Set(WARD_TREE.map(w => w.floor_name))];
            floorSelect.innerHTML = uniqueFloors.map(f => 
                `<option value="${f}" ${f === item.floor_name ? 'selected' : ''}>${f}</option>`
            ).join('');

            onEditFloorChange(item.ward_name, item.room_type);

            // Populate Days Checklist
            const daysContainer = document.getElementById('editDaysContainer');
            const days = item.days || [];
            daysContainer.innerHTML = days.map((d, i) => `
                <label class="day-check-item">
                    <input type="checkbox" class="edit-day-cb" value="${d}" checked>
                    <span>${formatDateShort(d)}</span>
                </label>
            `).join('');

            openModal('modalEdit');
        }

        function selectEditShift(shiftType) {
            document.getElementById('editSelectedShiftType').value = shiftType;
            document.querySelectorAll('.shift-opt-btn').forEach(btn => {
                btn.classList.toggle('active', btn.getAttribute('data-shift') === shiftType);
            });
        }

        function onEditFloorChange(selectedWard=null, selectedRoom=null) {
            const floor = document.getElementById('editFloorSelect').value;
            const wardSelect = document.getElementById('editWardSelect');
            
            const matchingWards = [...new Set(WARD_TREE.filter(w => w.floor_name === floor).map(w => w.ward_name))];
            wardSelect.innerHTML = matchingWards.map(w => 
                `<option value="${w}" ${w === selectedWard ? 'selected' : ''}>${w}</option>`
            ).join('');

            onEditWardChange(selectedRoom);
        }

        function onEditWardChange(selectedRoom=null) {
            const floor = document.getElementById('editFloorSelect').value;
            const ward  = document.getElementById('editWardSelect').value;
            const roomSelect = document.getElementById('editRoomSelect');

            const matchingRooms = WARD_TREE.filter(w => w.floor_name === floor && w.ward_name === ward).map(w => w.room_type);
            roomSelect.innerHTML = matchingRooms.map(r => 
                `<option value="${r}" ${r === selectedRoom ? 'selected' : ''}>${r}</option>`
            ).join('');
        }

        function toggleAllEditDays() {
            const cbs = document.querySelectorAll('.edit-day-cb');
            const allChecked = Array.from(cbs).every(cb => cb.checked);
            cbs.forEach(cb => cb.checked = !allChecked);
        }

        async function submitEditAssignment() {
            const scheduleId = parseInt(document.getElementById('editScheduleId').value);
            const oldNurseId = document.getElementById('editOldNurseId').value;
            const oldShiftType = document.getElementById('editOldShiftType').value;

            const newNurseId = document.getElementById('editNurseSelect').value;
            const newShiftType = document.getElementById('editSelectedShiftType').value;
            const newFloor = document.getElementById('editFloorSelect').value;
            const newWard  = document.getElementById('editWardSelect').value;
            const newRoom  = document.getElementById('editRoomSelect').value;

            const checkedDays = Array.from(document.querySelectorAll('.edit-day-cb:checked')).map(cb => cb.value);

            if (checkedDays.length === 0) {
                const alertBox = document.getElementById('editAlertBox');
                alertBox.innerHTML = '<i class="fas fa-exclamation-circle"></i> Please select at least one active date for the assignment.';
                alertBox.style.display = 'flex';
                return;
            }

            const btn = document.getElementById('btnSaveEdit');
            const origHTML = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
            btn.disabled = true;

            try {
                const res = await fetch(API_URL + '?action=edit_assignment', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        schedule_id: scheduleId,
                        old_nurse_id: oldNurseId,
                        old_shift_type: oldShiftType,
                        new_nurse_id: newNurseId,
                        new_shift_type: newShiftType,
                        new_floor_name: newFloor,
                        new_ward_name: newWard,
                        new_room_type: newRoom,
                        dates: checkedDays
                    })
                });
                const json = await res.json();

                if (json.success) {
                    showToast(json.message);
                    closeModal('modalEdit');
                    setTimeout(() => window.location.reload(), 700);
                } else {
                    const alertBox = document.getElementById('editAlertBox');
                    alertBox.innerHTML = `<i class="fas fa-exclamation-circle"></i> ${json.message}`;
                    alertBox.style.display = 'flex';
                }
            } catch (err) {
                showToast('Network error while saving assignment', true);
            } finally {
                btn.innerHTML = origHTML;
                btn.disabled = false;
            }
        }

        // ─────────────────────────────────────────────────────────────
        // 2. ADVANCED SWAP MATRIX LOGIC
        // ─────────────────────────────────────────────────────────────
        function openSwapModal(preSelectedIdx=null) {
            const targetSelect = document.getElementById('swapTargetSelect');
            const btnConfirm = document.getElementById('btnConfirmSwap');
            btnConfirm.disabled = true;

            let sourceItem = null;
            if (preSelectedIdx !== null && currentAssignments[preSelectedIdx]) {
                sourceItem = currentAssignments[preSelectedIdx];
            } else if (currentAssignments.length > 0) {
                sourceItem = currentAssignments[0];
            }

            if (!sourceItem) {
                showToast('No shift assignments available to swap.', true);
                return;
            }

            // Fill Source Details
            document.getElementById('swapSrcScheduleId').value = sourceItem.schedule_id;
            document.getElementById('swapSrcNurseId').value = sourceItem.nurse_id;
            document.getElementById('swapSrcShiftType').value = sourceItem.shift_type;

            document.getElementById('srcNurseName').textContent = sourceItem.nurse_name;
            document.getElementById('srcAvatar').textContent = sourceItem.nurse_name.substring(0, 2).toUpperCase();
            document.getElementById('srcShiftBadge').textContent = sourceItem.shift_type;
            document.getElementById('srcFloor').textContent = sourceItem.floor_name;
            document.getElementById('srcWard').textContent = sourceItem.ward_name;
            document.getElementById('srcRoom').textContent = sourceItem.room_type;
            document.getElementById('srcDates').textContent = `${formatDateShort(sourceItem.start_date)} - ${formatDateShort(sourceItem.end_date)} (${sourceItem.days_count} days)`;

            // Populate Target Dropdown with other shift assignments
            targetSelect.innerHTML = '<option value="">-- Choose Target Assignment --</option>' + 
                currentAssignments.map((item, idx) => {
                    const isSelf = (item.schedule_id === sourceItem.schedule_id && item.nurse_id === sourceItem.nurse_id && item.shift_type === sourceItem.shift_type);
                    if (isSelf) return '';
                    return `<option value="${idx}">${item.nurse_name} &bull; ${item.shift_type} &bull; ${item.floor_name} (${item.room_type})</option>`;
                }).join('');

            document.getElementById('tgtPreviewDetails').style.display = 'none';
            document.getElementById('tgtShiftBadge').textContent = 'Select';
            document.getElementById('swapSummaryText').textContent = `Choose an assignment on the right to swap duties with ${sourceItem.nurse_name}.`;

            openModal('modalSwap');
        }

        function onSwapTargetChange() {
            const idx = document.getElementById('swapTargetSelect').value;
            const btnConfirm = document.getElementById('btnConfirmSwap');
            const previewBox = document.getElementById('tgtPreviewDetails');
            const summaryText = document.getElementById('swapSummaryText');

            if (idx === '') {
                previewBox.style.display = 'none';
                document.getElementById('tgtShiftBadge').textContent = 'Select';
                btnConfirm.disabled = true;
                return;
            }

            const tgt = currentAssignments[parseInt(idx)];
            if (!tgt) return;

            document.getElementById('swapTgtScheduleId').value = tgt.schedule_id;
            document.getElementById('swapTgtNurseId').value = tgt.nurse_id;
            document.getElementById('swapTgtShiftType').value = tgt.shift_type;

            document.getElementById('tgtShiftBadge').textContent = tgt.shift_type;
            document.getElementById('tgtFloor').textContent = tgt.floor_name;
            document.getElementById('tgtWard').textContent = tgt.ward_name;
            document.getElementById('tgtRoom').textContent = tgt.room_type;
            document.getElementById('tgtDates').textContent = `${formatDateShort(tgt.start_date)} - ${formatDateShort(tgt.end_date)} (${tgt.days_count} days)`;
            previewBox.style.display = 'block';

            const srcName = document.getElementById('srcNurseName').textContent;
            summaryText.innerHTML = `<strong>Swap Plan:</strong> <strong>${srcName}</strong> will take <em>${tgt.floor_name} (${tgt.shift_type})</em> &bull; <strong>${tgt.nurse_name}</strong> will take <em>${document.getElementById('srcFloor').textContent} (${document.getElementById('srcShiftBadge').textContent})</em>.`;
            btnConfirm.disabled = false;
        }

        async function submitSwapAssignments() {
            const srcSchedId = parseInt(document.getElementById('swapSrcScheduleId').value);
            const srcNurseId = document.getElementById('swapSrcNurseId').value;
            const srcShiftType = document.getElementById('swapSrcShiftType').value;

            const tgtSchedId = parseInt(document.getElementById('swapTgtScheduleId').value);
            const tgtNurseId = document.getElementById('swapTgtNurseId').value;
            const tgtShiftType = document.getElementById('swapTgtShiftType').value;

            const btn = document.getElementById('btnConfirmSwap');
            const origHTML = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Swapping Duties...';
            btn.disabled = true;

            try {
                const res = await fetch(API_URL + '?action=swap_assignments', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        source: { schedule_id: srcSchedId, nurse_id: srcNurseId, shift_type: srcShiftType },
                        target: { schedule_id: tgtSchedId, nurse_id: tgtNurseId, shift_type: tgtShiftType }
                    })
                });
                const json = await res.json();

                if (json.success) {
                    showToast(json.message);
                    closeModal('modalSwap');
                    setTimeout(() => window.location.reload(), 700);
                } else {
                    showToast(json.message, true);
                }
            } catch (err) {
                showToast('Network error while swapping assignments', true);
            } finally {
                btn.innerHTML = origHTML;
                btn.disabled = false;
            }
        }

        // ─────────────────────────────────────────────────────────────
        // 3. DELETE MODAL LOGIC
        // ─────────────────────────────────────────────────────────────
        function openDeleteModal(idx) {
            const item = currentAssignments[idx];
            if (!item) return;

            document.getElementById('delScheduleId').value = item.schedule_id;
            document.getElementById('delNurseId').value = item.nurse_id;
            document.getElementById('delShiftType').value = item.shift_type;

            document.getElementById('delConfirmText').innerHTML = `Are you sure you want to delete shift assignment for <strong>${item.nurse_name}</strong>?`;
            document.getElementById('delDetailsBox').innerHTML = `
                <div>&bull; Shift: <strong>${item.shift_type}</strong></div>
                <div>&bull; Floor: <strong>${item.floor_name}</strong> &bull; Ward: <strong>${item.ward_name}</strong> (${item.room_type})</div>
                <div>&bull; Dates: <strong>${formatDateShort(item.start_date)} to ${formatDateShort(item.end_date)}</strong> (${item.days_count} days)</div>
            `;

            openModal('modalDelete');
        }

        async function submitDeleteAssignment() {
            const scheduleId = parseInt(document.getElementById('delScheduleId').value);
            const nurseId = document.getElementById('delNurseId').value;
            const shiftType = document.getElementById('delShiftType').value;

            const btn = document.getElementById('btnConfirmDelete');
            const origHTML = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Deleting...';
            btn.disabled = true;

            try {
                const res = await fetch(API_URL + '?action=delete_assignment', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        schedule_id: scheduleId,
                        nurse_id: nurseId,
                        shift_type: shiftType
                    })
                });
                const json = await res.json();

                if (json.success) {
                    showToast(json.message);
                    closeModal('modalDelete');
                    setTimeout(() => window.location.reload(), 700);
                } else {
                    showToast(json.message, true);
                }
            } catch (err) {
                showToast('Network error while deleting assignment', true);
            } finally {
                btn.innerHTML = origHTML;
                btn.disabled = false;
            }
        }

        // Helper
        function formatDateShort(dateStr) {
            if (!dateStr) return '';
            const d = new Date(dateStr);
            return d.toLocaleDateString('en-IN', { day: '2-digit', month: 'short' });
        }

        // PDF Export
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
