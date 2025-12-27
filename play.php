<?php
require_once __DIR__ . '/config/session.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/RedisManager.php';

$db = Database::getInstance();
$conn = $db->getConnection();
$redisManager = RedisManager::getInstance();

// Get game mode
$mode = $_GET['mode'] ?? null;
$sessionId = $_GET['session'] ?? null;

$isOnlineGame = false;
$opponent = null;
$playerSide = 'X'; // Default player is X (blue)

if ($sessionId) {
    // Load existing session
    $stmt = $conn->prepare("
        SELECT gs.*,
               u1.username as player1_name, u1.avatar as player1_avatar,
               u2.username as player2_name, u2.avatar as player2_avatar
        FROM game_sessions gs
        LEFT JOIN users u1 ON gs.player1_id = u1.id
        LEFT JOIN users u2 ON gs.player2_id = u2.id
        WHERE gs.id = ? AND (gs.player1_id = ? OR gs.player2_id = ?)
    ");
    $stmt->execute([$sessionId, $_SESSION['user_id'], $_SESSION['user_id']]);
    $session = $stmt->fetch();

    if (!$session) {
        die('Invalid game session');
    }

    $mode = $session['game_mode'];
    $isOnlineGame = $session['player2_id'] !== null && strpos($mode, 'pvp') !== false;
    $playerSide = ($session['player1_id'] == $_SESSION['user_id']) ? 'X' : 'O';

    if ($isOnlineGame) {
        $opponent = [
            'name' => $playerSide === 'X' ? $session['player2_name'] : $session['player1_name'],
            'avatar' => $playerSide === 'X' ? $session['player2_avatar'] : $session['player1_avatar']
        ];
    }
} else {
    // Create new session
    $validModes = ['pvc-easy', 'pvc-medium', 'pvc-hard'];
    if (!in_array($mode, $validModes)) {
        die('Invalid game mode');
    }

    $initialBoard = json_encode([
        'board' => array_fill(0, 9, null),
        'placedCount' => ['X' => 0, 'O' => 0],
        'phase' => 'placement',
        'turn' => 'X'
    ]);

    $stmt = $conn->prepare("
        INSERT INTO game_sessions (player1_id, game_mode, board_state)
        VALUES (?, ?, ?)
    ");
    $stmt->execute([$_SESSION['user_id'], $mode, $initialBoard]);
    $sessionId = $conn->lastInsertId();

    if ($redisManager->isEnabled()) {
        $redisManager->cacheGameState($sessionId, [
            'board_state' => $initialBoard,
            'status' => 'active',
            'winner_id' => null,
            'current_phase' => 'placement',
            'current_turn' => 'X'
        ]);
        $redisManager->trackActiveGame($sessionId, [
            'player1_id' => $_SESSION['user_id'],
            'player2_id' => null,
            'mode' => $mode
        ]);
    }
}

// Get user info
$stmt = $conn->prepare("SELECT username, avatar FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$currentUser = $stmt->fetch();

$presetAvatars = [
    'avatar1.svg' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><circle cx="50" cy="50" r="45" fill="#667eea"/><circle cx="35" cy="40" r="5" fill="#fff"/><circle cx="65" cy="40" r="5" fill="#fff"/><path d="M30 65 Q50 75 70 65" stroke="#fff" stroke-width="3" fill="none"/></svg>',
    'avatar2.svg' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><circle cx="50" cy="50" r="45" fill="#f093fb"/><circle cx="35" cy="40" r="5" fill="#fff"/><circle cx="65" cy="40" r="5" fill="#fff"/><circle cx="50" cy="65" r="8" fill="#fff"/></svg>',
    'avatar3.svg' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><circle cx="50" cy="50" r="45" fill="#4facfe"/><circle cx="35" cy="40" r="5" fill="#fff"/><circle cx="65" cy="40" r="5" fill="#fff"/><rect x="35" y="60" width="30" height="5" fill="#fff"/></svg>',
    'avatar4.svg' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><circle cx="50" cy="50" r="45" fill="#43e97b"/><circle cx="35" cy="40" r="5" fill="#fff"/><circle cx="65" cy="40" r="5" fill="#fff"/><path d="M35 65 L50 60 L65 65" stroke="#fff" stroke-width="3" fill="none"/></svg>',
    'avatar5.svg' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><circle cx="50" cy="50" r="45" fill="#fa709a"/><circle cx="35" cy="40" r="5" fill="#fff"/><circle cx="65" cy="40" r="5" fill="#fff"/><ellipse cx="50" cy="65" rx="15" ry="8" fill="#fff"/></svg>',
    'avatar6.svg' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><circle cx="50" cy="50" r="45" fill="#764ba2"/><circle cx="35" cy="40" r="5" fill="#fff"/><circle cx="65" cy="40" r="5" fill="#fff"/><path d="M30 70 Q50 60 70 70" stroke="#fff" stroke-width="3" fill="none"/></svg>',
];

function renderAvatar($avatar, $presetAvatars) {
    if (isset($presetAvatars[$avatar])) {
        return $presetAvatars[$avatar];
    } elseif ($avatar && file_exists($avatar)) {
        return '<img src="' . htmlspecialchars($avatar) . '" alt="Avatar" style="width:100%;height:100%;border-radius:50%;">';
    }
    return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><circle cx="50" cy="50" r="45" fill="#ccc"/></svg>';
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Playing - Tactical Pebble Game</title>

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">

  <style>
    * {
      font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
    }

    body {
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      min-height: 100vh;
      padding: 1rem;
    }

    .game-container {
      max-width: 1400px;
      margin: 0 auto;
    }

    .game-card {
      background: rgba(255, 255, 255, 0.95);
      backdrop-filter: blur(10px);
      border-radius: 24px;
      box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
      overflow: hidden;
    }

    .game-header {
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      color: white;
      padding: 1.5rem 2rem;
      display: flex;
      justify-content: space-between;
      align-items: center;
    }

    .game-title {
      font-size: 1.5rem;
      font-weight: 700;
      margin: 0;
    }

    .game-controls {
      display: flex;
      gap: 0.5rem;
    }

    .btn-icon {
      background: rgba(255, 255, 255, 0.2);
      border: none;
      border-radius: 8px;
      padding: 0.5rem 1rem;
      color: white;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.2s;
    }

    .btn-icon:hover {
      background: rgba(255, 255, 255, 0.3);
    }

    .controls-panel {
      background: #f8f9fa;
      padding: 2rem;
    }

    .player-info {
      display: flex;
      align-items: center;
      gap: 1rem;
      padding: 1rem;
      background: white;
      border-radius: 12px;
      margin-bottom: 1rem;
    }

    .player-avatar {
      width: 50px;
      height: 50px;
      border-radius: 50%;
      border: 3px solid #667eea;
      padding: 0.4rem;
      background: white;
    }

    .player-avatar svg,
    .player-avatar img {
      width: 100%;
      height: 100%;
      border-radius: 50%;
    }

    .stat-card {
      background: white;
      border-radius: 16px;
      padding: 1.5rem;
      box-shadow: 0 2px 8px rgba(0,0,0,0.06);
      margin-bottom: 1rem;
    }

    .stat-label {
      font-size: 0.85rem;
      color: #6c757d;
      font-weight: 600;
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }

    .stat-value {
      font-size: 1.5rem;
      font-weight: 700;
      color: #212529;
      margin-top: 0.25rem;
    }

    .board-wrap {
      width: 100%;
      max-width: 600px;
      aspect-ratio: 1 / 1;
      margin: 0 auto;
      position: relative;
      padding: 0rem;
    }

    svg {
      width: 100%;
      height: 100%;
      display: block;
      filter: drop-shadow(0 4px 12px rgba(0,0,0,0.1));
    }

    .board-lines {
      stroke: #cbd5e0;
      stroke-width: 2.5;
      stroke-linecap: round;
    }

    .point {
      cursor: pointer;
      transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .point.disabled {
      cursor: not-allowed;
      opacity: 0.5;
    }

    .pebble {
      filter: drop-shadow(0 2px 4px rgba(0,0,0,0.2));
      transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
      cursor: pointer;
      transform-origin: center;
    }

    .pebble:hover {
      transform: scale(1.15);
    }

    .pebble.selected circle:first-of-type {
      stroke: #ef4444 !important;
      stroke-width: 2.5 !important;
      filter: drop-shadow(0 0 8px rgba(239, 68, 68, 0.6));
    }

    .pebble.selected {
      animation: pulse 1s ease-in-out infinite;
    }

    @keyframes pulse {
      0%, 100% { transform: scale(1); }
      50% { transform: scale(1.05); }
    }

    .modal-content {
      border-radius: 16px;
    }
  </style>
</head>
<body>

<div class="game-container">
  <div class="game-card">
    <div class="game-header">
      <h1 class="game-title">Tactical Pebble Game</h1>
      <div class="game-controls">
        <button class="btn-icon" onclick="pauseGame()">⏸ Pause</button>
        <button class="btn-icon" onclick="exitGame()">✕ Exit</button>
      </div>
    </div>

    <div class="row g-0">
      <div class="col-12 col-lg-4 order-2 order-lg-1">
        <div class="controls-panel">

          <div class="player-info">
            <div class="player-avatar">
              <?= renderAvatar($currentUser['avatar'], $presetAvatars) ?>
            </div>
            <div>
              <strong><?= htmlspecialchars($currentUser['username']) ?></strong>
              <div class="text-muted small">You (<?= $playerSide === 'X' ? 'Blue' : 'Pink' ?>)</div>
            </div>
          </div>

          <?php if ($isOnlineGame && $opponent): ?>
          <div class="player-info">
            <div class="player-avatar" style="position: relative;">
              <?= renderAvatar($opponent['avatar'], $presetAvatars) ?>
              <span class="opponent-online-status" id="opponentOnlineStatus" style="position: absolute; bottom: -2px; right: -2px; width: 14px; height: 14px; background: #6c757d; border: 3px solid white; border-radius: 50%; box-shadow: 0 2px 4px rgba(0,0,0,0.2);" title="Checking..."></span>
            </div>
            <div class="flex-grow-1">
              <div class="d-flex align-items-center gap-2">
                <strong><?= htmlspecialchars($opponent['name']) ?></strong>
                <span class="badge bg-secondary-subtle text-secondary-emphasis" id="opponentStatusBadge" style="font-size: 0.7rem;">
                  Checking...
                </span>
              </div>
              <div class="text-muted small">Opponent</div>
            </div>
          </div>
          <?php endif; ?>

          <div class="stat-card">
            <div class="d-flex justify-content-between align-items-center mb-2">
              <div class="stat-label">Current Turn</div>
              <div class="stat-value" id="turnText"><?= $playerSide === 'X' ? htmlspecialchars($currentUser['username']) : ($isOnlineGame && $opponent ? htmlspecialchars($opponent['name']) : 'AI') ?></div>
            </div>
            <div class="mt-3">
              <div class="stat-label">Status</div>
              <div class="stat-value" id="statusText" style="font-size: 1.1rem;">Ready to play</div>
            </div>
          </div>

          <?php if (strpos($mode, 'pvc') !== false): ?>
          <div class="stat-card" id="aiReasoningPanel" style="display:none;">
            <div class="stat-label">🤖 AI Thinking</div>
            <div id="aiReasoningText" style="font-size: 0.9rem; color: #667eea; margin-top: 0.5rem; line-height: 1.4;">
              Analyzing board...
            </div>
          </div>
          <?php endif; ?>

          <?php if ($isOnlineGame): ?>
          <div class="stat-card" id="chatPanel">
            <div class="d-flex justify-content-between align-items-center mb-3">
              <div class="stat-label">💬 Chat</div>
              <button class="btn btn-sm btn-outline-secondary" onclick="toggleChat()" id="chatToggle">
                <svg width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                  <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14zm0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16z"/>
                  <path d="m8.93 6.588-2.29.287-.082.38.45.083c.294.07.352.176.288.469l-.738 3.468c-.194.897.105 1.319.808 1.319.545 0 1.178-.252 1.465-.598l.088-.416c-.2.176-.492.246-.686.246-.275 0-.375-.193-.304-.533L8.93 6.588zM9 4.5a1 1 0 1 1-2 0 1 1 0 0 1 2 0z"/>
                </svg>
              </button>
            </div>
            <div id="chatContainer" style="display: block;">
              <div id="chatMessages" style="height: 250px; overflow-y: auto; background: #f8f9fa; border-radius: 8px; padding: 0.75rem; margin-bottom: 0.75rem; scroll-behavior: smooth;">
                <div class="text-center text-muted small" style="padding: 2rem 0;">
                  No messages yet. Say hello! 👋
                </div>
              </div>
              <form id="chatForm" onsubmit="sendMessage(event)" style="display: flex; gap: 0.5rem;">
                <input
                  type="text"
                  id="chatInput"
                  class="form-control form-control-sm"
                  placeholder="Type a message..."
                  maxlength="200"
                  autocomplete="off"
                  style="flex: 1;"
                >
                <button type="submit" class="btn btn-primary btn-sm" style="min-width: 60px;">
                  Send
                </button>
              </form>
            </div>
          </div>
          <?php endif; ?>

        </div>
      </div>

      <div class="col-12 col-lg-8 order-1 order-lg-2">
        <div class="p-4">
          <div class="board-wrap">
            <svg viewBox="0 0 100 100" aria-label="Game Board">
              <g class="board-lines">
                <rect x="10" y="10" width="80" height="80" fill="none"/>
                <line x1="50" y1="10" x2="50" y2="90"/>
                <line x1="10" y1="50" x2="90" y2="50"/>
                <line x1="10" y1="10" x2="90" y2="90"/>
                <line x1="90" y1="10" x2="10" y2="90"/>
              </g>
              <g id="pointsLayer"></g>
              <g id="marksLayer"></g>
            </svg>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Pause Modal -->
<div class="modal fade" id="pauseModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Game Paused</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body text-center py-4">
        <p>Your game progress has been saved.</p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" onclick="exitToLobby()">Exit to Lobby</button>
        <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Resume</button>
      </div>
    </div>
  </div>
</div>

<script>
const SESSION_ID = <?= $sessionId ?>;
const GAME_MODE = '<?= $mode ?>';
const IS_ONLINE = <?= $isOnlineGame ? 'true' : 'false' ?>;
const PLAYER_SIDE = '<?= $playerSide ?>';
const COMPUTER_PLAYER = 'O';
const USER_ID = <?= $_SESSION['user_id'] ?>;
<?php if ($isOnlineGame && isset($session['player1_id']) && isset($session['player2_id'])): ?>
const OPPONENT_ID = <?= ($session['player1_id'] == $_SESSION['user_id']) ? $session['player2_id'] : $session['player1_id'] ?>;
<?php else: ?>
const OPPONENT_ID = null;
<?php endif; ?>

// Player names for display
const PLAYER_X_NAME = '<?= $playerSide === 'X' ? htmlspecialchars($currentUser['username'], ENT_QUOTES) : ($isOnlineGame && $opponent ? htmlspecialchars($opponent['name'], ENT_QUOTES) : 'Blue') ?>';
const PLAYER_O_NAME = '<?= $playerSide === 'O' ? htmlspecialchars($currentUser['username'], ENT_QUOTES) : ($isOnlineGame && $opponent ? htmlspecialchars($opponent['name'], ENT_QUOTES) : (strpos($mode, 'pvc') !== false ? 'AI' : 'Pink')) ?>';

// AI Difficulty from game settings
const AI_DIFFICULTY = '<?= $_GET['difficulty'] ?? 'medium' ?>';

// Include the original game.js logic here (will be loaded separately)
</script>
<script src="ai/LearnedAI.js?v=<?= time() ?>"></script>
<script src="ai/AdvancedAI.js?v=<?= time() ?>"></script>
<script src="game-engine.js?v=<?= time() ?>"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
function pauseGame() {
    saveGameState();
    const pauseModal = new bootstrap.Modal(document.getElementById('pauseModal'));
    pauseModal.show();
}

function exitGame() {
    Swal.fire({
        title: 'Exit Game?',
        text: 'Are you sure you want to exit? Your progress will be saved.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Yes, exit',
        cancelButtonText: 'Stay',
        confirmButtonColor: '#667eea'
    }).then(async (result) => {
        if (result.isConfirmed) {
            await saveGameState();
            window.location.href = 'dashboard.php';
        }
    });
}

async function exitToLobby() {
    await saveGameState();
    window.location.href = 'dashboard.php';
}

async function saveGameState() {
    const gameState = {
        board: board,
        placedCount: placedCount,
        phase: phase,
        turn: turn,
        currentMovingPlayer: currentMovingPlayer,
        gameOver: gameOver
    };

    try {
        await fetch('api/save-game-state.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                session_id: SESSION_ID,
                game_state: gameState
            })
        });
    } catch (error) {
        console.error('Failed to save game state:', error);
    }
}

// Auto-save every move
function autoSave() {
    saveGameState();
}

// Poll for opponent moves in online game
if (IS_ONLINE) {
    // Check opponent status
    async function checkOpponentStatus() {
        try {
            const response = await fetch(`api/check-opponent-status.php?session_id=${SESSION_ID}`);
            const data = await response.json();

            if (data.success) {
                const statusDot = document.getElementById('opponentOnlineStatus');
                const statusBadge = document.getElementById('opponentStatusBadge');

                if (statusDot && statusBadge) {
                    if (data.is_online) {
                        statusDot.style.background = '#10b981';
                        statusDot.title = 'Online';
                        statusBadge.className = 'badge bg-success-subtle text-success-emphasis';
                        statusBadge.innerHTML = '<span style="display:inline-block;width:6px;height:6px;background:currentColor;border-radius:50%;margin-right:4px;"></span>Online';
                    } else {
                        statusDot.style.background = '#6c757d';
                        statusDot.title = 'Offline';
                        statusBadge.className = 'badge bg-secondary-subtle text-secondary-emphasis';
                        statusBadge.textContent = 'Offline';
                    }

                    // Show warning if opponent disconnected during game
                    if (!data.is_online && data.last_seen) {
                        const lastSeen = new Date(data.last_seen);
                        const minutesAgo = Math.floor((Date.now() - lastSeen.getTime()) / 60000);
                        if (minutesAgo > 5) {
                            statusBadge.textContent = `Offline (${minutesAgo}m ago)`;
                        }
                    }
                }
            }
        } catch (error) {
            console.error('Failed to check opponent status:', error);
        }
    }

    // Check status immediately and then every 10 seconds
    checkOpponentStatus();
    setInterval(checkOpponentStatus, 10000);

    // Sync game state every 1 second for real-time gameplay
    setInterval(async () => {
        try {
            const response = await fetch(`api/get-game-state.php?session_id=${SESSION_ID}`);
            const data = await response.json();

            if (data.success) {
                // Check if game status changed to completed
                if (data.status === 'completed' && !gameOver) {
                    // Game ended, reload state to show winner
                    if (data.board_state) {
                        const state = JSON.parse(data.board_state);
                        board = state.board || board;

                        // Determine winner based on winner_id from database
                        gameOver = true;

                        // Check if current user won
                        const isPlayerWin = (data.winner_id && data.winner_id == USER_ID);

                        // Determine winner symbol
                        let winnerSide;
                        if (data.winner_id == null) {
                            // Draw
                            winnerSide = null;
                        } else if (isPlayerWin) {
                            // Current player won
                            winnerSide = PLAYER_SIDE;
                        } else {
                            // Opponent won
                            winnerSide = (PLAYER_SIDE === 'X' ? 'O' : 'X');
                        }

                        const winnerName = winnerSide ? getPlayerName(winnerSide) : 'Draw';

                        renderMarks();
                        updateUI();

                        // Show game result modal
                        setTimeout(() => {
                            showGameResultModal(winnerSide, isPlayerWin, winnerName);
                        }, 500);
                    }
                } else if (data.board_state && !gameOver) {
                    // Normal game state sync - only update if opponent made a move
                    const state = JSON.parse(data.board_state);

                    // Check if this is an opponent's move (turn in database is now ours)
                    const isOpponentMove = state.turn === PLAYER_SIDE && turn !== PLAYER_SIDE;
                    const boardChanged = JSON.stringify(state.board) !== JSON.stringify(board);

                    if (boardChanged || isOpponentMove) {
                        // Update game state
                        board = state.board || board;
                        placedCount = state.placedCount || placedCount;
                        phase = state.phase || phase;
                        turn = state.turn || turn;
                        currentMovingPlayer = state.currentMovingPlayer || currentMovingPlayer;
                        selectedFrom = null;

                        renderMarks();
                        updateUI();

                        console.log('Game state synced from opponent:', { phase, turn, placedCount });
                    }
                }
            }
        } catch (error) {
            console.error('Failed to sync game state:', error);
        }
    }, 1000); // Changed to 1 second for real-time experience
}

// Listen for AI reasoning events
window.addEventListener('ai-reasoning', (event) => {
    const panel = document.getElementById('aiReasoningPanel');
    const text = document.getElementById('aiReasoningText');

    if (panel && text) {
        panel.style.display = 'block';
        text.textContent = event.detail.message;

        // Auto-hide after 5 seconds
        setTimeout(() => {
            panel.style.display = 'none';
        }, 5000);
    }
});

// ===== CHAT FUNCTIONALITY =====
let lastChatMessageId = 0;

function toggleChat() {
    const container = document.getElementById('chatContainer');
    const toggle = document.getElementById('chatToggle');
    if (container.style.display === 'none') {
        container.style.display = 'block';
        toggle.innerHTML = '<svg width="16" height="16" fill="currentColor" viewBox="0 0 16 16"><path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14zm0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16z"/><path d="m8.93 6.588-2.29.287-.082.38.45.083c.294.07.352.176.288.469l-.738 3.468c-.194.897.105 1.319.808 1.319.545 0 1.178-.252 1.465-.598l.088-.416c-.2.176-.492.246-.686.246-.275 0-.375-.193-.304-.533L8.93 6.588zM9 4.5a1 1 0 1 1-2 0 1 1 0 0 1 2 0z"/></svg>';
    } else {
        container.style.display = 'none';
        toggle.innerHTML = '<svg width="16" height="16" fill="currentColor" viewBox="0 0 16 16"><path fill-rule="evenodd" d="M8 2a.5.5 0 0 1 .5.5v5h5a.5.5 0 0 1 0 1h-5v5a.5.5 0 0 1-1 0v-5h-5a.5.5 0 0 1 0-1h5v-5A.5.5 0 0 1 8 2Z"/></svg>';
    }
}

async function sendMessage(event) {
    event.preventDefault();
    const input = document.getElementById('chatInput');
    const message = input.value.trim();

    if (!message) return;

    try {
        const response = await fetch('api/send-chat-message.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                session_id: SESSION_ID,
                message: message
            })
        });

        const data = await response.json();

        if (data.success) {
            input.value = '';
            // Message will appear via polling
        } else {
            console.error('Failed to send message:', data.message);
        }
    } catch (error) {
        console.error('Error sending message:', error);
    }
}

