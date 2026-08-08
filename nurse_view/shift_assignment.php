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
        .main-content{flex:1;margin-left:185px;display:flex;flex-direction:column;min-height:100vh;}

        /* Top Bar */
        .top-bar{background:var(--white);padding:13px 22px;display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;border-bottom:1px solid var(--border);box-shadow:var(--sm);position:sticky;top:0;z-index:60;}
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
        .workspace{display:flex;flex:1;min-height:0;overflow:hidden;}

        /* Nurse Panel */
        .nurse-panel{width:245px;flex-shrink:0;background:var(--white);border-right:1px solid var(--border);display:flex;flex-direction:column;position:sticky;top:70px;height:calc(100vh - 70px);overflow:hidden;}
        .np-head{padding:12px 12px 8px;border-bottom:1px solid var(--border);}
        .np-title{font-size:10.5px;font-weight:800;color:var(--pd);text-transform:uppercase;letter-spacing:0.6px;display:flex;align-items:center;justify-content:space-between;margin-bottom:8px;}
        .np-count{background:linear-gradient(135deg,var(--primary),var(--pl));color:#fff;padding:2px 8px;border-radius:20px;font-size:10.5px;font-weight:800;}
        .np-search{position:relative;}
        .np-search i{position:absolute;left:8px;top:50%;transform:translateY(-50%);color:var(--muted);font-size:11px;}
        .np-search input{width:100%;padding:7px 8px 7px 26px;border:1.5px solid var(--border);border-radius:var(--sm-r);font-family:'Plus Jakarta Sans',sans-serif;font-size:12px;font-weight:600;color:var(--text);background:var(--bg);outline:none;transition:border-color 0.18s;}
        .np-search input:focus{border-color:var(--primary);}
        .tap-hint{margin-top:7px;padding:5px 8px;background:rgba(31,107,74,0.06);border:1px dashed rgba(31,107,74,0.2);border-radius:7px;font-size:10px;color:var(--pd);font-weight:600;text-align:center;}
        .shift-legend{padding:7px 12px;border-bottom:1px solid var(--border);display:flex;gap:5px;flex-wrap:wrap;}
        .leg-pill{display:flex;align-items:center;gap:3px;padding:2px 8px;border-radius:20px;font-size:9.5px;font-weight:700;}
        .leg-m{background:var(--cm-bg);color:var(--cm);border:1px solid var(--cm-b);}
        .leg-e{background:var(--ce-bg);color:var(--ce);border:1px solid var(--ce-b);}
        .leg-n{background:var(--cn-bg);color:var(--cn);border:1px solid var(--cn-b);}
        .leg-dot{width:5px;height:5px;border-radius:50%;}
        .leg-m .leg-dot{background:var(--cm);}.leg-e .leg-dot{background:var(--ce);}.leg-n .leg-dot{background:var(--cn);}
        #nurse-pool{flex:1;overflow-y:auto;padding:8px;display:flex;flex-direction:column;gap:5px;}
        #nurse-pool::-webkit-scrollbar{width:3px;}#nurse-pool::-webkit-scrollbar-thumb{background:rgba(31,107,74,0.18);border-radius:3px;}

        .nurse-card{background:var(--bg);border:1.5px solid var(--border);border-radius:var(--sm-r);padding:7px 9px;cursor:pointer;display:flex;align-items:flex-start;gap:7px;transition:all 0.16s ease;position:relative;overflow:hidden;height:auto;min-height:44px;}
        .nurse-card::before{content:'';position:absolute;left:0;top:0;bottom:0;width:3px;background:var(--primary);opacity:0;transition:opacity 0.16s;border-radius:3px 0 0 3px;}
        .nurse-card:hover{background:#fff;border-color:var(--primary);transform:translateX(2px);box-shadow:var(--sm);}
        .nurse-card:hover::before{opacity:1;}
        .nurse-card.selected{background:#fff;border-color:var(--cm);box-shadow:0 0 0 3px rgba(16,185,129,0.18);transform:translateX(2px);}
        .nurse-card.selected::before{opacity:1;background:var(--cm);}
        .nurse-card.on-leave{opacity:0.5;}
        .nc-avatar{width:30px;height:30px;border-radius:50%;background:linear-gradient(135deg,var(--primary),var(--pl));display:flex;align-items:center;justify-content:center;color:#fff;font-size:11px;font-weight:800;flex-shrink:0;}
        .nc-info{flex:1;min-width:0;}
        /* KEY FIX: nurse name wrapping naturally so full name is visible */
        .nc-name{font-size:11.5px;font-weight:800;color:var(--pd);line-height:1.3;white-space:normal;word-break:break-word;display:block;}
        .nc-role{font-size:9.5px;color:var(--muted);font-weight:500;margin-top:1px;}
        .nc-dot{width:7px;height:7px;border-radius:50%;flex-shrink:0;}
        .dot-av{background:var(--cm);}.dot-lv{background:var(--danger);}

        /* Right Area */
        .right-area{flex:1;display:flex;flex-direction:column;overflow:hidden;min-width:0;}

        /* Conflict Bar */
        #conflict-bar{background:rgba(239,68,68,0.08);border-bottom:1.5px solid rgba(239,68,68,0.2);padding:6px 16px;display:none;align-items:center;gap:10px;font-size:12px;font-weight:700;color:#b91c1c;}

        /* Control Bar */
        .ctrl-bar{background:var(--white);border-bottom:1px solid var(--border);padding:9px 16px;display:flex;align-items:center;gap:10px;flex-wrap:wrap;justify-content:space-between;}
        .counter-pills{display:flex;gap:6px;flex-wrap:wrap;}
        .cpill{display:flex;align-items:center;gap:5px;padding:4px 11px;border-radius:20px;font-size:11px;font-weight:700;border:1.5px solid;cursor:default;}
        .cpill-m{background:var(--cm-bg);color:var(--cm);border-color:var(--cm-b);}
        .cpill-e{background:var(--ce-bg);color:var(--ce);border-color:var(--ce-b);}
        .cpill-n{background:var(--cn-bg);color:var(--cn);border-color:var(--cn-b);}
        .cpill .cnt{background:rgba(0,0,0,0.09);padding:1px 6px;border-radius:10px;font-size:10px;}
        .filters{display:flex;align-items:center;gap:7px;flex-wrap:wrap;}
        .filter-lbl{font-size:10px;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:.5px;}
        .fsel{padding:5px 10px;border:1.5px solid var(--border);border-radius:var(--sm-r);font-family:'Plus Jakarta Sans',sans-serif;font-size:11.5px;font-weight:600;color:var(--pd);background:var(--bg);outline:none;cursor:pointer;transition:border-color 0.16s;}
        .fsel:focus{border-color:var(--primary);}
        .actions{display:flex;gap:6px;}

        /* Calendar */
        .cal-wrap{flex:1;overflow:auto;padding:12px 12px 24px 12px;}
        .cal-wrap::-webkit-scrollbar{width:10px;height:12px;}
        .cal-wrap::-webkit-scrollbar-track{background:rgba(31,107,74,0.06);border-radius:6px;margin:0 12px;}
        .cal-wrap::-webkit-scrollbar-thumb{background:rgba(31,107,74,0.35);border-radius:6px;border:2px solid var(--bg);}
        .cal-wrap::-webkit-scrollbar-thumb:hover{background:rgba(31,107,74,0.5);}

        table.sched{border-collapse:separate;border-spacing:0;border-radius:var(--lg-r);overflow:hidden;box-shadow:var(--md);border:1px solid var(--border);background:#fff;width:100%;min-width:900px;table-layout:fixed;}

        /* thead */
        table.sched thead tr{background:var(--pd);}
        .th-ward{padding:10px 8px;text-align:left;font-size:9.5px;font-weight:800;color:rgba(255,255,255,0.65);text-transform:uppercase;letter-spacing:0.5px;position:sticky;left:0;background:var(--pd);z-index:30;width:140px;border-right:1px solid rgba(255,255,255,0.09);}
        /* KEY FIX: fluid day columns to fit screen */
        .th-day{padding:8px 4px;text-align:center;border-right:1px solid rgba(255,255,255,0.07);vertical-align:middle;}
        .th-day:last-child{border-right:none;}
        .th-day .dn{font-size:9.5px;font-weight:800;color:rgba(255,255,255,0.55);text-transform:uppercase;letter-spacing:.7px;}
        .th-day .dd{font-size:18px;font-weight:800;color:#fff;line-height:1;margin-top:2px;}
        .th-day .dm{font-size:9.5px;font-weight:600;color:rgba(255,255,255,0.45);margin-top:1px;}
        .th-day.today{background:rgba(16,185,129,0.18);}
        .th-day.today .dn,.th-day.today .dd,.th-day.today .dm{color:#6ee7b7;}
        .today-badge{font-size:7.5px;font-weight:800;color:#6ee7b7;letter-spacing:.5px;margin-top:2px;}

        /* tbody */
        table.sched tbody tr{border-bottom:1px solid var(--border);}
        table.sched tbody tr:last-child{border-bottom:none;}
        table.sched tbody tr:hover .td-ward{background:#f6faf6;}

        /* Ward cell - sticky */
        .td-ward{padding:8px;background:#fafaf8;border-right:2px solid var(--border);position:sticky;left:0;z-index:10;vertical-align:top;width:140px;transition:background .12s;}
        .ward-row{display:flex;align-items:flex-start;gap:6px;}
        .ward-ic{width:22px;height:22px;border-radius:6px;background:linear-gradient(135deg,rgba(31,107,74,0.11),rgba(31,107,74,0.05));color:var(--primary);display:flex;align-items:center;justify-content:center;font-size:10px;flex-shrink:0;}
        .ward-txt .wn{font-size:10.5px;font-weight:800;color:var(--pd);line-height:1.2;}
        .ward-txt .wm{font-size:9px;color:var(--muted);font-weight:500;margin-top:2px;line-height:1.3;}
        .ward-txt .wb{display:inline-block;margin-top:4px;font-size:8px;font-weight:800;color:var(--primary);background:rgba(31,107,74,0.09);padding:1px 6px;border-radius:20px;}

        /* Day cell - fluid width */
        .td-day{padding:3px;border-right:1px solid var(--border);vertical-align:top;}
        .td-day:last-child{border-right:none;}
        .td-day.today{background:rgba(16,185,129,0.025);}

        /* Shift slot */
        .s-slot{border-radius:7px;padding:4px 5px;margin-bottom:3px;min-height:32px;cursor:pointer;transition:all .15s ease;display:flex;flex-direction:column;gap:2px;border:1.5px solid transparent;}
        .s-slot:last-child{margin-bottom:0;}
        .sl-m{background:var(--cm-bg);border-color:var(--cm-b);}
        .sl-e{background:var(--ce-bg);border-color:var(--ce-b);}
        .sl-n{background:var(--cn-bg);border-color:var(--cn-b);}
        .s-slot:hover{transform:scale(1.01);box-shadow:0 2px 8px rgba(0,0,0,0.07);}
        .s-slot.drag-over{transform:scale(1.02);box-shadow:0 4px 14px rgba(31,107,74,0.18);border-color:var(--primary)!important;}
        .sl-m.drag-over{background:rgba(16,185,129,0.12);}
        .sl-e.drag-over{background:rgba(245,158,11,0.12);}
        .sl-n.drag-over{background:rgba(99,102,241,0.12);}
        .s-slot.tgt-hi.sl-m{border-color:var(--cm)!important;box-shadow:0 0 0 2px rgba(16,185,129,0.22);}
        .s-slot.tgt-hi.sl-e{border-color:var(--ce)!important;box-shadow:0 0 0 2px rgba(245,158,11,0.22);}
        .s-slot.tgt-hi.sl-n{border-color:var(--cn)!important;box-shadow:0 0 0 2px rgba(99,102,241,0.22);}

        .slot-hd{display:flex;align-items:center;justify-content:space-between;gap:2px;}
        .stag{font-size:7.5px;font-weight:800;padding:1px 4px;border-radius:4px;color:#fff;letter-spacing:.3px;white-space:nowrap;}
        .stag-m{background:var(--cm);}.stag-e{background:var(--ce);}.stag-n{background:var(--cn);}
        .s-empty{font-size:8.5px;color:var(--muted);font-weight:600;opacity:.6;}

        /* Nurse chip in slot - KEY FIX: truncate name cleanly */
        .n-chip{display:flex;align-items:flex-start;gap:3px;background:#fff;border-radius:5px;padding:2px 4px;margin-top:2px;border:1px solid rgba(0,0,0,0.06);box-shadow:0 1px 3px rgba(0,0,0,0.04);overflow:hidden;}
        .chip-av{width:14px;height:14px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:6px;font-weight:800;color:#fff;flex-shrink:0;margin-top:1px;}
        .chav-m{background:var(--cm);}.chav-e{background:var(--ce);}.chav-n{background:var(--cn);}
        /* Name wraps naturally */
        .chip-nm{font-size:9.5px;font-weight:700;color:var(--text);flex:1;min-width:0;white-space:normal;word-break:break-word;line-height:1.2;}
        .chip-rm{width:13px;height:13px;border-radius:50%;background:rgba(239,68,68,0.09);color:var(--danger);display:flex;align-items:center;justify-content:center;font-size:6.5px;cursor:pointer;opacity:0;transition:opacity .14s,background .14s;flex-shrink:0;margin-left:auto;}
        .n-chip:hover .chip-rm{opacity:1;}.chip-rm:hover{background:var(--danger);color:#fff;}
        .chip-conflict{border-color:rgba(239,68,68,0.35)!important;background:rgba(239,68,68,0.04)!important;}
        .conflict-icon{color:var(--danger);font-size:8px;flex-shrink:0;}

        /* Week-off */
        .tr-wo .td-ward{background:#fffbeb;border-right-color:rgba(245,158,11,0.22);}
        .tr-wo .ward-ic{background:rgba(245,158,11,0.12);color:#d97706;}
        .tr-wo .wn{color:#92400e;}
        .wo-slot{background:rgba(245,158,11,0.07);border:1.5px dashed rgba(245,158,11,0.32);border-radius:7px;padding:4px 6px;min-height:36px;cursor:pointer;transition:all .15s;display:flex;flex-wrap:wrap;align-content:flex-start;gap:3px;}
        .wo-slot.drag-over{background:rgba(245,158,11,0.14);border-color:#f59e0b;}
        .wo-chip{display:flex;align-items:center;gap:4px;background:rgba(245,158,11,0.14);border:1px solid rgba(245,158,11,0.28);border-radius:5px;padding:2px 5px;width:100%;overflow:hidden;}
        .wo-chip span{font-size:10.5px;font-weight:700;color:#92400e;flex:1;white-space:normal;word-break:break-word;line-height:1.2;}
        .wo-rm{margin-left:auto;width:12px;height:12px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:6.5px;color:var(--danger);cursor:pointer;opacity:0;transition:opacity .14s;flex-shrink:0;}
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

        /* Toast */
        #toast{position:fixed;top:50%;left:50%;transform:translate(-50%,-50%) scale(0.9);padding:14px 28px;border-radius:12px;font-family:'Plus Jakarta Sans',sans-serif;font-size:15px;font-weight:700;color:#fff;box-shadow:0 12px 40px rgba(0,0,0,0.25);z-index:9999;transition:opacity .3s,transform .3s;display:none;opacity:0;}

        /* View mode */
        body.vm .nurse-panel{display:none;}
        body.vm .chip-rm,.vm .wo-rm{display:none!important;}
        body.vm .s-empty{display:none;}

        /* Responsive */
        @media(max-width:1023px){.main-content{margin-left:0;}}
        @media(max-width:768px){
            .workspace{flex-direction:column;}
            .nurse-panel{width:100%;position:static;height:auto;}
            #nurse-pool{flex-direction:row;overflow-x:auto;overflow-y:hidden;padding:8px;}
            .nurse-card{min-width:130px;flex-shrink:0;}
            .top-bar,.ctrl-bar{flex-direction:column;align-items:flex-start;}
            .bar-right,.actions{flex-wrap:wrap;}
        }
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
            <div class="week-nav">
                <button onclick="shiftWeek(-1)" title="Previous week"><i class="fas fa-chevron-left"></i></button>
                <span class="wlbl" id="week-lbl">Loading...</span>
                <button onclick="shiftWeek(1)" title="Next week"><i class="fas fa-chevron-right"></i></button>
            </div>
            <input type="date" id="week-start-date" value="<?php echo htmlspecialchars($startDateStr); ?>" onchange="goToDate(this.value)" style="display:none;">
            <button class="btn btn-g" onclick="document.getElementById('week-start-date').showPicker?document.getElementById('week-start-date').showPicker():document.getElementById('week-start-date').click()">
                <i class="fas fa-calendar-day"></i> Jump to Date
            </button>
            <button class="btn btn-v" id="btn-vm" onclick="toggleViewMode()">
                <i class="fas fa-eye"></i> View Mode
            </button>
        </div>
    </div>

    <!-- Workspace -->
    <div class="workspace">

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
                    <span class="filter-lbl"><i class="fas fa-filter"></i></span>
                    <select class="fsel" id="ff" onchange="updateFilters('floor')">
                        <option value="">All Floors</option>
                        <?php foreach($dbFloors as $f): ?><option value="<?php echo htmlspecialchars($f);?>"><?php echo htmlspecialchars($f);?></option><?php endforeach;?>
                    </select>
                    <select class="fsel" id="fw" onchange="updateFilters('ward')">
                        <option value="">All Wards</option>
                        <?php foreach($dbWardsList as $w): ?><option value="<?php echo htmlspecialchars($w);?>"><?php echo htmlspecialchars($w);?></option><?php endforeach;?>
                    </select>
                    <select class="fsel" id="fr" onchange="filterTable()">
                        <option value="">All Room Types</option>
                        <?php foreach($dbRoomTypes as $r): ?><option value="<?php echo htmlspecialchars($r);?>"><?php echo htmlspecialchars($r);?></option><?php endforeach;?>
                    </select>
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

<!-- Toast -->
<div id="toast"></div>

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

// -- BOOT --
document.addEventListener('DOMContentLoaded', () => {
    updateWeekLabel();
    renderHeader();
    renderCalendarBody();
    renderNursePool();
    setupDnD();
    loadExisting();
});

// -- WEEK LABEL --
function updateWeekLabel(){
    if(!DAYS||!DAYS.length) return;
    document.getElementById('week-lbl').textContent = DAYS[0].date + ' - ' + DAYS[6].date;
}
function shiftWeek(dir){
    const d=new Date(DAYS[0].fullDate);
    d.setDate(d.getDate()+dir*7);
    confirmAndGo(d.toISOString().split('T')[0]);
}
function goToDate(v){ if(v) confirmAndGo(v); }
function confirmAndGo(d){
    if(Object.keys(assignments).length>0 && !confirm('Unsaved assignments will be lost. Continue?')) return;
    window.location.href='?start_date='+d;
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

// -- RENDER CALENDAR BODY --
function renderCalendarBody(){
    const body=document.getElementById('cal-body');
    // Week-off row
    let wo=`<tr class="tr-wo"><td class="td-ward"><div class="ward-row"><div class="ward-ic"><i class="fas fa-umbrella-beach"></i></div><div class="ward-txt"><div class="wn">Staff Leave / W.O.</div><div class="wm">Mark weekly day off</div></div></div></td>`;
    for(let i=0;i<7;i++){
        const sid=`weekoff_${i}`,isT=DAYS[i].fullDate===TODAY_STR;
        wo+=`<td class="td-day ${isT?'today':''}"><div class="wo-slot s-slot" id="${sid}" data-shift="wo" onclick="handleClick('${sid}')"><span style="font-size:9px;color:#d97706;font-weight:600;opacity:.65;width:100%;">Day off</span></div></td>`;
    }
    wo+='</tr>';
    body.innerHTML=wo;

    MOCK_WARDS.forEach(ward=>{
        let row=`<tr id="row-${ward.id}"><td class="td-ward"><div class="ward-row"><div class="ward-ic"><i class="fas fa-procedures"></i></div><div class="ward-txt"><div class="wn">${ward.name}</div><div class="wm">${ward.type}</div><span class="wb"><i class="fas fa-bed" style="font-size:8px;"></i> ${ward.beds} Beds</span></div></div></td>`;
        for(let i=0;i<7;i++){
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
        body.innerHTML+=row;
    });
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
    document.querySelectorAll('.s-slot').forEach(el=>el.classList.remove('tgt-hi'));
    if(selectedNurseId===id){ selectedNurseId=null; return; }
    selectedNurseId=id;
    const card=document.getElementById('nurse_'+id);
    if(card) card.classList.add('selected');
    document.querySelectorAll('.s-slot:not(.wo-slot)').forEach(sl=>{
        if(!(assignments[sl.id]&&assignments[sl.id].length)) sl.classList.add('tgt-hi');
    });
    const n=MOCK_NURSES.find(n=>n.id==id);
    toast('Selected: '+(n?n.name:id)+' - tap a slot','#6366F1');
}
function clearSelection(){
    selectedNurseId=null;
    document.querySelectorAll('.nurse-card').forEach(el=>el.classList.remove('selected'));
    document.querySelectorAll('.s-slot').forEach(el=>el.classList.remove('tgt-hi'));
}
function handleClick(slotId){ if(selectedNurseId) assign(selectedNurseId,slotId); }

// -- DRAG & DROP --
function setupDnD(){
    document.addEventListener('dragstart',e=>{ const c=e.target.closest('.nurse-card'); if(c){draggedNurseId=c.dataset.id;c.style.opacity='.4';e.dataTransfer.effectAllowed='move';e.dataTransfer.setData('text/plain',draggedNurseId);} });
    document.addEventListener('dragend',e=>{ const c=e.target.closest('.nurse-card'); if(c){c.style.opacity='1';draggedNurseId=null;} });
    document.addEventListener('dragover',e=>{ e.preventDefault(); const s=e.target.closest('.s-slot'); if(s) s.classList.add('drag-over'); });
    document.addEventListener('dragleave',e=>{ const s=e.target.closest('.s-slot'); if(s) s.classList.remove('drag-over'); });
    document.addEventListener('drop',e=>{ e.preventDefault(); const s=e.target.closest('.s-slot'); if(s){ s.classList.remove('drag-over'); if(draggedNurseId) assign(draggedNurseId,s.id); } });
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
        const endDay = (isInit || !autoFill) ? startDay + 1 : 7;
        for(let i=startDay;i<endDay;i++){
            const tgt=`${wardId}_${i}_${sh}`;
            if(!assignments[tgt]) assignments[tgt]=[];
            if(!assignments[tgt].find(n=>n.id==nurseId)){ assignments[tgt].push(nurse); refreshSlot(tgt); }
        }
    }
    updateCounters(); detectConflicts(); clearSelection();
    if(!isInit) toast(nurse.name+' assigned','#10B981');
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
function loadExisting(){
    if(!EXISTING_ASSIGNMENTS||!EXISTING_ASSIGNMENTS.length) return;
    EXISTING_ASSIGNMENTS.forEach(rec=>{
        const nId='n_'+rec.nurse_id;
        const di=DAYS.findIndex(d=>d.fullDate===rec.shift_date);
        if(di===-1) return;
        let slotId;
        if(rec.shift_type==='Week Off'){ slotId=`weekoff_${di}`; }
        else {
            const ward=MOCK_WARDS.find(w=>w.ward_name===rec.ward_name&&w.floor_name===rec.floor_name&&w.room_type===rec.room_type);
            if(!ward) return;
            const sh=rec.shift_type==='Morning'?'m':rec.shift_type==='Evening'?'e':'n';
            slotId=`${ward.id}_${di}_${sh}`;
        }
        assign(nId,slotId,true);
    });
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
    document.querySelectorAll('#cal-body tr:not(.tr-wo)').forEach(row=>{
        const nm=row.querySelector('.wn')?.textContent.toLowerCase()||'';
        const mt=row.querySelector('.wm')?.textContent.toLowerCase()||'';
        let show=true;
        if(fl&&!mt.includes(fl)) show=false;
        if(fw&&!nm.includes(fw)) show=false;
        if(fr&&!mt.includes(fr)) show=false;
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
function clearAll(){
    if(!confirm('Clear all assignments from this week?')) return;
    Object.keys(assignments).forEach(k=>{ [...assignments[k]].forEach(n=>removeFromSlot(k,n.id)); });
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
function saveSchedule(){
    if(!Object.keys(assignments).length){ toast('Nothing to save','#F59E0B'); return; }
    if(conflicts.length>0 && !confirm(conflicts.length+' conflicts detected. Save anyway?')) return;
    const payload=[];
    Object.keys(assignments).forEach(slotId=>{ assignments[slotId].forEach(nurse=>{ const parts=slotId.split('_'),dbId=nurse.id.toString().replace('n_',''); if(parts[0]==='weekoff'){ const di=parseInt(parts[1]); payload.push({nurse_id:dbId,nurse_name:nurse.name,shift_date:DAYS[di].fullDate,shift_type:'Week Off',floor_name:null,ward_name:null,room_type:null}); } else { const wardId=parts[0]+'_'+parts[1],di=parseInt(parts[2]),sh=parts[3],st=sh==='m'?'Morning':sh==='e'?'Evening':'Night',ward=MOCK_WARDS.find(w=>w.id===wardId); payload.push({nurse_id:dbId,nurse_name:nurse.name,shift_date:DAYS[di].fullDate,shift_type:st,floor_name:ward?.floor_name||null,ward_name:ward?.ward_name||null,room_type:ward?.room_type||null}); } }); });
    payload.forEach(r=>{ if(r.shift_type==='Week Off'){ const ws=payload.find(p=>p.nurse_id===r.nurse_id&&p.shift_type!=='Week Off'); if(ws){r.floor_name=ws.floor_name;r.ward_name=ws.ward_name;r.room_type=ws.room_type;} } });
    const btn=event.currentTarget,orig=btn.innerHTML;
    btn.innerHTML='<i class="fas fa-spinner fa-spin"></i> Saving...';btn.disabled=true;
    fetch('save_shift_schedule.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({startDate:DAYS[0].fullDate,endDate:DAYS[6].fullDate,shifts:payload})})
    .then(r=>r.json()).then(data=>{ if(data.status==='success'){toast('Schedule saved!','#10B981');btn.innerHTML='<i class="fas fa-check"></i> Saved!';setTimeout(()=>window.location.reload(),1500);} else{toast('Error: '+data.message,'#EF4444');btn.innerHTML=orig;btn.disabled=false;} })
    .catch(()=>{toast('Network error','#EF4444');btn.innerHTML=orig;btn.disabled=false;});
}

// -- TOAST --
function toast(msg,color='#10B981'){
    const t=document.getElementById('toast');
    t.textContent=msg;t.style.background=color;t.style.display='block';
    setTimeout(()=>{ t.style.opacity='1'; t.style.transform='translate(-50%, -50%) scale(1)'; }, 10);
    clearTimeout(t._timer);
    t._timer=setTimeout(()=>{ t.style.opacity='0';t.style.transform='translate(-50%, -50%) scale(0.9)';setTimeout(()=>{t.style.display='none';},300); },2700);
}
</script>
</body>
</html>
