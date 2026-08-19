<?php
session_start();
require_once __DIR__ . '/../core/Autoloader.php';
use GM_HMS\Database\SecureDatabase;

// Require Superintendent Nurse or Admin to view the page
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['Superintendent_Nurse', 'Superintendent Nurse', 'admin', 'Admin', 'Head Nurse'])) {
    header('Location: dashboard.php');
    exit();
}

// PRG Pattern for Date Range to keep URL perfectly clean
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['start_date'])) {
    $_SESSION['bp_start_date'] = $_POST['start_date'];
    $_SESSION['bp_end_date'] = $_POST['end_date'] ?? '';
    header('Location: shift_assignment.php');
    exit();
}

// Reset session dates if explicitly cleared
if (isset($_GET['reset'])) {
    unset($_SESSION['bp_start_date'], $_SESSION['bp_end_date']);
}

// Determine Start Date
$startDateStr = date('Y-m-d'); // Default to current week
if (isset($_GET['start_date']) && !empty($_GET['start_date'])) {
    $startDateStr = $_GET['start_date'];
} elseif (isset($_SESSION['bp_start_date']) && !empty($_SESSION['bp_start_date'])) {
    $startDateStr = $_SESSION['bp_start_date'];
}
$startDate = new DateTime($startDateStr);

// Determine End Date
$hasEndDate = false;
$endDateStr = date('Y-m-d', strtotime('+6 days', strtotime($startDateStr)));
$hasEndDate = true;
if (isset($_GET['end_date']) && !empty($_GET['end_date'])) {
    $endDateStr = $_GET['end_date'];
} elseif (isset($_SESSION['bp_end_date']) && !empty($_SESSION['bp_end_date'])) {
    $endDateStr = $_SESSION['bp_end_date'];
}

if ($hasEndDate) {
    $endDate = new DateTime($endDateStr);
} else {
    // Default to a 7-day range (6 days after start date)
    $endDate = clone $startDate;
    $endDate->modify("+6 days");
    $endDateStr = $endDate->format('Y-m-d');
}

// Calculate the number of days between start and end (inclusive)
$interval = $startDate->diff($endDate);
$numDays = $interval->days + 1; // +1 to include both start and end date

// Cap to 31 days max to prevent accidental massive payloads
if ($numDays > 31) {
    $numDays = 31;
}

