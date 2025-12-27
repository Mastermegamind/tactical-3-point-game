# Admin System - Quick Start Guide

Get started with the admin panel in 5 minutes!

---

## 🚀 Quick Setup (3 Steps)

### Step 1: Install Database Tables

```bash
mysql -u root -proot game < database/admin-tables.sql
```

This creates all necessary admin tables and the default super admin account.

### Step 2: Login to Admin Panel

1. Navigate to: `http://your-domain/admin/login.php`
2. Login with default credentials:
   ```
   Username: admin
   Password: admin123
   ```

### Step 3: Change Password (IMPORTANT!)

1. Go to "Manage Admins" (Super Admin only)
2. Create your personal admin account
3. Logout and login with new account
4. Optionally deactivate/delete default admin

---

## 🎯 What You Can Do

### View Dashboard
- See overall statistics at a glance
- Monitor active users and recent games
- Check admin activity logs

### Manage Users
**Path:** Admin → Users

- View all registered players
- Search by username/email
- Edit user stats (rating, wins, losses, draws)
- Reset passwords
- Delete users

### Manage Games
**Path:** Admin → Games

- View all game sessions
- Filter by status (active/completed)
- Filter by mode (PvP, vs AI)
- See game durations and winners

### View Statistics
**Path:** Admin → Statistics

- User growth charts
- Games per day graphs
- AI performance metrics
- Top players leaderboard

### Train AI
**Path:** Admin → AI Training

- Click "Train AI" for each difficulty
- View training results
- Monitor AI performance

### Manage Admins
**Path:** Admin → Manage Admins (Super Admin only)

- Create new admin accounts
- Assign roles (Moderator, Admin, Super Admin)
- Activate/Deactivate admins
- Delete admins

---

## 👥 User Roles

### Super Admin (Full Access)
✅ Manage admins
✅ Manage users
✅ Train AI
✅ View all stats
✅ Everything

### Admin (Most Access)
✅ Manage users
✅ Train AI
✅ View stats
✅ View error logs
❌ Cannot manage admins

### Moderator (Read-Only)
✅ View users
✅ View games
✅ View stats
❌ Cannot edit/delete
❌ Cannot train AI

---

## 📊 Common Tasks

### Reset a User's Password

1. Go to "Users"
2. Find the user
3. Click "Reset PW"
4. Password is now: `password123`
5. Tell the user to login and change it

### Ban a User

1. Go to "Users"
2. Find the user
3. Click "Delete"
4. Confirm deletion
5. User account is permanently removed

### Check AI Performance

1. Go to "Statistics"
2. Scroll to "AI Performance by Difficulty"
3. Check win rates for each difficulty:
   - Easy: Should be 30-40%
   - Medium: Should be 50-60%
   - Hard: Should be 60-75%

### Train the AI

1. Go to "AI Training"
2. Ensure at least 20 games played for that difficulty
3. Click "Train AI (Easy/Medium/Hard)"
4. Wait 2-3 seconds
5. Review training results
6. AI immediately uses new strategy

### Create a New Admin

1. Go to "Manage Admins" (Super Admin only)
2. Fill in the form:
   - Username
   - Email
   - Full Name
   - Password
   - Role (Moderator/Admin/Super Admin)
3. Click "Create Admin"
4. New admin can login immediately

---

## 🔐 Security Tips

### Change Default Password
```bash
# Default credentials:
Username: admin
Password: admin123

# Change immediately!
```

### Create Personal Admin Account
1. Login with default admin
2. Create your own account
3. Logout
4. Login with your account
5. Delete or deactivate default admin

### Monitor Activity Logs
1. Check "Admin Activity Log" on dashboard
2. Look for suspicious actions
3. Review login times and IP addresses

### Best Practices
- ✅ Use strong passwords
- ✅ Create separate accounts for each admin
- ✅ Assign minimum required permissions
- ✅ Review logs regularly
- ✅ Deactivate unused accounts
- ❌ Don't share admin credentials
- ❌ Don't use same password for multiple admins

