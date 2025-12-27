# Error Display & Debugging System

Complete error handling, logging, and display system for debugging and troubleshooting your game application.

---

## Overview

The Error Display System provides comprehensive error tracking and debugging capabilities with:

- **Global Error Handler** - Catches all PHP errors, exceptions, and fatal errors
- **Beautiful Error Display** - User-friendly error messages with code context
- **Database Logging** - All errors logged to database for admin review
- **File Logging** - Errors also written to log files
- **Admin Panel Integration** - View, filter, and manage errors
- **Toggle Configuration** - Easily enable/disable error display
- **Production-Ready** - Safe for both development and production environments

---

## Features

### 1. Automatic Error Capture

**Captures:**
- Fatal errors (E_ERROR)
- Warnings (E_WARNING)
- Notices (E_NOTICE)
- Parse errors (E_PARSE)
- Deprecated warnings (E_DEPRECATED)
- User-triggered errors
- Uncaught exceptions
- Fatal shutdown errors

**For Each Error:**
- Error type and message
- File path and line number
- Code context (5 lines before/after)
- Full stack trace
- Request details (URI, method, IP)
- User agent information
- Timestamp

### 2. Visual Error Display

When `DISPLAY_ERRORS=true`:

**Beautiful Error Cards Showing:**
- 🎨 Gradient purple background
- ⚠️ Error type badge
- 📝 Clear error message
- 📂 File location with line number
- 💻 Code context with syntax highlighting
- 📊 Full stack trace
- 💡 Helpful tip to disable in production

**Error Display Features:**
- Color-coded by severity
- Responsive design
- Code highlighting (error line in red)
- Collapsible stack traces
- AJAX-compatible (returns JSON for AJAX requests)

### 3. Database Logging

**error_logs Table Structure:**
```sql
- id (Primary Key)
- error_type (Warning, Notice, Exception, etc.)
- error_message (Full error description)
- error_file (Path to file)
- error_line (Line number)
- stack_trace (JSON encoded trace)
- request_uri (URL where error occurred)
- request_method (GET, POST, etc.)
- user_agent (Browser info)
- ip_address (Client IP)
- created_at (Timestamp)
```

**Benefits:**
- Persistent error history
- Admin review and analysis
- Track error patterns
- Identify problematic code
- Monitor production issues

### 4. File Logging

**Log File:** `/logs/error.log`

**Format:**
```
[2025-12-27 14:30:45] Warning: Undefined variable in /path/to/file.php on line 42
[2025-12-27 14:31:10] Fatal Error: Call to undefined function in /path/to/file.php on line 58
```

**Benefits:**
- External monitoring tools can read logs
- Backup of error data
- Easy grep/search
- Persistent across database resets

---

## Configuration

### Environment Variables (.env)

```ini
# Error Display Settings
DISPLAY_ERRORS=true          # Show errors on screen (development)
ERROR_REPORTING=E_ALL        # Report all error types
LOG_ERRORS=true              # Log to database and file
ERROR_LOG_PATH=logs/error.log  # Log file location
```

### Configuration Options

**DISPLAY_ERRORS:**
- `true` - Show detailed errors in browser (development)
- `false` - Hide errors from users (production)

**ERROR_REPORTING:**
- `E_ALL` - All errors, warnings, notices (recommended for development)
- `E_ERROR` - Fatal errors only
- `E_WARNING` - Warnings only
- `0` - Turn off error reporting

**LOG_ERRORS:**
- `true` - Log all errors to database and file (recommended for production)
- `false` - Don't log errors (not recommended)

---

## Usage

### For Developers (Development)

**Recommended Settings:**
```ini
DISPLAY_ERRORS=true
ERROR_REPORTING=E_ALL
LOG_ERRORS=true
```

**What You'll See:**
- Errors displayed immediately in browser
- Beautiful error cards with full details
- Code context showing exact problem
- Stack trace for debugging
- All errors also logged for review

