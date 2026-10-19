<?php
/**
 * Somatiq HL7 Inbound Listener (Test Script)
 * This script runs in the background and listens for incoming results (ORU^R01) from Somatiq.
 * It will save the raw test results into a log file so you can view them before we build the database integration.
 *
 * Usage via Command Prompt:
 * C:\php82\php.exe C:\Websites\GM_HMS\modules\Radiology\Services\somatiq_hl7_listener.php
 */

set_time_limit(0);
ob_implicit_flush();

$host = '0.0.0.0'; // Listen on all network interfaces
$port = 2576;      // The port Somatiq will send results to (You must tell Somatiq to send to this port)
$logFile    = __DIR__ . '/../../../logs/somatiq_hl7_results.log';
$errLogFile = __DIR__ . '/../../../logs/somatiq_hl7_receiver_errors.log';

echo "=========================================================\n";
echo " Somatiq HL7 Result Listener Started\n";
echo " Listening on: tcp://$host:$port\n";
echo " Results will be logged to: " . realpath(dirname($logFile)) . "\\somatiq_hl7_results.log\n";
echo " Errors will be logged to:  " . realpath(dirname($logFile)) . "\\somatiq_hl7_receiver_errors.log\n";
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
        
        // Set socket timeout and buffer options
        stream_set_timeout($client, 5);
        
        $buffer = '';
        while (!feof($client)) {
            $data = fread($client, 4096);
            if ($data === false || $data === '') {
                $meta = stream_get_meta_data($client);
                if (!empty($meta['timed_out']) || feof($client)) break;
                continue;
            }
            $buffer .= $data;
            
            // MLLP end-of-block is 0x1C (FS). Stop reading immediately once received!
            if (strpos($buffer, "\x1C") !== false) {
                break;
            }
        }
        
        try {
            $byteCount = strlen($buffer);
            
            if ($byteCount > 0) {
                // Check if client attempted a TLS/SSL handshake (0x16 = TLS Handshake record)
                if (ord($buffer[0]) === 0x16) {
                    $warnMsg = "[" . date('Y-m-d H:i:s') . "] ⚠️ Warning: Received TLS handshake ($byteCount bytes) from $peerName. Somatiq is attempting TLS on this port.\n";
                    echo $warnMsg;
                    file_put_contents($errLogFile, $warnMsg, FILE_APPEND | LOCK_EX);
                } else {
                    // Strip MLLP framing characters (0x0B start, 0x1C end)
                    $cleanHl7 = trim($buffer, chr(0x0B) . chr(0x1C) . chr(0x0D) . "\n\r");
                    
                    echo "[" . date('Y-m-d H:i:s') . "] ✅ Received HL7 Message ($byteCount bytes) from $peerName.\n";
                    
                    // ── 1. IMMEDIATELY PARSE MSH AND SEND ACK TO PREVENT TIMEOUTS ──
                    $hl7Lines = explode("\n", str_replace("\r", "\n", $cleanHl7));
                    $incomingMsgControlId = '12345';
                    $senderApp = 'SOMATIQ_RIS';
                    $senderFac = 'SQ_TEST_DCM';
                    $ourApp    = 'GM';
                    $ourFac    = 'DEMO';

                    foreach ($hl7Lines as $line) {
                        $trimmedLine = trim($line);
                        if (strpos($trimmedLine, 'MSH|') === 0) {
                            $mshFields = explode('|', $trimmedLine);
                            if (!empty($mshFields[2])) $senderApp = $mshFields[2];
                            if (!empty($mshFields[3])) $senderFac = $mshFields[3];
                            if (!empty($mshFields[4])) $ourApp    = $mshFields[4];
                            if (!empty($mshFields[5])) $ourFac    = $mshFields[5];
                            if (!empty($mshFields[9])) $incomingMsgControlId = trim($mshFields[9]);
                            break;
                        }
                    }

                    // Generate standard HL7 ACK mirroring sending/receiving applications
                    $ackControlId = 'ACK' . date('YmdHis');
                    $ack = chr(0x0B) . "MSH|^~\\&|{$ourApp}|{$ourFac}|{$senderApp}|{$senderFac}|" . date('YmdHis') . "||ACK^R01^ACK|{$ackControlId}|P|2.3\rMSA|AA|{$incomingMsgControlId}\r" . chr(0x1C) . chr(0x0D);
                    
                    // Write ACK and immediately flush to network
                    fwrite($client, $ack);
                    fflush($client);
                    @stream_socket_shutdown($client, STREAM_SHUT_WR);
                    fclose($client);
                    $client = null; // Closed cleanly, prevents RST packets
                    
                    echo "[" . date('Y-m-d H:i:s') . "] ⚡ Instant ACK sent to Somatiq (ID: $incomingMsgControlId). Connection completed in <10ms.\n";
                    
                    // ── 2. LOG THE RECEIVED PAYLOAD ───────────────────────────────
                    $formattedHl7 = str_replace(["\r\n", "\r", "\n"], "\r\n", $cleanHl7);
                    $logEntry = "=========================================================\n";
                    $logEntry .= "Time Received: " . date('Y-m-d H:i:s') . "\n";
                    $logEntry .= "From: $peerName\n";
                    $logEntry .= "--- HL7 PAYLOAD ---\n";
                    $logEntry .= $formattedHl7 . "\n";
                    $logEntry .= "=========================================================\n\n";
                    file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
                    
                    // ── 3. ASYNCHRONOUSLY / SAFELY PROCESS & SAVE TO DATABASE ─────
                    try {
                        require_once __DIR__ . '/../../../core/Autoloader.php';
                        require_once __DIR__ . '/SomatiqResultProcessor.php';
                        $processor = new \GM_HMS\Modules\Radiology\Services\SomatiqResultProcessor();
                        $procResult = $processor->processHl7Message($cleanHl7);
                        if (!empty($procResult['success'])) {
                            echo "[" . date('Y-m-d H:i:s') . "] 💾 Saved to DB [{$procResult['table']}] for Order: {$procResult['order_id']} ({$procResult['patient_type']})\n";
                            if (!empty($procResult['report_url'])) echo "[" . date('Y-m-d H:i:s') . "] 📄 Report: {$procResult['report_url']}\n";
                            if (!empty($procResult['image_url']))  echo "[" . date('Y-m-d H:i:s') . "] 🖼️ Images: {$procResult['image_url']}\n";
                        } else {
                            echo "[" . date('Y-m-d H:i:s') . "] ⚠️ DB note: " . ($procResult['message'] ?? 'Skipped') . "\n";
                        }
                    } catch (\Throwable $dbEx) {
                        $dbErrMsg = "[" . date('Y-m-d H:i:s') . "] ❌ DB Processing Error: " . $dbEx->getMessage() . "\n";
                        echo $dbErrMsg;
                        file_put_contents($errLogFile, $dbErrMsg, FILE_APPEND | LOCK_EX);
                    }
                }
            } else {
                $infoMsg = "[" . date('Y-m-d H:i:s') . "] ℹ️ Connection from $peerName closed with 0 bytes (TCP probe / ping).\n";
                echo $infoMsg;
                file_put_contents($errLogFile, $infoMsg, FILE_APPEND | LOCK_EX);
            }
        } finally {
            if (is_resource($client)) {
                fclose($client);
            }
        }
    }
}
