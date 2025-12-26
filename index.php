<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Tactical Pebble Game</title>

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
      padding: 2rem;
      text-align: center;
    }

    .game-title {
      font-size: 2rem;
      font-weight: 700;
      margin: 0;
      text-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }

    .game-subtitle {
      opacity: 0.9;
      margin-top: 0.5rem;
      font-size: 0.95rem;
    }

    .controls-panel {
      background: #f8f9fa;
      padding: 2rem;
      border-radius: 16px;
    }

    .form-control, .form-select {
      border-radius: 12px;
      border: 2px solid #e9ecef;
      padding: 0.75rem 1rem;
      transition: all 0.2s;
    }

    .form-control:focus, .form-select:focus {
      border-color: #667eea;
      box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
    }

    .form-label {
      font-weight: 600;
      color: #495057;
      margin-bottom: 0.5rem;
      font-size: 0.9rem;
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

    .score-display {
      display: flex;
      justify-content: space-between;
      gap: 1rem;
    }

    .player-score {
      flex: 1;
      text-align: center;
      padding: 1rem;
      background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
      border-radius: 12px;
      color: white;
    }

    .player-score.blue {
      background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
    }

    .player-score-name {
      font-size: 0.85rem;
      opacity: 0.9;
      margin-bottom: 0.25rem;
    }

    .player-score-value {
      font-size: 2rem;
      font-weight: 700;
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
      transform-origin: center;
    }

    .point:hover:not(.disabled) {
      transform: scale(1.15);
    }

    .point.disabled {
      cursor: not-allowed;
      opacity: 0.5;
    }

    .pebble {
      filter: drop-shadow(0 2px 4px rgba(0,0,0,0.2));
      transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
      cursor: pointer;
    }

    .pebble.selected {
      filter: drop-shadow(0 4px 12px rgba(102, 126, 234, 0.5));
      animation: pulse 1s ease-in-out infinite;
    }

    @keyframes pulse {
      0%, 100% { transform: scale(1); }
      50% { transform: scale(1.05); }
    }

    .btn-game {
      border-radius: 12px;
      padding: 0.75rem 1.5rem;
      font-weight: 600;
      transition: all 0.2s;
      border: none;
    }

    .btn-primary-game {
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      color: white;
    }

    .btn-primary-game:hover {
      transform: translateY(-2px);
      box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
    }

    .btn-secondary-game {
      background: #e9ecef;
      color: #495057;
    }

    .btn-secondary-game:hover {
      background: #dee2e6;
      transform: translateY(-2px);
    }

    /* Mobile Responsive */
    @media (max-width: 768px) {
      .game-title {
        font-size: 1.5rem;
      }

      .game-header {
        padding: 1.5rem;
      }

      .controls-panel {
        padding: 1.5rem;
      }

      .board-wrap {
        padding: 1rem;
      }

      .stat-value {
        font-size: 1.25rem;
      }

      .player-score-value {
        font-size: 1.5rem;
      }
    }

    @media (max-width: 576px) {
      body {
        padding: 0.5rem;
      }

      .game-card {
        border-radius: 16px;
      }

      .stat-card {
        padding: 1rem;
      }
    }
  </style>
</head>
<body>

<div class="game-container">
  <div class="game-card">
    <div class="game-header">
      <h1 class="game-title">Tactical Pebble Game</h1>
      <p class="game-subtitle">Strategic board game • Align 3 pebbles to win</p>
    </div>

    <div class="row g-0">
      <div class="col-12 col-lg-4 order-2 order-lg-1">
        <div class="controls-panel">

          <div class="mb-3">
            <label class="form-label">Blue Pebble Player</label>
            <input type="text" id="playerXName" class="form-control" placeholder="Player 1" maxlength="20">
          </div>

          <div class="mb-3">
            <label class="form-label">Pink Pebble Player</label>
            <input type="text" id="playerOName" class="form-control" placeholder="Player 2 / AI" maxlength="20">
          </div>

          <div class="mb-3">
            <label class="form-label">Game Mode</label>
            <select id="gameModeSelect" class="form-select">
              <option value="pvp">Player vs Player</option>
              <option value="pvc-easy">vs Computer (Easy)</option>
              <option value="pvc-medium">vs Computer (Medium)</option>
              <option value="pvc-hard">vs Computer (Hard)</option>
            </select>
          </div>

          <div class="stat-card">
            <div class="stat-label">Score</div>
            <div class="score-display mt-3">
              <div class="player-score blue">
                <div class="player-score-name" id="playerXScoreName">Player 1</div>
                <div class="player-score-value" id="playerXScore">0</div>
              </div>
              <div class="player-score">
                <div class="player-score-name" id="playerOScoreName">Player 2</div>
                <div class="player-score-value" id="playerOScore">0</div>
              </div>
            </div>
          </div>

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

          <div class="d-flex gap-2">
            <button id="resetBtn" class="btn-game btn-primary-game flex-fill">Reset Round</button>
            <button id="newBtn" class="btn-game btn-secondary-game flex-fill">New Game</button>
          </div>

        </div>
      </div>

      <div class="col-12 col-lg-8 order-1 order-lg-2">
        <div class="p-4">
          <div class="board-wrap">
            <!-- Board SVG -->
            <svg viewBox="0 0 100 100" aria-label="Game Board">
              <!-- Board Lines -->
              <g class="board-lines">
                <!-- Outer square -->
                <rect x="10" y="10" width="80" height="80" fill="none"/>

                <!-- Cross lines -->
                <line x1="50" y1="10" x2="50" y2="90"/>
                <line x1="10" y1="50" x2="90" y2="50"/>

                <!-- Diagonals -->
                <line x1="10" y1="10" x2="90" y2="90"/>
                <line x1="90" y1="10" x2="10" y2="90"/>
              </g>

              <!-- Points (click targets) -->
              <g id="pointsLayer"></g>

              <!-- Pebbles (player markers) -->
              <g id="marksLayer"></g>
            </svg>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<script src="game.js"></script>
</body>
</html>
