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

// Helper function to get player name by side
function getPlayerName(side) {
  return side === 'X' ? PLAYER_X_NAME : PLAYER_O_NAME;
}

// Helper function to get current player name
function getCurrentPlayerName() {
  if (phase === "placement") {
    return getPlayerName(turn);
  } else {
    const currentPlayer = GAME_MODE === "pvp" || IS_ONLINE ? currentMovingPlayer : turn;
    return getPlayerName(currentPlayer);
  }
}

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
let gameStartTime = Date.now();

// AI Learning System
let learnedAI = null;
let aiLoaded = false;

// Initialize game
init();

async function init() {
  renderPoints();
  renderMarks();
  updateUI();
  await loadGameState();

  // Initialize learned AI if playing against computer
  if (GAME_MODE && GAME_MODE.startsWith('pvc')) {
    const difficulty = GAME_MODE.split("-")[1];
    if (typeof LearnedAI === 'function') {
      learnedAI = new LearnedAI(difficulty);
      aiLoaded = await learnedAI.loadStrategy();

      if (aiLoaded) {
        console.log(`AI using learned strategy for ${difficulty} difficulty`);
      } else {
        console.log(`AI using standard strategy for ${difficulty} difficulty`);
      }
    } else {
      aiLoaded = false;
      learnedAI = null;
      console.warn('LearnedAI not available. Falling back to standard AI.');
    }
  }
}

