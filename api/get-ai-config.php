<?php
require_once __DIR__ . '/../config/session.php';
header('Content-Type: application/json');

/**
 * Get AI Configuration from .env
 * Returns AI mode (ensemble/single) and status message for UI
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

// Get AI mode and preferred provider
$aiMode = $_ENV['AI_MODE'] ?? 'ensemble';
$preferredProvider = $_ENV['PREFERRED_AI_PROVIDER'] ?? 'deepseek';

// Count configured providers
$providers = [
    'deepseek' => $_ENV['DEEPSEEK_API_KEY'] ?? '',
    'openai' => $_ENV['OPENAI_API_KEY'] ?? '',
    'claude' => $_ENV['ANTHROPIC_API_KEY'] ?? '',
    'gemini' => $_ENV['GOOGLE_API_KEY'] ?? '',
    'grok' => $_ENV['XAI_API_KEY'] ?? '',
    'meta' => $_ENV['TOGETHER_API_KEY'] ?? ''
];

$providerNames = [
    'deepseek' => 'DeepSeek',
    'openai' => 'OpenAI GPT-4',
    'claude' => 'Anthropic Claude',
    'gemini' => 'Google Gemini',
    'grok' => 'xAI Grok',
    'meta' => 'Meta AI (Llama)'
];

$configuredProviders = [];
foreach ($providers as $key => $apiKey) {
    if (!empty($apiKey)) {
        $configuredProviders[] = $providerNames[$key];
    }
}

$configuredCount = count($configuredProviders);

// Build status message
if ($aiMode === 'ensemble') {
    if ($configuredCount === 0) {
        $statusMessage = '<strong>Ensemble Mode:</strong> Local AI only (no LLM providers configured)';
    } else if ($configuredCount === 1) {
        $statusMessage = '<strong>Ensemble Mode:</strong> 1 LLM (' . $configuredProviders[0] . ') + Local AI';
    } else {
        $providerList = implode(', ', $configuredProviders);
        $statusMessage = '<strong>Ensemble Mode:</strong> ' . $configuredCount . ' LLMs (' . $providerList . ') + Local AI will collaborate';
    }
} else {
    // Single mode
    $preferredName = $providerNames[$preferredProvider] ?? 'Unknown';
    $preferredConfigured = !empty($providers[$preferredProvider]);

    if ($preferredConfigured) {
        $statusMessage = '<strong>Single Mode:</strong> Using ' . $preferredName . ' + Local AI fallback';
    } else {
        $statusMessage = '<strong>Single Mode:</strong> ' . $preferredName . ' (not configured) - will use Local AI only';
    }
}

echo json_encode([
    'success' => true,
    'ai_mode' => $aiMode,
    'preferred_provider' => $preferredProvider,
    'configured_count' => $configuredCount,
    'configured_providers' => $configuredProviders,
    'status_message' => $statusMessage
]);
