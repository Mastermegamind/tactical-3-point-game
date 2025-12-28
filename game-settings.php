<?php
require_once __DIR__ . '/config/session.php';

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
    <title>Game Settings - Okwe - Tactical Pebble Game</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
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

        .user-card {
            padding: 1rem;
            border: 2px solid #e9ecef;
            border-radius: 12px;
            margin-bottom: 1rem;
            background: white;
            display: flex;
            align-items: center;
            justify-content: space-between;
            transition: all 0.2s;
        }

        .user-card:hover {
            border-color: #667eea;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .user-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            border: 2px solid #667eea;
            padding: 0.5rem;
            background: white;
        }

        .user-avatar svg {
            width: 100%;
            height: 100%;
        }

        .user-details h6 {
            margin: 0;
            font-weight: 600;
        }

        .user-stats {
            font-size: 0.85rem;
            color: #6c757d;
        }

        .btn-challenge {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 8px;
            padding: 0.5rem 1rem;
            font-weight: 600;
            color: white;
            font-size: 0.9rem;
            transition: all 0.2s;
        }

        .btn-challenge:hover {
            transform: translateY(-2px);
            box-shadow: 0 2px 8px rgba(102, 126, 234, 0.4);
        }

        .btn-challenge:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .empty-state {
            text-align: center;
            padding: 3rem 1rem;
            color: #6c757d;
        }

        .empty-state svg {
            width: 80px;
            height: 80px;
            margin-bottom: 1rem;
            opacity: 0.3;
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
                <div class="mode-description">Challenge an online player or find a random opponent</div>
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

                    <div id="ai-provider-select" class="mt-3" style="display: none;">
                        <label class="form-label mb-2">AI Provider (Hard Mode Only):</label>
                        <select class="form-select" id="ai-provider" onchange="saveAIProvider()">
                            <option value="deepseek">DeepSeek (Default)</option>
                            <option value="openai">OpenAI GPT-4</option>
                            <option value="claude">Anthropic Claude</option>
                            <option value="gemini">Google Gemini</option>
                            <option value="grok">xAI Grok</option>
                            <option value="meta">Meta AI (Llama)</option>
                        </select>
                        <small class="text-muted d-block mt-1">
                            <span id="provider-status">Checking providers...</span>
                        </small>
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

        <div id="pvp-mode-selection" style="display: none;">
            <h4 class="mb-4">Choose PvP Mode</h4>

            <div class="mode-option" onclick="selectPvPMode('challenge')">
                <div class="mode-title">Challenge Online Player</div>
                <div class="mode-description">Select from available online players to challenge</div>
            </div>

            <div class="mode-option" onclick="selectPvPMode('random')">
                <div class="mode-title">Random Matchmaking</div>
                <div class="mode-description">Find an opponent with similar skill level automatically</div>
            </div>

            <button class="btn btn-secondary-custom" onclick="backToModeSelection()">
                Back
            </button>
        </div>

        <div id="online-users-list" style="display: none;">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h4 class="mb-0">Online Players</h4>
                <button class="btn btn-sm btn-secondary" onclick="refreshOnlineUsers()">
                    <span id="refresh-icon">🔄</span> Refresh
                </button>
            </div>

            <div id="users-container" style="max-height: 400px; overflow-y: auto;">
                <!-- Online users will be loaded here -->
            </div>

            <button class="btn btn-secondary-custom mt-3" onclick="backToPvPModeSelection()">
                Back
            </button>
        </div>
    </div>

    <script>
        let selectedMode = null;
        let selectedDifficulty = 'medium';
        let matchmakingInterval = null;
        let pvpMode = null;

        const presetAvatars = {
            'avatar1.svg': '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><circle cx="50" cy="50" r="45" fill="#667eea"/><circle cx="35" cy="40" r="5" fill="#fff"/><circle cx="65" cy="40" r="5" fill="#fff"/><path d="M30 65 Q50 75 70 65" stroke="#fff" stroke-width="3" fill="none"/></svg>',
            'avatar2.svg': '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><circle cx="50" cy="50" r="45" fill="#f093fb"/><circle cx="35" cy="40" r="5" fill="#fff"/><circle cx="65" cy="40" r="5" fill="#fff"/><circle cx="50" cy="65" r="8" fill="#fff"/></svg>',
            'avatar3.svg': '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><circle cx="50" cy="50" r="45" fill="#4facfe"/><circle cx="35" cy="40" r="5" fill="#fff"/><circle cx="65" cy="40" r="5" fill="#fff"/><rect x="35" y="60" width="30" height="5" fill="#fff"/></svg>',
            'avatar4.svg': '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><circle cx="50" cy="50" r="45" fill="#43e97b"/><circle cx="35" cy="40" r="5" fill="#fff"/><circle cx="65" cy="40" r="5" fill="#fff"/><path d="M35 65 L50 60 L65 65" stroke="#fff" stroke-width="3" fill="none"/></svg>',
            'avatar5.svg': '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><circle cx="50" cy="50" r="45" fill="#fa709a"/><circle cx="35" cy="40" r="5" fill="#fff"/><circle cx="65" cy="40" r="5" fill="#fff"/><ellipse cx="50" cy="65" rx="15" ry="8" fill="#fff"/></svg>',
            'avatar6.svg': '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><circle cx="50" cy="50" r="45" fill="#764ba2"/><circle cx="35" cy="40" r="5" fill="#fff"/><circle cx="65" cy="40" r="5" fill="#fff"/><path d="M30 70 Q50 60 70 70" stroke="#fff" stroke-width="3" fill="none"/></svg>'
        };

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

            // Show/hide AI provider selector based on difficulty
            const providerSelect = document.getElementById('ai-provider-select');
            if (difficulty === 'hard') {
                providerSelect.style.display = 'block';
                checkAIProviders();
            } else {
                providerSelect.style.display = 'none';
            }
        }

        async function startGame() {
            if (!selectedMode) return;

            if (selectedMode === 'pvp') {
                // Show PvP mode selection
                document.getElementById('mode-selection').style.display = 'none';
                document.getElementById('pvp-mode-selection').style.display = 'block';
            } else {
                // Start AI game
                window.location.href = `play.php?mode=pvc-${selectedDifficulty}`;
            }
        }

        function selectPvPMode(mode) {
            pvpMode = mode;

            if (mode === 'challenge') {
                // Show online users list
                document.getElementById('pvp-mode-selection').style.display = 'none';
                document.getElementById('online-users-list').style.display = 'block';
                loadOnlineUsers();
            } else {
                // Start random matchmaking
                document.getElementById('pvp-mode-selection').style.display = 'none';
                startMatchmaking();
            }
        }

        async function loadOnlineUsers() {
            const container = document.getElementById('users-container');
            container.innerHTML = '<div class="text-center"><div class="spinner"></div><p>Loading online players...</p></div>';

            try {
                const response = await fetch('api/get-online-users.php');
                const data = await response.json();

                if (data.success) {
                    displayOnlineUsers(data.users);
                } else {
                    container.innerHTML = '<div class="empty-state"><p>Failed to load online users</p></div>';
                }
            } catch (error) {
                console.error('Error loading online users:', error);
                container.innerHTML = '<div class="empty-state"><p>An error occurred</p></div>';
            }
        }

        function displayOnlineUsers(users) {
            const container = document.getElementById('users-container');

            if (users.length === 0) {
                container.innerHTML = `
                    <div class="empty-state">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/>
                        </svg>
                        <h6>No players online</h6>
                        <p>Try random matchmaking or come back later</p>
                    </div>
                `;
                return;
            }

            container.innerHTML = users.map(user => {
                const avatarHtml = presetAvatars[user.avatar] || presetAvatars['avatar1.svg'];
                const totalGames = parseInt(user.wins) + parseInt(user.losses) + parseInt(user.draws);
                const winRate = totalGames > 0 ? Math.round((user.wins / totalGames) * 100) : 0;

                return `
                    <div class="user-card">
                        <div class="user-info">
                            <div class="user-avatar">${avatarHtml}</div>
                            <div class="user-details">
                                <h6>${escapeHtml(user.username)}</h6>
                                <div class="user-stats">
                                    Rating: ${user.rating} • W/L: ${user.wins}/${user.losses} • Win Rate: ${winRate}%
                                </div>
                            </div>
                        </div>
                        <button class="btn btn-challenge" onclick="challengeUser(${user.id}, '${escapeHtml(user.username)}')"
                                ${user.has_pending_challenge ? 'disabled' : ''}>
                            ${user.has_pending_challenge ? 'Challenge Sent' : 'Challenge'}
                        </button>
                    </div>
                `;
            }).join('');
        }

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        async function refreshOnlineUsers() {
            const icon = document.getElementById('refresh-icon');
            icon.style.display = 'inline-block';
            icon.style.animation = 'spin 1s linear infinite';
            await loadOnlineUsers();
            setTimeout(() => {
                icon.style.animation = 'none';
            }, 500);
        }

        async function challengeUser(userId, username) {
            try {
                const response = await fetch('api/send-challenge.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ challenged_id: userId })
                });

                const data = await response.json();

                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Challenge Sent!',
                        text: `Waiting for ${username} to accept your challenge...`,
                        timer: 2000,
                        showConfirmButton: false
                    });
                    // Start polling for challenge response
                    pollForChallengeResponse(data.challenge_id);
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Failed',
                        text: data.message || 'Failed to send challenge'
                    });
                }
            } catch (error) {
                console.error('Error sending challenge:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'An error occurred. Please try again.'
                });
            }
        }

        function pollForChallengeResponse(challengeId) {
            // Show waiting status
            document.getElementById('online-users-list').style.display = 'none';
            document.getElementById('matchmaking-status').classList.add('show');
            document.querySelector('#matchmaking-status h5').textContent = 'Waiting for opponent to accept...';
            document.querySelector('#matchmaking-status p').textContent = 'The challenge will expire in 2 minutes';

            matchmakingInterval = setInterval(async () => {
                try {
                    const response = await fetch('api/check-match.php');
                    const data = await response.json();

                    if (data.matched) {
                        clearInterval(matchmakingInterval);
                        window.location.href = `play.php?session=${data.session_id}`;
                    }
                } catch (error) {
                    console.error('Challenge poll error:', error);
                }
            }, 2000);

            // Auto-cancel after 2 minutes
            setTimeout(() => {
                if (matchmakingInterval) {
                    clearInterval(matchmakingInterval);
                    Swal.fire({
                        icon: 'info',
                        title: 'Challenge Expired',
                        text: 'The challenge timed out after 2 minutes',
                        timer: 3000
                    });
                    backToOnlineUsers();
                }
            }, 120000);
        }

        async function startMatchmaking() {
            document.getElementById('matchmaking-status').classList.add('show');
            document.querySelector('#matchmaking-status h5').textContent = 'Finding opponent...';
            document.querySelector('#matchmaking-status p').textContent = 'Please wait while we match you with a player';

            try {
                const response = await fetch('api/join-matchmaking.php', {
                    method: 'POST'
                });

                const data = await response.json();

                if (data.success) {
                    pollForMatch();
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Matchmaking Failed',
                        text: data.message || 'Failed to join matchmaking'
                    });
                    cancelMatchmaking();
                }
            } catch (error) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'An error occurred. Please try again.'
                });
                cancelMatchmaking();
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

            document.getElementById('matchmaking-status').classList.remove('show');
            document.getElementById('mode-selection').style.display = 'block';
        }

        function backToModeSelection() {
            document.getElementById('pvp-mode-selection').style.display = 'none';
            document.getElementById('mode-selection').style.display = 'block';
        }

        function backToPvPModeSelection() {
            document.getElementById('online-users-list').style.display = 'none';
            document.getElementById('pvp-mode-selection').style.display = 'block';
        }

        function backToOnlineUsers() {
            document.getElementById('matchmaking-status').classList.remove('show');
            document.getElementById('online-users-list').style.display = 'block';
            loadOnlineUsers();
        }

        // AI Provider Management
        function saveAIProvider() {
            const provider = document.getElementById('ai-provider').value;
            localStorage.setItem('ai_provider', provider);
            console.log('AI provider set to:', provider);
        }

        function loadAIProvider() {
            const savedProvider = localStorage.getItem('ai_provider') || 'deepseek';
            const select = document.getElementById('ai-provider');
            if (select) {
                select.value = savedProvider;
            }
        }

        async function checkAIProviders() {
            const statusElement = document.getElementById('provider-status');
            statusElement.textContent = 'Checking providers...';

            try {
                const response = await fetch('api/check-ai-providers.php');
                const data = await response.json();

                if (data.success) {
                    const configuredCount = Object.values(data.providers).filter(p => p.configured).length;
                    statusElement.innerHTML = `${configuredCount} of 6 providers configured`;

                    // Update select options to show which are configured
                    const select = document.getElementById('ai-provider');
                    const options = select.querySelectorAll('option');

                    options.forEach(option => {
                        const provider = option.value;
                        const providerData = data.providers[provider];

                        if (providerData && providerData.configured) {
                            option.textContent = option.textContent.replace(' (Not Configured)', '').replace(' ✓', '') + ' ✓';
                        } else {
                            option.textContent = option.textContent.replace(' (Not Configured)', '').replace(' ✓', '') + ' (Not Configured)';
                        }
                    });
                } else {
                    statusElement.textContent = 'Unable to check provider status';
                }
            } catch (error) {
                console.error('Error checking AI providers:', error);
                statusElement.textContent = 'Error checking providers';
            }
        }

        // Load saved AI provider on page load
        window.addEventListener('DOMContentLoaded', () => {
            loadAIProvider();
        });
    </script>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        const autoStartNotifications = true;
    </script>
    <script src="js/notification-handler.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
