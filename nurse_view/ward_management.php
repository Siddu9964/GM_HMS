<?php
session_start();
require_once __DIR__ . '/../core/Autoloader.php';
require_once __DIR__ . '/includes/nurse_auth_helper.php';
use GM_HMS\Database\SecureDatabase;

// Check authentication
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['Nurse', 'Superintendent_Nurse', 'Nursing_Superintendent', 'admin', 'Admin'])) {
    header('Location: ../login.php');
    exit();
}

$nurseId = $_SESSION['user_id'] ?? null;
$nurseName = $_SESSION['username'] ?? 'Nurse';

$db = SecureDatabase::getInstance();
$conn = $db->getConnection();
$currentWard = getCurrentNurseWard($conn, $nurseId);

$rooms = [];
if ($currentWard) {
    // Fetch rooms in this ward
    $stmt = $conn->prepare("
        SELECT b.room_no as room_number, b.bed_number, b.sl_no,
               ia.patient_id, p.first_name, p.last_name
        FROM hospital_beds b
        LEFT JOIN ipd_admissions ia ON b.sl_no = ia.bed_id AND ia.status IN ('Active', 'Admitted')
        LEFT JOIN patient p ON ia.patient_id = p.patient_id
        WHERE b.floor_name = ? AND b.ward_name = ? AND b.room_type = ?
        ORDER BY b.room_no, b.bed_number
    ");
    if ($stmt) {
        $stmt->bind_param("sss", $currentWard['floor_name'], $currentWard['ward_name'], $currentWard['room_type']);
        $stmt->execute();
        $res = $stmt->get_result();
        while($r = $res->fetch_assoc()) {
            $rNum = $r['room_number'] ?: 'Unknown Room';
            $rooms[$rNum][] = $r;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="stylesheet" href="/GM_HMS/assets/css/gm-theme.css">

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ward Management - GM HMS</title>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter', sans-serif; }
        :root { --primary: #4A90E2; --primary-dark: #357ABD; --success: #28A745; --warning: #FFC107; --danger: #DC3545; --info: #17A2B8; --light: #F8F9FA; --dark: #343A40; }
        body { background: #F5F7FA; min-height: 100vh; display: flex; }
        .main-layout { display: flex; width: 100%; }
        .content-wrapper { flex: 1; display: flex; flex-direction: column; }
        .main-content { flex: 1; padding: 20px; }
        .container { max-width: 1400px; margin: 0 auto; }
        .page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; }
        .page-header h1 { font-size: 24px; color: var(--dark); font-weight: 700; }
        .ward-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px; }
        .room-card { background: white; border-radius: 12px; padding: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); }
        .room-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; border-bottom: 1px solid #F1F3F5; padding-bottom: 10px; }
        .room-title { font-weight: 700; color: var(--primary-dark); }
        .bed-list { list-style: none; }
        .bed-item { display: flex; justify-content: space-between; align-items: center; padding: 10px 0; border-bottom: 1px solid #F8F9FA; font-size: 14px; }
        .bed-status { width: 10px; height: 10px; border-radius: 50%; display: inline-block; margin-right: 8px; }
        .status-occupied { background: var(--danger); }
        .status-vacant { background: var(--success); }
        .status-cleaning { background: var(--warning); }
    </style>
</head>
<body>
    <div class="main-layout">
        <?php include 'includes/nurse_sidebar.php'; ?>
        <div class="content-wrapper">
            <?php include 'includes/nurse_navbar.php'; ?>
            <div class="main-content">
                <div class="container">
                    <div class="page-header">
                        <h1>Ward Overview & Management</h1>
                        <?php if($currentWard): ?>
                            <span style="background: var(--primary); color: white; padding: 8px 16px; border-radius: 20px; font-weight: 600;">
                                <?php echo htmlspecialchars($currentWard['ward_name'] . ' - ' . $currentWard['room_type']); ?>
                            </span>
                        <?php else: ?>
                            <span style="background: var(--danger); color: white; padding: 8px 16px; border-radius: 20px; font-weight: 600;">No Ward Assigned Today</span>
                        <?php endif; ?>
                    </div>
                    <div class="ward-grid">
                        <?php if(!$currentWard): ?>
                            <div style="grid-column: 1 / -1; text-align: center; padding: 50px; background: white; border-radius: 12px; color: #6c757d;">
                                <i class="fas fa-bed fa-3x mb-3" style="color: #dee2e6;"></i>
                                <h2>No Ward Access</h2>
                                <p>You are not currently assigned to a ward today. Check your shift schedule.</p>
                            </div>
                        <?php elseif(empty($rooms)): ?>
                            <div style="grid-column: 1 / -1; text-align: center; padding: 50px; background: white; border-radius: 12px; color: #6c757d;">
                                <p>No rooms found in this ward.</p>
                            </div>
                        <?php else: ?>
                            <?php foreach($rooms as $roomNumber => $beds): ?>
                            <div class="room-card">
                                <div class="room-header">
                                    <span class="room-title">Room <?php echo htmlspecialchars($roomNumber); ?></span>
                                    <span class="badge" style="background: #E3F2FD; color: #1976D2; font-size: 11px; padding: 3px 8px; border-radius: 10px;">
                                        <?php echo htmlspecialchars($currentWard['room_type']); ?>
                                    </span>
                                </div>
                                <ul class="bed-list">
                                    <?php foreach($beds as $bed): ?>
                                    <li class="bed-item">
                                        <span>Bed <?php echo htmlspecialchars($bed['bed_number']); ?></span>
                                        <?php if($bed['patient_id']): ?>
                                            <span style="color: var(--danger); font-weight: 600; font-size: 12px;">
                                                <span class="bed-status status-occupied"></span>
                                                <?php echo htmlspecialchars($bed['first_name'] . ' ' . $bed['last_name']); ?>
                                            </span>
                                        <?php else: ?>
                                            <span style="color: var(--success); font-weight: 600; font-size: 12px;">
                                                <span class="bed-status status-vacant"></span>Vacant
                                            </span>
                                        <?php endif; ?>
                                    </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
