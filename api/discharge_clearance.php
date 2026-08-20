<?php
if (!headers_sent() && session_status() === PHP_SESSION_NONE) {
    session_start();
}
header('Content-Type: application/json');

require_once __DIR__ . '/../core/Autoloader.php';
use GM_HMS\Controllers\api\DischargeClearanceController;

$controller = new DischargeClearanceController();

$method = $_SERVER['REQUEST_METHOD'];

// Read JSON input if provided
$jsonInput = [];
$rawBody = file_get_contents('php://input');
if (!empty($rawBody)) {
    $decoded = json_decode($rawBody, true);
    if (is_array($decoded)) {
        $jsonInput = $decoded;
    }
}
$params = array_merge($_GET, $_POST, $jsonInput);
$action = $params['action'] ?? ($_GET['action'] ?? ($_POST['action'] ?? 'status'));


try {
    switch ($action) {
        case 'initiate':
            if ($method !== 'POST') {
                http_response_code(405);
                echo json_encode(['success' => false, 'message' => 'POST required for initiate']);
                exit;
            }
            $result = $controller->initiateClearance($params);
            echo json_encode($result);
            break;

        case 'status':
            $admissionId = $params['admission_id'] ?? '';
            $patientId   = $params['patient_id'] ?? '';
            $result = $controller->getClearanceStatus($admissionId, $patientId);
            echo json_encode($result);
            break;

        case 'pending_list':
        case 'pending-list':
        case 'list':
            $module = $params['module'] ?? ($_SESSION['role'] ?? 'admin');
            $limit  = (int)($params['limit'] ?? 20);
            $result = $controller->getPendingList($module, $limit);
            echo json_encode($result);
            break;

        case 'approve':
        case 'query':
        case 'update':
        case 'update_clearance':
        case 'update-clearance':
            if ($method !== 'POST') {
                http_response_code(405);
                echo json_encode(['success' => false, 'message' => 'POST required for update']);
                exit;
            }
            if ($action === 'approve' || $action === 'query') {
                $params['status_action'] = $action;
            }
            $result = $controller->updateDepartmentClearance($params);
            echo json_encode($result);
            break;

        case 'add_query':
        case 'add-query':
            if ($method !== 'POST') {
                http_response_code(405);
                echo json_encode(['success' => false, 'message' => 'POST required for add_query']);
                exit;
            }
            $result = $controller->addQuery($params);
            echo json_encode($result);
            break;

        case 'resolve_query':
        case 'resolve-query':
            if ($method !== 'POST') {
                http_response_code(405);
                echo json_encode(['success' => false, 'message' => 'POST required for resolve_query']);
                exit;
            }
            $queryId = (int)($params['query_id'] ?? 0);
            $resolvedBy = $params['user_name'] ?? ($_SESSION['full_name'] ?? ($_SESSION['username'] ?? 'Staff'));
            $result = $controller->resolveQuery($queryId, $resolvedBy);
            echo json_encode($result);
            break;

        case 'admin_confirm':
        case 'admin-confirm':
            if ($method !== 'POST') {
                http_response_code(405);
                echo json_encode(['success' => false, 'message' => 'POST required for admin_confirm']);
                exit;
            }
            $result = $controller->adminFinalConfirm($params);
            echo json_encode($result);
            break;

        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action specified: ' . htmlspecialchars($action)]);
            break;
    }
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server Error: ' . $e->getMessage()]);
}
