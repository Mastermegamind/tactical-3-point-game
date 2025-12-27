# Tactical Pebble Game - Complete Documentation

## Overview
A strategic two-player tic-tac-toe variant where players place and move pebbles on a 3x3 grid. The game has two distinct phases: placement and movement.

---

## Game Rules

### Objective
Get three of your pebbles in a row (horizontally, vertically, or diagonally) to win.

### Players
- **Player 1 (X)**: Blue pebbles
- **Player 2 (O)**: Pink pebbles

### Game Phases

#### **Phase 1: Placement Phase**
1. Players **alternate** placing one pebble at a time on any empty position
2. Each player places **exactly 3 pebbles total**
3. Turn order: X → O → X → O → X → O
4. Once a position is filled, it cannot be changed during this phase
5. **Special Rule**: If one player finishes placing all 3 pebbles first, the other player continues until they also place 3 pebbles
6. After both players have placed 3 pebbles, the game transitions to Movement Phase

#### **Phase 2: Movement Phase**
1. Players **alternate** moving one of their pebbles
2. On your turn, select one of your pebbles and move it to an **adjacent empty position**
3. Adjacent means: horizontally or vertically connected (NOT diagonal)
4. You can only move your own pebbles
5. Continue until someone wins or the game is abandoned

### Winning Conditions
- Get 3 of your pebbles in a row at **any time** (during placement or movement)
- Winning lines: 3 horizontally, 3 vertically, or 3 diagonally

---

## Game Modes

### 1. **Player vs Player (PvP)**
- Two human players compete in real-time
- Real-time synchronization every 1 second
- Features: chat, rematch, online status indicators

### 2. **Player vs Computer (PvC)**
Three AI difficulty levels:
- **Easy**: Random moves with basic strategy
- **Medium**: Evaluates positions with pattern recognition
- **Hard**: Advanced AI using learned patterns and strategic planning

### 3. **Challenge System**
- Send challenges to specific players
- Challenges expire after a set time (default: 5 minutes)
- Accept/Reject/Cancel options
- Creates a new game session when accepted

### 4. **Rematch System**
- After a game ends, request a rematch
- Opponent has limited time to accept/reject
- Creates a new game session if accepted
- Both players must agree

---

## Technical Architecture

### Database Schema

#### **game_sessions**
Stores active and completed games.
```sql
- id: Primary key
- player1_id: User ID of Player X (always goes first)
- player2_id: User ID of Player O (NULL for AI games)
- game_mode: 'pvp', 'pvc-easy', 'pvc-medium', 'pvc-hard'
- board_state: JSON object containing:
  {
    board: [9 positions], // null or 'X' or 'O'
    placedCount: {X: int, O: int},
    phase: 'placement' or 'movement',
    turn: 'X' or 'O',
    currentMovingPlayer: 'X' or 'O',
    gameOver: boolean
  }
- status: 'active', 'completed', 'abandoned'
- winner_id: User ID of winner (NULL for draw or AI win)
- created_at, started_at, completed_at
```

#### **game_challenges**
```sql
- id: Primary key
- challenger_id: User who sent the challenge
- challenged_id: User who received the challenge
- game_mode: Mode for the game
- status: 'pending', 'accepted', 'rejected', 'cancelled', 'expired'
- session_id: NULL until accepted, then links to game_sessions
- expires_at: Challenge expiration timestamp
- created_at, responded_at
```

#### **rematch_requests**
```sql
- id: Primary key
- original_session_id: The game that just ended
- requester_id: Who wants the rematch
- recipient_id: Who receives the request
- status: 'pending', 'accepted', 'rejected', 'expired'
- new_session_id: NULL until accepted, then links to new game
- expires_at: Expiration timestamp
- created_at, responded_at
```

#### **game_moves**
Records every move for history and AI training.
```sql
- id: Primary key
- session_id: Which game
- move_number: Sequential move counter
- player: 'X' or 'O'
- move_type: 'placement' or 'movement'
- from_position: NULL for placement, 0-8 for movement
- to_position: 0-8 (board position)
- board_state_before: JSON snapshot before move
- board_state_after: JSON snapshot after move
- timestamp, think_time_ms
```

