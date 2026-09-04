<?php
namespace GM_HMS\Modules\Quality\Requests;

use Exception;

class BMWCollectionCreateRequest
{
    public static function validate(array $data): array
    {
        if (empty($data['location'])) {
            throw new Exception('Collection location (Ward/OT/Department) is required.', 400);
        }

        $green  = (float)($data['h_green_weight']  ?? 0);
        $red    = (float)($data['h_red_weight']    ?? 0);
        $yellow = (float)($data['h_yellow_weight'] ?? 0);
        $blue   = (float)($data['h_blue_weight']   ?? 0);
        $white  = (float)($data['h_white_weight']  ?? 0);

        if (($green + $red + $yellow + $blue + $white) <= 0) {
            throw new Exception('At least one bin weight must be greater than 0 kg.', 400);
        }

        return $data;
    }
}
