<?php

/**
 * AI Learning Engine
 * Analyzes game data and extracts patterns to improve AI performance
 */
require_once __DIR__ . '/../config/RedisManager.php';

class AILearningEngine {
    private $conn;
    private $difficulty;

    public function __construct($pdo, $difficulty = 'hard') {
        $this->conn = $pdo;
        $this->difficulty = $difficulty;
    }

    /**
     * Analyze winning patterns from historical games
     * Returns patterns that lead to AI victories
     */
    public function analyzeWinningPatterns() {
        // Get all AI wins with their move sequences
        $stmt = $this->conn->prepare("
            SELECT
                gs.id as session_id,
                gm.move_number,
                gm.move_type,
                gm.from_position,
                gm.to_position,
                gm.player,
                gm.board_state_after,
                atd.total_moves,
                atd.player_rating
            FROM ai_training_data atd
            JOIN game_sessions gs ON atd.session_id = gs.id
            JOIN game_moves gm ON gs.id = gm.session_id
            WHERE atd.game_outcome = 'ai_win'
            AND atd.difficulty_level = ?
            ORDER BY atd.session_id, gm.move_number
        ");
        $stmt->execute([$this->difficulty]);
        $moves = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $this->extractPatterns($moves);
    }

    /**
     * Extract patterns from move sequences
     */
    private function extractPatterns($moves) {
        $patterns = [
            'opening_moves' => [],      // First 3 AI placements
            'winning_sequences' => [],  // Move sequences that led to wins
            'defensive_blocks' => [],   // Successful blocking moves
            'trap_setups' => []        // Moves that set up traps
        ];

        $gameGroups = [];
        foreach ($moves as $move) {
            $gameGroups[$move['session_id']][] = $move;
        }

        foreach ($gameGroups as $sessionId => $gameMoves) {
            // Extract opening moves (AI's placement phase)
            $aiPlacements = array_filter($gameMoves, function($m) {
                return $m['move_type'] === 'placement' && $m['player'] === 'O';
            });

            if (count($aiPlacements) >= 3) {
                $openingPattern = array_map(function($m) {
                    return $m['to_position'];
                }, array_slice($aiPlacements, 0, 3));

                $patterns['opening_moves'][] = [
                    'positions' => $openingPattern,
                    'session_id' => $sessionId,
                    'total_moves' => $gameMoves[0]['total_moves'] ?? 0
                ];
            }

            // Extract winning sequences (last 5 moves before victory)
            $aiMovements = array_filter($gameMoves, function($m) {
                return $m['move_type'] === 'movement' && $m['player'] === 'O';
            });

            if (count($aiMovements) >= 3) {
                $lastMoves = array_slice($aiMovements, -3);
                $patterns['winning_sequences'][] = [
                    'moves' => array_map(function($m) {
                        return [
                            'from' => $m['from_position'],
                            'to' => $m['to_position']
                        ];
                    }, $lastMoves),
                    'session_id' => $sessionId
                ];
            }
        }

        return $patterns;
    }

    /**
     * Get AI performance statistics
     */
    public function getPerformanceStats() {
        $stmt = $this->conn->prepare("
            SELECT
                difficulty_level,
                COUNT(*) as total_games,
                SUM(CASE WHEN game_outcome = 'ai_win' THEN 1 ELSE 0 END) as ai_wins,
                SUM(CASE WHEN game_outcome = 'player_win' THEN 1 ELSE 0 END) as player_wins,
                SUM(CASE WHEN game_outcome = 'draw' THEN 1 ELSE 0 END) as draws,
                ROUND(AVG(total_moves), 2) as avg_moves,
                ROUND(AVG(CASE WHEN game_outcome = 'ai_win' THEN total_moves END), 2) as avg_moves_ai_win,
                ROUND(AVG(player_rating), 0) as avg_opponent_rating
            FROM ai_training_data
            WHERE difficulty_level = ?
            GROUP BY difficulty_level
        ");
        $stmt->execute([$this->difficulty]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Get position frequency analysis
     * Shows which board positions are most commonly used in winning games
     */
    public function getPositionFrequency() {
        $stmt = $this->conn->prepare("
            SELECT
                gm.to_position as position,
                COUNT(*) as frequency,
                SUM(CASE WHEN gm.player = 'O' THEN 1 ELSE 0 END) as ai_uses,
                SUM(CASE WHEN gm.player = 'X' THEN 1 ELSE 0 END) as player_uses
            FROM ai_training_data atd
            JOIN game_moves gm ON atd.session_id = gm.session_id
            WHERE atd.game_outcome = 'ai_win'
            AND atd.difficulty_level = ?
            AND gm.move_type = 'placement'
            GROUP BY gm.to_position
            ORDER BY frequency DESC
        ");
        $stmt->execute([$this->difficulty]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Analyze opponent mistakes that led to AI victories
     */
    public function analyzeOpponentMistakes() {
        $stmt = $this->conn->prepare("
            SELECT
                gm.session_id,
                gm.move_number,
                gm.from_position,
                gm.to_position,
                gm.board_state_after,
                atd.total_moves
            FROM ai_training_data atd
            JOIN game_moves gm ON atd.session_id = gm.session_id
            WHERE atd.game_outcome = 'ai_win'
            AND atd.difficulty_level = ?
            AND gm.player = 'X'
            ORDER BY atd.total_moves ASC, gm.move_number DESC
            LIMIT 100
        ");
        $stmt->execute([$this->difficulty]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Generate learned strategy weights
     * Returns position preferences based on historical data
     */
    public function generateStrategyWeights() {
        $positionFreq = $this->getPositionFrequency();
        $patterns = $this->analyzeWinningPatterns();

        $weights = array_fill(0, 9, 1.0); // Default weight for all positions

        // Increase weights for frequently used positions in winning games
        foreach ($positionFreq as $pos) {
            if (isset($pos['position']) && $pos['ai_uses'] > 0) {
                $position = $pos['position'];
                $successRate = $pos['ai_uses'] / $pos['frequency'];
                $weights[$position] = 1.0 + ($successRate * 2); // Boost by success rate
            }
        }

        // Boost weights for common opening positions
        $openingCounts = array_fill(0, 9, 0);
        foreach ($patterns['opening_moves'] as $opening) {
            foreach ($opening['positions'] as $pos) {
                $openingCounts[$pos]++;
            }
        }

        foreach ($openingCounts as $pos => $count) {
            if ($count > 0) {
                $weights[$pos] *= (1 + ($count * 0.1)); // Additional boost for common openings
            }
        }

        return [
            'position_weights' => $weights,
            'total_games_analyzed' => count($patterns['opening_moves']),
            'difficulty' => $this->difficulty
        ];
    }

    /**
     * Save learned strategy to cache file
     */
    public function saveLearnedStrategy() {
        $strategy = $this->generateStrategyWeights();
        $patterns = $this->analyzeWinningPatterns();

        $learnedData = [
            'version' => '1.0',
            'difficulty' => $this->difficulty,
            'generated_at' => date('Y-m-d H:i:s'),
            'weights' => $strategy,
            'patterns' => $patterns,
            'stats' => $this->getPerformanceStats()
        ];

        $cacheDir = __DIR__ . '/../cache';
        if (!is_dir($cacheDir)) {
            mkdir($cacheDir, 0755, true);
        }

        $cacheFile = $cacheDir . "/ai_strategy_{$this->difficulty}.json";
        file_put_contents($cacheFile, json_encode($learnedData, JSON_PRETTY_PRINT));

        $redisManager = RedisManager::getInstance();
        if ($redisManager->isEnabled()) {
            $redisManager->cacheTrainingData($this->difficulty, $learnedData);
        }

        return $learnedData;
    }

    /**
     * Load learned strategy from cache
     */
    public static function loadLearnedStrategy($difficulty = 'hard') {
        $redisManager = RedisManager::getInstance();
        if ($redisManager->isEnabled()) {
            $cached = $redisManager->getTrainingData($difficulty);
            if ($cached) {
                return $cached;
            }
        }

        $cacheFile = __DIR__ . "/../cache/ai_strategy_{$difficulty}.json";

        if (file_exists($cacheFile)) {
            $data = file_get_contents($cacheFile);
            return json_decode($data, true);
        }

        return null;
    }

    /**
     * Generate and save strategies to ai_strategies table from training data
     * This analyzes past games and creates reusable strategic patterns
     */
    public function generateStrategiesFromTraining() {
        $strategiesCreated = 0;

        // Get all AI winning games for this difficulty
        $stmt = $this->conn->prepare("
            SELECT
                atd.session_id,
                atd.total_moves,
                atd.player_rating,
                gs.id as game_id
            FROM ai_training_data atd
            JOIN game_sessions gs ON atd.session_id = gs.id
            WHERE atd.game_outcome = 'ai_win'
            AND atd.difficulty_level = ?
            AND atd.session_id IS NOT NULL
            ORDER BY atd.created_at DESC
            LIMIT 50
        ");
        $stmt->execute([$this->difficulty]);
        $winningGames = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($winningGames as $game) {
            $strategiesCreated += $this->extractStrategiesFromGame($game['session_id'], $game['total_moves']);
        }

        // Generate opening strategies from patterns
        $patterns = $this->analyzeWinningPatterns();
        $strategiesCreated += $this->saveOpeningStrategies($patterns['opening_moves']);

        // Generate positional strategies from weights
        $weights = $this->generateStrategyWeights();
        $strategiesCreated += $this->savePositionalStrategies($weights['position_weights']);

        return $strategiesCreated;
    }

    /**
     * Extract strategies from a single game's move sequence
     */
    private function extractStrategiesFromGame($sessionId, $totalMoves) {
        $strategiesCreated = 0;

        // Get all moves from this game
        $stmt = $this->conn->prepare("
            SELECT
                move_number,
                player,
                move_type,
                from_position,
                to_position,
                board_state_before,
                board_state_after
            FROM game_moves
            WHERE session_id = ?
            ORDER BY move_number ASC
        ");
        $stmt->execute([$sessionId]);
        $moves = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $aiMoves = array_filter($moves, function($m) { return $m['player'] === 'O'; });
        $oppMoves = array_filter($moves, function($m) { return $m['player'] === 'X'; });

        // Analyze each AI move
        foreach ($aiMoves as $index => $move) {
            $boardBefore = json_decode($move['board_state_before'], true);
            $boardAfter = json_decode($move['board_state_after'], true);

            if (!$boardBefore || !$boardAfter) continue;

            // Extract positions
            $aiPositions = $this->extractPlayerPositions($boardBefore, 'O');
            $oppPositions = $this->extractPlayerPositions($boardBefore, 'X');

            // Determine game phase
            $totalPieces = count($aiPositions) + count($oppPositions);
            $phase = $totalPieces < 6 ? 'placement' : ($totalPieces < 10 ? 'movement' : 'endgame');

            // Calculate board score
            $boardScore = $this->evaluateBoardPosition($boardBefore, $aiPositions, $oppPositions);

            // Detect if this was a blocking move
            $wasBlock = $this->wasBlockingMove($boardBefore, $move['to_position'], $oppPositions);

            // Detect if this led to a win
            $ledToWin = ($index === count($aiMoves) - 1);

            // Determine strategy type
            $strategyType = 'balanced';
            if ($ledToWin) $strategyType = 'offensive';
            elseif ($wasBlock) $strategyType = 'defensive';
            elseif ($boardScore > 20) $strategyType = 'offensive';
            elseif ($boardScore < -10) $strategyType = 'defensive';

            // Detect opponent pattern
            $oppPattern = $this->detectPatternFromMoves($oppMoves, $index);

            // Calculate threat level
            $threatLevel = $this->calculateThreatFromBoard($boardBefore, $oppPositions);

            // Save strategy
            $strategyName = $ledToWin ? 'winning-sequence' : ($wasBlock ? 'defensive-block' : 'tactical-move');

            $saved = $this->saveStrategyToDatabase(
                $strategyName,
                $boardBefore,
                $aiPositions,
                $oppPositions,
                $move['from_position'],
                $move['to_position'],
                $move['move_type'],
                $phase,
                $oppPattern,
                $strategyType,
                $boardScore,
                $threatLevel,
                $totalMoves
            );

            if ($saved) $strategiesCreated++;
        }

        return $strategiesCreated;
    }

    /**
     * Save opening strategies from analyzed patterns
     */
    private function saveOpeningStrategies($openingMoves) {
        $strategiesCreated = 0;

        if (empty($openingMoves)) return 0;

        // Group by first move
        $firstMoves = [];
        foreach ($openingMoves as $opening) {
            if (!isset($opening['positions']) || count($opening['positions']) < 1) continue;
            $firstPos = $opening['positions'][0];
            if (!isset($firstMoves[$firstPos])) {
                $firstMoves[$firstPos] = 0;
            }
            $firstMoves[$firstPos]++;
        }

        // Save each popular opening
        foreach ($firstMoves as $position => $count) {
            if ($count < 2) continue; // Only save if used multiple times

            $boardState = array_fill(0, 9, null);
            $boardState[$position] = 'O';

            $saved = $this->saveStrategyToDatabase(
                'opening-move',
                $boardState,
                [$position],
                [],
                null,
                $position,
                'placement',
                'placement',
                'unknown',
                'offensive',
                10,
                0,
                null
            );

            if ($saved) $strategiesCreated++;
        }

        return $strategiesCreated;
    }

    /**
     * Save positional strategies from weights
     */
    private function savePositionalStrategies($positionWeights) {
        $strategiesCreated = 0;

        if (empty($positionWeights)) return 0;

        // Save strategies for high-value positions
        foreach ($positionWeights as $position => $weight) {
            if ($weight < 2.0) continue; // Only save strong positions

            $boardState = array_fill(0, 9, null);
            $boardState[$position] = 'O';

            $strategyType = $position === 4 ? 'offensive' : ($weight > 3.0 ? 'offensive' : 'balanced');

            $saved = $this->saveStrategyToDatabase(
                'high-value-position',
                $boardState,
                [$position],
                [],
                null,
                $position,
                'placement',
                'placement',
                'balanced',
                $strategyType,
                $weight * 5,
                0,
                null
            );

            if ($saved) $strategiesCreated++;
        }

        return $strategiesCreated;
    }

    /**
     * Save a strategy to the ai_strategies database table
     */
    private function saveStrategyToDatabase(
        $strategyName,
        $boardState,
        $aiPositions,
        $oppPositions,
        $moveFrom,
        $moveTo,
        $moveType,
        $gamePhase,
        $oppPattern,
        $strategyType,
        $boardScore,
        $threatLevel,
        $avgMoves
    ) {
        try {
            // Check if similar strategy exists
            $stmt = $this->conn->prepare("
                SELECT id, success_count, total_uses
                FROM ai_strategies
                WHERE difficulty_level = ?
                AND move_to = ?
                AND move_type = ?
                AND game_phase = ?
                AND strategy_type = ?
                LIMIT 1
            ");

            $stmt->execute([
                $this->difficulty,
                $moveTo,
                $moveType,
                $gamePhase,
                $strategyType
            ]);

            $existing = $stmt->fetch();

            if ($existing) {
                // Update existing strategy
                $newSuccessCount = $existing['success_count'] + 1;
                $newTotalUses = $existing['total_uses'] + 1;
                $successRate = ($newSuccessCount / $newTotalUses) * 100;
                $priority = min(100, max(30, round($successRate * 0.7 + $boardScore * 0.3)));

                $stmt = $this->conn->prepare("
                    UPDATE ai_strategies
                    SET success_count = ?,
                        total_uses = ?,
                        success_rate = ?,
                        priority_score = ?,
                        board_evaluation_score = ?,
                        avg_moves_to_win = ?,
                        updated_at = NOW()
                    WHERE id = ?
                ");

                $stmt->execute([
                    $newSuccessCount,
                    $newTotalUses,
                    round($successRate, 2),
                    $priority,
                    round($boardScore, 4),
                    $avgMoves,
                    $existing['id']
                ]);

                return true;
            } else {
                // Insert new strategy
                $priority = min(100, max(40, round($boardScore + 50)));

                $stmt = $this->conn->prepare("
                    INSERT INTO ai_strategies
                    (strategy_name, difficulty_level, board_state, ai_pieces_positions,
                     opponent_pieces_positions, move_from, move_to, move_type, game_phase,
                     opponent_pattern, strategy_type, board_evaluation_score, threat_level,
                     priority_score, avg_moves_to_win, created_at, last_used_at)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
                ");

                $stmt->execute([
                    $strategyName,
                    $this->difficulty,
                    json_encode($boardState),
                    json_encode($aiPositions),
                    json_encode($oppPositions),
                    $moveFrom,
                    $moveTo,
                    $moveType,
                    $gamePhase,
                    $oppPattern,
                    $strategyType,
                    round($boardScore, 4),
                    $threatLevel,
                    $priority,
                    $avgMoves
                ]);

                return true;
            }
        } catch (Exception $e) {
            error_log("Failed to save strategy: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Helper: Extract player positions from board state
     */
    private function extractPlayerPositions($board, $player) {
        $positions = [];
        foreach ($board as $index => $cell) {
            if ($cell === $player) {
                $positions[] = $index;
            }
        }
        return $positions;
    }

    /**
     * Helper: Evaluate board position score
     */
    private function evaluateBoardPosition($board, $aiPositions, $oppPositions) {
        $score = 0;

        $winLines = [
            [0, 1, 2], [3, 4, 5], [6, 7, 8],
            [0, 3, 6], [1, 4, 7], [2, 5, 8],
            [0, 4, 8], [2, 4, 6]
        ];

        foreach ($winLines as $line) {
            $aiCount = 0;
            $oppCount = 0;
            $emptyCount = 0;

            foreach ($line as $pos) {
                if ($board[$pos] === 'O') $aiCount++;
                elseif ($board[$pos] === 'X') $oppCount++;
                else $emptyCount++;
            }

            if ($aiCount === 2 && $emptyCount === 1) $score += 40;
            if ($oppCount === 2 && $emptyCount === 1) $score -= 50;
            if ($aiCount === 2 && $oppCount === 0) $score += 15;
            if ($oppCount === 2 && $aiCount === 0) $score -= 20;
        }

        // Center control
        if ($board[4] === 'O') $score += 10;
        if ($board[4] === 'X') $score -= 10;

        return $score;
    }

    /**
     * Helper: Check if move was blocking opponent
     */
    private function wasBlockingMove($board, $position, $oppPositions) {
        $winLines = [
            [0, 1, 2], [3, 4, 5], [6, 7, 8],
            [0, 3, 6], [1, 4, 7], [2, 5, 8],
            [0, 4, 8], [2, 4, 6]
        ];

        foreach ($winLines as $line) {
            if (!in_array($position, $line)) continue;

            $oppCount = 0;
            $emptyCount = 0;

            foreach ($line as $pos) {
                if ($board[$pos] === 'X') $oppCount++;
                elseif ($board[$pos] === null) $emptyCount++;
            }

            if ($oppCount === 2 && $emptyCount === 1) {
                return true;
            }
        }

        return false;
    }

    /**
     * Helper: Detect opponent pattern from moves
     */
    private function detectPatternFromMoves($oppMoves, $currentIndex) {
        if (count($oppMoves) < 2) return 'unknown';

        $positions = array_slice(array_column($oppMoves, 'to_position'), 0, min($currentIndex + 1, 3));

        if (in_array(4, $positions)) return 'center-focused';

        $corners = [0, 2, 6, 8];
        $cornerCount = count(array_filter($positions, function($p) use ($corners) {
            return in_array($p, $corners);
        }));

        if ($cornerCount >= 2) return 'corner-strategy';

        return 'balanced';
    }

    /**
     * Helper: Calculate threat level from board
     */
    private function calculateThreatFromBoard($board, $oppPositions) {
        $threat = 0;

        $winLines = [
            [0, 1, 2], [3, 4, 5], [6, 7, 8],
            [0, 3, 6], [1, 4, 7], [2, 5, 8],
            [0, 4, 8], [2, 4, 6]
        ];

        foreach ($winLines as $line) {
            $oppCount = 0;
            $emptyCount = 0;

            foreach ($line as $pos) {
                if ($board[$pos] === 'X') $oppCount++;
                elseif ($board[$pos] === null) $emptyCount++;
            }

            if ($oppCount === 2 && $emptyCount === 1) $threat += 4;
            elseif ($oppCount === 2) $threat += 2;
            elseif ($oppCount === 1 && $emptyCount === 2) $threat += 1;
        }

        return min(10, $threat);
    }

    /**
     * Record game outcome for training
     */
    public function recordTrainingData($sessionId, $outcome, $totalMoves, $duration, $playerRating) {
        $stmt = $this->conn->prepare("
            INSERT INTO ai_training_data
            (session_id, game_outcome, difficulty_level, total_moves, game_duration_seconds, player_rating)
            VALUES (?, ?, ?, ?, ?, ?)
        ");

        return $stmt->execute([
            $sessionId,
            $outcome,
            $this->difficulty,
            $totalMoves,
            $duration,
            $playerRating
        ]);
    }
}
