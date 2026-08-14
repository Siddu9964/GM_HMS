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
$startDateStr = '2026-08-16'; // Default test week
if (isset($_GET['start_date']) && !empty($_GET['start_date'])) {
    $startDateStr = $_GET['start_date'];
} elseif (isset($_SESSION['bp_start_date']) && !empty($_SESSION['bp_start_date'])) {
    $startDateStr = $_SESSION['bp_start_date'];
}
$startDate = new DateTime($startDateStr);

// Determine End Date
$hasEndDate = false;
$endDateStr = '2026-08-22'; // Default test week
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
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --bg:#F3EFE6; --primary:#1F6B4A; --pl:#2A8F63; --pd:#144731;
            --white:#fff; --text:#1a1a1a; --muted:#7a8694;
            --border:rgba(31,107,74,0.13);
            --sm:0 2px 8px rgba(31,107,74,0.07);
            --md:0 6px 20px rgba(31,107,74,0.10);
            --sm-r:10px; --md-r:16px; --lg-r:20px;
            --cm:#10B981; --cm-bg:#ecfdf5; --cm-b:rgba(16,185,129,0.22);
            --ce:#F59E0B; --ce-bg:#fffbeb; --ce-b:rgba(245,158,11,0.22);
            --cn:#6366F1; --cn-bg:#eef2ff; --cn-b:rgba(99,102,241,0.22);
            --danger:#EF4444;
        }
        *,*::before,*::after{box-sizing:border-box;margin:0;padding:0;}
        body{font-family:'Plus Jakarta Sans',sans-serif;background:var(--bg);color:var(--text);display:flex;min-height:100vh;overflow-x:hidden;}
        .main-content{flex:1;margin-left:185px;display:flex;flex-direction:column;min-height:100vh;overflow:hidden;max-width:calc(100vw - 185px);}

        /* Top Bar */
        .top-bar{background:var(--white);padding:13px 22px;display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;border-bottom:1px solid var(--border);box-shadow:var(--sm);position:sticky;top:0;z-index:50;}
        .bar-left{display:flex;align-items:center;gap:12px;}
        .bar-icon{width:42px;height:42px;flex-shrink:0;background:linear-gradient(135deg,var(--primary),var(--pl));border-radius:var(--md-r);display:flex;align-items:center;justify-content:center;color:#fff;font-size:16px;box-shadow:0 4px 12px rgba(31,107,74,0.28);}
        .bar-left h1{font-size:18px;font-weight:800;color:var(--pd);letter-spacing:-0.3px;}
        .bar-left p{font-size:11px;color:var(--muted);font-weight:500;margin-top:2px;}
        .bar-right{display:flex;align-items:center;gap:8px;flex-wrap:wrap;}
        .week-nav{display:flex;align-items:center;gap:5px;background:var(--bg);border:1px solid var(--border);border-radius:var(--sm-r);padding:4px 10px;}
        .week-nav button{background:none;border:none;cursor:pointer;color:var(--primary);font-size:13px;padding:2px 6px;border-radius:6px;transition:background 0.15s;}
        .week-nav button:hover{background:rgba(31,107,74,0.1);}
        .week-nav .wlbl{font-size:13px;font-weight:700;color:var(--pd);min-width:150px;text-align:center;}

        .btn{display:inline-flex;align-items:center;gap:6px;padding:7px 14px;border-radius:var(--sm-r);font-family:'Plus Jakarta Sans',sans-serif;font-size:12px;font-weight:700;cursor:pointer;border:none;transition:all 0.16s ease;white-space:nowrap;}
        .btn-p{background:linear-gradient(135deg,var(--primary),var(--pl));color:#fff;box-shadow:0 3px 12px rgba(31,107,74,0.22);}
        .btn-p:hover{transform:translateY(-1px);box-shadow:0 6px 18px rgba(31,107,74,0.33);}
        .btn-g{background:transparent;border:1.5px solid var(--border);color:var(--text);}
        .btn-g:hover{border-color:var(--primary);color:var(--primary);background:rgba(31,107,74,0.04);}
        .btn-d{background:transparent;border:1.5px solid rgba(239,68,68,0.3);color:var(--danger);}
        .btn-d:hover{background:rgba(239,68,68,0.06);}
        .btn-v{background:var(--bg);border:1.5px solid var(--border);color:var(--pd);}
        .btn-v.on{background:var(--pd);color:#fff;border-color:var(--pd);}

        /* Workspace */
        .workspace{display:flex;flex:1;min-height:0;}

        /* Nurse Panel */
        .nurse-panel{width:170px;min-width:170px;flex-shrink:0;background:var(--white);border-right:1px solid var(--border);display:flex;flex-direction:column;height:calc(100vh - 70px);overflow:hidden;position:sticky;top:70px;z-index:10;box-sizing:border-box;}
        .np-head{padding:7px 8px 5px;border-bottom:1px solid var(--border);}
        .np-title{font-size:9.5px;font-weight:800;color:var(--pd);text-transform:uppercase;letter-spacing:0.5px;display:flex;align-items:center;justify-content:space-between;margin-bottom:5px;}
        .np-count{background:linear-gradient(135deg,var(--primary),var(--pl));color:#fff;padding:1px 6px;border-radius:20px;font-size:9.5px;font-weight:800;}
        .np-search{position:relative;}
        .np-search i{position:absolute;left:6px;top:50%;transform:translateY(-50%);color:var(--muted);font-size:10px;}
        .np-search input{width:100%;padding:5px 6px 5px 22px;border:1.5px solid var(--border);border-radius:var(--sm-r);font-family:'Plus Jakarta Sans',sans-serif;font-size:10.5px;font-weight:600;color:var(--text);background:var(--bg);outline:none;transition:border-color 0.18s;}
        .np-search input:focus{border-color:var(--primary);}
        .tap-hint{margin-top:4px;padding:3px 6px;background:rgba(31,107,74,0.06);border:1px dashed rgba(31,107,74,0.2);border-radius:6px;font-size:9px;color:var(--pd);font-weight:600;text-align:center;}
        .shift-legend{padding:4px 8px;border-bottom:1px solid var(--border);display:flex;gap:3px;flex-wrap:wrap;}
        .leg-pill{display:flex;align-items:center;gap:2px;padding:1px 5px;border-radius:20px;font-size:8.5px;font-weight:700;}
        .leg-m{background:var(--cm-bg);color:var(--cm);border:1px solid var(--cm-b);}
        .leg-e{background:var(--ce-bg);color:var(--ce);border:1px solid var(--ce-b);}
        .leg-n{background:var(--cn-bg);color:var(--cn);border:1px solid var(--cn-b);}
        .leg-dot{width:4px;height:4px;border-radius:50%;}
        .leg-m .leg-dot{background:var(--cm);}.leg-e .leg-dot{background:var(--ce);}.leg-n .leg-dot{background:var(--cn);}
        #nurse-pool{flex:1;overflow-y:auto;padding:5px;display:flex;flex-direction:column;gap:3px;}
        #nurse-pool::-webkit-scrollbar{width:2px;}#nurse-pool::-webkit-scrollbar-thumb{background:rgba(31,107,74,0.18);border-radius:2px;}

        .nurse-card{background:var(--bg);border:1px solid var(--border);border-radius:8px;padding:4px 6px;cursor:pointer;display:flex;align-items:center;gap:5px;transition:all 0.16s ease;position:relative;overflow:hidden;min-height:32px;}
        .nurse-card::before{content:'';position:absolute;left:0;top:0;bottom:0;width:2px;background:var(--primary);opacity:0;transition:opacity 0.16s;border-radius:2px 0 0 2px;}
        .nurse-card:hover{background:#fff;border-color:var(--primary);transform:translateX(1px);box-shadow:var(--sm);}
        .nurse-card:hover::before{opacity:1;}
        .nurse-card.selected{background:#fff;border-color:var(--cm);box-shadow:0 0 0 2px rgba(16,185,129,0.18);transform:translateX(1px);}
        .nurse-card.selected::before{opacity:1;background:var(--cm);}
        .nurse-card.on-leave{opacity:0.5;}
        .nc-avatar{width:22px;height:22px;border-radius:50%;background:linear-gradient(135deg,var(--primary),var(--pl));display:flex;align-items:center;justify-content:center;color:#fff;font-size:8px;font-weight:800;flex-shrink:0;}
        .nc-info{flex:1;min-width:0;}
        .nc-name{font-size:10px;font-weight:700;color:var(--pd);line-height:1.2;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;display:block;}
        .nc-role{font-size:8.5px;color:var(--muted);font-weight:500;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}
        .nc-dot{width:5px;height:5px;border-radius:50%;flex-shrink:0;}
        .dot-av{background:var(--cm);}.dot-lv{background:var(--danger);}

        /* Right Area */
        .right-area{flex:1;display:flex;flex-direction:column;overflow:hidden;min-width:0;}

        /* Conflict Bar */
        #conflict-bar{background:rgba(239,68,68,0.08);border-bottom:1.5px solid rgba(239,68,68,0.2);padding:6px 16px;display:none;align-items:center;gap:10px;font-size:12px;font-weight:700;color:#b91c1c;position:sticky;top:70px;z-index:49;}

        /* Control Bar */
        .ctrl-bar{background:var(--white);border-bottom:1px solid var(--border);padding:5px 10px;display:flex;align-items:center;gap:6px;flex-wrap:wrap;justify-content:space-between;}
        .counter-pills{display:flex;gap:4px;flex-wrap:wrap;}
        .cpill{display:flex;align-items:center;gap:4px;padding:3px 8px;border-radius:20px;font-size:10px;font-weight:700;border:1px solid;cursor:default;}
        .cpill-m{background:var(--cm-bg);color:var(--cm);border-color:var(--cm-b);}
        .cpill-e{background:var(--ce-bg);color:var(--ce);border-color:var(--ce-b);}
        .cpill-n{background:var(--cn-bg);color:var(--cn);border-color:var(--cn-b);}
        .cpill .cnt{background:rgba(0,0,0,0.09);padding:1px 5px;border-radius:10px;font-size:9px;}
        .filters{display:flex;align-items:center;gap:5px;flex-wrap:wrap;}
        .filter-lbl{font-size:9px;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:.5px;}
        .fsel{padding:3px 7px;border:1.5px solid var(--border);border-radius:var(--sm-r);font-family:'Plus Jakarta Sans',sans-serif;font-size:10.5px;font-weight:600;color:var(--pd);background:var(--bg);outline:none;cursor:pointer;transition:border-color 0.16s;}
        .fsel:focus{border-color:var(--primary);}
        .actions{display:flex;gap:4px;}

        /* Calendar */
        .cal-wrap{flex:1;overflow:auto;padding:6px 6px 10px 6px;}
        .cal-wrap::-webkit-scrollbar{width:4px;height:4px;}
        .cal-wrap::-webkit-scrollbar-track{background:rgba(31,107,74,0.04);border-radius:4px;}
        .cal-wrap::-webkit-scrollbar-thumb{background:rgba(31,107,74,0.3);border-radius:4px;}
        .cal-wrap::-webkit-scrollbar-thumb:hover{background:rgba(31,107,74,0.5);}

        table.sched{border-collapse:separate;border-spacing:0;border-radius:var(--md-r);overflow:hidden;box-shadow:var(--sm);border:1px solid var(--border);background:#fff;width:100%;table-layout:fixed;}

        /* thead */
        table.sched thead tr{background:var(--pd);}
        .th-ward{padding:6px 5px;text-align:left;font-size:8.5px;font-weight:800;color:rgba(255,255,255,0.65);text-transform:uppercase;letter-spacing:0.4px;position:sticky;left:0;background:var(--pd);z-index:30;width:100px;border-right:1px solid rgba(255,255,255,0.09);}
        .th-day{padding:5px 2px;text-align:center;border-right:1px solid rgba(255,255,255,0.07);vertical-align:middle;}
        .th-day:last-child{border-right:none;}
        .th-day .dn{font-size:8px;font-weight:800;color:rgba(255,255,255,0.55);text-transform:uppercase;letter-spacing:.5px;}
        .th-day .dd{font-size:13px;font-weight:800;color:#fff;line-height:1;margin-top:1px;}
        .th-day .dm{font-size:8px;font-weight:600;color:rgba(255,255,255,0.45);margin-top:1px;}
        .th-day.today{background:rgba(16,185,129,0.18);}
        .th-day.today .dn,.th-day.today .dd,.th-day.today .dm{color:#6ee7b7;}
        .today-badge{font-size:6.5px;font-weight:800;color:#6ee7b7;letter-spacing:.4px;margin-top:1px;}

        /* tbody */
        table.sched tbody tr{border-bottom:1px solid var(--border);}
        table.sched tbody tr:last-child{border-bottom:none;}
        table.sched tbody tr:hover .td-ward{background:#f6faf6;}

        /* Ward cell - sticky */
        .td-ward{padding:4px 5px;background:#fafaf8;border-right:1px solid var(--border);position:sticky;left:0;z-index:10;vertical-align:top;width:100px;transition:background .12s;}
        .ward-row{display:flex;align-items:flex-start;gap:4px;}
        .ward-ic{width:16px;height:16px;border-radius:4px;background:linear-gradient(135deg,rgba(31,107,74,0.11),rgba(31,107,74,0.05));color:var(--primary);display:flex;align-items:center;justify-content:center;font-size:8px;flex-shrink:0;}
        .ward-txt .wn{font-size:9.5px;font-weight:800;color:var(--pd);line-height:1.2;word-break:break-word;}
        .ward-txt .wm{font-size:7.5px;color:var(--muted);font-weight:500;margin-top:1px;line-height:1.2;}
        .ward-txt .wb{display:inline-block;margin-top:2px;font-size:7px;font-weight:800;color:var(--primary);background:rgba(31,107,74,0.09);padding:1px 4px;border-radius:20px;}

        /* Day cell - fluid width */
        .td-day{padding:2px;border-right:1px solid var(--border);vertical-align:top;}
        .td-day:last-child{border-right:none;}
        .td-day.today{background:rgba(16,185,129,0.025);}

        /* Shift slot */
        .s-slot{border-radius:5px;padding:2px 3px;margin-bottom:2px;min-height:20px;cursor:pointer;transition:all .15s ease;display:flex;flex-direction:column;gap:1px;border:1px solid transparent;}
        .s-slot:last-child{margin-bottom:0;}
        .sl-m{background:var(--cm-bg);border-color:var(--cm-b);}
        .sl-e{background:var(--ce-bg);border-color:var(--ce-b);}
        .sl-n{background:var(--cn-bg);border-color:var(--cn-b);}
        .s-slot:hover{transform:scale(1.01);box-shadow:0 1px 4px rgba(0,0,0,0.07);}
        .s-slot.drag-over{transform:scale(1.02);box-shadow:0 2px 8px rgba(31,107,74,0.18);border-color:var(--primary)!important;}
        .sl-m.drag-over{background:rgba(16,185,129,0.12);}
        .sl-e.drag-over{background:rgba(245,158,11,0.12);}
        .sl-n.drag-over{background:rgba(99,102,241,0.12);}
        body.nurse-selected .s-slot.sl-m:not(.has-nurse){border-color:var(--cm)!important;box-shadow:0 0 0 1px rgba(16,185,129,0.22);}
        body.nurse-selected .s-slot.sl-e:not(.has-nurse){border-color:var(--ce)!important;box-shadow:0 0 0 1px rgba(245,158,11,0.22);}
        body.nurse-selected .s-slot.sl-n:not(.has-nurse){border-color:var(--cn)!important;box-shadow:0 0 0 1px rgba(99,102,241,0.22);}

        .slot-hd{display:flex;align-items:center;justify-content:space-between;gap:1px;}
        .stag{font-size:6.5px;font-weight:800;padding:1px 3px;border-radius:3px;color:#fff;letter-spacing:.2px;white-space:nowrap;}
        .stag-m{background:var(--cm);}.stag-e{background:var(--ce);}.stag-n{background:var(--cn);}
        .s-empty{font-size:7.5px;color:var(--muted);font-weight:600;opacity:.55;}

        /* Nurse chip in slot - KEY FIX: truncate name cleanly */
        .n-chip{display:flex;align-items:flex-start;gap:2px;background:#fff;border-radius:4px;padding:1px 3px;margin-top:1px;border:1px solid rgba(0,0,0,0.06);box-shadow:0 1px 2px rgba(0,0,0,0.04);}
        .chip-av{width:11px;height:11px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:5px;font-weight:800;color:#fff;flex-shrink:0;}
        .chav-m{background:var(--cm);}.chav-e{background:var(--ce);}.chav-n{background:var(--cn);}
        .chip-nm{font-size:8.5px;font-weight:700;color:var(--text);flex:1;min-width:0;white-space:normal;word-break:break-word;line-height:1.2;}
        .chip-rm{width:11px;height:11px;border-radius:50%;background:rgba(239,68,68,0.09);color:var(--danger);display:flex;align-items:center;justify-content:center;font-size:5.5px;cursor:pointer;opacity:0;transition:opacity .14s,background .14s;flex-shrink:0;margin-left:auto;}
        .n-chip:hover .chip-rm{opacity:1;}.chip-rm:hover{background:var(--danger);color:#fff;}
        .chip-conflict{border-color:rgba(239,68,68,0.35)!important;background:rgba(239,68,68,0.04)!important;}
        .conflict-icon{color:var(--danger);font-size:7px;flex-shrink:0;}

        /* Week-off */
        .tr-wo .td-ward{background:#fffbeb;border-right-color:rgba(245,158,11,0.22);}
        .tr-wo .ward-ic{background:rgba(245,158,11,0.12);color:#d97706;}
        .tr-wo .wn{color:#92400e;}
        .wo-slot{background:rgba(245,158,11,0.07);border:1px dashed rgba(245,158,11,0.32);border-radius:5px;padding:2px 3px;min-height:20px;cursor:pointer;transition:all .15s;display:flex;flex-wrap:wrap;align-content:flex-start;gap:2px;}
        .wo-slot.drag-over{background:rgba(245,158,11,0.14);border-color:#f59e0b;}
        .wo-chip{display:flex;align-items:center;gap:3px;background:rgba(245,158,11,0.14);border:1px solid rgba(245,158,11,0.28);border-radius:4px;padding:1px 4px;width:100%;overflow:hidden;}
        .wo-chip span{font-size:8.5px;font-weight:700;color:#92400e;flex:1;white-space:normal;word-break:break-word;line-height:1.2;}
        .wo-rm{margin-left:auto;width:10px;height:10px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:5.5px;color:var(--danger);cursor:pointer;opacity:0;transition:opacity .14s;flex-shrink:0;}
        .wo-chip:hover .wo-rm{opacity:1;}

        /* Modals */
        .modal-overlay{position:fixed;inset:0;background:rgba(0,0,0,0.45);z-index:200;display:flex;align-items:center;justify-content:center;opacity:0;pointer-events:none;transition:opacity .2s;}
        .modal-overlay.open{opacity:1;pointer-events:all;}
        .modal-box{background:#fff;border-radius:var(--lg-r);padding:22px;width:min(92vw,480px);max-height:80vh;overflow-y:auto;box-shadow:0 20px 60px rgba(0,0,0,0.2);transform:translateY(10px);transition:transform .2s;}
        .modal-overlay.open .modal-box{transform:translateY(0);}
        .modal-head{display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;}
        .modal-head h3{font-size:15px;font-weight:800;color:var(--pd);}
        .modal-close{background:var(--bg);border:none;border-radius:8px;padding:4px 10px;cursor:pointer;font-size:14px;color:var(--muted);}
        .modal-close:hover{color:var(--danger);}
        .summary-grid{display:grid;grid-template-columns:1fr 1fr 1fr;gap:8px;margin-bottom:14px;}
        .sg-card{background:var(--bg);border-radius:10px;padding:10px;text-align:center;}
        .sg-card .sv{font-size:22px;font-weight:800;color:var(--pd);}
        .sg-card .sl{font-size:9.5px;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:.5px;margin-top:2px;}
        .detail-table{width:100%;border-collapse:collapse;font-size:12px;}
        .detail-table th{background:var(--bg);padding:7px 9px;text-align:left;font-weight:800;color:var(--pd);font-size:10px;text-transform:uppercase;}
        .detail-table td{padding:6px 9px;border-bottom:1px solid var(--border);color:var(--text);}
        .detail-table tr:last-child td{border-bottom:none;}

        /* Toast Notification */
        #toast-overlay{
            position:fixed;top:0;left:185px;right:0;bottom:0;
            display:flex;align-items:center;justify-content:center;
            z-index:9999;pointer-events:none;
        }
        #toast{
            display:none;opacity:0;
            background:#fff;
            border-radius:14px;
            padding:18px 28px 18px 22px;
            box-shadow:0 20px 60px rgba(0,0,0,0.18),0 4px 16px rgba(0,0,0,0.10);
            font-family:'Plus Jakarta Sans',sans-serif;
            min-width:260px;max-width:400px;
            display:none;
            flex-direction:row;
            align-items:center;
            gap:14px;
            border-left:5px solid #10B981;
            transform:translateY(-20px) scale(0.96);
            transition:opacity 0.25s ease,transform 0.25s ease;
            pointer-events:all;
        }
        #toast.show{
            opacity:1;
            transform:translateY(0) scale(1);
        }
        #toast-icon{
            width:40px;height:40px;border-radius:10px;
            display:flex;align-items:center;justify-content:center;
            font-size:18px;flex-shrink:0;
        }
        #toast-body{ flex:1; min-width:0; }
        #toast-title{ font-size:13px;font-weight:800;color:#1a1a1a;margin-bottom:2px;letter-spacing:-0.2px; }
        #toast-msg{ font-size:11.5px;font-weight:500;color:#6b7280;line-height:1.4; }
        #toast-close{
            background:none;border:none;cursor:pointer;
            color:#9ca3af;font-size:14px;padding:2px 4px;
            border-radius:6px;transition:color .15s,background .15s;
            flex-shrink:0;
        }
        #toast-close:hover{color:#ef4444;background:rgba(239,68,68,0.08);}

        /* Custom Confirm Modal */
        #confirm-overlay{
            position:fixed;inset:0;
            background:rgba(0,0,0,0.45);
            z-index:10000;
            display:flex;align-items:center;justify-content:center;
            opacity:0;pointer-events:none;
            transition:opacity 0.2s ease;
            /* Center in content area, not behind sidebar */
            left:185px;
        }
        #confirm-overlay.open{ opacity:1; pointer-events:all; }
        #confirm-box{
            background:#fff;
            border-radius:18px;
            padding:30px 28px 24px;
            width:min(90vw,400px);
            box-shadow:0 24px 70px rgba(0,0,0,0.22),0 4px 16px rgba(0,0,0,0.1);
            transform:translateY(16px) scale(0.97);
            transition:transform 0.22s ease;
            text-align:center;
        }
        #confirm-overlay.open #confirm-box{ transform:translateY(0) scale(1); }
        #confirm-icon-wrap{
            width:60px;height:60px;border-radius:50%;
            margin:0 auto 16px;
            display:flex;align-items:center;justify-content:center;
            font-size:26px;
        }
        #confirm-title{
            font-size:17px;font-weight:800;color:#1a1a1a;
            margin-bottom:8px;letter-spacing:-0.3px;
        }
        #confirm-body{
            font-size:13px;font-weight:500;color:#6b7280;
            line-height:1.6;margin-bottom:22px;
        }
        .confirm-btns{
            display:flex;gap:10px;justify-content:center;
        }
        .confirm-btns button{
            flex:1;padding:10px 0;border-radius:10px;
            font-family:'Plus Jakarta Sans',sans-serif;
            font-size:13px;font-weight:700;
            cursor:pointer;border:none;
            transition:all 0.16s ease;
        }
        #confirm-cancel{
            background:var(--bg);color:var(--text);
            border:1.5px solid var(--border)!important;
        }
        #confirm-cancel:hover{ background:#f3f4f6;border-color:#9ca3af!important; }
        #confirm-ok{ color:#fff; }
        #confirm-ok:hover{ filter:brightness(1.08); transform:translateY(-1px); box-shadow:0 4px 14px rgba(0,0,0,0.15); }

        /* View mode */
        body.vm .nurse-panel{display:none;}
        body.vm .chip-rm,.vm .wo-rm{display:none!important;}
        body.vm .s-empty{display:none;}

        /* Responsive */
        @media(max-width:1023px){.main-content{margin-left:0;max-width:100vw;}}
        @media(max-width:768px){
            .workspace{flex-direction:column;}
            .nurse-panel{width:100%;position:static;height:auto;}
            #nurse-pool{flex-direction:row;overflow-x:auto;overflow-y:hidden;padding:8px;}
            .nurse-card{min-width:130px;flex-shrink:0;}
            .top-bar,.ctrl-bar{flex-direction:column;align-items:flex-start;}
            .bar-right,.actions{flex-wrap:wrap;}
        }

        /* Clean Layout Styles */
        .bp-container {
            display: flex; flex-direction: column; flex: 1; background: var(--bg);
            color: var(--text); overflow: auto; padding: 20px 20px 0 20px; font-family: 'Plus Jakarta Sans', sans-serif;
            width: 100%; box-sizing: border-box;
        }
        .bp-header { margin-bottom: 20px; border-bottom: 1px solid var(--border); padding-bottom: 10px; display: flex; align-items: center; gap: 10px; }
        .bp-title { font-size: 20px; font-weight: 800; color: var(--pd); text-transform: uppercase; letter-spacing: 1px; }
        .bp-breadcrumb { display: flex; gap: 8px; align-items: center; font-size: 13px; color: var(--muted); font-weight: 600; }
        .bp-crumb { cursor: pointer; transition: color 0.2s; }
        .bp-crumb:hover { color: var(--primary); text-decoration: underline; }
        .bp-crumb-sep { color: var(--border); }

        .bp-hospital-card {
            width: 100%; max-width: 240px; margin: 10px auto 0 auto; padding: 10px;
            border: 1.5px solid var(--border); border-radius: var(--md-r); background: var(--white);
            text-align: center; cursor: pointer; transition: all 0.2s ease;
            box-shadow: var(--sm); word-wrap: break-word;
        }
        .bp-hospital-card:hover {
            border-color: var(--primary); box-shadow: var(--md); transform: translateY(-2px);
        }
        .bp-hospital-card i { font-size: 20px; color: var(--primary); margin-bottom: 5px; display: block; }
        .bp-hospital-card span { font-size: 14px; font-weight: 800; color: var(--pd); letter-spacing: 0.5px; }

        .bp-tabs-container {
            display: flex; gap: 0; overflow-x: auto; padding: 0 0 30px 0; margin: 0 auto;
            width: 100%; max-width: 1000px; justify-content: center; flex-wrap: nowrap;
        }
        .bp-tabs-container::-webkit-scrollbar { height: 4px; }
        .bp-tabs-container::-webkit-scrollbar-thumb { background: rgba(31,107,74,0.3); border-radius: 2px; }
        .bp-tab {
            padding: 10px 20px; border: 1.5px solid var(--border); border-radius: 20px;
            background: var(--white); color: var(--text); font-weight: 700; font-size: 13px;
            cursor: pointer; white-space: nowrap; transition: all 0.2s;
        }
        .bp-tab:hover { background: rgba(31,107,74,0.05); border-color: var(--primary); color: var(--primary); }
        .bp-tab.active { background: var(--primary); color: #fff; border-color: var(--primary); box-shadow: 0 4px 10px rgba(31,107,74,0.2); }

        .bp-rooms-container {
            display: flex; gap: 0; overflow-x: auto; margin: 0 auto; width: 100%; max-width: 1000px;
            justify-content: center; flex-wrap: nowrap; padding: 0 0 30px 0;
        }
        .bp-room-card {
            flex: 1 1 120px; max-width: 160px; padding: 10px; border: 1.5px solid var(--border);
            border-radius: var(--sm-r); background: var(--white); cursor: pointer;
            transition: all 0.2s ease; position: relative; box-shadow: 0 1px 2px rgba(0,0,0,0.05);
        }
        .bp-room-card:hover {
            border-color: var(--primary); box-shadow: 0 4px 6px rgba(0,0,0,0.05); transform: translateY(-2px);
        }
        .bp-room-card.active {
            border-color: var(--primary); background: rgba(31,107,74,0.05); box-shadow: 0 4px 10px rgba(31,107,74,0.2);
        }
        .bp-room-name { font-size: 12px; font-weight: 800; color: var(--pd); margin-bottom: 4px; }
        .bp-room-type { font-size: 10px; color: var(--muted); font-weight: 600; text-transform: uppercase; }
        .bp-room-beds { font-size: 10px; font-weight: 700; color: var(--primary); margin-top: 8px; padding-top: 6px; border-top: 1px solid var(--border); }
        
        /* Connection Lines - Org Chart Style */
        .bp-conn-line {
            width: 2px; height: 20px; background: var(--primary); margin: 0 auto;
            animation: growLine 0.3s ease-out forwards; position: relative;
        }
        .bp-conn-line::after, .active-arrow-line::after {
            content: ''; position: absolute; bottom: 0; left: 50%; transform: translateX(-50%);
            border-width: 6px 5px 0 5px; border-style: solid;
            border-color: var(--primary) transparent transparent transparent;
        }
        @keyframes growLine { from { height: 0; opacity: 0; } to { height: 20px; opacity: 1; } }
        
        .active-arrow-line {
            position: absolute; bottom: -30px; left: 50%; transform: translateX(-50%);
            width: 2px; height: 30px; background: var(--primary); z-index: 10;
            animation: growLine 0.3s ease-out forwards;
        }
        
        .tree-node {
            position: relative; margin: 20px 10px 0 10px; flex-shrink: 0;
            animation: fadeInNode 0.3s ease-out forwards;
        }
        @keyframes fadeInNode { from { opacity: 0; transform: translateY(-5px); } to { opacity: 1; transform: translateY(0); } }
        
        /* Vertical line up from node */
        .tree-node::before {
            content: ''; position: absolute; top: -20px; left: 50%;
            width: 2px; height: 20px; background: var(--primary); transform: translateX(-50%);
        }
        /* Horizontal line connecting siblings */
        .tree-node::after {
            content: ''; position: absolute; top: -20px; left: -10px;
            width: calc(100% + 20px); height: 2px; background: var(--primary);
        }
        /* Edges */
        .tree-node:first-child::after { left: 50%; width: calc(50% + 10px); }
        .tree-node:last-child::after { width: calc(50% + 10px); left: -10px; }
        .tree-node:first-child:last-child::after { display: none; }

        /* State Control Classes */
        body.app-state-lvl1 .ctrl-bar, body.app-state-lvl1 .cal-wrap, body.app-state-lvl1 .nurse-panel { display: none !important; }
        body.app-state-lvl2 .ctrl-bar, body.app-state-lvl2 .cal-wrap, body.app-state-lvl2 .nurse-panel { display: none !important; }
        body.app-state-lvl3 .bp-container { display: none !important; }
        
        .blueprint-back-btn { background: var(--white); border: 1.5px solid var(--border); color: var(--pd); padding: 5px 12px; border-radius: var(--sm-r); cursor: pointer; font-family: 'Plus Jakarta Sans', sans-serif; font-size: 12px; font-weight: 700; transition: all 0.2s; margin-right: 15px; display: inline-flex; align-items: center; gap: 6px;}
        .blueprint-back-btn:hover { background: rgba(31,107,74,0.05); border-color: var(--primary); color: var(--primary); }
    </style>
</head>
<body>

<?php $sp=__DIR__.'/includes/nurse_sidebar.php'; if(file_exists($sp)) include $sp; ?>

<div class="main-content">

    <!-- Conflict Bar -->
    <div id="conflict-bar">
        <i class="fas fa-exclamation-triangle"></i>
        <span id="conflict-msg">Conflicts detected</span>
        <button class="btn btn-d" style="margin-left:auto;padding:3px 10px;font-size:11px;" onclick="openConflictModal()">View Conflicts</button>
    </div>

    <!-- Top Bar -->
    <div class="top-bar">
        <div class="bar-left">
            <div class="bar-icon"><i class="fas fa-calendar-alt"></i></div>
            <div>
                <h1>Nurse Shift Assignment</h1>
                <p>Drag &amp; drop or tap nurse &rarr; tap slot &nbsp;|&nbsp; Auto-detects conflicts</p>
            </div>
        </div>
        <div class="bar-right">
            <div style="display:flex; align-items:center; gap: 10px;">
                <label style="font-size:11px; font-weight:700; color:var(--muted);">Start</label>
                <input type="date" id="dr-start" value="<?php echo htmlspecialchars($startDateStr); ?>" class="fsel">
                <label style="font-size:11px; font-weight:700; color:var(--muted);">End</label>
                <input type="date" id="dr-end" value="<?php echo htmlspecialchars($endDateStr); ?>" class="fsel">
                <button class="btn btn-g" onclick="applyDateRange()">
                    <i class="fas fa-check"></i> Apply
                </button>
            </div>
            <button class="btn btn-v" id="btn-vm" onclick="toggleViewMode()">
                <i class="fas fa-eye"></i> View Mode
            </button>
        </div>
    </div>

    <!-- Blueprint View (Full Width Top) -->
    <div class="bp-container" id="blueprint-view">
        <div class="bp-header">
            <div class="bp-title"><i class="fas fa-hospital"></i> Hospital Blueprint</div>
            <div class="bp-breadcrumb" id="bp-breadcrumb"></div>
        </div>
        <div id="bp-content"></div>
    </div>

    <!-- Workspace (Nurse Panel + Calendar) -->
    <div class="workspace" id="shift-workspace" style="display: none;">

        <!-- Nurse Panel -->
        <div class="nurse-panel">
            <div class="np-head">
                <div class="np-title">Nurses <span class="np-count" id="np-count">0</span></div>
                <div class="np-search">
                    <i class="fas fa-search"></i>
                    <input type="text" id="ns-search" placeholder="Search nurse..." oninput="filterPool(this.value)">
                </div>
                <div class="tap-hint"><i class="fas fa-hand-pointer"></i> Tap nurse &rarr; tap shift slot</div>
            </div>
            <div class="shift-legend">
                <div class="leg-pill leg-m"><div class="leg-dot"></div> Morn</div>
                <div class="leg-pill leg-e"><div class="leg-dot"></div> Eve</div>
                <div class="leg-pill leg-n"><div class="leg-dot"></div> Night</div>
            </div>
            <div id="nurse-pool"></div>
        </div>

        <!-- Right -->
        <div class="right-area">

            <!-- Control Bar -->
            <div class="ctrl-bar">
                <div class="counter-pills">
                    <div class="cpill cpill-m"><i class="fas fa-sun" style="font-size:9px;"></i> Morning <span class="cnt" id="cnt-m">0</span></div>
                    <div class="cpill cpill-e"><i class="fas fa-cloud-sun" style="font-size:9px;"></i> Evening <span class="cnt" id="cnt-e">0</span></div>
                    <div class="cpill cpill-n"><i class="fas fa-moon" style="font-size:9px;"></i> Night <span class="cnt" id="cnt-n">0</span></div>
                </div>
                <div class="filters">
                    <button class="blueprint-back-btn" onclick="goBackToFloor()"><i class="fas fa-arrow-left"></i> Back to Floor Plan</button>
                    <div class="bp-breadcrumb" style="color:var(--pd); font-family:'Plus Jakarta Sans',sans-serif;" id="cal-breadcrumb"></div>
                    <input type="hidden" id="ff" value="">
                    <input type="hidden" id="fw" value="">
                    <input type="hidden" id="fr" value="">
                </div>
                <div class="actions">
                    <label style="display:flex;align-items:center;gap:6px;font-size:11.5px;font-weight:700;color:var(--pd);cursor:pointer;margin-right:10px;background:rgba(31,107,74,0.06);padding:5px 10px;border-radius:var(--sm-r);border:1px solid rgba(31,107,74,0.15);">
                        <input type="checkbox" id="auto-fill-cb" checked style="accent-color:var(--primary);width:14px;height:14px;cursor:pointer;"> Auto-fill rest of week
                    </label>
                    <button class="btn btn-g" onclick="openSummaryModal()"><i class="fas fa-chart-bar"></i> Summary</button>
                    <button class="btn btn-d" onclick="clearAll()"><i class="far fa-trash-alt"></i> Clear</button>
                    <button class="btn btn-p" onclick="saveSchedule()"><i class="fas fa-save"></i> Save Schedule</button>
                </div>
            </div>

            <!-- Calendar Table -->
            <div class="cal-wrap">
                <table class="sched">
                    <thead><tr>
                        <th class="th-ward">Wards / Rooms</th>
                        <!-- JS injects day headers -->
                    </tr></thead>
                    <tbody id="cal-body"><!-- JS --></tbody>
                </table>
            </div>

        </div><!-- /right-area -->
    </div><!-- /workspace -->
</div><!-- /main-content -->

<!-- Summary Modal -->
<div class="modal-overlay" id="modal-summary" onclick="closeSummaryModal(event)">
    <div class="modal-box">
        <div class="modal-head">
            <h3><i class="fas fa-chart-bar" style="color:var(--primary);margin-right:8px;"></i>Week Summary</h3>
            <button class="modal-close" onclick="document.getElementById('modal-summary').classList.remove('open')">&#10005;</button>
        </div>
        <div class="summary-grid" id="sum-grid"></div>
        <table class="detail-table">
            <thead><tr><th>Nurse</th><th>M</th><th>E</th><th>N</th><th>W.O.</th><th>Total</th></tr></thead>
            <tbody id="sum-tbody"></tbody>
        </table>
    </div>
</div>

<!-- Conflict Modal -->
<div class="modal-overlay" id="modal-conflict" onclick="closeConflictModal(event)">
    <div class="modal-box">
        <div class="modal-head">
            <h3 style="color:var(--danger);"><i class="fas fa-exclamation-triangle" style="margin-right:8px;"></i>Conflict Report</h3>
            <button class="modal-close" onclick="document.getElementById('modal-conflict').classList.remove('open')">&#10005;</button>
        </div>
        <div id="conflict-list" style="display:flex;flex-direction:column;gap:10px;"></div>
    </div>
</div>

<!-- Custom Confirm Modal -->
<div id="confirm-overlay">
    <div id="confirm-box">
        <div id="confirm-icon-wrap"><i id="confirm-icon-i" class="fas fa-question-circle"></i></div>
        <div id="confirm-title">Are you sure?</div>
        <div id="confirm-body"></div>
        <div class="confirm-btns">
            <button id="confirm-cancel" onclick="_confirmResolve(false)"><i class="fas fa-times" style="margin-right:5px;"></i>Cancel</button>
            <button id="confirm-ok"    onclick="_confirmResolve(true)"><i id="confirm-ok-icon" class="fas fa-check" style="margin-right:5px;"></i>Confirm</button>
        </div>
    </div>
</div>

<!-- Toast -->
<div id="toast-overlay">
    <div id="toast">
        <div id="toast-icon"><i id="toast-icon-i" class="fas fa-check"></i></div>
        <div id="toast-body">
            <div id="toast-title">Success</div>
            <div id="toast-msg"></div>
        </div>
        <button id="toast-close" onclick="closeToast()"><i class="fas fa-times"></i></button>
    </div>
</div>

<script>
// -- DATA --
const MOCK_NURSES = <?php echo $dbNursesJson; ?>;
if(!MOCK_NURSES.length){ MOCK_NURSES.push({id:'n1',name:'Nisha R.',role:'Staff Nurse',status:'Available'},{id:'n2',name:'Sneha P.',role:'Staff Nurse',status:'Available'}); }

const MOCK_WARDS = <?php echo $dbWardsJson; ?>;
if(!MOCK_WARDS.length){ MOCK_WARDS.push({id:'w1',name:'General Ward',type:'Ground Floor - General',beds:32,floor_name:'Ground Floor',ward_name:'General Ward',room_type:'General'}); }

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
    // Remove state classes if any were left over
    document.body.classList.remove('app-state-lvl1', 'app-state-lvl2', 'app-state-lvl3');
    
    // Hide workspace initially
    document.getElementById('shift-workspace').style.display = 'none';
    
    document.getElementById('bp-breadcrumb').innerHTML = `<span class="bp-crumb">Home</span>`;
    
    let html = `
    <div class="bp-hospital-card" onclick="toggleFloors()">
        <i class="far fa-hospital"></i>
        <span>GM HOSPITAL</span>
        <div style="font-size: 11px; color: var(--muted); margin-top: 5px; font-weight: 600; text-transform: uppercase; letter-spacing: 1px;">Basaveshwarnagara</div>
    </div>
    <div class="bp-conn-line" id="conn-hf" style="display:none;"></div>
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
        document.getElementById('conn-hf').style.display = 'block'; // Fix for missing hospital line
        viewFloors();
        setTimeout(() => {
            selectFloor(savedFf);
            setTimeout(() => {
                viewCalendarType(savedFf, savedFr);
            }, 10);
        }, 10);
    }
    
    // Bind scroll event to update dynamic connector
    const fc = document.getElementById('bp-floors-container');
    if (fc) {
        fc.addEventListener('scroll', updateDynamicConnections);
    }
}

function toggleFloors() {
    const fc = document.getElementById('bp-floors-container');
    const chf = document.getElementById('conn-hf');
    if (fc.style.display === 'none') {
        fc.style.display = 'flex';
        chf.style.display = 'block';
        // Only render if empty
        if (fc.innerHTML.trim() === '') {
            viewFloors();
        }
    } else {
        fc.style.display = 'none';
        chf.style.display = 'none';
        document.getElementById('bp-rooms-container').innerHTML = '';
        
        // Hide workspace
        document.getElementById('shift-workspace').style.display = 'none';
    }
}

function viewFloors() {
    let html = ``;
    DB_FLOORS.forEach(floor => {
        html += `<div class="bp-tab tree-node" id="tab-${floor.replace(/\s+/g, '-')}" onclick="selectFloor('${floor}')">${floor}</div>`;
    });
    document.getElementById('bp-floors-container').innerHTML = html;
    document.getElementById('bp-rooms-container').innerHTML = '';
    
    // Hide workspace
    document.getElementById('shift-workspace').style.display = 'none';
}

function selectFloor(floorName) {
    document.querySelectorAll('.bp-tab').forEach(t => {
        t.classList.remove('active');
        const arrow = t.querySelector('.active-arrow-line');
        if(arrow) arrow.remove();
    });
    
    const tabEl = document.getElementById(`tab-${floorName.replace(/\s+/g, '-')}`);
    if(tabEl) {
        tabEl.classList.add('active');
        if(!tabEl.querySelector('.active-arrow-line')) {
            tabEl.insertAdjacentHTML('beforeend', '<div class="active-arrow-line"></div>');
        }
    }
    
    const floorWards = DB_WARDS.filter(w => w.floor_name === floorName);
    let html = ``;
    
    if (floorWards.length === 0) {
        html = `<div style="color: var(--muted); font-style: italic; width: 100%; text-align: center; padding: 20px;">No rooms assigned to this floor.</div>`;
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
            html = `<div style="color: var(--muted); font-style: italic; width: 100%; text-align: center; padding: 20px;">No room types defined on this floor.</div>`;
        } else {
            rtKeys.sort().forEach(key => {
                const rt = roomTypes[key];
                html += `<div class="bp-room-card tree-node" id="rt-card-${rt.type.replace(/\s+/g, '-')}" onclick="viewCalendarType('${floorName}', '${rt.type}')">
                    <div class="bp-room-name">${rt.type}</div>
                    <div class="bp-room-type"><i class="fas fa-door-open"></i> ${rt.wardCount} Ward(s)</div>
                    <div class="bp-room-beds"><i class="fas fa-bed"></i> ${rt.beds} Total Beds</div>
                </div>`;
            });
        }
    }
    document.getElementById('bp-rooms-container').innerHTML = html;
    
    // Hide workspace
    document.getElementById('shift-workspace').style.display = 'none';
    
    // Draw dynamic connections
    setTimeout(updateDynamicConnections, 10);
}

function updateDynamicConnections() {
    const activeTab = document.querySelector('.bp-tab.active');
    const roomsContainer = document.getElementById('bp-rooms-container');
    const roomCards = roomsContainer ? roomsContainer.querySelectorAll('.bp-room-card') : [];
    
    let connector = document.getElementById('dynamic-connector');
    
    if (!activeTab || roomCards.length === 0) {
        if (connector) connector.style.display = 'none';
        return;
    }
    
    if (!connector) {
        connector = document.createElement('div');
        connector.id = 'dynamic-connector';
        connector.style.position = 'absolute';
        connector.style.top = '0';
        connector.style.height = '2px';
        connector.style.background = 'var(--primary)';
        connector.style.zIndex = '10';
        roomsContainer.style.position = 'relative';
        roomsContainer.appendChild(connector);
    }
    
    connector.style.display = 'block';
    
    const tabRect = activeTab.getBoundingClientRect();
    const containerRect = roomsContainer.getBoundingClientRect();
    const firstRoomRect = roomCards[0].getBoundingClientRect();
    const lastRoomRect = roomCards[roomCards.length - 1].getBoundingClientRect();
    
    // Calculate positions relative to the rooms container
    // We add roomsContainer.scrollLeft to account for internal scrolling if any
    const scrollOffset = roomsContainer.scrollLeft;
    
    const tabCenterX = (tabRect.left + tabRect.width / 2) - containerRect.left + scrollOffset;
    const firstRoomCenterX = (firstRoomRect.left + firstRoomRect.width / 2) - containerRect.left + scrollOffset;
    const lastRoomCenterX = (lastRoomRect.left + lastRoomRect.width / 2) - containerRect.left + scrollOffset;
    
    const minX = Math.min(tabCenterX, firstRoomCenterX);
    const maxX = Math.max(tabCenterX, lastRoomCenterX);
    
    connector.style.left = minX + 'px';
    connector.style.width = (maxX - minX) + 'px';
}

// Add event listeners for dynamic connection updates
window.addEventListener('resize', updateDynamicConnections);
document.addEventListener('DOMContentLoaded', () => {
    // We bind scroll to the container in initBlueprint, but we can also bind globally or check later
});

function viewCalendarType(floorName, roomType) {
    document.getElementById('cal-breadcrumb').innerHTML = `<b>${floorName}</b> &nbsp;&rsaquo;&nbsp; <b>${roomType}</b>`;
    document.getElementById('ff').value = floorName;
    document.getElementById('fw').value = '';
    document.getElementById('fr').value = roomType;
    
    // Build calendar ONLY for this floor+room type, and load only matching saved shifts
    renderCalendarBody(floorName, roomType);
    loadExisting(floorName, roomType);
    
    // Highlight active room card
    document.querySelectorAll('.bp-room-card').forEach(c => {
        c.classList.remove('active');
        const arrow = c.querySelector('.active-arrow-line');
        if(arrow) arrow.remove();
    });
    const rc = document.getElementById(`rt-card-${roomType.replace(/\s+/g, '-')}`);
    if (rc) {
        rc.classList.add('active');
        if(!rc.querySelector('.active-arrow-line')) {
            rc.insertAdjacentHTML('beforeend', '<div class="active-arrow-line"></div>');
        }
    }
    
    // Show workspace
    document.getElementById('shift-workspace').style.display = 'flex';
    
    // Smooth scroll down to the workspace
    setTimeout(() => {
        document.getElementById('shift-workspace').scrollIntoView({ behavior: 'smooth', block: 'start' });
    }, 50);
}

function goBackToFloor() {
    // Hide workspace
    document.getElementById('shift-workspace').style.display = 'none';
    
    // Scroll back up
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
            type: 'warning',
            okLabel: 'Yes, Continue',
            okIcon: 'fa-arrow-right'
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
    
    // Submit via POST to keep the URL clean
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
    const tr=document.querySelector('table.sched thead tr');
    DAYS.forEach(day=>{
        const [dd,dm]=day.date.split(' ');
        const isT=day.fullDate===TODAY_STR;
        tr.innerHTML+=`<th class="th-day ${isT?'today':''}">
            <div class="dn">${day.short}</div>
            <div class="dd">${dd}</div>
            <div class="dm">${dm}</div>
            ${isT?'<div class="today-badge">TODAY</div>':''}
        </th>`;
    });
}

// -- RENDER CALENDAR BODY (on-demand for selected floor+roomType) --
function renderCalendarBody(floorName, roomType){
    // Save current view
    currentFloorName = floorName;
    currentRoomType = roomType;
    assignments = {};
    conflicts = [];
    
    const body=document.getElementById('cal-body');
    // Week-off row
    let html=`<tr class="tr-wo"><td class="td-ward"><div class="ward-row"><div class="ward-ic"><i class="fas fa-umbrella-beach"></i></div><div class="ward-txt"><div class="wn">Staff Leave / W.O.</div><div class="wm">Mark weekly day off</div></div></div></td>`;
    for(let i=0;i<DAYS.length;i++){
        const sid=`weekoff_${i}`,isT=DAYS[i].fullDate===TODAY_STR;
        html+=`<td class="td-day ${isT?'today':''}"><div class="wo-slot s-slot" id="${sid}" data-shift="wo" onclick="handleClick('${sid}')"><span style="font-size:9px;color:#d97706;font-weight:600;opacity:.65;width:100%;">Day off</span></div></td>`;
    }
    html+='</tr>';

    // Only render wards matching the selected floor + room type
    const visibleWards = MOCK_WARDS.filter(w => w.floor_name === floorName && w.room_type === roomType);
    
    visibleWards.forEach(ward=>{
        let row=`<tr id="row-${ward.id}"><td class="td-ward"><div class="ward-row"><div class="ward-ic"><i class="fas fa-procedures"></i></div><div class="ward-txt"><div class="wn">${ward.name}</div><div class="wm">${ward.type}</div><span class="wb"><i class="fas fa-bed" style="font-size:8px;"></i> ${ward.beds} Beds</span></div></div></td>`;
        for(let i=0;i<DAYS.length;i++){
            const isT=DAYS[i].fullDate===TODAY_STR;
            row+=`<td class="td-day ${isT?'today':''}">`;
            ['m','e','n'].forEach(sh=>{
                const sid=`${ward.id}_${i}_${sh}`;
                const label=sh==='m'?'Morn':sh==='e'?'Eve':'Night';
                row+=`<div class="s-slot sl-${sh}" id="${sid}" data-shift="${sh}" onclick="handleClick('${sid}')"><div class="slot-hd"><span class="stag stag-${sh}">${label}</span><span class="s-empty">Drop here</span></div></div>`;
            });
            row+=`</td>`;
        }
        row+=`</tr>`;
        html+=row;
    });
    
    body.innerHTML = html;
    updateCounters();
}

// -- NURSE POOL --
function renderNursePool(filter=''){
    const pool=document.getElementById('nurse-pool');
    pool.innerHTML='';
    const lf=filter.toLowerCase();
    const list=MOCK_NURSES.filter(n=>!lf||n.name.toLowerCase().includes(lf));
    document.getElementById('np-count').textContent=list.length;
    if(!list.length){ pool.innerHTML=`<div style="text-align:center;padding:18px;color:var(--muted);font-size:12px;font-weight:600;"><i class="fas fa-search" style="display:block;font-size:20px;margin-bottom:8px;opacity:.4;"></i>No nurses found</div>`; return; }
    list.forEach(n=>{
        const ini=n.name.split(' ').map(w=>w[0]).join('').slice(0,2).toUpperCase();
        const lv=n.status==='On Leave';
        pool.innerHTML+=`<div class="nurse-card ${lv?'on-leave':''}" draggable="true" id="nurse_${n.id}" data-id="${n.id}" onclick="selectNurse('${n.id}')" title="${n.name}">
            <div class="nc-avatar">${ini}</div>
            <div class="nc-info"><div class="nc-name">${n.name}</div><div class="nc-role">${n.role||'Nurse'}</div></div>
            <div class="nc-dot ${lv?'dot-lv':'dot-av'}" title="${n.status}"></div>
        </div>`;
    });
}
function filterPool(v){ renderNursePool(v); }

// -- SELECT NURSE --
function selectNurse(id){
    document.querySelectorAll('.nurse-card').forEach(el=>el.classList.remove('selected'));
    if(selectedNurseId===id){ 
        selectedNurseId=null; 
        document.body.classList.remove('nurse-selected');
        return; 
    }
    selectedNurseId=id;
    const card=document.getElementById('nurse_'+id);
    if(card) card.classList.add('selected');
    
    document.body.classList.add('nurse-selected');
    
    const n=MOCK_NURSES.find(n=>n.id==id);
    toast('Selected: '+(n?n.name:id)+' - tap a slot','#6366F1');
}
function clearSelection(){
    selectedNurseId=null;
    document.querySelectorAll('.nurse-card').forEach(el=>el.classList.remove('selected'));
    document.body.classList.remove('nurse-selected');
}
function handleClick(slotId){ if(selectedNurseId) assign(selectedNurseId,slotId); }

// -- DRAG & DROP --
function setupDnD(){
    document.addEventListener('dragstart',e=>{ 
        const c=e.target.closest('.nurse-card'); 
        if(c){
            draggedNurseId=c.dataset.id;
            c.style.opacity='.4';
            e.dataTransfer.effectAllowed='move';
            e.dataTransfer.setData('text/plain', draggedNurseId);
        } 
    });
    document.addEventListener('dragend',e=>{ 
        const c=e.target.closest('.nurse-card'); 
        if(c){
            c.style.opacity='1';
            // Do NOT nullify draggedNurseId here because some browsers fire dragend BEFORE drop
            setTimeout(() => { draggedNurseId=null; }, 100); 
        } 
    });
    document.addEventListener('dragover',e=>{ 
        e.preventDefault(); 
        const s=e.target.closest('.s-slot'); 
        if(s) s.classList.add('drag-over'); 
    });
    document.addEventListener('dragleave',e=>{ 
        const s=e.target.closest('.s-slot'); 
        if(s) s.classList.remove('drag-over'); 
    });
    document.addEventListener('drop',e=>{ 
        e.preventDefault(); 
        const s=e.target.closest('.s-slot'); 
        if(s){ 
            s.classList.remove('drag-over'); 
            const nId = e.dataTransfer.getData('text/plain') || draggedNurseId;
            if(nId) assign(nId, s.id); 
        } 
    });
}

// -- ASSIGN --
function assign(nurseId,slotId,isInit=false){
    const nurse=MOCK_NURSES.find(n=>n.id==nurseId);
    if(!nurse) return;
    if(nurse.status==='On Leave'){ if(!isInit) toast('Nurse is On Leave','#F59E0B'); return; }
    const parts=slotId.split('_');
    if(parts[0]==='weekoff'){
        if(!assignments[slotId]) assignments[slotId]=[];
        if(!assignments[slotId].find(n=>n.id==nurseId)){ assignments[slotId].push(nurse); refreshSlot(slotId); }
    } else {
        const wardId=parts[0]+'_'+parts[1];
        const startDay=parseInt(parts[2]);
        const sh=parts[3];
        const autoFill = document.getElementById('auto-fill-cb') ? document.getElementById('auto-fill-cb').checked : false;
        
        // Start from day 0 if autoFill is checked and we are not in init (load) mode
        const sDay = (isInit || !autoFill) ? startDay : 0;
        const eDay = (isInit || !autoFill) ? startDay + 1 : DAYS.length;
        
        for(let i=sDay;i<eDay;i++){
            const tgt=`${wardId}_${i}_${sh}`;
            if(!assignments[tgt]) assignments[tgt]=[];
            if(!assignments[tgt].find(n=>n.id==nurseId)){ assignments[tgt].push(nurse); refreshSlot(tgt); }
        }
    }
    if(!isInit){
        updateCounters(); 
        detectConflicts(); 
        clearSelection();
        toast(nurse.name+' assigned','#10B981');
    }
}

window.removeFromSlot=function(slotId,nurseId){
    if(!assignments[slotId]) return;
    assignments[slotId]=assignments[slotId].filter(n=>n.id!=nurseId);
    if(!assignments[slotId].length) delete assignments[slotId];
    refreshSlot(slotId); updateCounters(); detectConflicts();
};

// -- REFRESH SLOT --
function refreshSlot(slotId){
    const slot=document.getElementById(slotId);
    if(!slot) return;
    const isWO=slotId.startsWith('weekoff');
    const nurses=assignments[slotId];
    const sh=slot.dataset.shift;
    const label=sh==='m'?'Morn':sh==='e'?'Eve':'Night';
    if(isWO){
        slot.innerHTML=nurses&&nurses.length
            ?nurses.map(n=>`<div class="wo-chip" title="${n.name}"><i class="fas fa-umbrella-beach" style="font-size:8px;color:#d97706;flex-shrink:0;"></i><span>${n.name}</span><div class="wo-rm" onclick="removeFromSlot('${slotId}','${n.id}')"><i class="fas fa-times"></i></div></div>`).join('')
            :`<span style="font-size:9px;color:#d97706;font-weight:600;opacity:.65;width:100%;">Day off</span>`;
    } else {
        if(nurses&&nurses.length){
            slot.classList.add('has-nurse');
            slot.innerHTML=`<div class="slot-hd"><span class="stag stag-${sh}">${label}</span></div>`
                +nurses.map(n=>{
                    const ini=n.name.split(' ').map(w=>w[0]).join('').slice(0,2).toUpperCase();
                    const isConf=conflicts.some(c=>c.nurseId==n.id&&c.slotId===slotId);
                    return `<div class="n-chip ${isConf?'chip-conflict':''}" title="${n.name}">
                        <div class="chip-av chav-${sh}">${ini}</div>
                        ${isConf?'<i class="fas fa-exclamation-circle conflict-icon"></i>':''}
                        <span class="chip-nm">${n.name}</span>
                        <div class="chip-rm" onclick="removeFromSlot('${slotId}','${n.id}')"><i class="fas fa-times"></i></div>
                    </div>`;
                }).join('');
        } else {
            slot.classList.remove('has-nurse');
            slot.innerHTML=`<div class="slot-hd"><span class="stag stag-${sh}">${label}</span><span class="s-empty">Drop here</span></div>`;
        }
    }
}

// -- CONFLICT DETECTION --
function detectConflicts(){
    conflicts=[];
    const byND={};
    Object.keys(assignments).forEach(slotId=>{
        if(slotId.startsWith('weekoff')) return;
        const p=slotId.split('_');
        const key_day=p[2],sh=p[3];
        assignments[slotId].forEach(nurse=>{
            const k=nurse.id+'_'+key_day+'_'+sh;
            if(!byND[k]) byND[k]=[];
            byND[k].push(slotId);
        });
    });
    Object.entries(byND).forEach(([key,slots])=>{
        if(slots.length>1){
            const nId=key.split('_')[0];
            slots.forEach(sid=>{ conflicts.push({nurseId:nId,slotId:sid}); });
        }
    });
    Object.keys(assignments).forEach(sid=>refreshSlot(sid));
    const bar=document.getElementById('conflict-bar');
    if(conflicts.length>0){
        const un=[...new Set(conflicts.map(c=>c.nurseId))].length;
        document.getElementById('conflict-msg').textContent=un+' nurse(s) double-booked on same shift';
        bar.style.display='flex';
    } else { bar.style.display='none'; }
}

// -- LOAD EXISTING --
// -- LOAD EXISTING (only for currently visible floor+room type) --
function loadExisting(floorName, roomType){
    if(!EXISTING_ASSIGNMENTS||!EXISTING_ASSIGNMENTS.length) return;
    EXISTING_ASSIGNMENTS.forEach(rec=>{
        const nId='n_'+rec.nurse_id;
        const di=DAYS.findIndex(d=>d.fullDate===rec.shift_date);
        if(di===-1) return;
        let slotId;
        if(rec.shift_type==='Week Off'){ 
            slotId=`weekoff_${di}`; 
        } else {
            // Only restore assignments that match the current visible floor+room type
            if(rec.floor_name !== floorName || rec.room_type !== roomType) return;
            const ward=MOCK_WARDS.find(w=>w.ward_name===rec.ward_name&&w.floor_name===rec.floor_name&&w.room_type===rec.room_type);
            if(!ward) return;
            const sh=rec.shift_type==='Morning'?'m':rec.shift_type==='Evening'?'e':'n';
            slotId=`${ward.id}_${di}_${sh}`;
        }
        assign(nId,slotId,true);
    });
    updateCounters();
    detectConflicts();
}

// -- COUNTERS --
function updateCounters(){
    let m=0,e=0,n=0;
    Object.keys(assignments).forEach(k=>{ const c=assignments[k].length; if(k.endsWith('_m')) m+=c; if(k.endsWith('_e')) e+=c; if(k.endsWith('_n')) n+=c; });
    document.getElementById('cnt-m').textContent=m;
    document.getElementById('cnt-e').textContent=e;
    document.getElementById('cnt-n').textContent=n;
}

// -- FILTERS --
function updateFilters(source){
    const fl = document.getElementById('ff').value;
    const fw = document.getElementById('fw').value;
    const ws = document.getElementById('fw');
    const rs = document.getElementById('fr');
    
    let list = MOCK_WARDS;
    if(fl) list = list.filter(w=>w.floor_name===fl);
    
    if(source==='floor'){
        ws.innerHTML='<option value="">All Wards</option>';
        [...new Set(list.map(w=>w.ward_name).filter(Boolean))].sort().forEach(w=>{ ws.innerHTML+=`<option value="${w}">${w}</option>`; });
        
        rs.innerHTML='<option value="">All Room Types</option>';
        [...new Set(list.map(w=>w.room_type).filter(Boolean))].sort().forEach(r=>{ rs.innerHTML+=`<option value="${r}">${r}</option>`; });
    } else if(source==='ward'){
        if(fw) list = list.filter(w=>w.ward_name===fw);
        rs.innerHTML='<option value="">All Room Types</option>';
        [...new Set(list.map(w=>w.room_type).filter(Boolean))].sort().forEach(r=>{ rs.innerHTML+=`<option value="${r}">${r}</option>`; });
    }
    filterTable();
}
function filterTable(){
    const fl=document.getElementById('ff').value.toLowerCase();
    const fw=document.getElementById('fw').value.toLowerCase();
    const fr=document.getElementById('fr').value.toLowerCase();
    const hasFilter = (fl !== '' || fw !== '' || fr !== '');
    
    document.querySelectorAll('#cal-body tr:not(.tr-wo)').forEach(row=>{
        const nm=row.querySelector('.wn')?.textContent.toLowerCase()||'';
        const mt=row.querySelector('.wm')?.textContent.toLowerCase()||'';
        let show=false;
        
        if (hasFilter) {
            show=true;
            if(fl&&!mt.includes(fl)) show=false;
            if(fw&&!nm.includes(fw)) show=false;
            if(fr&&!mt.includes(fr)) show=false;
        }
        
        row.style.display=show?'':'none';
    });
}

// -- VIEW MODE --
function toggleViewMode(){
    document.body.classList.toggle('vm');
    const on=document.body.classList.contains('vm');
    const btn=document.getElementById('btn-vm');
    btn.innerHTML=on?'<i class="fas fa-edit"></i> Edit Mode':'<i class="fas fa-eye"></i> View Mode';
    btn.classList.toggle('on',on);
    document.querySelectorAll('#cal-body tr').forEach(row=>{
        if(on){ const has=row.querySelector('.n-chip,.wo-chip'); row.style.display=has?'':'none'; }
        else { row.style.display=''; filterTable(); }
    });
}

// -- CLEAR --
async function clearAll(){
    const ok = await showConfirm({
        title: 'Clear All Assignments',
        message: 'This will remove all nurse assignments for this week. This action cannot be undone.',
        type: 'danger',
        okLabel: 'Yes, Clear All',
        okIcon: 'fa-trash-alt'
    });
    if (!ok) return;
    Object.keys(assignments).forEach(k=>{ [...assignments[k]].forEach(n=>removeFromSlot(k,n.id)); });
    
    // Clear saved session state so we don't auto-restore
    sessionStorage.removeItem('bp_saved_ff');
    sessionStorage.removeItem('bp_saved_fr');
    
    // Return to GM Hospital home page
    initBlueprint();
}

// -- SUMMARY --
function openSummaryModal(){
    let tm=0,te=0,tn=0;
    Object.keys(assignments).forEach(k=>{ const c=assignments[k].length; if(k.endsWith('_m')) tm+=c; if(k.endsWith('_e')) te+=c; if(k.endsWith('_n')) tn+=c; });
    document.getElementById('sum-grid').innerHTML=`
        <div class="sg-card"><div class="sv" style="color:var(--cm)">${tm}</div><div class="sl">Morning</div></div>
        <div class="sg-card"><div class="sv" style="color:var(--ce)">${te}</div><div class="sl">Evening</div></div>
        <div class="sg-card"><div class="sv" style="color:var(--cn)">${tn}</div><div class="sl">Night</div></div>`;
    const nm={};
    Object.keys(assignments).forEach(k=>{ assignments[k].forEach(nurse=>{ if(!nm[nurse.id]) nm[nurse.id]={name:nurse.name,m:0,e:0,n:0,wo:0}; if(k.startsWith('weekoff')) nm[nurse.id].wo++; else if(k.endsWith('_m')) nm[nurse.id].m++; else if(k.endsWith('_e')) nm[nurse.id].e++; else if(k.endsWith('_n')) nm[nurse.id].n++; }); });
    const rows=Object.values(nm).sort((a,b)=>a.name.localeCompare(b.name));
    document.getElementById('sum-tbody').innerHTML=rows.length
        ?rows.map(r=>`<tr><td>${r.name}</td><td><b style="color:var(--cm)">${r.m}</b></td><td><b style="color:var(--ce)">${r.e}</b></td><td><b style="color:var(--cn)">${r.n}</b></td><td>${r.wo}</td><td><b>${r.m+r.e+r.n+r.wo}</b></td></tr>`).join('')
        :'<tr><td colspan="6" style="text-align:center;color:var(--muted);padding:14px;">No assignments yet</td></tr>';
    document.getElementById('modal-summary').classList.add('open');
}
function closeSummaryModal(e){ if(e.target===e.currentTarget) document.getElementById('modal-summary').classList.remove('open'); }

// -- CONFLICT MODAL --
function openConflictModal(){
    const byNurse={};
    conflicts.forEach(c=>{ const nurse=MOCK_NURSES.find(n=>n.id==c.nurseId); if(!nurse) return; if(!byNurse[c.nurseId]) byNurse[c.nurseId]={name:nurse.name,slots:[]}; byNurse[c.nurseId].slots.push(c.slotId); });
    document.getElementById('conflict-list').innerHTML=Object.values(byNurse).map(b=>`<div style="background:rgba(239,68,68,0.06);border:1px solid rgba(239,68,68,0.2);border-radius:10px;padding:12px;">
        <div style="font-size:13px;font-weight:800;color:#b91c1c;margin-bottom:6px;"><i class="fas fa-user-nurse"></i> ${b.name}</div>
        <div style="font-size:11px;color:var(--muted);font-weight:600;">Assigned to multiple wards same shift:<br>
        ${b.slots.map(sid=>{const p=sid.split('_');const di=parseInt(p[2]);const sh=p[3]==='m'?'Morning':p[3]==='e'?'Evening':'Night';return `<span style="display:inline-block;margin:2px 3px;background:rgba(239,68,68,0.1);padding:2px 7px;border-radius:20px;font-weight:700;color:#b91c1c;">${DAYS[di]?.date} - ${sh}</span>`;}).join('')}
        </div></div>`).join('');
    document.getElementById('modal-conflict').classList.add('open');
}
function closeConflictModal(e){ if(e.target===e.currentTarget) document.getElementById('modal-conflict').classList.remove('open'); }

// -- SAVE --
async function saveSchedule(){
    if(!Object.keys(assignments).length){ toast('Nothing to save','#F59E0B'); return; }
    if(conflicts.length > 0) {
        const ok = await showConfirm({
            title: 'Conflicts Detected',
            message: conflicts.length + ' nurse(s) are double-booked on the same shift. Do you still want to save?',
            type: 'warning',
            okLabel: 'Save Anyway',
            okIcon: 'fa-save'
        });
        if (!ok) return;
    }
    const payload=[];
    Object.keys(assignments).forEach(slotId=>{ assignments[slotId].forEach(nurse=>{ const parts=slotId.split('_'),dbId=nurse.id.toString().replace('n_',''); if(parts[0]==='weekoff'){ const di=parseInt(parts[1]); payload.push({nurse_id:dbId,nurse_name:nurse.name,shift_date:DAYS[di].fullDate,shift_type:'Week Off',floor_name:null,ward_name:null,room_type:null}); } else { const wardId=parts[0]+'_'+parts[1],di=parseInt(parts[2]),sh=parts[3],st=sh==='m'?'Morning':sh==='e'?'Evening':'Night',ward=MOCK_WARDS.find(w=>w.id===wardId); payload.push({nurse_id:dbId,nurse_name:nurse.name,shift_date:DAYS[di].fullDate,shift_type:st,floor_name:ward?.floor_name||null,ward_name:ward?.ward_name||null,room_type:ward?.room_type||null}); } }); });
    payload.forEach(r=>{ if(r.shift_type==='Week Off'){ const ws=payload.find(p=>p.nurse_id===r.nurse_id&&p.shift_type!=='Week Off'); if(ws){r.floor_name=ws.floor_name;r.ward_name=ws.ward_name;r.room_type=ws.room_type;} } });
    const btn=event.currentTarget,orig=btn.innerHTML;
    btn.innerHTML='<i class="fas fa-spinner fa-spin"></i> Saving...';btn.disabled=true;
    fetch('save_shift_schedule.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({startDate:DAYS[0].fullDate,endDate:DAYS[DAYS.length-1].fullDate,shifts:payload})})
    .then(r=>r.json()).then(data=>{ if(data.status==='success'){toast('Schedule saved!','#10B981');btn.innerHTML='<i class="fas fa-check"></i> Saved!';setTimeout(()=>window.location.reload(),1500);} else{toast('Error: '+data.message,'#EF4444');btn.innerHTML=orig;btn.disabled=false;} })
    .catch(()=>{toast('Network error','#EF4444');btn.innerHTML=orig;btn.disabled=false;});
}

// -- CUSTOM CONFIRM --
let _confirmResolve = null;
function showConfirm({ title='Are you sure?', message='', type='danger', okLabel='Confirm', okIcon='fa-check' } = {}) {
    return new Promise(resolve => {
        _confirmResolve = (val) => {
            document.getElementById('confirm-overlay').classList.remove('open');
            resolve(val);
        };

        const cfg = {
            danger  : { iconCls:'fa-trash-alt',        iconClr:'#EF4444', iconBg:'rgba(239,68,68,0.1)',    okBg:'#EF4444' },
            warning : { iconCls:'fa-exclamation-circle', iconClr:'#F59E0B', iconBg:'rgba(245,158,11,0.1)',  okBg:'#F59E0B' },
            info    : { iconCls:'fa-info-circle',       iconClr:'#6366F1', iconBg:'rgba(99,102,241,0.1)',  okBg:'#6366F1' },
            success : { iconCls:'fa-check-circle',      iconClr:'#10B981', iconBg:'rgba(16,185,129,0.1)',  okBg:'#10B981' },
        }[type] || {};

        const iw = document.getElementById('confirm-icon-wrap');
        const ii = document.getElementById('confirm-icon-i');
        iw.style.background = cfg.iconBg;
        ii.className = 'fas ' + cfg.iconCls;
        ii.style.color = cfg.iconClr;

        document.getElementById('confirm-title').textContent = title;
        document.getElementById('confirm-body').textContent  = message;

        const ok = document.getElementById('confirm-ok');
        ok.style.background = cfg.okBg;
        ok.innerHTML = `<i class="fas ${okIcon}" style="margin-right:5px;"></i>${okLabel}`;

        document.getElementById('confirm-overlay').classList.add('open');
    });
}

// -- TOAST --
function toast(msg, color='#10B981') {
    const t      = document.getElementById('toast');
    const icon   = document.getElementById('toast-icon');
    const iconI  = document.getElementById('toast-icon-i');
    const title  = document.getElementById('toast-title');
    const msgEl  = document.getElementById('toast-msg');

    // Determine type from color
    let type = 'success';
    if (color === '#EF4444' || color === '#ef4444') type = 'error';
    else if (color === '#F59E0B' || color === '#f59e0b') type = 'warning';
    else if (color === '#6366F1' || color === '#6366f1') type = 'info';

    const cfg = {
        success : { label:'Success',  iconCls:'fa-check-circle',  bg:'rgba(16,185,129,0.1)',  clr:'#10B981', bdr:'#10B981' },
        error   : { label:'Error',    iconCls:'fa-times-circle',  bg:'rgba(239,68,68,0.1)',   clr:'#EF4444', bdr:'#EF4444' },
        warning : { label:'Warning',  iconCls:'fa-exclamation-triangle', bg:'rgba(245,158,11,0.1)', clr:'#F59E0B', bdr:'#F59E0B' },
        info    : { label:'Info',     iconCls:'fa-info-circle',   bg:'rgba(99,102,241,0.1)',  clr:'#6366F1', bdr:'#6366F1' },
    }[type];

    // Apply
    t.style.borderLeftColor = cfg.bdr;
    icon.style.background   = cfg.bg;
    iconI.className         = 'fas ' + cfg.iconCls;
    iconI.style.color       = cfg.clr;
    title.textContent       = cfg.label;
    title.style.color       = cfg.clr;
    msgEl.textContent       = msg;

    // Show
    t.style.display = 'flex';
    clearTimeout(t._timer);
    requestAnimationFrame(() => {
        requestAnimationFrame(() => { t.classList.add('show'); });
    });

    t._timer = setTimeout(closeToast, 3000);
}

function closeToast() {
    const t = document.getElementById('toast');
    t.classList.remove('show');
    setTimeout(() => { t.style.display = 'none'; }, 280);
}
</script>
</body>
</html>
