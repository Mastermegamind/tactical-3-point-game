# AI Training → Strategies System

## What Happens When You Click "Train AI"

When you train the AI in `admin/ai-training.php`, it now does **THREE things**:

### 1. Analyzes Games
- Looks at all completed games for that difficulty
- Calculates win rates, average moves, etc.

### 2. Generates Learning Data
- Creates position weights (which positions are valuable)
- Finds opening patterns (successful first moves)
- Identifies winning sequences

### 3. **NEW: Creates Strategies** 💾
- Extracts specific strategies from each winning game
- Saves them to `ai_strategies` table
- AI can reuse these strategies in future games

---

## Strategy Types Created During Training

### From Individual Games

For each AI winning game, the training analyzes every AI move:

**1. Winning Sequences**
```
Strategy: "winning-sequence"
Example: Final move that won the game
Board: [O, O, X]
       [X, X, ·]
       [·, ·, ·]
Move: Place at position 2 → WIN!
Priority: 95-100 (very high)
```

**2. Defensive Blocks**
```
Strategy: "defensive-block"
Example: Blocking opponent's winning move
Board: [X, X, ·]
       [O, ·, ·]
       [·, ·, ·]
Move: Block at position 2
Priority: 85-95 (high)
```

**3. Tactical Moves**
```
Strategy: "tactical-move"
Example: Strong positional play
Board: [·, ·, ·]
       [·, O, ·]
       [·, ·, ·]
Move: Center control
Priority: 50-70 (medium)
```

### From Pattern Analysis

**4. Opening Moves**
```
Strategy: "opening-move"
From: Popular first moves (used 2+ times)
Example: Position 0 (corner), Position 4 (center)
Priority: 60-75
```

**5. High-Value Positions**
```
Strategy: "high-value-position"
From: Positions with weight > 2.0
Example: Position 4 (weight 3.5) = strong position
Priority: Based on weight (weight * 5)
```

---

## Example Training Session

**You click "Train AI" for Hard difficulty**

### Step 1: Game Analysis
```
Found 25 winning games
- Game #1: 12 moves, won with center strategy
- Game #2: 8 moves, blocked then won
- Game #3: 26 moves, endgame victory
... (22 more games)
```

### Step 2: Extract Strategies

From Game #1 (12 moves):
- Move 1: Opening at position 4 → "opening-move" strategy
- Move 3: Block at position 2 → "defensive-block" strategy
- Move 5: Tactical play at position 7 → "tactical-move" strategy
- Move 6: Final winning move → "winning-sequence" strategy

Repeat for all 25 games...

### Step 3: Pattern Strategies

Opening Analysis:
- Position 0 used 12 times → Create "opening-move" strategy
- Position 4 used 18 times → Create "opening-move" strategy

Weight Analysis:
- Position 4 has weight 5.01 (>2.0) → Create "high-value-position" strategy
- Position 0 has weight 3.85 (>2.0) → Create "high-value-position" strategy

### Result
```
✅ Training Complete!
📊 Games Analyzed: 25
🎯 AI Wins: 12
📈 Win Rate: 48%
💾 Strategies Created: 87
```

**Where did 87 strategies come from?**
- 25 games × ~3 moves per game = ~75 game-based strategies
- + 2 opening strategies
- + 9 high-value position strategies
- + some defensive blocks
= **87 total strategies**

---

## How Strategies Are Saved

### Smart De-duplication

If similar strategy already exists, it **updates** instead of creating duplicate:

```php
Existing Strategy:
- Move to position 4
- Type: offensive
- Phase: placement
- Uses: 5
- Success: 4/5 = 80%

New similar move found:
→ Updates to:
- Uses: 6
- Success: 5/6 = 83.3%
- Priority recalculated
```

### Priority Calculation

```
Priority Score (0-100) =
  (Success Rate × 0.7) + (Board Score × 0.3)

Examples:
- 90% success, +30 board score = 63 + 9 = 72 priority
- 100% success, +50 board score = 70 + 15 = 85 priority
- 50% success, -10 board score = 35 + (-3) = 32 priority
```

---

## Viewing Generated Strategies

### In Admin Panel

Go to: `admin/ai-strategies.php`

You'll see:
- Mini board showing position
- Strategy name and type
- Success rate
- Total uses
- Priority score
- When last used

### Filter By:
- Difficulty (easy/medium/hard)
- Strategy Type (offensive/defensive/balanced)
- Game Phase (placement/movement/endgame)

---

## Strategy Data Includes

Each strategy saves:

**Board Context**:
- Full 9-position board state
- AI piece locations: `[2, 4]`
- Opponent piece locations: `[0, 7]`

**Move Details**:
- From position (or NULL for placement)
- To position
- Move type (placement/movement)

**Intelligence**:
- Opponent pattern detected
- Board evaluation score (-100 to +100)
- Threat level (0-10)
- Game phase

**Performance**:
- Success count (times led to win)
- Failure count (times led to loss)
- Success rate percentage
- Average moves to win

---

## How AI Uses These Strategies

During gameplay, when AI needs to make a move:

1. **Load strategies** from database
2. **Filter** by current context:
   - Same difficulty
   - Same game phase
   - Similar board state
   - Threat level within ±2
3. **Sort** by priority and success rate
4. **Use** highest-rated matching strategy

Example:
```
Current Board: [X, ·, ·, ·, ·, ·, ·, ·, ·]
Phase: placement
Threat: 1/10

Finds matching strategy:
- Name: "center-control-counter"
- Priority: 78
- Success: 85%
- Move: Position 4

AI plays position 4 ✓
```

---

## Benefits of Training-Generated Strategies

### 1. **Comprehensive Coverage**
- Strategies for early, mid, and late game
- Both offensive and defensive plays
- Covers all board configurations seen in real games

### 2. **Proven Performance**
- Only saves moves from winning games
- Success rates based on real outcomes
- Higher priority for more successful moves

### 3. **Context-Aware**
- Knows opponent patterns
- Understands board state
- Adapts to game phase

### 4. **Self-Improving**
- More training = more strategies
- Strategies with low success get demoted
- Popular strategies get reinforced

---

## Training Best Practices

### For Best Results:

1. **Play 20-30 games** per difficulty first
2. **Train AI** after every 10 new games
3. **Check strategies page** to see what was learned
4. **Re-train periodically** as new games are played

### Training Frequency:

- **After setup**: Train once with initial games
- **Weekly**: If users play regularly
- **After updates**: When AI behavior changes
- **Before tournaments**: Ensure AI is optimized

---

## Troubleshooting

### "Only created 5 strategies"

**Reasons**:
- Not enough winning games (need 5+ AI wins)
- Games too similar (strategies merged)
- Low position weight variance

**Fix**: Play more diverse games

### "Strategies have low success rates"

**Reasons**:
- AI losing most games
- Poor strategic choices being saved
- Need more training data

**Fix**: Train with more AI winning games

### "No strategies created"

**Reasons**:
- No game data in database
- No AI wins for that difficulty
- Database permission issues

**Fix**: Verify games exist in `ai_training_data` table

---

## Summary

**Training now builds a comprehensive strategy library!**

- ✅ Analyzes every move from winning games
- ✅ Creates 50-100+ strategies per training session
- ✅ Saves to `ai_strategies` table
- ✅ AI uses them in real-time during games
- ✅ Self-improving over time

**Next time you train the AI, watch the "Strategies Created" count grow! 📈**
