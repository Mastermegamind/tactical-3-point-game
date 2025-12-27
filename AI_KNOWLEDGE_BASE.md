# AI Knowledge Base & Neural Network Visualization

Complete guide to viewing, managing, and understanding the AI learning system's knowledge base and neural network pathways.

---

## Overview

The AI Knowledge Base provides administrators with powerful tools to visualize and manage what the AI has learned from gameplay. This includes:

- **Position Weight Heatmaps** - Visual representation of strategic board positions
- **Opening Patterns** - Common first moves and their success rates
- **Training History** - Complete record of all AI training sessions
- **Neural Pathway Visualization** - How the AI makes decisions
- **Knowledge Base Management** - View, analyze, and reset AI learning data

---

## Features

### 1. Position Weights Heatmap

**Visual Representation:**
- 4x4 grid showing all 16 board positions
- Color-coded from red (weak) to green (strong)
- Hover tooltips showing exact weight values
- Real-time position strength analysis

**Color Legend:**
- **Red (RGB 255, 50, 50)** - Low value positions (weak strategic value)
- **Yellow (RGB 255, 255, 50)** - Medium value positions
- **Green (RGB 50, 255, 50)** - High value positions (strong strategic value)

**Position Analysis Includes:**
- Top 3 strongest positions with weights
- Bottom 3 weakest positions with weights
- Average weight across all positions
- Total games analyzed for this data

**Use Cases:**
- Understand which board positions AI values most
- Identify strategic patterns AI has learned
- Compare strategy across difficulty levels
- Debug AI decision-making issues

---

### 2. Opening Patterns & Decision Tree

**What It Shows:**
- Most common opening moves AI has learned
- Success scores for each pattern
- Frequency of pattern usage
- Top 12 patterns organized by effectiveness

**Pattern Information:**
- Pattern identifier (move sequence)
- Success score (how often it leads to wins)
- Pattern ranking by frequency

**Use Cases:**
- Analyze AI opening strategy
- Identify repetitive AI behavior
- Compare patterns across difficulties
- Understand AI learning progression

---

### 3. Training Records History

**Complete Database of Training Sessions:**

Each record shows:
- **ID** - Unique training session identifier
- **Difficulty** - Easy, Medium, or Hard
- **Games Analyzed** - Number of games used for training
- **Win Rate** - AI success percentage from analyzed games
- **Average Moves** - Mean moves per game
- **Trained At** - Timestamp of training session

**Actions Available:**
- **View** - See detailed position weights, opening patterns, and winning sequences
- **Delete** - Remove specific training record (admin only)

---

### 4. Difficulty Statistics Cards

**Summary Cards for Each Difficulty:**
- Total training records count
- Last training timestamp
- Visual gradient background
- Quick overview metrics

**Helps Answer:**
- When was each difficulty level last trained?
- How much training data exists per difficulty?
- Which difficulty needs more training?

---

### 5. Knowledge Base Management

#### Filter Options

**Difficulty Level:**
- All Difficulties (combined view)
- Easy only
- Medium only
- Hard only

**Records Limit:**
- 25 Records
- 50 Records (default)
- 100 Records
- 500 Records

#### Reset Knowledge Base (Super Admin Only)

**Options:**
- Reset all difficulties (complete reset)
- Reset Easy only
- Reset Medium only
- Reset Hard only

**Warning:** This permanently deletes AI training data and cannot be undone.

---

## Database Structure

### AI Training Data Table

```sql
CREATE TABLE `ai_training_data` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `difficulty` varchar(20) NOT NULL,
  `position_weights` JSON DEFAULT NULL,
  `opening_patterns` JSON DEFAULT NULL,
  `winning_sequences` JSON DEFAULT NULL,
  `games_analyzed` int(11) DEFAULT 0,
  `win_rate` decimal(5,2) DEFAULT 0.00,
  `avg_moves` decimal(5,2) DEFAULT 0.00,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
);
```

