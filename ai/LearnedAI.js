/**
 * Learned AI - Uses machine learning data to make better decisions
 * This AI improves over time by analyzing historical game data
 */

class LearnedAI {
    constructor(difficulty = 'hard') {
        this.difficulty = difficulty;
        this.strategyWeights = null;
        this.patterns = null;
        this.loaded = false;
        this.lastReasoning = '';
    }

    /**
     * Log AI reasoning for transparency
     */
    logReasoning(message) {
        this.lastReasoning = message;
        console.log(`[AI Reasoning] ${message}`);

        // Dispatch event so UI can display it
        if (typeof window !== 'undefined') {
            window.dispatchEvent(new CustomEvent('ai-reasoning', { detail: { message } }));
        }
    }

    /**
     * Get last reasoning message
     */
    getLastReasoning() {
        return this.lastReasoning;
    }

    /**
     * Load learned strategy from server
     */
    async loadStrategy() {
        try {
            const response = await fetch(`api/ai-stats.php?difficulty=${this.difficulty}`);
            const data = await response.json();

            if (data.success && data.learned_strategy) {
                this.strategyWeights = data.learned_strategy.weights.position_weights;
                this.patterns = data.learned_strategy.patterns;
                this.loaded = true;
                console.log(`Loaded AI strategy for ${this.difficulty} difficulty`, data.learned_strategy.stats);
                return true;
            }
        } catch (error) {
            console.error('Failed to load AI strategy:', error);
        }

        this.loaded = false;
        return false;
    }

    /**
     * Get best placement move using learned weights
     */
    getBestPlacement(board, placedCount) {
        const emptyPositions = board
            .map((cell, index) => cell === null ? index : null)
            .filter(index => index !== null);

        if (emptyPositions.length === 0) return -1;

        // If we have learned weights, use them
        if (this.loaded && this.strategyWeights) {
            return this.getWeightedPlacement(board, emptyPositions, placedCount);
        }

        // Fallback to standard AI logic
        return this.getStandardPlacement(board, emptyPositions);
    }

    /**
     * Use learned position weights to choose placement
     */
    getWeightedPlacement(board, emptyPositions, placedCount) {
        // First, check for winning moves
        const winMove = this.findWinningPlacement(board, 'O');
        if (winMove !== -1) {
            this.logReasoning(`🎯 Winning move detected at position ${winMove}`);
            return winMove;
        }

        // Second, block player's winning moves
        const blockMove = this.findWinningPlacement(board, 'X');
        if (blockMove !== -1) {
            this.logReasoning(`🛡️ Blocking opponent's winning move at position ${blockMove}`);
            return blockMove;
        }

        // Use learned opening patterns for first placement
        if (placedCount.O === 0 && this.patterns && this.patterns.opening_moves.length > 0) {
            const commonOpening = this.getMostCommonOpening();
            if (commonOpening && board[commonOpening.positions[0]] === null) {
                this.logReasoning(`📚 Using learned opening pattern: position ${commonOpening.positions[0]} (from ${commonOpening.count || 1} winning games)`);
                return commonOpening.positions[0];
            }
        }

        // Use weighted selection for other placements
        let bestScore = -1;
        let bestMove = -1;
        let positionScores = {};

        emptyPositions.forEach(pos => {
            let baseWeight = this.strategyWeights[pos] || 1.0;
            let score = baseWeight;

            // Bonus for center
            if (pos === 4) score *= 1.5;

            // Bonus for corners
            if ([0, 2, 6, 8].includes(pos)) score *= 1.2;

            // Add randomness to avoid predictability
            score *= (0.8 + Math.random() * 0.4);

            positionScores[pos] = { score, baseWeight };

            if (score > bestScore) {
                bestScore = score;
                bestMove = pos;
            }
        });

        const chosenWeight = positionScores[bestMove].baseWeight;
        this.logReasoning(`🧠 Strategic placement at position ${bestMove} (learned weight: ${chosenWeight.toFixed(2)}, score: ${bestScore.toFixed(2)})`);

        return bestMove;
    }

