<?php
/**
 * Somatiq HL7 Inbound Listener (Test Script)
 * This script runs in the background and listens for incoming results (ORU^R01) from Somatiq.
 * It will save the raw test results into a log file so you can view them before we build the database integration.
 *
 * Usage via Command Prompt:
 * php D:\xampp\htdocs\GM_HMS\modules\Radiology\Services\somatiq_hl7_listener.php
 */

set_time_limit(0);
ob_implicit_flush();

$host = '0.0.0.0'; // Listen on all network interfaces
$port = 2577;      // The port Somatiq will send results to (You must tell Somatiq to send to this port)
$logFile = __DIR__ . '/../../../logs/somatiq_hl7_results.log';

echo "=========================================================\n";
echo " Somatiq HL7 Result Listener Started\n";
echo " Listening on: tcp://$host:$port\n";
echo " Results will be logged to: " . realpath(dirname($logFile)) . "\\somatiq_hl7_results.log\n";
echo "=========================================================\n\n";

// Create TCP socket
$socket = stream_socket_server("tcp://$host:$port", $errno, $errstr);
if (!$socket) {
    die("Error starting listener: $errstr ($errno)\n");
}

while (true) {
    $client = @stream_socket_accept($socket, -1);
    if ($client) {
        $peerName = stream_socket_get_name($client, true);
        echo "[" . date('Y-m-d H:i:s') . "] Connection accepted from $peerName\n";
        
        $buffer = '';
        while (!feof($client)) {
            $data = fread($client, 1024);
            if ($data === false || $data === '') break;
            $buffer .= $data;
            
            // MLLP messages end with 0x1C 0x0D (FS CR)
            if (strpos($buffer, chr(0x1C) . chr(0x0D)) !== false) {
                break;
            }
        }
        
        if (!empty($buffer)) {
            // Strip MLLP framing characters (0x0B at start, 0x1C 0x0D at end)
            $cleanHl7 = trim($buffer, chr(0x0B) . chr(0x1C) . chr(0x0D));
            
            echo "[" . date('Y-m-d H:i:s') . "] Received Result Message! Saving to log...\n";
            
            // Log the result beautifully
            $logEntry = "=========================================================\n";
            $logEntry .= "Time Received: " . date('Y-m-d H:i:s') . "\n";
            $logEntry .= "From: $peerName\n";
            $logEntry .= "--- HL7 PAYLOAD ---\n";
            $logEntry .= $cleanHl7 . "\n";
            $logEntry .= "=========================================================\n\n";
            
            file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
            
            // Generate a simple ACK to tell Somatiq we received it successfully
            $ack = chr(0x0B) . "MSH|^~\\&|GM|DEMO|PACS||" . date('YmdHis') . "||ACK|12345|P|2.3\rMSA|AA|12345\r" . chr(0x1C) . chr(0x0D);
            fwrite($client, $ack);
            
            echo "ACK sent to Somatiq. Waiting for next result...\n";
        }
        
        fclose($client);
    }
}
