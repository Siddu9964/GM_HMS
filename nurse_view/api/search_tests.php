<?php
/**
 * API to search tests (Lab, Radiology, Other)
 */
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../../core/Autoloader.php';
use GM_HMS\Models\NurseTestsModel;

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$query = $_GET['q'] ?? '';
$type = $_GET['type'] ?? 'all'; // lab, radiology, other, all

if (empty($query)) {
    echo json_encode(['success' => true, 'data' => []]);
    exit;
}

try {
    $model = new NurseTestsModel();
    $results = [];
    
    if ($type === 'lab') {
        $results = $model->searchLabTests($query);
    } elseif ($type === 'radiology') {
        $results = $model->searchRadiology($query);
    } elseif ($type === 'other') {
        $results = $model->searchOther($query);
    } else {
        $results = $model->searchAllTests($query);
    }
    
    echo json_encode(['success' => true, 'data' => $results]);
} catch (\Throwable $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