**Column Descriptions:**
- `position_weights` - JSON array of 16 values (one per board position)
- `opening_patterns` - JSON object mapping patterns to success scores
- `winning_sequences` - JSON array of move sequences that led to wins
- `games_analyzed` - Count of games used for this training session
- `win_rate` - Percentage of games AI won in analyzed dataset
- `avg_moves` - Average number of moves per game

---

## How to Use

### Viewing Position Weights

1. Navigate to **Admin Panel** → **AI Knowledge Base**
2. Select difficulty level from dropdown
3. View the 4x4 heatmap
4. Hover over cells to see exact values
5. Check "Position Analysis" section below heatmap

**Interpreting Values:**
- Values typically range from 0-100
- Higher = AI values this position more strategically
- Corner and center positions often have higher weights
- Pattern depends on game rules and AI learning

---

### Analyzing Opening Patterns

1. Select difficulty level
2. Scroll to "Opening Patterns & Decision Tree"
3. View pattern cards showing:
   - Pattern number
   - Move description
   - Success score (green badge)
4. Top 12 most effective patterns displayed

**Example Pattern:**
```
Pattern #1
Move: "0-1-5"
Score: 87.5
```
This means: Opening with positions 0, 1, 5 has 87.5% success rate

---

### Viewing Training Record Details

1. Find record in "Training Records History" table
2. Click "👁️ View" button
3. Modal shows:
   - Training statistics
   - Position weights (JSON sample)
   - Opening patterns (JSON sample)
   - Winning sequences (JSON sample)

**Detailed View Shows:**
- Full metadata about training session
- Raw JSON data (first 500 characters)
- All key metrics and timestamps

---

### Deleting Training Records

**Requirements:** Admin or Super Admin role

**Steps:**
1. Find record to delete
2. Click "🗑️ Delete" button
3. Confirm deletion in popup
4. Record permanently removed
5. Admin action is logged

**Use Cases:**
- Remove corrupted training data
- Delete test training sessions
- Clean up old/outdated training
- Manage database size

---

### Resetting Knowledge Base

**Requirements:** Super Admin role only

**Complete Reset Process:**
1. Click "🔄 Reset Knowledge Base" button
2. Select target:
   - "All Difficulties" - Complete wipe
   - Specific difficulty only
3. Confirm action (irreversible)
4. All matching records deleted
5. AI reverts to default behavior

**Partial Reset Process:**
Same as above, but select specific difficulty

**Use Cases:**
- Start AI training from scratch
- Remove problematic learning
- Prepare for new training strategy
- Testing purposes

---

## Understanding the Heatmap

### Color Calculation

```javascript
// Normalize weight to 0-100 scale
normalized = max(0, min(100, weight))

// Calculate color gradient
if (normalized < 50) {
    red = 255
    green = normalized * 2 * 2.55
} else {
    red = (100 - normalized) * 2 * 2.55
    green = 255
}
blue = 50
```

**Result:**
- Low weights (0-50): Red → Yellow gradient
- High weights (50-100): Yellow → Green gradient

### Grid Layout

```
[ 0] [ 1] [ 2] [ 3]
[ 4] [ 5] [ 6] [ 7]
[ 8] [ 9] [10] [11]
[12] [13] [14] [15]
```

Position numbers correspond to board coordinates in the game.

---

## Neural Network Visualization

### What You're Seeing

The heatmap represents the **first layer** of the AI's decision-making process:

1. **Input Layer** - Current board state (16 positions)
2. **Weight Layer** - Strategic value assigned to each position (heatmap)
3. **Pattern Layer** - Opening patterns learned from winners
4. **Decision Layer** - Final move selection

**Simplified Neural Pathway:**
```
Board State → Position Weights → Pattern Matching → Move Selection
   (16)            (16)              (N patterns)        (1)
```

### Learning Process

1. AI analyzes completed games
2. Identifies winning positions and patterns
3. Increases weights for positions used in wins
4. Stores patterns that correlate with victories
5. Uses weights + patterns for future move decisions

---

## Permission Levels

### View Access
**Required:** Moderator, Admin, or Super Admin

**Can Do:**
- View all heatmaps
- View opening patterns
- View training records
- Filter by difficulty

