<?php
require_once __DIR__ . '/../config/session.php';
header('Content-Type: application/json');

require_once __DIR__ . '/../config/database.php';

/**
 * Universal LLM Move Endpoint
 * Supports multiple AI providers: DeepSeek, OpenAI, Claude, Gemini, Grok, Meta AI
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

$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['board']) || !isset($input['gameState']) || !isset($input['playerSide'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
    exit;
}

$board = $input['board'];
$gameState = $input['gameState'];
$playerSide = $input['playerSide'];
$sessionId = $input['session_id'] ?? null;
$playerId = $input['player_id'] ?? null;
$provider = $input['provider'] ?? 'deepseek'; // Default to DeepSeek

// Normalize board
for ($i = 0; $i < 9; $i++) {
    if (!isset($board[$i]) || $board[$i] === '' || $board[$i] === 0 || $board[$i] === false) {
        $board[$i] = null;
    }
}

// Get database insights
$db = Database::getInstance();
$conn = $db->getConnection();
$dbInsights = getGameInsights($conn, $sessionId, $playerId);

// Get AI provider configuration
$aiConfig = getAIProviderConfig($provider);

if (!$aiConfig) {
    echo json_encode([
        'success' => false,
        'message' => "Unsupported AI provider: {$provider}",
        'fallback' => true
    ]);
    exit;
}

// Check if API key is configured
if (!$aiConfig['api_key']) {
    echo json_encode([
        'success' => false,
        'message' => "{$aiConfig['name']} API key not configured",
        'fallback' => true
    ]);
    exit;
}

// Build prompt
$prompt = buildUniversalPrompt($board, $gameState, $playerSide, $dbInsights);

// Call appropriate AI provider
try {
    $response = callAIProvider($aiConfig, $prompt);

    if ($response && isset($response['move'])) {
        // Validate the move
        if (validateMove($response['move'], $board, $gameState, $playerSide)) {
            echo json_encode([
                'success' => true,
                'move' => $response['move'],
                'reasoning' => $response['move']['reasoning'] ?? 'Strategic move',
                'confidence' => $response['move']['confidence'] ?? 85,
                'provider' => $aiConfig['name']
            ]);
        } else {
            $debugInfo = [
                'to_position' => $response['move']['to_position'] ?? 'missing',
                'board_at_position' => isset($response['move']['to_position']) ? $board[$response['move']['to_position']] : 'N/A',
                'move_type' => $response['move']['move_type'] ?? 'missing',
                'phase' => $gameState['phase'],
                'board' => $board
            ];

            echo json_encode([
                'success' => false,
                'message' => 'Invalid move suggested by LLM',
                'fallback' => true,
                'llm_response' => $response['move'],
                'debug' => $debugInfo,
                'provider' => $aiConfig['name']
            ]);
        }
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'No valid response from AI provider',
            'fallback' => true,
            'provider' => $aiConfig['name']
        ]);
    }

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'API request failed: ' . $e->getMessage(),
        'fallback' => true,
        'provider' => $aiConfig['name']
    ]);
}

/**
 * Get AI provider configuration
 */
function getAIProviderConfig($provider) {
    $providers = [
        'deepseek' => [
            'name' => 'DeepSeek',
            'endpoint' => 'https://api.deepseek.com/v1/chat/completions',
            'model' => 'deepseek-chat',
            'api_key' => $_ENV['DEEPSEEK_API_KEY'] ?? null,
            'temperature' => 0.3,
            'max_tokens' => 500
        ],
        'openai' => [
            'name' => 'OpenAI GPT-4',
            'endpoint' => 'https://api.openai.com/v1/chat/completions',
            'model' => 'gpt-4-turbo-preview',
            'api_key' => $_ENV['OPENAI_API_KEY'] ?? null,
            'temperature' => 0.3,
            'max_tokens' => 500
        ],
        'claude' => [
            'name' => 'Anthropic Claude',
            'endpoint' => 'https://api.anthropic.com/v1/messages',
            'model' => 'claude-3-5-sonnet-20241022',
            'api_key' => $_ENV['ANTHROPIC_API_KEY'] ?? null,
            'temperature' => 0.3,
            'max_tokens' => 500
        ],
        'gemini' => [
            'name' => 'Google Gemini',
            'endpoint' => 'https://generativelanguage.googleapis.com/v1beta/models/gemini-pro:generateContent',
            'model' => 'gemini-pro',
            'api_key' => $_ENV['GOOGLE_API_KEY'] ?? null,
            'temperature' => 0.3,
            'max_tokens' => 500
        ],
        'grok' => [
            'name' => 'xAI Grok',
            'endpoint' => 'https://api.x.ai/v1/chat/completions',
            'model' => 'grok-beta',
            'api_key' => $_ENV['XAI_API_KEY'] ?? null,
            'temperature' => 0.3,
            'max_tokens' => 500
        ],
        'meta' => [
            'name' => 'Meta AI (Llama)',
            'endpoint' => 'https://api.together.xyz/v1/chat/completions',
            'model' => 'meta-llama/Llama-3-70b-chat-hf',
            'api_key' => $_ENV['TOGETHER_API_KEY'] ?? null,
            'temperature' => 0.3,
            'max_tokens' => 500
        ]
    ];

    return $providers[$provider] ?? null;
}