async function loadChatMessages() {
    try {
        const response = await fetch(`api/get-chat-messages.php?session_id=${SESSION_ID}&after=${lastChatMessageId}`);
        const data = await response.json();

        if (data.success && data.messages.length > 0) {
            const messagesContainer = document.getElementById('chatMessages');
            const wasEmpty = messagesContainer.querySelector('.text-center');

            if (wasEmpty) {
                messagesContainer.innerHTML = '';
            }

            data.messages.forEach(msg => {
                const isMe = msg.user_id == USER_ID;
                const messageDiv = document.createElement('div');
                messageDiv.style.marginBottom = '0.75rem';
                messageDiv.innerHTML = `
                    <div style="display: flex; justify-content: ${isMe ? 'flex-end' : 'flex-start'};">
                        <div style="max-width: 70%; background: ${isMe ? '#667eea' : '#e9ecef'}; color: ${isMe ? 'white' : '#212529'}; padding: 0.5rem 0.75rem; border-radius: 12px; font-size: 0.9rem;">
                            ${!isMe ? `<strong style="display: block; margin-bottom: 0.25rem; font-size: 0.75rem; opacity: 0.8;">${msg.username}</strong>` : ''}
                            <div>${escapeHtml(msg.message)}</div>
                            <div style="font-size: 0.7rem; margin-top: 0.25rem; opacity: 0.7;">${formatTime(msg.created_at)}</div>
                        </div>
                    </div>
                `;
                messagesContainer.appendChild(messageDiv);
                lastChatMessageId = msg.id;
            });

            // Scroll to bottom
            messagesContainer.scrollTop = messagesContainer.scrollHeight;
        }
    } catch (error) {
        console.error('Error loading messages:', error);
    }
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function formatTime(timestamp) {
    const date = new Date(timestamp);
    return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
}

// Load chat messages if in online game
if (IS_ONLINE) {
    // Load initial messages
    loadChatMessages();
    // Poll for new messages every 1 second for real-time chat
    setInterval(loadChatMessages, 1000);
}

// ===== REMATCH FUNCTIONALITY =====
async function requestRematch() {
    try {
        Swal.fire({
            title: 'Requesting Rematch...',
            text: 'Sending rematch request to opponent',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });

        const response = await fetch('api/request-rematch.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ session_id: SESSION_ID })
        });

        const data = await response.json();

        if (data.success) {
            Swal.fire({
                title: 'Rematch Requested!',
                text: 'Waiting for opponent to accept...',
                icon: 'info',
                showConfirmButton: false,
                allowOutsideClick: false,
                didOpen: () => {
                    // Poll for rematch response
                    const checkInterval = setInterval(async () => {
                        try {
                            const checkResponse = await fetch(`api/check-rematch-status.php?request_id=${data.request_id}`);
                            const checkData = await checkResponse.json();

                            if (checkData.status === 'accepted') {
                                clearInterval(checkInterval);
                                Swal.fire({
                                    title: 'Rematch Accepted!',
                                    text: 'Starting new game...',
                                    icon: 'success',
                                    timer: 1500,
                                    showConfirmButton: false
                                }).then(() => {
                                    window.location.href = `play.php?session=${checkData.new_session_id}`;
                                });
                            } else if (checkData.status === 'rejected') {
                                clearInterval(checkInterval);
                                Swal.fire({
                                    title: 'Rematch Declined',
                                    text: 'Opponent declined the rematch',
                                    icon: 'error',
                                    confirmButtonText: 'Back to Dashboard',
                                    confirmButtonColor: '#667eea'
                                }).then(() => {
                                    window.location.href = 'dashboard.php';
                                });
                            }
                        } catch (error) {
                            console.error('Error checking rematch status:', error);
                        }
                    }, 2000);

                    // Auto-cancel after 60 seconds
                    setTimeout(() => {
                        clearInterval(checkInterval);
                        Swal.fire({
                            title: 'Request Timeout',
                            text: 'Opponent did not respond',
                            icon: 'warning',
                            confirmButtonText: 'Back to Dashboard',
                            confirmButtonColor: '#667eea'
                        }).then(() => {
                            window.location.href = 'dashboard.php';
                        });
                    }, 60000);
                }
            });
        } else {
            Swal.fire({
                title: 'Error',
                text: data.message || 'Failed to send rematch request',
                icon: 'error',
                confirmButtonColor: '#667eea'
            });
        }
    } catch (error) {
        console.error('Error requesting rematch:', error);
        Swal.fire({
            title: 'Error',
            text: 'Failed to send rematch request',
            icon: 'error',
            confirmButtonColor: '#667eea'
        });
    }
}