async function loadGameState() {
  try {
    const response = await fetch(`api/get-game-state.php?session_id=${SESSION_ID}`);
    const data = await response.json();

    if (data.success && data.board_state) {
      const state = JSON.parse(data.board_state);

      // Restore complete game state
      board = state.board || Array(9).fill(null);
      placedCount = state.placedCount || { X: 0, O: 0 };
      phase = state.phase || 'placement';
      turn = state.turn || 'X';
      currentMovingPlayer = state.currentMovingPlayer || 'X';
      gameOver = state.gameOver || false;
      selectedFrom = null; // Reset selection on reload

      // Calculate move number from board state
      const piecesOnBoard = board.filter(c => c !== null).length;
      moveNumber = piecesOnBoard;

      // Log the restored state
      console.log('Game state restored:', {
        phase,
        turn,
        placedCount,
        boardFilled: piecesOnBoard,
        gameOver
      });

      // Re-render everything
      renderMarks();
      updateUI();

      // Show status message
      if (!gameOver) {
        if (phase === 'placement') {
          statusText.textContent = `Placement Phase - ${placedCount.X}/3 X, ${placedCount.O}/3 O placed`;
        } else if (phase === 'movement') {
          statusText.textContent = 'Movement Phase - Click your piece to move';
        }

        // If it's AI's turn and game not over, trigger AI move after a short delay
        if (!IS_ONLINE && !gameOver && turn === COMPUTER_PLAYER && GAME_MODE && GAME_MODE.startsWith('pvc')) {
          setTimeout(() => {
            if (!computerMoving) {
              makeComputerMove();
            }
          }, 1000);
        }
      } else {
        // Check if there's a winner and show game over state
        const winner = checkWinner();
        if (winner) {
          statusText.textContent = `${getPlayerName(winner)} Wins!`;
        }
      }
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

  // Render immediately before saving
  renderMarks();
  updateUI();

  // Save move to database
  await recordMove('placement', null, id, boardBefore, 0, currentPlayer);

  const winner = checkWinner();
  if (winner) {
    endGame(winner);
    return;
  }

  // ALTERNATING PLACEMENT SYSTEM
  // Players alternate placing one pebble at a time: X → O → X → O → X → O

  if (placedCount.X === 3 && placedCount.O === 3) {
    // Both players finished placing, move to movement phase
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
  }

  // Alternate turns between players after each placement
  turn = turn === "X" ? "O" : "X";

  renderMarks();
  updateUI();
  await saveGameState();

  // If it's now the computer's turn, make AI move
  if (isComputerTurn()) {
    makeComputerMove();
  }
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
    const playerName = getPlayerName(clickedPiece);
    statusText.textContent = `${playerName}'s pebble selected - Click where to move`;
    return;
  }

  if (board[clickedId]) {
    if (board[clickedId] !== allowedPlayer) return;

    selectedFrom = clickedId;
    moveStartTime = Date.now();
    renderMarks();
    const playerName = getPlayerName(board[clickedId]);
    statusText.textContent = `${playerName}'s pebble selected - Click where to move`;
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
  const winnerName = getPlayerName(winnerSymbol);
  const isPlayerWin = winnerSymbol === PLAYER_SIDE;

  statusText.textContent = isPlayerWin ? "You Win!" : `${winnerName} Wins!`;
  selectedFrom = null;

  renderMarks();
  updateUI();

  // Save completion to database
  try {
    // Calculate winnerId properly
    let winnerId = null;

    if (IS_ONLINE) {
      // In online mode, winner is current user if they won, otherwise it's the opponent
      winnerId = (winnerSymbol === PLAYER_SIDE) ? USER_ID : OPPONENT_ID;
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

    // Show SweetAlert modal with game results
    showGameResultModal(winnerSymbol, isPlayerWin, winnerName);

  } catch (error) {
    console.error('Failed to complete game:', error);
  }
}

function showGameResultModal(winnerSymbol, isPlayerWin, winnerName) {
  const totalMoves = moveNumber;
  const gameDuration = Math.floor((Date.now() - gameStartTime) / 1000); // in seconds
  const minutes = Math.floor(gameDuration / 60);
  const seconds = gameDuration % 60;

  const player1Name = PLAYER_X_NAME;
  const player2Name = PLAYER_O_NAME;

  // Determine icon and title based on result
  const isDraw = (winnerSymbol === null);
  const icon = isDraw ? 'info' : (isPlayerWin ? 'success' : 'error');
  const title = isDraw ? '🤝 Draw!' : (isPlayerWin ? '🎉 Victory!' : '😔 Defeat');

  // Create HTML content for the modal
  const htmlContent = `
    <div style="text-align: center; padding: 1rem;">
      <h3 style="color: #667eea; margin-bottom: 1.5rem; font-weight: 700;">
        ${isDraw ? 'It\'s a Draw!' : winnerName + ' Wins!'}
      </h3>

      <div style="background: #f8f9fa; border-radius: 12px; padding: 1.5rem; margin-bottom: 1rem;">
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; text-align: left;">
          <div>
            <div style="font-size: 0.85rem; color: #6c757d; font-weight: 600; margin-bottom: 0.5rem;">PLAYER 1 (X)</div>
            <div style="font-size: 1.1rem; font-weight: 600; color: #212529;">${player1Name}</div>
          </div>
          <div>
            <div style="font-size: 0.85rem; color: #6c757d; font-weight: 600; margin-bottom: 0.5rem;">PLAYER 2 (O)</div>
            <div style="font-size: 1.1rem; font-weight: 600; color: #212529;">${player2Name}</div>
          </div>
        </div>
      </div>

      <div style="background: #f8f9fa; border-radius: 12px; padding: 1.5rem;">
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
          <div>
            <div style="font-size: 0.85rem; color: #6c757d; font-weight: 600; margin-bottom: 0.5rem;">TOTAL MOVES</div>
            <div style="font-size: 1.5rem; font-weight: 700; color: #667eea;">${totalMoves}</div>
          </div>
          <div>
            <div style="font-size: 0.85rem; color: #6c757d; font-weight: 600; margin-bottom: 0.5rem;">GAME DURATION</div>
            <div style="font-size: 1.5rem; font-weight: 700; color: #667eea;">${minutes}:${seconds.toString().padStart(2, '0')}</div>
          </div>
        </div>
      </div>

      ${isDraw ?
        '<div style="margin-top: 1rem; padding: 1rem; background: #e7f3ff; border-radius: 8px; color: #004085; font-weight: 600;">No Rating Change</div>' :
        (isPlayerWin ?
          '<div style="margin-top: 1rem; padding: 1rem; background: #d1f4e0; border-radius: 8px; color: #1e7e34; font-weight: 600;">+25 Rating Points</div>' :
          '<div style="margin-top: 1rem; padding: 1rem; background: #f8d7da; border-radius: 8px; color: #721c24; font-weight: 600;">-10 Rating Points</div>')
      }
    </div>
  `;

  // Show different buttons for online vs AI games
  const buttons = IS_ONLINE ? {
    showDenyButton: true,
    showCancelButton: false,
    confirmButtonText: 'Rematch',
    denyButtonText: 'Dashboard',
    confirmButtonColor: '#667eea',
    denyButtonColor: '#6c757d'
  } : {
    confirmButtonText: 'Back to Dashboard',
    confirmButtonColor: '#667eea'
  };

  Swal.fire({
    title: title,
    html: htmlContent,
    icon: icon,
    ...buttons,
    allowOutsideClick: false,
    customClass: {
      popup: 'game-result-modal'
    }
  }).then((result) => {
    if (result.isConfirmed && IS_ONLINE) {
      // Request rematch
      requestRematch();
    } else if (result.isDenied || (result.isConfirmed && !IS_ONLINE)) {
      window.location.href = 'dashboard.php';
    }
  });
}

function updateUI() {
  // Update current turn display
  turnText.textContent = getCurrentPlayerName();

  if (!gameOver) {
    if (phase === "placement") {
      if (turn === "X") {
        statusText.textContent = `${PLAYER_X_NAME}: Place your pebbles (${placedCount.X}/3 placed)`;
      } else {
        statusText.textContent = `${PLAYER_O_NAME}: Place your pebbles (${placedCount.O}/3 placed)`;
      }
    } else {
      if (selectedFrom === null) {
        if (IS_ONLINE && turn !== PLAYER_SIDE) {
          statusText.textContent = `${getCurrentPlayerName()}'s turn`;
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

  // Use learned AI if available
  if (aiLoaded && learnedAI) {
    move = learnedAI.getBestPlacement(board, placedCount);
  } else {
    // Fallback to standard AI logic
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
  }

  if (move !== -1) {
    handlePlacement(move);
  }
}

function computerMovement() {
  const difficulty = GAME_MODE.split("-")[1];
  let fromTo = null;

  // Use learned AI if available
  if (aiLoaded && learnedAI) {
    fromTo = learnedAI.getBestMovement(board, COMPUTER_PLAYER);
  }

  // Fallback to standard AI logic if learned AI didn't return a move
  if (!fromTo) {
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
