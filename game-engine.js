// Enhanced game engine with save system, move tracking, and online sync
// Based on the original game.js with added features

// Game state variables (defined in play.php)
// SESSION_ID, GAME_MODE, IS_ONLINE, PLAYER_SIDE, COMPUTER_PLAYER

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

const pointsLayer = document.getElementById("pointsLayer");
const marksLayer = document.getElementById("marksLayer");
const turnText = document.getElementById("turnText");
const statusText = document.getElementById("statusText");

let board = Array(9).fill(null);
let turn = "X";
let gameOver = false;
let phase = "placement";
let placedCount = { X: 0, O: 0 };
let selectedFrom = null;
let currentMovingPlayer = "X";
let computerMoving = false;
let moveNumber = 0;
let moveStartTime = Date.now();

// Initialize game
init();

function init() {
  renderPoints();
  renderMarks();
  updateUI();
  loadGameState();
}

async function loadGameState() {
  try {
    const response = await fetch(`api/get-game-state.php?session_id=${SESSION_ID}`);
    const data = await response.json();

    if (data.success && data.board_state) {
      const state = JSON.parse(data.board_state);
      board = state.board || Array(9).fill(null);
      placedCount = state.placedCount || { X: 0, O: 0 };
      phase = state.phase || 'placement';
      turn = state.turn || 'X';
      currentMovingPlayer = state.currentMovingPlayer || 'X';

      renderMarks();
      updateUI();
    }
  } catch (error) {
    console.error('Failed to load game state:', error);
  }
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

  board.forEach((val, idx) => {
    if (!val) return;
    const p = points[idx];

    const g = document.createElementNS("http://www.w3.org/2000/svg", "g");
    g.classList.add("pebble");
    if (phase === "movement" && selectedFrom === idx) {
      g.classList.add("selected");
    }

    // Add click handler for movement phase
    if (phase === "movement") {
      g.addEventListener("click", (e) => {
        e.stopPropagation();
        onPointClick(idx);
      });
    }

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

  const circles = pointsLayer.querySelectorAll("circle.point");
  circles.forEach((c) => {
    const id = Number(c.dataset.id);
    c.classList.toggle("disabled", gameOver);

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

  // In online mode, only allow moves if it's your turn
  if (IS_ONLINE && turn !== PLAYER_SIDE) {
    statusText.textContent = "Wait for opponent's turn";
    return;
  }

  if (phase === "placement") {
    if (isComputerTurn()) return;
    handlePlacement(id);
    return;
  }

  if (phase === "movement") {
    handleMovement(id);
    return;
  }
}

async function handlePlacement(id) {
  if (board[id]) return;
  if (placedCount[turn] >= 3) return;

  const boardBefore = [...board];
  moveStartTime = Date.now();

  const currentPlayer = turn;
  board[id] = currentPlayer;
  placedCount[currentPlayer]++;

  // Save move to database
  await recordMove('placement', null, id, boardBefore, 0, currentPlayer);

  const winner = checkWinner();
  if (winner) {
    endGame(winner);
    return;
  }

  if (placedCount.X === 3 && placedCount.O === 3) {
    phase = "movement";
    selectedFrom = null;
    currentMovingPlayer = "X";
    turn = "X"; // CRITICAL: Set turn to X (player) at start of movement phase
    statusText.textContent = "Movement Phase - Click a pebble to move";

    renderMarks();
    updateUI();
    await saveGameState();

    // Don't call makeComputerMove here - it's player's turn to move first
    return;
  } else {
    turn = turn === "X" ? "O" : "X";
  }

  renderMarks();
  updateUI();
  await saveGameState();

  makeComputerMove();
}

async function handleMovement(clickedId) {
  // Determine which player is allowed to move
  let allowedPlayer;
  if (IS_ONLINE) {
    allowedPlayer = PLAYER_SIDE;
  } else if (GAME_MODE === "pvp") {
    allowedPlayer = currentMovingPlayer;
  } else {
    // In AI mode, use the current turn (X for player, O for AI)
    allowedPlayer = turn;
  }

  if (selectedFrom === null) {
    const clickedPiece = board[clickedId];
    if (!clickedPiece || clickedPiece !== allowedPlayer) return;

    selectedFrom = clickedId;
    moveStartTime = Date.now();
    renderMarks();
    const color = clickedPiece === "X" ? "Blue" : "Pink";
    statusText.textContent = `${color} pebble selected - Click where to move`;
    return;
  }

  if (board[clickedId]) {
    if (board[clickedId] !== allowedPlayer) return;

    selectedFrom = clickedId;
    moveStartTime = Date.now();
    renderMarks();
    const color = board[clickedId] === "X" ? "Blue" : "Pink";
    statusText.textContent = `${color} pebble selected - Click where to move`;
    return;
  }

  const to = clickedId;
  const from = selectedFrom;
  const isEmpty = board[to] === null;

  if (!isEmpty) {
    statusText.textContent = `Invalid move - that spot is occupied`;
    return;
  }

  // CRITICAL: Validate that the piece being moved belongs to the allowed player
  // This prevents the AI from moving player pieces
  if (board[from] !== allowedPlayer) {
    console.error('Invalid move attempt: piece does not belong to current player');
    selectedFrom = null;
    renderMarks();
    return;
  }

  const boardBefore = [...board];
  const currentPlayer = board[from]; // Store who is moving BEFORE we move the piece

  board[to] = board[from];
  board[from] = null;
  selectedFrom = null;

  // Save move with the player who actually made the move
  const thinkTime = Date.now() - moveStartTime;
  await recordMove('movement', from, to, boardBefore, thinkTime, currentPlayer);

  const winner = checkWinner();
  if (winner) {
    endGame(winner);
    return;
  }

  // Switch turns - CRITICAL: Update turn for both PvP and AI modes
  if (GAME_MODE === "pvp" || IS_ONLINE) {
    currentMovingPlayer = currentMovingPlayer === "X" ? "O" : "X";
    turn = currentMovingPlayer;
  } else {
    // In AI mode, switch between X and O
    turn = turn === "X" ? "O" : "X";
  }

  renderMarks();
  updateUI();
  await saveGameState();

  // Only trigger AI if it's computer's turn
  if (GAME_MODE !== "pvp" && !IS_ONLINE && turn === COMPUTER_PLAYER) {
    makeComputerMove();
  }
}

async function recordMove(moveType, fromPosition, toPosition, boardBefore, thinkTime = 0, player = null) {
  moveNumber++;

  // If player not provided, use current turn (but this should always be provided now)
  const movingPlayer = player || turn;

  try {
    await fetch('api/save-move.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        session_id: SESSION_ID,
        move_number: moveNumber,
        player: movingPlayer,
        move_type: moveType,
        from_position: fromPosition,
        to_position: toPosition,
        board_before: boardBefore,
        board_after: board,
        think_time_ms: thinkTime
      })
    });
  } catch (error) {
    console.error('Failed to record move:', error);
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

async function endGame(winnerSymbol) {
  gameOver = true;
  const winnerText = winnerSymbol === PLAYER_SIDE ? "You Win!" :
                    (IS_ONLINE ? "Opponent Wins!" :
                    (winnerSymbol === "X" ? "Blue Wins!" : "Pink Wins!"));
  statusText.textContent = winnerText;
  selectedFrom = null;

  renderMarks();
  updateUI();

  // Save completion to database
  try {
    // Calculate winnerId properly
    let winnerId = null;

    if (IS_ONLINE) {
      // In online mode, winner is current user if they won, otherwise it's the opponent
      winnerId = (winnerSymbol === PLAYER_SIDE) ? USER_ID : null;
    } else if (GAME_MODE.startsWith('pvc')) {
      // In AI mode, winner is current user if X won, null if AI (O) won
      winnerId = (winnerSymbol === "X") ? USER_ID : null;
    } else {
      // In PvP mode, player1 is always X
      winnerId = (winnerSymbol === "X") ? USER_ID : null;
    }

    await fetch('api/complete-game.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        session_id: SESSION_ID,
        winner_id: winnerId
      })
    });

    // Redirect to dashboard after 3 seconds
    setTimeout(() => {
      window.location.href = 'dashboard.php';
    }, 3000);
  } catch (error) {
    console.error('Failed to complete game:', error);
  }
}

