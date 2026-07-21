<?php
/**
 * Groq API Configuration
 * 
 * Configuration for Groq's Whisper API for audio translation
 * Endpoint: https://api.groq.com/openai/v1/audio/translations
 */

return [
    // API Configuration
    'api_key' => getenv('GROQ_API_KEY') ?: '',
    'api_endpoint' => 'https://api.groq.com/openai/v1/audio/translations',
    
    // Model Selection
    // whisper-large-v3-turbo: Faster, but ONLY supports transcription (NOT translation)
    // whisper-large-v3: Supports both transcription AND translation (use this for multilingual)
    'model' => 'whisper-large-v3',
    
    // File Upload Limits
    'max_file_size' => 25 * 1024 * 1024, // 25MB for free tier
    
    // Supported Audio Formats
    'supported_formats' => ['webm', 'mp3', 'wav', 'm4a', 'ogg', 'flac', 'mp4', 'mpeg', 'mpga'],
    
    // Request Settings
    'timeout' => 30, // seconds
    'temperature' => 0, // 0 for most deterministic output
    'response_format' => 'json', // json, text, or verbose_json
    
    // Temporary Storage
    'temp_dir' => __DIR__ . '/../temp/audio/',
];
