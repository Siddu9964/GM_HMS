<?php
/**
 * Google Gemini AI Configuration
 * Secure storage for API credentials and settings
 */

// Gemini API Configuration
define('GEMINI_API_KEY', 'AIzaSyANUrEz7lo9qllB1UVMhbItsOjR94NkrKA');
define('GEMINI_API_ENDPOINT', 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent');
define('GEMINI_MODEL', 'gemini-2.5-flash');

// API Settings
define('GEMINI_TIMEOUT', 30); // seconds
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
