<?php
/**
 * ============================================================
 * NurseShiftController — API Reference
 * ============================================================
 * Base URL : http://localhost/GM_HMS/api
 * Auth     : All endpoints require Auth (Session or Bearer token)
 * Tables   : nurse_allocation, hospital_beds, staff
 * ------------------------------------------------------------
 *
 * 1. GET /api/nurse-shifts
 *    Query Params:
 *      role_id    (int)    - Filter by nurse role ID
 *      date_from  (date)   - Shift date from YYYY-MM-DD
 *      date_to    (date)   - Shift date to YYYY-MM-DD
 *      ward       (string) - Filter by ward name
 *    Response: [ { id, role_id, nurse_name, shift_date, shift_type, ward_name, status } ]
 *
 * 2. POST /api/nurse-shifts        [Required: role_id, shift_date_from, shift_date_to, shift_type, ward_name]
 *    Creates shifts for a date range (one shift per day in range)
 *    Body:
 *      {
 *        "role_id":         12,
 *        "shift_date_from": "2026-06-26",
 *        "shift_date_to":   "2026-06-30",
 *        "shift_type":      "Morning",
 *        "ward_name":       "General Ward",
 *        "floor_name":      "Ground Floor",
 *        "floor_number":    0,
 *        "ward_type":       "General",
 *        "room_number":     "101",
 *        "room_name":       "Recovery Room",
 *        "assigned_beds":   "Bed 1, Bed 2",
 *        "status":          "Scheduled"
 *      }
 *    shift_type: Morning | Evening | Night
 *    Response 201: { allocation_id, days, message }
 *
 * 3. PUT /api/nurse-shifts/{id}
 *    Body: Send only fields to update (same as POST)
 *
 * 4. DELETE /api/nurse-shifts/{id}
 *    No body required.
 *
 * 5. GET /api/nurse-shifts/nurses
 *    Returns all Active staff with Nurse in designation.
 *    Response: [ { sl_no, role_id, full_name, designation } ]
 *
 * 6. GET /api/nurse-shifts/wards
 *    Query: ward_type, floor (optional filters)
 *    Response: [ { ward_name, ward_type, floor_name, floor_number } ]
 *
 * 7. GET /api/nurse-shifts/floors
 *    Response: [ { floor_number, floor_name } ]
 *
 * 8. GET /api/nurse-shifts/ward-types
 *    Response: [ { ward_type } ]
 *
 * 9. GET /api/nurse-shifts/rooms
 *    Query: ward, floor_number (optional)
 *    Response: [ { floor_number, floor_name, ward_name, ward_type, room_number, room_name } ]
 * ------------------------------------------------------------
 */
namespace GM_HMS\Controllers\api;

use Exception;
use GM_HMS\Controllers\BaseController;
use GM_HMS\Models\NurseShiftModel;

/**
 * Nurse Shift API Controller
 * Handles administrative operations for assigning and managing nurse shifts
 */
class NurseShiftController extends BaseController
{
    private $shiftModel;

    public function __construct()
    {
        parent::__construct();
        $this->shiftModel = new NurseShiftModel();
    }