**Example Error Display:**
```
⚠️ Warning
An error occurred in your application

Error Message:
Undefined variable: userId

Location:
File: /var/www/game.test/api/get_user.php
Line: 42

Code Context:
  40:  $conn = $db->getConnection();
  41:
→ 42:  $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
  43:  $stmt->execute([$userId]);
  44:  $user = $stmt->fetch();

Stack Trace:
1. getUserById() in /var/www/game.test/api/get_user.php on line 42
2. handleRequest() in /var/www/game.test/api/index.php on line 15

💡 Tip: To hide error details in production, set DISPLAY_ERRORS=false in your .env file
```

### For Admins (Production)

**Recommended Settings:**
```ini
DISPLAY_ERRORS=false
ERROR_REPORTING=E_ALL
LOG_ERRORS=true
```

**What Happens:**
- Errors logged silently to database
- Users see generic error message (or none)
- Admins can review errors in admin panel
- No sensitive information exposed

**Admin Panel Features:**
1. **View Error Logs** - `/admin/errors.php`
   - See all logged errors
   - Filter by error type
   - View full details and stack traces
   - Delete individual errors
   - Clear all errors (super admin only)

2. **Debug Settings** - `/admin/debug-settings.php`
   - Toggle error display on/off
   - Change error reporting level
   - Enable/disable logging
   - See current configuration status

---

## Admin Panel

### Error Logs Page

**URL:** `/admin/errors.php`

**Features:**
- **Pagination** - 20 errors per page
- **Filtering** - Filter by error type
- **Search** - Find specific errors
- **Expand Details** - Click to see full trace
- **Delete** - Remove individual errors (admin)
- **Clear All** - Delete all logs (super admin)
- **Refresh** - Reload to see new errors

**Error Display:**
```
[Warning] Dec 27, 2025 14:30:45
Undefined variable: userId
/var/www/game.test/api/get_user.php:42

▼ Click to expand details

[Expanded View Shows:]
Request: GET /api/get_user.php?id=123
IP: 192.168.1.100
User Agent: Mozilla/5.0...
Stack Trace:
[Full stack trace here...]
```

### Debug Settings Page

**URL:** `/admin/debug-settings.php`

**Access:** Super Admin only

**Settings:**

1. **Display Errors on Screen**
   - ☑️ Enabled - Show errors in browser
   - ☐ Disabled - Hide errors from users

2. **Log Errors to Database**
   - ☑️ Enabled - Save errors to database
   - ☐ Disabled - Don't save errors

3. **Error Reporting Level**
   - E_ALL - All errors and warnings
   - E_ERROR - Fatal errors only
   - E_WARNING - Warnings only
   - 0 - Silent (no reporting)

**Status Indicators:**
- ⚠️ Development Mode - Error display enabled
- ✅ Production Safe - Error display disabled
- ✅ Enabled - Feature is active
- ❌ Disabled - Feature is inactive

---

## How It Works

### Initialization Flow

1. **Bootstrap** - `config/database.php` loads error handler
2. **Handler Registration** - Error, exception, and shutdown handlers registered
3. **Error Occurs** - PHP triggers error
4. **Handler Catches** - `ErrorHandler::handleError()` is called
5. **Logging** - Error logged to database and file
6. **Display** - Error shown to user (if enabled)

### Error Handler Architecture

```php
ErrorHandler::init()
  ├─ set_error_handler() → handleError()
  ├─ set_exception_handler() → handleException()
  └─ register_shutdown_function() → handleFatalError()

On Error:
  ├─ logError()
  │   ├─ Log to file (logs/error.log)
  │   └─ Log to database (error_logs table)
  └─ displayError() [if enabled]
      ├─ Check if AJAX request
      ├─ Return JSON (AJAX) or HTML
      └─ Show beautiful error card
```

### Code Context Extraction

