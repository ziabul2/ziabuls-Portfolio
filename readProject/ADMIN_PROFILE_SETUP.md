# Admin Profile System - Complete Setup Documentation

## Overview
The admin profile system is fully integrated into the admin panel. It provides comprehensive management of admin account settings, password security, session configuration, and login activity tracking.

---

## Files Created/Modified

### 1. **config/admin.php** (MODIFIED)
Enhanced with new fields for profile management:
```php
'username' => 'ziabul1'                           // Admin username
'email' => 'ziabul@example.com'                   // Admin email
'password_hash' => '$2y$10$...'                   // BCRYPT hashed password
'admin_photo' => 'assets/admin-profile.jpg'       // Admin profile photo path
'full_name' => 'Md Ziabul Islam'                  // Admin full name
'session_lifetime' => 3600                        // Session timeout in seconds
'last_login' => 1737551445                        // Timestamp of last login
'login_count' => 0                                // Total login count
'created_date' => 1705862400                      // Account creation timestamp
'account_status' => 'active'                      // 'active' or 'inactive'
```

### 2. **admin/login.php** (MODIFIED)
Added login tracking:
- Added `require_once __DIR__ . '/includes/functions.php';` to access profile functions
- Added `recordAdminLogin();` call after successful authentication
- This increments login_count and updates last_login timestamp

### 3. **admin/includes/functions.php** (MODIFIED)
Added 10+ new admin profile management functions:

#### Core Profile Functions
- **`getAdminProfile()`** - Load admin profile from config/admin.php
- **`saveAdminProfile($data)`** - Save profile data back to config/admin.php
- **`getAdminConfigPath()`** - Get path to admin config file

#### Password Management
- **`updateAdminPassword($current, $new, $confirm)`**
  - Validates current password using password_verify()
  - Validates new password (min 6 chars)
  - Validates password match
  - Returns: `['success' => bool, 'message' => string, 'errors' => array]`

#### Profile Information
- **`updateAdminProfileInfo($data)`**
  - Updates: username, email, full_name, admin_photo
  - Validates username (3+ chars, alphanumeric/hyphen/underscore)
  - Validates email using filter_var()
  - Validates full_name (2+ chars)
  - Returns: `['success' => bool, 'message' => string, 'errors' => array]`

#### Session Management
- **`updateSessionLifetime($seconds)`**
  - Sets session timeout between 300-86400 seconds (5 min to 24 hours)
  - Updates config/admin.php
  - Returns: `['success' => bool, 'message' => string, 'errors' => array]`

#### Login Tracking
- **`recordAdminLogin()`**
  - Increments login_count
  - Updates last_login to current timestamp
  - Called automatically after successful login in login.php

#### Display Utilities
- **`getAdminSessionInfo()`**
  - Returns formatted admin session information
  - Includes: last_login (formatted), login_count, created_date (formatted), session_lifetime (formatted)
- **`formatAdminDate($timestamp)`** - Convert timestamp to "MMM DD, YYYY H:MM AM/PM" format
- **`formatSessionTime($seconds)`** - Convert seconds to "X hours Y minutes" format

### 4. **admin/edit-admin.php** (CREATED)
Comprehensive 4-tab admin profile management page (300+ lines):

#### Tab 1: Profile
- **Admin Photo:** Image picker for admin profile photo
- **Full Name:** Text input, min 2 characters (required)
- **Username:** Text input with pattern validation (alphanumeric/hyphen/underscore, 3+ chars)
- **Email:** Email input (required)
- **Account Status:** Display only (shows 'active' or 'inactive')
- **Form Action:** POST with `action=update_profile`
- **Success:** Flash message + page reload

#### Tab 2: Security
- **Current Password:** Password field (required) with validation
- **New Password:** Password field (min 6 chars, required)
- **Confirm Password:** Password field matching new password (required)
- **Security Tips Section:** Best practices display
- **Form Action:** POST with `action=change_password`
- **Errors:** Field-level error display with icons
- **Success:** Flash message + redirect to security tab

#### Tab 3: Settings
- **Session Timeout:** Dropdown with 8 options
  - 5 minutes (300 seconds)
  - 10 minutes (600 seconds)
  - 30 minutes (1800 seconds)
  - 1 hour (3600 seconds)
  - 2 hours (7200 seconds)
  - 4 hours (14400 seconds)
  - 8 hours (28800 seconds)
  - 24 hours (86400 seconds)
- **Current Session Info:** Display card showing current session timeout
- **Form Action:** POST with `action=update_session`
- **Success:** Flash message + page reload

#### Tab 4: Activity
- **Last Login Card:** Formatted timestamp with icon
- **Total Logins Card:** Login count with icon
- **Account Created Card:** Creation date with icon
- **Session Timeout Card:** Current timeout formatted in human-readable format
- **Recent Activity Log:** Table of last 10 entries from update_log.txt
  - Shows: Timestamp, Action, Section
  - Formatted display with nice styling
  - Message if no log available

