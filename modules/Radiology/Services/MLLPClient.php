<?php
namespace GM_HMS\Modules\Radiology\Services;

/**
 * MLLPClient — High-Performance MLLP Protocol Client for Somatiq RIS.
 *
 * MLLP Framing:
 *   Start Block:  0x0B (VT)
 *   HL7 Message:  CR-delimited segments
 *   End Block:    0x1C (FS) + 0x0D (CR)
 *
 * Optimizations implemented:
 *   - TCP_NODELAY enabled (disables Nagle algorithm, eliminates 200-500ms latency)
 *   - Automatic retry policy on transient network drops
 *   - Flexible MLLP delimiter recognition (handles \x1C\x0D, \x1C\x0A, or \x1C)
 *   - Non-blocking flush & guaranteed socket cleanup
 */
class MLLPClient
{
    private string $host;
    private int    $port;
    private int    $timeout;
    private int    $maxRetries;

    public function __construct(string $host, int $port, int $timeout = 15, int $maxRetries = 2)
    {
        $this->host       = $host;
        $this->port       = $port;
        $this->timeout    = $timeout > 0 ? $timeout : 15;
        $this->maxRetries = $maxRetries;
    }

    /**
     * Send an HL7 message to Somatiq RIS and return the ACK response.
     *
     * @param  string $hl7Message Raw HL7 message (CR-delimited)
     * @return string Raw HL7 ACK payload (stripped of MLLP framing)
     * @throws \Exception On unrecoverable network or ACK timeout failure
     */
    public function send(string $hl7Message): string
    {
        $lastException = null;

        for ($attempt = 1; $attempt <= $this->maxRetries; $attempt++) {
            try {
                return $this->executeSendAndReceive($hl7Message);
            } catch (\Throwable $e) {
                $lastException = $e;
                if ($attempt < $this->maxRetries) {
                    // Short 300ms backoff before retrying
                    usleep(300000);
                }
            }
        }

        throw new \Exception(
            "MLLP transmission failed after {$this->maxRetries} attempt(s). Last error: " . 
            ($lastException ? $lastException->getMessage() : 'Unknown error')
        );
    }

    /**
     * Execute a single send-and-receive cycle
     */
    private function executeSendAndReceive(string $hl7Message): string
    {
        $isTls = (strpos($this->host, 'tls://') === 0 || strpos($this->host, 'ssl://') === 0);
        $cleanHost = preg_replace('/^(tls|ssl|tcp):\/\//', '', $this->host);

        // Build target URI for stream_socket_client
        $remoteUri = ($isTls ? 'tls://' : 'tcp://') . $cleanHost . ':' . $this->port;

        // Stream context for high-performance TCP/TLS
        $context = stream_context_create([
            'ssl' => [
                'verify_peer'       => false,
                'verify_peer_name'  => false,
                'allow_self_signed' => true,
                'SNI_enabled'       => true,
                'peer_name'         => $cleanHost,
            ],
            'socket' => [
                'tcp_nodelay'       => true, // Disables Nagle algorithm for immediate packet dispatch
                'so_keepalive'      => true,
            ]
        ]);

        $errno  = 0;
        $errstr = '';

        $socket = @stream_socket_client(
            $remoteUri,
            $errno,
            $errstr,
            $this->timeout,
            STREAM_CLIENT_CONNECT,
            $context
        );

        if ($socket === false) {
            throw new \Exception("Cannot connect to Somatiq RIS at {$remoteUri} ({$errno}: {$errstr})");
        }

        try {
            // Apply read/write timeout
            stream_set_timeout($socket, $this->timeout);

            // Wrap in standard MLLP envelope (0x0B ... 0x1C 0x0D)
            $framed = "\x0B" . $hl7Message . "\x1C\x0D";
            $length = strlen($framed);
            $sent   = 0;

            while ($sent < $length) {
                $written = fwrite($socket, substr($framed, $sent));
                if ($written === false || $written === 0) {
                    throw new \Exception('Socket closed prematurely while sending MLLP bytes.');
                }
                $sent += $written;
            }

            fflush($socket);

            // Read ACK response
            $response = '';
            while (!feof($socket)) {
                $chunk = fread($socket, 2048);
                if ($chunk === false) break;

                if ($chunk !== '') {
                    $response .= $chunk;

                    // MLLP end-of-block is 0x1C (FS). Stop immediately once found!
                    if (strpos($response, "\x1C") !== false) {
                        break;
                    }
                }

                $meta = stream_get_meta_data($socket);
                if (!empty($meta['timed_out'])) {
                    throw new \Exception("Timed out waiting for Somatiq ACK after {$this->timeout}s.");
                }
            }

            if (empty($response)) {
                throw new \Exception("Somatiq closed connection without returning an ACK.");
            }

            return $this->stripMLLP($response);
        } finally {
            if (is_resource($socket)) {
                @stream_socket_shutdown($socket, STREAM_SHUT_RDWR);
                fclose($socket);
            }
        }
    }

    /**
     * Remove MLLP start (VT 0x0B) and end (FS 0x1C + CR/LF) wrapper bytes.
     */
    private function stripMLLP(string $raw): string
    {
        // Strip leading VT (0x0B)
        $startPos = strpos($raw, "\x0B");
        if ($startPos !== false) {
            $raw = substr($raw, $startPos + 1);
        }

        // Strip trailing FS (0x1C) and subsequent characters
        $endPos = strpos($raw, "\x1C");
        if ($endPos !== false) {
            $raw = substr($raw, 0, $endPos);
        }

        return trim($raw, "\r\n\t ");
    }
}