```php
getFileContext($file, $line, $contextLines = 5)
  ├─ Read file contents
  ├─ Extract lines before/after error
  ├─ Highlight error line in red
  └─ Return formatted HTML
```

---

## Security Considerations

### Production Environment

**⚠️ CRITICAL: Disable Error Display in Production**

```ini
# .env for production
DISPLAY_ERRORS=false
```

**Why?**
- Error messages can expose:
  - File paths and directory structure
  - Database schema and query structure
  - API keys and credentials (if in code)
  - Software versions
  - Business logic

**Recommended Production Setup:**
```ini
DISPLAY_ERRORS=false      # Hide from users
ERROR_REPORTING=E_ALL     # Report all to logs
LOG_ERRORS=true           # Save for admin review
```

### Development Environment

**Safe to Enable Display:**
```ini
DISPLAY_ERRORS=true
ERROR_REPORTING=E_ALL
LOG_ERRORS=true
```

**Benefits:**
- Immediate feedback
- Faster debugging
- Better development experience
- Catch errors early

### Admin Panel Access

**Error Logs Page:**
- Requires Admin or Super Admin role
- Activity logging (who viewed what)
- Delete protection (confirm dialogs)

**Debug Settings Page:**
- Requires Super Admin role only
- Changes logged to admin_activity_log
- Confirmation before changes

---

## Troubleshooting

### Errors Not Displaying

**Problem:** Errors occur but nothing shows on screen

**Solutions:**
1. Check `.env` file: `DISPLAY_ERRORS=true`
2. Verify `error_handler.php` is loaded
3. Check PHP `display_errors` ini setting
4. Clear browser cache
5. Check if error occurred before handler init

### Errors Not Logging

**Problem:** Errors happen but not in database/file

**Solutions:**
1. Check `.env` file: `LOG_ERRORS=true`
2. Verify `error_logs` table exists
3. Check database connection
4. Verify `logs/` directory is writable:
   ```bash
   chmod 755 logs/
   ```
5. Check `error_logs` table structure

### Permission Denied on Log File

**Problem:** Cannot write to `logs/error.log`

**Solution:**
```bash
# Create logs directory
mkdir -p /var/www/game.test/logs

# Set permissions
chmod 755 /var/www/game.test/logs
chown www-data:www-data /var/www/game.test/logs
```

### Stack Trace Too Large

**Problem:** Database error - stack trace exceeds column size

**Solution:**
```sql
-- Increase stack_trace column size
ALTER TABLE error_logs
MODIFY COLUMN stack_trace LONGTEXT;
```

### Duplicate Errors

**Problem:** Same error logged multiple times

**Cause:** Error occurs in loop or frequently-called function

**Solutions:**
1. Fix the underlying code issue
2. Add error suppression (`@`) temporarily
3. Filter duplicates in admin panel
4. Add rate limiting to error logging

---

## Advanced Usage

### Custom Error Logging

```php
// Manual error logging
ErrorHandler::logError([
    'type' => 'Custom Error',
    'message' => 'Something went wrong',
    'file' => __FILE__,
    'line' => __LINE__,
    'trace' => debug_backtrace()
]);
```

### Exception Handling

```php
try {
    // Your code here
} catch (Exception $e) {
    // Error automatically caught and logged
    // by set_exception_handler()
}
```

### AJAX Error Handling

**JavaScript:**
```javascript
fetch('/api/endpoint')
    .then(response => response.json())
    .then(data => {
        if (data.error) {
            console.error('API Error:', data.message);
            console.error('File:', data.file);
            console.error('Line:', data.line);
        }
    });
```

**PHP automatically returns:**
```json
{
    "error": true,
    "type": "Exception",
    "message": "Database connection failed",
    "file": "/var/www/game.test/config/database.php",
    "line": 42
}
```

### Filtering Errors in Admin Panel

**By Type:**
```
/admin/errors.php?type=Warning
/admin/errors.php?type=Exception
/admin/errors.php?type=Fatal%20Error
```

