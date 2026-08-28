<?php
/**
 * Nurse Shift Assignment Management API
 * Handles Edit, Update, Delete, Reassign, and Swap operations for nurse shift assignments.
 */

error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

require_once __DIR__ . '/../../core/Autoloader.php';
use GM_HMS\Database\SecureDatabase;

header('Content-Type: application/json');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Authentication & Role Check
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['Superintendent_Nurse', 'Superintendent Nurse', 'admin', 'Admin', 'Head Nurse'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
    exit();
}

$inputJSON = file_get_contents('php://input');
$data = json_decode($inputJSON, true);

$action = $_GET['action'] ?? $data['action'] ?? '';

try {
    $db = SecureDatabase::getInstance();
    $conn = $db->getConnection();

    switch ($action) {
        // ─────────────────────────────────────────────────────────────
        // 1. GET METADATA (Nurses, Floors, Wards, Room Types)
        // ─────────────────────────────────────────────────────────────
        case 'get_meta':
            // Active Nurses
            $nurses = [];
            $stmtNurses = $conn->query("
                SELECT sl_no as staff_id, full_name as name, designation as role, status 
                FROM staff 
                WHERE designation IN ('Nurse', 'Superintendent_Nurse', 'Nursing_Superintendent', 'Head Nurse', 'Staff Nurse')
                ORDER BY full_name ASC
            ");
            if ($stmtNurses) {
                while ($r = $stmtNurses->fetch_assoc()) {
                    $nurses[] = [
                        'id'     => $r['staff_id'],
                        'name'   => $r['name'],
                        'role'   => $r['role'] ?: 'Staff Nurse',
                        'status' => $r['status']
                    ];
                }
            }

            // Floors
            $floors = [];
            $stmtFloors = $conn->query("SELECT DISTINCT floor_name FROM hospital_beds WHERE floor_name IS NOT NULL AND floor_name != ''");
            if ($stmtFloors) {
                while ($r = $stmtFloors->fetch_assoc()) $floors[] = $r['floor_name'];
            }
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

            // Wards & Rooms mapping
            $wardTree = [];
            $stmtWards = $conn->query("
                SELECT floor_name, ward_name, room_type, GROUP_CONCAT(DISTINCT room_name SEPARATOR ', ') as room_names, COUNT(bed_number) as total_beds
                FROM hospital_beds
                WHERE floor_name IS NOT NULL AND ward_name IS NOT NULL
                GROUP BY floor_name, ward_name, room_type
                ORDER BY floor_name, ward_name, room_type
            ");
            if ($stmtWards) {
                while ($r = $stmtWards->fetch_assoc()) {
                    $wardTree[] = $r;
                }
            }

            // Active list of all shift assignments for live swapping reference
            $activeAssignments = [];
            $stmtActive = $conn->query("SELECT sl_no, floor_name, ward_name, room_type, room_name, start_date, end_date, shift_data FROM shift_schedules ORDER BY start_date DESC");
            if ($stmtActive) {
                while ($row = $stmtActive->fetch_assoc()) {
                    $jsonData = json_decode($row['shift_data'], true);
                    if (is_array($jsonData)) {
                        $grouped = [];
                        foreach ($jsonData as $shift) {
                            $key = $shift['nurse_id'] . '|' . $shift['shift_type'];
                            if (!isset($grouped[$key])) {
                                $grouped[$key] = [
                                    'nurse_id'   => $shift['nurse_id'],
                                    'nurse_name' => $shift['nurse_name'],
                                    'shift_type' => $shift['shift_type'],
                                    'dates'      => []
                                ];
                            }
                            $grouped[$key]['dates'][] = $shift['shift_date'];
                        }
                        foreach ($grouped as $g) {
                            $activeAssignments[] = [
                                'schedule_id' => (int)$row['sl_no'],
                                'nurse_id'    => (string)$g['nurse_id'],
                                'nurse_name'  => $g['nurse_name'],
                                'shift_type'  => $g['shift_type'],
                                'start_date'  => $row['start_date'],
                                'end_date'    => $row['end_date'],
                                'floor_name'  => $row['floor_name'] ?: 'Unassigned',
                                'ward_name'   => $row['ward_name'] ?: 'Unassigned',
                                'room_type'   => $row['room_type'] ?: 'Unassigned',
                                'room_name'   => $row['room_name'] ?: '',
                                'dates'       => $g['dates'],
                                'days_count'  => count($g['dates'])
                            ];
                        }
                    }
                }
            }

            echo json_encode([
                'success' => true,
                'data' => [
                    'nurses'             => $nurses,
                    'floors'             => $floors,
                    'wardTree'           => $wardTree,
                    'activeAssignments'  => $activeAssignments
                ]
            ]);
            break;

        // ─────────────────────────────────────────────────────────────
        // 2. DELETE SHIFT ASSIGNMENT
        // ─────────────────────────────────────────────────────────────
        case 'delete_assignment':
            $scheduleId = (int)($data['schedule_id'] ?? 0);
            $nurseId    = (string)($data['nurse_id'] ?? '');
            $shiftType  = $data['shift_type'] ?? '';
            $datesToDelete = $data['dates'] ?? []; // Optional specific dates

            if (!$scheduleId || $nurseId === '' || !$shiftType) {
                echo json_encode(['success' => false, 'message' => 'Missing required assignment identifiers.']);
                exit();
            }

            $conn->begin_transaction();

            $stmt = $conn->prepare("SELECT sl_no, shift_data FROM shift_schedules WHERE sl_no = ? FOR UPDATE");
            $stmt->bind_param("i", $scheduleId);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();

            if (!$row) {
                $conn->rollback();
                echo json_encode(['success' => false, 'message' => 'Shift schedule record not found.']);
                exit();
            }

            $shiftData = json_decode($row['shift_data'], true);
            if (!is_array($shiftData)) $shiftData = [];

            // Filter out matching entries
            $newShiftData = [];
            $deletedCount = 0;
            foreach ($shiftData as $item) {
                $itemNurseId = (string)($item['nurse_id'] ?? '');
                $itemShiftType = $item['shift_type'] ?? '';
                $itemDate = $item['shift_date'] ?? '';

                $matchesNurseAndShift = ($itemNurseId === $nurseId && $itemShiftType === $shiftType);
                $matchesDate = empty($datesToDelete) || in_array($itemDate, $datesToDelete);

                if ($matchesNurseAndShift && $matchesDate) {
                    $deletedCount++;
                } else {
                    $newShiftData[] = $item;
                }
            }

            if (empty($newShiftData)) {
                // No shifts left in this room schedule, delete the row
                $stmtDel = $conn->prepare("DELETE FROM shift_schedules WHERE sl_no = ?");
                $stmtDel->bind_param("i", $scheduleId);
                $stmtDel->execute();
            } else {
                // Update with remaining shifts
                $updatedJson = json_encode($newShiftData);
                $stmtUpd = $conn->prepare("UPDATE shift_schedules SET shift_data = ? WHERE sl_no = ?");
                $stmtUpd->bind_param("si", $updatedJson, $scheduleId);
                $stmtUpd->execute();
            }

            $conn->commit();
            echo json_encode([
                'success' => true,
                'message' => "Successfully removed {$deletedCount} shift assignment(s)."
            ]);
            break;

        // ─────────────────────────────────────────────────────────────
        // 3. EDIT / UPDATE / REASSIGN SHIFT ASSIGNMENT
        // ─────────────────────────────────────────────────────────────
        case 'edit_assignment':
            $scheduleId     = (int)($data['schedule_id'] ?? 0);
            $oldNurseId     = (string)($data['old_nurse_id'] ?? '');
            $oldShiftType   = $data['old_shift_type'] ?? '';
            
            $newNurseId     = (string)($data['new_nurse_id'] ?? $oldNurseId);
            $newShiftType   = $data['new_shift_type'] ?? $oldShiftType;
            $newFloorName   = $data['new_floor_name'] ?? null;
            $newWardName    = $data['new_ward_name'] ?? null;
            $newRoomType    = $data['new_room_type'] ?? null;
            $assignedDates  = $data['dates'] ?? []; // Array of dates to assign

            if (!$scheduleId || $oldNurseId === '' || !$oldShiftType || !$newNurseId || !$newShiftType) {
                echo json_encode(['success' => false, 'message' => 'Invalid parameters for shift edit.']);
                exit();
            }

            $conn->begin_transaction();

            // Fetch Old Schedule Row
            $stmt = $conn->prepare("SELECT * FROM shift_schedules WHERE sl_no = ? FOR UPDATE");
            $stmt->bind_param("i", $scheduleId);
            $stmt->execute();
            $oldRow = $stmt->get_result()->fetch_assoc();

            if (!$oldRow) {
                $conn->rollback();
                echo json_encode(['success' => false, 'message' => 'Source schedule record not found.']);
                exit();
            }

            // Fetch Nurse Name
            $stmtN = $conn->prepare("SELECT full_name, status FROM staff WHERE sl_no = ?");
            $stmtN->bind_param("s", $newNurseId);
            $stmtN->execute();
            $nurseInfo = $stmtN->get_result()->fetch_assoc();
            if (!$nurseInfo) {
                $conn->rollback();
                echo json_encode(['success' => false, 'message' => 'Selected nurse not found in staff records.']);
                exit();
            }
            if ($nurseInfo['status'] === 'On Leave') {
                $conn->rollback();
                echo json_encode(['success' => false, 'message' => "Nurse {$nurseInfo['full_name']} is currently marked 'On Leave'."]);
                exit();
            }
            $newNurseName = $nurseInfo['full_name'];

            $targetFloor = $newFloorName ?: $oldRow['floor_name'];
            $targetWard  = $newWardName ?: $oldRow['ward_name'];
            $targetRoom  = $newRoomType ?: $oldRow['room_type'];
            $startDate   = $oldRow['start_date'];
            $endDate     = $oldRow['end_date'];

            // Check if dates were provided; if not, keep original dates of old assignment
            $oldShiftData = json_decode($oldRow['shift_data'], true) ?: [];
            if (empty($assignedDates)) {
                foreach ($oldShiftData as $s) {
                    if ((string)$s['nurse_id'] === $oldNurseId && $s['shift_type'] === $oldShiftType) {
                        $assignedDates[] = $s['shift_date'];
                    }
                }
            }

            // ── VALIDATION: Check for double-booking conflicts for the new nurse ──
            if ($newShiftType !== 'Week Off') {
                $stmtCheck = $conn->prepare("
                    SELECT sl_no, floor_name, ward_name, room_type, shift_data
                    FROM shift_schedules
                    WHERE (start_date <= ? AND end_date >= ?)
                ");
                $stmtCheck->bind_param("ss", $endDate, $startDate);
                $stmtCheck->execute();
                $checkRes = $stmtCheck->get_result();

                while ($cRow = $checkRes->fetch_assoc()) {
                    $cShifts = json_decode($cRow['shift_data'], true) ?: [];
                    foreach ($cShifts as $cs) {
                        // Skip if it's the exact assignment we are modifying
                        if ((int)$cRow['sl_no'] === $scheduleId && (string)$cs['nurse_id'] === $oldNurseId && $cs['shift_type'] === $oldShiftType) {
                            continue;
                        }
                        if ((string)$cs['nurse_id'] === $newNurseId && in_array($cs['shift_date'], $assignedDates)) {
                            if ($cs['shift_type'] === $newShiftType && ($cRow['floor_name'] !== $targetFloor || $cRow['ward_name'] !== $targetWard || $cRow['room_type'] !== $targetRoom)) {
                                $conn->rollback();
                                echo json_encode([
                                    'success' => false,
                                    'message' => "Conflict: Nurse {$newNurseName} is already assigned to {$cRow['ward_name']} ({$cRow['floor_name']}) for the {$newShiftType} shift on {$cs['shift_date']}."
                                ]);
                                exit();
                            }
                            if ($cs['shift_type'] === 'Week Off') {
                                $conn->rollback();
                                echo json_encode([
                                    'success' => false,
                                    'message' => "Conflict: Nurse {$newNurseName} has a scheduled Week Off on {$cs['shift_date']}."
                                ]);
                                exit();
                            }
                        }
                    }
                }
            }

            $isSameLocation = ($targetFloor === $oldRow['floor_name'] && $targetWard === $oldRow['ward_name'] && $targetRoom === $oldRow['room_type']);

            if ($isSameLocation) {
                // Update directly in the same row
                $updatedShifts = [];
                foreach ($oldShiftData as $s) {
                    if ((string)$s['nurse_id'] === $oldNurseId && $s['shift_type'] === $oldShiftType) {
                        if (in_array($s['shift_date'], $assignedDates)) {
                            $updatedShifts[] = [
                                'nurse_id'   => $newNurseId,
                                'nurse_name' => $newNurseName,
                                'shift_date' => $s['shift_date'],
                                'shift_type' => $newShiftType
                            ];
                        }
                    } else {
                        $updatedShifts[] = $s;
                    }
                }

                $updatedJson = json_encode($updatedShifts);
                $stmtUpd = $conn->prepare("UPDATE shift_schedules SET shift_data = ? WHERE sl_no = ?");
                $stmtUpd->bind_param("si", $updatedJson, $scheduleId);
                $stmtUpd->execute();

            } else {
                // Relocation to another floor/ward/room:
                // 1. Remove old nurse entries from old schedule
                $remainingOldShifts = [];
                foreach ($oldShiftData as $s) {
                    if (!((string)$s['nurse_id'] === $oldNurseId && $s['shift_type'] === $oldShiftType && in_array($s['shift_date'], $assignedDates))) {
                        $remainingOldShifts[] = $s;
                    }
                }

                if (empty($remainingOldShifts)) {
                    $stmtDel = $conn->prepare("DELETE FROM shift_schedules WHERE sl_no = ?");
                    $stmtDel->bind_param("i", $scheduleId);
                    $stmtDel->execute();
                } else {
                    $updOldJson = json_encode($remainingOldShifts);
                    $stmtUpdOld = $conn->prepare("UPDATE shift_schedules SET shift_data = ? WHERE sl_no = ?");
                    $stmtUpdOld->bind_param("si", $updOldJson, $scheduleId);
                    $stmtUpdOld->execute();
                }

                // 2. Find or create destination schedule record
                $stmtFindDest = $conn->prepare("
                    SELECT sl_no, shift_data FROM shift_schedules 
                    WHERE start_date = ? AND end_date = ? AND floor_name = ? AND ward_name = ? AND room_type = ?
                    LIMIT 1 FOR UPDATE
                ");
                $stmtFindDest->bind_param("sssss", $startDate, $endDate, $targetFloor, $targetWard, $targetRoom);
                $stmtFindDest->execute();
                $destRow = $stmtFindDest->get_result()->fetch_assoc();

                // Prepare new shift entries to add
                $newEntries = [];
                foreach ($assignedDates as $d) {
                    $newEntries[] = [
                        'nurse_id'   => $newNurseId,
                        'nurse_name' => $newNurseName,
                        'shift_date' => $d,
                        'shift_type' => $newShiftType
                    ];
                }

                if ($destRow) {
                    $destShifts = json_decode($destRow['shift_data'], true) ?: [];
                    $destShifts = array_merge($destShifts, $newEntries);
                    $destJson = json_encode($destShifts);

                    $stmtUpdDest = $conn->prepare("UPDATE shift_schedules SET shift_data = ? WHERE sl_no = ?");
                    $stmtUpdDest->bind_param("si", $destJson, $destRow['sl_no']);
                    $stmtUpdDest->execute();
                } else {
                    // Fetch room names for the target ward
                    $stmtFetchRoom = $conn->prepare("
                        SELECT GROUP_CONCAT(DISTINCT room_name SEPARATOR ', ') as r_name
                        FROM hospital_beds
                        WHERE floor_name = ? AND ward_name = ? AND room_type = ?
                    ");
                    $stmtFetchRoom->bind_param("sss", $targetFloor, $targetWard, $targetRoom);
                    $stmtFetchRoom->execute();
                    $rFetch = $stmtFetchRoom->get_result()->fetch_assoc();
                    $destRoomName = $rFetch['r_name'] ?? $targetRoom;

                    $destJson = json_encode($newEntries);
                    $assignedBy = $_SESSION['user_id'] ?? 1;

                    $stmtInsDest = $conn->prepare("
                        INSERT INTO shift_schedules (floor_name, ward_name, room_type, room_name, start_date, end_date, shift_data, assigned_by)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                    ");
                    $stmtInsDest->bind_param("sssssssi", $targetFloor, $targetWard, $targetRoom, $destRoomName, $startDate, $endDate, $destJson, $assignedBy);
                    $stmtInsDest->execute();
                }
            }

            $conn->commit();
            echo json_encode([
                'success' => true,
                'message' => "Shift assignment for {$newNurseName} updated successfully."
            ]);
            break;

        // ─────────────────────────────────────────────────────────────
        // 4. ADVANCED NURSE SWAP MATRIX
        // ─────────────────────────────────────────────────────────────
        case 'swap_assignments':
            $sourceSchedId  = (int)($data['source']['schedule_id'] ?? 0);
            $sourceNurseId  = (string)($data['source']['nurse_id'] ?? '');
            $sourceShiftType= $data['source']['shift_type'] ?? '';

            $targetSchedId  = (int)($data['target']['schedule_id'] ?? 0);
            $targetNurseId  = (string)($data['target']['nurse_id'] ?? '');
            $targetShiftType= $data['target']['shift_type'] ?? '';

            $swapMode       = $data['swap_mode'] ?? 'nurse'; // 'nurse' (swap personnel), 'full' (swap full slot)

            if (!$sourceSchedId || $sourceNurseId === '' || !$sourceShiftType ||
                !$targetSchedId || $targetNurseId === '' || !$targetShiftType) {
                echo json_encode(['success' => false, 'message' => 'Missing source or target assignment parameters for swap.']);
                exit();
            }

            if ($sourceSchedId === $targetSchedId && $sourceNurseId === $targetNurseId && $sourceShiftType === $targetShiftType) {
                echo json_encode(['success' => false, 'message' => 'Cannot swap an assignment with itself.']);
                exit();
            }

            $conn->begin_transaction();

            // Fetch Source Schedule
            $stmtSrc = $conn->prepare("SELECT * FROM shift_schedules WHERE sl_no = ? FOR UPDATE");
            $stmtSrc->bind_param("i", $sourceSchedId);
            $stmtSrc->execute();
            $srcRow = $stmtSrc->get_result()->fetch_assoc();

            // Fetch Target Schedule
            $stmtTgt = $conn->prepare("SELECT * FROM shift_schedules WHERE sl_no = ? FOR UPDATE");
            $stmtTgt->bind_param("i", $targetSchedId);
            $stmtTgt->execute();
            $tgtRow = $stmtTgt->get_result()->fetch_assoc();

            if (!$srcRow || !$tgtRow) {
                $conn->rollback();
                echo json_encode(['success' => false, 'message' => 'One or both schedule records not found.']);
                exit();
            }

            // Fetch Nurse Details
            $stmtN1 = $conn->prepare("SELECT full_name FROM staff WHERE sl_no = ?");
            $stmtN1->bind_param("s", $sourceNurseId);
            $stmtN1->execute();
            $srcNurse = $stmtN1->get_result()->fetch_assoc();

            $stmtN2 = $conn->prepare("SELECT full_name FROM staff WHERE sl_no = ?");
            $stmtN2->bind_param("s", $targetNurseId);
            $stmtN2->execute();
            $tgtNurse = $stmtN2->get_result()->fetch_assoc();

            $srcNurseName = $srcNurse['full_name'] ?? 'Nurse A';
            $tgtNurseName = $tgtNurse['full_name'] ?? 'Nurse B';

            $srcShifts = json_decode($srcRow['shift_data'], true) ?: [];
            $tgtShifts = ($sourceSchedId === $targetSchedId) ? $srcShifts : (json_decode($tgtRow['shift_data'], true) ?: []);

            if ($sourceSchedId === $targetSchedId) {
                // Swapping within the same room schedule row
                foreach ($srcShifts as &$s) {
                    if ((string)$s['nurse_id'] === $sourceNurseId && $s['shift_type'] === $sourceShiftType) {
                        $s['nurse_id'] = $targetNurseId;
                        $s['nurse_name'] = $tgtNurseName;
                    } elseif ((string)$s['nurse_id'] === $targetNurseId && $s['shift_type'] === $targetShiftType) {
                        $s['nurse_id'] = $sourceNurseId;
                        $s['nurse_name'] = $srcNurseName;
                    }
                }
                unset($s);

                $updatedJson = json_encode($srcShifts);
                $stmtUpd = $conn->prepare("UPDATE shift_schedules SET shift_data = ? WHERE sl_no = ?");
                $stmtUpd->bind_param("si", $updatedJson, $sourceSchedId);
                $stmtUpd->execute();

            } else {
                // Swapping across two different schedule rows (different floors/rooms)
                foreach ($srcShifts as &$s) {
                    if ((string)$s['nurse_id'] === $sourceNurseId && $s['shift_type'] === $sourceShiftType) {
                        $s['nurse_id'] = $targetNurseId;
                        $s['nurse_name'] = $tgtNurseName;
                    }
                }
                unset($s);

                foreach ($tgtShifts as &$s) {
                    if ((string)$s['nurse_id'] === $targetNurseId && $s['shift_type'] === $targetShiftType) {
                        $s['nurse_id'] = $sourceNurseId;
                        $s['nurse_name'] = $srcNurseName;
                    }
                }
                unset($s);

                $srcJson = json_encode($srcShifts);
                $stmtUpdSrc = $conn->prepare("UPDATE shift_schedules SET shift_data = ? WHERE sl_no = ?");
                $stmtUpdSrc->bind_param("si", $srcJson, $sourceSchedId);
                $stmtUpdSrc->execute();

                $tgtJson = json_encode($tgtShifts);
                $stmtUpdTgt = $conn->prepare("UPDATE shift_schedules SET shift_data = ? WHERE sl_no = ?");
                $stmtUpdTgt->bind_param("si", $tgtJson, $targetSchedId);
                $stmtUpdTgt->execute();
            }

            $conn->commit();
            echo json_encode([
                'success' => true,
                'message' => "Successfully swapped duties between {$srcNurseName} and {$tgtNurseName}."
            ]);
            break;

        default:
            echo json_encode(['success' => false, 'message' => 'Invalid or unspecified action.']);
            break;
    }

} catch (Throwable $e) {
    if (isset($conn) && $conn->ping()) {
        @$conn->rollback();
    }
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Server Error: ' . $e->getMessage()
    ]);
}
