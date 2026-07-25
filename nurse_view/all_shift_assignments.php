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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Assigned Shifts - GM HMS</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <style>
        :root {
            --primary: #1f6b4a;
            --primary-light: #2a8f63;
            --primary-dark: #154a33;
            --bg-color: #f3efe6;
            --card-bg: #ffffff;
            --text-main: #1e293b;
            --text-muted: #64748b;
            --border-color: rgba(31, 107, 74, 0.1);
            --shadow-sm: 0 4px 12px rgba(31,107,74,0.05);
        }

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Plus Jakarta Sans', sans-serif; }
        body { background: var(--bg-color); min-height: 100vh; display: flex; color: var(--text-main); overflow-x: hidden; }
        .main-layout { display: flex; width: 100%; }
        .content-wrapper { flex: 1; display: flex; flex-direction: column; overflow: hidden; height: 100vh; }
        
        @media (min-width: 1024px) {
            .content-wrapper { margin-left: 185px; } /* Prevent sidebar overlap */
        }
        
        @media (max-width: 768px) {
            .main-content { padding: 15px; }
            .header-toolbar { flex-direction: column; align-items: stretch; gap: 15px; }
            .header-toolbar > div:last-child { display: flex; justify-content: stretch; width: 100%; }
            .header-toolbar > div:last-child button { flex: 1; justify-content: center; }
            .header-title h1 { font-size: 20px; }
            .header-title .icon-box { width: 40px; height: 40px; font-size: 16px; }
            .filters-panel { flex-direction: column; padding: 15px; gap: 10px; }
            .filter-group { min-width: 100%; }
            th, td { padding: 12px 15px; font-size: 13px; }
        }
        
        .main-content { flex: 1; padding: 30px; overflow-y: auto; }
        .container { max-width: 1400px; margin: 0 auto; animation: fadeIn 0.4s ease-out; }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .header-toolbar {
            display: flex; justify-content: space-between; align-items: flex-end; 
            margin-bottom: 25px;
        }

        .header-title {
            display: flex; align-items: center; gap: 12px;
        }
        .header-title h1 { font-size: 24px; font-weight: 800; color: var(--primary-dark); }
        .header-title .icon-box { 
            background: rgba(31,107,74,0.1); color: var(--primary); 
            width: 48px; height: 48px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 20px;
        }

        .filters-panel {
            background: var(--card-bg); padding: 20px; border-radius: 16px; margin-bottom: 25px;
            box-shadow: var(--shadow-sm); display: flex; gap: 15px; flex-wrap: wrap; align-items: flex-end;
        }
        .filter-group { display: flex; flex-direction: column; gap: 6px; flex: 1; min-width: 150px; }
        .filter-group label { font-size: 12px; font-weight: 700; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.5px; }
        .filter-group select {
            padding: 12px 15px; border: 1px solid var(--border-color); border-radius: 10px;
            font-size: 14px; font-weight: 600; color: var(--primary-dark); outline: none; background: #fff; cursor: pointer;
        }
        .filter-group select:focus { border-color: var(--primary); }

        .btn {
            padding: 12px 20px; border-radius: 10px; font-size: 14px; font-weight: 700; cursor: pointer; border: none;
            display: flex; align-items: center; gap: 8px; transition: all 0.2s;
        }
        .btn-outline { background: transparent; border: 2px solid var(--primary); color: var(--primary); }
        .btn-outline:hover { background: rgba(31,107,74,0.05); }
        
        .btn-pdf { border-color: #0284c7; color: #0284c7; }
        .btn-pdf:hover { background: rgba(2, 132, 199, 0.05); }
        
        .card {
            background: var(--card-bg); border-radius: 16px; padding: 0; box-shadow: var(--shadow-sm); overflow: hidden;
        }

        /* Table */
        .table-responsive { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; white-space: nowrap; }
        th { 
            background: rgba(243, 239, 230, 0.5); padding: 16px 20px; text-align: left;
            font-size: 12px; font-weight: 800; color: var(--primary-dark); text-transform: uppercase; letter-spacing: 1px;
            border-bottom: 2px solid var(--border-color);
        }
        td { 
            padding: 16px 20px; border-bottom: 1px solid var(--border-color);
            font-size: 14px; font-weight: 600; color: var(--text-main);
        }
        tr:hover { background: rgba(243, 239, 230, 0.3); }
        tr:last-child td { border-bottom: none; }

        .nurse-info { display: flex; align-items: center; gap: 12px; }
        .nurse-avatar { 
            width: 36px; height: 36px; border-radius: 10px; background: rgba(31,107,74,0.1);
            color: var(--primary); display: flex; align-items: center; justify-content: center; font-size: 14px; font-weight: 800;
        }

        .badge { padding: 6px 12px; border-radius: 50px; font-size: 12px; font-weight: 700; text-transform: uppercase; }
        .badge-morning { background: #d1fae5; color: #065f46; }
        .badge-evening { background: #fef3c7; color: #92400e; }
        .badge-night { background: #e0e7ff; color: #3730a3; }
        .badge-weekoff { background: #fee2e2; color: #991b1b; }

        .date-range { display: flex; align-items: center; gap: 8px; font-size: 13px; color: var(--text-main); font-weight: 700; }
        .date-range i { color: var(--primary); opacity: 0.6; }

        /* Print Specific CSS */
        @media print {
            body * { visibility: hidden; }
            #printableArea, #printableArea * { visibility: visible; }
            #printableArea { position: absolute; left: 0; top: 0; width: 100%; box-shadow: none; }
            .nurse-sidebar { display: none !important; }
            .content-wrapper { padding: 0 !important; margin: 0 !important; }
            th { background: #f3efe6 !important; -webkit-print-color-adjust: exact; }
            .badge { border: 1px solid #ccc; background: transparent !important; color: #000 !important; }
        }
    </style>
</head>
<body>
    <div class="main-layout">
        <?php include 'includes/nurse_sidebar.php'; ?>
        <div class="content-wrapper">
            <?php include 'includes/nurse_navbar.php'; ?>
            
            <div class="main-content">
                <div class="container">
                    
                    <div class="header-toolbar">
                        <div class="header-title">
                            <div class="icon-box"><i class="fas fa-list-alt"></i></div>
                            <div>
                                <h1>All Assigned Shifts</h1>
                                <p style="color:var(--text-muted); font-size:13px; font-weight:600; margin-top:2px;">View grouped assignments by week range.</p>
                            </div>
                        </div>
                        
                        <div style="display: flex; gap: 10px;">
                            <button class="btn btn-outline" onclick="window.print()">
                                <i class="fas fa-print"></i> Print
                            </button>
                            <button class="btn btn-outline btn-pdf" onclick="exportPDF()">
                                <i class="fas fa-file-pdf"></i> Export PDF
                            </button>
                        </div>
                    </div>

                    <!-- Filters -->
                    <div class="filters-panel">
                        <div class="filter-group">
                            <label>Floor</label>
                            <select id="filterFloor" onchange="applyFilters()">
                                <option value="">All Floors</option>
                                <?php foreach($floors as $f): ?>
                                    <option value="<?php echo htmlspecialchars($f); ?>"><?php echo htmlspecialchars($f); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="filter-group">
                            <label>Ward</label>
                            <select id="filterWard" onchange="applyFilters()">
                                <option value="">All Wards</option>
                                <?php foreach($wards as $w): ?>
                                    <option value="<?php echo htmlspecialchars($w); ?>"><?php echo htmlspecialchars($w); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="filter-group">
                            <label>Room Type</label>
                            <select id="filterRoom" onchange="applyFilters()">
                                <option value="">All Room Types</option>
                                <?php foreach($roomTypes as $r): ?>
                                    <option value="<?php echo htmlspecialchars($r); ?>"><?php echo htmlspecialchars($r); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="card" id="printableArea">
                        <div class="table-responsive">
                            <table id="shiftTable">
                                <thead>
                                    <tr>
                                        <th>Nurse</th>
                                        <th>Date Range</th>
                                        <th>Shift Type</th>
                                        <th>Floor</th>
                                        <th>Ward</th>
                                        <th>Room Type</th>
                                        <th>Days Assigned</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if(empty($allShifts)): ?>
                                        <tr><td colspan="7" style="text-align:center; padding:40px; color:var(--text-muted);">No shift assignments found.</td></tr>
                                    <?php else: ?>
                                        <?php foreach($allShifts as $shift): 
                                            $type = strtolower($shift['shift_type'] ?? '');
                                            $badgeClass = 'badge-morning';
                                            if(strpos($type, 'evening') !== false) $badgeClass = 'badge-evening';
                                            elseif(strpos($type, 'night') !== false) $badgeClass = 'badge-night';
                                            elseif(strpos($type, 'week off') !== false) $badgeClass = 'badge-weekoff';
                                            
                                            $initials = substr($shift['nurse_name'], 0, 2);
                                        ?>
                                            <tr data-floor="<?php echo htmlspecialchars($shift['floor_name']); ?>"
                                                data-ward="<?php echo htmlspecialchars($shift['ward_name']); ?>"
                                                data-room="<?php echo htmlspecialchars($shift['room_type']); ?>">
                                                
                                                <td>
                                                    <div class="nurse-info">
                                                        <div class="nurse-avatar"><?php echo strtoupper($initials); ?></div>
                                                        <div style="font-weight: 700; color: var(--primary-dark);"><?php echo htmlspecialchars($shift['nurse_name']); ?></div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="date-range">
                                                        <i class="far fa-calendar-alt"></i>
                                                        <?php echo date('d M', strtotime($shift['start_date'])); ?> 
                                                        <i class="fas fa-arrow-right" style="font-size:10px; margin:0 4px;"></i> 
                                                        <?php echo date('d M, Y', strtotime($shift['end_date'])); ?>
                                                    </div>
                                                </td>
                                                <td><span class="badge <?php echo $badgeClass; ?>"><?php echo htmlspecialchars($shift['shift_type']); ?></span></td>
                                                <td><?php echo htmlspecialchars($shift['floor_name']); ?></td>
                                                <td><?php echo htmlspecialchars($shift['ward_name']); ?></td>
                                                <td><?php echo htmlspecialchars($shift['room_type']); ?></td>
                                                <td><span style="font-weight:800; color:var(--primary);"><?php echo $shift['days_count']; ?> days</span></td>
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
            let fFloor = document.getElementById('filterFloor').value.toLowerCase();
            let fWard = document.getElementById('filterWard').value.toLowerCase();
            let fRoom = document.getElementById('filterRoom').value.toLowerCase();
            
            let table = document.getElementById("shiftTable");
            let tr = table.getElementsByTagName("tr");

            for (let i = 1; i < tr.length; i++) {
                // Skip header row
                let rFloor = tr[i].getAttribute('data-floor');
                if(!rFloor) continue; // If no data attribute (e.g. empty state row)
                
                rFloor = rFloor.toLowerCase();
                let rWard = tr[i].getAttribute('data-ward').toLowerCase();
                let rRoom = tr[i].getAttribute('data-room').toLowerCase();

                let show = true;
                if(fFloor && rFloor !== fFloor) show = false;
                if(fWard && rWard !== fWard) show = false;
                if(fRoom && rRoom !== fRoom) show = false;
                
                tr[i].style.display = show ? "" : "none";
            }
        }

        function exportPDF() {
            const element = document.getElementById('printableArea');
            
            const opt = {
                margin:       0.5,
                filename:     'Nurse_Assignments.pdf',
                image:        { type: 'jpeg', quality: 0.98 },
                html2canvas:  { scale: 2, useCORS: true },
                jsPDF:        { unit: 'in', format: 'a4', orientation: 'landscape' }
            };
            
            const btn = document.querySelector('.btn-pdf');
            const originalHTML = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Generating...';
            
            html2pdf().set(opt).from(element).save().then(() => {
                btn.innerHTML = originalHTML;
            });
        }
    </script>
</body>
</html>