#### **game_chat_messages**
```sql
- id, session_id, user_id, message
- created_at
```

---

## Game Logic Flow

### Starting a New Game

**PvP Online:**
1. Player A sends challenge to Player B
2. Challenge inserted into `game_challenges` with status='pending'
3. Player B accepts → Creates NEW `game_sessions` record
4. `game_challenges.session_id` updated with new session ID
5. Both players redirected to `play.php?session={new_id}`

**PvC (vs AI):**
1. Player selects difficulty and clicks "Play vs Computer"
2. Creates `game_sessions` with player2_id=NULL
3. Redirect to `play.php?session={id}`

### Turn Management

**Placement Phase:**
```javascript
// Current player clicks empty position
if (turn !== PLAYER_SIDE && IS_ONLINE) {
    return; // Not your turn
}

if (placedCount[turn] >= 3) {
    return; // Already placed all 3
}

// Place pebble
board[position] = turn;
placedCount[turn]++;

// Check for immediate win
if (checkWinner()) {
    endGame(winner);
    return;
}

// Determine next turn
if (placedCount.X === 3 && placedCount.O < 3) {
    turn = 'O'; // X finished, O still needs to place
} else if (placedCount.O === 3 && placedCount.X < 3) {
    turn = 'X'; // O finished, X still needs to place
} else {
    turn = (turn === 'X') ? 'O' : 'X'; // Normal alternation
}

// Check if both finished
if (placedCount.X === 3 && placedCount.O === 3) {
    phase = 'movement';
    turn = 'X'; // X always goes first in movement
}

saveGameState();
```

**Movement Phase:**
```javascript
// First click: Select your pebble
if (selectedFrom === null) {
    if (board[clickedId] !== PLAYER_SIDE) return;
    selectedFrom = clickedId;
    return;
}

// Second click: Move to adjacent empty position
if (board[clickedId] !== null) {
    // Clicked another pebble - change selection
    selectedFrom = clickedId;
    return;
}

if (!isAdjacent(selectedFrom, clickedId)) {
    return; // Invalid move - not adjacent
}

// Execute move
board[clickedId] = board[selectedFrom];
board[selectedFrom] = null;
selectedFrom = null;

// Check win
if (checkWinner()) {
    endGame(winner);
    return;
}

// Switch turns
turn = (turn === 'X') ? 'O' : 'X';
saveGameState();
```

### Real-Time Synchronization (PvP)

**Debounce System:**
```javascript
let lastLocalMoveTime = 0;
const SYNC_DEBOUNCE_MS = 2000;

// When player makes a move
handlePlacement/handleMovement() {
    lastLocalMoveTime = Date.now();
    // Make move locally
    renderMarks();
    updateUI();
    await saveGameState(); // Save to database
}

// Polling every 1 second
setInterval(async () => {
    const timeSinceLastMove = Date.now() - lastLocalMoveTime;

    if (timeSinceLastMove < SYNC_DEBOUNCE_MS) {
        return; // Skip sync if we just moved (prevent overwriting our own move)
    }

    const data = await fetch(`api/get-game-state.php?session_id=${SESSION_ID}`);

    if (boardChanged || isOpponentMove) {
        // Update from database
        board = state.board;
        placedCount = state.placedCount;
        turn = state.turn;
        phase = state.phase;
        renderMarks();
        updateUI();
    }
}, 1000);
```

**Why Debounce:**
- Player A makes a move at T=0
- Move saves to database at T=0.1
- Sync polls at T=1 but skips (within 2-second debounce)
- Player B's sync picks up the change at T=1
- Player A's sync can resume at T=2+

### AI Logic (PvC)

**When AI Makes a Move:**
1. Check if it's AI's turn (`turn === 'O'` and not online)
2. Call appropriate AI based on difficulty:
   - Easy: `makeRandomMove()`
   - Medium: `AdvancedAI.makeMove()`
   - Hard: `LearnedAI.makeMove()` (uses training data)
3. AI evaluates board and returns move
4. Execute move using same `handlePlacement()` or `handleMovement()`
5. AI moves are also saved to `game_moves` for training