/**
 * Call AI provider with appropriate format
 */
function callAIProvider($config, $prompt) {
    $ch = curl_init();

    // Build request based on provider
    if ($config['name'] === 'Anthropic Claude') {
        // Claude uses a different API format
        $payload = [
            'model' => $config['model'],
            'messages' => [
                [
                    'role' => 'user',
                    'content' => $prompt
                ]
            ],
            'temperature' => $config['temperature'],
            'max_tokens' => $config['max_tokens'],
            'system' => 'You are an expert Tactical Pebble Game AI. Analyze board positions and suggest optimal moves. Always respond with valid JSON only.'
        ];

        $headers = [
            'Content-Type: application/json',
            'x-api-key: ' . $config['api_key'],
            'anthropic-version: 2023-06-01'
        ];

    } elseif ($config['name'] === 'Google Gemini') {
        // Gemini uses a different format
        $endpoint = str_replace('gemini-pro', $config['model'], $config['endpoint']);
        $endpoint .= '?key=' . $config['api_key'];

        $payload = [
            'contents' => [
                [
                    'parts' => [
                        ['text' => $prompt]
                    ]
                ]
            ],
            'generationConfig' => [
                'temperature' => $config['temperature'],
                'maxOutputTokens' => $config['max_tokens']
            ]
        ];

        $headers = ['Content-Type: application/json'];
        curl_setopt($ch, CURLOPT_URL, $endpoint);

    } else {
        // OpenAI-compatible format (DeepSeek, OpenAI, Grok, Meta)
        $payload = [
            'model' => $config['model'],
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'You are an expert Tactical Pebble Game AI. Analyze board positions and suggest optimal moves. Always respond with valid JSON only.'
                ],
                [
                    'role' => 'user',
                    'content' => $prompt
                ]
            ],
            'temperature' => $config['temperature'],
            'max_tokens' => $config['max_tokens'],
            'response_format' => ['type' => 'json_object']
        ];

        $headers = [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $config['api_key']
        ];
    }

    if ($config['name'] !== 'Google Gemini') {
        curl_setopt($ch, CURLOPT_URL, $config['endpoint']);
    }

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    if ($error || $httpCode !== 200) {
        throw new Exception("API request failed: " . ($error ?: "HTTP $httpCode"));
    }

    $data = json_decode($response, true);

    // Parse response based on provider
    if ($config['name'] === 'Anthropic Claude') {
        $content = $data['content'][0]['text'] ?? null;
    } elseif ($config['name'] === 'Google Gemini') {
        $content = $data['candidates'][0]['content']['parts'][0]['text'] ?? null;
    } else {
        // OpenAI-compatible
        $content = $data['choices'][0]['message']['content'] ?? null;
    }

    if (!$content) {
        return null;
    }

    // Parse JSON response
    $move = json_decode($content, true);

    return ['move' => $move];
}

