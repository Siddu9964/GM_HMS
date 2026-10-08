<?php
namespace GM_HMS\Modules\Radiology\Services;

/**
 * MLLPClient — Sends HL7 messages using the MLLP (Minimal Lower Layer Protocol).
 *
 * MLLP framing:
 *   Start Block:  0x0B (VT / Vertical Tab)
 *   HL7 message   (CR-delimited segments)
 *   End Block:    0x1C (FS / File Separator) + 0x0D (CR)
 *
 * Used to communicate with Somatiq RIS over TLS/TCP on port 2576.
 */
class MLLPClient
{
    private string $host;
    private int    $port;
    private int    $timeout;

    public function __construct(string $host, int $port, int $timeout = 30)
    {
        $this->host    = $host;
        $this->port    = $port;
        $this->timeout = $timeout;
    }

    /**
     * Send an HL7 message to Somatiq RIS and return the ACK string.
     *
     * @param  string $hl7Message  The raw HL7 message (CR-delimited segments)
     * @return string              The HL7 ACK response from Somatiq (without MLLP framing)
     * @throws \Exception          On connection or write failure
     */
    public function send(string $hl7Message): string
    {
        $errno  = 0;
        $errstr = '';

        /*
        |--------------------------------------------------------------------------
        | Open TCP/TLS connection
        |--------------------------------------------------------------------------
        */
        $socket = @fsockopen(
            $this->host,
            $this->port,
            $errno,
            $errstr,
            $this->timeout
        );

        if ($socket === false) {
            throw new \Exception(
                "Cannot connect to Somatiq RIS at {$this->host}:{$this->port}. " .
                "Error {$errno}: {$errstr}"
            );
        }

        stream_set_timeout($socket, $this->timeout);

        /*
        |--------------------------------------------------------------------------
        | Wrap message in MLLP frame
        |--------------------------------------------------------------------------
        */
        $framed = "\x0B" . $hl7Message . "\x1C\x0D";
        $length = strlen($framed);
        $sent   = 0;

        while ($sent < $length) {
            $written = fwrite($socket, substr($framed, $sent));
            if ($written === false || $written === 0) {
                fclose($socket);
                throw new \Exception('Failed to write HL7 bytes to Somatiq socket.');
            }
            $sent += $written;
        }

        fflush($socket);

        /*
        |--------------------------------------------------------------------------
        | Read ACK response
        |--------------------------------------------------------------------------
        */
        $response = '';

        while (!feof($socket)) {
            $chunk = fread($socket, 4096);
            if ($chunk === false) break;

            if ($chunk !== '') {
                $response .= $chunk;

                // Stop as soon as a complete MLLP frame is received
                if (strpos($response, "\x1C\x0D") !== false) break;
            }

            $meta = stream_get_meta_data($socket);
            if ($meta['timed_out']) break;
        }

        fclose($socket);

        if ($response === '') {
            throw new \Exception(
                "No ACK received from Somatiq within {$this->timeout} seconds."
            );
        }

        return $this->stripMLLP($response);
    }

    /*
    |--------------------------------------------------------------------------
    | Private helpers
    |--------------------------------------------------------------------------
    */

    /**
     * Remove MLLP start (VT 0x0B) and end (FS 0x1C + CR 0x0D) wrapper bytes.
     */
    private function stripMLLP(string $raw): string
    {
        // Remove leading VT (Start Block)
        if (isset($raw[0]) && ord($raw[0]) === 0x0B) {
            $raw = substr($raw, 1);
        }

        // Remove trailing FS + CR (End Block)
        $pos = strpos($raw, "\x1C\x0D");
        if ($pos !== false) {
            $raw = substr($raw, 0, $pos);
        }

        return trim($raw, "\r\n");
    }
}