**AI Training Data:**
- Stored in `ai_training_data` table
- Records: game outcome, difficulty, total moves, duration, player rating
- LearnedAI uses this to improve strategy

---

## API Endpoints

### Game Management
- **GET** `api/get-game-state.php?session_id={id}` - Get current board state
- **POST** `api/save-game-state.php` - Save board state to database
- **POST** `api/complete-game.php` - Mark game as completed, update stats
- **POST** `api/abandon-game.php` - Abandon active game (counts as loss)

### Challenges
- **POST** `api/send-challenge.php` - Send challenge to user
- **POST** `api/respond-challenge.php` - Accept/reject challenge
- **POST** `api/cancel-challenge.php` - Cancel sent challenge
- **GET** `api/get-challenge-counts.php` - Get incoming/outgoing counts

### Rematch
- **POST** `api/request-rematch.php` - Request rematch after game
- **POST** `api/respond-rematch.php` - Accept/reject rematch
- **GET** `api/check-rematch-status.php` - Poll for rematch response
- **GET** `api/check-incoming-rematch.php` - Check for incoming rematch

### Chat
- **POST** `api/send-chat-message.php` - Send message
- **GET** `api/get-chat-messages.php?session_id={id}&after={last_id}` - Get new messages

### User Management
- **POST** `api/update-activity.php` - Update last_activity timestamp
- **GET** `api/check-opponent-status.php?session_id={id}` - Check if opponent online

---

## File Structure

```
/var/www/game.test/
├── index.php              # Landing page
├── login.php              # Authentication
├── register.php           # User registration
├── dashboard.php          # User dashboard (stats, challenges, games)
├── lobby.php              # Game lobby (challenge players, matchmaking)
├── play.php               # Main game interface
│
├── game-engine.js         # Core game logic (placement, movement, rendering)
├── ai/
│   ├── AdvancedAI.js      # Medium difficulty AI
│   └── LearnedAI.js       # Hard difficulty AI (uses training data)
│
├── api/                   # All backend endpoints
│   ├── send-challenge.php
│   ├── respond-challenge.php
│   ├── request-rematch.php
│   ├── respond-rematch.php
│   ├── get-game-state.php
│   ├── save-game-state.php
│   ├── complete-game.php
│   └── ...
│
├── config/
│   ├── database.php       # PDO database singleton
│   ├── session.php        # Session management
│   └── RedisManager.php   # Redis caching (optional)
│
└── database/
    └── game.sql           # Database schema
```

---

## Key Features

### 1. **Session Persistence**
- Games can be paused and resumed
- State saved after every move
- `loadGameState()` restores full game state on page load

### 2. **Online Status**
- Green dot = online (active within 2 minutes)
- Gray dot = offline
- Updates every 10 seconds

### 3. **Real-Time Chat**
- Polls every 1 second for new messages
- Incremental loading (only messages after last ID)
- Character limit: 200 chars
- HTML escaped for security

### 4. **Challenge Management Dashboard**
Six tabs:
- **Incoming**: Challenges sent to you (Accept/Decline)
- **Sent**: Challenges you sent (Cancel)
- **Accepted**: Shows session IDs, link to active games
- **Rejected**: Historical record
- **Cancelled**: Historical record
- **Expired**: Challenges that timed out

### 5. **Game End Logic**
```javascript
async function endGame(winnerSymbol) {
    gameOver = true;

    // Calculate winner ID
    let winnerId;
    if (IS_ONLINE) {
        winnerId = (winnerSymbol === PLAYER_SIDE) ? USER_ID : OPPONENT_ID;
    } else if (PvC) {
        winnerId = (winnerSymbol === 'X') ? USER_ID : null; // null = AI won
    }

    // Save to database
    await fetch('api/complete-game.php', {
        method: 'POST',
        body: JSON.stringify({
            session_id: SESSION_ID,
            winner_id: winnerId
        })
    });

    // Show modal
    const isPlayerWin = (winnerSymbol === PLAYER_SIDE);
    showGameResultModal(winnerSymbol, isPlayerWin, winnerName);
}
```

