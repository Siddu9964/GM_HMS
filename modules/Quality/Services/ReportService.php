<?php
namespace GM_HMS\Modules\Quality\Services;

use GM_HMS\Modules\Quality\Repositories\ReportRepository;
use Exception;

class ReportService
{
    private $repo;

    public function __construct()
    {
        $this->repo = new ReportRepository();
    }

    /**
     * Generate report based on type
     */
    public function generateReport(string $type, array $params): array
    {
        switch ($type) {
            case 'daily':
                return $this->buildDailyReport($params['date'] ?? date('Y-m-d'));

            case 'monthly':
                return $this->buildMonthlyReport(
                    (int)($params['month'] ?? date('m')),
                    (int)($params['year']  ?? date('Y'))
                );

            case 'reconciliation':
                return $this->buildReconciliationReport(
                    (int)($params['month'] ?? date('m')),
                    (int)($params['year']  ?? date('Y'))
                );

            default:
                throw new Exception("Unknown report type: {$type}", 400);
        }
    }

    // ─────────────────────────────────────────────

    private function buildDailyReport(string $date): array
    {
        $rows = $this->repo->getDailyReport($date);

        $totals = ['green' => 0, 'red' => 0, 'yellow' => 0, 'blue' => 0, 'white' => 0, 'total' => 0];
        foreach ($rows as $row) {
            $totals['green']  += (float)$row['green'];
            $totals['red']    += (float)$row['red'];
            $totals['yellow'] += (float)$row['yellow'];
            $totals['blue']   += (float)$row['blue'];
            $totals['white']  += (float)$row['white'];
            $totals['total']  += (float)$row['total'];
        }

        return [
            'type'   => 'daily',
            'date'   => $date,
            'rows'   => $rows,
            'totals' => $totals
        ];
    }

    // ─────────────────────────────────────────────

    private function buildMonthlyReport(int $month, int $year): array
    {
        $rows      = $this->repo->getMonthlyReport($month, $year);
        $records   = $this->repo->getMonthlyDetailedRecords($month, $year);
        $locations = $this->repo->getMonthlyLocationBreakdown($month, $year);

        $grandTotal = ['h_total' => 0, 'v_total' => 0, 'variance' => 0, 'entries' => 0];
        foreach ($rows as $row) {
            $grandTotal['h_total']  += (float)$row['h_total'];
            $grandTotal['v_total']  += (float)$row['v_total'];
            $grandTotal['variance'] += (float)$row['variance'];
            $grandTotal['entries']  += (int)$row['entries'];
        }

        return [
            'type'        => 'monthly',
            'month'       => $month,
            'year'        => $year,
            'rows'        => $rows,
            'records'     => $records,
            'locations'   => $locations,
            'grand_total' => $grandTotal
        ];
    }

    // ─────────────────────────────────────────────

    private function buildReconciliationReport(int $month, int $year): array
    {
        $rows = $this->repo->getMonthlyReconciliation($month, $year);

        $totals = ['h_total' => 0, 'v_total' => 0, 'variance' => 0];
        foreach ($rows as $row) {
            $totals['h_total']  += (float)$row['h_total'];
            $totals['v_total']  += (float)$row['v_total'];
            $totals['variance'] += (float)$row['variance'];
        }

        return [
            'type'   => 'reconciliation',
            'month'  => $month,
            'year'   => $year,
            'rows'   => $rows,
            'totals' => $totals
        ];
    }
}
