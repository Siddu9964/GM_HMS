<?php
namespace GM_HMS\Modules\Quality\Repositories;

use GM_HMS\Database\SecureDatabase;

class BMWRepository
{
    private $db;

    public function __construct()
    {
        $this->db = SecureDatabase::getInstance();
    }

    // ─────────────────────────────────────────────
    //  CREATE
    // ─────────────────────────────────────────────

    public function create(array $data): int
    {
        $sql = "INSERT INTO bmw_waste_records
                    (collection_at, location,
                     h_green_weight, h_red_weight, h_yellow_weight, h_blue_weight, h_white_weight,
                     h_total_weight, weight_unit, remarks, status, created_by)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $params = [
            $data['collection_at'],
            $data['location'],
            $data['h_green_weight'],
            $data['h_red_weight'],
            $data['h_yellow_weight'],
            $data['h_blue_weight'],
            $data['h_white_weight'],
            $data['h_total_weight'],
            $data['weight_unit'] ?? 'Kg',
            $data['remarks'] ?? null,
            $data['status'] ?? 'Collected',
            $data['created_by']
        ];

        $res = $this->db->execute($sql, $params);
        return (int)($res['insert_id'] ?? 0);
    }

    // ─────────────────────────────────────────────
    //  READ — single record
    // ─────────────────────────────────────────────

    public function findById(int $id): ?array
    {
        $sql = "SELECT b.*,
                       u.full_name AS logged_by_user,
                       s.full_name AS supervisor_user_name
                FROM   bmw_waste_records b
                LEFT JOIN staff u ON u.sl_no = b.created_by
                LEFT JOIN staff s ON s.sl_no = b.supervisor_id
                WHERE  b.id = ?";

        return $this->db->fetchOne($sql, [$id]) ?: null;
    }

    // ─────────────────────────────────────────────
    //  READ — paginated list with filters
    // ─────────────────────────────────────────────

    public function findAll(array $filters): array
    {
        $where  = ['1=1'];
        $params = [];

        if (!empty($filters['status'])) {
            $where[]  = 'b.status = ?';
            $params[] = $filters['status'];
        }
        if (!empty($filters['date_from'])) {
            $where[]  = 'DATE(b.collection_at) >= ?';
            $params[] = $filters['date_from'];
        }
        if (!empty($filters['date_to'])) {
            $where[]  = 'DATE(b.collection_at) <= ?';
            $params[] = $filters['date_to'];
        }
        if (!empty($filters['location'])) {
            $where[]  = 'b.location LIKE ?';
            $params[] = '%' . $filters['location'] . '%';
        }

        $whereSql = implode(' AND ', $where);
        $page     = max(1, (int)($filters['page'] ?? 1));
        $limit    = max(1, (int)($filters['limit'] ?? 25));
        $offset   = ($page - 1) * $limit;

        $countRow     = $this->db->fetchOne("SELECT COUNT(*) AS total FROM bmw_waste_records b WHERE {$whereSql}", $params);
        $totalRecords = (int)($countRow['total'] ?? 0);

        $sql = "SELECT b.*,
                       u.full_name AS logged_by_user,
                       s.full_name AS supervisor_user_name
                FROM   bmw_waste_records b
                LEFT JOIN staff u ON u.sl_no = b.created_by
                LEFT JOIN staff s ON s.sl_no = b.supervisor_id
                WHERE  {$whereSql}
                ORDER  BY b.collection_at DESC
                LIMIT  {$limit} OFFSET {$offset}";

        return [
            'total'       => $totalRecords,
            'page'        => $page,
            'limit'       => $limit,
            'total_pages' => (int)ceil($totalRecords / $limit),
            'data'        => $this->db->fetchAll($sql, $params) ?: []
        ];
    }

    // ─────────────────────────────────────────────
    //  UPDATE — collection phase (h_*)
    // ─────────────────────────────────────────────

    public function update(int $id, array $data): bool
    {
        $sql = "UPDATE bmw_waste_records
                SET    collection_at   = ?,
                       location        = ?,
                       h_green_weight  = ?,
                       h_red_weight    = ?,
                       h_yellow_weight = ?,
                       h_blue_weight   = ?,
                       h_white_weight  = ?,
                       h_total_weight  = ?,
                       remarks         = ?
                WHERE  id = ?";

        $params = [
            $data['collection_at'],
            $data['location'],
            $data['h_green_weight'],
            $data['h_red_weight'],
            $data['h_yellow_weight'],
            $data['h_blue_weight'],
            $data['h_white_weight'],
            $data['h_total_weight'],
            $data['remarks'],
            $id
        ];

        return (bool)$this->db->execute($sql, $params);
    }

    // ─────────────────────────────────────────────
    //  UPDATE — dispatch phase (v_*)
    // ─────────────────────────────────────────────

    public function updateDispatch(int $id, array $data): bool
    {
        $sql = "UPDATE bmw_waste_records
                SET    dispatch_at       = ?,
                       dispatch_time     = ?,
                       vendor_name       = ?,
                       vehicle_number    = ?,
                       driver_name       = ?,
                       driver_contact    = ?,
                       v_green_weight    = ?,
                       v_red_weight      = ?,
                       v_yellow_weight   = ?,
                       v_blue_weight     = ?,
                       v_white_weight    = ?,
                       v_total_weight    = ?,
                       weight_difference = ?,
                       supervisor_id     = ?,
                       reference_no      = ?,
                       remarks           = ?,
                       status            = ?
                WHERE  id = ?";

        $params = [
            $data['dispatch_at'],
            $data['dispatch_time'],
            $data['vendor_name'],
            $data['vehicle_number'],
            $data['driver_name'],
            $data['driver_contact'],
            $data['v_green_weight'],
            $data['v_red_weight'],
            $data['v_yellow_weight'],
            $data['v_blue_weight'],
            $data['v_white_weight'],
            $data['v_total_weight'],
            $data['weight_difference'],
            $data['supervisor_id'],
            $data['reference_no'],
            $data['remarks'],
            $data['status'],
            $id
        ];

        return (bool)$this->db->execute($sql, $params);
    }

