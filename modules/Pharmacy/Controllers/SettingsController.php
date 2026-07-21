<?php
namespace GM_HMS\Modules\Pharmacy\Controllers;

use Exception;
use GM_HMS\Controllers\BaseController;
use GM_HMS\Modules\Pharmacy\Repositories\SettingsRepository;

/**
 * SettingsController
 * Handles pharmacy configuration
 */
class SettingsController extends BaseController {
    private $repository;

    public function __construct() {
        parent::__construct();
        $this->repository = new SettingsRepository();
    }

    /** GET /api/pharmacy/settings */
    public function index(): void {
        $this->restrictMethod('GET');
        $this->requireAuth();
        try {
            $settings = $this->repository->getAll();
            $this->respondSuccess($settings);
        } catch (Exception $e) { $this->handleException($e); }
    }

    /** POST /api/pharmacy/settings */
    public function save(): void {
        $this->restrictMethod('POST');
        $this->requireAuth();
        try {
            $data = $this->getJsonInput();
            foreach ($data as $key => $value) {
                $this->repository->set($key, $value);
            }
            $this->respondSuccess(null, "Settings saved successfully.");
        } catch (Exception $e) { $this->handleException($e); }
    }
}
