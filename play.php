<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/config/database.php';

$db = Database::getInstance();
$conn = $db->getConnection();

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
      padding: 2rem;
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
            <div class="player-avatar">
              <?= renderAvatar($opponent['avatar'], $presetAvatars) ?>
            </div>
            <div>
              <strong><?= htmlspecialchars($opponent['name']) ?></strong>
              <div class="text-muted small">Opponent</div>
            </div>
          </div>
          <?php endif; ?>

          <div class="stat-card">
            <div class="d-flex justify-content-between align-items-center mb-2">
              <div class="stat-label">Current Turn</div>
              <div class="stat-value" id="turnText">Blue</div>
            </div>
            <div class="mt-3">
              <div class="stat-label">Status</div>
              <div class="stat-value" id="statusText" style="font-size: 1.1rem;">Ready to play</div>
            </div>
          </div>

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

// Include the original game.js logic here (will be loaded separately)
</script>
<script src="game-engine.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
function pauseGame() {
    saveGameState();
    const pauseModal = new bootstrap.Modal(document.getElementById('pauseModal'));
    pauseModal.show();
}

function exitGame() {
    if (confirm('Are you sure you want to exit? Your progress will be saved.')) {
        saveGameState();
        window.location.href = 'dashboard.php';
    }
}

function exitToLobby() {
    saveGameState();
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
    setInterval(async () => {
        if (gameOver) return;

        try {
            const response = await fetch(`api/get-game-state.php?session_id=${SESSION_ID}`);
            const data = await response.json();

            if (data.success && data.board_state) {
                const state = JSON.parse(data.board_state);
                if (JSON.stringify(state.board) !== JSON.stringify(board)) {
                    loadGameState(state);
                }
            }
        } catch (error) {
            console.error('Failed to sync game state:', error);
        }
    }, 2000);
}
</script>

</body>
</html>
