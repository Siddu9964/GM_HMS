<?php
require_once __DIR__ . '/core/Autoloader.php';
$_SERVER['HTTP_X_HOSPITAL_BRANCH'] = 'basaveshwaranagar';
$db = new \GM_HMS\Database\SecureDatabase();
$model = new \GM_HMS\Models\AppointmentModel($db);
$doctors = $model->getDoctorsByDepartment(' GENERAL SURGERY');
print_r($doctors);