**Pagination:**
```
/admin/errors.php?page=2
/admin/errors.php?page=3
```

---

## Maintenance

### Log Rotation

**Manually:**
```bash
# Rotate error log
mv logs/error.log logs/error.log.$(date +%Y%m%d)
touch logs/error.log
chmod 644 logs/error.log
```

**Automated (cron):**
```bash
# Add to crontab
0 0 * * 0 /path/to/rotate_logs.sh
```

### Database Cleanup

**Delete Old Errors:**
```sql
-- Delete errors older than 30 days
DELETE FROM error_logs
WHERE created_at < DATE_SUB(NOW(), INTERVAL 30 DAY);
```

**Clear All Errors:**
```sql
-- Via admin panel (super admin)
DELETE FROM error_logs;
```

Or use Admin Panel → Error Logs → Clear All button

### Monitor Disk Usage

**Check Log File Size:**
```bash
du -sh logs/error.log
```

**If Too Large:**
1. Rotate logs (see above)
2. Fix errors causing excessive logging
3. Increase log rotation frequency

---

## File Reference

### Core Files

| File | Purpose |
|------|---------|
| [config/error_handler.php](config/error_handler.php) | Global error handler class |
| [admin/errors.php](admin/errors.php) | Error log viewer |
| [admin/debug-settings.php](admin/debug-settings.php) | Debug configuration page |
| [.env](.env) | Environment configuration |
| logs/error.log | Error log file |

### Database Tables

| Table | Purpose |
|-------|---------|
| error_logs | Stores all logged errors |
| admin_activity_log | Tracks admin actions on errors |

---

## Quick Reference

### Enable Error Display

**Development Mode:**
```bash
# Edit .env
DISPLAY_ERRORS=true
ERROR_REPORTING=E_ALL
LOG_ERRORS=true
```

### Disable Error Display

**Production Mode:**
```bash
# Edit .env
DISPLAY_ERRORS=false
ERROR_REPORTING=E_ALL
LOG_ERRORS=true
```

### View Errors

**Admin Panel:**
```
/admin/errors.php
```

### Configure Settings

**Debug Settings:**
```
/admin/debug-settings.php
```

### Check Logs

**View Log File:**
```bash
tail -f logs/error.log
```

**Database Errors:**
```sql
SELECT * FROM error_logs ORDER BY created_at DESC LIMIT 50;
```

---

## Best Practices

### Development

✅ **Do:**
- Enable error display (`DISPLAY_ERRORS=true`)
- Use `E_ALL` error reporting
- Review errors immediately
- Fix warnings and notices
- Test error handling

❌ **Don't:**
- Ignore warnings
- Suppress errors with `@`
- Leave debug code in production
- Commit with errors

### Production

✅ **Do:**
- Disable error display (`DISPLAY_ERRORS=false`)
- Enable error logging (`LOG_ERRORS=true`)
- Monitor error logs regularly
- Set up alerts for critical errors
- Review logs weekly

❌ **Don't:**
- Display errors to users
- Disable error logging
- Ignore error logs
- Let logs grow indefinitely
- Expose stack traces

### Security

✅ **Do:**
- Restrict error log access to admins
- Log admin actions on errors
- Sanitize error messages for users
- Keep logs secure
- Rotate logs regularly

❌ **Don't:**
- Show sensitive data in errors
- Allow public access to logs
- Log passwords or API keys
- Expose file paths to users
- Keep logs publicly accessible

---

## Related Documentation

- [ADMIN_SYSTEM.md](ADMIN_SYSTEM.md) - Admin panel documentation
- [ADMIN_USER_MANAGEMENT.md](ADMIN_USER_MANAGEMENT.md) - User management
- [AI_KNOWLEDGE_BASE.md](AI_KNOWLEDGE_BASE.md) - AI learning system

---

**Version:** 1.0
**Last Updated:** December 27, 2025
**Status:** ✅ Production Ready
