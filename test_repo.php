<?php

require_once 'config/database.php';
require_once 'modules/Laboratory/Repositories/LaboratoryRepository.php';

use Modules\Laboratory\Repositories\LaboratoryRepository;
use Config\Database;

$db = new Database();
$repo = new LaboratoryRepository($db);

$serviceId = 'SRV-TEST-999';

// Fake parameters
$params = [
    [
        'parameter_name' => 'Complete Blood Count',
        'unit' => 'cells/mcL',
        'normal_range' => '4000-11000',
        'normal_range_male' => '5000-10000',
        'normal_range_female' => '4500-11000',
        'normal_range_child' => '5000-12000',
        'normal_range_newborn' => '9000-30000',
        'normal_range_Infant(29 days–12 months)' => '6000-17000',
        'normal_range_toddler(1–3 years)' => '5000-15000',
        'normal_range_preschool_child(4–5 years)' => '5000-14500',
        'normal_range_school_child(6–12 years)' => '4500-13500',
        'normal_range_adolescent(13–17 years)' => '4500-13000',
        'normal_range_adult(18–59 years)' => '4000-11000',
        'normal_range_elderly(60–74 years)' => '3500-10500',
        'normal_range_senior_elderly(75+ years)' => '3000-10000',
        'min_age_days' => 0,
        'max_age_days' => 36500
    ]
];

echo "Saving parameters...\n";
$repo->saveTestParameters($serviceId, $params);
echo "Saved successfully.\n";

echo "Retrieving parameters...\n";
$fetched = $repo->getLabTestParameters($serviceId);
echo json_encode($fetched, JSON_PRETTY_PRINT);

