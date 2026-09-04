<?php
namespace GM_HMS\Modules\Quality\Repositories;

use GM_HMS\Database\SecureDatabase;

class ReportRepository
{
    private $db;

    public function __construct()
    {
        $this->db = SecureDatabase::getInstance();
    }

    /**
     * Daily collection matrix — all rows for a given date, grouped by location
     */
    public function getDailyReport(string $date): array
    {
        $sql = "SELECT
                    location,
                    DATE_FORMAT(collection_at, '%H:%i') AS time,
                    COALESCE(h_green_weight, 0)  AS green,
                    COALESCE(h_red_weight, 0)    AS red,
                    COALESCE(h_yellow_weight, 0) AS yellow,
                    COALESCE(h_blue_weight, 0)   AS blue,
                    COALESCE(h_white_weight, 0)  AS white,
                    COALESCE(h_total_weight, 0)  AS total,
                    status,
                    reference_no,
                    vendor_name,
                    id
                FROM bmw_waste_records
                WHERE DATE(collection_at) = ?
                ORDER BY collection_at ASC";

        return $this->db->fetchAll($sql, [$date]) ?: [];
    }

    /**
     * Monthly summary — one row per day with bin colour totals
     */
    public function getMonthlyReport(int $month, int $year): array
    {
        $sql = "SELECT
                    DATE(collection_at)           AS date,
                    COUNT(*)                      AS entries,
                    COALESCE(SUM(h_green_weight), 0)  AS green,
                    COALESCE(SUM(h_red_weight), 0)    AS red,
                    COALESCE(SUM(h_yellow_weight), 0) AS yellow,
                    COALESCE(SUM(h_blue_weight), 0)   AS blue,
                    COALESCE(SUM(h_white_weight), 0)  AS white,
                    COALESCE(SUM(h_total_weight), 0)  AS h_total,
                    COALESCE(SUM(v_total_weight), 0)  AS v_total,
                    COALESCE(SUM(weight_difference), 0) AS variance,
                    SUM(CASE WHEN status = 'Collected' THEN 1 ELSE 0 END) AS pending_count,
                    SUM(CASE WHEN status = 'Completed' OR status = 'Dispatched' THEN 1 ELSE 0 END) AS completed_count,
                    GROUP_CONCAT(DISTINCT status SEPARATOR ', ') AS statuses
                FROM bmw_waste_records
                WHERE MONTH(collection_at) = ?
                  AND YEAR(collection_at)  = ?
                GROUP BY DATE(collection_at)
                ORDER BY date ASC";

        return $this->db->fetchAll($sql, [$month, $year]) ?: [];
    }

    /**
     * Monthly itemized collection records — all individual entries for that month
     */
    public function getMonthlyDetailedRecords(int $month, int $year): array
    {
        $sql = "SELECT
                    id,
                    DATE_FORMAT(collection_at, '%Y-%m-%d %H:%i') AS collection_time,
                    location,
                    COALESCE(h_green_weight, 0)  AS green,
                    COALESCE(h_red_weight, 0)    AS red,
                    COALESCE(h_yellow_weight, 0) AS yellow,
                    COALESCE(h_blue_weight, 0)   AS blue,
                    COALESCE(h_white_weight, 0)  AS white,
                    COALESCE(h_total_weight, 0)  AS total,
                    COALESCE(v_total_weight, 0)  AS v_total,
                    COALESCE(weight_difference, 0) AS variance,
                    status,
                    reference_no,
                    vendor_name
                FROM bmw_waste_records
                WHERE MONTH(collection_at) = ?
                  AND YEAR(collection_at)  = ?
                ORDER BY collection_at ASC";

        return $this->db->fetchAll($sql, [$month, $year]) ?: [];
    }

    /**
     * Monthly location-wise breakdown
     */
    public function getMonthlyLocationBreakdown(int $month, int $year): array
    {
        $sql = "SELECT
                    location,
                    COALESCE(SUM(h_green_weight), 0)  AS green,
                    COALESCE(SUM(h_red_weight), 0)    AS red,
                    COALESCE(SUM(h_yellow_weight), 0) AS yellow,
                    COALESCE(SUM(h_blue_weight), 0)   AS blue,
                    COALESCE(SUM(h_white_weight), 0)  AS white,
                    COALESCE(SUM(h_total_weight), 0)  AS h_total,
                    COUNT(*) AS entries
                FROM bmw_waste_records
                WHERE MONTH(collection_at) = ?
                  AND YEAR(collection_at)  = ?
                GROUP BY location
                ORDER BY h_total DESC";

        return $this->db->fetchAll($sql, [$month, $year]) ?: [];
    }

    /**
     * Monthly dispatch reconciliation — hospital vs vendor weights
     */
    public function getMonthlyReconciliation(int $month, int $year): array
    {
        $sql = "SELECT
                    DATE(dispatch_at) AS dispatch_date,
                    vendor_name,
                    reference_no,
                    COALESCE(SUM(h_total_weight), 0)  AS h_total,
                    COALESCE(SUM(v_total_weight), 0)  AS v_total,
                    COALESCE(SUM(weight_difference), 0) AS variance
                FROM bmw_waste_records
                WHERE status IN ('Dispatched','Completed')
                  AND MONTH(dispatch_at) = ?
                  AND YEAR(dispatch_at)  = ?
                GROUP BY DATE(dispatch_at), vendor_name, reference_no
                ORDER BY dispatch_date ASC";

        return $this->db->fetchAll($sql, [$month, $year]) ?: [];
    }
}
