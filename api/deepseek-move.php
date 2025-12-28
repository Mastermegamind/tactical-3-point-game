<?php
require_once __DIR__ . '/../config/session.php';
header('Content-Type: application/json');

/**
 * DeepSeek AI Move Endpoint
 * Calls DeepSeek API to get intelligent move suggestion
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

// Get API key from environment or config
$deepseekApiKey = $_ENV['DEEPSEEK_API_KEY'] ?? getenv('DEEPSEEK_API_KEY');

if (!$deepseekApiKey) {
    echo json_encode([
        'success' => false,
        'message' => 'DeepSeek API key not configured',
        'fallback' => true
    ]);
    exit;
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

// Normalize board - JavaScript null becomes PHP null
// But sometimes comes through as empty string or 0
for ($i = 0; $i < 9; $i++) {
    if (!isset($board[$i]) || $board[$i] === '' || $board[$i] === 0 || $board[$i] === false) {
        $board[$i] = null;
    }
}

// Get database insights for enhanced strategy
require_once __DIR__ . '/../config/database.php';
$db = Database::getInstance();
$conn = $db->getConnection();

$dbInsights = getGameInsights($conn, $sessionId, $playerId);

// Build the prompt with database context
$prompt = buildDeepSeekPrompt($board, $gameState, $playerSide, $dbInsights);

// Call DeepSeek API
$apiEndpoint = 'https://api.deepseek.com/v1/chat/completions';

$payload = [
    'model' => 'deepseek-chat', // or 'deepseek-reasoner' for more thinking
    'messages' => [
        [
            'role' => 'system',
            'content' => 'You are an expert Tactical Pebble Game AI. Analyze board positions and suggest optimal moves. Always respond with valid JSON only, no markdown formatting.'
        ],
        [
            'role' => 'user',
            'content' => $prompt
        ]
    ],
    'temperature' => 0.3,
    'max_tokens' => 500,
    'response_format' => ['type' => 'json_object']
];

$ch = curl_init($apiEndpoint);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Authorization: Bearer ' . $deepseekApiKey
]);
curl_setopt($ch, CURLOPT_TIMEOUT, 10); // 10 second timeout

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

if ($error || $httpCode !== 200) {
    echo json_encode([
        'success' => false,
        'message' => 'API request failed',
        'error' => $error,
        'fallback' => true
    ]);
    exit;
}

$data = json_decode($response, true);

if (!isset($data['choices'][0]['message']['content'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid API response',
        'fallback' => true
    ]);
    exit;
}

// Parse the LLM response
$llmResponse = json_decode($data['choices'][0]['message']['content'], true);

// Validate the move
if (validateMove($llmResponse, $board, $gameState, $playerSide)) {
    echo json_encode([
        'success' => true,
        'move' => $llmResponse,
        'reasoning' => $llmResponse['reasoning'] ?? 'Strategic move',
        'confidence' => $llmResponse['confidence'] ?? 85
    ]);
} else {
    // Add debug info to understand why validation failed
    $debugInfo = [
        'to_position' => $llmResponse['to_position'] ?? 'missing',
        'board_at_position' => isset($llmResponse['to_position']) ? $board[$llmResponse['to_position']] : 'N/A',
        'move_type' => $llmResponse['move_type'] ?? 'missing',
        'phase' => $gameState['phase'],
        'board' => $board
    ];

    echo json_encode([
        'success' => false,
        'message' => 'Invalid move suggested by LLM',
        'fallback' => true,
        'llm_response' => $llmResponse,
        'debug' => $debugInfo
    ]);
}

/**
 * Get game insights from database
 */
