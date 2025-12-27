<?php

/**
 * AI Learning Engine
 * Analyzes game data and extracts patterns to improve AI performance
 */
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

        return $learnedData;
    }

    /**
     * Load learned strategy from cache
     */
    public static function loadLearnedStrategy($difficulty = 'hard') {
        $cacheFile = __DIR__ . "/../cache/ai_strategy_{$difficulty}.json";

        if (file_exists($cacheFile)) {
            $data = file_get_contents($cacheFile);
            return json_decode($data, true);
        }

        return null;
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
