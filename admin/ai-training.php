<?php
require_once 'auth_check.php';
require_once __DIR__ . '/../config/database.php';

$db = Database::getInstance();
$conn = $db->getConnection();

// Get AI statistics for all difficulties
$difficulties = ['easy', 'medium', 'hard'];
$aiStats = [];

foreach ($difficulties as $difficulty) {
    // Get game statistics
    $stmt = $conn->prepare("
        SELECT
            COUNT(*) as total_games,
            SUM(CASE WHEN winner = 'O' THEN 1 ELSE 0 END) as ai_wins,
            SUM(CASE WHEN winner = 'X' THEN 1 ELSE 0 END) as player_wins,
            SUM(CASE WHEN winner IS NULL THEN 1 ELSE 0 END) as draws,
            AVG(move_count) as avg_moves
        FROM ai_training_data
        WHERE difficulty = ?
    ");
    $stmt->execute([$difficulty]);
    $stats = $stmt->fetch();

    // Check if learned strategy exists
    $strategyFile = __DIR__ . "/../cache/ai_strategy_{$difficulty}.json";
    $hasLearnedStrategy = file_exists($strategyFile);
    $lastTrainedAt = $hasLearnedStrategy ? date('Y-m-d H:i:s', filemtime($strategyFile)) : null;

    $aiStats[$difficulty] = [
        'stats' => $stats,
        'has_strategy' => $hasLearnedStrategy,
        'last_trained' => $lastTrainedAt
    ];
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>AI Training Dashboard - Admin</title>

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
            padding: 2rem 0;
        }

        .container {
            max-width: 1200px;
        }

        .admin-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            overflow: hidden;
            margin-bottom: 2rem;
        }

        .admin-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem;
            text-align: center;
        }

        .admin-header h1 {
            font-size: 2rem;
            font-weight: 700;
            margin: 0;
        }

        .admin-header p {
            margin: 0.5rem 0 0 0;
            opacity: 0.9;
        }

        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .stat-card h3 {
            font-size: 1.2rem;
            font-weight: 600;
            margin-bottom: 1rem;
            color: #667eea;
            text-transform: capitalize;
        }

        .stat-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .stat-item {
            text-align: center;
            padding: 1rem;
            background: #f8f9fa;
            border-radius: 8px;
        }

        .stat-label {
            font-size: 0.875rem;
            color: #6c757d;
            margin-bottom: 0.5rem;
        }

        .stat-value {
            font-size: 1.5rem;
            font-weight: 700;
            color: #212529;
        }

        .badge-success {
            background: #28a745;
            color: white;
            padding: 0.25rem 0.75rem;
            border-radius: 4px;
            font-size: 0.875rem;
        }

        .badge-warning {
            background: #ffc107;
            color: #212529;
            padding: 0.25rem 0.75rem;
            border-radius: 4px;
            font-size: 0.875rem;
        }

        .btn-train {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            color: white;
            padding: 0.75rem 2rem;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s;
        }

        .btn-train:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
        }

        .btn-train:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        .training-info {
            background: #e7f3ff;
            border-left: 4px solid #667eea;
            padding: 1rem;
            border-radius: 4px;
            margin-bottom: 1rem;
        }

        .back-link {
            color: white;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 1rem;
        }

        .back-link:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="../dashboard.php" class="back-link">← Back to Dashboard</a>

        <div class="admin-card">
            <div class="admin-header">
                <h1>🤖 AI Training Dashboard</h1>
                <p>Train and monitor AI performance across all difficulty levels</p>
            </div>

            <div style="padding: 2rem;">
                <div class="training-info">
                    <strong>How it works:</strong> The AI learning system analyzes historical game data to extract winning patterns,
                    opening strategies, and position weights. Click "Train AI" to update the AI's strategy based on the latest game data.
                </div>

                <?php foreach ($difficulties as $difficulty): ?>
                    <div class="stat-card">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h3><?php echo ucfirst($difficulty); ?> Difficulty</h3>
                            <?php if ($aiStats[$difficulty]['has_strategy']): ?>
                                <span class="badge-success">Strategy Loaded</span>
                            <?php else: ?>
                                <span class="badge-warning">No Strategy</span>
                            <?php endif; ?>
                        </div>

                        <?php if ($aiStats[$difficulty]['last_trained']): ?>
                            <p style="color: #6c757d; font-size: 0.875rem; margin-bottom: 1rem;">
                                Last trained: <?php echo $aiStats[$difficulty]['last_trained']; ?>
                            </p>
                        <?php endif; ?>

                        <div class="stat-grid">
                            <div class="stat-item">
                                <div class="stat-label">Total Games</div>
                                <div class="stat-value"><?php echo number_format($aiStats[$difficulty]['stats']['total_games']); ?></div>
                            </div>
                            <div class="stat-item">
                                <div class="stat-label">AI Wins</div>
                                <div class="stat-value" style="color: #28a745;">
                                    <?php echo number_format($aiStats[$difficulty]['stats']['ai_wins']); ?>
                                </div>
                            </div>
                            <div class="stat-item">
                                <div class="stat-label">Player Wins</div>
                                <div class="stat-value" style="color: #dc3545;">
                                    <?php echo number_format($aiStats[$difficulty]['stats']['player_wins']); ?>
                                </div>
                            </div>
                            <div class="stat-item">
                                <div class="stat-label">Draws</div>
                                <div class="stat-value" style="color: #6c757d;">
                                    <?php echo number_format($aiStats[$difficulty]['stats']['draws']); ?>
                                </div>
                            </div>
                            <div class="stat-item">
                                <div class="stat-label">Win Rate</div>
                                <div class="stat-value" style="color: #667eea;">
                                    <?php
                                    $total = $aiStats[$difficulty]['stats']['total_games'];
                                    $winRate = $total > 0 ? ($aiStats[$difficulty]['stats']['ai_wins'] / $total * 100) : 0;
                                    echo number_format($winRate, 1) . '%';
                                    ?>
                                </div>
                            </div>
                            <div class="stat-item">
                                <div class="stat-label">Avg Moves</div>
                                <div class="stat-value">
                                    <?php echo number_format($aiStats[$difficulty]['stats']['avg_moves'] ?? 0, 1); ?>
                                </div>
                            </div>
                        </div>

                        <button
                            class="btn-train w-100"
                            onclick="trainAI('<?php echo $difficulty; ?>')"
                            id="train-btn-<?php echo $difficulty; ?>">
                            Train AI (<?php echo ucfirst($difficulty); ?>)
                        </button>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        async function trainAI(difficulty) {
            const btn = document.getElementById(`train-btn-${difficulty}`);
            btn.disabled = true;
            btn.textContent = 'Training...';

            try {
                const response = await fetch(`../api/train-ai.php?difficulty=${difficulty}`);
                const data = await response.json();

                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'AI Training Complete!',
                        html: `
                            <div style="text-align: left; padding: 1rem;">
                                <p><strong>Training Results for ${difficulty.charAt(0).toUpperCase() + difficulty.slice(1)} Difficulty:</strong></p>
                                <ul style="list-style: none; padding-left: 0;">
                                    <li>📊 Games Analyzed: <strong>${data.stats.total_games}</strong></li>
                                    <li>🎯 Win Rate: <strong>${data.stats.win_rate}%</strong></li>
                                    <li>📈 Opening Patterns Found: <strong>${data.learned_data.patterns.opening_moves.length}</strong></li>
                                    <li>🧠 Winning Sequences: <strong>${data.learned_data.patterns.winning_sequences.length}</strong></li>
                                </ul>
                            </div>
                        `,
                        confirmButtonText: 'Great!'
                    }).then(() => {
                        location.reload();
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Training Failed',
                        text: data.error || 'Failed to train AI. Please try again.',
                    });
                    btn.disabled = false;
                    btn.textContent = `Train AI (${difficulty.charAt(0).toUpperCase() + difficulty.slice(1)})`;
                }
            } catch (error) {
                console.error('Training error:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'An error occurred while training the AI.',
                });
                btn.disabled = false;
                btn.textContent = `Train AI (${difficulty.charAt(0).toUpperCase() + difficulty.slice(1)})`;
            }
        }
    </script>
</body>
</html>
