/**
 * Advanced AI Engine - Real-time board analysis and strategic decision making
 * Analyzes current board state, opponent patterns, and saves winning strategies
 */

class AdvancedAI {
    constructor(sessionId, difficulty = 'medium') {
        this.sessionId = sessionId;
        this.difficulty = difficulty;
        this.strategies = [];
        this.opponentMoves = [];
        this.aiMoves = [];
        this.currentPhase = 'placement';
        this.loaded = false;
    }

    /**
     * Load strategies from database
     */
    async loadStrategies() {
        try {
            const response = await fetch(`api/ai-get-strategies.php?difficulty=${this.difficulty}`);
            const data = await response.json();

            if (data.success && data.strategies) {
                this.strategies = data.strategies;
                this.loaded = true;
                console.log(`✅ Loaded ${this.strategies.length} strategies for ${this.difficulty} difficulty`);
                return true;
            }
        } catch (error) {
            console.error('Failed to load strategies:', error);
        }

        this.loaded = false;
        return false;
    }

    /**
     * Analyze current board state in real-time
     */
    analyzeBoardState(board, placedCount, phase) {
        const aiPositions = [];
        const opponentPositions = [];
        const emptyPositions = [];

        // Extract all piece positions
        board.forEach((cell, index) => {
            if (cell === 'O') aiPositions.push(index);
            else if (cell === 'X') opponentPositions.push(index);
            else if (cell === null) emptyPositions.push(index);
        });

        // Evaluate board strength
        const boardScore = this.evaluateBoardStrength(board, aiPositions, opponentPositions);

        // Detect opponent patterns
        const opponentPattern = this.detectOpponentPattern();

        // Calculate threat level
        const threatLevel = this.calculateThreatLevel(board, opponentPositions);

        // Determine game phase
        this.currentPhase = this.determineGamePhase(placedCount, phase);

        return {
            board: [...board],
            aiPositions,
            opponentPositions,
            emptyPositions,
            boardScore,
            opponentPattern,
            threatLevel,
            phase: this.currentPhase,
            aiPieceCount: aiPositions.length,
            opponentPieceCount: opponentPositions.length
        };
    }

    /**
     * Evaluate board strength score (-100 to +100)
     * Positive = AI advantage, Negative = Opponent advantage
     */
    evaluateBoardStrength(board, aiPositions, opponentPositions) {
        let score = 0;

        const winLines = [
            [0, 1, 2], [3, 4, 5], [6, 7, 8], // Rows
            [0, 3, 6], [1, 4, 7], [2, 5, 8], // Columns
            [0, 4, 8], [2, 4, 6]             // Diagonals
        ];

        winLines.forEach(line => {
            const aiCount = line.filter(pos => board[pos] === 'O').length;
            const oppCount = line.filter(pos => board[pos] === 'X').length;
            const emptyCount = line.filter(pos => board[pos] === null).length;

            // AI about to win
            if (aiCount === 2 && emptyCount === 1) score += 50;
            // Opponent about to win (threat)
            if (oppCount === 2 && emptyCount === 1) score -= 60;
            // AI has 2 in a line
            if (aiCount === 2 && oppCount === 0) score += 20;
            // Opponent has 2 in a line
            if (oppCount === 2 && aiCount === 0) score -= 25;
            // AI has 1 in a line
            if (aiCount === 1 && oppCount === 0 && emptyCount === 2) score += 5;
            // Opponent has 1 in a line
            if (oppCount === 1 && aiCount === 0 && emptyCount === 2) score -= 5;
        });

        // Center control bonus
        if (board[4] === 'O') score += 10;
        if (board[4] === 'X') score -= 10;

        // Corner control
        const corners = [0, 2, 6, 8];
        corners.forEach(pos => {
            if (board[pos] === 'O') score += 5;
            if (board[pos] === 'X') score -= 5;
        });

        return score;
    }

