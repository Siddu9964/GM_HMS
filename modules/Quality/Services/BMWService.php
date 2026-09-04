<?php
namespace GM_HMS\Modules\Quality\Services;

use Exception;
use GM_HMS\Modules\Quality\Repositories\BMWRepository;

class BMWService
{
    private $repo;

    public function __construct()
    {
        $this->repo = new BMWRepository();
    }

    // ─────────────────────────────────────────────
    //  LOG COLLECTION (h_* phase)
    // ─────────────────────────────────────────────

    public function logCollection(array $data, int $userId): int
    {
        if (empty($data['location'])) {
            throw new Exception('Collection location (Ward/Department) is required.', 400);
        }

        $green  = (float)($data['h_green_weight']  ?? 0);
        $red    = (float)($data['h_red_weight']    ?? 0);
        $yellow = (float)($data['h_yellow_weight'] ?? 0);
        $blue   = (float)($data['h_blue_weight']   ?? 0);
        $white  = (float)($data['h_white_weight']  ?? 0);

        $h_total = $green + $red + $yellow + $blue + $white;

        if ($h_total <= 0) {
            throw new Exception('At least one bin weight must be greater than 0 kg.', 400);
        }

        $payload = [
            'collection_at'    => !empty($data['collection_at']) ? $data['collection_at'] : date('Y-m-d H:i:s'),
            'location'         => trim($data['location']),
            'h_green_weight'   => $green  > 0 ? $green  : null,
            'h_red_weight'     => $red    > 0 ? $red    : null,
            'h_yellow_weight'  => $yellow > 0 ? $yellow : null,
            'h_blue_weight'    => $blue   > 0 ? $blue   : null,
            'h_white_weight'   => $white  > 0 ? $white  : null,
            'h_total_weight'   => $h_total,
            'weight_unit'      => $data['weight_unit'] ?? 'Kg',
            'remarks'          => !empty($data['remarks']) ? trim($data['remarks']) : null,
            'status'           => 'Collected',
            'created_by'       => $userId
        ];

        return $this->repo->create($payload);
    }

    // ─────────────────────────────────────────────
    //  UPDATE COLLECTION
    // ─────────────────────────────────────────────

    public function updateCollection(int $id, array $data): bool
    {
        $existing = $this->repo->findById($id);
        if (!$existing) {
            throw new Exception("Waste record #{$id} not found.", 404);
        }

        $green  = (float)($data['h_green_weight']  ?? $existing['h_green_weight']  ?? 0);
        $red    = (float)($data['h_red_weight']    ?? $existing['h_red_weight']    ?? 0);
        $yellow = (float)($data['h_yellow_weight'] ?? $existing['h_yellow_weight'] ?? 0);
        $blue   = (float)($data['h_blue_weight']   ?? $existing['h_blue_weight']   ?? 0);
        $white  = (float)($data['h_white_weight']  ?? $existing['h_white_weight']  ?? 0);

        $h_total = $green + $red + $yellow + $blue + $white;

        $updateData = [
            'collection_at'   => $data['collection_at'] ?? $existing['collection_at'],
            'location'        => !empty($data['location']) ? trim($data['location']) : $existing['location'],
            'h_green_weight'  => $green  > 0 ? $green  : null,
            'h_red_weight'    => $red    > 0 ? $red    : null,
            'h_yellow_weight' => $yellow > 0 ? $yellow : null,
            'h_blue_weight'   => $blue   > 0 ? $blue   : null,
            'h_white_weight'  => $white  > 0 ? $white  : null,
            'h_total_weight'  => $h_total,
            'remarks'         => isset($data['remarks']) ? trim($data['remarks']) : $existing['remarks']
        ];

        return $this->repo->update($id, $updateData);
    }

    // ─────────────────────────────────────────────
    //  PROCESS DISPATCH (v_* phase)
    // ─────────────────────────────────────────────

    public function processDispatch(int $id, array $data, ?int $supervisorId): array
    {
        $existing = $this->repo->findById($id);
        if (!$existing) {
            throw new Exception("Waste record #{$id} not found.", 404);
        }

        if (empty($data['vendor_name']) || empty($data['vehicle_number'])) {
            throw new Exception('Vendor name and vehicle number are required.', 400);
        }

        $v_green  = (float)($data['v_green_weight']  ?? 0);
        $v_red    = (float)($data['v_red_weight']    ?? 0);
        $v_yellow = (float)($data['v_yellow_weight'] ?? 0);
        $v_blue   = (float)($data['v_blue_weight']   ?? 0);
        $v_white  = (float)($data['v_white_weight']  ?? 0);

        $v_total = $v_green + $v_red + $v_yellow + $v_blue + $v_white;
        $h_total = (float)$existing['h_total_weight'];

        // Variance: vendor weighed vs hospital weighed
        $weight_difference = round($v_total - $h_total, 2);

        $dispatchAt   = !empty($data['dispatch_at']) ? $data['dispatch_at'] : date('Y-m-d H:i:s');
        $dispatchTime = !empty($data['dispatch_time']) ? $data['dispatch_time'] : date('H:i:s');

        // Auto-generate a reference number if not supplied
        $refNo = !empty($data['reference_no'])
            ? trim($data['reference_no'])
            : 'BMW-REF-' . date('Ymd') . '-' . str_pad($id, 4, '0', STR_PAD_LEFT);

        $updateData = [
            'dispatch_at'       => $dispatchAt,
            'dispatch_time'     => $dispatchTime,
            'vendor_name'       => trim($data['vendor_name']),
            'vehicle_number'    => strtoupper(trim($data['vehicle_number'])),
            'driver_name'       => trim($data['driver_name'] ?? ''),
            'driver_contact'    => trim($data['driver_contact'] ?? ''),
            'v_green_weight'    => $v_green  > 0 ? $v_green  : null,
            'v_red_weight'      => $v_red    > 0 ? $v_red    : null,
            'v_yellow_weight'   => $v_yellow > 0 ? $v_yellow : null,
            'v_blue_weight'     => $v_blue   > 0 ? $v_blue   : null,
            'v_white_weight'    => $v_white  > 0 ? $v_white  : null,
            'v_total_weight'    => $v_total,
            'weight_difference' => $weight_difference,
            'supervisor_id'     => $supervisorId,
            'reference_no'      => $refNo,
            'remarks'           => !empty($data['remarks']) ? trim($data['remarks']) : $existing['remarks'],
            'status'            => 'Completed'
        ];

        $this->repo->updateDispatch($id, $updateData);

        return [
            'id'                => $id,
            'reference_no'      => $refNo,
            'h_total_weight'    => $h_total,
            'v_total_weight'    => $v_total,
            'weight_difference' => $weight_difference,
            'status'            => 'Completed'
        ];
    }

    // ─────────────────────────────────────────────
    //  GENERIC READS
    // ─────────────────────────────────────────────

    public function getRecords(array $filters): array
    {
        return $this->repo->findAll($filters);
    }

    public function getRecordById(int $id): ?array
    {
        return $this->repo->findById($id);
    }

    public function deleteRecord(int $id): bool
    {
        $existing = $this->repo->findById($id);
        if (!$existing) {
            throw new Exception("Waste record #{$id} not found.", 404);
        }
        return $this->repo->delete($id);
    }

    public function getRoomTypes(): array
    {
        return $this->repo->getDistinctRoomTypes();
    }
}
