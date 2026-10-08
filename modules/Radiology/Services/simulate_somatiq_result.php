<?php
/**
 * Somatiq Result Simulator
 * This script pretends to be the Somatiq RIS. It sends a fake CT Brain Result (ORU^R01) 
 * to your GM_HMS listener on port 2577, just so you can see what it looks like!
 */

$host = '127.0.0.1';
$port = 2577;

// A standard HL7 ORU^R01 message containing a CT Brain Report
$hl7 = "MSH|^~\\&|PACS|DEMO|GM|DEMO|" . date('YmdHis') . "||ORU^R01|MSG99999|P|2.3\r" .
       "PID|1||PID-20260910-045||AKSHAY KUMAR SAHOO||19770101|M\r" .
       "OBR|1|OPB-20261007-0004||RDS171^CT SCAN OF BRAIN|||" . date('YmdHis') . "|||||||||||||||F\r" .
       "OBX|1|TX|REPORT^Findings||The brain parenchyma appears normal. No evidence of acute intracranial hemorrhage, mass effect, or midline shift. Ventricles and sulci are age-appropriate.||||||F\r" .
       "OBX|2|TX|IMPRESSION^Impression||Normal CT scan of the brain.||||||F\r";

// Wrap in MLLP framing characters
$mllpMessage = chr(0x0B) . $hl7 . chr(0x1C) . chr(0x0D);

echo "Connecting to GM_HMS Listener on $host:$port...\n";
$socket = @fsockopen($host, $port, $errno, $errstr, 10);

if (!$socket) {
    die("Failed to connect! Is the listener running?\nError: $errstr ($errno)\n");
}

echo "Connected! Sending Test Result (ORU^R01)...\n";
fwrite($socket, $mllpMessage);

echo "Waiting for ACK from GM_HMS...\n";
$response = fread($socket, 1024);
echo "Received: " . trim($response, chr(0x0B) . chr(0x1C) . chr(0x0D)) . "\n";

fclose($socket);
echo "Done!\n";