    /**
     * Calculate opponent threat level (0-10)
     */
    calculateThreatLevel(board, opponentPositions) {
        let threat = 0;

        const winLines = [
            [0, 1, 2], [3, 4, 5], [6, 7, 8],
            [0, 3, 6], [1, 4, 7], [2, 5, 8],
            [0, 4, 8], [2, 4, 6]
        ];

        winLines.forEach(line => {
            const oppCount = line.filter(pos => board[pos] === 'X').length;
            const emptyCount = line.filter(pos => board[pos] === null).length;

            if (oppCount === 2 && emptyCount === 1) threat += 4; // Immediate threat
            if (oppCount === 2) threat += 2; // Potential threat
            if (oppCount === 1 && emptyCount === 2) threat += 1; // Building threat
        });

        return Math.min(10, threat);
    }

    /**
     * Detect opponent's playing pattern
     */
    detectOpponentPattern() {
        if (this.opponentMoves.length < 2) return 'unknown';

        const lastMoves = this.opponentMoves.slice(-3);
        const positions = lastMoves.map(m => m.to);

        // Check if opponent prefers center
        if (positions.includes(4)) return 'center-focused';

        // Check if opponent prefers corners
        const corners = [0, 2, 6, 8];
        const cornerMoves = positions.filter(p => corners.includes(p));
        if (cornerMoves.length >= 2) return 'corner-strategy';

        // Check if opponent is aggressive (going for wins)
        if (this.opponentMoves.length >= 3) {
            const hasThreats = this.opponentMoves.slice(-2).some(m => m.createdThreat);
            if (hasThreats) return 'aggressive';
        }

        return 'balanced';
    }

    /**
     * Determine current game phase
     */
    determineGamePhase(placedCount, currentPhase) {
        if (currentPhase === 'placement') return 'placement';

        const totalPieces = placedCount.X + placedCount.O;
        if (totalPieces === 6) return 'movement';

        // Endgame: few pieces or critical positions
        if (this.aiMoves.length + this.opponentMoves.length > 10) return 'endgame';

        return 'movement';
    }

    /**
     * Find best move using real-time board analysis and saved strategies
     */
    async getBestMove(board, placedCount, phase, moveType) {
        // Analyze current state
        const state = this.analyzeBoardState(board, placedCount, phase);

        console.log('📊 Board Analysis:', {
            score: state.boardScore,
            threat: state.threatLevel,
            pattern: state.opponentPattern,
            phase: state.phase
        });

        // Priority 1: Check for winning move
        const winMove = this.findWinningMove(board, 'O', moveType);
        if (winMove) {
            await this.saveStrategy('winning-move', state, winMove.from, winMove.to, moveType, 'offensive', state.boardScore);
            this.logReasoning(`🎯 WINNING MOVE! Position ${winMove.to} (Board Score: +${state.boardScore})`);
            return winMove;
        }

        // Priority 2: Block opponent's winning move
        const blockMove = this.findWinningMove(board, 'X', moveType);
        if (blockMove) {
            await this.saveStrategy('defensive-block', state, blockMove.from, blockMove.to, moveType, 'defensive', state.boardScore);
            this.logReasoning(`🛡️ BLOCKING opponent win at position ${blockMove.to} (Threat Level: ${state.threatLevel}/10)`);
            return this.convertBlockToAIMove(board, blockMove, state);
        }

        // Priority 3: Use saved strategies matching current board state
        const matchingStrategy = this.findMatchingStrategy(state, moveType);
        if (matchingStrategy) {
            this.logReasoning(`📚 Using proven strategy: "${matchingStrategy.strategy_name}" (${matchingStrategy.success_rate}% win rate, used ${matchingStrategy.total_uses} times)`);
            return { from: matchingStrategy.move_from, to: matchingStrategy.move_to };
        }

        // Priority 4: Calculate best strategic move
        const strategicMove = this.calculateBestStrategicMove(state, board, moveType);
        if (strategicMove) {
            await this.saveStrategy('strategic-move', state, strategicMove.from, strategicMove.to, moveType, 'offensive', state.boardScore);
            this.logReasoning(`🧠 Strategic ${moveType} to position ${strategicMove.to} (Score: ${state.boardScore.toFixed(1)}, Pattern: ${state.opponentPattern})`);
            return strategicMove;
        }

        return null;
    }

