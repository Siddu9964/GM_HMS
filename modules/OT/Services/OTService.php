<?php
namespace GM_HMS\Modules\OT\Services;

use GM_HMS\Modules\OT\Repositories\OTRepository;

class OTService
{
    private $repo;

    public function __construct()
    {
        $this->repo = new OTRepository();
    }

    /**
     * Get dashboard stats
     */
    public function getDashboardStats()
    {
        return $this->repo->getDashboardStats();
    }

    /**
     * Get OT rooms from hospital_beds
     */
    public function getOTRooms()
    {
        return $this->repo->getOTRooms();
    }

    /**
     * Get all surgeries with optional filters
     */
    public function getSurgeries($date = null, $status = '', $search = '')
    {
        return $this->repo->getAll($date, $status, $search);
    }

    /**
     * Get distinct doctor roles from surgeries table
     */
    public function getDistinctDoctorRoles()
    {
        return $this->repo->getDistinctDoctorRoles();
    }

    /**
     * Get a single surgery by ID
     */
    public function getSurgeryById($id)
    {
        $surgery = $this->repo->getById($id);
        if (!$surgery) {
            throw new \Exception("Surgery record not found");
        }
        return $surgery;
    }

    /**
     * Create a new surgery
     */
    public function createSurgery($data)
    {
        // Validate required fields
        $required = ['patient_name', 'schedule_date', 'start_time', 'surgery_name'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                throw new \Exception("Field '{$field}' is required");
            }
        }
        
        if (empty($data['name']) || $data['name'] === '[]' || $data['name'] === '[""]') {
            throw new \Exception("At least one Surgeon Name is required");
        }

        // Check Room Availability
        $endTime = $data['end_time'] ?? null;
        if (!empty($data['ot_room_name'])) {
            $conflict = $this->repo->checkRoomAvailability(
                $data['ot_room_name'],
                $data['schedule_date'],
                $data['start_time'],
                $endTime
            );

            if ($conflict) {
                throw new \Exception("This OT Room is already scheduled for another surgery at the selected date and time. Please select a different OT Room or time.");
            }
        }