function updateUI() {
  if (phase === "placement") {
    turnText.textContent = turn === "X" ? "Blue" : "Pink";
  } else {
    // In movement phase, show current turn
    const currentPlayer = GAME_MODE === "pvp" || IS_ONLINE ? currentMovingPlayer : turn;
    turnText.textContent = currentPlayer === "X" ? "Blue" : "Pink";
  }

  if (!gameOver) {
    if (phase === "placement") {
      statusText.textContent =
        `Place your pebbles (${placedCount.X}/3 Blue, ${placedCount.O}/3 Pink)`;
    } else {
      if (selectedFrom === null) {
        if (IS_ONLINE && turn !== PLAYER_SIDE) {
          statusText.textContent = "Opponent's turn";
        } else if (GAME_MODE !== "pvp" && !IS_ONLINE && turn === COMPUTER_PLAYER) {
          statusText.textContent = "AI is thinking...";
        } else {
          statusText.textContent = "Click a pebble to move it";
        }
      }
    }
  }
}

// Computer AI
function isComputerTurn() {
  return GAME_MODE !== "pvp" && !IS_ONLINE && turn === COMPUTER_PLAYER && !gameOver;
}

function makeComputerMove() {
  // Don't make moves in PvP or online mode
  if (GAME_MODE === "pvp" || IS_ONLINE) return;

  // In placement phase, only move if it's computer's turn
  if (phase === "placement" && !isComputerTurn()) return;

  // In movement phase, only move if it's computer's turn
  if (phase === "movement" && turn !== COMPUTER_PLAYER) return;

  // Prevent multiple simultaneous moves
  if (computerMoving) return;

  computerMoving = true;

  setTimeout(() => {
    if (phase === "placement") {
      computerPlacement();
    } else if (phase === "movement") {
      computerMovement();
    }
    computerMoving = false;
  }, 500);
}