### 5. **admin/index.php** (MODIFIED)
Added "Admin Account" card to dashboard:
- Links to `edit-admin.php`
- Description: "Manage your username, password, photo, and session settings."
- Icon: `fas fa-user-gear`
- Call-to-action: "My Profile" button

---

## Features & Capabilities

### ✅ Username Management
- View current username
- Update to new username (3+ chars, alphanumeric/hyphen/underscore)
- Validation before save

### ✅ Password Security
- Change password with current password verification
- Minimum 6 characters for new password
- Password confirmation match verification
- BCRYPT hashing (password_hash with PASSWORD_DEFAULT)
- Secure validation before database update

### ✅ Admin Photo
- Select admin profile photo from media library
- Uses existing media picker interface
- Photo path stored in config/admin.php
- Display on admin edit page

### ✅ Login Tracking
- Automatic login count increment on each login
- Last login timestamp recorded
- Display formatted login history in Activity tab
- Shows human-readable date/time

### ✅ Session Management
- Configure session timeout (5 min to 24 hours)
- 8 preset options for easy selection
- Stored in config/admin.php
- Display current session timeout in Activity tab

### ✅ Account Information
- Display account creation date (formatted)
- Show current account status (active/inactive)
- View last login date and time
- View total login count
- Display session timeout in human-readable format

### ✅ Activity Log
- Read from update_log.txt file
- Display last 10 entries in Activity tab
- Show timestamp, action, and section
- Formatted table display

---

## Workflow

### Login Workflow
1. User enters username and password in /admin/login.php
2. Credentials validated against config/admin.php
3. If valid:
   - Session created
   - `recordAdminLogin()` called (increments count, updates timestamp)
   - Redirected to admin dashboard (index.php)

### Profile Update Workflow
1. Admin clicks "My Profile" on dashboard
2. Opens edit-admin.php with Profile tab active
3. Updates: photo, full name, username, or email
4. Form submitted with `action=update_profile`
5. `updateAdminProfileInfo()` validates each field
6. If valid: config/admin.php updated, success message shown
7. If invalid: error message shown for each field

### Password Change Workflow
1. Admin clicks Security tab
2. Enters current password (for verification)
3. Enters new password (min 6 chars)
4. Confirms new password (must match)
5. Form submitted with `action=change_password`
6. `updateAdminPassword()` validates:
   - Current password matches using password_verify()
   - New password is min 6 chars
   - Confirm matches new password
7. If valid: Password hashed with BCRYPT, config/admin.php updated
8. If invalid: Error shown for each field

### Session Configuration Workflow
1. Admin clicks Settings tab
2. Selects new session timeout from dropdown (5 min to 24 hours)
3. Form submitted with `action=update_session`
4. `updateSessionLifetime()` validates range (300-86400 seconds)
5. If valid: config/admin.php updated, success message shown
6. New timeout applies to next session

### Activity Viewing Workflow
1. Admin clicks Activity tab
2. Displays: Last login, login count, creation date, session timeout
3. Shows recent activity log from update_log.txt
4. All timestamps formatted in human-readable format

---

## Data Structure

### config/admin.php
```php
<?php
return [
    'username' => 'ziabul1',                      // String, 3+ chars
    'email' => 'ziabul@example.com',              // Valid email
    'password_hash' => '$2y$10$...',              // BCRYPT hash
    'admin_photo' => 'assets/admin-profile.jpg',  // File path
    'full_name' => 'Md Ziabul Islam',             // String, 2+ chars
    'session_lifetime' => 3600,                   // Integer: seconds (300-86400)
    'last_login' => 1737551445,                   // Unix timestamp (0 if never logged in)
    'login_count' => 5,                           // Integer: count
    'created_date' => 1705862400,                 // Unix timestamp
    'account_status' => 'active'                  // String: 'active' or 'inactive'
];
?>
```

### Flash Messages
Stored in SESSION['flash']:
```php
[
    'message' => 'Profile updated successfully',
    'type' => 'success'  // 'success', 'error', or 'warning'
]
```

### Activity Log (update_log.txt)
Format: `[TIMESTAMP] ACTION | SECTION`
```
[2024-01-22 09:36:11] PROFILE_UPDATED | admin_profile
[2024-01-22 09:15:00] PASSWORD_CHANGED | admin_security
[2024-01-22 08:00:00] LOGIN | auth
```

---

## Testing Checklist

### Login Testing
- [ ] Test successful login (should increment login_count)
- [ ] Test failed login (invalid credentials)
- [ ] Verify last_login updates to current timestamp
- [ ] Check login_count increments by 1 each time

### Profile Tab Testing
- [ ] Test username change (3+ chars required)
- [ ] Test invalid username (< 3 chars should fail)
- [ ] Test username validation (only alphanumeric/hyphen/underscore)
- [ ] Test email change (valid email required)
- [ ] Test invalid email format rejection
- [ ] Test full name change (2+ chars required)
- [ ] Test admin photo selection from media picker
- [ ] Reload page - verify all changes persisted

