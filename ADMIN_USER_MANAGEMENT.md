# Admin User Management - Complete Guide

Comprehensive user administration with ban/block features and game-related actions.

---

## 🎯 Overview

The enhanced admin user management system provides complete control over user accounts with advanced moderation and administration features.

---

## ✨ Features

### 1. **Ban System** 🚫
- Permanently or temporarily ban users
- Custom ban reasons
- Automatic ban expiration
- Force logout on ban
- Ban history tracking

### 2. **User Statistics Management** 📊
- Edit individual stats (W/L/D, Rating)
- Reset all stats to default
- Adjust rating incrementally
- View complete user profile

### 3. **Account Actions** ⚙️
- Force logout online users
- Reset passwords
- Delete user accounts
- Delete all user games

### 4. **Security Features** 🔐
- Activity logging for all actions
- Ban enforcement at login
- Admin tracking (who banned whom)
- IP address logging

---

## 🗄️ Database Schema

### Users Table (Enhanced)

New columns added:

```sql
is_banned TINYINT(1) DEFAULT 0
ban_reason TEXT DEFAULT NULL
banned_at TIMESTAMP NULL
banned_by INT(11) NULL
ban_expires_at TIMESTAMP NULL
```

**Column Descriptions:**
- `is_banned`: Boolean flag (0 = active, 1 = banned)
- `ban_reason`: Admin-provided reason for ban
- `banned_at`: When the ban was applied
- `banned_by`: Admin ID who issued the ban
- `ban_expires_at`: NULL for permanent, or expiration timestamp

---

## 🎮 Admin Actions

### Ban User

**Purpose:** Temporarily or permanently block a user from logging in

**How to Use:**
1. Click "⚙️ Manage" next to user
2. Click "🚫 Ban User"
3. Enter ban reason
4. Select duration:
   - Permanent
   - 1 Hour
   - 6 Hours
   - 1 Day
   - 3 Days
   - 1 Week
   - 1 Month
5. Confirm ban

**What Happens:**
- User is immediately logged out
- Cannot login until ban expires/removed
- Ban reason shown on login attempt
- Admin action is logged

**Example Ban Reasons:**
- "Cheating detected"
- "Inappropriate username"
- "Harassment/toxicity"
- "Multiple account violations"
- "Terms of service violation"

---

### Unban User

**Purpose:** Remove ban from a user account

**How to Use:**
1. Find banned user (highlighted in red)
2. Click "⚙️ Manage"
3. Click "✅ Unban User"
4. Confirm unban

**What Happens:**
- All ban data cleared
- User can immediately login
- Admin action is logged

---

### Edit User Stats

**Purpose:** Manually adjust user game statistics

**How to Use:**
1. Click "⚙️ Manage" → "✏️ Edit Stats"
2. Modify:
   - Rating
   - Wins
   - Losses
   - Draws
3. Save changes

**Use Cases:**
- Correct errors in stats
- Adjust for cheating/unfair games
- Reward legitimate players
- Testing purposes

---

### Adjust Rating

**Purpose:** Increase or decrease user rating by specific amount

**How to Use:**
1. Click "⚙️ Manage" → "⭐ Adjust Rating"
2. Enter change amount:
   - Positive number (e.g., `+50`) to increase
   - Negative number (e.g., `-25`) to decrease
3. Confirm

**Use Cases:**
- Penalty for rule violations
- Reward for tournament wins
- Compensation for technical issues
- Rating corrections

**Examples:**
- User cheated: `-100` rating
- Won tournament: `+200` rating
- Server glitch caused unfair loss: `+25` rating

---

### Reset All Stats

**Purpose:** Reset user to default statistics

**How to Use:**
1. Click "⚙️ Manage" → "🔄 Reset All Stats"
2. Confirm reset

**What Happens:**
- Rating → 1000 (default)
- Wins → 0
- Losses → 0
- Draws → 0

**Use Cases:**
- Fresh start for returning players
- Account reset requests
- After major rule changes
- Testing purposes

---

### Reset Password