function computerPlacement() {
  const difficulty = GAME_MODE.split("-")[1];
  let move = -1;

  if (difficulty === "hard") {
    move = findWinningMove(COMPUTER_PLAYER);
    if (move === -1) move = findWinningMove(turn === "X" ? "O" : "X");
    if (move === -1 && board[4] === null) move = 4;
    if (move === -1) move = findFirstEmpty([0, 2, 6, 8]);
    if (move === -1) move = findFirstEmpty([1, 3, 5, 7]);
  } else if (difficulty === "medium") {
    if (Math.random() < 0.5) {
      move = findWinningMove(COMPUTER_PLAYER);
      if (move === -1) move = findWinningMove(turn === "X" ? "O" : "X");
    }
    if (move === -1) move = getRandomEmptySpot();
  } else {
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

function computerMovement() {
  const difficulty = GAME_MODE.split("-")[1];
  let fromTo = null;

  if (difficulty === "hard") {
    // Try to win with AI's own pieces
    fromTo = findWinningMovementMove(COMPUTER_PLAYER);
    // If can't win, try to block opponent by finding a blocking move
    if (!fromTo) fromTo = findBlockingMovementMove(COMPUTER_PLAYER);
    // Otherwise, make the best strategic move
    if (!fromTo) fromTo = findBestMovementMove();
  } else if (difficulty === "medium") {
    if (Math.random() < 0.6) {
      fromTo = findWinningMovementMove(COMPUTER_PLAYER);
      if (!fromTo) fromTo = findBlockingMovementMove(COMPUTER_PLAYER);
    }
    if (!fromTo) fromTo = getRandomMovementMove();
  } else {
    fromTo = getRandomMovementMove();
  }

  if (fromTo) {
    console.log('AI moving from', fromTo.from, 'to', fromTo.to);
    selectedFrom = fromTo.from;
    handleMovement(fromTo.to);
  } else {
    console.error('AI could not find a valid move!');
  }
}

function findWinningMove(player) {
  for (const line of winLines) {
    const [a, b, c] = line;
    const vals = [board[a], board[b], board[c]];

    if (vals.filter(v => v === player).length === 2 && vals.filter(v => v === null).length === 1) {
      if (board[a] === null) return a;
      if (board[b] === null) return b;
      if (board[c] === null) return c;
    }
  }
  return -1;
}

function findFirstEmpty(positions) {
  for (const pos of positions) {
    if (board[pos] === null) return pos;
  }
  return -1;
}

function getRandomEmptySpot() {
  const empty = [];
  for (let i = 0; i < 9; i++) {
    if (board[i] === null) empty.push(i);
  }
  return empty.length > 0 ? empty[Math.floor(Math.random() * empty.length)] : -1;
}

function findWinningMovementMove(player) {
  for (let from = 0; from < 9; from++) {
    if (board[from] !== player) continue;

    for (let to = 0; to < 9; to++) {
      if (board[to] !== null) continue;

      const tempBoard = [...board];
      tempBoard[to] = tempBoard[from];
      tempBoard[from] = null;

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

function findBlockingMovementMove(aiPlayer) {
  // Find a move where AI moves its OWN piece to block opponent from winning
  const opponent = aiPlayer === "X" ? "O" : "X";

  // For each potential winning line for the opponent
  for (const line of winLines) {
    const [a, b, c] = line;
    const positions = [a, b, c];
    const values = [board[a], board[b], board[c]];

    // Check if opponent has 2 pieces in this line and one empty spot
    const opponentCount = values.filter(v => v === opponent).length;
    const emptyCount = values.filter(v => v === null).length;

    if (opponentCount === 2 && emptyCount === 1) {
      // Find the empty position that would complete opponent's line
      const emptyPos = positions[values.indexOf(null)];

      // Try to move one of AI's pieces to that empty position to block
      for (let from = 0; from < 9; from++) {
        if (board[from] === aiPlayer) {
          // Check if this AI piece can move to the blocking position
          return { from, to: emptyPos };
        }
      }
    }
  }

  return null;
}

function findBestMovementMove() {
  const moves = getAllValidMovementMoves(COMPUTER_PLAYER);
  if (moves.length === 0) return null;

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

function evaluateMovementPosition(move) {
  const tempBoard = [...board];
  tempBoard[move.to] = tempBoard[move.from];
  tempBoard[move.from] = null;

  let score = 0;

  for (const line of winLines) {
    const [a, b, c] = line;
    const vals = [tempBoard[a], tempBoard[b], tempBoard[c]];
    const myCount = vals.filter(v => v === COMPUTER_PLAYER).length;
    const emptyCount = vals.filter(v => v === null).length;

    if (myCount === 2 && emptyCount === 1) score += 10;
    if (myCount === 1 && emptyCount === 2) score += 2;
  }

  if (move.to === 4) score += 3;

  return score;
}

function getRandomMovementMove() {
  const moves = getAllValidMovementMoves(COMPUTER_PLAYER);
  return moves.length > 0 ? moves[Math.floor(Math.random() * moves.length)] : null;
}

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