### Manage Access
**Required:** Admin or Super Admin

**Can Do:**
- Delete individual records
- View detailed JSON data
- Access all view features

### Reset Access
**Required:** Super Admin only

**Can Do:**
- Reset knowledge base (all or specific difficulty)
- All admin capabilities
- All view capabilities

---

## Best Practices

### Regular Monitoring

- **Weekly:** Check training record counts
- **After Major Updates:** Verify AI still performs well
- **Before Tournaments:** Review position weights for balance
- **Monthly:** Archive or delete old training data

### Knowledge Base Health

**Good Indicators:**
- Diverse position weights (not all high or all low)
- Multiple opening patterns with varying scores
- Regular training sessions (not too old)
- Win rates between 30-70% depending on difficulty

**Warning Signs:**
- All positions have similar weights (no strategy)
- Only 1-2 opening patterns (too rigid)
- No recent training (using outdated data)
- Win rate 0% or 100% (broken learning)

### Training Strategy

**Recommended Approach:**
1. Start with 20+ games for initial training
2. Re-train after every 50 new games
3. Compare before/after position weights
4. Monitor win rate trends
5. Reset if AI behavior becomes problematic

**Training Frequency by Difficulty:**
- Easy: Every 100 games or monthly
- Medium: Every 50 games or bi-weekly
- Hard: Every 25 games or weekly

---

## Troubleshooting

### Heatmap Not Showing

**Problem:** Empty heatmap or "No data" message

**Solutions:**
1. Check if AI has been trained for selected difficulty
2. Verify `ai_training_data` table has records
3. Try selecting "All Difficulties"
4. Train AI using AI Training page

### All Weights Are Zero

**Problem:** All position weights show 0.00

**Cause:** No training data or training failed

**Fix:**
1. Go to AI Training page
2. Ensure 20+ games exist for difficulty
3. Click "Train AI"
4. Return to Knowledge Base to verify

### Opening Patterns Empty

**Problem:** No patterns displayed

**Cause:** Insufficient training data or no winning games

**Fix:**
1. Check games_analyzed count in training record
2. Verify games have winners (not all draws)
3. Re-train with more diverse game data
4. Check database for `opening_patterns` JSON data

### Cannot Delete Records

**Problem:** Delete button missing or fails

**Solutions:**
1. Verify you have Admin role (check top-right badge)
2. Check browser console for JavaScript errors
3. Verify database connection
4. Check admin_activity_log for error details

### Reset Button Not Visible

**Problem:** Can't find "Reset Knowledge Base" button

**Cause:** Not Super Admin role

**Fix:**
1. Only Super Admins can reset knowledge base
2. Ask existing Super Admin to promote your account
3. Or use database directly (not recommended):
   ```sql
   UPDATE admins SET role = 'super_admin' WHERE username = 'you';
   ```

---

## API Integration

### Getting Position Weights

```php
// Example: Fetch latest position weights for medium difficulty
$stmt = $conn->prepare("
    SELECT position_weights
    FROM ai_training_data
    WHERE difficulty = ?
    ORDER BY created_at DESC
    LIMIT 1
");
$stmt->execute(['medium']);
$data = $stmt->fetch();
$weights = json_decode($data['position_weights'], true);

// Access specific position
$cornerWeight = $weights[0]; // Top-left corner
```

### Exporting Knowledge Base

```php
// Example: Export all AI knowledge to JSON file
$stmt = $conn->query("SELECT * FROM ai_training_data");
$allData = $stmt->fetchAll(PDO::FETCH_ASSOC);

file_put_contents(
    'ai_knowledge_export.json',
    json_encode($allData, JSON_PRETTY_PRINT)
);
```

---

## Security & Logging

### Activity Logging

All actions are logged in `admin_activity_log`:

**Logged Actions:**
- `delete_ai_record` - When training record is deleted
- `reset_all_ai` - When all knowledge is reset
- `reset_ai` - When specific difficulty is reset

**Log Entry Example:**
```
admin_id: 1
action: 'delete_ai_record'
description: 'Deleted AI training record ID: 42'
target_type: NULL
target_id: NULL
ip_address: '192.168.1.100'
created_at: '2025-12-27 14:30:00'
```

