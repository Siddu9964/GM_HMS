<?php
namespace GM_HMS\Modules\Radiology\Controllers;

use Exception;
use Throwable;
use GM_HMS\Controllers\BaseController;
use GM_HMS\Modules\Radiology\Services\RadiologyService;

class RadiologyController extends BaseController
{
    private $service;

    public function __construct()
    {
        parent::__construct();
        $this->service = new RadiologyService();
    }

    /**
     * GET /api/radiology/services
     */
    public function getServices()
    {
        $this->restrictMethod('GET');
        $this->requireAuth();
        try {
            $modality = $_GET['modality'] ?? null;
            $data = $this->service->getAllServices($modality);
            $this->respondSuccess($data);
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    /**
     * POST /api/radiology/services
     */
    public function createService()
    {
        $this->restrictMethod('POST');
        $this->requireAuth();
        try {
            $data = $this->getJsonInput();
            $result = $this->service->createService($data);
            if ($result) {
                $this->respondSuccess($result, 'Radiology procedure created successfully');
            } else {
                $this->respondBadRequest('Failed to create radiology procedure');
            }
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    /**
     * PUT /api/radiology/services/:id
     */
    public function updateService($id)
    {
        $this->restrictMethod('PUT');
        $this->requireAuth();
        try {
            $data = $this->getJsonInput();
            $result = $this->service->updateService($id, $data);
            if ($result) {
                $this->respondSuccess(null, 'Radiology procedure updated successfully');
            } else {
                $this->respondNotFound('Procedure not found or update failed');
            }
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    /**
     * DELETE /api/radiology/services/:id
     */
    public function deleteService($id)
    {
        $this->restrictMethod('DELETE');
        $this->requireAuth();
        try {
            $result = $this->service->deleteService($id);
            if ($result) {
                $this->respondSuccess(null, 'Procedure deleted successfully');
            } else {
                $this->respondNotFound('Procedure not found or delete failed');
            }
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    /**
     * GET /api/radiology/dashboard
     */
    public function getDashboard()
    {
        $this->restrictMethod('GET');
        $this->requireAuth();
        try {
            $stats = $this->service->getDashboardStats();
            $this->respondSuccess($stats);
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    /**
     * GET /api/radiology/orders
     */
    public function getOrders()
    {
        $this->restrictMethod('GET');
        $this->requireAuth();
        try {
            $date     = $_GET['date']     ?? date('Y-m-d');
            $status   = $_GET['status']   ?? '';
            $priority = $_GET['priority'] ?? '';
            $search   = $_GET['search']   ?? '';
            $modality = $_GET['modality'] ?? '';
            $all      = $_GET['all']      ?? '0';

            $orders = $this->service->getOrders($all, $date, $status, $priority, $search, $modality);
            $this->respondSuccess($orders);
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    /**
     * POST /api/radiology/orders
     */
    public function createOrder()
    {
        $this->restrictMethod('POST');
        $this->requireAuth();
        try {
            $data = $this->getJsonInput();
            $order = $this->service->createOrder($data);
            $this->respondSuccess($order, 'Radiology order created successfully');
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    /**
     * GET /api/radiology/orders/:id
     */
    public function getOrder($id)
    {
        $this->restrictMethod('GET');
        $this->requireAuth();
        try {
            $order = $this->service->getOrderById($id);
            $this->respondSuccess($order);
        } catch (Exception $e) {
            $this->respondNotFound($e->getMessage());
        }
    }

    /**
     * PUT /api/radiology/orders/:id/status
     */
    public function updateOrderStatus($orderId)
    {
        $this->restrictMethod('PUT');
        $this->requireAuth();
        try {
            $input = $this->getJsonInput();
            $status = $input['status'] ?? '';
            $allowed = ['Ordered', 'In Progress', 'Completed', 'Reported'];

            if (!in_array($status, $allowed)) {
                $this->respondBadRequest('Invalid status. Allowed: ' . implode(', ', $allowed));
            }

            $this->service->updateOrderStatus($orderId, $status);
            $this->respondSuccess(null, 'Order status updated successfully');
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    /**
     * GET /api/radiology/ipd-orders
     */
    public function getIpdOrders()
    {
        $this->restrictMethod('GET');
        $this->requireAuth();
        try {
            $all = $_GET['all'] ?? '0';
            $date = $_GET['date'] ?? date('Y-m-d');
            $statusFilter = $_GET['status'] ?? 'all';
            $search = $_GET['search'] ?? '';
            $modality = $_GET['modality'] ?? '';
            $orders = $this->service->getIpdOrders($all, $date, $statusFilter, $search, $modality);
            $this->respondSuccess($orders);
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    /**
     * PUT /api/radiology/ipd-orders/:id/status
     */
    public function updateIpdOrderStatus($orderId)
    {
        $this->restrictMethod('PUT');
        $this->requireAuth();
        try {
            $data = $this->getJsonInput();
            if (!isset($data['status'])) {
                $this->respondError("Status is required", 400);
            }
            $this->service->updateIpdOrderStatus($orderId, $data['status']);
            $this->respondSuccess(null, "Status updated successfully");
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    /**
     * GET /api/radiology/orders/:id/result
     */
    public function getResult($orderId)
    {
        $this->restrictMethod('GET');
        $this->requireAuth();
        try {
            $result = $this->service->getResult($orderId);
            $this->respondSuccess($result);
        } catch (Exception $e) {
            $this->respondNotFound($e->getMessage());
        }
    }

    /**
     * POST /api/radiology/orders/:id/result
     */
    public function saveResult($orderId)
    {
        $this->restrictMethod('POST');
        $this->requireAuth();
        try {
            $data = [];
            if (isset($_POST['result_data']) || isset($_POST['findings']) || isset($_POST['status'])) {
                $data['result_data'] = $_POST['result_data'] ?? null;
                $data['status'] = $_POST['status'] ?? 'Reported';
                $data['test_name'] = $_POST['test_name'] ?? null;
                $data['patient_id'] = $_POST['patient_id'] ?? null;

                if (!empty($_POST['result_data'])) {
                    $parsed = is_array($_POST['result_data']) ? $_POST['result_data'] : json_decode($_POST['result_data'], true);
                    if (is_array($parsed)) {
                        $data['technique'] = $parsed['technique'] ?? null;
                        $data['clinical_history'] = $parsed['clinical_history'] ?? null;
                        $data['findings'] = $parsed['findings'] ?? null;
                        $data['impression'] = $parsed['impression'] ?? null;
                        $data['modality'] = $parsed['modality'] ?? null;
                    }
                }
            } else {
                $data = $this->getJsonInput();
                if (isset($data['result_data']) && is_string($data['result_data'])) {
                    $parsed = json_decode($data['result_data'], true);
                    if (is_array($parsed)) {
                        $data['technique'] = $data['technique'] ?? ($parsed['technique'] ?? null);
                        $data['clinical_history'] = $data['clinical_history'] ?? ($parsed['clinical_history'] ?? null);
                        $data['findings'] = $data['findings'] ?? ($parsed['findings'] ?? null);
                        $data['impression'] = $data['impression'] ?? ($parsed['impression'] ?? null);
                        $data['modality'] = $data['modality'] ?? ($parsed['modality'] ?? null);
                    }
                }
            }

            // Direct field overrides if present in POST
            if (isset($_POST['technique'])) $data['technique'] = $_POST['technique'];
            if (isset($_POST['clinical_history'])) $data['clinical_history'] = $_POST['clinical_history'];
            if (isset($_POST['findings'])) $data['findings'] = $_POST['findings'];
            if (isset($_POST['impression'])) $data['impression'] = $_POST['impression'];
            if (isset($_POST['modality'])) $data['modality'] = $_POST['modality'];
            if (isset($_POST['patient_id'])) $data['patient_id'] = $_POST['patient_id'];

            $file = $_FILES['report_file'] ?? null;
            $result = $this->service->saveResult($orderId, $data, $file);
            $this->respondSuccess($result, 'Radiology report saved successfully');
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    /**
     * GET /api/radiology/patients/:id/previous-results
     */
    public function getPreviousResults($patientId)
    {
        $this->restrictMethod('GET');
        $this->requireAuth();
        try {
            $result = $this->service->getPatientPreviousResults($patientId);
            $this->respondSuccess($result);
        } catch (Exception $e) {
            $this->respondNotFound($e->getMessage());
        }
    }

    /**
     * POST /api/radiology/templates/auto-generate
     */
    public function autoGenerateTemplate()
    {
        $this->restrictMethod('POST');
        $this->requireAuth();
        try {
            $body = $this->getJsonInput();
            $examName = trim($body['exam_name'] ?? '');
            
            if (empty($examName)) {
                $this->respondBadRequest('Examination name is required');
            }

            // Standard clinical fallbacks based on examination type
            $upper = strtoupper($examName);
            $template = [
                'technique'        => 'Standard examination protocol performed.',
                'clinical_history' => 'Evaluation as requested by treating physician.',
                'findings'         => "Visualized structures demonstrate normal anatomical morphology and density. No acute focal lesion, fracture, or abnormal mass effect identified.",
                'impression'       => "No acute radiological abnormality identified."
            ];

            if (strpos($upper, 'CHEST') !== false || strpos($upper, 'X-RAY') !== false) {
                $template['technique'] = "Standard Posteroanterior (PA) view of the chest was acquired in full inspiration.";
                $template['findings'] = "Bilateral lung fields appear clear without focal consolidation, pneumothorax, or pleural effusion.\nCardiothoracic ratio is within normal limits.\nHilar and mediastinal contours appear normal.\nBilateral diaphragmatic domes and costophrenic angles are well visualized and sharp.\nBony cage and visual soft tissues are unremarkable.";
                $template['impression'] = "Normal Chest Radiograph. No acute cardiopulmonary pathology seen.";
            } elseif (strpos($upper, 'CT') !== false && strpos($upper, 'BRAIN') !== false) {
                $template['technique'] = "Non-contrast volumetric axial CT acquisition of the head from base of skull to vertex.";
                $template['findings'] = "Cerebral hemispheres demonstrate symmetric attenuation without evidence of acute territorial infarction or intracranial hemorrhage.\nVentricles, sulci, and basal cisterns are within normal limits for age.\nNo midline shift or mass effect identified.\nPosterior fossa structures and cerebellum are unremarkable.\nVisualized bony calvarium and paranasal sinuses are clear.";
                $template['impression'] = "Normal non-contrast CT study of the brain. No acute intracranial hemorrhage or mass effect.";
            } elseif (strpos($upper, 'ULTRA') !== false || strpos($upper, 'USG') !== false || strpos($upper, 'ABDOMEN') !== false) {
                $template['technique'] = "Real-time B-mode ultrasound examination of the abdomen and pelvis using convex transducer.";
                $template['findings'] = "Liver: Normal in size and parenchymal echogenicity. No focal lesion or intrahepatic biliary radicle dilatation.\nGallbladder: Well distended, thin-walled, lumen is clear without calculi or sludge.\nCBD and Portal Vein: Normal in caliber.\nPancreas: Visualized portions appear normal in size and echotexture.\nSpleen: Normal size, homogeneous parenchyma.\nBilateral Kidneys: Normal in size, shape, and cortical thickness with preserved corticomedullary differentiation. No calculus, hydronephrosis, or mass.\nUrinary Bladder: Normal distension, wall thickness normal.\nNo free fluid or ascites seen in abdomen or pelvis.";
                $template['impression'] = "Normal Ultrasound examination of the Abdomen and Pelvis.";
            } elseif (strpos($upper, 'KNEE') !== false || strpos($upper, 'HIP') !== false || strpos($upper, 'SPINE') !== false) {
                $template['technique'] = "Anteroposterior (AP) and lateral orthogonal projections acquired.";
                $template['findings'] = "Alignment and joint spaces are well preserved.\nNo evidence of fracture, dislocation, or destructive bony lesion.\nBone density is appropriate for age.\nVisualized periarticular soft tissues are unremarkable.";
                $template['impression'] = "No acute fracture or dislocation identified.";
            }

            $this->respondSuccess($template);
        } catch (Throwable $e) {
            $this->handleException($e);
        }
    }
}