    // ─────────────────────────────────────────────
    //  DELETE
    // ─────────────────────────────────────────────

    public function delete(int $id): bool
    {
        return (bool)$this->db->execute("DELETE FROM bmw_waste_records WHERE id = ?", [$id]);
    }

    // ─────────────────────────────────────────────
    //  ANALYTICS
    // ─────────────────────────────────────────────

    /**
     * Today's hospital collection totals
     */
    public function getTodayCollectionStats(): array
    {
        $sql = "SELECT
                    COUNT(*) AS total_entries,
                    COALESCE(SUM(h_green_weight), 0)  AS green,
                    COALESCE(SUM(h_red_weight), 0)    AS red,
                    COALESCE(SUM(h_yellow_weight), 0) AS yellow,
                    COALESCE(SUM(h_blue_weight), 0)   AS blue,
                    COALESCE(SUM(h_white_weight), 0)  AS white,
                    COALESCE(SUM(h_total_weight), 0)  AS total_weight
                FROM bmw_waste_records
                WHERE DATE(collection_at) = CURDATE()";

        return $this->db->fetchOne($sql) ?: ['total_entries' => 0, 'total_weight' => 0];
    }

    /**
     * Today's vendor dispatch totals
     */
    public function getTodayDispatchStats(): array
    {
        $sql = "SELECT
                    COUNT(*) AS total_dispatches,
                    COALESCE(SUM(v_green_weight), 0)  AS green,
                    COALESCE(SUM(v_red_weight), 0)    AS red,
                    COALESCE(SUM(v_yellow_weight), 0) AS yellow,
                    COALESCE(SUM(v_blue_weight), 0)   AS blue,
                    COALESCE(SUM(v_white_weight), 0)  AS white,
                    COALESCE(SUM(v_total_weight), 0)  AS total_weight,
                    COALESCE(SUM(weight_difference), 0) AS net_variance
                FROM bmw_waste_records
                WHERE DATE(dispatch_at) = CURDATE()
                  AND status IN ('Dispatched','Completed')";

        return $this->db->fetchOne($sql) ?: ['total_dispatches' => 0, 'total_weight' => 0];
    }

    /**
     * Count of records waiting to be dispatched
     */
    public function getPendingDispatchCount(): int
    {
        $row = $this->db->fetchOne("SELECT COUNT(*) AS pending FROM bmw_waste_records WHERE status = 'Collected'");
        return (int)($row['pending'] ?? 0);
    }

    /**
     * Latest N records for dashboard feed
     */
    public function getRecentRecords(int $limit = 10): array
    {
        $sql = "SELECT b.id, b.collection_at, b.location, b.h_total_weight, b.status,
                       u.full_name AS logged_by_user
                FROM   bmw_waste_records b
                LEFT JOIN staff u ON u.sl_no = b.created_by
                ORDER  BY b.collection_at DESC
                LIMIT  {$limit}";

        return $this->db->fetchAll($sql) ?: [];
    }

    /**
     * Last 30 days trend — one row per day
     */
    public function getMonthlyTrendChart(): array
    {
        $sql = "SELECT
                    DATE(collection_at) AS date,
                    COALESCE(SUM(h_green_weight), 0)  AS green,
                    COALESCE(SUM(h_red_weight), 0)    AS red,
                    COALESCE(SUM(h_yellow_weight), 0) AS yellow,
                    COALESCE(SUM(h_blue_weight), 0)   AS blue,
                    COALESCE(SUM(h_white_weight), 0)  AS white,
                    COALESCE(SUM(h_total_weight), 0)  AS total
                FROM bmw_waste_records
                WHERE collection_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
                GROUP BY DATE(collection_at)
                ORDER BY date ASC";

        return $this->db->fetchAll($sql) ?: [];
    }

    /**
     * Location-wise distribution (current month)
     */
    public function getLocationWiseDistribution(): array
    {
        $sql = "SELECT
                    location,
                    COALESCE(SUM(h_total_weight), 0) AS total_weight,
                    COUNT(*) AS entries
                FROM bmw_waste_records
                WHERE MONTH(collection_at) = MONTH(CURDATE())
                  AND YEAR(collection_at)  = YEAR(CURDATE())
                GROUP BY location
                ORDER BY total_weight DESC";

        return $this->db->fetchAll($sql) ?: [];
    }

    /**
     * Fetch distinct room types from hospital_beds table
     */
    public function getDistinctRoomTypes(): array
    {
        $sql = "SELECT DISTINCT room_type 
                FROM hospital_beds 
                WHERE room_type IS NOT NULL AND TRIM(room_type) != '' 
                ORDER BY room_type ASC";
        $rows = $this->db->fetchAll($sql) ?: [];
        return array_values(array_filter(array_map(function($r) {
            return trim($r['room_type'] ?? '');
        }, $rows)));
    }
}