    /**
     * Find winning move for a player
     */
    findWinningMove(board, player, moveType) {
        const playerPieces = board
            .map((cell, index) => cell === player ? index : null)
            .filter(index => index !== null);

        const winLines = [
            [0, 1, 2], [3, 4, 5], [6, 7, 8],
            [0, 3, 6], [1, 4, 7], [2, 5, 8],
            [0, 4, 8], [2, 4, 6]
        ];

        if (moveType === 'placement') {
            // Check for placement win
            for (let line of winLines) {
                const values = line.map(i => board[i]);
                const playerCount = values.filter(v => v === player).length;
                const emptyCount = values.filter(v => v === null).length;

                if (playerCount === 2 && emptyCount === 1) {
                    const to = line[values.indexOf(null)];
                    return { from: null, to };
                }
            }
        } else {
            // Check for movement win
            for (let from of playerPieces) {
                const adjacents = this.getAdjacentPositions(from);
                for (let to of adjacents) {
                    if (board[to] === null) {
                        const tempBoard = [...board];
                        tempBoard[from] = null;
                        tempBoard[to] = player;

                        if (this.checkWin(tempBoard, player)) {
                            return { from, to };
                        }
                    }
                }
            }
        }

        return null;
    }

    /**
     * Convert opponent's blocking position to AI's best counter-move
     */
    convertBlockToAIMove(board, blockMove, state) {
        // For placement, we need to find our best blocking position
        if (blockMove.from === null) {
            // Find our pieces that can block
            const aiPieces = state.aiPositions;
            const targetPos = blockMove.to;

            // If we're in movement phase, move a piece to block
            if (state.phase !== 'placement') {
                for (let from of aiPieces) {
                    const adjacents = this.getAdjacentPositions(from);
                    if (adjacents.includes(targetPos)) {
                        return { from, to: targetPos };
                    }
                }
            }

            // Placement phase - place at blocking position
            return { from: null, to: targetPos };
        }

        return blockMove;
    }

    /**
     * Find matching strategy from database
     */
    findMatchingStrategy(state, moveType) {
        if (!this.loaded || this.strategies.length === 0) return null;

        // Filter strategies by current context
        const matching = this.strategies.filter(s => {
            return s.move_type === moveType &&
                   s.game_phase === state.phase &&
                   s.difficulty_level === this.difficulty &&
                   s.success_rate > 50 &&
                   Math.abs(s.threat_level - state.threatLevel) <= 2;
        });

        if (matching.length === 0) return null;

        // Sort by priority and success rate
        matching.sort((a, b) => {
            const scoreA = (a.priority_score * 0.6) + (a.success_rate * 0.4);
            const scoreB = (b.priority_score * 0.6) + (b.success_rate * 0.4);
            return scoreB - scoreA;
        });

        return matching[0];
    }