    /**
     * GET /api/nurse-shifts
     * List all allocations with filters
     */
    public function index()
    {
        $this->restrictMethod('GET');
        $this->requireAuth();

        try {
            $roleId = $_GET['role_id'] ?? null;
            $dateFrom = $_GET['date_from'] ?? null;
            $dateTo = $_GET['date_to'] ?? null;
            $ward = $_GET['ward'] ?? null;

            $sql = "SELECT na.*, r.role_name as nurse_name
                    FROM nurse_allocation na
                    LEFT JOIN roles r ON na.role_id = r.sl_no
                    WHERE 1=1";
            $params = [];

            if ($roleId) {
                $sql .= " AND na.role_id = ?";
                $params[] = $roleId;
            }
            if ($dateFrom) {
                $sql .= " AND na.shift_date >= ?";
                $params[] = $dateFrom;
            }
            if ($dateTo) {
                $sql .= " AND na.shift_date <= ?";
                $params[] = $dateTo;
            }
            if ($ward) {
                $sql .= " AND na.ward_name = ?";
                $params[] = $ward;
            }

            $sql .= " ORDER BY na.shift_date DESC, na.shift_type ASC";

            $shifts = $this->db->fetchAll($sql, $params);
            $this->respondSuccess($shifts);
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    /**
     * POST /api/nurse-shifts
     * Create a new allocation (supports date ranges)
     */
    public function create()
    {
        $this->restrictMethod('POST');
        $this->requireAuth();

        $schema = [
            'required' => ['role_id', 'shift_date_from', 'shift_date_to', 'shift_type', 'ward_name'],
            'properties' => [
                'role_id' => ['type' => 'integer'],
                'shift_date_from' => ['type' => 'string', 'format' => 'date'],
                'shift_date_to' => ['type' => 'string', 'format' => 'date'],
                'shift_type' => ['type' => 'string', 'enum' => ['Morning', 'Evening', 'Night']],
                'work_area' => ['type' => 'string'],
                'ward_name' => ['type' => 'string'],
                'floor_name' => ['type' => 'string'],
                'floor_number' => ['type' => 'integer'],
                'ward_type' => ['type' => 'string'],
                'room_number' => ['type' => 'string'],
                'room_name' => ['type' => 'string'],
                'assigned_beds' => ['type' => 'string'],
                'status' => ['type' => 'string', 'enum' => ['Scheduled', 'Active', 'Completed']]
            ]
        ];

        $data = $this->getJsonInput($schema);

        try {
            // Create shift with date range
            $result = $this->shiftModel->createBulkShifts($data);

            $message = $result['days'] == 1
                ? "Shift assignment created successfully"
                : "Shift assignment created for {$result['days']} days ({$data['shift_date_from']} to {$data['shift_date_to']})";

            $this->respondCreated([
                'allocation_id' => $result['ids'][0],
                'days' => $result['days'],
                'message' => $message
            ]);
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    /**
     * PUT /api/nurse-shifts/{id}
     * Update an allocation
     */
    public function update($id)
    {
        $this->restrictMethod('PUT');
        $this->requireAuth();

        $data = $this->getJsonInput();

        try {
            $this->db->update('nurse_allocation', $data, 'id = ?', [$id]);
            $this->respondSuccess(null, 'Allocation updated successfully');
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    /**
     * DELETE /api/nurse-shifts/{id}
     * Remove an allocation
     */
    public function delete($id)
    {
        $this->restrictMethod('DELETE');
        $this->requireAuth();

        try {
            $this->db->delete('nurse_allocation', 'id = ?', [$id]);
            $this->respondSuccess(null, 'Allocation deleted successfully');
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    /**
     * GET /api/nurse-shifts/nurses
     * Get all roles for the allocation dropdown
     */
    public function getNurses()
    {
        $this->restrictMethod('GET');
        $this->requireAuth();

        try {
            $sql = "SELECT sl_no, role_id, full_name, designation 
                    FROM staff 
                    WHERE designation LIKE '%Nurse%' AND status = 'Active'
                    ORDER BY full_name ASC";
            $nurses = $this->db->fetchAll($sql);
            $this->respondSuccess($nurses);
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    /**
     * GET /api/nurse-shifts/wards
     * Get distinct wards from hospital_beds, optionally filtered by ward_type
     */
    public function getWards()
    {
        $this->restrictMethod('GET');
        $this->requireAuth();

        try {
            $wardType = $_GET['ward_type'] ?? $_GET['category'] ?? null;
            $floorName = $_GET['floor'] ?? null;
            $floorNumber = $_GET['floor_number'] ?? null;

            $sql = "SELECT DISTINCT ward_name, ward_type, floor_name, floor_number
                    FROM hospital_beds
                    WHERE ward_name IS NOT NULL AND ward_name != ''";
            $params = [];

            if ($wardType) {
                $sql .= " AND ward_type = ?";
                $params[] = $wardType;
            }
            if ($floorName) {
                $sql .= " AND floor_name = ?";
                $params[] = $floorName;
            }
            if ($floorNumber) {
                $sql .= " AND floor_number = ?";
                $params[] = $floorNumber;
            }

            $sql .= " ORDER BY floor_name ASC, ward_type ASC, ward_name ASC";

            $wards = $this->db->fetchAll($sql, $params);
            $this->respondSuccess($wards);
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    /**
     * GET /api/nurse-shifts/floors
     * Get distinct floor names from hospital_beds
     */
    public function getFloors()
    {
        $this->restrictMethod('GET');
        $this->requireAuth();

        try {
            $sql = "SELECT DISTINCT floor_number, floor_name
                    FROM hospital_beds
                    WHERE floor_name IS NOT NULL AND floor_name != ''
                    ORDER BY floor_number ASC";
            $floors = $this->db->fetchAll($sql);
            $this->respondSuccess($floors);
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    /**
     * GET /api/nurse-shifts/ward-types
     * Get distinct ward types from hospital_beds
     */
    public function getWardTypes()
    {
        $this->restrictMethod('GET');
        $this->requireAuth();

        try {
            $sql = "SELECT DISTINCT ward_type
                    FROM hospital_beds
                    WHERE ward_type IS NOT NULL AND ward_type != ''
                    ORDER BY ward_type ASC";
            $wardTypes = $this->db->fetchAll($sql);
            $this->respondSuccess($wardTypes);
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    /**
     * GET /api/nurse-shifts/rooms
     * Get distinct room numbers from hospital_beds, optionally filtered by ward
     */
    public function getRooms()
    {
        $this->restrictMethod('GET');
        $this->requireAuth();

        try {
            $ward = $_GET['ward'] ?? null;
            $floorNumber = $_GET['floor_number'] ?? null;

            $sql = "SELECT DISTINCT
                        floor_number,
                        floor_name,
                        ward_name,
                        ward_type,
                        room_number,
                        room_name
                    FROM hospital_beds
                    WHERE room_number IS NOT NULL
                      AND room_number != ''";
            $params = [];

            if ($ward) {
                $sql .= " AND ward_name = ?";
                $params[] = $ward;
            }
            if ($floorNumber) {
                $sql .= " AND floor_number = ?";
                $params[] = $floorNumber;
            }

            $sql .= " ORDER BY floor_number ASC, ward_name ASC, room_number ASC";

            $rooms = $this->db->fetchAll($sql, $params);
            $this->respondSuccess($rooms);
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }
}