// Check for incoming rematch requests
if (IS_ONLINE) {
    setInterval(async () => {
        try {
            const response = await fetch(`api/check-incoming-rematch.php?session_id=${SESSION_ID}`);
            const data = await response.json();

            if (data.has_request) {
                Swal.fire({
                    title: 'Rematch Request',
                    text: `${data.requester_name} wants a rematch!`,
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: 'Accept',
                    cancelButtonText: 'Decline',
                    confirmButtonColor: '#10b981',
                    cancelButtonColor: '#ef4444',
                    allowOutsideClick: false
                }).then(async (result) => {
                    try {
                        const respondResponse = await fetch('api/respond-rematch.php', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({
                                request_id: data.request_id,
                                accept: result.isConfirmed
                            })
                        });

                        const respondData = await respondResponse.json();

                        if (result.isConfirmed && respondData.success) {
                            Swal.fire({
                                title: 'Rematch Accepted!',
                                text: 'Starting new game...',
                                icon: 'success',
                                timer: 1500,
                                showConfirmButton: false
                            }).then(() => {
                                window.location.href = `play.php?session=${respondData.new_session_id}`;
                            });
                        } else if (!result.isConfirmed) {
                            window.location.href = 'dashboard.php';
                        }
                    } catch (error) {
                        console.error('Error responding to rematch:', error);
                    }
                });
            }
        } catch (error) {
            console.error('Error checking rematch requests:', error);
        }
    }, 5000); // Check every 5 seconds
}
</script>

</body>
</html>
