<?php
require_once __DIR__ . '/../config/session.php';
header('Content-Type: application/json');

/**
 * Check AI Provider Configuration Status
 * Returns which AI providers have API keys configured
 */

// Load .env file
$envFile = __DIR__ . '/../.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        if (strpos($line, '=') === false) continue;
        list($key, $value) = explode('=', $line, 2);
        $_ENV[trim($key)] = trim($value);
    }
}

// Check each provider
$providers = [
    'deepseek' => [
        'name' => 'DeepSeek',
        'key' => $_ENV['DEEPSEEK_API_KEY'] ?? '',
        'configured' => !empty($_ENV['DEEPSEEK_API_KEY'] ?? '')
    ],
    'openai' => [
        'name' => 'OpenAI GPT-4',
        'key' => $_ENV['OPENAI_API_KEY'] ?? '',
        'configured' => !empty($_ENV['OPENAI_API_KEY'] ?? '')
    ],
    'claude' => [
        'name' => 'Anthropic Claude',
        'key' => $_ENV['ANTHROPIC_API_KEY'] ?? '',
        'configured' => !empty($_ENV['ANTHROPIC_API_KEY'] ?? '')
    ],
    'gemini' => [
        'name' => 'Google Gemini',
        'key' => $_ENV['GOOGLE_API_KEY'] ?? '',
        'configured' => !empty($_ENV['GOOGLE_API_KEY'] ?? '')
    ],
    'grok' => [
        'name' => 'xAI Grok',
        'key' => $_ENV['XAI_API_KEY'] ?? '',
        'configured' => !empty($_ENV['XAI_API_KEY'] ?? '')
    ],
    'meta' => [
        'name' => 'Meta AI (Llama)',
        'key' => $_ENV['TOGETHER_API_KEY'] ?? '',
        'configured' => !empty($_ENV['TOGETHER_API_KEY'] ?? '')
    ]
];

// Remove actual API keys from response (security)
foreach ($providers as $key => $provider) {
    unset($providers[$key]['key']);
}

echo json_encode([
    'success' => true,
    'providers' => $providers
]);
