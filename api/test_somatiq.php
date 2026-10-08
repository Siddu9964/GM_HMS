<?php
// test_somatiq.php - A script to simulate Somatiq sending a report to your listener

// 1. A tiny valid PDF encoded in Base64
$dummy_pdf_base64 = "JVBERi0xLjQKJcOkw7zDtsOfCjIgMCBvYmoKPDwvTGVuZ3RoIDMgMCBSL0ZpbHRlci9GbGF0ZURlY29kZT4+CnN0cmVhbQp4nDPQM1Qo5ypUMFAwALJMLYyMFEwVXBLLUlOUclLzEikAAAAA//8DAC1WB8UKZW5kc3RyZWFtCmVuZG9iagozIDAgb2JqCjQ0CmVuZG9iagoxIDAgb2JqCjw8L1R5cGUvUGFnZS9NZWRpYUJveFswIDAgNTk1IDg0Ml0vUmVzb3VyY2VzPDwvWE9iamVjdDw8L0ltMFswIDAgMCBvYmpdCj4+Pj4vQ29udGVudHMgMiAwIFIvUGFyZW50IDQgMCBSPj4KZW5kb2JqCjQgMCBvYmoKPDwvVHlwZS9QYWdlcy9Db3VudCAxL0tpZHNbMSAwIFJdPj4KZW5kb2JqCjUgMCBvYmoKPDwvVHlwZS9DYXRhbG9nL1BhZ2VzIDQgMCBSPj4KZW5kb2JqCjYgMCBvYmoKPDwvUHJvZHVjZXIoZlBERiAxLjg0KS9DcmVhdGlvbkRhdGUoRDoyMDI2MTAwODEyMDAwMCk+PgplbmRvYmoKeHJlZgowIDcKMDAwMDAwMDAwMCA2NTUzNSBmIAowMDAwMDAwMjAxIDAwMDAwIG4gCjAwMDAwMDAwMjIgMDAwMDAgbiAKMDAwMDAwMDE4MiAwMDAwMCBuIAowMDAwMDAwMzM1IDAwMDAwIG4gCjAwMDAwMDAwMzkyIDAwMDAwIG4gCjAwMDAwMDAwNDQyIDAwMDAwIG4gCnRyYWlsZXIKPDwvU2l6ZSA3L1Jvb3QgNSAwIFIvSW5mbyA2IDAgUj4+CnN0YXJ0eHJlZgo1MzQKJSVFT0YK";

// 2. Build the fake HL7 message matching Somatiq's format
$hl7_payload = "MSH|^~\\&|SOMATIQ_RIS|<CLIENT_AE>|TEST_VENDOR|TEST_CENTRE|20261008150500||ORU^R01|MSG999999|P|2.3\r" .
               "PID|1||PAT-0001||JOHN DOE||19900101|M\r" .
               "OBR|1|ORD-TEST-1001|<CLIENT_AE>|ORD-TEST-1001^MRI Lumbar\r" .
               "OBX|1|ED|ORD-TEST-1001^MRI Lumbar||application/pdf^Base64^" . $dummy_pdf_base64 . "||||||F|||20261008150455||Dr TEST";

// 3. Set the URL to your local listener
$listener_url = "http://localhost/GM_HMS/api/somatiq_listener.php";

echo "Sending Fake HL7 Data to: " . $listener_url . "\n\n";

// 4. Send the HTTP POST request using cURL
$ch = curl_init($listener_url);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, $hl7_payload);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: text/plain'
]);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

// 5. Output the results
echo "HTTP Status Code: " . $http_code . "\n\n";
echo "Response from Listener (ACK):\n";
echo $response . "\n\n";

if ($http_code == 200 && strpos($response, 'MSA|AA') !== false) {
    echo "✅ TEST SUCCESSFUL! The listener received the message and returned an AA (Accept Acknowledgment).\n";
    echo "Check your GM_HMS/reports/ folder for the PDF!\n";
} else {
    echo "❌ TEST FAILED. Check the output above for errors.\n";
}
