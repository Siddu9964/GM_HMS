<?php
/**
 * Somatiq HL7 Listener (HTTP POST)
 * 
 * This script receives HL7 ORU^R01 messages via HTTP POST, 
 * extracts the Base64 PDF report, saves it, and responds with an MSA|AA ACK.
 */

// 1. Get raw POST data (The HL7 message body)
$hl7_data = file_get_contents('php://input');

if (empty($hl7_data)) {
    http_response_code(400);
    die("No HL7 data received.");
}

// Ensure the reports directory exists
$reports_dir = __DIR__ . '/../reports';
if (!is_dir($reports_dir)) {
    mkdir($reports_dir, 0777, true);
}

// 2. Parse the HL7 message
// HL7 segments can be separated by \r or \n
$lines = explode("\r", str_replace("\n", "\r", $hl7_data));

$message_control_id = "";
$order_number = "UNKNOWN";
$base64_pdf = "";

foreach ($lines as $line) {
    // Skip empty lines
    if (trim($line) === '') continue;

    $fields = explode("|", $line);
    $segment_name = $fields[0];

    // Get Message Control ID from MSH for the ACK
    if ($segment_name === "MSH") {
        $message_control_id = isset($fields[9]) ? $fields[9] : "";
    }
    
    // Get Order Number from OBR (to name the PDF file)
    if ($segment_name === "OBR") {
        $order_number = isset($fields[2]) ? $fields[2] : "UNKNOWN";
    }
    
    // Get Base64 PDF from OBX
    if ($segment_name === "OBX") {
        // OBX-2 must be 'ED' for Encapsulated Data
        if (isset($fields[2]) && $fields[2] === "ED") {
            // OBX-5 contains: application/pdf^Base64^<actual_data>
            $obx5 = isset($fields[5]) ? $fields[5] : "";
            $obx5_parts = explode("^", $obx5);
            
            if (count($obx5_parts) >= 3 && $obx5_parts[1] === "Base64") {
                $base64_pdf = $obx5_parts[2];
            }
        }
    }
}

// 3. Save the PDF if we found one
if (!empty($base64_pdf)) {
    $pdf_decoded = base64_decode($base64_pdf);
    // Sanitize order number for filename
    $safe_order_num = preg_replace('/[^A-Za-z0-9_-]/', '', $order_number);
    $filename = $reports_dir . "/somatiq_report_" . $safe_order_num . "_" . time() . ".pdf";
    
    file_put_contents($filename, $pdf_decoded);
    
    // TODO: Add database logic here to link the report to the patient's record in GM_HMS
}

// 4. Generate the ACK message
$date_time = date('YmdHis');

// Replace YOUR_VENDOR_CODE and YOUR_CENTRE_CODE with the values provided by Somatiq
$vendor_code = "YOUR_VENDOR_CODE"; 
$centre_code = "YOUR_CENTRE_CODE";

$ack_message = "MSH|^~\\&|SOMATIQ_RIS||$vendor_code|$centre_code|$date_time||ACK^R01^ACK|$message_control_id|P|2.3\r" .
               "MSA|AA|$message_control_id\r";

// 5. Send the ACK back as the HTTP response
header("Content-Type: application/hl7-v2; charset=utf-8");
echo $ack_message;
