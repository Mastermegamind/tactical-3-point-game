<?php
require_once 'auth_check.php';
require_once __DIR__ . '/../config/database.php';

// Only admins and super_admins can access
if (!hasPermission('admin')) {
    header('Location: index.php');
    exit;
}

$db = Database::getInstance();
$conn = $db->getConnection();

// Get filter parameters
$difficulty = $_GET['difficulty'] ?? 'all';
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;

// Get AI training data statistics
$stmt = $conn->query("
    SELECT
        difficulty_level as difficulty,
        COUNT(*) as total_records,
        MAX(created_at) as last_training
    FROM ai_training_data
    GROUP BY difficulty_level
");
$difficultyStats = $stmt->fetchAll();

// Get detailed training data
$query = "
    SELECT
        id,
        difficulty_level as difficulty,
        position_weights,
        opening_patterns,
        winning_sequences,
        games_analyzed,
        win_rate,
        avg_moves,
        created_at
    FROM ai_training_data
    WHERE 1=1
";

if ($difficulty !== 'all') {
    $query .= " AND difficulty_level = :difficulty";
}

$query .= " ORDER BY created_at DESC LIMIT :limit";

$stmt = $conn->prepare($query);
if ($difficulty !== 'all') {
    $stmt->bindValue(':difficulty', $difficulty, PDO::PARAM_STR);
}
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->execute();
$trainingRecords = $stmt->fetchAll();

// Get aggregated position weights for visualization
$positionQuery = "
    SELECT
        difficulty_level as difficulty,
        position_weights,
        games_analyzed
    FROM ai_training_data
";
if ($difficulty !== 'all') {
    $positionQuery .= " WHERE difficulty_level = :difficulty";
}
$positionQuery .= " ORDER BY created_at DESC LIMIT 1";

$stmt = $conn->prepare($positionQuery);
if ($difficulty !== 'all') {
    $stmt->bindValue(':difficulty', $difficulty, PDO::PARAM_STR);
}
$stmt->execute();
$latestWeights = $stmt->fetch();

// Parse position weights for heatmap
$positionWeightsArray = [];
if ($latestWeights && $latestWeights['position_weights']) {
    $positionWeightsArray = json_decode($latestWeights['position_weights'], true) ?? [];
}

// Get opening patterns for tree visualization
$openingPatterns = [];
if ($latestWeights) {
    $stmt = $conn->prepare("
        SELECT opening_patterns, winning_sequences
        FROM ai_training_data
        WHERE difficulty_level = :difficulty
        ORDER BY created_at DESC
        LIMIT 1
    ");
    $stmt->execute(['difficulty' => $difficulty === 'all' ? 'medium' : $difficulty]);
    $patterns = $stmt->fetch();
    if ($patterns) {
        $openingPatterns = json_decode($patterns['opening_patterns'], true) ?? [];
    }
}

// Handle delete action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    if ($action === 'delete_record' && hasPermission('admin')) {
        $recordId = (int)$_POST['record_id'];
        $stmt = $conn->prepare("DELETE FROM ai_training_data WHERE id = ?");
        $stmt->execute([$recordId]);
        logAdminActivity('delete_ai_record', "Deleted AI training record ID: $recordId");
        header('Location: ai-knowledge-base.php?difficulty=' . $difficulty);
        exit;
    }

    if ($action === 'reset_all' && hasPermission('super_admin')) {
        $targetDifficulty = $_POST['target_difficulty'] ?? 'all';
        if ($targetDifficulty === 'all') {
            $conn->query("DELETE FROM ai_training_data");
            logAdminActivity('reset_all_ai', "Reset all AI knowledge base");
        } else {
            $stmt = $conn->prepare("DELETE FROM ai_training_data WHERE difficulty_level = ?");
            $stmt->execute([$targetDifficulty]);
            logAdminActivity('reset_ai', "Reset AI knowledge base for: $targetDifficulty");
        }
        header('Location: ai-knowledge-base.php');
        exit;
    }
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>AI Knowledge Base - Admin Panel</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">

    <style>
        * { font-family: 'Inter', sans-serif; }
        body { background: #f8f9fa; }
        .admin-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 1.5rem 0;
            margin-bottom: 2rem;
        }
        .content-card {
            background: white;
            border-radius: 12px;
            padding: 2rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }
        .stat-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 1rem;
        }
        .heatmap-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 8px;
            max-width: 400px;
            margin: 0 auto;
        }
        .heatmap-cell {
            aspect-ratio: 1;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 0.85rem;
            transition: transform 0.2s;
            border: 2px solid rgba(0,0,0,0.1);
        }
        .heatmap-cell:hover {
            transform: scale(1.1);
            z-index: 10;
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
        }
        .pattern-tree {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 1rem;
            margin: 0.5rem 0;
        }
        .pattern-node {
            background: white;
            border: 2px solid #667eea;
            border-radius: 8px;
            padding: 0.5rem 1rem;
            display: inline-block;
            margin: 0.25rem;
            font-size: 0.9rem;
        }
        .navbar {
            background: white;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }
        .navbar-brand { font-weight: 700; }
        .nav-link { color: #495057 !important; }
        .nav-link:hover { color: #667eea !important; }
        .weight-legend {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 1rem;
            margin-top: 1rem;
            font-size: 0.85rem;
        }
        .legend-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .legend-color {
            width: 30px;
            height: 20px;
            border-radius: 4px;
            border: 1px solid rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg">
        <div class="container-fluid">
            <a class="navbar-brand" href="index.php">Admin Panel</a>
            <div class="navbar-nav">
                <a class="nav-link" href="index.php">Dashboard</a>
                <a class="nav-link" href="users.php">Users</a>
                <a class="nav-link" href="games.php">Games</a>
                <a class="nav-link" href="statistics.php">Statistics</a>
                <a class="nav-link" href="ai-training.php">AI Training</a>
                <a class="nav-link active" href="ai-knowledge-base.php">AI Knowledge Base</a>
                <?php if (hasPermission('super_admin')): ?>
                <a class="nav-link" href="admins.php">Admins</a>
                <?php endif; ?>
                <a class="nav-link" href="logout.php">Logout</a>
            </div>
        </div>
    </nav>

    <div class="admin-header">
        <div class="container">
            <h1>🧠 AI Knowledge Base & Neural Network Pathways</h1>
            <p class="mb-0">View and manage AI learning data, position weights, and decision patterns</p>
        </div>
    </div>

    <div class="container">
        <!-- Statistics Cards -->
        <div class="row mb-4">
            <?php foreach ($difficultyStats as $stat): ?>
            <div class="col-md-4">
                <div class="stat-card">
                    <h5><?= strtoupper($stat['difficulty']) ?> AI</h5>
                    <h2><?= number_format($stat['total_records']) ?></h2>
                    <small>Training Records</small>
                    <div class="mt-2">
                        <small>Last trained: <?= $stat['last_training'] ? date('M d, Y H:i', strtotime($stat['last_training'])) : 'Never' ?></small>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Filters -->
        <div class="content-card">
            <form method="GET" class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Difficulty Level</label>
                    <select name="difficulty" class="form-select" onchange="this.form.submit()">
                        <option value="all" <?= $difficulty === 'all' ? 'selected' : '' ?>>All Difficulties</option>
                        <option value="easy" <?= $difficulty === 'easy' ? 'selected' : '' ?>>Easy</option>
                        <option value="medium" <?= $difficulty === 'medium' ? 'selected' : '' ?>>Medium</option>
                        <option value="hard" <?= $difficulty === 'hard' ? 'selected' : '' ?>>Hard</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Records Limit</label>
                    <select name="limit" class="form-select" onchange="this.form.submit()">
                        <option value="25" <?= $limit === 25 ? 'selected' : '' ?>>25 Records</option>
                        <option value="50" <?= $limit === 50 ? 'selected' : '' ?>>50 Records</option>
                        <option value="100" <?= $limit === 100 ? 'selected' : '' ?>>100 Records</option>
                        <option value="500" <?= $limit === 500 ? 'selected' : '' ?>>500 Records</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Actions</label>
                    <div class="d-flex gap-2">
                        <?php if (hasPermission('super_admin')): ?>
                        <button type="button" class="btn btn-danger w-100" onclick="resetKnowledgeBase()">
                            🔄 Reset Knowledge Base
                        </button>
                        <?php endif; ?>
                    </div>
                </div>
            </form>
        </div>

        <!-- Position Weights Heatmap -->
        <?php if (!empty($positionWeightsArray)): ?>
        <div class="content-card">
            <h5 class="mb-4">📊 Position Weights Heatmap (<?= strtoupper($difficulty !== 'all' ? $difficulty : 'medium') ?>)</h5>
            <p class="text-muted small">Shows the strategic value the AI assigns to each board position. Higher values = stronger positions.</p>

            <div class="heatmap-grid">
                <?php for ($i = 0; $i < 16; $i++):
                    $weight = $positionWeightsArray[$i] ?? 0;
                    $normalized = max(0, min(100, $weight)); // Normalize to 0-100

                    // Color gradient from red (low) to yellow (medium) to green (high)
                    if ($normalized < 50) {
                        $red = 255;
                        $green = round($normalized * 2 * 2.55);
                    } else {
                        $red = round((100 - $normalized) * 2 * 2.55);
                        $green = 255;
                    }
                    $blue = 50;
                    $bgColor = "rgb($red, $green, $blue)";
                    $textColor = $normalized > 50 ? '#000' : '#fff';
                ?>
                <div class="heatmap-cell"
                     style="background-color: <?= $bgColor ?>; color: <?= $textColor ?>;"
                     title="Position <?= $i ?>: Weight <?= round($weight, 2) ?>"
                     data-bs-toggle="tooltip">
                    <?= round($weight, 1) ?>
                </div>
                <?php endfor; ?>
            </div>

            <div class="weight-legend">
                <span><strong>Legend:</strong></span>
                <div class="legend-item">
                    <div class="legend-color" style="background: rgb(255, 50, 50);"></div>
                    <span>Low (Weak)</span>
                </div>
                <div class="legend-item">
                    <div class="legend-color" style="background: rgb(255, 255, 50);"></div>
                    <span>Medium</span>
                </div>
                <div class="legend-item">
                    <div class="legend-color" style="background: rgb(50, 255, 50);"></div>
                    <span>High (Strong)</span>
                </div>
            </div>

            <div class="mt-4">
                <h6>Position Analysis:</h6>
                <div class="row">
                    <div class="col-md-4">
                        <strong>Strongest Positions:</strong>
                        <ul class="small">
                            <?php
                            arsort($positionWeightsArray);
                            $top3 = array_slice($positionWeightsArray, 0, 3, true);
                            foreach ($top3 as $pos => $weight):
                            ?>
                            <li>Position <?= $pos ?>: <?= round($weight, 2) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    <div class="col-md-4">
                        <strong>Weakest Positions:</strong>
                        <ul class="small">
                            <?php
                            $bottom3 = array_slice($positionWeightsArray, -3, 3, true);
                            foreach ($bottom3 as $pos => $weight):
                            ?>
                            <li>Position <?= $pos ?>: <?= round($weight, 2) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    <div class="col-md-4">
                        <strong>Statistics:</strong>
                        <ul class="small">
                            <li>Average Weight: <?= round(array_sum($positionWeightsArray) / count($positionWeightsArray), 2) ?></li>
                            <li>Total Positions: <?= count($positionWeightsArray) ?></li>
                            <li>Games Analyzed: <?= number_format($latestWeights['games_analyzed'] ?? 0) ?></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Opening Patterns Visualization -->
        <?php if (!empty($openingPatterns)): ?>
        <div class="content-card">
            <h5 class="mb-3">🌳 Opening Patterns & Decision Tree</h5>
            <p class="text-muted small">Common opening moves learned by the AI, organized by frequency and success rate.</p>

            <div class="row">
                <?php
                $sortedPatterns = $openingPatterns;
                if (is_array($sortedPatterns)) {
                    arsort($sortedPatterns);
                    $patternCount = 0;
                    foreach (array_slice($sortedPatterns, 0, 12) as $pattern => $score):
                        $patternCount++;
                ?>
                <div class="col-md-4 mb-3">
                    <div class="pattern-tree">
                        <div class="pattern-node">
                            <strong>Pattern #<?= $patternCount ?></strong><br>
                            <small>Move: <?= htmlspecialchars($pattern) ?></small><br>
                            <span class="badge bg-success">Score: <?= round($score, 2) ?></span>
                        </div>
                    </div>
                </div>
                <?php
                    endforeach;
                }
                ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Training Records Table -->
        <div class="content-card">
            <h5 class="mb-3">📚 Training Records History</h5>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Difficulty</th>
                            <th>Games Analyzed</th>
                            <th>Win Rate</th>
                            <th>Avg Moves</th>
                            <th>Trained At</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($trainingRecords)): ?>
                        <tr>
                            <td colspan="7" class="text-center text-muted">No training records found</td>
                        </tr>
                        <?php else: ?>
                        <?php foreach ($trainingRecords as $record): ?>
                        <tr>
                            <td><?= $record['id'] ?></td>
                            <td><span class="badge bg-primary"><?= strtoupper($record['difficulty']) ?></span></td>
                            <td><?= number_format($record['games_analyzed']) ?></td>
                            <td><?= round($record['win_rate'], 1) ?>%</td>
                            <td><?= round($record['avg_moves'], 1) ?></td>
                            <td><?= date('M d, Y H:i', strtotime($record['created_at'])) ?></td>
                            <td>
                                <button class="btn btn-sm btn-info" onclick="viewRecordDetails(<?= htmlspecialchars(json_encode($record)) ?>)">
                                    👁️ View
                                </button>
                                <?php if (hasPermission('admin')): ?>
                                <button class="btn btn-sm btn-danger" onclick="deleteRecord(<?= $record['id'] ?>)">
                                    🗑️ Delete
                                </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        // Initialize tooltips
        const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]');
        const tooltipList = [...tooltipTriggerList].map(tooltipTriggerEl => new bootstrap.Tooltip(tooltipTriggerEl));

        function viewRecordDetails(record) {
            const positionWeights = JSON.parse(record.position_weights || '{}');
            const openingPatterns = JSON.parse(record.opening_patterns || '{}');
            const winningSequences = JSON.parse(record.winning_sequences || '{}');

            let html = `
                <div class="text-start">
                    <h6>Training Record #${record.id}</h6>
                    <p><strong>Difficulty:</strong> ${record.difficulty}</p>
                    <p><strong>Games Analyzed:</strong> ${record.games_analyzed}</p>
                    <p><strong>Win Rate:</strong> ${record.win_rate}%</p>
                    <p><strong>Average Moves:</strong> ${record.avg_moves}</p>
                    <hr>
                    <h6>Position Weights (Sample):</h6>
                    <pre class="small bg-light p-2 rounded">${JSON.stringify(positionWeights, null, 2).substring(0, 500)}...</pre>
                    <h6>Opening Patterns (Sample):</h6>
                    <pre class="small bg-light p-2 rounded">${JSON.stringify(openingPatterns, null, 2).substring(0, 500)}...</pre>
                    <h6>Winning Sequences (Sample):</h6>
                    <pre class="small bg-light p-2 rounded">${JSON.stringify(winningSequences, null, 2).substring(0, 500)}...</pre>
                </div>
            `;

            Swal.fire({
                title: 'Training Record Details',
                html: html,
                width: '800px',
                confirmButtonText: 'Close'
            });
        }

        function deleteRecord(recordId) {
            Swal.fire({
                title: 'Delete Training Record?',
                text: 'This will permanently remove this AI training record. This cannot be undone.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                confirmButtonText: 'Yes, delete it'
            }).then((result) => {
                if (result.isConfirmed) {
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.innerHTML = `
                        <input type="hidden" name="action" value="delete_record">
                        <input type="hidden" name="record_id" value="${recordId}">
                    `;
                    document.body.appendChild(form);
                    form.submit();
                }
            });
        }

        function resetKnowledgeBase() {
            Swal.fire({
                title: 'Reset AI Knowledge Base',
                html: `
                    <p>This will delete AI training data. Select which difficulty to reset:</p>
                    <select id="target_difficulty" class="form-select">
                        <option value="all">All Difficulties (Complete Reset)</option>
                        <option value="easy">Easy Only</option>
                        <option value="medium">Medium Only</option>
                        <option value="hard">Hard Only</option>
                    </select>
                `,
                icon: 'error',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                confirmButtonText: 'Yes, reset',
                preConfirm: () => {
                    return document.getElementById('target_difficulty').value;
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.innerHTML = `
                        <input type="hidden" name="action" value="reset_all">
                        <input type="hidden" name="target_difficulty" value="${result.value}">
                    `;
                    document.body.appendChild(form);
                    form.submit();
                }
            });
        }
    </script>
</body>
</html>
