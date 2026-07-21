<?php
namespace GM_HMS\Modules\Pharmacy\Resources;

class DashboardResource {
    public static function format(array $data): array {
        // Formatting logic if needed (e.g. currency symbols, number rounding)
        $data['stats']['today_sales_formatted'] = '₹' . number_format($data['stats']['today_sales'], 2);
        $data['stats']['month_sales_formatted'] = '₹' . number_format($data['stats']['month_sales'], 2);
        
        return $data;
    }
}
