<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>3-Point Tactical Board Game</title>

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

  <style>
    .board-wrap{
      width: min(520px, 92vw);
      aspect-ratio: 1 / 1;
      margin: 0 auto;
      position: relative;
    }
    svg{ width: 100%; height: 100%; display:block; }
    .point{
      cursor: pointer;
      transition: transform .08s ease;
      transform-origin: center;
    }
    .point:hover{ transform: scale(1.06); }
    .point.disabled{ cursor: not-allowed; }
    .mark{
      font-weight: 900;
      font-family: system-ui, -apple-system, Segoe UI, Roboto, Arial, sans-serif;
      user-select: none;
      pointer-events: none;
    }
  </style>
</head>
<body class="bg-light">

<div class="container py-4">
  <div class="row g-4 align-items-start">
    <div class="col-12 col-lg-4">
      <div class="card shadow-sm border-0 rounded-4">
        <div class="card-body">
          <h4 class="mb-1">Tactical 3-Point Win</h4>
          <p class="text-muted mb-3">2 players • line up 3 points to win</p>

          <div class="p-3 rounded-4 bg-body-tertiary mb-3">
            <div class="d-flex justify-content-between">
              <span class="text-muted">Turn</span>
              <span id="turnText" class="fw-bold">X</span>
            </div>
            <div class="d-flex justify-content-between mt-2">
              <span class="text-muted">Status</span>
              <span id="statusText" class="fw-bold">Playing</span>
            </div>
          </div>

          <div class="d-flex gap-2">
            <button id="resetBtn" class="btn btn-primary w-100">Reset</button>
            <button id="newBtn" class="btn btn-outline-secondary w-100">New Game</button>
          </div>

          <hr class="my-4">
          <div class="small text-muted">
            <div class="mb-2"><span class="fw-bold">How to win:</span> get any 3 in a straight line.</div>
            <ul class="mb-0">
              <li>Top row, middle row, bottom row</li>
              <li>Left column, middle column, right column</li>
              <li>Both diagonals</li>
            </ul>
          </div>

        </div>
      </div>
    </div>

    <div class="col-12 col-lg-8">
      <div class="card shadow-sm border-0 rounded-4">
        <div class="card-body">
          <div class="board-wrap">
            <!-- Board SVG -->
            <svg viewBox="0 0 100 100" aria-label="Board">
              <!-- Outer square -->
              <rect x="10" y="10" width="80" height="80" fill="none" stroke="black" stroke-width="2.2"/>

              <!-- Cross lines -->
              <line x1="50" y1="10" x2="50" y2="90" stroke="black" stroke-width="2"/>
              <line x1="10" y1="50" x2="90" y2="50" stroke="black" stroke-width="2"/>

              <!-- Diagonals -->
              <line x1="10" y1="10" x2="90" y2="90" stroke="black" stroke-width="2"/>
              <line x1="90" y1="10" x2="10" y2="90" stroke="black" stroke-width="2"/>

              <!-- Points (click targets) -->
              <!-- Coordinates: corners, mid-edges, center -->
              <g id="pointsLayer"></g>

              <!-- Marks (X/O) -->
              <g id="marksLayer"></g>
            </svg>
          </div>

          <div class="mt-3 text-muted small">
            Click on any point to place your mark.
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<script src="game.js"></script>
</body>
</html>
