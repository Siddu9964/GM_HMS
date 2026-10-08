<?php
/**
 * Somatiq HL7 / RIS Integration Configuration
 * ─────────────────────────────────────────────────────────────────────────
 *
 * MSH-3  sending_application  = VENDOR_CODE  (provided by Somatiq)
 * MSH-4  sending_facility     = CENTRE_CODE  (provided by Somatiq)
 * MSH-5  receiving_application = 'PACS'      (always 'PACS' for Somatiq)
 *
 * ⚠️  IMPORTANT: Change 'DEMO' to your real CENTRE_CODE before going live!
 *     Contact Somatiq support to get your production CENTRE_CODE.
 * ─────────────────────────────────────────────────────────────────────────
 */
return [

    /*
    |--------------------------------------------------------------------------
    | Somatiq RIS Server Connection
    |--------------------------------------------------------------------------
    | host: Use 'tls://' prefix because Somatiq requires TLS encryption.
    | port: 2576 is Somatiq's standard MLLP port.
    */
    'host'                  => 'tls://hl7.ris.somatiq.ai',
    'port'                  => 2576,
    'timeout'               => 30,      // seconds — increase if you see ACK timeouts

    /*
    |--------------------------------------------------------------------------
    | HL7 Version
    |--------------------------------------------------------------------------
    */
    'hl7_version'           => '2.3',

    /*
    |--------------------------------------------------------------------------
    | Your Hospital Identity
    |--------------------------------------------------------------------------
    | Somatiq will give you these two codes when they set up your account.
    | sending_application = VENDOR_CODE  (e.g., 'GM' for your hospital)
    | sending_facility    = CENTRE_CODE  (e.g., 'DEMO' for testing)
    */
    'sending_application'   => 'GM',       // MSH-3: your VENDOR_CODE
    'sending_facility'      => 'DEMO',     // MSH-4: your CENTRE_CODE — CHANGE THIS TO PROD VALUE

    /*
    |--------------------------------------------------------------------------
    | Somatiq RIS Receiver Identity
    |--------------------------------------------------------------------------
    | Always 'PACS' for Somatiq — do not change.
    */
    'receiving_application' => 'PACS',     // MSH-5

];