    /**
     * Get most common winning opening pattern
     */
    getMostCommonOpening() {
        if (!this.patterns || !this.patterns.opening_moves) return null;

        const openingMap = {};

        this.patterns.opening_moves.forEach(opening => {
            const key = opening.positions.join(',');
            if (!openingMap[key]) {
                openingMap[key] = { count: 0, pattern: opening };
            }
            openingMap[key].count++;
        });

        let bestOpening = null;
        let maxCount = 0;

        Object.values(openingMap).forEach(entry => {
            if (entry.count > maxCount) {
                maxCount = entry.count;
                bestOpening = { ...entry.pattern, count: entry.count };
            }
        });

        return bestOpening;
    }

    /**
     * Find winning placement move
     */
    findWinningPlacement(board, player) {
        const winLines = [
            [0, 1, 2], [3, 4, 5], [6, 7, 8], // Rows
            [0, 3, 6], [1, 4, 7], [2, 5, 8], // Columns
            [0, 4, 8], [2, 4, 6]             // Diagonals
        ];

        for (let line of winLines) {
            const values = line.map(i => board[i]);
            const playerCount = values.filter(v => v === player).length;
            const emptyCount = values.filter(v => v === null).length;

            if (playerCount === 2 && emptyCount === 1) {
                return line[values.indexOf(null)];
            }
        }

        return -1;
    }

    /**
     * Standard placement logic (fallback)
     */
    getStandardPlacement(board, emptyPositions) {
        // Priority: Center > Corners > Edges
        if (board[4] === null) return 4;

        const corners = [0, 2, 6, 8].filter(pos => board[pos] === null);
        if (corners.length > 0) {
            return corners[Math.floor(Math.random() * corners.length)];
        }

        return emptyPositions[Math.floor(Math.random() * emptyPositions.length)];
    }

    /**
     * Get best movement move using learned patterns
     */
    getBestMovement(board, player) {
        const playerPieces = board
            .map((cell, index) => cell === player ? index : null)
            .filter(index => index !== null);

        // Try winning moves first
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

        // Try blocking moves
        const opponentPlayer = player === 'X' ? 'O' : 'X';
        const opponentPieces = board
            .map((cell, index) => cell === opponentPlayer ? index : null)
            .filter(index => index !== null);

        for (let from of opponentPieces) {
            const adjacents = this.getAdjacentPositions(from);
            for (let to of adjacents) {
                if (board[to] === null) {
                    const tempBoard = [...board];
                    tempBoard[from] = null;
                    tempBoard[to] = opponentPlayer;

                    if (this.checkWin(tempBoard, opponentPlayer)) {
                        // Block this move with our piece
                        for (let myFrom of playerPieces) {
                            if (this.getAdjacentPositions(myFrom).includes(to)) {
                                return { from: myFrom, to };
                            }
                        }
                    }
                }
            }
        }

        // Use learned patterns if available
        if (this.loaded && this.patterns && this.patterns.winning_sequences) {
            const move = this.findPatternMove(board, playerPieces);
            if (move) return move;
        }

        // Random strategic move
        return this.getRandomMove(board, playerPieces);
    }

    /**
     * Find move matching learned winning patterns
     */
    findPatternMove(board, playerPieces) {
        // Simplified pattern matching - use position weights
        let bestScore = -1;
        let bestMove = null;

        for (let from of playerPieces) {
            const adjacents = this.getAdjacentPositions(from);
            for (let to of adjacents) {
                if (board[to] === null) {
                    const score = (this.strategyWeights && this.strategyWeights[to]) || 1.0;
                    if (score > bestScore) {
                        bestScore = score;
                        bestMove = { from, to };
                    }
                }
            }
        }

        return bestMove;
    }

    /**
     * Get random valid move
     */
    getRandomMove(board, playerPieces) {
        const validMoves = [];

        for (let from of playerPieces) {
            const adjacents = this.getAdjacentPositions(from);
            for (let to of adjacents) {
                if (board[to] === null) {
                    validMoves.push({ from, to });
                }
            }
        }

        if (validMoves.length === 0) return null;
        return validMoves[Math.floor(Math.random() * validMoves.length)];
    }

    /**
     * Get adjacent positions for a given position
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
     * Check if a player has won
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
}

// Make available globally
window.LearnedAI = LearnedAI;