function getGameInsights($conn, $sessionId, $playerId) {
    $insights = [
        'player_patterns' => [],
        'ai_strategies' => [],
        'opening_patterns' => [],
        'winning_patterns' => []
    ];

    try {
        // Get current session info
        $stmt = $conn->prepare("SELECT player1_id FROM game_sessions WHERE id = ?");
        $stmt->execute([$sessionId]);
        $session = $stmt->fetch();

        if ($session && $session['player1_id']) {
            $playerId = $session['player1_id'];

            // Get player's recent game patterns (last 10 games)
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

            // Get player's win/loss ratio and preferred strategies
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

        // Get AI's successful strategies from ai_strategies table
        $stmt = $conn->prepare("
            SELECT strategy_name, success_rate, times_used
            FROM ai_strategies
            WHERE success_rate > 0.6
            ORDER BY success_rate DESC
            LIMIT 5
        ");
        $stmt->execute();
        $insights['ai_strategies'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Get winning opening patterns from ai_training_data
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
        // Log error but continue with empty insights
        error_log("DeepSeek insights error: " . $e->getMessage());
    }

    return $insights;
}

/**
 * Build comprehensive prompt for DeepSeek with database insights
 */
function buildDeepSeekPrompt($board, $gameState, $playerSide, $dbInsights = []) {
    $phase = $gameState['phase'];
    $placedCount = $gameState['placedCount'];
    $opponentSide = $playerSide === 'X' ? 'O' : 'X';

    $boardVisual = visualizeBoard($board);

    $prompt = <<<PROMPT
You are playing Tactical Pebble Game (strategic tic-tac-toe variant).

**GAME RULES:**
1. Two phases: PLACEMENT then MOVEMENT
2. Placement: Players alternate placing 3 pebbles each
3. Movement: Players alternate moving pebbles to adjacent empty spots (horizontal/vertical, NOT diagonal)
4. Goal: Get 3 in a row (horizontal, vertical, or diagonal)

**CURRENT STATE:**
Phase: {$phase}
Your Side: {$playerSide}
Opponent: {$opponentSide}
Placed Count: You have {$placedCount[$playerSide]}/3, Opponent has {$placedCount[$opponentSide]}/3

**BOARD POSITIONS:**
0 | 1 | 2
---------
3 | 4 | 5
---------
6 | 7 | 8

**CURRENT BOARD:**
{$boardVisual}

**OCCUPIED POSITIONS:**
PROMPT;

    // List occupied positions explicitly
    $occupied = [];
    $available = [];
    for ($i = 0; $i < 9; $i++) {
        if ($board[$i] !== null) {
            $occupied[] = "Position {$i}: {$board[$i]}";
        } else {
            $available[] = $i;
        }
    }

    if (!empty($occupied)) {
        $prompt .= implode(", ", $occupied) . "\n";
    } else {
        $prompt .= "None (board is empty)\n";
    }

    $prompt .= "**AVAILABLE POSITIONS:** " . implode(", ", $available) . "\n\n";
    $prompt .= "**IMPORTANT:** You MUST choose from available positions only!\n";

    $prompt .= <<<PROMPT

**WINNING LINES:**
[0,1,2], [3,4,5], [6,7,8] (rows)
[0,3,6], [1,4,7], [2,5,8] (columns)
[0,4,8], [2,4,6] (diagonals)

**STRATEGIC PRIORITIES:**
1. WIN: If you can complete 3-in-a-row, do it immediately
2. BLOCK: If opponent can win next turn, block them
3. CENTER CONTROL: Position 4 is most valuable
4. CORNER CONTROL: Positions 0,2,6,8 create multiple threats
5. FORK CREATION: Create two winning threats at once

PROMPT;

    // Add database insights if available
    if (!empty($dbInsights)) {
        $prompt .= "\n**ADVANCED INTELLIGENCE:**\n";

        // Player patterns
        if (!empty($dbInsights['player_patterns'])) {
            $prompt .= "Opponent's Playing Style:\n";
            foreach ($dbInsights['player_patterns'] as $pattern) {
                $prompt .= "- Frequently plays position {$pattern['position']} ({$pattern['frequency']} times)\n";
            }
        }

        // Player stats
        if (!empty($dbInsights['player_stats'])) {
            $stats = $dbInsights['player_stats'];
            $winRate = $stats['total_games'] > 0 ? round(($stats['wins'] / $stats['total_games']) * 100) : 0;
            $prompt .= "Opponent Stats: {$stats['wins']}W-{$stats['losses']}L ({$winRate}% win rate)\n";
        }

        // AI successful strategies
        if (!empty($dbInsights['ai_strategies'])) {
            $prompt .= "\nProven AI Strategies:\n";
            foreach ($dbInsights['ai_strategies'] as $strategy) {
                $successRate = round($strategy['success_rate'] * 100);
                $prompt .= "- {$strategy['strategy_name']}: {$successRate}% success ({$strategy['times_used']} games)\n";
            }
        }

        // Winning opening patterns
        if (!empty($dbInsights['opening_patterns'])) {
            $prompt .= "\nWinning Opening Patterns:\n";
            foreach ($dbInsights['opening_patterns'] as $opening) {
                $winRate = round($opening['win_rate'] * 100);
                $prompt .= "- Pattern '{$opening['opening_pattern']}': {$winRate}% win rate ({$opening['sample_size']} games)\n";
            }
        }

        $prompt .= "\n";
    }

    $prompt .= <<<PROMPT

**REQUIRED JSON RESPONSE:**
{
    "move_type": "{$phase}",
    "to_position": <0-8>,
PROMPT;

    if ($phase === 'movement') {
        $prompt .= "\n    \"from_position\": <0-8>,";
    }

    $prompt .= <<<PROMPT

    "reasoning": "Brief strategic explanation",
    "confidence": <0-100>
}

Analyze deeply and respond with the best move as JSON only.
PROMPT;

    return $prompt;
}

function visualizeBoard($board) {
    $display = array_map(fn($cell) => $cell ?? '·', $board);
    return <<<BOARD
 {$display[0]} | {$display[1]} | {$display[2]}
-----------
 {$display[3]} | {$display[4]} | {$display[5]}
-----------
 {$display[6]} | {$display[7]} | {$display[8]}
BOARD;
}

function validateMove($move, $board, $gameState, $playerSide) {
    if (!is_array($move)) return false;

    $phase = $gameState['phase'];
    $to = $move['to_position'] ?? -1;

    if ($to < 0 || $to > 8) return false;

    // Check if destination is empty (after normalization, empty cells are null)
    if ($board[$to] !== null) {
        return false;
    }

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
