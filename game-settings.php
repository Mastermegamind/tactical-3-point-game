<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Game Settings - Tactical Pebble Game</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
        }

        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
        }

        .settings-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 24px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            padding: 3rem;
            max-width: 600px;
            width: 100%;
        }

        .page-header {
            text-align: center;
            margin-bottom: 3rem;
        }

        .page-title {
            font-size: 2rem;
            font-weight: 700;
            color: #212529;
            margin-bottom: 0.5rem;
        }

        .page-subtitle {
            color: #6c757d;
            font-size: 0.95rem;
        }

        .mode-option {
            padding: 2rem;
            border: 3px solid #e9ecef;
            border-radius: 16px;
            cursor: pointer;
            transition: all 0.3s;
            margin-bottom: 1.5rem;
            background: white;
        }

        .mode-option:hover {
            border-color: #667eea;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .mode-option.selected {
            border-color: #667eea;
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.1), rgba(118, 75, 162, 0.1));
        }

        .mode-title {
            font-size: 1.3rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            color: #212529;
        }

        .mode-description {
            color: #6c757d;
            margin-bottom: 1rem;
        }

        .difficulty-select {
            display: none;
            margin-top: 1rem;
        }

        .difficulty-select.show {
            display: block;
        }

        .difficulty-btn {
            border: 2px solid #e9ecef;
            border-radius: 8px;
            padding: 0.5rem 1rem;
            margin: 0.25rem;
            background: white;
            font-weight: 600;
            color: #495057;
            cursor: pointer;
            transition: all 0.2s;
        }

        .difficulty-btn:hover {
            border-color: #667eea;
        }

        .difficulty-btn.selected {
            border-color: #667eea;
            background: #667eea;
            color: white;
        }

        .btn-primary-custom {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 12px;
            padding: 0.75rem 2rem;
            font-weight: 600;
            color: white;
            width: 100%;
            transition: all 0.2s;
        }

        .btn-primary-custom:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
        }

        .btn-primary-custom:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .btn-secondary-custom {
            background: #e9ecef;
            border: none;
            border-radius: 12px;
            padding: 0.75rem 2rem;
            font-weight: 600;
            color: #495057;
            width: 100%;
            transition: all 0.2s;
            margin-bottom: 1rem;
        }

        .btn-secondary-custom:hover {
            background: #dee2e6;
        }

        .matchmaking-status {
            display: none;
            text-align: center;
            padding: 2rem;
        }

        .matchmaking-status.show {
            display: block;
        }

        .spinner {
            border: 3px solid #f3f3f3;
            border-top: 3px solid #667eea;
            border-radius: 50%;
            width: 50px;
            height: 50px;
            animation: spin 1s linear infinite;
            margin: 0 auto 1rem;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <div class="settings-card">
        <div class="page-header">
            <h1 class="page-title">Game Settings</h1>
            <p class="page-subtitle">Choose your game mode and start playing</p>
        </div>

        <div id="mode-selection">
            <div class="mode-option" onclick="selectMode('pvp')">
                <div class="mode-title">Player vs Player</div>
                <div class="mode-description">Find an online opponent with similar skill level</div>
            </div>

            <div class="mode-option" onclick="selectMode('ai')">
                <div class="mode-title">Player vs AI</div>
                <div class="mode-description">Practice against computer opponent</div>
                <div class="difficulty-select" id="difficulty-select">
                    <label class="form-label mb-2">Select Difficulty:</label>
                    <div>
                        <button class="difficulty-btn" onclick="selectDifficulty(event, 'easy')">Easy</button>
                        <button class="difficulty-btn selected" onclick="selectDifficulty(event, 'medium')">Medium</button>
                        <button class="difficulty-btn" onclick="selectDifficulty(event, 'hard')">Hard</button>
                    </div>
                </div>
            </div>

            <button class="btn btn-primary-custom" id="startBtn" onclick="startGame()" disabled>
                Start Game
            </button>

            <button class="btn btn-secondary-custom" onclick="window.location.href='dashboard.php'">
                Back to Dashboard
            </button>
        </div>

        <div id="matchmaking-status" class="matchmaking-status">
            <div class="spinner"></div>
            <h5>Finding opponent...</h5>
            <p class="text-muted">Please wait while we match you with a player</p>
            <button class="btn btn-secondary-custom" onclick="cancelMatchmaking()">Cancel</button>
        </div>
    </div>

    <script>
        let selectedMode = null;
        let selectedDifficulty = 'medium';
        let matchmakingInterval = null;

        function selectMode(mode) {
            selectedMode = mode;

            document.querySelectorAll('.mode-option').forEach(el => {
                el.classList.remove('selected');
            });

            event.currentTarget.classList.add('selected');

            if (mode === 'ai') {
                document.getElementById('difficulty-select').classList.add('show');
            } else {
                document.getElementById('difficulty-select').classList.remove('show');
            }

            document.getElementById('startBtn').disabled = false;
        }

        function selectDifficulty(event, difficulty) {
            event.stopPropagation();
            selectedDifficulty = difficulty;

            document.querySelectorAll('.difficulty-btn').forEach(btn => {
                btn.classList.remove('selected');
            });
            event.currentTarget.classList.add('selected');
        }

        async function startGame() {
            if (!selectedMode) return;

            if (selectedMode === 'pvp') {
                // Start matchmaking
                document.getElementById('mode-selection').style.display = 'none';
                document.getElementById('matchmaking-status').classList.add('show');

                try {
                    const response = await fetch('api/join-matchmaking.php', {
                        method: 'POST'
                    });

                    const data = await response.json();

                    if (data.success) {
                        // Poll for match
                        pollForMatch();
                    } else {
                        alert(data.message || 'Failed to join matchmaking');
                        cancelMatchmaking();
                    }
                } catch (error) {
                    alert('An error occurred. Please try again.');
                    cancelMatchmaking();
                }
            } else {
                // Start AI game
                window.location.href = `play.php?mode=pvc-${selectedDifficulty}`;
            }
        }

        function pollForMatch() {
            matchmakingInterval = setInterval(async () => {
                try {
                    const response = await fetch('api/check-match.php');
                    const data = await response.json();

                    if (data.matched) {
                        clearInterval(matchmakingInterval);
                        window.location.href = `play.php?session=${data.session_id}`;
                    }
                } catch (error) {
                    console.error('Matchmaking poll error:', error);
                }
            }, 2000);
        }

        async function cancelMatchmaking() {
            if (matchmakingInterval) {
                clearInterval(matchmakingInterval);
            }

            await fetch('api/leave-matchmaking.php', { method: 'POST' });

            document.getElementById('mode-selection').style.display = 'block';
            document.getElementById('matchmaking-status').classList.remove('show');
        }
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