    /**
     * Calculate best strategic move based on board analysis
     */
    calculateBestStrategicMove(state, board, moveType) {
        let bestMove = null;
        let bestScore = -Infinity;

        if (moveType === 'placement') {
            // Evaluate each empty position
            state.emptyPositions.forEach(to => {
                const tempBoard = [...board];
                tempBoard[to] = 'O';
                const score = this.evaluateBoardStrength(tempBoard, [...state.aiPositions, to], state.opponentPositions);

                // Bonus for center and corners
                let positionBonus = 0;
                if (to === 4) positionBonus = 15;
                if ([0, 2, 6, 8].includes(to)) positionBonus = 10;

                const totalScore = score + positionBonus;

                if (totalScore > bestScore) {
                    bestScore = totalScore;
                    bestMove = { from: null, to };
                }
            });
        } else {
            // Evaluate each possible movement
            state.aiPositions.forEach(from => {
                const adjacents = this.getAdjacentPositions(from);
                adjacents.forEach(to => {
                    if (board[to] === null) {
                        const tempBoard = [...board];
                        tempBoard[from] = null;
                        tempBoard[to] = 'O';

                        const newAiPos = state.aiPositions.filter(p => p !== from).concat([to]);
                        const score = this.evaluateBoardStrength(tempBoard, newAiPos, state.opponentPositions);

                        if (score > bestScore) {
                            bestScore = score;
                            bestMove = { from, to };
                        }
                    }
                });
            });
        }

        return bestMove;
    }

    /**
     * Save successful strategy to database
     */
    async saveStrategy(strategyName, state, from, to, moveType, strategyType, boardScore) {
        try {
            const response = await fetch('api/ai-save-strategy.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    session_id: this.sessionId,
                    strategy_name: strategyName,
                    difficulty: this.difficulty,
                    board_state: state.board,
                    ai_pieces: state.aiPositions,
                    opponent_pieces: state.opponentPositions,
                    move_from: from,
                    move_to: to,
                    move_type: moveType,
                    game_phase: state.phase,
                    opponent_pattern: state.opponentPattern,
                    strategy_type: strategyType,
                    board_score: boardScore,
                    threat_level: state.threatLevel
                })
            });

            const data = await response.json();
            if (data.success) {
                console.log(`💾 Strategy saved: ${strategyName}`);
            }
        } catch (error) {
            console.error('Failed to save strategy:', error);
        }
    }

    /**
     * Record a move for pattern analysis
     */
    recordMove(player, from, to, board) {
        const move = {
            player,
            from,
            to,
            timestamp: Date.now(),
            createdThreat: false
        };

        // Check if move created a threat
        const winLines = [
            [0, 1, 2], [3, 4, 5], [6, 7, 8],
            [0, 3, 6], [1, 4, 7], [2, 5, 8],
            [0, 4, 8], [2, 4, 6]
        ];

        winLines.forEach(line => {
            const values = line.map(i => board[i]);
            const playerCount = values.filter(v => v === player).length;
            const emptyCount = values.filter(v => v === null).length;

            if (playerCount === 2 && emptyCount === 1) {
                move.createdThreat = true;
            }
        });

        if (player === 'X') {
            this.opponentMoves.push(move);
        } else {
            this.aiMoves.push(move);
        }
    }

    /**
     * Get adjacent positions for movement
     */
    getAdjacentPositions(pos) {
        const adjacency = {
            0: [1, 3, 4],
            1: [0, 2, 3, 4, 5],
            2: [1, 4, 5],
            3: [0, 1, 4, 6, 7],
            4: [0, 1, 2, 3, 5, 6, 7, 8],
            5: [1, 2, 4, 7, 8],
            6: [3, 4, 7],
            7: [3, 4, 5, 6, 8],
            8: [4, 5, 7]
        };

        return adjacency[pos] || [];
    }

    /**
     * Check if player has won
     */
    checkWin(board, player) {
        const winLines = [
            [0, 1, 2], [3, 4, 5], [6, 7, 8],
            [0, 3, 6], [1, 4, 7], [2, 5, 8],
            [0, 4, 8], [2, 4, 6]
        ];

        return winLines.some(line =>
            line.every(pos => board[pos] === player)
        );
    }

    /**
     * Log AI reasoning
     */
    logReasoning(message) {
        console.log(`[Advanced AI] ${message}`);

        if (typeof window !== 'undefined') {
            window.dispatchEvent(new CustomEvent('ai-reasoning', { detail: { message } }));
        }
    }
}

// Make available globally
window.AdvancedAI = AdvancedAI;
