// 9 points laid out like a 3x3 grid:
// 0 1 2
// 3 4 5
// 6 7 8

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

// Win lines (same as tic-tac-toe)
const winLines = [
  [0, 1, 2],
  [3, 4, 5],
  [6, 7, 8],
  [0, 3, 6],
  [1, 4, 7],
  [2, 5, 8],
  [0, 4, 8],
  [2, 4, 6],
];

// Graph connections (legal moves along drawn lines):
// Outer edges + center cross + diagonals
const neighbors = {
  0: [1, 3, 4],
  1: [0, 2, 4],
  2: [1, 5, 4],
  3: [0, 6, 4],
  4: [0, 1, 2, 3, 5, 6, 7, 8],
  5: [2, 8, 4],
  6: [3, 7, 4],
  7: [6, 8, 4],
  8: [5, 7, 4],
};

const pointsLayer = document.getElementById("pointsLayer");
const marksLayer = document.getElementById("marksLayer");

const turnText = document.getElementById("turnText");
const statusText = document.getElementById("statusText");
const resetBtn = document.getElementById("resetBtn");
const newBtn = document.getElementById("newBtn");
const gameModeSelect = document.getElementById("gameModeSelect");
const playerXNameInput = document.getElementById("playerXName");
const playerONameInput = document.getElementById("playerOName");
const playerXScoreEl = document.getElementById("playerXScore");
const playerOScoreEl = document.getElementById("playerOScore");
const playerXScoreName = document.getElementById("playerXScoreName");
const playerOScoreName = document.getElementById("playerOScoreName");

let board = Array(9).fill(null);      // 'X' | 'O' | null
let turn = "X";
let gameOver = false;

// Player names and scores
let playerXName = "Player X";
let playerOName = "Player O";
let scores = { X: 0, O: 0 };

// Phase control
let phase = "placement";              // "placement" | "movement"
let placedCount = { X: 0, O: 0 };

// Movement selection
let selectedFrom = null;              // index of selected piece to move
let currentMovingPlayer = "X";        // Track who can move in movement phase (PvP)

// Computer opponent settings
let gameMode = "pvp";                 // "pvp" | "pvc-easy" | "pvc-medium" | "pvc-hard"
let computerPlayer = "O";             // Computer always plays as O
let computerMoving = false;           // Prevent multiple computer moves

init();

function init() {
  renderPoints();
  renderMarks();
  updateUI();
  updateScoreDisplay();

  resetBtn.addEventListener("click", resetBoard);
  newBtn.addEventListener("click", newGame);
  gameModeSelect.addEventListener("change", onGameModeChange);
  playerXNameInput.addEventListener("input", updatePlayerNames);
  playerONameInput.addEventListener("input", updatePlayerNames);

  // Load saved names from localStorage if available
  const savedXName = localStorage.getItem("playerXName");
  if (savedXName) {
    playerXName = savedXName;
    playerXNameInput.value = savedXName;
  }

  // Set Player O name based on initial game mode
  if (gameMode !== "pvp") {
    playerOName = "AI";
    playerONameInput.value = "AI";
    playerONameInput.disabled = true;
  } else {
    const savedOName = localStorage.getItem("playerOName");
    if (savedOName && savedOName !== "AI") {
      playerOName = savedOName;
      playerONameInput.value = savedOName;
    }
  }

  updateScoreDisplay();
}

function onGameModeChange() {
  gameMode = gameModeSelect.value;

  // Update Player O name based on mode
  if (gameMode !== "pvp") {
    // Computer mode - set to "AI" and disable input
    playerOName = "AI";
    playerONameInput.value = "AI";
    playerONameInput.disabled = true;
    playerONameInput.placeholder = "AI";
  } else {
    // PvP mode - enable input and restore saved name
    playerONameInput.disabled = false;
    playerONameInput.placeholder = "Player O";
    const savedOName = localStorage.getItem("playerOName");
    if (savedOName && savedOName !== "AI") {
      playerOName = savedOName;
      playerONameInput.value = savedOName;
    } else {
      playerOName = "Player O";
      playerONameInput.value = "";
    }
  }

  updateScoreDisplay();
  resetBoard();
}

function updatePlayerNames() {
  playerXName = playerXNameInput.value.trim() || "Player X";
  playerOName = playerONameInput.value.trim() || "Player O";

  // Save to localStorage
  localStorage.setItem("playerXName", playerXName);
  localStorage.setItem("playerOName", playerOName);

  updateScoreDisplay();
}

