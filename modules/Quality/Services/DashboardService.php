<?php
namespace GM_HMS\Modules\Quality\Services;

use GM_HMS\Modules\Quality\Repositories\BMWRepository;

class DashboardService
{
    private $repo;

    public function __construct()
    {
        $this->repo = new BMWRepository();
    }

    /**
     * Aggregates all data needed for the BMW dashboard page
     */
    public function getStats(): array
    {
        return [
            'today_collected'  => $this->repo->getTodayCollectionStats(),
            'today_dispatched' => $this->repo->getTodayDispatchStats(),
            'pending_dispatch' => $this->repo->getPendingDispatchCount(),
            'recent_records'   => $this->repo->getRecentRecords(10),
            'monthly_trends'   => $this->repo->getMonthlyTrendChart(),
            'location_wise'    => $this->repo->getLocationWiseDistribution()
        ];
    }
}
