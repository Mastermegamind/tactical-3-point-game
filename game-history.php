<?php
require_once __DIR__ . '/config/session.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/config/database.php';

$db = Database::getInstance();
$conn = $db->getConnection();

$sessionId = $_GET['id'] ?? null;

if (!$sessionId) {
    header('Location: dashboard.php');
    exit;
}

// Get game session details
$stmt = $conn->prepare("
    SELECT gs.*,
           u1.username as player1_name, u1.avatar as player1_avatar,
           u2.username as player2_name, u2.avatar as player2_avatar,
           w.username as winner_name
    FROM game_sessions gs
    LEFT JOIN users u1 ON gs.player1_id = u1.id
    LEFT JOIN users u2 ON gs.player2_id = u2.id
    LEFT JOIN users w ON gs.winner_id = w.id
    WHERE gs.id = ? AND (gs.player1_id = ? OR gs.player2_id = ?)
");
$stmt->execute([$sessionId, $_SESSION['user_id'], $_SESSION['user_id']]);
$session = $stmt->fetch();

if (!$session) {
    die('Game not found');
}

// Get all moves for replay
$stmt = $conn->prepare("
    SELECT * FROM game_moves
    WHERE session_id = ?
    ORDER BY move_number ASC
");
$stmt->execute([$sessionId]);
$moves = $stmt->fetchAll();

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
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Game Replay - Tactical Pebble Game</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
        }

        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 2rem 1rem;
        }

        .replay-container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .card-custom {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 24px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            padding: 2rem;
            margin-bottom: 2rem;
            border: none;
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

        .pebble {
            filter: drop-shadow(0 2px 4px rgba(0,0,0,0.2));
        }

        .replay-controls {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 1rem;
            margin-top: 2rem;
        }

        .btn-control {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 12px;
            padding: 0.75rem 1.5rem;
            color: white;
            font-weight: 600;
            cursor: pointer;
        }

        .btn-control:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
        }

        .btn-control:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .move-info {
            text-align: center;
            font-size: 1.1rem;
            font-weight: 600;
            color: #495057;
            margin-top: 1rem;
        }

        .game-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .info-box {
            padding: 1rem;
            background: #f8f9fa;
            border-radius: 12px;
            text-align: center;
        }

        .info-label {
            font-size: 0.875rem;
            color: #6c757d;
            font-weight: 600;
            text-transform: uppercase;
        }

        .info-value {
            font-size: 1.25rem;
            font-weight: 700;
            color: #212529;
            margin-top: 0.25rem;
        }

        .player-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            border: 3px solid #667eea;
            padding: 0.4rem;
            background: white;
        }
    </style>