### Viewing Logs

```sql
-- See all AI-related admin actions
SELECT * FROM admin_activity_log
WHERE action LIKE '%ai%'
ORDER BY created_at DESC;

-- See who reset the knowledge base
SELECT
    a.username,
    aal.description,
    aal.created_at
FROM admin_activity_log aal
JOIN admins a ON aal.admin_id = a.id
WHERE aal.action IN ('reset_all_ai', 'reset_ai')
ORDER BY aal.created_at DESC;
```

---

## Advanced Usage

### Comparing Difficulty Strategies

1. Open Knowledge Base in multiple browser tabs
2. Select different difficulty in each tab
3. Compare position weight heatmaps side-by-side
4. Note differences in strategic value

**Expected Differences:**
- **Easy:** More random weights, less focused strategy
- **Medium:** Balanced weights, some clear preferences
- **Hard:** Highly optimized weights, clear strategic focus

### Exporting for Analysis

```javascript
// JavaScript to export visible heatmap data
function exportHeatmap() {
    const cells = document.querySelectorAll('.heatmap-cell');
    const data = Array.from(cells).map((cell, index) => ({
        position: index,
        weight: parseFloat(cell.textContent),
        color: cell.style.backgroundColor
    }));

    console.log(JSON.stringify(data, null, 2));
    // Copy from console and paste into analysis tool
}
```

### Custom Visualizations

You can extend the page with additional charts:

1. **Position Weight Trends** - Line chart showing how weights change over time
2. **Pattern Evolution** - How opening patterns shift with more training
3. **Win Rate Correlation** - Scatter plot of weights vs. win rates
4. **3D Heatmap** - Three-dimensional view with time as Z-axis

---

## Technical Details

### Page Load Performance

- Position weights: O(1) query with LIMIT 1
- Training records: Paginated with configurable limit
- Heatmap rendering: Pure CSS, no heavy libraries
- Pattern display: Limited to top 12 for performance

### Browser Compatibility

**Requires:**
- Modern browser with CSS Grid support
- JavaScript enabled
- Bootstrap 5.3+ compatible browser

**Tested On:**
- Chrome 90+
- Firefox 88+
- Safari 14+
- Edge 90+

### Database Queries

**Main Queries:**
1. Difficulty stats aggregation (GROUP BY)
2. Latest position weights (ORDER BY + LIMIT)
3. Training records with filters (WHERE + LIMIT)
4. Record deletion (DELETE by ID)

**Query Performance:**
- All queries use indexes
- JSON parsing done in PHP, not MySQL
- Pagination prevents large result sets

---

## Quick Reference

### Page URL
```
/admin/ai-knowledge-base.php
```

### Navigation Path
```
Admin Panel → AI Knowledge Base
```

### Required Role
```
Moderator (view only)
Admin (view + delete records)
Super Admin (full access)
```

### Key Components
```
1. Statistics Cards (3)
2. Filter Form (difficulty + limit)
3. Position Weights Heatmap (16 cells)
4. Opening Patterns Tree (up to 12)
5. Training Records Table (paginated)
```

### Main Actions
```
View Details → Shows full training record
Delete Record → Removes training data (admin)
Reset Knowledge Base → Wipes AI learning (super admin)
```

---

## Related Documentation

- **[AI_LEARNING_SYSTEM.md](AI_LEARNING_SYSTEM.md)** - How AI learns from games
- **[ADMIN_SYSTEM.md](ADMIN_SYSTEM.md)** - Complete admin system docs
- **[ADMIN_USER_MANAGEMENT.md](ADMIN_USER_MANAGEMENT.md)** - User moderation features

---

## Changelog

### Version 1.0 (December 27, 2025)
- Initial release
- Position weights heatmap visualization
- Opening patterns decision tree
- Training records history table
- Knowledge base reset functionality
- Difficulty filtering
- Activity logging
- Admin permissions

---

**Version:** 1.0
**Last Updated:** December 27, 2025
**Status:** ✅ Production Ready