function renderPoints() {
  pointsLayer.innerHTML = "";

  points.forEach((p) => {
    const c = document.createElementNS("http://www.w3.org/2000/svg", "circle");
    c.setAttribute("cx", p.x);
    c.setAttribute("cy", p.y);
    c.setAttribute("r", 3.5);
    c.setAttribute("fill", "#e2e8f0");
    c.setAttribute("stroke", "#94a3b8");
    c.setAttribute("stroke-width", "1.5");
    c.classList.add("point");
    c.dataset.id = String(p.id);

    c.addEventListener("click", () => onPointClick(p.id));
    pointsLayer.appendChild(c);
  });
}

function renderMarks() {
  marksLayer.innerHTML = "";

  // Render pebbles
  board.forEach((val, idx) => {
    if (!val) return;
    const p = points[idx];

    // Create pebble group
    const g = document.createElementNS("http://www.w3.org/2000/svg", "g");
    g.classList.add("pebble");
    if (phase === "movement" && selectedFrom === idx) {
      g.classList.add("selected");
    }

    // Pebble colors
    const isX = val === "X";
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

    // Main pebble circle
    const pebble = document.createElementNS("http://www.w3.org/2000/svg", "circle");
    pebble.setAttribute("cx", p.x);
    pebble.setAttribute("cy", p.y);
    pebble.setAttribute("r", 4.8);
    pebble.setAttribute("fill", `url(#${gradientId})`);
    pebble.setAttribute("stroke", isX ? "#2563eb" : "#db2777");
    pebble.setAttribute("stroke-width", "1.2");

    // Highlight effect
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

  // update point states and highlighting
  const circles = pointsLayer.querySelectorAll("circle.point");
  circles.forEach((c) => {
    const id = Number(c.dataset.id);

    c.classList.toggle("disabled", gameOver);

    // highlight legal destinations in movement phase
    if (phase === "movement" && selectedFrom !== null) {
      const isEmpty = board[id] === null;
      c.setAttribute("fill", isEmpty ? "#e9f5ff" : "white");
    } else {
      c.setAttribute("fill", "white");
    }
  });
}

function onPointClick(id) {
  if (gameOver) return;

  if (phase === "placement") {
    // Prevent player input during computer's turn in placement phase
    if (isComputerTurn()) return;
    handlePlacement(id);
    return;
  }

  if (phase === "movement") {
    // In movement phase, allow free movement (no turn restrictions)
    handleMovement(id);
    return;
  }
}

function handlePlacement(id) {
  if (board[id]) return; // must place on empty spot
  if (placedCount[turn] >= 3) return; // safety

  board[id] = turn;
  placedCount[turn]++;

  // Check win during placement (optional, but usually allowed)
  const winner = checkWinner();
  if (winner) {
    const winnerName = winner === "X" ? playerXName : playerOName;
    endGame(`${winnerName} Wins!`, winner);
    return;
  }

  // If both have placed 3, start movement phase
  if (placedCount.X === 3 && placedCount.O === 3) {
    phase = "movement";
    selectedFrom = null;
    currentMovingPlayer = "X"; // X starts first in movement phase
    if (gameMode === "pvp") {
      statusText.textContent = `Movement Phase - ${playerXName}'s turn`;
    } else {
      statusText.textContent = "Movement Phase - Click a pebble to move";
    }
  } else {
    // Continue placement turns
    turn = turn === "X" ? "O" : "X";
  }

  renderMarks();
  updateUI();

  // Trigger computer move if it's computer's turn
  makeComputerMove();
}

function handleMovement(clickedId) {
  // Determine which player is allowed to move
  const allowedPlayer = gameMode === "pvp" ? currentMovingPlayer : "X";

  // Step 1: choose a piece
  if (selectedFrom === null) {
    const clickedPiece = board[clickedId];
    if (!clickedPiece) return; // must pick a piece, not empty space

    // Only allow selecting your own pieces
    if (clickedPiece !== allowedPlayer) return;

    selectedFrom = clickedId;
    renderMarks();
    const color = clickedPiece === "X" ? "Blue" : "Pink";
    statusText.textContent = `${color} pebble selected - Click where to move`;
    return;
  }

  // Step 2: if click another piece, switch selection
  if (board[clickedId]) {
    // Only allow selecting your own pieces
    if (board[clickedId] !== allowedPlayer) return;

    selectedFrom = clickedId;
    renderMarks();
    const color = board[clickedId] === "X" ? "Blue" : "Pink";
    statusText.textContent = `${color} pebble selected - Click where to move`;
    return;
  }

  // Step 3: attempt move to any empty spot
  const to = clickedId;
  const from = selectedFrom;

  const isEmpty = board[to] === null;

  if (!isEmpty) {
    // invalid move; keep selection
    statusText.textContent = `Invalid move - that spot is occupied`;
    return;
  }

  // Apply move
  board[to] = board[from];
  board[from] = null;
  selectedFrom = null;

  // Check win
  const winner = checkWinner();
  if (winner) {
    const winnerName = winner === "X" ? playerXName : playerOName;
    endGame(`${winnerName} Wins!`, winner);
    return;
  }

  // Switch turns in PvP mode
  if (gameMode === "pvp") {
    currentMovingPlayer = currentMovingPlayer === "X" ? "O" : "X";
    const nextPlayer = currentMovingPlayer === "X" ? playerXName : playerOName;
    statusText.textContent = `Movement Phase - ${nextPlayer}'s turn`;
  } else {
    statusText.textContent = "Click a pebble to move it";
  }

  renderMarks();
  updateUI();

  // Trigger computer move after player moves (if playing vs computer)
  if (gameMode !== "pvp") {
    makeComputerMove();
  }
}

function checkWinner() {
  for (const line of winLines) {
    const [a, b, c] = line;
    if (board[a] && board[a] === board[b] && board[a] === board[c]) {
      return board[a];
    }
  }
  return null;
}

function endGame(message, winnerSymbol = null) {
  gameOver = true;
  statusText.textContent = message;
  selectedFrom = null;

  // Update score if there's a winner
  if (winnerSymbol && (winnerSymbol === "X" || winnerSymbol === "O")) {
    scores[winnerSymbol]++;
    updateScoreDisplay();
  }

  renderMarks();
  updateUI();
}

function updateScoreDisplay() {
  playerXScoreName.textContent = playerXName;
  playerXScoreEl.textContent = scores.X;
  playerOScoreName.textContent = playerOName;
  playerOScoreEl.textContent = scores.O;
}

function updateUI() {
  // Update turn display with color names
  if (phase === "placement") {
    turnText.textContent = turn === "X" ? "Blue" : "Pink";
  } else {
    // In movement phase, show current moving player
    const currentPlayer = gameMode === "pvp" ? currentMovingPlayer : "X";
    turnText.textContent = currentPlayer === "X" ? "Blue" : "Pink";
  }

  if (!gameOver) {
    if (phase === "placement") {
      statusText.textContent =
        `Place your pebbles (${placedCount.X}/3 Blue, ${placedCount.O}/3 Pink)`;
    } else {
      // movement
      if (selectedFrom === null) {
        if (gameMode === "pvp") {
          const playerName = currentMovingPlayer === "X" ? playerXName : playerOName;
          statusText.textContent = `${playerName}'s turn to move`;
        } else {
          statusText.textContent = `Click a pebble to move it`;
        }
      }
    }
  }
}

function resetBoard() {
  board = Array(9).fill(null);
  turn = "X";
  gameOver = false;
  phase = "placement";
  placedCount = { X: 0, O: 0 };
  selectedFrom = null;
  currentMovingPlayer = "X";
  computerMoving = false;

  statusText.textContent = "Ready to play!";
  renderMarks();
  updateUI();

  // Start computer move if computer plays first (very unlikely but possible)
  makeComputerMove();
}

function newGame() {
  resetBoard();
}

// ============================================
// COMPUTER AI
// ============================================

function isComputerTurn() {
  return gameMode !== "pvp" && turn === computerPlayer && !gameOver;
}

function makeComputerMove() {
  // In placement phase, check if it's computer's turn
  if (phase === "placement" && !isComputerTurn()) return;

  // In movement phase, only allow one computer move at a time
  if (phase === "movement" && (gameMode === "pvp" || computerMoving)) return;

  // Prevent multiple simultaneous computer moves
  if (computerMoving) return;
  computerMoving = true;

  // Add a small delay to make it feel more natural
  setTimeout(() => {
    if (phase === "placement") {
      computerPlacement();
    } else if (phase === "movement") {
      computerMovement();
    }
    computerMoving = false;
  }, 500);
}

// Computer placement strategy
function computerPlacement() {
  const difficulty = gameMode.split("-")[1]; // "easy", "medium", or "hard"

  let move = -1;

  if (difficulty === "hard") {
    // Try to win
    move = findWinningMove(computerPlayer);
    // Block opponent from winning
    if (move === -1) move = findWinningMove(turn === "X" ? "O" : "X");
    // Take center if available
    if (move === -1 && board[4] === null) move = 4;
    // Take corners
    if (move === -1) move = findFirstEmpty([0, 2, 6, 8]);
    // Take any edge
    if (move === -1) move = findFirstEmpty([1, 3, 5, 7]);
  } else if (difficulty === "medium") {
    // 50% chance to play optimally
    if (Math.random() < 0.5) {
      move = findWinningMove(computerPlayer);
      if (move === -1) move = findWinningMove(turn === "X" ? "O" : "X");
    }
    // Otherwise random
    if (move === -1) move = getRandomEmptySpot();
  } else {
    // Easy: pure random with slight preference for center
    if (Math.random() < 0.3 && board[4] === null) {
      move = 4;
    } else {
      move = getRandomEmptySpot();
    }
  }

  if (move !== -1) {
    handlePlacement(move);
  }
}

// Computer movement strategy
function computerMovement() {
  const difficulty = gameMode.split("-")[1];

  let fromTo = null;
  const opponent = turn === "X" ? "O" : "X";

  if (difficulty === "hard") {
    // Try to win
    fromTo = findWinningMovementMove(computerPlayer);
    // Block opponent
    if (!fromTo) fromTo = findWinningMovementMove(opponent);
    // Make strategic move
    if (!fromTo) fromTo = findBestMovementMove();
  } else if (difficulty === "medium") {
    // 60% chance to play smart
    if (Math.random() < 0.6) {
      fromTo = findWinningMovementMove(computerPlayer);
      if (!fromTo) fromTo = findWinningMovementMove(opponent);
    }
    if (!fromTo) fromTo = getRandomMovementMove();
  } else {
    // Easy: random valid move
    fromTo = getRandomMovementMove();
  }

  if (fromTo) {
    selectedFrom = fromTo.from;
    handleMovement(fromTo.to);
  }
}

// Helper: find a move that would create a winning line
function findWinningMove(player) {
  for (const line of winLines) {
    const [a, b, c] = line;
    const vals = [board[a], board[b], board[c]];

    // Check if two are player's and one is empty
    if (vals.filter(v => v === player).length === 2 && vals.filter(v => v === null).length === 1) {
      if (board[a] === null) return a;
      if (board[b] === null) return b;
      if (board[c] === null) return c;
    }
  }
  return -1;
}

// Helper: find first empty spot from a list
function findFirstEmpty(positions) {
  for (const pos of positions) {
    if (board[pos] === null) return pos;
  }
  return -1;
}

// Helper: get random empty spot
function getRandomEmptySpot() {
  const empty = [];
  for (let i = 0; i < 9; i++) {
    if (board[i] === null) empty.push(i);
  }
  return empty.length > 0 ? empty[Math.floor(Math.random() * empty.length)] : -1;
}

// Helper: find a movement that would win
function findWinningMovementMove(player) {
  // Try each piece of the player
  for (let from = 0; from < 9; from++) {
    if (board[from] !== player) continue;

    // Try moving to any empty spot
    for (let to = 0; to < 9; to++) {
      if (board[to] !== null) continue;

      // Simulate the move
      const tempBoard = [...board];
      tempBoard[to] = tempBoard[from];
      tempBoard[from] = null;

      // Check if this creates a win
      for (const line of winLines) {
        const [a, b, c] = line;
        if (tempBoard[a] === player && tempBoard[b] === player && tempBoard[c] === player) {
          return { from, to };
        }
      }
    }
  }
  return null;
}

// Helper: find the best strategic movement
function findBestMovementMove() {
  const moves = getAllValidMovementMoves(computerPlayer);
  if (moves.length === 0) return null;

  // Prefer moves that create more winning opportunities
  let bestMove = moves[0];
  let bestScore = -1;

  for (const move of moves) {
    const score = evaluateMovementPosition(move);
    if (score > bestScore) {
      bestScore = score;
      bestMove = move;
    }
  }

  return bestMove;
}

// Helper: evaluate a movement position
function evaluateMovementPosition(move) {
  // Simulate the move
  const tempBoard = [...board];
  tempBoard[move.to] = tempBoard[move.from];
  tempBoard[move.from] = null;

  let score = 0;

  // Count potential winning lines
  for (const line of winLines) {
    const [a, b, c] = line;
    const vals = [tempBoard[a], tempBoard[b], tempBoard[c]];
    const myCount = vals.filter(v => v === computerPlayer).length;
    const emptyCount = vals.filter(v => v === null).length;

    if (myCount === 2 && emptyCount === 1) score += 10; // Almost winning
    if (myCount === 1 && emptyCount === 2) score += 2;  // Potential
  }

  // Prefer center position
  if (move.to === 4) score += 3;

  return score;
}

// Helper: get random valid movement
function getRandomMovementMove() {
  const moves = getAllValidMovementMoves(computerPlayer);
  return moves.length > 0 ? moves[Math.floor(Math.random() * moves.length)] : null;
}

// Helper: get all valid movement moves for a player
function getAllValidMovementMoves(player) {
  const moves = [];
  for (let from = 0; from < 9; from++) {
    if (board[from] !== player) continue;
    for (let to = 0; to < 9; to++) {
      if (board[to] === null) {
        moves.push({ from, to });
      }
    }
  }
  return moves;
}
