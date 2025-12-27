# AI Learning System - Quick Start Guide

This guide will help you get started with the AI learning system in just a few minutes.

---

## 🚀 Quick Setup (3 Steps)

### Step 1: Ensure Database is Ready

The `ai_training_data` table should already exist from the database migration. Verify:

```bash
mysql -u root -proot game
```

```sql
SHOW TABLES LIKE 'ai_training_data';
```

If the table doesn't exist, run the migration:

```bash
mysql -u root -proot game < database/game.sql
```

### Step 2: Play Some Games Against AI

The AI needs game data to learn from. Play at least 10-20 games:

1. Go to Dashboard → Start New Game
2. Select "Player vs Computer"
3. Choose a difficulty (Easy, Medium, or Hard)
4. Play the game to completion
5. Repeat for different difficulties

**Pro Tip**: The AI learns best from:
- Mix of wins and losses (don't let it win every time!)
- Different opening strategies
- At least 50+ games for optimal learning

### Step 3: Train the AI

Once you have game data:

1. **Login as Admin** (User ID 1)
2. Click **"🤖 AI Training"** button on dashboard
3. Click **"Train AI"** for the difficulty you want to train
4. Wait for training to complete (~2-3 seconds)
5. Review the training results

That's it! The AI will now use learned strategies in future games.

---

## 🎮 Testing the Learned AI

To see if the AI is using learned strategies:

1. Start a new game vs AI (same difficulty you trained)
2. Open browser console (F12)
3. Look for the message:
   ```
   AI using learned strategy for hard difficulty
   ```

If you see "AI using standard strategy", the AI is not using learned data.

---

## 📊 Monitoring AI Performance

### From Admin Dashboard

View real-time statistics:
- Total games played
- AI win/loss/draw counts
- Win rate percentage
- Average moves per game
- Last training timestamp

### From Console Logs

During gameplay, watch for:
```javascript
Loaded AI strategy for hard difficulty { total_games: 150, win_rate: 63.33 }
```

---

## 🔄 When to Retrain

Retrain the AI when:
- ✅ After every 20-30 new games
- ✅ When win rate seems off (too high or too low)
- ✅ After making changes to game logic
- ✅ When difficulty seems unbalanced

**Auto-reminder**: There's no automatic retraining yet, so set a reminder to retrain weekly if you have active players.

---

## 🐛 Troubleshooting

### Problem: "No training data available"

**Solution**: Play more games! You need at least 10 games for that difficulty level.

```bash
# Check game count in database:
mysql -u root -proot game
```

```sql
SELECT difficulty, COUNT(*) as game_count
FROM ai_training_data
GROUP BY difficulty;
```

### Problem: Training button doesn't work

**Possible causes:**
1. Not logged in as admin (User ID 1)
2. Database connection issue
3. Cache directory not writable

**Fix:**
```bash
# Check cache permissions:
ls -la /var/www/game.test/cache/

# Make writable if needed:
chmod 755 /var/www/game.test/cache/
```

### Problem: AI still seems dumb after training

**Possible causes:**
1. Not enough training data (< 50 games)
2. Training data is too one-sided (all wins or all losses)
3. Cache file not loading

**Fix:**
```bash
# Check if strategy file exists:
ls -la /var/www/game.test/cache/ai_strategy_*.json

# View strategy contents:
cat /var/www/game.test/cache/ai_strategy_hard.json
```

### Problem: Error during training

**Check error logs:**
1. Visit `/admin-errors.php`
2. Filter by "exception" or "database"
3. Look for training-related errors

---

## 💡 Tips for Best Results

### 1. Diverse Training Data

Train the AI with varied gameplay:
- Different opening moves (center, corners, edges)
- Aggressive and defensive strategies
- Quick wins and long games
- Both AI wins and AI losses

### 2. Balanced Win Rate

Aim for these AI win rates:
- **Easy**: 30-40% (AI should lose more)
- **Medium**: 50-60% (balanced)
- **Hard**: 60-75% (AI should win more)

If win rates are off, retrain or adjust difficulty parameters.

### 3. Regular Maintenance

Set a schedule:
- **Daily**: Check performance stats
- **Weekly**: Retrain AI with new data
- **Monthly**: Review and clean old training data

### 4. Monitor Edge Cases

Watch for:
- Games that end in 3 moves (too easy)
- Games that go 20+ moves (might be draws)
- Repeated same opening patterns

---

## 🎯 Example Training Workflow

Here's a complete workflow from start to finish:

### Week 1: Initial Data Collection
```
Day 1-3: Play 20 games vs Easy AI
Day 4-5: Play 20 games vs Medium AI
Day 6-7: Play 20 games vs Hard AI
```

### Week 2: First Training
```
Monday: Train Easy AI (should have ~20 games)
Tuesday: Train Medium AI
Wednesday: Train Hard AI
Thursday-Sunday: Play 10 more games vs each difficulty
```

### Week 3: First Retrain
```
Monday: Retrain all difficulties with ~30 games each
Tuesday-Sunday: Monitor win rates and adjust
```

### Week 4+: Maintenance Mode
```
Weekly: Retrain if 20+ new games
Monthly: Review stats and clean data
```

---

## 📈 Expected Improvements

After training with good data, you should see:

### Easy Difficulty
- More defensive blocking
- Better opening moves (center/corners)
- Still makes occasional mistakes
- Win rate: 30-40%

### Medium Difficulty
- Recognizes winning patterns
- Uses learned opening sequences
- Blocks most obvious threats
- Win rate: 50-60%

### Hard Difficulty
- Executes winning strategies consistently
- Uses advanced opening patterns
- Rarely misses winning moves
- Sets up multi-move traps
- Win rate: 60-75%

---

## 🔐 Security Notes

### Admin Access

By default, only User ID 1 can access AI training. To add more admins:

**Edit admin-ai-training.php (line 17):**
```php
// Before:
$stmt = $conn->prepare("SELECT id FROM users WHERE id = ? AND id = 1");

// After (multiple admins):
$adminIds = [1, 5, 10]; // Add your admin user IDs
$stmt = $conn->prepare("SELECT id FROM users WHERE id = ? AND id IN (" . implode(',', $adminIds) . ")");
```

### File Permissions

Ensure proper permissions:
```bash
# Cache directory (AI can write strategies):
chmod 755 cache/

# Strategy files (readable by web server):
chmod 644 cache/ai_strategy_*.json

# Admin page (not publicly accessible):
# Add .htaccess or move outside web root if needed
```

---

## 📞 Getting Help

### Check Documentation
- [AI_LEARNING_SYSTEM.md](AI_LEARNING_SYSTEM.md) - Complete technical docs
- [MIGRATION_GUIDE.md](MIGRATION_GUIDE.md) - Database setup
- [GAME_IMPROVEMENTS.md](GAME_IMPROVEMENTS.md) - Recent features

### Debug Tools
1. Browser Console (F12) - JavaScript errors
2. `/admin-errors.php` - Server-side errors
3. Network Tab - API request/response
4. Database Queries - Check data manually

### Common Files to Check
- [ai/LearnedAI.js](ai/LearnedAI.js) - Client-side AI logic
- [ai/AILearningEngine.php](ai/AILearningEngine.php) - Server-side learning
- [api/train-ai.php](api/train-ai.php) - Training endpoint
- [game-engine.js](game-engine.js) - AI integration

---

## ✅ Quick Checklist

Before deploying to production:

- [ ] Database table `ai_training_data` exists
- [ ] Cache directory is writable
- [ ] Admin can access `/admin-ai-training.php`
- [ ] At least 50 games played per difficulty
- [ ] Each difficulty has been trained once
- [ ] AI training dashboard shows statistics
- [ ] Tested gameplay with learned AI
- [ ] Console logs show "AI using learned strategy"
- [ ] Win rates are balanced (not 100% or 0%)
- [ ] Error logs are empty (no training errors)

---

**Happy Training!** 🚀

The AI will continuously improve as more games are played and more training occurs. Encourage players to try all difficulty levels to generate diverse training data.

---

**Version:** 1.0
**Last Updated:** December 27, 2025
