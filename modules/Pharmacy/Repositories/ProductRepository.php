<?php
namespace GM_HMS\Modules\Pharmacy\Repositories;

use GM_HMS\Database\SecureDatabase;

/**
 * ProductRepository
 * Handles CRUD operations for medicines and inventory
 */
class ProductRepository {
    private $db;

    public function __construct() {
        $this->db = SecureDatabase::getInstance();
    }

    /**
     * Get all products with optional search and filters
     */
    public function list(array $filters = [], int $limit = 5000): array {
        $where = ['1=1'];
        $params = [];

        if (!empty($filters['search'])) {
            $like = '%' . $filters['search'] . '%';
            $where[] = "(product_name LIKE ? OR product_id LIKE ? OR batch_number LIKE ? OR therapeutic LIKE ? OR content LIKE ?)";
            $params = array_merge($params, array_fill(0, 5, $like));
        }

        if (!empty($filters['form'])) {
            $where[] = "form = ?";
            $params[] = $filters['form'];
        }

        if (!empty($filters['therapeutic'])) {
            $where[] = "therapeutic = ?";
            $params[] = $filters['therapeutic'];
        }

        if (isset($filters['is_active'])) {
            $where[] = "is_active = ?";
            $params[] = (int)$filters['is_active'];
        }

        $params[] = $limit;
        return $this->db->fetchAll(
            "SELECT * FROM ph_product WHERE " . implode(' AND ', $where) . " ORDER BY product_name LIMIT ?",
            $params
        );
    }

    /**
     * Get single product by sl_no
     */
    public function getById(int $slNo): ?array {
        return $this->db->fetchOne("SELECT * FROM ph_product WHERE sl_no = ?", [$slNo]) ?: null;
    }

    /**
     * Create product
     */
    public function create(array $data): int {
        $cols = '`' . implode('`, `', array_keys($data)) . '`';
        $phs  = implode(', ', array_fill(0, count($data), '?'));
        $this->db->execute("INSERT INTO ph_product ($cols) VALUES ($phs)", array_values($data));
        return (int)$this->db->getConnection()->insert_id;
    }

    /**
     * Update product
     */
    public function update(int $slNo, array $data): bool {
        $sets = [];
        foreach ($data as $k => $v) {
            $sets[] = "`$k` = ?";
        }
        $params = array_values($data);
        $params[] = $slNo;
        $this->db->execute("UPDATE ph_product SET " . implode(', ', $sets) . " WHERE sl_no = ?", $params);
        return true;
    }

    /**
     * Delete product
     */
    public function delete(int $slNo): bool {
        $this->db->execute("DELETE FROM ph_product WHERE sl_no = ?", [$slNo]);
        return true;
    }

    /**
     * Get unique forms for filter
     */
    public function getUniqueForms(): array {
        $res = $this->db->fetchAll("SELECT DISTINCT form FROM ph_product WHERE form != '' AND form IS NOT NULL ORDER BY form");
        return array_column($res, 'form');
    }

    /**
     * Get unique therapeutics for filter
     */
    public function getUniqueTherapeutics(): array {
        $res = $this->db->fetchAll("SELECT DISTINCT therapeutic FROM ph_product WHERE therapeutic != '' AND therapeutic IS NOT NULL ORDER BY therapeutic");
        return array_column($res, 'therapeutic');
    }
}
