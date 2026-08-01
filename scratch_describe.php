<?php
$response = file_get_contents('http://localhost/GM_HMS/api/doctors');
echo "Doctors API Response:\n";
echo substr($response, 0, 1000);
