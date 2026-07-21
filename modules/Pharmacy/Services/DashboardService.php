<?php
namespace GM_HMS\Modules\Pharmacy\Services;

use GM_HMS\Modules\Pharmacy\Repositories\ReportRepository;
use GM_HMS\Modules\Pharmacy\Repositories\SettingsRepository;

class DashboardService {
    private $reportRepo;
    private $settingsRepo;

    public function __construct() {
        $this->reportRepo = new ReportRepository();
        $this->settingsRepo = new SettingsRepository();
    }

    public function getDashboardData(): array {
        $threshold  = (int)$this->settingsRepo->getSetting('low_stock_threshold', '20');
        $expiryDays = (int)$this->settingsRepo->getSetting('expiry_alert_days', '60');

        return [
            'stats' => [
                'total_products'  => $this->reportRepo->getTotalProducts(),
                'low_stock'       => $this->reportRepo->getLowStockCount($threshold),
                'expiry_soon'     => $this->reportRepo->getExpirySoonCount($expiryDays),
                'today_sales'     => $this->reportRepo->getTodaySalesTotal(),
                'month_sales'     => $this->reportRepo->getMonthSalesTotal(),
                'pending_indents' => $this->reportRepo->getPendingIndentsCount(),
                'total_suppliers' => $this->reportRepo->getTotalSuppliersCount(),
                'total_customers' => $this->reportRepo->getTotalCustomersCount()
            ],
            'charts' => [
                'sales_history' => $this->reportRepo->getLast7DaysSales(),
                'stock_distribution' => $this->reportRepo->getStockDistribution($threshold)
            ],
            'top_products' => $this->reportRepo->getTopSellingProducts(5),
            'recent_alerts' => $this->reportRepo->getRecentAlerts($threshold, $expiryDays, 8),
            'config' => [
                'threshold' => $threshold,
                'expiry_days' => $expiryDays
            ]
        ];
    }
}