**Purpose:** Change user password to default

**How to Use:**
1. Click "⚙️ Manage" → "🔑 Reset Password"
2. Confirm reset

**What Happens:**
- Password → `password123`
- User receives no notification (tell them manually)

**Use Cases:**
- User forgot password
- Account recovery
- Security breach response

**⚠️ Remember:** Tell the user their new password is `password123`

---

### Force Logout

**Purpose:** Immediately disconnect online user

**How to Use:**
1. Click "⚙️ Manage" → "🚪 Force Logout"
2. Confirm action

**What Happens:**
- User marked as offline in database
- Next page load will redirect to login
- Current session invalidated

**Use Cases:**
- Suspicious activity
- Before banning user
- Account security issues
- Testing purposes

---

### Delete All Games

**Purpose:** Remove all game history for a user

**How to Use:**
1. Click "⚙️ Manage" → "🗑️ Delete All Games"
2. Confirm deletion

**What Happens:**
- All game_sessions where user was player1 or player2 are deleted
- Game moves for those sessions also deleted (cascade)
- User stats remain unchanged
- Shows count of deleted games

**Use Cases:**
- Cheated games cleanup
- Data privacy requests
- Account reset requests
- Testing/development

**⚠️ Warning:** This cannot be undone!

---

### Delete Account

**Purpose:** Permanently remove user account

**How to Use:**
1. Click "⚙️ Manage" → "❌ Delete Account"
2. Confirm deletion

**What Happens:**
- User account permanently deleted
- All associated data may be removed (depending on foreign keys)
- Cannot be undone

**Use Cases:**
- GDPR/data deletion requests
- Permanent ban
- Duplicate accounts
- Spam accounts

**⚠️ Warning:** This is permanent and cannot be undone!

---

## 📊 User Table Display

### Status Column

Shows current user status:

**Active User:**
```
✅ Active
```

**Banned User:**
```
🚫 BANNED
Reason: Cheating detected
Until: Dec 31, 2025 23:59
```

or

```
🚫 BANNED
Reason: Terms of service violation
Permanent
```

### Visual Indicators

- **Red highlight** - Banned user row
- **Green badge** - Online status
- **Red badge** - BANNED label
- **Primary badge** - Rating display

---

## 🔐 Ban Enforcement

### Login Protection

Banned users cannot login:

1. User enters credentials
2. Password verified
3. **Ban check performed**
4. If banned:
   - Check if ban expired
   - If expired: auto-unban and allow login
   - If not expired: show ban message and prevent login

### Ban Messages

**Permanent Ban:**
```
Your account has been banned: Cheating detected
```

**Temporary Ban:**
```
Your account has been banned: Inappropriate behavior (Until: Dec 31, 2025 23:59)
```

---

## 📝 Activity Logging

All admin actions are logged in `admin_activity_log`:

### Logged Actions

| Action | Description |
|--------|-------------|
| `ban_user` | User was banned |
| `unban_user` | User was unbanned |
| `update_user` | User stats were modified |
| `reset_password` | Password was reset |
| `reset_stats` | All stats were reset |
| `force_logout` | User was force logged out |
| `delete_games` | User's games were deleted |
| `delete_user` | User account was deleted |
| `adjust_rating` | User rating was adjusted |

### Log Entry Example

```sql
admin_id: 1
action: 'ban_user'
description: 'Banned user ID: 42 - Reason: Cheating detected'
target_type: 'user'
target_id: 42
ip_address: '192.168.1.100'
created_at: '2025-12-27 10:30:45'
```

### View Activity Logs

```sql
-- See all user moderation actions
SELECT * FROM admin_activity_log
WHERE target_type = 'user'
ORDER BY created_at DESC;

-- See specific admin's actions
SELECT * FROM admin_activity_log
WHERE admin_id = 1
AND action IN ('ban_user', 'unban_user')
ORDER BY created_at DESC;

-- See all bans
SELECT * FROM admin_activity_log
WHERE action = 'ban_user'
ORDER BY created_at DESC;
```

---

## 🛡️ Best Practices

