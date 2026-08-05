<?php
$conn = new mysqli('localhost', 'root', '', 'hmsc_basaveshwranagara');
$result = $conn->query("DESCRIBE lab_test_parameters");
while ($row = $result->fetch_assoc()) {
    echo $row['Field'] . " - " . $row['Type'] . "\n";
}
