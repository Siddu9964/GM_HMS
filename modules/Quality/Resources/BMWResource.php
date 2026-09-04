<?php
namespace GM_HMS\Modules\Quality\Resources;

class BMWResource
{
    public static function toArray(array $item): array
    {
        return [
            'id'                  => (int)$item['id'],
            'collection_at'       => $item['collection_at'],
            'location'            => $item['location'],
            'h_green_weight'      => (float)($item['h_green_weight']  ?? 0),
            'h_red_weight'        => (float)($item['h_red_weight']    ?? 0),
            'h_yellow_weight'     => (float)($item['h_yellow_weight'] ?? 0),
            'h_blue_weight'       => (float)($item['h_blue_weight']   ?? 0),
            'h_white_weight'      => (float)($item['h_white_weight']  ?? 0),
            'h_total_weight'      => (float)($item['h_total_weight']  ?? 0),
            'weight_unit'         => $item['weight_unit'] ?? 'Kg',
            'dispatch_at'         => $item['dispatch_at'],
            'dispatch_time'       => $item['dispatch_time'],
            'vendor_name'         => $item['vendor_name'],
            'vehicle_number'      => $item['vehicle_number'],
            'driver_name'         => $item['driver_name'],
            'driver_contact'      => $item['driver_contact'],
            'v_green_weight'      => (float)($item['v_green_weight']  ?? 0),
            'v_red_weight'        => (float)($item['v_red_weight']    ?? 0),
            'v_yellow_weight'     => (float)($item['v_yellow_weight'] ?? 0),
            'v_blue_weight'       => (float)($item['v_blue_weight']   ?? 0),
            'v_white_weight'      => (float)($item['v_white_weight']  ?? 0),
            'v_total_weight'      => (float)($item['v_total_weight']  ?? 0),
            'weight_difference'   => (float)($item['weight_difference'] ?? 0),
            'supervisor_id'       => $item['supervisor_id'] ? (int)$item['supervisor_id'] : null,
            'supervisor_user_name'=> $item['supervisor_user_name'] ?? null,
            'reference_no'        => $item['reference_no'],
            'remarks'             => $item['remarks'],
            'status'              => $item['status'],
            'created_by'          => (int)$item['created_by'],
            'logged_by_user'      => $item['logged_by_user'] ?? null,
            'created_at'          => $item['created_at'],
            'updated_at'          => $item['updated_at']
        ];
    }

    public static function collection(array $items): array
    {
        return array_map([self::class, 'toArray'], $items);
    }
}