### When to Ban

✅ **Ban for:**
- Confirmed cheating
- Harassment/toxicity
- Multiple rule violations
- Terms of service violations
- Spam/bot accounts

❌ **Don't ban for:**
- Single mistakes
- Minor rule infractions (use warnings)
- Disputes between players
- Personal disagreements

### Ban Duration Guidelines

| Violation | Suggested Duration |
|-----------|-------------------|
| First offense (minor) | 6 hours - 1 day |
| Second offense | 3 days - 1 week |
| Third offense | 1 week - 1 month |
| Severe violations | Permanent |
| Cheating/hacking | Permanent |
| Spam bots | Permanent |

### Documentation

Always document:
- Clear ban reason
- Evidence (if applicable)
- Duration and rationale
- Communication with user (if any)

---

## 🔍 Finding Banned Users

### Search for Banned Users

```sql
-- All banned users
SELECT * FROM users WHERE is_banned = 1;

-- Banned users with active (non-expired) bans
SELECT * FROM users
WHERE is_banned = 1
AND (ban_expires_at IS NULL OR ban_expires_at > NOW());

-- Banned users by admin
SELECT u.*, a.username as banned_by_admin
FROM users u
JOIN admins a ON u.banned_by = a.id
WHERE u.is_banned = 1;
```

### Filter in Admin Panel

Currently, all users are shown together. Banned users are highlighted in red.

---

## ⏰ Auto-Expiring Bans

Bans automatically expire at login:

1. User attempts login
2. System checks `ban_expires_at`
3. If current time > expiration time:
   - Ban is removed
   - User can login normally
4. If ban still active:
   - Login denied
   - Ban message shown

---

## 📞 Support Workflow

### User Reports Account Ban

**Admin Steps:**
1. Go to Admin Panel → Users
2. Search for username
3. View ban status
4. Check ban reason and duration
5. Review admin activity log
6. Decide:
   - Keep ban: Explain reason to user
   - Remove ban: Click "Unban User"

### User Requests Account Deletion

**Admin Steps:**
1. Verify user identity
2. Go to Admin Panel → Users
3. Find user account
4. Choose deletion method:
   - Delete games only (keep account)
   - Delete account entirely
5. Confirm with user before deleting
6. Execute deletion
7. Document in logs

---

## ✅ Testing Checklist

### Ban System
- [ ] Ban user with permanent ban
- [ ] Ban user with temporary ban (1 hour)
- [ ] Verify user cannot login when banned
- [ ] Wait for ban to expire (or modify DB)
- [ ] Verify user auto-unbanned at login
- [ ] Unban user manually
- [ ] Verify user can login after unban

### User Actions
- [ ] Edit user stats
- [ ] Reset all stats
- [ ] Adjust rating (+/-)
- [ ] Reset password
- [ ] Force logout online user
- [ ] Delete user games
- [ ] Delete user account

### Activity Logging
- [ ] Verify all actions logged
- [ ] Check admin ID is correct
- [ ] Verify IP address captured
- [ ] View logs in admin panel

---

## 🎯 Quick Reference

### Common Tasks

**Ban a cheater:**
```
Manage → Ban User → Reason: "Cheating detected" → Permanent → Confirm
```

**Temporarily ban for toxicity:**
```
Manage → Ban User → Reason: "Inappropriate behavior" → 3 Days → Confirm
```

**Reset stats for new season:**
```
Manage → Reset All Stats → Confirm
```

**Compensate for server issue:**
```
Manage → Adjust Rating → Enter: +25 → Confirm
```

**Remove all games for privacy request:**
```
Manage → Delete All Games → Confirm
```

---

## 📚 Related Documentation

- [ADMIN_SYSTEM.md](ADMIN_SYSTEM.md) - Complete admin system docs
- [ADMIN_QUICK_START.md](ADMIN_QUICK_START.md) - Quick start guide
- Database schema: `database/admin-tables.sql`

---

**Version:** 1.0
**Last Updated:** December 27, 2025
**Status:** ✅ Production Ready