        $result = $this->repo->create($data);
        if (!$result) {
            throw new \Exception("Failed to create surgery record");
        }
        return $result;
    }

    /**
     * Update an existing surgery
     */
    public function updateSurgery($id, $data)
    {
        $existing = $this->getSurgeryById($id); // ensure it exists

        // If time/date/room is being updated, check availability
        $room = $data['ot_room_name'] ?? $existing['ot_room_name'];
        $date = $data['schedule_date'] ?? $existing['schedule_date'];
        $start = $data['start_time'] ?? $existing['start_time'];
        // Fix array key issue for end_time:
        $end = array_key_exists('end_time', $data) ? $data['end_time'] : $existing['end_time'];

        if (!empty($room)) {
            $conflict = $this->repo->checkRoomAvailability($room, $date, $start, $end, $id);
            if ($conflict) {
                throw new \Exception("This OT Room is already scheduled for another surgery at the selected date and time. Please select a different OT Room or time.");
            }
        }

        $result = $this->repo->update($id, $data);
        if (!$result) {
            throw new \Exception("Failed to update surgery record");
        }
        return $this->repo->getById($id);
    }

    /**
     * Update surgery status with an optional reason note
     */
    public function updateStatus($id, $status, $reason = null)
    {
        $allowed = ['Scheduled', 'Ongoing', 'Completed', 'Cancelled', 'Postponed', 'Preponed'];
        if (!in_array($status, $allowed)) {
            throw new \Exception("Invalid status. Allowed: " . implode(', ', $allowed));
        }

        // Require reason for certain statuses
        $requiresReason = ['Cancelled', 'Postponed', 'Preponed'];
        if (in_array($status, $requiresReason) && empty($reason)) {
            throw new \Exception("A reason note is required when status is '{$status}'");
        }

        $surgery = $this->getSurgeryById($id); // ensure it exists

        // STRICT RULES:
        // 1. Prevent setting 'Ongoing' or 'Completed' for future dates
        if (in_array($status, ['Ongoing', 'Completed']) && $surgery['schedule_date'] > date('Y-m-d')) {
            throw new \Exception("Cannot mark a future surgery as {$status}.");
        }

        // 2. Require End Time when marking as 'Completed'
        if ($status === 'Completed' && empty($surgery['end_time'])) {
            throw new \Exception("Cannot mark as Completed. Please edit the surgery and add an End Time first.");
        }

        $result = $this->repo->updateStatus($id, $status, $reason);
        if (!$result) {
            throw new \Exception("Failed to update surgery status");
        }
        return $this->repo->getById($id);
    }

    /**
     * Update only the start and end time
     */
    public function updateTime($id, $data)
    {
        $existing = $this->getSurgeryById($id); // ensure it exists

        $start = array_key_exists('start_time', $data) ? $data['start_time'] : $existing['start_time'];
        $end = array_key_exists('end_time', $data) ? $data['end_time'] : $existing['end_time'];

        $conflict = $this->repo->checkRoomAvailability($existing['ot_room_name'], $existing['schedule_date'], $start, $end, $id);
        if ($conflict) {
            throw new \Exception("Time overlaps with another surgery. Patient: {$conflict['patient_name']} ({$conflict['start_time']} - " . ($conflict['end_time'] ?: 'Ongoing') . ")");
        }

        $result = $this->repo->updateTime($id, $start, $end);
        if (!$result) {
            throw new \Exception("Failed to update surgery time");
        }
        return $this->repo->getById($id);
    }

    /**
     * Delete a surgery record
     */
    public function deleteSurgery($id)
    {
        $this->getSurgeryById($id); // ensure it exists
        $result = $this->repo->delete($id);
        if (!$result) {
            throw new \Exception("Failed to delete surgery record");
        }
        return ['message' => 'Surgery record deleted successfully'];
    }

    /**
     * Search patient ID
     */
    public function searchPatient($patientId)
    {
        if (empty($patientId)) {
            throw new \Exception("Patient ID is required");
        }
        return $this->repo->searchPatient($patientId);
    }

    /**
     * Search Pharmacy Products
     */
    public function searchPatientsList($query)
    {
        if (strlen($query) < 2) return [];
        return $this->repo->searchPatientsList($query);
    }

    public function searchPharmacyProducts($query)
    {
        return $this->repo->searchPharmacyProducts($query);
    }

    /**
     * Create Pharmacy Order
     */
    public function createPharmacyOrder($data, $userId)
    {
        $patientId = $data['patient_id'] ?? '';
        $admissionId = $data['admission_id'] ?? '';
        $items = $data['items'] ?? [];

        if (empty($patientId)) {
            throw new \Exception("Patient ID is required");
        }
        if (empty($items) || !is_array($items)) {
            throw new \Exception("Items are required");
        }

        return $this->repo->createPharmacyOrder($patientId, $admissionId, $items, $userId);
    }

    public function getPendingPharmacyOrders($patientId, $admissionId)
    {
        if (empty($patientId)) {
            throw new \Exception("Patient ID is required");
        }
        return $this->repo->getPendingPharmacyOrders($patientId, $admissionId);
    }

    public function reconcilePharmacyOrder($data, $userId)
    {
        $patientId = $data['patient_id'] ?? '';
        $admissionId = $data['admission_id'] ?? '';
        $items = $data['items'] ?? [];

        if (empty($patientId)) {
            throw new \Exception("Patient ID is required");
        }
        if (empty($items) || !is_array($items)) {
            throw new \Exception("Items are required");
        }

        return $this->repo->reconcilePharmacyOrder($patientId, $admissionId, $items, $userId);
    }

    public function getAllPharmacyOrders()
    {
        return $this->repo->getAllPharmacyOrders();
    }
    public function editPharmacyOrderItem($patientId, $admissionId, $uniqueId, $qty, $userId)
    {
        if (empty($patientId) || empty($uniqueId) || empty($qty)) {
            throw new \Exception("Missing required fields for editing order.");
        }
        return $this->repo->editPharmacyOrderItem($patientId, $admissionId, $uniqueId, $qty, $userId);
    }

    public function deletePharmacyOrderItem($patientId, $admissionId, $uniqueId, $userId)
    {
        if (empty($patientId) || empty($uniqueId)) {
            throw new \Exception("Missing required fields for deleting order item.");
        }
        return $this->repo->deletePharmacyOrderItem($patientId, $admissionId, $uniqueId, $userId);
    }

    /**
     * Check OT Room Availability API
     */
    public function checkRoom($data)
    {
        $room = $data['ot_room_name'] ?? '';
        $date = $data['schedule_date'] ?? '';
        $start = $data['start_time'] ?? '';
        $end = $data['end_time'] ?? null;
        $id = $data['exclude_id'] ?? null;

        if (!$room || !$date || !$start) {
            return ['available' => true]; // not enough info to check yet
        }

        $conflict = $this->repo->checkRoomAvailability($room, $date, $start, $end, $id);
        
        if ($conflict) {
            return [
                'available' => false,
                'conflict' => $conflict
            ];
        }

        return ['available' => true];
    }
    
    /**
     * Get distinct departments / specializations
     */
    public function getDistinctDepartments()
    {
        return $this->repo->getDistinctDepartments();
    }
}
