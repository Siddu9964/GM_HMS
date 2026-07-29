<?php
/**
 * IPD Services Search API
 * Searches lab_services, radiology_services, other_services
 * Returns service name, category, and price based on room type
 *
 * Usage: GET /GM_HMS/reception_view/api/ipd_services_search.php?q=echo&room_type=general_ward
 * room_type: general_ward | semi_private | private_icu | suite | opd
 */

session_start();
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

header('Content-Type: application/json');

$q = trim($_GET['q'] ?? '');
$roomType = $_GET['room_type'] ?? 'general_ward';

if (strlen($q) < 2) {
    echo json_encode(['success' => true, 'data' => []]);
    exit();
}

try {
    $pdo = new PDO(
        'mysql:host=localhost;dbname=hmsci;charset=utf8mb4',
        'root',
        '',
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    $like = '%' . $q . '%';
    $results = [];

    // ── Map room_type → column names ───────────────────
    $labCol   = 'gw_rate';
    $othCol   = 'op_gw_price';
    $radCol   = 'general_ward_price';

    switch ($roomType) {
        case 'semi_private':
            $labCol = 'spvt_rate';
            $othCol = 'semi_private_price';
            $radCol = 'semi_private_price';
            break;
        case 'private_icu':
            $labCol = 'pvt_ccu_rate';
            $othCol = 'private_icu_price';
            $radCol = 'private_icu_price';
            break;
        case 'suite':
            $labCol = 'suite_rate';
            $othCol = 'suite_price';
            $radCol = 'suite_price';
            break;
        case 'opd':
            $labCol = 'opd_rate';
            $othCol = 'op_gw_price';
            $radCol = 'opd_price';
            break;
    }

    // ── lab_services ────────────────────────────────────
    $stmt = $pdo->prepare("SELECT service_id, test_name AS name, '{$labCol}' AS price_col, {$labCol} AS price
                            FROM lab_services
                            WHERE test_name LIKE ?
                            ORDER BY test_name
                            LIMIT 20");
    $stmt->execute([$like]);
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $results[] = [
            'service_id'   => $row['service_id'],
            'name'         => $row['name'],
            'category'     => 'LAB',
            'charge_type'  => 'LAB',
            'price'        => (float) $row['price'],
        ];
    }

    // ── radiology_services ──────────────────────────────
    $stmt = $pdo->prepare("SELECT service_id, billing_name AS name, {$radCol} AS price, modality_name
                            FROM radiology_services
                            WHERE billing_name LIKE ?
                            ORDER BY billing_name
                            LIMIT 20");
    $stmt->execute([$like]);
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $results[] = [
            'service_id'   => $row['service_id'],
            'name'         => $row['name'],
            'category'     => 'RADIOLOGY',
            'charge_type'  => 'RADIOLOGY',
            'price'        => (float) $row['price'],
            'sub'          => $row['modality_name'] ?? '',
        ];
    }

    // ── other_services ──────────────────────────────────
    $stmt = $pdo->prepare("SELECT service_id, billing_name AS name, {$othCol} AS price
                            FROM other_services
                            WHERE billing_name LIKE ?
                            ORDER BY billing_name
                            LIMIT 20");
    $stmt->execute([$like]);
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $results[] = [
            'service_id'   => $row['service_id'],
            'name'         => $row['name'],
            'category'     => 'OTHER',
            'charge_type'  => 'OTHER',
            'price'        => (float) $row['price'],
        ];
    }

    // Sort by name and limit total
    usort($results, fn($a,$b) => strcmp($a['name'], $b['name']));
    $results = array_slice($results, 0, 30);

    echo json_encode(['success' => true, 'data' => $results]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
