<?php
/**
 * Google Gemini AI Configuration
 * Secure storage for API credentials and settings
 */

// Load the secret API key from a separate file that is ignored by Git
if (file_exists(__DIR__ . '/gemini_secrets.php')) {
    require_once __DIR__ . '/gemini_secrets.php';
} else {
    // Fallback if secrets file is missing
    define('GEMINI_API_KEY', 'YOUR_API_KEY_HERE');
}

define('GEMINI_API_ENDPOINT', 'https://generativelanguage.googleapis.com/v1beta/models/gemini-3.5-flash-lite:generateContent');
define('GEMINI_MODEL', 'gemini-3.5-flash-lite');

// API Settings
define('GEMINI_TIMEOUT', 90); // seconds
define('GEMINI_MAX_RETRIES', 2);
define('GEMINI_TEMPERATURE', 0.7); // 0-1, lower = more focused/deterministic

// Safety Settings
define('GEMINI_SAFETY_THRESHOLD', 'BLOCK_MEDIUM_AND_ABOVE');

/**
 * Get Gemini API URL with key
 */
function getGeminiApiUrl()
{
    return GEMINI_API_ENDPOINT . '?key=' . GEMINI_API_KEY;
}
