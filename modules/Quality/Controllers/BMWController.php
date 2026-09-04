<?php
namespace GM_HMS\Modules\Quality\Controllers;

use Exception;
use GM_HMS\Controllers\BaseController;
use GM_HMS\Modules\Quality\Services\BMWService;
use GM_HMS\Modules\Quality\Requests\BMWCollectionCreateRequest;
use GM_HMS\Modules\Quality\Requests\BMWDispatchRequest;
use GM_HMS\Modules\Quality\Resources\BMWResource;

class BMWController extends BaseController
{
    private $service;

    public function __construct()
    {
        parent::__construct();
        $this->service = new BMWService();
    }

    // ─────────────────────────────────────────────
    //  GET  /api/quality/bmw/records
    // ─────────────────────────────────────────────

    public function getRecords()
    {
        $this->restrictMethod('GET');
        $this->requireAuth();
        try {
            $filters = [
                'status'    => $_GET['status']    ?? null,
                'date_from' => $_GET['date_from'] ?? null,
                'date_to'   => $_GET['date_to']   ?? null,
                'location'  => $_GET['location']  ?? null,
                'page'      => (int)($_GET['page']  ?? 1),
                'limit'     => (int)($_GET['limit'] ?? 25)
            ];
            $result = $this->service->getRecords($filters);

            // Transform inner data rows
            if (!empty($result['data'])) {
                $result['data'] = BMWResource::collection($result['data']);
            }

            $this->respondSuccess($result);
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    // ─────────────────────────────────────────────
    //  GET  /api/quality/bmw/records/:id
    // ─────────────────────────────────────────────

    public function getRecordById($id)
    {
        $this->restrictMethod('GET');
        $this->requireAuth();
        try {
            $record = $this->service->getRecordById((int)$id);
            if (!$record) {
                $this->respondError('Waste record not found', 404);
                return;
            }
            $this->respondSuccess(BMWResource::toArray($record));
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    // ─────────────────────────────────────────────
    //  POST  /api/quality/bmw/records
    // ─────────────────────────────────────────────

    public function createCollection()
    {
        $this->restrictMethod('POST');
        $this->requireAuth();
        try {
            $body      = $this->getJsonInput();
            $validated = BMWCollectionCreateRequest::validate($body);
            $userId    = $_SESSION['user_id'] ?? 1;

            $newId = $this->service->logCollection($validated, $userId);
            $this->respondSuccess(['id' => $newId], 'Waste collection recorded successfully', 201);
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    // ─────────────────────────────────────────────
    //  PUT  /api/quality/bmw/records/:id
    // ─────────────────────────────────────────────

    public function updateCollection($id)
    {
        $this->restrictMethod('PUT');
        $this->requireAuth();
        try {
            $body = $this->getJsonInput();
            $this->service->updateCollection((int)$id, $body);
            $this->respondSuccess(null, 'Waste collection updated successfully');
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    // ─────────────────────────────────────────────
    //  POST  /api/quality/bmw/records/:id/dispatch
    // ─────────────────────────────────────────────

    public function dispatchRecord($id)
    {
        $this->restrictMethod('POST');
        $this->requireAuth();
        try {
            $body         = $this->getJsonInput();
            $validated    = BMWDispatchRequest::validate($body);
            $supervisorId = $_SESSION['user_id'] ?? null;

            $result = $this->service->processDispatch((int)$id, $validated, $supervisorId);
            $this->respondSuccess($result, 'Waste dispatched to vendor successfully');
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    // ─────────────────────────────────────────────
    //  DELETE  /api/quality/bmw/records/:id
    // ─────────────────────────────────────────────

    public function deleteRecord($id)
    {
        $this->restrictMethod('DELETE');
        $this->requireAuth();
        try {
            $this->service->deleteRecord((int)$id);
            $this->respondSuccess(null, 'Record deleted successfully');
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    // ─────────────────────────────────────────────
    //  GET  /api/quality/bmw/room-types
    // ─────────────────────────────────────────────

    public function getRoomTypes()
    {
        $this->restrictMethod('GET');
        $this->requireAuth();
        try {
            $roomTypes = $this->service->getRoomTypes();
            $this->respondSuccess($roomTypes);
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }
}