**Both players detect game end:**
- Winner's client calls `endGame()` immediately
- Database updated with `status='completed'`, `winner_id={id}`
- Loser's 1-second polling detects `status='completed'`
- Loser calculates they lost: `data.winner_id !== USER_ID`
- Loser shows "😔 Defeat" modal, Winner shows "🎉 Victory!" modal

### 6. **Rating System**
- Win: +25 rating
- Loss: -10 rating
- Draw: No change
- Updated in `complete-game.php`

---

## Common Scenarios

### Scenario 1: Player vs Player Game
1. Player A challenges Player B from lobby
2. Challenge created with 5-minute expiration
3. Player B sees notification and dashboard alert
4. Player B clicks "Accept"
5. **NEW** game_sessions record created (session_id = 64)
6. Both players redirected to play.php?session=64
7. Players alternate placing 3 pebbles each
8. Phase transitions to movement
9. Players alternate moving pebbles
10. Player A gets 3 in a row
11. Player A's client calls endGame('X')
12. Database updated: winner_id = Player A's ID
13. Player B's sync detects completion, shows defeat modal
14. Player A shows victory modal with rematch option

### Scenario 2: Rematch Flow
1. Game 64 ends (Player A wins)
2. Player A clicks "Rematch" button
3. rematch_requests created (original_session_id=64, status='pending')
4. Notification sent to Player B
5. Player B sees rematch request (polling or notification)
6. Player B clicks "Accept"
7. **NEW** game_sessions record created (session_id = 65)
8. rematch_requests.new_session_id = 65
9. Both players redirected to play.php?session=65
10. Fresh game starts

### Scenario 3: One Player Finishes Placement Early
1. Placement: X places at 4, O places at 6
2. Placement: X places at 7, O places at 0
3. Placement: X places at 2 (X now has 3 pebbles)
4. Turn logic: X=3, O=2 → turn forced to 'O'
5. O clicks position 5 (O now has 3 pebbles)
6. Both have 3: phase = 'movement', turn = 'X'
7. Movement phase begins

---

## Error Handling

### Turn Validation
```javascript
if (IS_ONLINE && phase === 'placement') {
    if (turn !== PLAYER_SIDE) {
        statusText.textContent = "Wait for opponent's turn";
        return;
    }
    if (placedCount[PLAYER_SIDE] >= 3) {
        statusText.textContent = "Waiting for opponent to finish placement";
        return;
    }
}
```

### Challenge Expiration
- Checked on respond-challenge.php (line 49-55)
- If expired, status updated to 'expired'
- Returns error message

### Network Failures
- All API calls wrapped in try/catch
- User shown error alerts
- Game state preserved (can refresh to retry)

---

## Performance Optimizations

### Redis Caching (Optional)
- Game state cached with key: `game:session:{id}`
- User stats cached: `user:stats:{id}`
- Leaderboard cached: `leaderboard:top_players`
- Cache invalidated on state changes

### Database Indexing
- Primary keys on all tables
- Indexes on foreign keys (player1_id, player2_id, session_id)
- Index on status for active game queries
- Index on expires_at for challenge cleanup

---

## Summary for AI Assistant

**Quick Start Understanding:**

1. **Game**: Tic-tac-toe variant with placement phase (place 3 pebbles alternating) then movement phase (move 1 pebble to adjacent spot)

2. **Database Source of Truth**: Always use database board_state, not local variables

3. **Turn Logic Key Rule**: If one player finishes placement early, force turn to the player who hasn't finished yet

4. **New Sessions Always**: Accepting challenge or rematch creates brand NEW game_sessions record

5. **Real-time Sync**: 1-second polling with 2-second debounce after own moves prevents conflicts

6. **End Game**: Both players detect via different paths - winner calls endGame(), loser detects via polling status='completed'

7. **Session ID Tracking**: Every challenge state (pending, accepted, rejected, cancelled, expired) can have associated session_id for history

**Most Important Files:**
- `game-engine.js` - Core game logic
- `play.php` - Game interface with real-time sync
- `api/respond-challenge.php` - Challenge acceptance (creates new session)
- `api/complete-game.php` - Game completion and stats update
- `dashboard.php` - Challenge management UI