// Reuse helper functions - copy them here to avoid duplicate execution
function getGameInsights($conn, $sessionId, $playerId) {
    $insights = [
        'player_patterns' => [],
        'ai_strategies' => [],
        'opening_patterns' => [],
        'winning_patterns' => []
    ];

    try {
        $stmt = $conn->prepare("SELECT player1_id FROM game_sessions WHERE id = ?");
        $stmt->execute([$sessionId]);
        $session = $stmt->fetch();

        if ($session && $session['player1_id']) {
            $playerId = $session['player1_id'];

            $stmt = $conn->prepare("
                SELECT gm.position, gm.move_type, gm.phase, COUNT(*) as frequency
                FROM game_moves gm
                JOIN game_sessions gs ON gm.session_id = gs.id
                WHERE gs.player1_id = ?
                AND gm.move_number <= 3
                GROUP BY gm.position, gm.move_type, gm.phase
                ORDER BY frequency DESC
                LIMIT 5
            ");
            $stmt->execute([$playerId]);
            $insights['player_patterns'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $stmt = $conn->prepare("
                SELECT
                    COUNT(*) as total_games,
                    SUM(CASE WHEN winner_id = ? THEN 1 ELSE 0 END) as wins,
                    SUM(CASE WHEN winner_id IS NOT NULL AND winner_id != ? THEN 1 ELSE 0 END) as losses
                FROM game_sessions
                WHERE player1_id = ? AND status = 'completed'
            ");
            $stmt->execute([$playerId, $playerId, $playerId]);
            $stats = $stmt->fetch(PDO::FETCH_ASSOC);
            $insights['player_stats'] = $stats;
        }

        $stmt = $conn->prepare("
            SELECT strategy_name, success_rate, times_used
            FROM ai_strategies
            WHERE success_rate > 0.6
            ORDER BY success_rate DESC
            LIMIT 5
        ");
        $stmt->execute();
        $insights['ai_strategies'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $stmt = $conn->prepare("
            SELECT opening_pattern, win_rate, sample_size
            FROM ai_training_data
            WHERE win_rate > 0.5 AND sample_size > 5
            ORDER BY win_rate DESC, sample_size DESC
            LIMIT 5
        ");
        $stmt->execute();
        $insights['opening_patterns'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

    } catch (Exception $e) {
        error_log("LLM insights error: " . $e->getMessage());
    }

    return $insights;
}

function buildUniversalPrompt($board, $gameState, $playerSide, $dbInsights = []) {
    $phase = $gameState['phase'];
    $placedCount = $gameState['placedCount'];
    $opponentSide = $playerSide === 'X' ? 'O' : 'X';

    $boardVisual = visualizeBoard($board);

    $prompt = "You are playing Tactical Pebble Game (strategic tic-tac-toe variant).\n\n";
    $prompt .= "**GAME RULES:**\n";
    $prompt .= "1. Two phases: PLACEMENT then MOVEMENT\n";
    $prompt .= "2. Placement: Players alternate placing 3 pebbles each\n";
    $prompt .= "3. Movement: Players alternate moving pebbles to adjacent empty spots (horizontal/vertical, NOT diagonal)\n";
    $prompt .= "4. Goal: Get 3 in a row (horizontal, vertical, or diagonal)\n\n";

    $prompt .= "**CURRENT STATE:**\n";
    $prompt .= "Phase: {$phase}\n";
    $prompt .= "Your Side: {$playerSide}\n";
    $prompt .= "Opponent: {$opponentSide}\n";
    $prompt .= "Placed Count: You have {$placedCount[$playerSide]}/3, Opponent has {$placedCount[$opponentSide]}/3\n\n";

    $prompt .= "**BOARD POSITIONS:**\n";
    $prompt .= "0 | 1 | 2\n---------\n3 | 4 | 5\n---------\n6 | 7 | 8\n\n";

    $prompt .= "**CURRENT BOARD:**\n{$boardVisual}\n\n";

    // List occupied and available positions
    $occupied = [];
    $available = [];
    for ($i = 0; $i < 9; $i++) {
        if ($board[$i] !== null) {
            $occupied[] = "Position {$i}: {$board[$i]}";
        } else {
            $available[] = $i;
        }
    }

    $prompt .= "**OCCUPIED POSITIONS:** ";
    $prompt .= !empty($occupied) ? implode(", ", $occupied) : "None (board is empty)";
    $prompt .= "\n**AVAILABLE POSITIONS:** " . implode(", ", $available) . "\n\n";
    $prompt .= "**IMPORTANT:** You MUST choose from available positions only!\n\n";

    $prompt .= "**WINNING LINES:**\n";
    $prompt .= "[0,1,2], [3,4,5], [6,7,8] (rows)\n";
    $prompt .= "[0,3,6], [1,4,7], [2,5,8] (columns)\n";
    $prompt .= "[0,4,8], [2,4,6] (diagonals)\n\n";

    $prompt .= "**STRATEGIC PRIORITIES:**\n";
    $prompt .= "1. WIN: If you can complete 3-in-a-row, do it immediately\n";
    $prompt .= "2. BLOCK: If opponent can win next turn, block them\n";
    $prompt .= "3. CENTER CONTROL: Position 4 is most valuable\n";
    $prompt .= "4. CORNER CONTROL: Positions 0,2,6,8 create multiple threats\n";
    $prompt .= "5. FORK CREATION: Create two winning threats at once\n\n";

    // Add database insights
    if (!empty($dbInsights)) {
        $prompt .= "**ADVANCED INTELLIGENCE:**\n";

        if (!empty($dbInsights['player_patterns'])) {
            $prompt .= "Opponent's Playing Style:\n";
            foreach ($dbInsights['player_patterns'] as $pattern) {
                $prompt .= "- Frequently plays position {$pattern['position']} ({$pattern['frequency']} times)\n";
            }
        }

        if (!empty($dbInsights['player_stats'])) {
            $stats = $dbInsights['player_stats'];
            $winRate = $stats['total_games'] > 0 ? round(($stats['wins'] / $stats['total_games']) * 100) : 0;
            $prompt .= "Opponent Stats: {$stats['wins']}W-{$stats['losses']}L ({$winRate}% win rate)\n";
        }

        if (!empty($dbInsights['ai_strategies'])) {
            $prompt .= "\nProven AI Strategies:\n";
            foreach ($dbInsights['ai_strategies'] as $strategy) {
                $successRate = round($strategy['success_rate'] * 100);
                $prompt .= "- {$strategy['strategy_name']}: {$successRate}% success ({$strategy['times_used']} games)\n";
            }
        }

        if (!empty($dbInsights['opening_patterns'])) {
            $prompt .= "\nWinning Opening Patterns:\n";
            foreach ($dbInsights['opening_patterns'] as $opening) {
                $winRate = round($opening['win_rate'] * 100);
                $prompt .= "- Pattern '{$opening['opening_pattern']}': {$winRate}% win rate ({$opening['sample_size']} games)\n";
            }
        }

        $prompt .= "\n";
    }

    $prompt .= "**REQUIRED JSON RESPONSE:**\n{\n";
    $prompt .= "    \"move_type\": \"{$phase}\",\n";
    $prompt .= "    \"to_position\": <0-8>,\n";

    if ($phase === 'movement') {
        $prompt .= "    \"from_position\": <0-8>,\n";
    }

    $prompt .= "    \"reasoning\": \"Brief strategic explanation\",\n";
    $prompt .= "    \"confidence\": <0-100>\n";
    $prompt .= "}\n\nAnalyze deeply and respond with the best move as JSON only.";

    return $prompt;
}

function visualizeBoard($board) {
    $display = array_map(fn($cell) => $cell ?? '·', $board);
    return " {$display[0]} | {$display[1]} | {$display[2]}\n-----------\n {$display[3]} | {$display[4]} | {$display[5]}\n-----------\n {$display[6]} | {$display[7]} | {$display[8]}";
}

function validateMove($move, $board, $gameState, $playerSide) {
    if (!is_array($move)) return false;

    $phase = $gameState['phase'];
    $to = $move['to_position'] ?? -1;

    if ($to < 0 || $to > 8) return false;
    if ($board[$to] !== null) return false;

    if ($phase === 'placement') {
        return $move['move_type'] === 'placement';
    } else {
        if ($move['move_type'] !== 'movement') return false;

        $from = $move['from_position'] ?? -1;
        if ($from < 0 || $from > 8) return false;
        if ($board[$from] !== $playerSide) return false;

        return isAdjacent($from, $to);
    }
}

function isAdjacent($from, $to) {
    $adjacencyMap = [
        0 => [1, 3],
        1 => [0, 2, 4],
        2 => [1, 5],
        3 => [0, 4, 6],
        4 => [1, 3, 5, 7],
        5 => [2, 4, 8],
        6 => [3, 7],
        7 => [4, 6, 8],
        8 => [5, 7]
    ];
    return in_array($to, $adjacencyMap[$from] ?? []);
}