$dbDays = [];
for ($i = 0; $i < $numDays; $i++) {
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
        WHERE designation IN ('Nurse', 'Superintendent_Nurse', 'Nursing_Superintendent', 'Head Nurse', 'Staff Nurse')
        ORDER BY full_name
    ");
    $stmt->execute();
    $result = $stmt->get_result();
    
    $index = 0;
    while ($row = $result->fetch_assoc()) {
        $dbNurses[] = [
            'id'     => 'n_' . $row['staff_id'],
            'name'   => $row['name'],
            'role'   => $row['role'] ?: 'Staff Nurse',
            'status' => ($row['status'] === 'Active') ? 'Available' : 'On Leave'
        ];
        $index++;
    }

    // Fetch Unique Dropdown Filters from Database
    $dbFloors = [];
    $dbWardsList = [];
    $dbRoomTypes = [];
    
    try {
        $stmtFilters = $conn->prepare("
            SELECT DISTINCT floor_name 
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
            'type' => ($row['floor_name'] ?: 'General Floor') . ' - ' . ($row['room_type'] ?: 'General'),
            'beds' => $row['total_beds'],
            'floor_name' => $row['floor_name'],
            'ward_name' => $row['ward_name'],
            'room_type' => $row['room_type']
        ];
        $wardIndex++;
    }

    // Fetch Existing Assignments
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Nurse Duty Shift Assignment & Calendar - GM HMS</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* ── GM HMS Signature Two-Color Design System (#f3efe6 & #1f6b4a) ── */
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

            /* Shift Theme Colors */
            --sh-m-bg: rgba(31, 107, 74, 0.10);
            --sh-m-color: #1f6b4a;
            --sh-m-border: rgba(31, 107, 74, 0.28);

            --sh-e-bg: rgba(217, 119, 6, 0.10);
            --sh-e-color: #b45309;
            --sh-e-border: rgba(217, 119, 6, 0.28);

            --sh-n-bg: rgba(30, 64, 175, 0.10);
            --sh-n-color: #1e40af;
            --sh-n-border: rgba(30, 64, 175, 0.28);

            --sh-wo-bg: rgba(220, 38, 38, 0.08);
            --sh-wo-color: #dc2626;
            --sh-wo-border: rgba(220, 38, 38, 0.25);
        }

        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body { 
            font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, sans-serif; 
            background: var(--gm-bg); 
            color: var(--gm-text-body); 
            display: flex; 
            min-height: 100vh; 
            overflow-x: hidden; 
            -webkit-font-smoothing: antialiased;
        }

        .main-content { 
            flex: 1; 
            margin-left: var(--gm-sidebar-w, 185px); 
            display: flex; 
            flex-direction: column; 
            min-height: 100vh; 
            max-width: calc(100vw - var(--gm-sidebar-w, 185px)); 
            background: var(--gm-bg);
            transition: margin-left 0.25s ease;
        }

        /* ── Top Bar ── */
        .top-bar { 
            background: #ffffff; 
            padding: 14px 24px; 
            display: flex; 
            align-items: center; 
            justify-content: space-between; 
            gap: 14px; 
            flex-wrap: wrap; 
            border-bottom: 2px solid var(--gm-border); 
            box-shadow: 0 4px 16px rgba(31, 107, 74, 0.05); 
            position: sticky; 
            top: 0; 
            z-index: 50; 
        }

        .bar-left { display: flex; align-items: center; gap: 14px; }
        .bar-icon { 
            width: 44px; 
            height: 44px; 
            flex-shrink: 0; 
            background: var(--gm-primary); 
            border-radius: 12px; 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            color: #f3efe6; 
            font-size: 1.2rem; 
            box-shadow: 0 4px 12px rgba(31, 107, 74, 0.25); 
        }

        .bar-left h1 { 
            font-size: 1.35rem; 
            font-weight: 800; 
            color: var(--gm-primary); 
            letter-spacing: -0.3px; 
            margin: 0;
        }

        .bar-left p { 
            font-size: 0.8rem; 
            color: var(--gm-text-muted); 
            font-weight: 600; 
            margin-top: 2px; 
        }

        .bar-right { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }
        .date-range-box {
            display: flex;
            align-items: center;
            gap: 8px;
            background: var(--gm-bg);
            border: 1.5px solid var(--gm-border);
            padding: 6px 12px;
            border-radius: 10px;
        }

        .date-range-box label {
            font-size: 0.72rem;
            font-weight: 800;
            color: var(--gm-primary);
            text-transform: uppercase;
        }

        .date-range-box input[type="date"] {
            border: 1px solid var(--gm-border);
            border-radius: 6px;
            padding: 4px 8px;
            font-size: 0.82rem;
            font-weight: 700;
            color: var(--gm-primary);
            background: #ffffff;
            outline: none;
        }

        .btn { 
            display: inline-flex; 
            align-items: center; 
            justify-content: center;
            gap: 6px; 
            padding: 8px 16px; 
            border-radius: 8px; 
            font-size: 0.82rem; 
            font-weight: 700; 
            cursor: pointer; 
            border: 1.5px solid var(--gm-border); 
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1); 
            white-space: nowrap; 
            min-height: 38px;
            text-decoration: none;
            background: #ffffff;
            color: var(--gm-primary);
        }

        .btn:hover { 
            background: var(--gm-primary-light); 
            border-color: var(--gm-primary); 
            transform: translateY(-1px); 
        }

        .btn-p { 
            background: var(--gm-primary); 
            color: #f3efe6; 
            border-color: var(--gm-primary); 
            box-shadow: 0 4px 12px rgba(31, 107, 74, 0.25); 
        }

        .btn-p:hover { 
            background: var(--gm-primary-dark); 
            color: #ffffff;
            box-shadow: 0 6px 18px rgba(31, 107, 74, 0.35); 
        }

        .btn-d { 
            background: transparent; 
            border-color: rgba(220, 38, 38, 0.3); 
            color: #dc2626; 
        }

        .btn-d:hover { 
            background: rgba(220, 38, 38, 0.08); 
            border-color: #dc2626;
        }

        .btn-v.on { 
            background: var(--gm-primary); 
            color: #f3efe6; 
            border-color: var(--gm-primary); 
        }

        /* ── Workspace ── */
        .workspace { display: flex; flex: 1; min-height: 0; }

        /* ── Left Nurse Panel ── */
        .nurse-panel { 
            width: 220px; 
            min-width: 220px; 
            flex-shrink: 0; 
            background: #ffffff; 
            border-right: 2px solid var(--gm-border); 
            display: flex; 
            flex-direction: column; 
            height: calc(100vh - 74px); 
            overflow: hidden; 
            position: sticky; 
            top: 74px; 
            z-index: 20; 
            box-sizing: border-box; 
        }

        .np-head { 
            padding: 12px 14px; 
            border-bottom: 1.5px solid var(--gm-border); 
            background: #ffffff;
        }

        .np-title { 
            font-size: 0.8rem; 
            font-weight: 800; 
            color: var(--gm-primary); 
            text-transform: uppercase; 
            letter-spacing: 0.5px; 
            display: flex; 
            align-items: center; 
            justify-content: space-between; 
            margin-bottom: 8px; 
        }

        .np-count { 
            background: var(--gm-primary); 
            color: #f3efe6; 
            padding: 2px 8px; 
            border-radius: 12px; 
            font-size: 0.74rem; 
            font-weight: 800; 
        }

        .np-search { position: relative; }
        .np-search i { 
            position: absolute; 
            left: 10px; 
            top: 50%; 
            transform: translateY(-50%); 
            color: var(--gm-primary); 
            font-size: 0.85rem; 
        }

        .np-search input { 
            width: 100%; 
            padding: 8px 10px 8px 30px; 
            border: 1.5px solid var(--gm-border); 
            border-radius: 8px; 
            font-size: 0.82rem; 
            font-weight: 600; 
            color: var(--gm-primary); 
            background: var(--gm-bg); 
            outline: none; 
            transition: all 0.2s ease; 
        }

        .np-search input:focus { 
            background: #ffffff;
            border-color: var(--gm-primary); 
            box-shadow: 0 0 0 3px var(--gm-primary-light);
        }

        .tap-hint { 
            margin-top: 8px; 
            padding: 6px 8px; 
            background: var(--gm-primary-light); 
            border: 1px dashed var(--gm-primary); 
            border-radius: 6px; 
            font-size: 0.72rem; 
            color: var(--gm-primary); 
            font-weight: 700; 
            text-align: center; 
        }

        .shift-legend { 
            padding: 8px 12px; 
            border-bottom: 1.5px solid var(--gm-border); 
            display: flex; 
            gap: 6px; 
            flex-wrap: wrap; 
            background: var(--gm-bg);
        }

        .leg-pill { 
            display: inline-flex; 
            align-items: center; 
            gap: 4px; 
            padding: 3px 7px; 
            border-radius: 6px; 
            font-size: 0.72rem; 
            font-weight: 800; 
            border: 1px solid; 
        }

        .leg-m { background: var(--sh-m-bg); color: var(--sh-m-color); border-color: var(--sh-m-border); }
        .leg-e { background: var(--sh-e-bg); color: var(--sh-e-color); border-color: var(--sh-e-border); }
        .leg-n { background: var(--sh-n-bg); color: var(--sh-n-color); border-color: var(--sh-n-border); }
        
        #nurse-pool { 
            flex: 1; 
            overflow-y: auto; 
            padding: 10px; 
            display: flex; 
            flex-direction: column; 
            gap: 6px; 
        }

        .nurse-card { 
            background: var(--gm-bg); 
            border: 1.5px solid var(--gm-border); 
            border-radius: 10px; 
            padding: 8px 10px; 
            cursor: pointer; 
            display: flex; 
            align-items: center; 
            gap: 8px; 
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1); 
            user-select: none;
        }

        .nurse-card:hover { 
            background: #ffffff; 
            border-color: var(--gm-primary); 
            transform: translateX(2px); 
            box-shadow: 0 4px 12px rgba(31, 107, 74, 0.12); 
        }

        .nurse-card.selected { 
            background: #ffffff; 
            border-color: var(--gm-primary); 
            box-shadow: 0 0 0 2.5px var(--gm-primary); 
            transform: translateX(3px); 
        }

        .nurse-card.on-leave { opacity: 0.55; }
        
        .nc-avatar { 
            width: 28px; 
            height: 28px; 
            border-radius: 8px; 
            background: var(--gm-primary); 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            color: #f3efe6; 
            font-size: 0.74rem; 
            font-weight: 800; 
            flex-shrink: 0; 
        }

        .nc-info { flex: 1; min-width: 0; }
        .nc-name { 
            font-size: 0.82rem; 
            font-weight: 800; 
            color: var(--gm-primary); 
            line-height: 1.2; 
            white-space: nowrap; 
            overflow: hidden; 
            text-overflow: ellipsis; 
        }

        .nc-role { 
            font-size: 0.7rem; 
            color: var(--gm-text-muted); 
            font-weight: 600; 
            white-space: nowrap; 
            overflow: hidden; 
            text-overflow: ellipsis; 
        }

        .nc-dot { 
            width: 7px; 
            height: 7px; 
            border-radius: 50%; 
            flex-shrink: 0; 
        }
        .dot-av { background: #10B981; }
        .dot-lv { background: #EF4444; }

        /* ── Right Area ── */
        .right-area { 
            flex: 1; 
            display: flex; 
            flex-direction: column; 
            overflow: hidden; 
            min-width: 0; 
            background: var(--gm-bg);
        }

        /* Conflict Bar */
        #conflict-bar { 
            background: rgba(220, 38, 38, 0.1); 
            border-bottom: 2px solid #dc2626; 
            padding: 8px 20px; 
            display: none; 
            align-items: center; 
            gap: 12px; 
            font-size: 0.84rem; 
            font-weight: 800; 
            color: #dc2626; 
            position: sticky; 
            top: 74px; 
            z-index: 40; 
        }

        /* Control Bar */
        .ctrl-bar { 
            background: #ffffff; 
            border-bottom: 1.5px solid var(--gm-border); 
            padding: 10px 18px; 
            display: flex; 
            align-items: center; 
            gap: 12px; 
            flex-wrap: wrap; 
            justify-content: space-between; 
        }

        .counter-pills { display: flex; gap: 8px; flex-wrap: wrap; }
        .cpill { 
            display: inline-flex; 
            align-items: center; 
            gap: 6px; 
            padding: 5px 12px; 
            border-radius: 20px; 
            font-size: 0.78rem; 
            font-weight: 800; 
            border: 1.5px solid; 
        }

        .cpill-m { background: var(--sh-m-bg); color: var(--sh-m-color); border-color: var(--sh-m-border); }
        .cpill-e { background: var(--sh-e-bg); color: var(--sh-e-color); border-color: var(--sh-e-border); }
        .cpill-n { background: var(--sh-n-bg); color: var(--sh-n-color); border-color: var(--sh-n-border); }
        
        .cpill .cnt { 
            background: #ffffff; 
            padding: 1px 7px; 
            border-radius: 10px; 
            font-size: 0.76rem; 
            border: 1px solid currentColor;
        }

        .actions { display: flex; gap: 8px; align-items: center; flex-wrap: wrap; }

        /* ── Calendar Table Container ── */
        .cal-wrap { 
            flex: 1; 
            overflow: auto; 
            padding: 16px; 
            -webkit-overflow-scrolling: touch;
        }

        table.sched { 
            border-collapse: separate; 
            border-spacing: 0; 
            border-radius: 12px; 
            overflow: hidden; 
            box-shadow: 0 4px 18px rgba(31, 107, 74, 0.08); 
            border: 1.5px solid var(--gm-border); 
            background: #ffffff; 
            width: 100%; 
            table-layout: fixed; 
            min-width: 900px;
        }

        /* thead */
        table.sched thead tr { background: var(--gm-primary); }
        .th-ward { 
            padding: 12px 14px; 
            text-align: left; 
            font-size: 0.76rem; 
            font-weight: 800; 
            color: #f3efe6; 
            text-transform: uppercase; 
            letter-spacing: 0.5px; 
            position: sticky; 
            left: 0; 
            background: var(--gm-primary); 
            z-index: 30; 
            width: 150px; 
            border-right: 1.5px solid rgba(243, 239, 230, 0.2); 
        }

        .th-day { 
            padding: 10px 6px; 
            text-align: center; 
            border-right: 1px solid rgba(243, 239, 230, 0.2); 
            vertical-align: middle; 
        }
        .th-day:last-child { border-right: none; }
        .th-day .dn { font-size: 0.72rem; font-weight: 800; color: rgba(243, 239, 230, 0.75); text-transform: uppercase; }
        .th-day .dd { font-size: 1.15rem; font-weight: 800; color: #ffffff; line-height: 1.1; margin-top: 2px; }
        .th-day .dm { font-size: 0.72rem; font-weight: 600; color: rgba(243, 239, 230, 0.75); }
        .th-day.today { background: var(--gm-primary-dark); }
        .today-badge { 
            font-size: 0.65rem; 
            font-weight: 800; 
            color: #f3efe6; 
            background: rgba(243, 239, 230, 0.25); 
            padding: 1px 6px; 
            border-radius: 4px; 
            display: inline-block; 
            margin-top: 3px; 
        }

        /* tbody */
        table.sched tbody tr { border-bottom: 1.5px solid var(--gm-border); }
        table.sched tbody tr:last-child { border-bottom: none; }
        table.sched tbody tr:hover .td-ward { background: #f6faf6; }

        /* Ward cell - sticky */
        .td-ward { 
            padding: 10px 12px; 
            background: #ffffff; 
            border-right: 1.5px solid var(--gm-border); 
            position: sticky; 
            left: 0; 
            z-index: 10; 
            vertical-align: top; 
            width: 150px; 
            transition: background 0.15s; 
        }

        .ward-row { display: flex; align-items: flex-start; gap: 8px; }
        .ward-ic { 
            width: 26px; 
            height: 26px; 
            border-radius: 6px; 
            background: var(--gm-primary-light); 
            color: var(--gm-primary); 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            font-size: 0.85rem; 
            flex-shrink: 0; 
        }

        .ward-txt .wn { font-size: 0.88rem; font-weight: 800; color: var(--gm-primary); line-height: 1.2; }
        .ward-txt .wm { font-size: 0.72rem; color: var(--gm-text-muted); font-weight: 600; margin-top: 2px; }
        .ward-txt .wb { 
            display: inline-flex; 
            align-items: center; 
            gap: 4px; 
            margin-top: 4px; 
            font-size: 0.68rem; 
            font-weight: 800; 
            color: var(--gm-primary); 
            background: var(--gm-primary-light); 
            padding: 2px 6px; 
            border-radius: 4px; 
            border: 1px solid var(--gm-border);
        }

        /* Day cell */
        .td-day { 
            padding: 6px; 
            border-right: 1px solid var(--gm-border); 
            vertical-align: top; 
            background: #ffffff;
        }
        .td-day:last-child { border-right: none; }
        .td-day.today { background: rgba(31, 107, 74, 0.03); }

        /* Shift Slot */
        .s-slot { 
            border-radius: 8px; 
            padding: 5px 7px; 
            margin-bottom: 5px; 
            min-height: 28px; 
            cursor: pointer; 
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1); 
            display: flex; 
            flex-direction: column; 
            gap: 3px; 
            border: 1.5px solid transparent; 
        }
        .s-slot:last-child { margin-bottom: 0; }
        .sl-m { background: var(--sh-m-bg); border-color: var(--sh-m-border); }
        .sl-e { background: var(--sh-e-bg); border-color: var(--sh-e-border); }
        .sl-n { background: var(--sh-n-bg); border-color: var(--sh-n-border); }
        
        .s-slot:hover { 
            transform: translateY(-1px); 
            box-shadow: 0 4px 10px rgba(31, 107, 74, 0.12); 
        }

        body.nurse-selected .s-slot:not(.has-nurse) { 
            border-color: var(--gm-primary) !important; 
            box-shadow: 0 0 0 2px var(--gm-primary-light); 
            animation: pulseSlot 1.5s infinite;
        }

        @keyframes pulseSlot {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.02); }
        }

        .slot-hd { 
            display: flex; 
            align-items: center; 
            justify-content: space-between; 
            gap: 4px; 
        }

        .stag { 
            font-size: 0.68rem; 
            font-weight: 800; 
            padding: 2px 5px; 
            border-radius: 4px; 
            color: #ffffff; 
            letter-spacing: 0.2px; 
            display: inline-flex;
            align-items: center;
            gap: 3px;
        }

        .stag-m { background: var(--sh-m-color); }
        .stag-e { background: var(--sh-e-color); }
        .stag-n { background: var(--sh-n-color); }
        
        .s-empty { 
            font-size: 0.7rem; 
            color: var(--gm-text-muted); 
            font-weight: 700; 
            opacity: 0.65; 
        }

        /* Nurse chip in slot */
        .n-chip { 
            display: flex; 
            align-items: center; 
            gap: 5px; 
            background: #ffffff; 
            border-radius: 6px; 
            padding: 3px 6px; 
            border: 1px solid var(--gm-border); 
            box-shadow: 0 1px 3px rgba(0,0,0,0.05); 
            transition: all 0.15s ease;
        }

        .chip-av { 
            width: 16px; 
            height: 16px; 
            border-radius: 4px; 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            font-size: 0.6rem; 
            font-weight: 800; 
            color: #ffffff; 
            flex-shrink: 0; 
        }
        .chav-m { background: var(--sh-m-color); }
        .chav-e { background: var(--sh-e-color); }
        .chav-n { background: var(--sh-n-color); }

        .chip-nm { 
            font-size: 0.78rem; 
            font-weight: 800; 
            color: var(--gm-primary); 
            flex: 1; 
            min-width: 0; 
            white-space: nowrap; 
            overflow: hidden; 
            text-overflow: ellipsis; 
            line-height: 1.2; 
        }

        .chip-rm { 
            width: 16px; 
            height: 16px; 
            border-radius: 50%; 
            background: rgba(220, 38, 38, 0.1); 
            color: #dc2626; 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            font-size: 0.65rem; 
            cursor: pointer; 
            opacity: 0.5; 
            transition: all 0.15s ease; 
            flex-shrink: 0; 
            margin-left: auto; 
        }
        .n-chip:hover .chip-rm { opacity: 1; }
        .chip-rm:hover { background: #dc2626; color: #ffffff; }
        
        .chip-conflict { 
            border-color: #dc2626 !important; 
            background: rgba(220, 38, 38, 0.08) !important; 
        }
        .conflict-icon { color: #dc2626; font-size: 0.75rem; flex-shrink: 0; }

        /* Week-off Row */
        .tr-wo .td-ward { background: rgba(220, 38, 38, 0.05); border-right-color: rgba(220, 38, 38, 0.2); }
        .tr-wo .ward-ic { background: rgba(220, 38, 38, 0.12); color: #dc2626; }
        .tr-wo .wn { color: #dc2626; }

        .wo-slot { 
            background: var(--sh-wo-bg); 
            border: 1.5px dashed var(--sh-wo-border); 
            border-radius: 8px; 
            padding: 5px 6px; 
            min-height: 28px; 
            cursor: pointer; 
            display: flex; 
            flex-direction: column; 
            gap: 4px; 
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .wo-slot:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 10px rgba(220, 38, 38, 0.12);
        }

        .wo-slot.drag-over {
            background: rgba(220, 38, 38, 0.18) !important;
            border-color: #dc2626 !important;
            transform: scale(1.02);
            box-shadow: 0 4px 12px rgba(220, 38, 38, 0.22);
        }

        body.nurse-selected .wo-slot:not(.has-nurse) {
            border-color: #dc2626 !important;
            box-shadow: 0 0 0 2px rgba(220, 38, 38, 0.25);
            animation: pulseSlot 1.5s infinite;
        }

        .wo-chip { 
            display: flex; 
            align-items: center; 
            gap: 4px; 
            background: #ffffff; 
            border: 1px solid rgba(220, 38, 38, 0.3); 
            border-radius: 5px; 
            padding: 2px 6px; 
            width: 100%; 
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        }
        .wo-chip span { 
            font-size: 0.76rem; 
            font-weight: 800; 
            color: #dc2626; 
            flex: 1; 
            white-space: nowrap; 
            overflow: hidden; 
            text-overflow: ellipsis; 
        }
        .wo-rm { 
            width: 15px; 
            height: 15px; 
            border-radius: 50%; 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            font-size: 0.6rem; 
            color: #dc2626; 
            cursor: pointer; 
            margin-left: auto;
            background: rgba(220, 38, 38, 0.1);
            transition: all 0.15s ease;
        }
        .wo-rm:hover {
            background: #dc2626;
            color: #ffffff;
        }

        /* ── Blueprint Org Tree Navigation ── */
        .bp-container { 
            display: flex; 
            flex-direction: column; 
            flex: 1; 
            background: var(--gm-bg); 
            overflow: auto; 
            padding: 20px 24px; 
            width: 100%; 
            box-sizing: border-box; 
        }

        .bp-header { 
            margin-bottom: 20px; 
            border-bottom: 2px solid var(--gm-border); 
            padding-bottom: 12px; 
            display: flex; 
            align-items: center; 
            justify-content: space-between;
            gap: 10px; 
        }

        .bp-title { 
            font-size: 1.25rem; 
            font-weight: 800; 
            color: var(--gm-primary); 
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .bp-hospital-card { 
            width: 100%; 
            max-width: 280px; 
            margin: 10px auto 0 auto; 
            padding: 16px; 
            border: 2px solid var(--gm-primary); 
            border-radius: 14px; 
            background: #ffffff; 
            text-align: center; 
            cursor: pointer; 
            transition: all 0.2s ease; 
            box-shadow: 0 4px 16px rgba(31, 107, 74, 0.08); 
        }

        .bp-hospital-card:hover { 
            transform: translateY(-2px); 
            box-shadow: 0 8px 24px rgba(31, 107, 74, 0.16); 
        }

        .bp-hospital-card i { 
            font-size: 1.8rem; 
            color: var(--gm-primary); 
            margin-bottom: 6px; 
            display: block; 
        }

        .bp-hospital-card span { 
            font-size: 1.05rem; 
            font-weight: 800; 
            color: var(--gm-primary); 
            letter-spacing: 0.5px; 
        }

        .bp-tabs-container { 
            display: flex; 
            gap: 10px; 
            overflow-x: auto; 
            padding: 20px 0; 
            margin: 0 auto; 
            width: 100%; 
            max-width: 1000px; 
            justify-content: center; 
            flex-wrap: wrap; 
        }

        .bp-tab { 
            padding: 10px 22px; 
            border: 1.5px solid var(--gm-border); 
            border-radius: 20px; 
            background: #ffffff; 
            color: var(--gm-primary); 
            font-weight: 800; 
            font-size: 0.86rem; 
            cursor: pointer; 
            white-space: nowrap; 
            transition: all 0.2s ease; 
            box-shadow: 0 2px 6px rgba(31, 107, 74, 0.05);
        }

        .bp-tab:hover, .bp-tab.active { 
            background: var(--gm-primary); 
            color: #f3efe6; 
            border-color: var(--gm-primary); 
            box-shadow: 0 4px 14px rgba(31, 107, 74, 0.25); 
            transform: translateY(-1px);
        }

        .bp-rooms-container { 
            display: flex; 
            gap: 12px; 
            overflow-x: auto; 
            margin: 0 auto; 
            width: 100%; 
            max-width: 1000px; 
            justify-content: center; 
            flex-wrap: wrap; 
            padding-bottom: 30px; 
        }

        .bp-room-card { 
            flex: 1 1 150px; 
            max-width: 200px; 
            padding: 14px; 
            border: 1.5px solid var(--gm-border); 
            border-radius: 12px; 
            background: #ffffff; 
            cursor: pointer; 
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1); 
            box-shadow: 0 2px 8px rgba(31, 107, 74, 0.05); 
        }

        .bp-room-card:hover { 
            border-color: var(--gm-primary); 
            box-shadow: 0 8px 20px rgba(31, 107, 74, 0.15); 
            transform: translateY(-2px); 
        }

        .bp-room-name { font-size: 0.92rem; font-weight: 800; color: var(--gm-primary); margin-bottom: 4px; }
        .bp-room-type { font-size: 0.74rem; color: var(--gm-text-muted); font-weight: 700; text-transform: uppercase; }
        .bp-room-beds { 
            font-size: 0.76rem; 
            font-weight: 800; 
            color: var(--gm-primary); 
            margin-top: 8px; 
            padding-top: 6px; 
            border-top: 1px solid var(--gm-border); 
        }

        /* ── Modals & Overlays ── */
        .modal-overlay { 
            position: fixed; 
            inset: 0; 
            background: rgba(31, 107, 74, 0.45); 
            backdrop-filter: blur(3px);
            z-index: 200; 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            opacity: 0; 
            pointer-events: none; 
            transition: opacity 0.2s; 
        }

        .modal-overlay.open { opacity: 1; pointer-events: all; }
        .modal-box { 
            background: #ffffff; 
            border-radius: 14px; 
            padding: 24px; 
            width: min(92vw, 520px); 
            max-height: 80vh; 
            overflow-y: auto; 
            box-shadow: 0 20px 60px rgba(31, 107, 74, 0.25); 
            border: 2px solid var(--gm-primary);
        }

        .modal-head { display: flex; align-items: center; justify-content: space-between; margin-bottom: 16px; }
        .modal-head h3 { font-size: 1.1rem; font-weight: 800; color: var(--gm-primary); display: flex; align-items: center; gap: 8px; }
        .modal-close { background: var(--gm-bg); border: none; border-radius: 8px; padding: 6px 12px; cursor: pointer; font-size: 0.9rem; font-weight: 800; color: var(--gm-primary); }

        .summary-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; margin-bottom: 16px; }
        .sg-card { background: var(--gm-bg); border-radius: 10px; padding: 12px; text-align: center; border: 1px solid var(--gm-border); }
        .sg-card .sv { font-size: 1.4rem; font-weight: 800; color: var(--gm-primary); }
        .sg-card .sl { font-size: 0.72rem; font-weight: 800; color: var(--gm-text-muted); text-transform: uppercase; margin-top: 2px; }

        .detail-table { width: 100%; border-collapse: collapse; font-size: 0.84rem; }
        .detail-table th { background: var(--gm-bg); padding: 8px 10px; text-align: left; font-weight: 800; color: var(--gm-primary); font-size: 0.72rem; text-transform: uppercase; border-bottom: 1.5px solid var(--gm-border); }
        .detail-table td { padding: 8px 10px; border-bottom: 1px solid var(--gm-bg); }

        /* Custom Confirm */
        #confirm-overlay { position: fixed; inset: 0; background: rgba(31, 107, 74, 0.45); backdrop-filter: blur(3px); z-index: 10000; display: flex; align-items: center; justify-content: center; opacity: 0; pointer-events: none; transition: opacity 0.2s ease; }
        #confirm-overlay.open { opacity: 1; pointer-events: all; }
        #confirm-box { background: #ffffff; border-radius: 14px; padding: 28px; width: min(90vw, 420px); border: 2px solid var(--gm-primary); text-align: center; }
        #confirm-title { font-size: 1.15rem; font-weight: 800; color: var(--gm-primary); margin-bottom: 8px; }
        #confirm-body { font-size: 0.88rem; font-weight: 600; color: var(--gm-text-body); line-height: 1.5; margin-bottom: 20px; }
        .confirm-btns { display: flex; gap: 10px; justify-content: center; }

        /* Floating Toast */
        #toast-overlay { position: fixed; top: 24px; right: 24px; z-index: 9999; pointer-events: none; }
        #toast { 
            background: var(--gm-primary); 
            color: #f3efe6; 
            border-radius: 10px; 
            padding: 14px 22px; 
            box-shadow: 0 10px 30px rgba(31, 107, 74, 0.3); 
            display: none; 
            align-items: center; 
            gap: 10px; 
            font-size: 0.88rem; 
            font-weight: 700; 
            pointer-events: all; 
            border: 1.5px solid rgba(243, 239, 230, 0.3);
        }

        /* Responsive Breakpoints */
        @media(max-width:1023px){
            .main-content { margin-left: 0; max-width: 100vw; }
            .nurse-panel { height: auto; position: static; width: 100%; }
            #nurse-pool { flex-direction: row; overflow-x: auto; padding: 10px; }
            .nurse-card { min-width: 150px; flex-shrink: 0; }
            .workspace { flex-direction: column; }
        }
    </style>
</head>
<body>

<?php $sp=__DIR__.'/includes/nurse_sidebar.php'; if(file_exists($sp)) include $sp; ?>

<div class="main-content">

    <!-- Conflict Warning Bar -->
    <div id="conflict-bar">
        <i class="fas fa-exclamation-triangle"></i>
        <span id="conflict-msg">Shift conflicts detected</span>
        <button class="btn btn-d" style="margin-left:auto;padding:3px 10px;font-size:0.75rem;" onclick="openConflictModal()">
            <i class="fas fa-search"></i> View Conflicts
        </button>
    </div>

    <!-- Top Navigation Bar -->
    <div class="top-bar">
        <div class="bar-left">
            <div class="bar-icon"><i class="fas fa-calendar-alt"></i></div>
            <div>
                <h1>Nurse Shift Assignment</h1>
                <p>Assign staff nurses to duty rosters with real-time conflict detection & auto-fill.</p>
            </div>
        </div>
        <div class="bar-right">
            <div class="date-range-box">
                <label>From</label>
                <input type="date" id="dr-start" value="<?php echo htmlspecialchars($startDateStr); ?>">
                <label>To</label>
                <input type="date" id="dr-end" value="<?php echo htmlspecialchars($endDateStr); ?>">
                <button class="btn btn-p" onclick="applyDateRange()" style="min-height:30px; padding:4px 10px;">
                    <i class="fas fa-check"></i> Apply
                </button>
            </div>
            <button class="btn" id="btn-vm" onclick="toggleViewMode()">
                <i class="fas fa-eye"></i> View Mode
            </button>
        </div>
    </div>

    <!-- Hospital Blueprint / Level 1 & 2 Navigation -->
    <div class="bp-container" id="blueprint-view">
        <div class="bp-header">
            <div class="bp-title"><i class="fas fa-hospital-alt"></i> Hospital Floor & Ward Blueprint Directory</div>
            <div class="bp-breadcrumb" id="bp-breadcrumb"></div>
        </div>
        <div id="bp-content"></div>
    </div>

    <!-- Workspace (Nurse Pool + Duty Calendar) -->
    <div class="workspace" id="shift-workspace" style="display: none;">

        <!-- Left Nurse Pool -->
        <div class="nurse-panel">
            <div class="np-head">
                <div class="np-title">
                    <span><i class="fas fa-user-nurse"></i> Available Nurses</span>
                    <span class="np-count" id="np-count">0</span>
                </div>
                <div class="np-search">
                    <i class="fas fa-search"></i>
                    <input type="text" id="ns-search" placeholder="Search nurse name..." oninput="filterPool(this.value)">
                </div>
                <div class="tap-hint"><i class="fas fa-hand-pointer"></i> Tap nurse &rarr; tap slot to assign</div>
            </div>
            <div class="shift-legend">
                <div class="leg-pill leg-m"><i class="fas fa-sun" style="font-size:0.6rem;"></i> Morning</div>
                <div class="leg-pill leg-e"><i class="fas fa-cloud-sun" style="font-size:0.6rem;"></i> Evening</div>
                <div class="leg-pill leg-n"><i class="fas fa-moon" style="font-size:0.6rem;"></i> Night</div>
            </div>
            <div id="nurse-pool"></div>
        </div>

        <!-- Right Calendar Workspace -->
        <div class="right-area">

            <!-- Control Bar -->
            <div class="ctrl-bar">
                <div class="counter-pills">
                    <div class="cpill cpill-m"><i class="fas fa-sun"></i> Morning <span class="cnt" id="cnt-m">0</span></div>
                    <div class="cpill cpill-e"><i class="fas fa-cloud-sun"></i> Evening <span class="cnt" id="cnt-e">0</span></div>
                    <div class="cpill cpill-n"><i class="fas fa-moon"></i> Night <span class="cnt" id="cnt-n">0</span></div>
                </div>
                <div style="display:flex; align-items:center; gap:8px;">
                    <button class="btn" onclick="goBackToFloor()"><i class="fas fa-arrow-left"></i> Change Floor/Room</button>
                    <span id="cal-breadcrumb" style="font-size:0.86rem; font-weight:800; color:var(--gm-primary);"></span>
                    <input type="hidden" id="ff" value="">
                    <input type="hidden" id="fw" value="">
                    <input type="hidden" id="fr" value="">
                </div>
                <div class="actions">
                    <label style="display:flex;align-items:center;gap:6px;font-size:0.8rem;font-weight:700;color:var(--gm-primary);cursor:pointer;background:var(--gm-primary-light);padding:6px 12px;border-radius:8px;border:1px solid var(--gm-border);">
                        <input type="checkbox" id="auto-fill-cb" checked style="accent-color:var(--gm-primary);width:15px;height:15px;cursor:pointer;"> Auto-fill Week
                    </label>
                    <button class="btn" onclick="openSummaryModal()"><i class="fas fa-chart-bar"></i> Summary</button>
                    <button class="btn btn-d" onclick="clearAll()"><i class="far fa-trash-alt"></i> Clear</button>
                    <button class="btn btn-p" onclick="saveSchedule()"><i class="fas fa-save"></i> Save Schedule</button>
                </div>
            </div>

            <!-- Calendar Table -->
            <div class="cal-wrap">
                <table class="sched">
                    <thead>
                        <tr>
                            <th class="th-ward">Wards / Rooms</th>
                            <!-- JS injects day headers -->
                        </tr>
                    </thead>
                    <tbody id="cal-body">
                        <!-- JS injects ward rows and slots -->
                    </tbody>
                </table>
            </div>

        </div><!-- /right-area -->
    </div><!-- /workspace -->
</div><!-- /main-content -->

<!-- Summary Modal -->
<div class="modal-overlay" id="modal-summary" onclick="closeSummaryModal(event)">
    <div class="modal-box">
        <div class="modal-head">
            <h3><i class="fas fa-chart-bar" style="color:var(--gm-primary);"></i> Week Duty Summary</h3>
            <button class="modal-close" onclick="document.getElementById('modal-summary').classList.remove('open')">&#10005;</button>
        </div>
        <div class="summary-grid" id="sum-grid"></div>
        <table class="detail-table">
            <thead>
                <tr><th>Nurse</th><th>M</th><th>E</th><th>N</th><th>W.O.</th><th>Total</th></tr>
            </thead>
            <tbody id="sum-tbody"></tbody>
        </table>
    </div>
</div>

<!-- Conflict Modal -->
<div class="modal-overlay" id="modal-conflict" onclick="closeConflictModal(event)">
    <div class="modal-box">
        <div class="modal-head">
            <h3 style="color:#dc2626;"><i class="fas fa-exclamation-triangle"></i> Conflict Details</h3>
            <button class="modal-close" onclick="document.getElementById('modal-conflict').classList.remove('open')">&#10005;</button>
        </div>
        <div id="conflict-list" style="display:flex;flex-direction:column;gap:10px;"></div>
    </div>
</div>

<!-- Custom Confirm Modal -->
<div id="confirm-overlay">
    <div id="confirm-box">
        <div id="confirm-title">Confirmation Required</div>
        <div id="confirm-body"></div>
        <div class="confirm-btns">
            <button class="btn" id="confirm-cancel" onclick="_confirmResolve(false)"><i class="fas fa-times"></i> Cancel</button>
            <button class="btn btn-p" id="confirm-ok" onclick="_confirmResolve(true)"><i class="fas fa-check"></i> Confirm</button>
        </div>
    </div>
</div>

<!-- Toast -->
<div id="toast-overlay">
    <div id="toast">
        <i class="fas fa-check-circle" id="toast-icon"></i>
        <span id="toast-msg">Action Completed</span>
    </div>
</div>

<script>
// -- DATA --
const MOCK_NURSES = <?php echo $dbNursesJson; ?>;
const MOCK_WARDS = <?php echo $dbWardsJson; ?>;
const DAYS = <?php echo $dbDaysJson; ?>;
const EXISTING_ASSIGNMENTS = <?php echo $existingAssignmentsJson; ?>;
const TODAY_STR = new Date().toISOString().split('T')[0];

let assignments = {};
let selectedNurseId = null;
let draggedNurseId = null;
let conflicts = [];
let currentFloorName = null;
let currentRoomType = null;

// -- BLUEPRINT LOGIC --
const DB_FLOORS = <?php echo json_encode($dbFloors); ?>;
const DB_WARDS = MOCK_WARDS;

function initBlueprint() {
    document.getElementById('shift-workspace').style.display = 'none';
    document.getElementById('bp-breadcrumb').innerHTML = `<span class="bp-crumb" style="font-weight:800; color:var(--gm-primary);">Home</span>`;
    
    let html = `
    <div class="bp-hospital-card" onclick="toggleFloors()">
        <i class="fas fa-hospital-alt"></i>
        <span>GM HOSPITAL & RESEARCH CENTER</span>
        <div style="font-size: 0.74rem; color: var(--gm-text-muted); margin-top: 4px; font-weight: 700; text-transform: uppercase;">Basaveshwaranagara</div>
    </div>
    <div class="bp-tabs-container" id="bp-floors-container" style="display:none;"></div>
    <div id="bp-rooms-container" class="bp-rooms-container"></div>
    `;
    
    document.getElementById('bp-content').innerHTML = html;
    
    // Auto-restore state if session has saved selection
    const savedFf = sessionStorage.getItem('bp_saved_ff');
    const savedFr = sessionStorage.getItem('bp_saved_fr');
    if (savedFf && savedFr) {
        const fc = document.getElementById('bp-floors-container');
        fc.style.display = 'flex';
        viewFloors();
        setTimeout(() => {
            selectFloor(savedFf);
            setTimeout(() => {
                viewCalendarType(savedFf, savedFr);
            }, 10);
        }, 10);
    }
}

function toggleFloors() {
    const fc = document.getElementById('bp-floors-container');
    if (fc.style.display === 'none') {
        fc.style.display = 'flex';
        if (fc.innerHTML.trim() === '') {
            viewFloors();
        }
    } else {
        fc.style.display = 'none';
        document.getElementById('bp-rooms-container').innerHTML = '';
        document.getElementById('shift-workspace').style.display = 'none';
    }
}

function viewFloors() {
    let html = ``;
    DB_FLOORS.forEach(floor => {
        html += `<div class="bp-tab" id="tab-${floor.replace(/\s+/g, '-')}" onclick="selectFloor('${floor}')"><i class="fas fa-layer-group"></i> ${floor}</div>`;
    });
    document.getElementById('bp-floors-container').innerHTML = html;
    document.getElementById('bp-rooms-container').innerHTML = '';
    document.getElementById('shift-workspace').style.display = 'none';
}

function selectFloor(floorName) {
    document.querySelectorAll('.bp-tab').forEach(t => t.classList.remove('active'));
    
    const tabEl = document.getElementById(`tab-${floorName.replace(/\s+/g, '-')}`);
    if(tabEl) tabEl.classList.add('active');
    
    const floorWards = DB_WARDS.filter(w => w.floor_name === floorName);
    let html = ``;
    
    if (floorWards.length === 0) {
        html = `<div style="color: var(--gm-text-muted); font-style: italic; width: 100%; text-align: center; padding: 20px;">No wards assigned to this floor.</div>`;
    } else {
        const roomTypes = {};
        floorWards.forEach(w => {
            if (!w.room_type) return;
            if (!roomTypes[w.room_type]) {
                roomTypes[w.room_type] = { type: w.room_type, beds: 0, wardCount: 0 };
            }
            roomTypes[w.room_type].beds += parseInt(w.beds) || 0;
            roomTypes[w.room_type].wardCount++;
        });
        
        const rtKeys = Object.keys(roomTypes);
        if (rtKeys.length === 0) {
            html = `<div style="color: var(--gm-text-muted); font-style: italic; width: 100%; text-align: center; padding: 20px;">No room types defined on this floor.</div>`;
        } else {
            rtKeys.sort().forEach(key => {
                const rt = roomTypes[key];
                html += `<div class="bp-room-card" id="rt-card-${rt.type.replace(/\s+/g, '-')}" onclick="viewCalendarType('${floorName}', '${rt.type}')">
                    <div class="bp-room-name">${rt.type}</div>
                    <div class="bp-room-type"><i class="fas fa-door-open"></i> ${rt.wardCount} Ward(s)</div>
                    <div class="bp-room-beds"><i class="fas fa-bed"></i> ${rt.beds} Beds</div>
                </div>`;
            });
        }
    }
    document.getElementById('bp-rooms-container').innerHTML = html;
    document.getElementById('shift-workspace').style.display = 'none';
}

function viewCalendarType(floorName, roomType) {
    document.getElementById('cal-breadcrumb').innerHTML = `<i class="fas fa-map-marker-alt"></i> ${floorName} &nbsp;&rsaquo;&nbsp; ${roomType}`;
    document.getElementById('ff').value = floorName;
    document.getElementById('fw').value = '';
    document.getElementById('fr').value = roomType;
    
    renderCalendarBody(floorName, roomType);
    loadExisting(floorName, roomType);
    
    document.querySelectorAll('.bp-room-card').forEach(c => c.classList.remove('active'));
    const rc = document.getElementById(`rt-card-${roomType.replace(/\s+/g, '-')}`);
    if (rc) rc.classList.add('active');
    
    document.getElementById('shift-workspace').style.display = 'flex';
    setTimeout(() => {
        document.getElementById('shift-workspace').scrollIntoView({ behavior: 'smooth', block: 'start' });
    }, 50);
}

function goBackToFloor() {
    document.getElementById('shift-workspace').style.display = 'none';
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

// -- BOOT --
document.addEventListener('DOMContentLoaded', () => {
    renderHeader();
    renderNursePool();
    setupDnD();
    initBlueprint();
});

// -- DATE RANGE --
async function applyDateRange(){
    const s = document.getElementById('dr-start').value;
    const e = document.getElementById('dr-end').value;
    const ff = document.getElementById('ff').value;
    const fr = document.getElementById('fr').value;
    
    if(Object.keys(assignments).length > 0) {
        const ok = await showConfirm({
            title: 'Unsaved Assignments',
            message: 'You have unsaved assignments that will be lost if you change the date range. Do you want to continue?',
            okLabel: 'Yes, Continue'
        });
        if (!ok) return;
    }
    
    if (ff && fr) {
        sessionStorage.setItem('bp_saved_ff', ff);
        sessionStorage.setItem('bp_saved_fr', fr);
    } else {
        sessionStorage.removeItem('bp_saved_ff');
        sessionStorage.removeItem('bp_saved_fr');
    }
    
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = 'shift_assignment.php';
    form.style.display = 'none';
    
    const inputS = document.createElement('input');
    inputS.type = 'hidden';
    inputS.name = 'start_date';
    inputS.value = s;
    form.appendChild(inputS);
    
    const inputE = document.createElement('input');
    inputE.type = 'hidden';
    inputE.name = 'end_date';
    inputE.value = e;
    form.appendChild(inputE);
    
    document.body.appendChild(form);
    form.submit();
}

// -- RENDER HEADER --
function renderHeader(){
    const tr = document.querySelector('table.sched thead tr');
    DAYS.forEach(day => {
        const [dd, dm] = day.date.split(' ');
        const isT = day.fullDate === TODAY_STR;
        tr.innerHTML += `<th class="th-day ${isT ? 'today' : ''}">
            <div class="dn">${day.short}</div>
            <div class="dd">${dd}</div>
            <div class="dm">${dm}</div>
            ${isT ? '<div class="today-badge">TODAY</div>' : ''}
        </th>`;
    });
}

// -- RENDER CALENDAR BODY --
function renderCalendarBody(floorName, roomType){
    currentFloorName = floorName;
    currentRoomType = roomType;
    assignments = {};
    conflicts = [];
    
    const body = document.getElementById('cal-body');
    let html = `<tr class="tr-wo">
        <td class="td-ward">
            <div class="ward-row">
                <div class="ward-ic"><i class="fas fa-coffee"></i></div>
                <div class="ward-txt">
                    <div class="wn">Staff Week Off</div>
                    <div class="wm">Weekly Rest Day</div>
                </div>
            </div>
        </td>`;
    for(let i=0; i<DAYS.length; i++){
        const sid = `weekoff_${i}`, isT = DAYS[i].fullDate === TODAY_STR;
        html += `<td class="td-day ${isT ? 'today' : ''}">
            <div class="wo-slot s-slot" id="${sid}" data-shift="wo" onclick="handleClick('${sid}')">
                <span style="font-size:0.72rem;color:#dc2626;font-weight:700;opacity:0.6;">+ Add Off</span>
            </div>
        </td>`;
    }
    html += '</tr>';

    const visibleWards = MOCK_WARDS.filter(w => w.floor_name === floorName && w.room_type === roomType);
    
    visibleWards.forEach(ward => {
        let row = `<tr id="row-${ward.id}">
            <td class="td-ward">
                <div class="ward-row">
                    <div class="ward-ic"><i class="fas fa-procedures"></i></div>
                    <div class="ward-txt">
                        <div class="wn">${ward.name}</div>
                        <div class="wm">${ward.type}</div>
                        <span class="wb"><i class="fas fa-bed"></i> ${ward.beds} Beds</span>
                    </div>
                </div>
            </td>`;
        for(let i=0; i<DAYS.length; i++){
            const isT = DAYS[i].fullDate === TODAY_STR;
            row += `<td class="td-day ${isT ? 'today' : ''}">`;
            ['m','e','n'].forEach(sh => {
                const sid = `${ward.id}_${i}_${sh}`;
                const label = sh === 'm' ? 'Morn' : (sh === 'e' ? 'Eve' : 'Night');
                const sIcon = sh === 'm' ? 'fa-sun' : (sh === 'e' ? 'fa-cloud-sun' : 'fa-moon');
                row += `<div class="s-slot sl-${sh}" id="${sid}" data-shift="${sh}" onclick="handleClick('${sid}')">
                    <div class="slot-hd">
                        <span class="stag stag-${sh}"><i class="fas ${sIcon}"></i> ${label}</span>
                        <span class="s-empty">Assign</span>
                    </div>
                </div>`;
            });
            row += `</td>`;
        }
        row += `</tr>`;
        html += row;
    });
    
    body.innerHTML = html;
    updateCounters();
}

// -- NURSE POOL --
function renderNursePool(filter=''){
    const pool = document.getElementById('nurse-pool');
    pool.innerHTML = '';
    const lf = filter.toLowerCase();
    const list = MOCK_NURSES.filter(n => !lf || n.name.toLowerCase().includes(lf));
    document.getElementById('np-count').textContent = list.length;
    
    if(!list.length){ 
        pool.innerHTML = `<div style="text-align:center;padding:18px;color:var(--gm-text-muted);font-size:0.8rem;font-weight:700;"><i class="fas fa-search" style="display:block;font-size:1.5rem;margin-bottom:6px;opacity:.4;"></i>No nurses found</div>`; 
        return; 
    }
    
    list.forEach(n => {
        const ini = n.name.split(' ').map(w => w[0]).join('').slice(0, 2).toUpperCase();
        const lv = n.status === 'On Leave';
        pool.innerHTML += `<div class="nurse-card ${lv ? 'on-leave' : ''}" draggable="true" id="nurse_${n.id}" data-id="${n.id}" onclick="selectNurse('${n.id}')" title="${n.name}">
            <div class="nc-avatar">${ini}</div>
            <div class="nc-info">
                <div class="nc-name">${n.name}</div>
                <div class="nc-role">${n.role || 'Staff Nurse'}</div>
            </div>
            <div class="nc-dot ${lv ? 'dot-lv' : 'dot-av'}" title="${n.status}"></div>
        </div>`;
    });
}
function filterPool(v){ renderNursePool(v); }

// -- SELECT NURSE --
function selectNurse(id){
    document.querySelectorAll('.nurse-card').forEach(el => el.classList.remove('selected'));
    if(selectedNurseId === id){ 
        selectedNurseId = null; 
        document.body.classList.remove('nurse-selected');
        return; 
    }
    selectedNurseId = id;
    const card = document.getElementById('nurse_' + id);
    if(card) card.classList.add('selected');
    
    document.body.classList.add('nurse-selected');
    const n = MOCK_NURSES.find(n => n.id == id);
    toast('Selected ' + (n ? n.name : id) + ' — Tap any shift or week-off slot to assign');
}
function clearSelection(){
    selectedNurseId = null;
    document.querySelectorAll('.nurse-card').forEach(el => el.classList.remove('selected'));
    document.body.classList.remove('nurse-selected');
}
function handleClick(slotId){ 
    if(selectedNurseId) assign(selectedNurseId, slotId); 
}

// -- DRAG & DROP --
function setupDnD(){
    document.addEventListener('dragstart', e => { 
        const c = e.target.closest('.nurse-card'); 
        if(c){
            draggedNurseId = c.dataset.id;
            c.style.opacity = '.4';
            e.dataTransfer.effectAllowed = 'copyMove';
            e.dataTransfer.setData('text/plain', draggedNurseId);
        } 
    });
    document.addEventListener('dragend', e => { 
        const c = e.target.closest('.nurse-card'); 
        if(c){
            c.style.opacity = '1';
            setTimeout(() => { draggedNurseId = null; }, 200); 
        } 
    });
    document.addEventListener('dragover', e => { 
        const s = e.target.closest('.s-slot, .wo-slot'); 
        if(s){
            e.preventDefault(); 
            e.dataTransfer.dropEffect = 'copy';
            s.classList.add('drag-over'); 
        }
    });
    document.addEventListener('dragleave', e => { 
        const s = e.target.closest('.s-slot, .wo-slot'); 
        if(s) s.classList.remove('drag-over'); 
    });
    document.addEventListener('drop', e => { 
        const s = e.target.closest('.s-slot, .wo-slot'); 
        if(s){ 
            e.preventDefault();
            s.classList.remove('drag-over'); 
            const nId = e.dataTransfer.getData('text/plain') || draggedNurseId;
            if(nId) assign(nId, s.id); 
        } 
    });
}

// -- ASSIGN --
function assign(nurseId, slotId, isInit=false){
    const nurse = MOCK_NURSES.find(n => n.id == nurseId);
    if(!nurse) return;
    if(nurse.status === 'On Leave'){ if(!isInit) toast('Nurse is currently On Leave', true); return; }
    
    const parts = slotId.split('_');
    if(parts[0] === 'weekoff'){
        if(!assignments[slotId]) assignments[slotId] = [];
        if(!assignments[slotId].find(n => n.id == nurseId)){ 
            assignments[slotId].push(nurse); 
            refreshSlot(slotId); 
        }
    } else {
        const wardId = parts[0] + '_' + parts[1];
        const startDay = parseInt(parts[2]);
        const sh = parts[3];
        const autoFill = document.getElementById('auto-fill-cb') ? document.getElementById('auto-fill-cb').checked : false;
        
        const sDay = (isInit || !autoFill) ? startDay : 0;
        const eDay = (isInit || !autoFill) ? startDay + 1 : DAYS.length;
        
        for(let i=sDay; i<eDay; i++){
            const tgt = `${wardId}_${i}_${sh}`;
            if(!assignments[tgt]) assignments[tgt] = [];
            if(!assignments[tgt].find(n => n.id == nurseId)){ 
                assignments[tgt].push(nurse); 
                refreshSlot(tgt); 
            }
        }
    }
    if(!isInit){
        updateCounters(); 
        detectConflicts(); 
        clearSelection();
        toast('Assigned ' + nurse.name + (parts[0]==='weekoff' ? ' (Week Off)' : ''));
    }
}

window.removeFromSlot = function(slotId, nurseId){
    if(!assignments[slotId]) return;
    assignments[slotId] = assignments[slotId].filter(n => n.id != nurseId);
    if(!assignments[slotId].length) delete assignments[slotId];
    refreshSlot(slotId); 
    updateCounters(); 
    detectConflicts();
};

// -- REFRESH SLOT --
function refreshSlot(slotId){
    const slot = document.getElementById(slotId);
    if(!slot) return;
    const isWO = slotId.startsWith('weekoff');
    const nurses = assignments[slotId];
    const sh = slot.dataset.shift;
    const label = sh === 'm' ? 'Morn' : (sh === 'e' ? 'Eve' : 'Night');
    const sIcon = sh === 'm' ? 'fa-sun' : (sh === 'e' ? 'fa-cloud-sun' : 'fa-moon');
    
    if(isWO){
        if(nurses && nurses.length){
            slot.classList.add('has-nurse');
            slot.innerHTML = nurses.map(n => `<div class="wo-chip" title="${n.name}"><i class="fas fa-coffee" style="font-size:0.7rem;color:#dc2626;flex-shrink:0;"></i><span>${n.name}</span><div class="wo-rm" onclick="event.stopPropagation(); removeFromSlot('${slotId}','${n.id}')" title="Remove"><i class="fas fa-times"></i></div></div>`).join('');
        } else {
            slot.classList.remove('has-nurse');
            slot.innerHTML = `<span style="font-size:0.72rem;color:#dc2626;font-weight:700;opacity:0.6;">+ Add Off</span>`;
        }
    } else {
        if(nurses && nurses.length){
            slot.classList.add('has-nurse');
            slot.innerHTML = `<div class="slot-hd"><span class="stag stag-${sh}"><i class="fas ${sIcon}"></i> ${label}</span></div>`
                + nurses.map(n => {
                    const ini = n.name.split(' ').map(w => w[0]).join('').slice(0, 2).toUpperCase();
                    const isConf = conflicts.some(c => c.nurseId == n.id && c.slotId === slotId);
                    return `<div class="n-chip ${isConf ? 'chip-conflict' : ''}" title="${n.name}">
                        <div class="chip-av chav-${sh}">${ini}</div>
                        ${isConf ? '<i class="fas fa-exclamation-triangle conflict-icon" title="Conflict: Double booked"></i>' : ''}
                        <span class="chip-nm">${n.name}</span>
                        <div class="chip-rm" onclick="event.stopPropagation(); removeFromSlot('${slotId}','${n.id}')" title="Remove"><i class="fas fa-times"></i></div>
                    </div>`;
                }).join('');
        } else {
            slot.classList.remove('has-nurse');
            slot.innerHTML = `<div class="slot-hd"><span class="stag stag-${sh}"><i class="fas ${sIcon}"></i> ${label}</span><span class="s-empty">Assign</span></div>`;
        }
    }
}

// -- CONFLICT DETECTION --
function detectConflicts(){
    conflicts = [];
    const byND = {};
    Object.keys(assignments).forEach(slotId => {
        if(slotId.startsWith('weekoff')) return;
        const p = slotId.split('_');
        const key_day = p[2], sh = p[3];
        assignments[slotId].forEach(nurse => {
            const k = nurse.id + '_' + key_day + '_' + sh;
            if(!byND[k]) byND[k] = [];
            byND[k].push(slotId);
        });
    });
    Object.entries(byND).forEach(([key, slots]) => {
        if(slots.length > 1){
            const nId = key.split('_')[0];
            slots.forEach(sid => { conflicts.push({nurseId:nId, slotId:sid}); });
        }
    });
    Object.keys(assignments).forEach(sid => refreshSlot(sid));
    const bar = document.getElementById('conflict-bar');
    if(conflicts.length > 0){
        const un = [...new Set(conflicts.map(c => c.nurseId))].length;
        document.getElementById('conflict-msg').textContent = un + ' nurse(s) double-booked on the same duty shift';
        bar.style.display = 'flex';
    } else { 
        bar.style.display = 'none'; 
    }
}

// -- LOAD EXISTING --
function loadExisting(floorName, roomType){
    if(!EXISTING_ASSIGNMENTS || !EXISTING_ASSIGNMENTS.length) return;
    EXISTING_ASSIGNMENTS.forEach(rec => {
        const nId = 'n_' + rec.nurse_id;
        const di = DAYS.findIndex(d => d.fullDate === rec.shift_date);
        if(di === -1) return;
        let slotId;
        if(rec.shift_type === 'Week Off'){ 
            slotId = `weekoff_${di}`; 
        } else {
            if(rec.floor_name !== floorName || rec.room_type !== roomType) return;
            const ward = MOCK_WARDS.find(w => w.ward_name === rec.ward_name && w.floor_name === rec.floor_name && w.room_type === rec.room_type);
            if(!ward) return;
            const sh = rec.shift_type === 'Morning' ? 'm' : (rec.shift_type === 'Evening' ? 'e' : 'n');
            slotId = `${ward.id}_${di}_${sh}`;
        }
        assign(nId, slotId, true);
    });
    updateCounters();
    detectConflicts();
}

// -- COUNTERS --
function updateCounters(){
    let m = 0, e = 0, n = 0;
    Object.keys(assignments).forEach(k => { 
        const c = assignments[k].length; 
        if(k.endsWith('_m')) m += c; 
        if(k.endsWith('_e')) e += c; 
        if(k.endsWith('_n')) n += c; 
    });
    document.getElementById('cnt-m').textContent = m;
    document.getElementById('cnt-e').textContent = e;
    document.getElementById('cnt-n').textContent = n;
}

// -- VIEW MODE --
function toggleViewMode(){
    document.body.classList.toggle('vm');
    const on = document.body.classList.contains('vm');
    const btn = document.getElementById('btn-vm');
    btn.innerHTML = on ? '<i class="fas fa-edit"></i> Edit Mode' : '<i class="fas fa-eye"></i> View Mode';
    btn.classList.toggle('on', on);
    document.querySelectorAll('#cal-body tr').forEach(row => {
        if(on){ 
            const has = row.querySelector('.n-chip, .wo-chip'); 
            row.style.display = has ? '' : 'none'; 
        } else { 
            row.style.display = ''; 
        }
    });
}

// -- CLEAR --
async function clearAll(){
    const ok = await showConfirm({
        title: 'Clear Current Floor Assignments',
        message: 'This will remove all nurse duty assignments for this floor. Are you sure you want to continue?',
        okLabel: 'Yes, Clear'
    });
    if (!ok) return;
    Object.keys(assignments).forEach(k => { 
        [...assignments[k]].forEach(n => removeFromSlot(k, n.id)); 
    });
    
    sessionStorage.removeItem('bp_saved_ff');
    sessionStorage.removeItem('bp_saved_fr');
    initBlueprint();
}

// -- SUMMARY --
function openSummaryModal(){
    let tm = 0, te = 0, tn = 0;
    Object.keys(assignments).forEach(k => { 
        const c = assignments[k].length; 
        if(k.endsWith('_m')) tm += c; 
        if(k.endsWith('_e')) te += c; 
        if(k.endsWith('_n')) tn += c; 
    });
    document.getElementById('sum-grid').innerHTML = `
        <div class="sg-card"><div class="sv" style="color:var(--sh-m-color)">${tm}</div><div class="sl">Morning</div></div>
        <div class="sg-card"><div class="sv" style="color:var(--sh-e-color)">${te}</div><div class="sl">Evening</div></div>
        <div class="sg-card"><div class="sv" style="color:var(--sh-n-color)">${tn}</div><div class="sl">Night</div></div>`;
    
    const nm = {};
    Object.keys(assignments).forEach(k => { 
        assignments[k].forEach(nurse => { 
            if(!nm[nurse.id]) nm[nurse.id] = { name: nurse.name, m: 0, e: 0, n: 0, wo: 0 }; 
            if(k.startsWith('weekoff')) nm[nurse.id].wo++; 
            else if(k.endsWith('_m')) nm[nurse.id].m++; 
            else if(k.endsWith('_e')) nm[nurse.id].e++; 
            else if(k.endsWith('_n')) nm[nurse.id].n++; 
        }); 
    });
    
    const rows = Object.values(nm).sort((a, b) => a.name.localeCompare(b.name));
    document.getElementById('sum-tbody').innerHTML = rows.length
        ? rows.map(r => `<tr><td><strong>${r.name}</strong></td><td><b style="color:var(--sh-m-color)">${r.m}</b></td><td><b style="color:var(--sh-e-color)">${r.e}</b></td><td><b style="color:var(--sh-n-color)">${r.n}</b></td><td>${r.wo}</td><td><strong>${r.m + r.e + r.n + r.wo}</strong></td></tr>`).join('')
        : '<tr><td colspan="6" style="text-align:center;color:var(--gm-text-muted);padding:14px;">No duty assignments logged yet</td></tr>';
    
    document.getElementById('modal-summary').classList.add('open');
}
function closeSummaryModal(e){ 
    if(e.target === e.currentTarget) document.getElementById('modal-summary').classList.remove('open'); 
}

// -- CONFLICT MODAL --
function openConflictModal(){
    const byNurse = {};
    conflicts.forEach(c => { 
        const nurse = MOCK_NURSES.find(n => n.id == c.nurseId); 
        if(!nurse) return; 
        if(!byNurse[c.nurseId]) byNurse[c.nurseId] = { name: nurse.name, slots: [] }; 
        byNurse[c.nurseId].slots.push(c.slotId); 
    });
    
    document.getElementById('conflict-list').innerHTML = Object.values(byNurse).map(b => `<div style="background:rgba(220,38,38,0.06);border:1.5px solid rgba(220,38,38,0.25);border-radius:10px;padding:12px;">
        <div style="font-size:0.9rem;font-weight:800;color:#dc2626;margin-bottom:6px;"><i class="fas fa-user-nurse"></i> ${b.name}</div>
        <div style="font-size:0.8rem;color:var(--gm-text-muted);font-weight:700;">Double-booked across multiple locations:<br>
        ${b.slots.map(sid => {
            const p = sid.split('_');
            const di = parseInt(p[2]);
            const sh = p[3] === 'm' ? 'Morning' : (p[3] === 'e' ? 'Evening' : 'Night');
            return `<span style="display:inline-block;margin:3px;background:rgba(220,38,38,0.15);padding:2px 8px;border-radius:6px;font-weight:800;color:#dc2626;">${DAYS[di]?.date} — ${sh}</span>`;
        }).join('')}
        </div></div>`).join('');
    
    document.getElementById('modal-conflict').classList.add('open');
}
function closeConflictModal(e){ 
    if(e.target === e.currentTarget) document.getElementById('modal-conflict').classList.remove('open'); 
}

// -- SAVE SCHEDULE --
async function saveSchedule(){
    if(!Object.keys(assignments).length){ toast('No shift assignments entered to save', true); return; }
    if(conflicts.length > 0) {
        const ok = await showConfirm({
            title: 'Duty Conflicts Detected',
            message: conflicts.length + ' nurse assignments have double-booked shifts. Do you still want to save?',
            okLabel: 'Save Anyway'
        });
        if (!ok) return;
    }
    
    const payload = [];
    Object.keys(assignments).forEach(slotId => { 
        assignments[slotId].forEach(nurse => { 
            const parts = slotId.split('_'), dbId = nurse.id.toString().replace('n_', ''); 
            if(parts[0] === 'weekoff'){ 
                const di = parseInt(parts[1]); 
                payload.push({
                    nurse_id: dbId,
                    nurse_name: nurse.name,
                    shift_date: DAYS[di].fullDate,
                    shift_type: 'Week Off',
                    floor_name: null,
                    ward_name: null,
                    room_type: null
                }); 
            } else { 
                const wardId = parts[0] + '_' + parts[1], di = parseInt(parts[2]), sh = parts[3];
                const st = sh === 'm' ? 'Morning' : (sh === 'e' ? 'Evening' : 'Night');
                const ward = MOCK_WARDS.find(w => w.id === wardId); 
                payload.push({
                    nurse_id: dbId,
                    nurse_name: nurse.name,
                    shift_date: DAYS[di].fullDate,
                    shift_type: st,
                    floor_name: ward?.floor_name || null,
                    ward_name: ward?.ward_name || null,
                    room_type: ward?.room_type || null
                }); 
            } 
        }); 
    });
    
    payload.forEach(r => { 
        if(r.shift_type === 'Week Off'){ 
            const ws = payload.find(p => p.nurse_id === r.nurse_id && p.shift_type !== 'Week Off'); 
            if(ws){ 
                r.floor_name = ws.floor_name; 
                r.ward_name = ws.ward_name; 
                r.room_type = ws.room_type; 
            } 
        } 
    });
    
    const btn = event.currentTarget;
    const orig = btn.innerHTML;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving Schedule...';
    btn.disabled = true;
    
    fetch('save_shift_schedule.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            startDate: DAYS[0].fullDate,
            endDate: DAYS[DAYS.length - 1].fullDate,
            shifts: payload
        })
    })
    .then(r => r.json())
    .then(data => { 
        if(data.status === 'success'){ 
            toast('Shift Schedule Saved Successfully!'); 
            btn.innerHTML = '<i class="fas fa-check"></i> Saved!'; 
            setTimeout(() => window.location.reload(), 1200); 
        } else { 
            toast('Error: ' + data.message, true); 
            btn.innerHTML = orig; 
            btn.disabled = false; 
        } 
    })
    .catch(() => { 
        toast('Network connection error', true); 
        btn.innerHTML = orig; 
        btn.disabled = false; 
    });
}

// -- CUSTOM CONFIRM MODAL --
let _confirmResolve = null;
function showConfirm({ title='Confirmation Required', message='', okLabel='Confirm' } = {}) {
    return new Promise(resolve => {
        _confirmResolve = (val) => {
            document.getElementById('confirm-overlay').classList.remove('open');
            resolve(val);
        };
        document.getElementById('confirm-title').textContent = title;
        document.getElementById('confirm-body').textContent = message;
        document.getElementById('confirm-ok').innerHTML = `<i class="fas fa-check"></i> ${okLabel}`;
        document.getElementById('confirm-overlay').classList.add('open');
    });
}

// -- FLOATING TOAST --
function toast(msg, isErr=false) {
    const t = document.getElementById('toast');
    const msgEl = document.getElementById('toast-msg');
    const icon = document.getElementById('toast-icon');
    
    t.style.background = isErr ? '#dc2626' : 'var(--gm-primary)';
    icon.className = isErr ? 'fas fa-exclamation-circle' : 'fas fa-check-circle';
    msgEl.textContent = msg;
    
    t.style.display = 'flex';
    clearTimeout(t._timer);
    t._timer = setTimeout(() => { t.style.display = 'none'; }, 3200);
}
</script>
</body>
</html>
