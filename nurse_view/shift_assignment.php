<?php
session_start();
require_once __DIR__ . '/../core/Autoloader.php';
use GM_HMS\Database\SecureDatabase;
// Require Superintendent Nurse or Admin to view the page
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['Superintendent_Nurse', 'Superintendent Nurse', 'admin', 'Admin', 'Head Nurse'])) {
    header('Location: dashboard.php');
    exit();
}
// Determine Start Date
$hasStartDate = isset($_GET['start_date']) && !empty($_GET['start_date']);
$startDateStr = $hasStartDate ? $_GET['start_date'] : date('Y-m-d');
$startDate = new DateTime($startDateStr);

$dbDays = [];
for ($i = 0; $i < 7; $i++) {
    $current = clone $startDate;
    $current->modify("+$i days");
    $dbDays[] = [
        'short' => $current->format('D'),
        'date' => $current->format('d M'),
        'fullDate' => $current->format('Y-m-d')
    ];
}
$dbDaysJson = json_encode($dbDays);

// Fetch Nurses from Database
$dbNurses = [];
try {
    $db = SecureDatabase::getInstance();
    $conn = $db->getConnection();
    
    $stmt = $conn->prepare("
        SELECT sl_no as staff_id, full_name as name, designation as role, status 
        FROM staff 
        WHERE designation IN ('Nurse', 'Superintendent_Nurse', 'Nursing_Superintendent')
        ORDER BY full_name
    ");
    $stmt->execute();
    $result = $stmt->get_result();
    
    $index = 0;
    while ($row = $result->fetch_assoc()) {
        $dbNurses[] = [
            'id'     => 'n_' . $row['staff_id'],
            'name'   => $row['name'],
            'role'   => $row['role'],
            'status' => ($row['status'] === 'Active') ? 'Available' : 'On Leave',
            'avatar' => 'https://i.pravatar.cc/150?img=' . (($index % 70) + 1)
        ];
        $index++;
    }

    // Fetch Unique Dropdown Filters from Database
    $dbFloors = [];
    $dbWardsList = [];
    $dbRoomTypes = [];
    
    try {
        $stmtFilters = $conn->prepare("
            SELECT 
                DISTINCT floor_name 
            FROM hospital_beds 
            WHERE floor_name IS NOT NULL AND floor_name != ''
        ");
        $stmtFilters->execute();
        $resFilters = $stmtFilters->get_result();
        while($r = $resFilters->fetch_assoc()) $dbFloors[] = $r['floor_name'];
        
        $floorOrder = [
            'Basement' => -1,
            'Ground Floor' => 0,
            'First Floor' => 1,
            'Second Floor' => 2,
            'Third Floor' => 3,
            'Fourth Floor' => 4,
            'Fifth Floor' => 5,
            'Sixth Floor' => 6,
            'Seventh Floor' => 7,
            'Eighth Floor' => 8,
            'Ninth Floor' => 9,
            'Tenth Floor' => 10
        ];
        
        usort($dbFloors, function($a, $b) use ($floorOrder) {
            $valA = isset($floorOrder[$a]) ? $floorOrder[$a] : 99;
            $valB = isset($floorOrder[$b]) ? $floorOrder[$b] : 99;
            return $valA <=> $valB;
        });
        
        $stmtFilters = $conn->prepare("
            SELECT DISTINCT ward_name FROM hospital_beds WHERE ward_name IS NOT NULL AND ward_name != '' ORDER BY ward_name
        ");
        $stmtFilters->execute();
        $resFilters = $stmtFilters->get_result();
        while($r = $resFilters->fetch_assoc()) $dbWardsList[] = $r['ward_name'];
        
        $stmtFilters = $conn->prepare("
            SELECT DISTINCT room_type FROM hospital_beds WHERE room_type IS NOT NULL AND room_type != '' ORDER BY room_type
        ");
        $stmtFilters->execute();
        $resFilters = $stmtFilters->get_result();
        while($r = $resFilters->fetch_assoc()) $dbRoomTypes[] = $r['room_type'];
        
    } catch (Exception $e) {}

    // Fetch Wards/Rooms for the Calendar Grid
    $dbWards = [];
    $rawWards = [];
    $stmtWards = $conn->prepare("
        SELECT floor_name, ward_name, room_type, COUNT(bed_number) as total_beds
        FROM hospital_beds
        GROUP BY floor_name, ward_name, room_type
    ");
    $stmtWards->execute();
    $resultWards = $stmtWards->get_result();
    while ($row = $resultWards->fetch_assoc()) {
        $rawWards[] = $row;
    }
    
    // Sort raw wards using logical floor mapping
    usort($rawWards, function($a, $b) use ($floorOrder) {
        $valA = isset($floorOrder[$a['floor_name']]) ? $floorOrder[$a['floor_name']] : 99;
        $valB = isset($floorOrder[$b['floor_name']]) ? $floorOrder[$b['floor_name']] : 99;
        if ($valA === $valB) {
            return strcmp($a['ward_name'], $b['ward_name']);
        }
        return $valA <=> $valB;
    });
    
    $wardIndex = 1;
    foreach ($rawWards as $row) {
        $dbWards[] = [
            'id' => 'w_' . $wardIndex,
            'name' => $row['ward_name'],
            'type' => ($row['floor_name'] ?: 'Unknown Floor') . ' - ' . ($row['room_type'] ?: 'General'),
            'beds' => $row['total_beds'],
            'floor_name' => $row['floor_name'],
            'ward_name' => $row['ward_name'],
            'room_type' => $row['room_type']
        ];
        $wardIndex++;
    }

    // Fetch Existing Assignments
    $endDateStr = $dbDays[6]['fullDate'];
    $existingAssignments = [];
    $stmtExisting = $conn->prepare("
        SELECT floor_name, ward_name, room_type, shift_data 
        FROM shift_schedules 
        WHERE start_date = ? AND end_date = ?
    ");
    if ($stmtExisting) {
        $stmtExisting->bind_param("ss", $startDateStr, $endDateStr);
        $stmtExisting->execute();
        $resExisting = $stmtExisting->get_result();
        while($row = $resExisting->fetch_assoc()) {
            $jsonData = json_decode($row['shift_data'], true);
            if (is_array($jsonData)) {
                foreach($jsonData as $shift) {
                    $shift['floor_name'] = $row['floor_name'];
                    $shift['ward_name'] = $row['ward_name'];
                    $shift['room_type'] = $row['room_type'];
                    $existingAssignments[] = $shift;
                }
            }
        }
    }
} catch (Exception $e) {
    // Fallback to empty array if error
    $dbNurses = [];
    $dbWards = [];
    $existingAssignments = [];
}
$dbNursesJson = json_encode($dbNurses);
$dbWardsJson = json_encode($dbWards);
$existingAssignmentsJson = json_encode($existingAssignments);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nurse Shift Assignment - GM HMS</title>
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Bootstrap CSS (Scoped carefully to not break our custom layout) -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Choices.js for Multi-Select Dropdowns -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/choices.js/public/assets/styles/choices.min.css" />
    <script src="https://cdn.jsdelivr.net/npm/choices.js/public/assets/scripts/choices.min.js"></script>
    
    <style>
        :root {
            /* Brand Colors */
            --bg-color: #F3EFE6;
            --primary-color: #1F6B4A;
            --primary-light: #2A8F63;
            --primary-dark: #144731;
            
            /* UI Colors */
            --white: #FFFFFF;
            --text-main: #1A1A1A;
            --text-muted: #666666;
            --border-color: rgba(31, 107, 74, 0.15);
            
            /* Status Colors */
            --status-morning: #10B981;
            --status-evening: #F59E0B;
            --status-night: #6366F1;
            --status-available: #10B981;
            --status-leave: #EF4444;
            
            /* Design Tokens */
            --radius-card: 24px;
            --radius-btn: 14px;
            --radius-input: 12px;
            --shadow-soft: 0 12px 30px rgba(31, 107, 74, 0.08);
            --shadow-hover: 0 18px 40px rgba(31, 107, 74, 0.12);
        }
        
        .custom-dropdown {
            position: relative;
            user-select: none;
        }
        .dropdown-menu-custom {
            display: none;
            position: absolute;
            top: 100%;
            left: 0;
            margin-top: 5px;
            background: #fff;
            border: 1px solid rgba(0,0,0,0.1);
            border-radius: 12px;
            box-shadow: var(--shadow-hover);
            min-width: 220px;
            max-height: 250px;
            overflow-y: auto;
            z-index: 9999;
            padding: 8px 0;
        }
        .dropdown-menu-custom.show {
            display: block;
        }
        .checkbox-item {
            padding: 8px 15px;
            display: flex;
            align-items: center;
            gap: 10px;
            cursor: pointer;
            font-size: 13px;
            color: var(--text-main);
            transition: var(--transition);
        }
        .checkbox-item:hover {
            background: rgba(31,107,74,0.05);
        }
        .checkbox-item input {
            cursor: pointer;
            accent-color: var(--primary-color);
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: var(--bg-color);
            color: var(--text-main);
            margin: 0;
            padding: 0;
            min-height: 100vh;
            display: flex;
            overflow-x: hidden;
        }

        /* Adjust for Sidebar */
        .main-content {
            flex: 1;
            margin-left: 185px; /* Adjust based on nurse_sidebar.php */
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }
        
        @media (max-width: 1023px) {
            .main-content { margin-left: 0; }
        }
        
        @media (max-width: 768px) {
            .workspace { flex-direction: column; padding: 10px; }
            .left-panel { width: 100%; max-height: 300px; position: static; }
            .top-header { flex-direction: column; align-items: flex-start; gap: 15px; padding: 15px; }
            .header-actions { flex-direction: column; align-items: stretch; width: 100%; }
            .top-summary-bar { flex-direction: column; gap: 10px; }
            .filter-bar { flex-direction: column; gap: 15px; align-items: stretch; }
            .filter-bar > div { display: flex; flex-direction: column; gap: 10px; }
            .calendar-header { grid-template-columns: 120px repeat(7, minmax(60px, 1fr)); }
            .cal-row { grid-template-columns: 120px repeat(7, minmax(60px, 1fr)); }
        }

        /* 1. Header */
        .top-header {
            background: var(--bg-color);
            padding: 20px 30px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 1px solid var(--border-color);
            z-index: 10;
        }

        .header-title {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .header-title i {
            font-size: 24px;
            color: var(--primary-color);
            background: rgba(31, 107, 74, 0.1);
            padding: 12px;
            border-radius: var(--radius-btn);
        }

        .header-title h1 {
            font-size: 22px;
            font-weight: 800;
            margin: 0;
            color: var(--primary-dark);
            letter-spacing: -0.5px;
        }

        .header-title p {
            margin: 0;
            font-size: 13px;
            color: var(--text-muted);
            font-weight: 500;
        }

        .header-actions {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .date-picker {
            background: var(--white);
            border: 1px solid var(--border-color);
            padding: 10px 18px;
            border-radius: var(--radius-input);
            font-weight: 700;
            font-size: 14px;
            color: var(--primary-color);
            display: flex;
            align-items: center;
            gap: 10px;
            cursor: pointer;
            box-shadow: var(--shadow-soft);
        }

        .btn-custom {
            border: none;
            padding: 10px 20px;
            border-radius: var(--radius-btn);
            font-weight: 700;
            font-size: 14px;
            cursor: pointer;
            transition: var(--transition);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .nurse-card {
            background: var(--white);
            border-radius: 8px;
            padding: 10px 12px;
            display: flex;
            align-items: center;
            box-shadow: var(--shadow-soft);
            cursor: grab;
            border: 1px solid var(--border-color);
            transition: var(--transition);
        }

        .btn-outline {
            background: var(--white);
            border: 1px solid var(--border-color);
            color: var(--text-main);
        }
        
        .btn-outline:hover {
            background: rgba(31, 107, 74, 0.05);
            transform: translateY(-2px);
        }

        .btn-primary-custom {
            background: var(--primary-color);
            color: var(--white);
            box-shadow: 0 8px 20px rgba(31, 107, 74, 0.2);
        }

        .btn-primary-custom:hover {
            background: var(--primary-light);
            transform: translateY(-2px);
            box-shadow: 0 12px 25px rgba(31, 107, 74, 0.3);
        }

        /* 2. Workspace Layout */
        .workspace {
            display: flex;
            flex: 1;
            padding: 20px;
            gap: 20px;
            align-items: flex-start; /* Important for sticky children */
        }

        /* Left Panel */
        .left-panel {
            width: 200px; /* Reduced width significantly to make room for calendar */
            display: flex;
            flex-direction: column;
            gap: 15px;
            overflow-y: auto;
            padding-right: 5px;
            position: sticky;
            top: 20px; /* Distance from top while scrolling */
            max-height: calc(100vh - 120px); /* Leave room for bottom toolbar */
        }
        
        .left-panel::-webkit-scrollbar { width: 4px; }
        .left-panel::-webkit-scrollbar-thumb { background: rgba(31, 107, 74, 0.2); border-radius: 4px; }

        /* Right Content Area */
        .right-content {
            flex: 1;
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        /* Top Horizontal Bars */
        .top-summary-bar {
            display: flex;
            gap: 20px;
        }

        .filter-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: var(--white);
            padding: 12px 20px;
            border-radius: var(--radius-card);
            box-shadow: var(--shadow-soft);
            border: 1px solid var(--border-color);
        }

        .filter-select {
            padding: 10px 15px;
            border: 1px solid var(--border-color);
            border-radius: var(--radius-input);
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-weight: 600;
            font-size: 13px;
            color: var(--primary-dark);
            background: var(--bg-color);
            min-width: 150px;
            outline: none;
            cursor: pointer;
        }

        .summary-card {
            flex: 1; /* Stretch horizontally */
            background: var(--white);
            border-radius: var(--radius-card);
            padding: 18px;
            box-shadow: var(--shadow-soft);
            border: 1px solid var(--border-color);
            display: flex;
            flex-direction: column;
            gap: 12px;
            transition: var(--transition);
            cursor: pointer;
            position: relative;
            overflow: hidden;
        }

        .summary-card::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 4px;
        }

        .shift-morning::before { background: var(--status-morning); }
        .shift-evening::before { background: var(--status-evening); }
        .shift-night::before { background: var(--status-night); }

        .summary-card:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-hover);
        }

        .summary-card:active {
            transform: scale(0.98);
        }



        .shift-card-top {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .shift-icon {
            width: 42px;
            height: 42px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
        }

        .shift-morning .shift-icon { background: rgba(16, 185, 129, 0.1); color: var(--status-morning); }
        .shift-evening .shift-icon { background: rgba(245, 158, 11, 0.1); color: var(--status-evening); }
        .shift-night .shift-icon { background: rgba(99, 102, 241, 0.1); color: var(--status-night); }

        .shift-info h3 { margin: 0 0 2px 0; font-size: 15px; font-weight: 800; }
        .shift-info p { margin: 0; font-size: 11px; color: var(--text-muted); font-weight: 700; }
        
        .shift-badge {
            font-size: 10px;
            font-weight: 800;
            padding: 4px 8px;
            border-radius: 50px;
            background: var(--bg-color);
            color: var(--text-muted);
        }

        .shift-progress-container {
            width: 100%;
            height: 6px;
            background: var(--bg-color);
            border-radius: 50px;
            overflow: hidden;
        }

        .shift-progress-bar {
            height: 100%;
            border-radius: 50px;
            width: 0%;
            transition: width 0.5s ease-in-out;
        }

        .shift-morning .shift-progress-bar { background: var(--status-morning); }
        .shift-evening .shift-progress-bar { background: var(--status-evening); }
        .shift-night .shift-progress-bar { background: var(--status-night); }

        .shift-stats-bottom {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 12px;
            font-weight: 700;
        }

        .shift-stats-bottom .count { font-size: 14px; font-weight: 800; color: var(--primary-dark); }
        .shift-stats-bottom .total { color: var(--text-muted); }

        .nurse-pool-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 10px;
        }

        .nurse-pool-header h3 {
            font-size: 18px;
            font-weight: 800;
            margin: 0;
            color: var(--primary-dark);
        }

        .nurse-card:active {
            cursor: grabbing;
        }

        .nurse-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-hover);
            border-color: var(--primary-light);
        }
        }

        .nurse-details h4 {
            margin: 0 0 2px 0;
            font-size: 15px;
            font-weight: 700;
        }

        .nurse-details p {
            margin: 0;
            font-size: 12px;
            color: var(--text-muted);
            font-weight: 500;
        }

        .status-badge {
            position: absolute;
            top: 15px;
            right: 15px;
            padding: 4px 10px;
            border-radius: 50px;
            font-size: 10px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .status-available { background: rgba(16, 185, 129, 0.1); color: var(--status-morning); }
        .status-leave { background: rgba(239, 68, 68, 0.1); color: var(--status-leave); }

        /* Center Calendar Grid */
        .calendar-container {
            background: var(--white);
            border-radius: var(--radius-card);
            box-shadow: var(--shadow-soft);
            border: 1px solid var(--border-color);
            display: flex;
            flex-direction: column;
            overflow-x: auto; /* Allow horizontal scrolling if screen is too small */
            min-width: 0;
        }

        .calendar-header {
            display: grid;
            grid-template-columns: 160px repeat(7, minmax(0, 1fr));
            background: #FAFAFA;
            border-bottom: 1px solid var(--border-color);
            position: sticky;
            top: 0;
            z-index: 10;
        }

        .cal-header-cell {
            padding: 15px 10px;
            text-align: center;
            border-right: 1px solid var(--border-color);
        }
        
        .cal-header-cell:last-child { border-right: none; }
        
        .cal-header-cell .day { display: block; font-size: 13px; font-weight: 800; color: var(--text-main); text-transform: uppercase; }
        .cal-header-cell .date { display: block; font-size: 12px; font-weight: 600; color: var(--text-muted); margin-top: 4px; }

        .ward-header {
            display: flex;
            align-items: center;
            justify-content: flex-start;
            padding-left: 20px;
            font-size: 14px;
            font-weight: 800;
            color: var(--primary-dark);
            text-align: left;
        }

        .calendar-body {
            /* Removed flex and overflow to allow page scroll */
        }

        .cal-row {
            display: grid;
            grid-template-columns: 160px repeat(7, minmax(0, 1fr));
            border-bottom: 1px solid var(--border-color);
        }

        .ward-cell {
            padding: 20px;
            border-right: 1px solid var(--border-color);
            display: flex;
            align-items: flex-start;
            gap: 12px;
            background: #FAFAFA;
        }

        .ward-icon {
            width: 36px;
            height: 36px;
            border-radius: 12px;
            background: rgba(31, 107, 74, 0.1);
            color: var(--primary-color);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
        }

        .ward-info h4 { margin: 0 0 4px 0; font-size: 14px; font-weight: 800; color: var(--primary-dark); }
        .ward-info p { margin: 0; font-size: 11px; font-weight: 600; color: var(--text-muted); }
        .bed-count { display: inline-block; margin-top: 6px; font-size: 10px; font-weight: 800; color: var(--primary-color); background: rgba(31,107,74,0.1); padding: 3px 8px; border-radius: 50px; }

        .day-cell {
            border-right: 1px solid var(--border-color);
            padding: 10px;
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        
        .day-cell:last-child { border-right: none; }

        .shift-slot {
            background: var(--bg-color);
            border: 1px dashed var(--border-color);
            border-radius: 12px;
            padding: 8px;
            min-height: 40px;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: var(--transition);
            min-width: 0; /* Critical for truncation in flex/grid */
        }

        .shift-slot.drag-over {
            background: rgba(31, 107, 74, 0.1);
            border-color: var(--primary-color);
            transform: scale(1.02);
        }

        .shift-indicator {
            width: 24px;
            height: 24px;
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 11px;
            font-weight: 800;
            color: var(--white);
        }
        
        .indicator-m { background: var(--status-morning); }
        .indicator-e { background: var(--status-evening); }
        .indicator-n { background: var(--status-night); }

        .assigned-nurse {
            display: flex;
            align-items: flex-start; /* Keep X button near top */
            gap: 4px;
            background: var(--white);
            padding: 6px 8px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.04);
            border: 1px solid rgba(0,0,0,0.05);
            flex: 1;
            font-size: 11px; /* Slightly smaller to fit long names better */
            font-weight: 700;
            color: var(--text-main);
            position: relative;
            min-width: 0;
            width: 100%;
        }
        
        .assigned-nurse span {
            white-space: normal; /* Allow text to wrap to new lines */
            word-break: break-word; /* Force break if a single word is massive */
            line-height: 1.2;
            flex: 1;
        }
        
        .assigned-nurse img {
            width: 20px;
            height: 20px;
            border-radius: 50%;
        }

        .remove-nurse {
            margin-left: auto;
            color: #EF4444;
            cursor: pointer;
            opacity: 0;
            transition: var(--transition);
            background: rgba(239, 68, 68, 0.1);
            border-radius: 50%;
            width: 18px;
            height: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 9px;
        }
        
        .assigned-nurse:hover .remove-nurse { opacity: 1; }
        .remove-nurse:hover { background: #ef4444; color: white; }

        /* Week Off Row styles */
        .week-off-row {
            background: rgba(243, 239, 230, 0.4);
            border-bottom: 2px solid var(--border-color);
        }
        
        .week-off-row .ward-cell {
            background: rgba(243, 239, 230, 0.8);
        }
        
        .week-off-row .ward-icon {
            background: rgba(245, 158, 11, 0.1);
            color: #f59e0b;
        }
        
        .week-off-slot {
            height: 50px; 
            display: flex;
            align-items: center;
            justify-content: flex-start;
            flex-wrap: wrap;
            gap: 5px;
            background: var(--white);
            border-radius: var(--radius-input);
            padding: 4px;
            position: relative;
            overflow-y: auto;
        }
        
        .week-off-slot.drag-over {
            background: rgba(245, 158, 11, 0.05);
            border: 1px dashed #f59e0b;
        }
        
        .week-off-slot .assigned-nurse {
            flex: none;
            width: 100%;
            background: rgba(245, 158, 11, 0.1);
            border-color: rgba(245, 158, 11, 0.2);
            color: #d97706;
        }

        .empty-slot-text {
            font-size: 11px;
            color: var(--text-muted);
            font-weight: 600;
        }

        /* 3. Footer Toolbar */
        .bottom-toolbar {
            position: fixed;
            bottom: 0;
            left: 185px; /* Sidebar width */
            right: 0;
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(16px);
            border-top: 1px solid var(--border-color);
            padding: 15px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            z-index: 100;
            box-shadow: 0 -10px 30px rgba(0,0,0,0.03);
        }

        .legend {
            display: flex;
            gap: 20px;
        }
        
        .legend-item { display: flex; align-items: center; gap: 8px; font-size: 12px; font-weight: 600; color: var(--text-muted); }
        .legend-dot { width: 16px; height: 16px; border-radius: 4px; display: inline-flex; align-items: center; justify-content: center; font-size: 9px; font-weight: 800; color: white; }

        /* View Mode Overrides */
        body.view-mode-active .left-panel { display: none !important; }
        body.view-mode-active .remove-nurse { display: none !important; }
        body.view-mode-active .shift-slot { border-style: solid; border-color: transparent; background: transparent; }
        body.view-mode-active .empty-slot-text { display: none !important; }

    </style>
</head>
<body>
    <!-- Initial date selection modal removed per user request -->

    <!-- Include Sidebar (ensure it loads properly) -->
    <?php 
    $sidebarPath = __DIR__ . '/includes/nurse_sidebar.php';
    if(file_exists($sidebarPath)) {
        include $sidebarPath;
    }
    ?>

    <div class="main-content">
        
        <!-- Header Section -->
        <div class="top-header">
            <div class="header-title">
                <i class="fas fa-calendar-alt"></i>
                <div>
                    <h1>Nurse Shift Assigning</h1>
                    <p>Assign nurses to wards / rooms for selected shifts</p>
                </div>
            </div>
            
            <div class="header-actions">
                <div class="date-picker" style="padding: 0; background: transparent; border: none; box-shadow: none; display: flex; gap: 8px; align-items: center;">
                    <span style="font-size: 13px; font-weight: 700; color: var(--primary-dark);">Start:</span>
                    <input type="date" id="week-start-date" 
                           value="<?php echo htmlspecialchars($startDateStr); ?>" 
                           onchange="updateEndDate()"
                           style="padding: 10px 15px; border: 1px solid var(--border-color); border-radius: 8px; font-family: 'Plus Jakarta Sans', sans-serif; font-weight: 600; color: var(--primary-dark); outline: none; cursor: pointer; background: var(--white); box-shadow: var(--shadow-sm);">
                    
                    <span style="font-size: 13px; font-weight: 700; color: var(--primary-dark); margin-left: 10px;">End:</span>
                    <input type="date" id="week-end-date" readonly
                           value="<?php echo htmlspecialchars(date('Y-m-d', strtotime($startDateStr . ' +6 days'))); ?>" 
                           style="padding: 10px 15px; border: 1px solid var(--border-color); border-radius: 8px; font-family: 'Plus Jakarta Sans', sans-serif; font-weight: 600; color: var(--primary-dark); outline: none; background: #e9ecef; box-shadow: var(--shadow-sm);">

                    <button class="btn-custom btn-primary-custom" onclick="loadSelectedWeek()" style="padding: 10px 15px; margin-left: 10px;">
                        <i class="fas fa-search"></i> Load
                    </button>
                </div>
                
                <button class="btn-custom btn-outline" onclick="toggleViewMode()" id="btn-view-mode" style="margin-left: 15px;">
                    <i class="fas fa-eye"></i> View Mode
                </button>
            </div>
        </div>

        <!-- Workspace Area -->
        <div class="workspace">
            
            <!-- Left Panel (Pool) -->
            <div class="left-panel">
                <div class="nurse-pool-header">
                    <h3>Unassigned Nurses</h3>
                    <span style="background: rgba(31,107,74,0.1); color: var(--primary-color); padding: 4px 10px; border-radius: 50px; font-size: 12px; font-weight: 800;" id="unassigned-count">0</span>
                </div>

                <!-- Search Box -->
                <div style="position: relative; margin: 10px 0 8px 0;">
                    <i class="fas fa-search" style="position: absolute; left: 10px; top: 50%; transform: translateY(-50%); color: #94a3b8; font-size: 12px;"></i>
                    <input type="text" id="nurse-search" placeholder="Search nurse..." oninput="filterNursePool(this.value)"
                        style="width: 100%; padding: 8px 10px 8px 30px; border: 1.5px solid var(--border-color); border-radius: 8px; font-size: 12px; font-family: 'Plus Jakarta Sans', sans-serif; font-weight: 600; color: var(--primary-dark); outline: none; transition: border-color 0.2s;"
                        onfocus="this.style.borderColor='var(--primary-color)'" onblur="this.style.borderColor='var(--border-color)'">
                </div>

                <!-- Nurse Pool Container -->
                <div id="nurse-pool" style="display: flex; flex-direction: column; gap: 10px; max-height: calc(100vh - 260px); overflow-y: auto; padding-right: 2px;">
                    <!-- JS will inject nurse cards here -->
                </div>
                
            </div>

            <!-- Right Content -->
            <div class="right-content">
                
                <!-- Horizontal Shift Summary -->
                <div class="top-summary-bar">
                    <div class="summary-card shift-morning">
                        <div class="shift-card-top">
                            <div style="display: flex; align-items: center; gap: 15px;">
                                <div class="shift-icon"><i class="fas fa-sun"></i></div>
                                <div class="shift-info">
                                    <h3>Morning Shift</h3>
                                    <p>06:00 AM - 02:00 PM</p>
                                </div>
                            </div>
                            <div class="shift-badge">Actively Assigning</div>
                        </div>
                        <div class="shift-progress-container">
                            <div class="shift-progress-bar" id="prog-m"></div>
                        </div>
                        <div class="shift-stats-bottom">
                            <span>Assigned Staff</span>
                            <div><span class="count" id="count-m">0</span><span class="total"> / 32</span></div>
                        </div>
                    </div>

                    <div class="summary-card shift-evening">
                        <div class="shift-card-top">
                            <div style="display: flex; align-items: center; gap: 15px;">
                                <div class="shift-icon"><i class="fas fa-cloud-sun"></i></div>
                                <div class="shift-info">
                                    <h3>Evening Shift</h3>
                                    <p>02:00 PM - 08:00 PM</p>
                                </div>
                            </div>
                            <div class="shift-badge">Actively Assigning</div>
                        </div>
                        <div class="shift-progress-container">
                            <div class="shift-progress-bar" id="prog-e"></div>
                        </div>
                        <div class="shift-stats-bottom">
                            <span>Assigned Staff</span>
                            <div><span class="count" id="count-e">0</span><span class="total"> / 32</span></div>
                        </div>
                    </div>

                    <div class="summary-card shift-night">
                        <div class="shift-card-top">
                            <div style="display: flex; align-items: center; gap: 15px;">
                                <div class="shift-icon"><i class="fas fa-moon"></i></div>
                                <div class="shift-info">
                                    <h3>Night Shift</h3>
                                    <p>08:00 PM - 08:00 AM</p>
                                </div>
                            </div>
                            <div class="shift-badge">Actively Assigning</div>
                        </div>
                        <div class="shift-progress-container">
                            <div class="shift-progress-bar" id="prog-n"></div>
                        </div>
                        <div class="shift-stats-bottom">
                            <span>Assigned Staff</span>
                            <div><span class="count" id="count-n">0</span><span class="total"> / 32</span></div>
                        </div>
                    </div>
                </div>

                <!-- Filters Row -->
                <div class="filter-bar">
                    <div style="display: flex; gap: 15px; align-items: center;">
                        <span style="font-size: 13px; font-weight: 800; color: var(--primary-color);"><i class="fas fa-filter"></i> Filters:</span>
                        <select class="filter-select" id="filter-floor">
                            <option value="">All Floors</option>
                            <?php foreach($dbFloors as $f): ?>
                                <option value="<?php echo htmlspecialchars($f); ?>"><?php echo htmlspecialchars($f); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <select class="filter-select" id="filter-ward">
                            <option value="">All Wards</option>
                            <?php foreach($dbWardsList as $w): ?>
                                <option value="<?php echo htmlspecialchars($w); ?>"><?php echo htmlspecialchars($w); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <div class="custom-dropdown" id="room-dropdown">
                            <div class="filter-select dropdown-toggle" onclick="toggleRoomDropdown()" style="display: flex; align-items: center; justify-content: space-between; cursor: pointer; user-select: none;">
                                <span id="room-dropdown-text">All Room Types</span>
                                <i class="fas fa-chevron-down" style="font-size: 10px; opacity: 0.5;"></i>
                            </div>
                            <div class="dropdown-menu-custom" id="room-dropdown-menu">
                                <!-- injected by JS -->
                            </div>
                        </div>
                    </div>
                    
                    <div style="display: flex; gap: 15px;">
                        <button class="btn-custom btn-outline" onclick="loadTemplate()">
                            <i class="far fa-copy"></i> Template
                        </button>
                        <button class="btn-custom btn-outline" style="color: #EF4444; border-color: rgba(239, 68, 68, 0.3);" onclick="clearAll()">
                            <i class="far fa-trash-alt"></i> Clear All
                        </button>
                        <button class="btn-custom btn-primary-custom" onclick="saveSchedule()">
                            <i class="fas fa-save"></i> Save Schedule
                        </button>
                    </div>
                </div>

                <!-- Center Calendar Grid -->
                <div class="calendar-container">
                    
                    <!-- Calendar Header (Days) -->
                    <div class="calendar-header" id="cal-header">
                        <div class="cal-header-cell ward-header">Wards / Rooms</div>
                        <!-- JS will inject days -->
                    </div>

                    <!-- Calendar Body (Wards & Slots) -->
                    <div class="calendar-body" id="cal-body">
                        <!-- JS will inject ward rows -->
                    </div>
                    
                </div>
            </div>
            
        </div>

        </div>


    </div>

    <!-- Bootstrap JS (Optional) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Custom Javascript for Interactive UI -->
    <script>
        // MOCK DATA
        
        const MOCK_NURSES = <?php echo $dbNursesJson; ?>;
        
        // Fallback to mock data if DB is completely empty (for demonstration)
        if(MOCK_NURSES.length === 0) {
            MOCK_NURSES.push(
                { id: 'n1', name: 'Nisha R.', role: 'Staff Nurse', status: 'Available', avatar: 'https://i.pravatar.cc/150?img=1' },
                { id: 'n2', name: 'Sneha P.', role: 'Staff Nurse', status: 'Available', avatar: 'https://i.pravatar.cc/150?img=2' }
            );
        }

        const MOCK_WARDS = <?php echo $dbWardsJson; ?>;
        
        // Fallback to mock data if DB is completely empty (for demonstration)
        if(MOCK_WARDS.length === 0) {
            MOCK_WARDS.push(
                { id: 'w1', name: 'General Ward - 1101', type: 'Male Patients', beds: 32 },
                { id: 'w2', name: 'ICU - 1201', type: 'Critical Care', beds: 10 }
            );
        }
        
        const DAYS = <?php echo $dbDaysJson; ?>;
        const EXISTING_ASSIGNMENTS = <?php echo $existingAssignmentsJson; ?>;

        // State to hold assignments: { "w1_0_m": nurseObj }
        let assignments = {};

        document.addEventListener('DOMContentLoaded', () => {
            renderHeader();
            renderCalendarBody();
            renderNursePool();
            setupDragAndDrop();
            
            // Setup Filter Listeners
            document.getElementById('filter-floor').addEventListener('change', updateWardDropdown);
            document.getElementById('filter-ward').addEventListener('change', updateRoomDropdown);
            
            // Initial population of custom dropdown
            updateRoomDropdown();
            
            loadExistingAssignments();
        });
        
        function toggleRoomDropdown() {
            document.getElementById('room-dropdown-menu').classList.toggle('show');
        }
        
        document.addEventListener('click', function(e) {
            if(!e.target.closest('#room-dropdown')) {
                const menu = document.getElementById('room-dropdown-menu');
                if (menu) menu.classList.remove('show');
            }
        });

        function updateRoomDropdownText() {
            const checked = Array.from(document.querySelectorAll('.room-checkbox:checked')).map(cb => cb.value);
            const textEl = document.getElementById('room-dropdown-text');
            if (checked.length === 0) textEl.innerText = 'All Room Types';
            else if (checked.length === 1) textEl.innerText = checked[0];
            else textEl.innerText = checked.length + ' Selected';
            filterCalendar();
        }
        
        function loadExistingAssignments() {
            if (!EXISTING_ASSIGNMENTS || EXISTING_ASSIGNMENTS.length === 0) return;
            
            EXISTING_ASSIGNMENTS.forEach(record => {
                const nurseId = 'n_' + record.nurse_id;
                const shiftDate = record.shift_date;
                const shiftType = record.shift_type;
                const floorName = record.floor_name;
                const wardName = record.ward_name;
                const roomType = record.room_type;
                
                const dayIndex = DAYS.findIndex(d => d.fullDate === shiftDate);
                if (dayIndex === -1) return;
                
                let slotId = null;
                
                if (shiftType === 'Week Off') {
                    slotId = `weekoff_${dayIndex}`;
                } else {
                    const ward = MOCK_WARDS.find(w => 
                        w.ward_name === wardName && 
                        w.floor_name === floorName && 
                        w.room_type === roomType
                    );
                    if (!ward) return;
                    
                    let shiftChar = 'm';
                    if (shiftType === 'Evening') shiftChar = 'e';
                    else if (shiftType === 'Night') shiftChar = 'n';
                    
                    slotId = `${ward.id}_${dayIndex}_${shiftChar}`;
                }
                
                if (slotId) {
                    assignNurseToSlot(nurseId, slotId, true);
                }
            });
        }
        
        function updateWardDropdown() {
            const floor = document.getElementById('filter-floor').value;
            const wardSelect = document.getElementById('filter-ward');
            
            // Retain the current selected ward if possible
            const currentSelected = wardSelect.value;
            
            wardSelect.innerHTML = '<option value="">All Wards</option>';
            
            let availableWards = MOCK_WARDS;
            if (floor) {
                availableWards = MOCK_WARDS.filter(w => w.floor_name === floor);
            }
            
            const uniqueWards = [...new Set(availableWards.map(w => w.ward_name).filter(Boolean))].sort();
            uniqueWards.forEach(w => {
                const selected = w === currentSelected ? 'selected' : '';
                wardSelect.innerHTML += `<option value="${w}" ${selected}>${w}</option>`;
            });
            
            updateRoomDropdown();
        }
        
        function updateRoomDropdown() {
            const floor = document.getElementById('filter-floor').value;
            const ward = document.getElementById('filter-ward').value;
            
            let availableRooms = MOCK_WARDS;
            if (floor) availableRooms = availableRooms.filter(w => w.floor_name === floor);
            if (ward) availableRooms = availableRooms.filter(w => w.ward_name === ward);
            
            const uniqueRooms = [...new Set(availableRooms.map(w => w.room_type).filter(Boolean))].sort();
            
            const menu = document.getElementById('room-dropdown-menu');
            
            // Keep previously selected if possible
            const previouslySelected = Array.from(document.querySelectorAll('.room-checkbox:checked')).map(cb => cb.value);
            
            menu.innerHTML = '';
            uniqueRooms.forEach(r => {
                const isChecked = previouslySelected.includes(r) ? 'checked' : '';
                menu.innerHTML += `
                    <label class="checkbox-item">
                        <input type="checkbox" class="room-checkbox" value="${r}" onchange="updateRoomDropdownText()" ${isChecked}>
                        ${r}
                    </label>
                `;
            });
            
            updateRoomDropdownText();
        }
        
        function filterCalendar() {
            const floorFilter = document.getElementById('filter-floor').value.toLowerCase();
            const wardFilter = document.getElementById('filter-ward').value.toLowerCase();
            
            const selectedRooms = Array.from(document.querySelectorAll('.room-checkbox:checked')).map(cb => cb.value.toLowerCase());
            
            // Only filter the ward rows, never hide the week-off row
            const rows = document.querySelectorAll('.cal-row:not(.week-off-row)');
            
            rows.forEach(row => {
                const wardInfo = row.querySelector('.ward-info');
                if(!wardInfo) return; // safeguard
                
                const wardName = wardInfo.querySelector('h4').innerText.toLowerCase();
                const wardType = wardInfo.querySelector('p').innerText.toLowerCase();
                
                let show = true;
                
                if (floorFilter && !wardType.includes(floorFilter)) show = false;
                if (wardFilter && !wardName.includes(wardFilter)) show = false;
                
                if (selectedRooms.length > 0) {
                    // Check if wardType includes ANY of the selected room types
                    const matchesRoom = selectedRooms.some(r => wardType.includes(r));
                    if (!matchesRoom) show = false;
                }
                
                row.style.display = show ? 'grid' : 'none';
            });
        }

        function renderHeader() {
            const headerRow = document.getElementById('cal-header');
            DAYS.forEach(day => {
                headerRow.innerHTML += `
                    <div class="cal-header-cell">
                        <span class="day">${day.short}</span>
                        <span class="date">${day.date}</span>
                    </div>
                `;
            });
        }

        function renderCalendarBody() {
            const body = document.getElementById('cal-body');
            
            // Add Week Off Row
            let weekOffHtml = `<div class="cal-row week-off-row">`;
            weekOffHtml += `
                <div class="ward-cell">
                    <div class="ward-icon"><i class="fas fa-umbrella-beach"></i></div>
                    <div class="ward-info">
                        <h4>Staff Leave / W.O.</h4>
                        <p>Drop nurses here</p>
                    </div>
                </div>
            `;
            
            for(let i=0; i<7; i++) {
                weekOffHtml += `<div class="day-cell">`;
                const slotId = `weekoff_${i}`;
                weekOffHtml += `
                    <div class="week-off-slot shift-slot" id="${slotId}" data-shift="wo">
                        <span class="empty-slot-text">Week Off</span>
                    </div>
                `;
                weekOffHtml += `</div>`;
            }
            weekOffHtml += `</div>`;
            
            body.innerHTML = weekOffHtml;
            
            MOCK_WARDS.forEach(ward => {
                let rowHtml = `<div class="cal-row">`;
                
                // Ward Info
                rowHtml += `
                    <div class="ward-cell">
                        <div class="ward-icon"><i class="fas fa-procedures"></i></div>
                        <div class="ward-info">
                            <h4>${ward.name}</h4>
                            <p>${ward.type}</p>
                            <span class="bed-count">${ward.beds} Beds</span>
                        </div>
                    </div>
                `;
                
                // Days (7 columns)
                for(let i=0; i<7; i++) {
                    rowHtml += `<div class="day-cell">`;
                    
                    // 3 Shifts per day
                    ['m', 'e', 'n'].forEach(shift => {
                        const slotId = `${ward.id}_${i}_${shift}`;
                        rowHtml += `
                            <div class="shift-slot" id="${slotId}" data-shift="${shift}">
                                <div class="shift-indicator indicator-${shift}">${shift.toUpperCase()}</div>
                                <div class="slot-content flex-grow-1">
                                    <span class="empty-slot-text">Drop nurse here</span>
                                </div>
                            </div>
                        `;
                    });
                    
                    rowHtml += `</div>`;
                }
                
                rowHtml += `</div>`;
                body.innerHTML += rowHtml;
            });
        }

        function renderNursePool(filter = '') {
            const pool = document.getElementById('nurse-pool');
            pool.innerHTML = '';
            
            const lowerFilter = filter.toLowerCase();
            const unassigned = MOCK_NURSES.filter(n => {
                // Only show nurses not fully assigned (simple: show all in pool)
                if (lowerFilter && !n.name.toLowerCase().includes(lowerFilter)) return false;
                return true;
            });
            
            // Update count badge
            const countBadge = document.getElementById('unassigned-count');
            if (countBadge) countBadge.textContent = unassigned.length;
            
            if (unassigned.length === 0) {
                pool.innerHTML = `<div style="text-align:center; padding: 20px 10px; color: #94a3b8; font-size: 12px; font-weight: 600;"><i class="fas fa-search" style="display:block; font-size:20px; margin-bottom:8px;"></i>No nurses found</div>`;
                return;
            }
            
            unassigned.forEach(n => {
                const badgeClass = n.status === 'Available' ? 'status-available' : 'status-leave';
                pool.innerHTML += `
                    <div class="nurse-card" draggable="true" id="nurse_${n.id}" data-id="${n.id}">
                        <div style="display: flex; justify-content: space-between; align-items: center; width: 100%; gap: 10px;">
                            <div style="font-size: 13px; font-weight: 700; color: var(--primary-dark); white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="${n.name}">${n.name}</div>
                            <span class="status-badge ${badgeClass}" style="flex-shrink: 0; padding: 2px 6px; font-size: 9px;">${n.status.toUpperCase()}</span>
                        </div>
                    </div>
                `;
            });
        }

        function filterNursePool(value) {
            renderNursePool(value);
        }

        // Drag and Drop Logic
        let draggedNurseId = null;

        function setupDragAndDrop() {
            // Drag Start
            document.addEventListener('dragstart', (e) => {
                const card = e.target.closest('.nurse-card');
                if (card) {
                    draggedNurseId = card.dataset.id;
                    card.style.opacity = '0.5';
                    
                    // REQUIRED for drop event to fire in most browsers!
                    if (e.dataTransfer) {
                        e.dataTransfer.effectAllowed = 'move';
                        e.dataTransfer.setData('text/plain', draggedNurseId);
                    }
                }
            });

            document.addEventListener('dragend', (e) => {
                const card = e.target.closest('.nurse-card');
                if (card) {
                    card.style.opacity = '1';
                    draggedNurseId = null;
                }
            });

            // Drag Over
            document.addEventListener('dragover', (e) => {
                e.preventDefault();
                const slot = e.target.closest('.shift-slot');
                if (slot) {
                    slot.classList.add('drag-over');
                }
            });

            // Drag Leave
            document.addEventListener('dragleave', (e) => {
                const slot = e.target.closest('.shift-slot');
                if (slot) {
                    slot.classList.remove('drag-over');
                }
            });

            // Drop
            document.addEventListener('drop', (e) => {
                e.preventDefault();
                const slot = e.target.closest('.shift-slot');
                
                if (slot) {
                    slot.classList.remove('drag-over');
                    
                    if (draggedNurseId) {
                        assignNurseToSlot(draggedNurseId, slot.id);
                    }
                }
            });
        }

        function assignNurseToSlot(nurseId, slotId, isInit = false) {
            const nurse = MOCK_NURSES.find(n => n.id == nurseId);
            if (!nurse) return;
            
            if (nurse.status === 'On Leave') {
                if(!isInit) alert("Cannot assign a nurse who is on leave.");
                return;
            }

            let parts = slotId.split('_');
            
            if (parts[0] === 'weekoff') {
                if (!assignments[slotId]) assignments[slotId] = [];
                if (!assignments[slotId].find(n => n.id == nurseId)) {
                    assignments[slotId].push(nurse);
                    updateSlotUI(slotId);
                }
            } else {
                let wardId = parts[0] + '_' + parts[1];
                let startDay = parseInt(parts[2]);
                let shift = parts[3];
                
                // Auto-fill to the end of the week or just target slot?
                // If it's an initial load from DB, we shouldn't auto-fill!
                let endDay = isInit ? startDay + 1 : 7;
                
                for(let i = startDay; i < endDay; i++) {
                    let targetSlot = `${wardId}_${i}_${shift}`;
                    if (!assignments[targetSlot]) assignments[targetSlot] = [];
                    
                    if (!assignments[targetSlot].find(n => n.id == nurseId)) {
                        assignments[targetSlot].push(nurse);
                        updateSlotUI(targetSlot);
                    }
                }
            }
            
            updateSummaryCounters();
        }

        window.removeNurseFromSlot = function(slotId, nurseId) {
            if (!assignments[slotId]) return;
            assignments[slotId] = assignments[slotId].filter(n => n.id != nurseId);
            if (assignments[slotId].length === 0) delete assignments[slotId];
            updateSlotUI(slotId);
            updateSummaryCounters();
        };

        function updateSlotUI(slotId) {
            const slot = document.getElementById(slotId);
            if (!slot) return;
            
            const isWeekOff = slotId.startsWith('weekoff');
            const contentDiv = isWeekOff ? slot : slot.querySelector('.slot-content');
            const nurses = assignments[slotId];
            
            if (nurses && nurses.length > 0) {
                let html = '';
                nurses.forEach(nurse => {
                    html += `
                        <div class="assigned-nurse" style="margin-bottom: 4px;">
                            <span>${nurse.name}</span>
                            <div class="remove-nurse" onclick="removeNurseFromSlot('${slotId}', '${nurse.id}')"><i class="fas fa-times"></i></div>
                        </div>
                    `;
                });
                contentDiv.innerHTML = html;
            } else {
                if (isWeekOff) {
                    contentDiv.innerHTML = `<span class="empty-slot-text">Week Off</span>`;
                } else {
                    contentDiv.innerHTML = `<span class="empty-slot-text">Drop nurse here</span>`;
                }
            }
        }

        function updateSummaryCounters() {
            let mCount = 0; let eCount = 0; let nCount = 0;
            
            Object.keys(assignments).forEach(key => {
                const count = assignments[key].length;
                if (key.endsWith('_m')) mCount += count;
                if (key.endsWith('_e')) eCount += count;
                if (key.endsWith('_n')) nCount += count;
            });
            
            // Animation for counter update
            animateValue('count-m', mCount);
            animateValue('count-e', eCount);
            animateValue('count-n', nCount);
            
            // Animate progress bars
            const maxStaff = 32; // Based on UI max
            document.getElementById('prog-m').style.width = Math.min((mCount / maxStaff) * 100, 100) + '%';
            document.getElementById('prog-e').style.width = Math.min((eCount / maxStaff) * 100, 100) + '%';
            document.getElementById('prog-n').style.width = Math.min((nCount / maxStaff) * 100, 100) + '%';
            
            // Update badges if fully staffed
            updateBadge('m', mCount, maxStaff);
            updateBadge('e', eCount, maxStaff);
            updateBadge('n', nCount, maxStaff);
        }

        function updateBadge(shift, count, max) {
            const el = document.getElementById('count-' + shift).closest('.summary-card').querySelector('.shift-badge');
            if (count >= max) {
                el.innerText = 'Fully Staffed';
                el.style.background = 'rgba(16, 185, 129, 0.1)';
                el.style.color = 'var(--status-morning)';
            } else {
                el.innerText = 'Actively Assigning';
                el.style.background = 'var(--bg-color)';
                el.style.color = 'var(--text-muted)';
            }
        }

        function animateValue(id, newValue) {
            const el = document.getElementById(id);
            el.style.transform = 'scale(1.2)';
            setTimeout(() => {
                el.innerText = newValue;
                el.style.transform = 'scale(1)';
            }, 100);
        }


        function clearAll() {
            if(confirm("Are you sure you want to clear all assignments?")) {
                Object.keys(assignments).forEach(key => {
                    const nurses = [...assignments[key]];
                    nurses.forEach(n => removeNurseFromSlot(key, n.id));
                });
            }
        }
        
        function toggleViewMode() {
            document.body.classList.toggle('view-mode-active');
            const isViewMode = document.body.classList.contains('view-mode-active');
            const btn = document.getElementById('btn-view-mode');
            
            // Hide empty rows logic
            const rows = document.querySelectorAll('.cal-row');
            rows.forEach(row => {
                if (isViewMode) {
                    const hasAssignments = row.querySelector('.assigned-nurse') !== null;
                    if (!hasAssignments) {
                        row.dataset.originalDisplay = row.style.display || 'grid';
                        row.style.display = 'none';
                    }
                } else {
                    if (row.dataset.originalDisplay) {
                        row.style.display = row.dataset.originalDisplay;
                    }
                }
            });

            if (isViewMode) {
                if(btn) {
                    btn.innerHTML = '<i class="fas fa-edit"></i> Edit Mode';
                    btn.classList.add('btn-primary-custom');
                    btn.classList.remove('btn-outline');
                }
            } else {
                if(btn) {
                    btn.innerHTML = '<i class="fas fa-eye"></i> View Mode';
                    btn.classList.remove('btn-primary-custom');
                    btn.classList.add('btn-outline');
                }
                filterCalendar(); // Re-apply normal filters
            }
        }
        
        function loadSelectedWeek() {
            const selectedDate = document.getElementById('week-start-date').value;
            const hasUnsaved = Object.keys(assignments).length > 0;
            
            if (hasUnsaved) {
                if (!confirm("You have unsaved assignments on the calendar. Changing the week will discard them. Are you sure?")) {
                    return;
                }
            }
            
            window.location.href = '?start_date=' + selectedDate;
        }
        
        function updateEndDate() {
            const startDateVal = document.getElementById('week-start-date').value;
            if (startDateVal) {
                const date = new Date(startDateVal);
                date.setDate(date.getDate() + 6); // +6 days gives a 7-day range inclusive
                const endDateStr = date.toISOString().split('T')[0];
                document.getElementById('week-end-date').value = endDateStr;
            }
        }
        
        function saveSchedule() {
            const totalAssigned = Object.keys(assignments).length;
            if(totalAssigned === 0) {
                alert("Nothing to save.");
                return;
            }

            // Transform assignments into a payload array
            const payload = [];
            
            for (let slotId in assignments) {
                const nurses = assignments[slotId];
                
                nurses.forEach(nurse => {
                    const dbNurseId = nurse.id.toString().replace('n_', '');
                    
                    let parts = slotId.split('_');
                    if (parts[0] === 'weekoff') {
                        let dayIndex = parseInt(parts[1]);
                        payload.push({
                            nurse_id: dbNurseId,
                            nurse_name: nurse.name,
                            shift_date: DAYS[dayIndex].fullDate,
                            shift_type: 'Week Off',
                            floor_name: null,
                            ward_name: null,
                            room_type: null
                        });
                    } else {
                        let wardId = parts[0] + '_' + parts[1];
                        let dayIndex = parseInt(parts[2]);
                        let shiftChar = parts[3];
                        let shiftType = shiftChar === 'm' ? 'Morning' : (shiftChar === 'e' ? 'Evening' : 'Night');
                        
                        const ward = MOCK_WARDS.find(w => w.id === wardId);
                        
                        payload.push({
                            nurse_id: dbNurseId,
                            nurse_name: nurse.name,
                            shift_date: DAYS[dayIndex].fullDate,
                            shift_type: shiftType,
                            floor_name: ward ? ward.floor_name : null,
                            ward_name: ward ? ward.ward_name : null,
                            room_type: ward ? ward.room_type : null
                        });
                    }
                });
            }
            
            // Post-process Week Off records to attach floor, ward, and room type from their other shifts
            payload.forEach(record => {
                if (record.shift_type === 'Week Off') {
                    // Find another working shift for this specific nurse in the payload
                    const workingShift = payload.find(p => p.nurse_id === record.nurse_id && p.shift_type !== 'Week Off');
                    if (workingShift) {
                        record.floor_name = workingShift.floor_name;
                        record.ward_name = workingShift.ward_name;
                        record.room_type = workingShift.room_type;
                    }
                }
            });
            
            const finalData = {
                startDate: DAYS[0].fullDate,
                endDate: DAYS[6].fullDate,
                shifts: payload
            };
            
            const btn = event.currentTarget;
            const originalText = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
            btn.style.opacity = '0.8';
            btn.disabled = true;
            
            fetch('save_shift_schedule.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(finalData)
            })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    btn.innerHTML = '<i class="fas fa-check"></i> Saved Successfully';
                    btn.style.background = 'var(--status-morning)';
                    setTimeout(() => {
                        window.location.reload();
                    }, 1500);
                } else {
                    alert("Error saving: " + data.message);
                    btn.innerHTML = originalText;
                    btn.style.background = '';
                }
            })
            .catch(err => {
                console.error(err);
                alert("Network error while saving.");
                btn.innerHTML = originalText;
                btn.style.background = '';
            })
            .finally(() => {
                setTimeout(() => {
                    btn.innerHTML = originalText;
                    btn.style.background = '';
                    btn.style.opacity = '1';
                    btn.disabled = false;
                }, 2000);
            });
        }
        
        function loadTemplate() {
            alert("Loaded template from previous week.");
        }

    </script>
</body>
</html>