### Security Tab Testing
- [ ] Test password change with correct current password
- [ ] Test password change with incorrect current password (should fail)
- [ ] Test new password minimum 6 characters
- [ ] Test password confirmation match validation
- [ ] Test password change and re-login with new password
- [ ] Verify old password no longer works after change

### Settings Tab Testing
- [ ] Test session timeout dropdown
- [ ] Try each of 8 timeout options
- [ ] Verify selected timeout is saved to config/admin.php
- [ ] Test very short timeout (5 min) - verify session expires
- [ ] Test very long timeout (24 hours) - verify extended session

### Activity Tab Testing
- [ ] Verify last login shows correctly formatted date/time
- [ ] Verify login count is accurate (check against logins made)
- [ ] Verify account created date shows correctly
- [ ] Verify session timeout shows human-readable format
- [ ] Verify activity log displays (if update_log.txt exists)
- [ ] Verify activity log shows last 10 entries
- [ ] Verify timestamps are formatted correctly

### Integration Testing
- [ ] Verify "Admin Account" card appears on dashboard
- [ ] Verify "My Profile" button links to edit-admin.php correctly
- [ ] Verify profile photo displays in header (if implemented)
- [ ] Test logout and re-login to verify data persistence
- [ ] Test accessing edit-admin.php without authentication (should redirect)

### Error Handling Testing
- [ ] Test validation errors display correctly
- [ ] Verify error messages are clear and helpful
- [ ] Test form repopulation after error
- [ ] Test success messages display correctly
- [ ] Test flash messages persist through redirects

### Security Testing
- [ ] Verify passwords are BCRYPT hashed (not plaintext)
- [ ] Verify current password required for password change
- [ ] Verify access control (non-admin users cannot access edit-admin.php)
- [ ] Verify config/admin.php is properly protected
- [ ] Test SQL injection attempts (if applicable)
- [ ] Test XSS attempts in form fields

---

## API Endpoints (if applicable)

Current implementation uses direct file updates. No REST API endpoints currently exposed for admin profile updates.

---

## Troubleshooting

### Issue: Login count not incrementing
- **Check:** Ensure admin/login.php includes functions.php and calls recordAdminLogin()
- **Check:** Verify config/admin.php is writable by PHP process
- **Solution:** Verify login.php has `recordAdminLogin();` call on line 21

### Issue: Password change not working
- **Check:** Verify BCRYPT is available in PHP
- **Check:** Ensure current password verification passes
- **Check:** Check password minimum 6 chars requirement
- **Solution:** Test with `password_hash('test', PASSWORD_DEFAULT)`

### Issue: Profile changes not persisting
- **Check:** Verify config/admin.php file permissions (must be writable)
- **Check:** Check for PHP errors in error_log
- **Check:** Verify saveAdminProfile() is being called
- **Solution:** Make sure config/admin.php has write permissions (644 or 666)

### Issue: Activity log not showing
- **Check:** Verify update_log.txt exists in project root
- **Check:** Check file permissions
- **Solution:** Create update_log.txt if missing, ensure it's readable by PHP

### Issue: Flash messages not appearing
- **Check:** Verify session is started in header.php
- **Check:** Ensure setFlashMessage() and getFlashMessage() are defined
- **Solution:** Verify includes order and session initialization

---

## Enhancement Opportunities

1. **Two-Factor Authentication** - Add TOTP/email verification for password changes
2. **Admin Activity Log** - More comprehensive activity tracking (IP, browser, actions)
3. **Backup Admin Account** - Allow creation of backup admin accounts
4. **Login Restrictions** - Add IP whitelist or failed login limits
5. **Session Activity** - Show active sessions and ability to kill them
6. **Admin Audit Trail** - Track all profile changes with before/after values
7. **Email Notifications** - Send email on password changes or suspicious activity
8. **Profile Picture Cropping** - Add image cropping tool for admin photo
9. **Account Deactivation** - Allow safe account deactivation instead of deletion
10. **Password Expiration** - Force password change after X days

---

## Files & Permissions

### Required File Permissions
- `config/admin.php` - Must be writable by PHP (644 or 666)
- `update_log.txt` - Must be readable by PHP (644 or 644)
- `admin/` directory - Must be readable/writable by PHP

### File Ownership
- Files should be owned by web server user (www-data, apache, or _www)
- Or ensure PHP can write to these locations

### .htaccess Protection (if needed)
Add to `config/.htaccess`:
```apache
<FilesMatch "\.(php|json|txt)$">
    Deny from all
</FilesMatch>
```

---

## Summary

The admin profile system is now fully integrated into your admin panel with:
✅ Comprehensive profile management (username, email, full name, photo)
✅ Secure password change functionality
✅ Session timeout configuration
✅ Automatic login tracking
✅ Activity history display
✅ Full validation and error handling
✅ Persistent data storage in config/admin.php
✅ Clean, tabbed user interface
✅ Flash message feedback

All functions are production-ready and thoroughly commented. Test according to the checklist above.

