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

let board = Array(9).fill(null);      // 'X' | 'O' | null
let turn = "X";
let gameOver = false;

// Phase control
let phase = "placement";              // "placement" | "movement"
let placedCount = { X: 0, O: 0 };

// Movement selection
let selectedFrom = null;              // index of selected piece to move

init();

function init() {
  renderPoints();
  renderMarks();
  updateUI();

  resetBtn.addEventListener("click", resetBoard);
  newBtn.addEventListener("click", newGame);
}

function renderPoints() {
  pointsLayer.innerHTML = "";

  points.forEach((p) => {
    const c = document.createElementNS("http://www.w3.org/2000/svg", "circle");
    c.setAttribute("cx", p.x);
    c.setAttribute("cy", p.y);
    c.setAttribute("r", 4.2);
    c.setAttribute("fill", "white");
    c.setAttribute("stroke", "black");
    c.setAttribute("stroke-width", "1.8");
    c.classList.add("point");
    c.dataset.id = String(p.id);

    c.addEventListener("click", () => onPointClick(p.id));
    pointsLayer.appendChild(c);
  });
}

function renderMarks() {
  marksLayer.innerHTML = "";

  // marks
  board.forEach((val, idx) => {
    if (!val) return;
    const p = points[idx];

    const t = document.createElementNS("http://www.w3.org/2000/svg", "text");
    t.setAttribute("x", p.x);
    t.setAttribute("y", p.y + 2.4);
    t.setAttribute("text-anchor", "middle");
    t.setAttribute("font-size", "10");
    t.setAttribute("class", "mark");
    t.textContent = val;
    t.setAttribute("fill", val === "X" ? "#0d6efd" : "#d63384");

    // highlight selected piece during movement
    if (phase === "movement" && selectedFrom === idx) {
      t.setAttribute("stroke", "#111");
      t.setAttribute("stroke-width", "0.7");
    }

    marksLayer.appendChild(t);
  });

  // update point states and highlighting
  const circles = pointsLayer.querySelectorAll("circle.point");
  circles.forEach((c) => {
    const id = Number(c.dataset.id);

    c.classList.toggle("disabled", gameOver);

    // highlight legal destinations in movement phase
    if (phase === "movement" && selectedFrom !== null) {
      const isNeighbor = neighbors[selectedFrom].includes(id);
      const isEmpty = board[id] === null;
      const canGo = isNeighbor && isEmpty;

      c.setAttribute("fill", canGo ? "#e9f5ff" : "white");
    } else {
      c.setAttribute("fill", "white");
    }
  });
}

function onPointClick(id) {
  if (gameOver) return;

  if (phase === "placement") {
    handlePlacement(id);
    return;
  }

  if (phase === "movement") {
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
    endGame(`${winner} Wins`);
    return;
  }

  // If both have placed 3, start movement phase
  if (placedCount.X === 3 && placedCount.O === 3) {
    phase = "movement";
    selectedFrom = null;
    statusText.textContent = "Movement Phase";
  } else {
    // Continue placement turns
    turn = turn === "X" ? "O" : "X";
  }

  renderMarks();
  updateUI();
}

function handleMovement(clickedId) {
  // Step 1: choose a piece of your own
  if (selectedFrom === null) {
    if (board[clickedId] !== turn) return; // must pick own piece
    selectedFrom = clickedId;
    renderMarks();
    statusText.textContent = `Selected: move ${turn}`;
    return;
  }

  // Step 2: if click another of your pieces, switch selection
  if (board[clickedId] === turn) {
    selectedFrom = clickedId;
    renderMarks();
    statusText.textContent = `Selected: move ${turn}`;
    return;
  }

  // Step 3: attempt move to empty neighbor
  const to = clickedId;
  const from = selectedFrom;

  const isNeighbor = neighbors[from].includes(to);
  const isEmpty = board[to] === null;

  if (!isNeighbor || !isEmpty) {
    // invalid move; keep selection
    statusText.textContent = "Invalid move (must move to a connected empty point)";
    return;
  }

  // Apply move
  board[to] = board[from];
  board[from] = null;
  selectedFrom = null;

  // Check win
  const winner = checkWinner();
  if (winner) {
    endGame(`${winner} Wins`);
    return;
  }

  // Next player's turn
  turn = turn === "X" ? "O" : "X";
  statusText.textContent = "Movement Phase";

  renderMarks();
  updateUI();
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

function endGame(message) {
  gameOver = true;
  statusText.textContent = message;
  selectedFrom = null;
  renderMarks();
  updateUI();
}

function updateUI() {
  turnText.textContent = turn;

  if (!gameOver) {
    if (phase === "placement") {
      statusText.textContent =
        `Placement Phase (${placedCount.X}/3 X, ${placedCount.O}/3 O)`;
    } else {
      // movement
      if (selectedFrom === null) {
        statusText.textContent = "Movement Phase (select a piece to move)";
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

  statusText.textContent = "Playing";
  renderMarks();
  updateUI();
}

function newGame() {
  resetBoard();
}
