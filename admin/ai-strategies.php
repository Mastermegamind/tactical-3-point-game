<?php
require_once 'auth_check.php';
require_once __DIR__ . '/../config/database.php';

if (!hasPermission('admin')) {
    header('Location: index.php');
    exit;
}

$db = Database::getInstance();
$conn = $db->getConnection();

// Get filters
$difficulty = $_GET['difficulty'] ?? 'all';
$strategyType = $_GET['strategy_type'] ?? 'all';
$gamePhase = $_GET['phase'] ?? 'all';

// Build query
$query = "SELECT * FROM ai_strategies WHERE 1=1";
$params = [];

if ($difficulty !== 'all') {
    $query .= " AND difficulty_level = ?";
    $params[] = $difficulty;
}

if ($strategyType !== 'all') {
    $query .= " AND strategy_type = ?";
    $params[] = $strategyType;
}

if ($gamePhase !== 'all') {
    $query .= " AND game_phase = ?";
    $params[] = $gamePhase;
}

$query .= " ORDER BY priority_score DESC, success_rate DESC LIMIT 100";

$stmt = $conn->prepare($query);
$stmt->execute($params);
$strategies = $stmt->fetchAll();

// Get statistics
$stmt = $conn->query("
    SELECT
        difficulty_level,
        COUNT(*) as total_strategies,
        AVG(success_rate) as avg_success_rate,
        SUM(total_uses) as total_uses
    FROM ai_strategies
    GROUP BY difficulty_level
");
$stats = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>AI Strategies - Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { font-family: 'Inter', sans-serif; }
        body { background: #f8f9fa; }
        .admin-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 1.5rem 0;
            margin-bottom: 2rem;
        }
        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            margin-bottom: 1rem;
        }
        .strategy-card {
            background: white;
            border-left: 4px solid #667eea;
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 1rem;
            transition: all 0.2s;
        }
        .strategy-card:hover {
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            transform: translateX(4px);
        }
        .board-mini {
            display: inline-grid;
            grid-template-columns: repeat(3, 20px);
            gap: 2px;
            background: #e9ecef;
            padding: 4px;
            border-radius: 4px;
        }
        .board-cell {
            width: 20px;
            height: 20px;
            background: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 10px;
            font-weight: bold;
        }
        .badge-priority-high { background: #10b981; }
        .badge-priority-medium { background: #f59e0b; }
        .badge-priority-low { background: #6b7280; }
    </style>
</head>
<body>
    <div class="admin-header">
        <div class="container">
            <h1>🎯 AI Strategies Database</h1>
            <p class="mb-0">Real-time AI decision making and strategy learning</p>
            <div class="alert alert-light mt-3" style="background: rgba(255,255,255,0.2); border: none; color: white;">
                <strong>💡 How this works:</strong> Every move the AI makes during gameplay is analyzed and saved here. The AI reads the board state, opponent positions, and uses these proven strategies to make smarter decisions in real-time.
            </div>
        </div>
    </div>

    <div class="container">
        <!-- Statistics Cards -->
        <div class="row mb-4">
            <?php foreach ($stats as $stat): ?>
            <div class="col-md-4">
                <div class="stat-card">
                    <h5><?= strtoupper($stat['difficulty_level']) ?></h5>
                    <h2><?= number_format($stat['total_strategies']) ?></h2>
                    <small>Strategies</small>
                    <div class="mt-2">
                        <small>Avg Success Rate: <?= number_format($stat['avg_success_rate'], 1) ?>%</small><br>
                        <small>Total Uses: <?= number_format($stat['total_uses']) ?></small>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Filters -->
        <div class="stat-card">
            <form method="GET" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Difficulty</label>
                    <select name="difficulty" class="form-select" onchange="this.form.submit()">
                        <option value="all" <?= $difficulty === 'all' ? 'selected' : '' ?>>All</option>
                        <option value="easy" <?= $difficulty === 'easy' ? 'selected' : '' ?>>Easy</option>
                        <option value="medium" <?= $difficulty === 'medium' ? 'selected' : '' ?>>Medium</option>
                        <option value="hard" <?= $difficulty === 'hard' ? 'selected' : '' ?>>Hard</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Strategy Type</label>
                    <select name="strategy_type" class="form-select" onchange="this.form.submit()">
                        <option value="all" <?= $strategyType === 'all' ? 'selected' : '' ?>>All Types</option>
                        <option value="offensive" <?= $strategyType === 'offensive' ? 'selected' : '' ?>>Offensive</option>
                        <option value="defensive" <?= $strategyType === 'defensive' ? 'selected' : '' ?>>Defensive</option>
                        <option value="counter" <?= $strategyType === 'counter' ? 'selected' : '' ?>>Counter</option>
                        <option value="balanced" <?= $strategyType === 'balanced' ? 'selected' : '' ?>>Balanced</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Game Phase</label>
                    <select name="phase" class="form-select" onchange="this.form.submit()">
                        <option value="all" <?= $gamePhase === 'all' ? 'selected' : '' ?>>All Phases</option>
                        <option value="placement" <?= $gamePhase === 'placement' ? 'selected' : '' ?>>Placement</option>
                        <option value="movement" <?= $gamePhase === 'movement' ? 'selected' : '' ?>>Movement</option>
                        <option value="endgame" <?= $gamePhase === 'endgame' ? 'selected' : '' ?>>Endgame</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">&nbsp;</label>
                    <a href="ai-strategies.php" class="btn btn-secondary w-100">Reset Filters</a>
                </div>
            </form>
        </div>

        <!-- Strategies List -->
        <h5 class="mb-3">Saved Strategies (<?= count($strategies) ?>)</h5>

        <?php foreach ($strategies as $strategy):
            $boardState = json_decode($strategy['board_state'], true);
            $aiPieces = json_decode($strategy['ai_pieces_positions'], true);
            $oppPieces = json_decode($strategy['opponent_pieces_positions'], true);

            // Priority badge
            $priorityClass = $strategy['priority_score'] >= 70 ? 'badge-priority-high' :
                           ($strategy['priority_score'] >= 40 ? 'badge-priority-medium' : 'badge-priority-low');
        ?>
        <div class="strategy-card">
            <div class="row align-items-center">
                <div class="col-md-2">
                    <div class="board-mini">
                        <?php for ($i = 0; $i < 9; $i++):
                            $cell = $boardState[$i] ?? null;
                            $color = $cell === 'O' ? 'text-primary' : ($cell === 'X' ? 'text-danger' : 'text-muted');
                        ?>
                        <div class="board-cell <?= $color ?>"><?= $cell ?? '·' ?></div>
                        <?php endfor; ?>
                    </div>
                </div>
                <div class="col-md-10">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <h6 class="mb-1"><?= htmlspecialchars($strategy['strategy_name']) ?></h6>
                            <div class="small text-muted">
                                <span class="badge bg-secondary"><?= ucfirst($strategy['difficulty_level']) ?></span>
                                <span class="badge bg-info"><?= ucfirst($strategy['strategy_type']) ?></span>
                                <span class="badge bg-dark"><?= ucfirst($strategy['game_phase']) ?></span>
                                <span class="badge <?= $priorityClass ?>">Priority: <?= $strategy['priority_score'] ?></span>
                            </div>
                            <div class="mt-2 small">
                                <strong>Move:</strong> <?= $strategy['move_from'] !== null ? "From {$strategy['move_from']} → " : '' ?>To <strong class="text-primary"><?= $strategy['move_to'] ?></strong>
                                | <strong>Board Score:</strong> <?= number_format($strategy['board_evaluation_score'], 2) ?>
                                | <strong>Threat:</strong> <?= $strategy['threat_level'] ?>/10
                                | <strong>Pattern:</strong> <?= $strategy['opponent_pattern'] ?>
                            </div>
                        </div>
                        <div class="text-end">
                            <div class="badge bg-success mb-1"><?= number_format($strategy['success_rate'], 1) ?>% Win Rate</div>
                            <div class="small text-muted">
                                <?= $strategy['total_uses'] ?> uses
                                (<?= $strategy['success_count'] ?> wins, <?= $strategy['failure_count'] ?> losses)
                            </div>
                            <div class="small text-muted">
                                Last used: <?= $strategy['last_used_at'] ? date('M d, H:i', strtotime($strategy['last_used_at'])) : 'Never' ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>

        <?php if (empty($strategies)): ?>
        <div class="alert alert-info">
            <strong>No strategies found.</strong> The AI will start learning strategies as it plays games. Play some games against the AI to build the strategy database!
        </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