---

## 🐛 Troubleshooting

### Problem: Can't Login

**Solution:**
1. Verify credentials are correct
2. Check if account is active in database:
   ```sql
   SELECT * FROM admins WHERE username = 'admin';
   ```
3. Ensure `is_active = 1`
4. Clear browser cache and cookies

### Problem: Permission Denied

**Solution:**
1. Check your role:
   ```sql
   SELECT role FROM admins WHERE username = 'your_username';
   ```
2. Verify the page requires your role level
3. Ask super admin to upgrade your role

### Problem: Can't See "Manage Admins"

**Solution:**
- Only Super Admins can manage other admins
- Ask existing super admin to promote you
- Or use database:
  ```sql
  UPDATE admins SET role = 'super_admin' WHERE username = 'your_username';
  ```

---

## 📈 Quick Stats

### Dashboard Metrics
- **Total Users:** All registered players
- **Active Users:** Online now or in last 10 minutes
- **Total Games:** All games ever played
- **Games Today:** Games started today
- **Completion Rate:** % of games finished

### User Management
- **20 users per page** with pagination
- **Search** by username or email
- **Filter** by online status
- **Sort** by join date

### Statistics
- **7-day game chart** - Games per day
- **Mode distribution** - Pie chart
- **AI performance** - Win rates by difficulty
- **Top 10 players** - Leaderboard

---

## 🎯 Quick Actions

### Promote User to Admin
```sql
-- Option 1: Add to admins table
INSERT INTO admins (username, email, password, full_name, role)
VALUES ('username', 'email@example.com', 'hashed_password', 'Full Name', 'admin');

-- Option 2: Give existing user admin access
UPDATE users SET is_admin = 1 WHERE username = 'username';
```

### View All Admin Actions
```sql
SELECT
    a.username,
    aal.action,
    aal.description,
    aal.created_at
FROM admin_activity_log aal
JOIN admins a ON aal.admin_id = a.id
ORDER BY aal.created_at DESC
LIMIT 50;
```

### Check Active Sessions
```sql
SELECT
    a.username,
    s.ip_address,
    s.login_time,
    s.is_active
FROM admin_sessions s
JOIN admins a ON s.admin_id = a.id
WHERE s.is_active = 1
ORDER BY s.login_time DESC;
```

---

## 📞 Getting Help

### Documentation
- **Full Docs:** [ADMIN_SYSTEM.md](ADMIN_SYSTEM.md)
- **Database Schema:** See `database/admin-tables.sql`
- **API Docs:** Coming soon

### Common Files
- **Login:** `/admin/login.php`
- **Dashboard:** `/admin/index.php`
- **Users:** `/admin/users.php`
- **Games:** `/admin/games.php`
- **Statistics:** `/admin/statistics.php`
- **AI Training:** `/admin/ai-training.php`
- **Admins:** `/admin/admins.php`

### Database Tables
```
admins                  -- Admin accounts
admin_sessions          -- Login sessions
admin_activity_log      -- Action audit trail
users                   -- Game users (with is_admin column)
```

---

## ✅ Post-Installation Checklist

After setting up the admin system:

- [ ] Logged in with default credentials
- [ ] Created personal super admin account
- [ ] Changed/deleted default admin account
- [ ] Created additional admin accounts if needed
- [ ] Assigned appropriate roles
- [ ] Tested user management features
- [ ] Tested AI training
- [ ] Viewed statistics dashboard
- [ ] Checked admin activity logs
- [ ] Set up regular backups
- [ ] Documented admin credentials securely

---

## 🎉 You're Ready!

You now have a fully functional admin panel. Explore the features and manage your game platform with ease.

**Quick Links:**
- [Full Documentation](ADMIN_SYSTEM.md)
- [AI Learning System](AI_LEARNING_SYSTEM.md)
- [Game Updates](UPDATES_V1.7.md)

---

**Happy Administrating!** 🚀

**Version:** 1.0
**Last Updated:** December 27, 2025
