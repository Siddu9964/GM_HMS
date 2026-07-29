<?php
/**
 * API Router
 * 
 * Routes API requests to appropriate controllers
 * 
 * @package IPD_Management
 */

class Router
{
    private $routes = [];

    /**
     * Add route
     */
    public function addRoute($pattern, $controller)
    {
        $this->routes[$pattern] = $controller;
    }

    /**
     * Route request to controller
     */
    public function route($uri)
    {
        // Remove query string
        $uri = strtok($uri, '?');

        // Remove leading/trailing slashes
        $uri = trim($uri, '/');

        // Try to match route
        foreach ($this->routes as $pattern => $controller) {
            if (preg_match($pattern, $uri)) {
                return $controller;
            }
        }

        return null;
    }
}

// Define routes
$router = new Router();

// Admissions routes
$router->addRoute('#^api/admissions/?$#', 'AdmissionsController');

// Beds routes
$router->addRoute('#^api/beds/?$#', 'BedsController');

// Procedures routes
$router->addRoute('#^api/procedures/?$#', 'ProceduresController');

// Discharge routes
$router->addRoute('#^api/discharge/?$#', 'DischargeController');

// Visitors routes
$router->addRoute('#^api/visitors/?$#', 'VisitorsController');

// Payments routes
$router->addRoute('#^api/payments/?$#', 'PaymentsController');

// Charges routes
$router->addRoute('#^api/charges/?$#', 'ChargesController');

// IPD Billing routes
$router->addRoute('#^api/billing/?$#', 'IpdBillingController');

// Dashboard routes (including sub-routes for patients, doctors, and appointments)
$router->addRoute('#^api/dashboard(/patients|/doctors|/appointments)?/?$#', 'DashboardController');

// ── NEW IPD Billing Module Routes ──────────────────────────────
// Billing Master (ONE per admission)
$router->addRoute('#^api/ipd-billing-master/?$#', 'IpdBillingMasterController');

// Billing Items (charges)
$router->addRoute('#^api/ipd-billing-items/?$#', 'IpdBillingItemController');

// IPD Payments (always INSERT)
$router->addRoute('#^api/ipd-payment/?$#', 'IpdPaymentController');

// IPD Insurance
$router->addRoute('#^api/ipd-insurance/?$#', 'IpdInsuranceController');
// ───────────────────────────────────────────────────────────────

return $router;