</head>
<body>
    <div class="replay-container">
        <div class="card-custom">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2>Game Replay</h2>
                <a href="dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
            </div>

            <div class="game-info">
                <div class="info-box">
                    <div class="info-label">Date</div>
                    <div class="info-value"><?= date('M d, Y', strtotime($session['started_at'])) ?></div>
                </div>
                <div class="info-box">
                    <div class="info-label">Game Mode</div>
                    <div class="info-value"><?= strtoupper(str_replace('-', ' ', $session['game_mode'])) ?></div>
                </div>
                <div class="info-box">
                    <div class="info-label">Total Moves</div>
                    <div class="info-value"><?= count($moves) ?></div>
                </div>
                <div class="info-box">
                    <div class="info-label">Winner</div>
                    <div class="info-value"><?= $session['winner_name'] ?? 'Draw' ?></div>
                </div>
            </div>

            <div class="board-wrap">
                <svg viewBox="0 0 100 100" id="replayBoard">
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

            <div class="move-info" id="moveInfo">
                Move <span id="currentMove">0</span> of <span id="totalMoves"><?= count($moves) ?></span>
            </div>

            <div class="replay-controls">
                <button class="btn-control" id="firstBtn" onclick="goToFirst()">⏮ First</button>
                <button class="btn-control" id="prevBtn" onclick="previousMove()">◀ Previous</button>
                <button class="btn-control" id="playBtn" onclick="togglePlay()">▶ Play</button>
                <button class="btn-control" id="nextBtn" onclick="nextMove()">Next ▶</button>
                <button class="btn-control" id="lastBtn" onclick="goToLast()">Last ⏭</button>
            </div>
        </div>
    </div>

    <script>
        const moves = <?= json_encode($moves) ?>;
        let currentMoveIndex = 0;
        let isPlaying = false;
        let playInterval = null;

        const points = [
            { id: 0, x: 10, y: 10 },
            { id: 1, x: 50, y: 10 },
            { id: 2, x: 90, y: 10 },
            { id: 3, x: 10, y: 50 },
            { id: 4, x: 50, y: 50 },
            { id: 5, x: 90, y: 50 },
            { id: 6, x: 10, y: 90 },
            { id: 7, x: 50, y: 90 },
            { id: 8, x: 90, y: 90 },
        ];

        function renderBoard() {
            const pointsLayer = document.getElementById('pointsLayer');
            pointsLayer.innerHTML = '';

            points.forEach(p => {
                const c = document.createElementNS("http://www.w3.org/2000/svg", "circle");
                c.setAttribute("cx", p.x);
                c.setAttribute("cy", p.y);
                c.setAttribute("r", 3);
                c.setAttribute("fill", "#e2e8f0");
                c.setAttribute("stroke", "#94a3b8");
                c.setAttribute("stroke-width", "1.5");
                pointsLayer.appendChild(c);
            });
        }

        function renderMove(moveIndex) {
            if (moveIndex < 0 || moveIndex >= moves.length) return;

            const move = moves[moveIndex];
            const boardState = JSON.parse(move.board_state_after);
            const marksLayer = document.getElementById('marksLayer');
            marksLayer.innerHTML = '';

            boardState.forEach((val, idx) => {
                if (!val) return;
                const p = points[idx];

                const isX = val === 'X';
                const gradient = document.createElementNS("http://www.w3.org/2000/svg", "radialGradient");
                const gradientId = `pebble-gradient-${idx}`;
                gradient.setAttribute("id", gradientId);

                const stop1 = document.createElementNS("http://www.w3.org/2000/svg", "stop");
                stop1.setAttribute("offset", "0%");
                stop1.setAttribute("stop-color", isX ? "#60a5fa" : "#f472b6");

                const stop2 = document.createElementNS("http://www.w3.org/2000/svg", "stop");
                stop2.setAttribute("offset", "100%");
                stop2.setAttribute("stop-color", isX ? "#3b82f6" : "#ec4899");

                gradient.appendChild(stop1);
                gradient.appendChild(stop2);
                marksLayer.appendChild(gradient);

                const g = document.createElementNS("http://www.w3.org/2000/svg", "g");
                g.classList.add("pebble");

                const pebble = document.createElementNS("http://www.w3.org/2000/svg", "circle");
                pebble.setAttribute("cx", p.x);
                pebble.setAttribute("cy", p.y);
                pebble.setAttribute("r", 4.8);
                pebble.setAttribute("fill", `url(#${gradientId})`);
                pebble.setAttribute("stroke", isX ? "#2563eb" : "#db2777");
                pebble.setAttribute("stroke-width", "1.2");

                const highlight = document.createElementNS("http://www.w3.org/2000/svg", "ellipse");
                highlight.setAttribute("cx", p.x - 0.8);
                highlight.setAttribute("cy", p.y - 0.8);
                highlight.setAttribute("rx", 1.8);
                highlight.setAttribute("ry", 1.2);
                highlight.setAttribute("fill", "rgba(255, 255, 255, 0.5)");

                g.appendChild(pebble);
                g.appendChild(highlight);
                marksLayer.appendChild(g);
            });

            document.getElementById('currentMove').textContent = moveIndex + 1;
            updateButtons();
        }

        function updateButtons() {
            document.getElementById('firstBtn').disabled = currentMoveIndex === 0;
            document.getElementById('prevBtn').disabled = currentMoveIndex === 0;
            document.getElementById('nextBtn').disabled = currentMoveIndex === moves.length - 1;
            document.getElementById('lastBtn').disabled = currentMoveIndex === moves.length - 1;
        }

        function nextMove() {
            if (currentMoveIndex < moves.length - 1) {
                currentMoveIndex++;
                renderMove(currentMoveIndex);
            } else if (isPlaying) {
                stopPlay();
            }
        }

        function previousMove() {
            if (currentMoveIndex > 0) {
                currentMoveIndex--;
                renderMove(currentMoveIndex);
            }
        }

        function goToFirst() {
            currentMoveIndex = 0;
            renderMove(currentMoveIndex);
        }

        function goToLast() {
            currentMoveIndex = moves.length - 1;
            renderMove(currentMoveIndex);
        }

        function togglePlay() {
            if (isPlaying) {
                stopPlay();
            } else {
                startPlay();
            }
        }

        function startPlay() {
            isPlaying = true;
            document.getElementById('playBtn').textContent = '⏸ Pause';
            playInterval = setInterval(() => {
                nextMove();
            }, 1000);
        }

        function stopPlay() {
            isPlaying = false;
            document.getElementById('playBtn').textContent = '▶ Play';
            if (playInterval) {
                clearInterval(playInterval);
                playInterval = null;
            }
        }

        // Initialize
        renderBoard();
        if (moves.length > 0) {
            renderMove(0);
        }
        updateButtons();
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
