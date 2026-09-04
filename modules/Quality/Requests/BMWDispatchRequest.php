<?php
namespace GM_HMS\Modules\Quality\Requests;

use Exception;

class BMWDispatchRequest
{
    public static function validate(array $data): array
    {
        if (empty($data['vendor_name'])) {
            throw new Exception('Vendor name is required.', 400);
        }
        if (empty($data['vehicle_number'])) {
            throw new Exception('Vehicle number is required.', 400);
        }

        $v_total = (float)($data['v_green_weight']  ?? 0)
                 + (float)($data['v_red_weight']    ?? 0)
                 + (float)($data['v_yellow_weight'] ?? 0)
                 + (float)($data['v_blue_weight']   ?? 0)
                 + (float)($data['v_white_weight']  ?? 0);

        if ($v_total <= 0) {
            throw new Exception('At least one vendor bin weight must be greater than 0 kg.', 400);
        }

        return $data;
    }
}
