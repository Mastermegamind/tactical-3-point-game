/**
 * DeepSeek LLM-Powered AI
 * Uses DeepSeek API to make intelligent game decisions
 */

class DeepSeekAI {
    constructor(apiKey) {
        this.apiKey = apiKey;
        this.apiEndpoint = 'https://api.deepseek.com/v1/chat/completions';
        this.model = 'deepseek-chat'; // or 'deepseek-reasoner' for deeper thinking
    }

    /**
     * Get best move from DeepSeek
     * @param {Array} board - Current board state (9 positions)
     * @param {Object} gameState - Full game state
     * @param {string} playerSide - 'X' or 'O'
     * @returns {Promise<Object>} - {type: 'placement'|'movement', position: number, from?: number, reasoning: string}
     */
    async getBestMove(board, gameState, playerSide) {
        const prompt = this.buildPrompt(board, gameState, playerSide);

        try {
            const response = await fetch(this.apiEndpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': `Bearer ${this.apiKey}`
                },
                body: JSON.stringify({
                    model: this.model,
                    messages: [
                        {
                            role: 'system',
                            content: 'You are an expert Tactical Pebble Game AI. You analyze board positions and suggest optimal moves. Always respond with valid JSON.'
                        },
                        {
                            role: 'user',
                            content: prompt
                        }
                    ],
                    temperature: 0.3, // Lower = more consistent/strategic
                    max_tokens: 500,
                    response_format: { type: 'json_object' }
                })
            });

            const data = await response.json();

            if (!data.choices || !data.choices[0]) {
                throw new Error('Invalid API response');
            }

            const llmResponse = JSON.parse(data.choices[0].message.content);

            // Validate the move
            if (this.validateMove(llmResponse, board, gameState, playerSide)) {
                return llmResponse;
            } else {
                console.warn('LLM suggested invalid move, falling back to AdvancedAI');
                return null; // Fallback will be used
            }

        } catch (error) {
            console.error('DeepSeek API error:', error);
            return null; // Fallback to AdvancedAI
        }
    }

    /**
     * Build comprehensive prompt for LLM
     */
    buildPrompt(board, gameState, playerSide) {
        const { phase, placedCount, turn } = gameState;
        const opponentSide = playerSide === 'X' ? 'O' : 'X';

        // Convert board to visual representation
        const boardVisual = this.visualizeBoard(board);

        const prompt = `
You are playing Tactical Pebble Game (strategic tic-tac-toe variant).

**GAME RULES:**
1. Two phases: PLACEMENT then MOVEMENT
2. Placement: Players alternate placing 3 pebbles each
3. Movement: Players alternate moving pebbles to adjacent empty spots (horizontal/vertical, NOT diagonal)
4. Goal: Get 3 in a row (horizontal, vertical, or diagonal)

**CURRENT STATE:**
Phase: ${phase}
Your Side: ${playerSide}
Opponent: ${opponentSide}
Placed Count: You have ${placedCount[playerSide]}/3, Opponent has ${placedCount[opponentSide]}/3

**BOARD POSITIONS:**
Board uses positions 0-8:
0 | 1 | 2
---------
3 | 4 | 5
---------
6 | 7 | 8

**CURRENT BOARD:**
${boardVisual}

**WINNING LINES:**
[0,1,2], [3,4,5], [6,7,8] (rows)
[0,3,6], [1,4,7], [2,5,8] (columns)
[0,4,8], [2,4,6] (diagonals)

**STRATEGIC PRIORITIES:**
1. WIN: If you can complete 3-in-a-row, do it immediately
2. BLOCK: If opponent can win next turn, block them
3. CENTER CONTROL: Position 4 (center) is most valuable
4. CORNER CONTROL: Positions 0,2,6,8 create multiple threats
5. FORK CREATION: Create two winning threats at once
6. PATTERN RECOGNITION: Look for proven winning sequences

${phase === 'placement' ? this.getPlacementInstructions(board, placedCount, playerSide) : this.getMovementInstructions(board, playerSide)}

**REQUIRED JSON RESPONSE FORMAT:**
{
    "move_type": "${phase === 'placement' ? 'placement' : 'movement'}",
    "to_position": <number 0-8>,
    ${phase === 'movement' ? '"from_position": <number 0-8>,' : ''}
    "reasoning": "Brief explanation of strategic thinking",
    "confidence": <0-100>
}

Analyze the board deeply and respond with the best move.
`;

        return prompt.trim();
    }

    getPlacementInstructions(board, placedCount, playerSide) {
        return `
**PLACEMENT PHASE STRATEGY:**
- You have ${3 - placedCount[playerSide]} pebbles left to place
- Choose an EMPTY position (null in board array)
- Prioritize: Center(4) > Corners(0,2,6,8) > Edges(1,3,5,7)
- Look for immediate winning moves or blocks
`;
    }

    getMovementInstructions(board, playerSide) {
        const myPositions = [];
        board.forEach((cell, idx) => {
            if (cell === playerSide) myPositions.push(idx);
        });

        return `
**MOVEMENT PHASE STRATEGY:**
- Your pebbles are at positions: ${myPositions.join(', ')}
- Select one of YOUR pebbles and move to adjacent empty spot
- Adjacent means: directly connected horizontally or vertically
- Adjacency map:
  0→[1,3], 1→[0,2,4], 2→[1,5]
  3→[0,4,6], 4→[1,3,5,7], 5→[2,4,8]
  6→[3,7], 7→[4,6,8], 8→[5,7]
- Look for moves that create multiple winning threats
`;
    }

    visualizeBoard(board) {
        const display = board.map(cell => cell || '·');
        return `
 ${display[0]} | ${display[1]} | ${display[2]}
-----------
 ${display[3]} | ${display[4]} | ${display[5]}
-----------
 ${display[6]} | ${display[7]} | ${display[8]}
`;
    }

    /**
     * Validate LLM suggested move
     */
    validateMove(move, board, gameState, playerSide) {
        if (!move || typeof move !== 'object') return false;

        const { phase } = gameState;
        const to = move.to_position;

        // Basic validation
        if (to < 0 || to > 8) return false;
        if (board[to] !== null) return false; // Destination must be empty

        if (phase === 'placement') {
            return move.move_type === 'placement';
        } else {
            // Movement phase
            if (move.move_type !== 'movement') return false;

            const from = move.from_position;
            if (from < 0 || from > 8) return false;
            if (board[from] !== playerSide) return false; // Must be your pebble

            // Check adjacency
            return this.isAdjacent(from, to);
        }
    }

    isAdjacent(from, to) {
        const adjacencyMap = {
            0: [1, 3],
            1: [0, 2, 4],
            2: [1, 5],
            3: [0, 4, 6],
            4: [1, 3, 5, 7],
            5: [2, 4, 8],
            6: [3, 7],
            7: [4, 6, 8],
            8: [5, 7]
        };
        return adjacencyMap[from]?.includes(to) || false;
    }
}

// Export for use in game
if (typeof module !== 'undefined' && module.exports) {
    module.exports = DeepSeekAI;
}
