# Admin Profile System - Visual Architecture Map

## System Architecture Overview

```
┌─────────────────────────────────────────────────────────────────┐
│                    ADMIN PROFILE SYSTEM                          │
└─────────────────────────────────────────────────────────────────┘

                         ┌─────────────┐
                         │  Admin User │
                         └──────┬──────┘
                                │
                    ┌───────────┴──────────┐
                    │                      │
                 Login              Access Profile
                    │                      │
         ┌──────────▼──────────┐   ┌──────▼──────────┐
         │  admin/login.php    │   │ admin/index.php │
         │  (Authentication)   │   │  (Dashboard)    │
         └──────────┬──────────┘   └──────┬──────────┘
                    │                      │
         ┌──────────▼──────────┐   ┌──────▼──────────┐
         │ recordAdminLogin()  │   │ Admin Account   │
         │ (Track Login)       │   │ Card Link       │
         └──────────┬──────────┘   └──────┬──────────┘
                    │                      │
                    └──────────┬───────────┘
                               │
                    ┌──────────▼──────────┐
                    │ admin/edit-admin.php│
                    │ (Profile Manager)   │
                    └──────────┬──────────┘
                               │
        ┌──────────────────────┼──────────────────────┐
        │                      │                      │
    ┌───▼────┐  ┌───────────┐  ┌──────────────┐  ┌──────────┐
    │ Profile │  │ Security  │  │  Settings    │  │ Activity │
    │ Tab     │  │ Tab       │  │  Tab         │  │ Tab      │
    └───┬────┘  └───┬───────┘  └──────┬───────┘  └──────┬───┘
        │           │                  │                │
    ┌───▼────────────▼──────────────────▼────────────────▼───┐
    │   admin/includes/functions.php                        │
    │   (Admin Management Functions)                        │
    └───┬────────────────────────────────────────────────────┘
        │
        │ Uses/Updates
        │
    ┌───▼────────────────────────────────────────────────────┐
    │   config/admin.php                                    │
    │   (Admin Metadata & Settings)                         │
    └────────────────────────────────────────────────────────┘
```

---

## Data Flow Diagram

```
┌─────────────┐
│ Admin User  │
└──────┬──────┘
       │
       ├─────► Edit Username
       ├─────► Edit Email
       ├─────► Edit Full Name
       ├─────► Upload Photo
       ├─────► Change Password
       ├─────► Update Session Timeout
       └─────► View Activity

       All ▼ Operations

┌──────────────────────────────────────────────┐
│  updateAdminProfileInfo()                    │
│  updateAdminPassword()                       │
│  updateSessionLifetime()                     │
│  recordAdminLogin()                          │
└──────────────┬───────────────────────────────┘
               │
               ├─► Validation
               ├─► Error Checking
               └─► Data Formatting

       All ▼ Persist To

┌──────────────────────────────────────────────┐
│  config/admin.php                            │
│  ├─ username                                 │
│  ├─ email                                    │
│  ├─ password_hash                            │
│  ├─ admin_photo                              │
│  ├─ full_name                                │
│  ├─ session_lifetime                         │
│  ├─ last_login                               │
│  ├─ login_count                              │
│  ├─ created_date                             │
│  └─ account_status                           │
└──────────────┬───────────────────────────────┘
               │
               ├─► Display on Profile Tab
               ├─► Show in Activity Tab
               └─► Use for Session Config

       All ▼ Displayed Via

┌──────────────────────────────────────────────┐
│  admin/edit-admin.php                        │
│  ├─ Tab Navigation                           │
│  ├─ Form Rendering                           │
│  ├─ Error Display                            │
│  └─ Success Messages                         │
└──────────────────────────────────────────────┘
```

---

## Tab Structure

```
┌─────────────────────────────────────────────────────────────┐
│                   admin/edit-admin.php                       │
├─────────────────────────────────────────────────────────────┤
│                                                              │
│  ┌──────────┐  ┌──────────┐  ┌──────────┐  ┌──────────┐   │
│  │ Profile  │  │ Security │  │ Settings │  │ Activity │   │
│  └────┬─────┘  └────┬─────┘  └────┬─────┘  └────┬─────┘   │
│       │            │             │             │           │
├───────┼────────────┼─────────────┼─────────────┼──────────┤
│       │            │             │             │           │
│   Profile Tab   Security Tab   Settings Tab   Activity Tab │
│   ┌──────────┐  ┌──────────┐  ┌──────────┐  ┌─────────┐  │
│   │ Username │  │ Current  │  │ Session  │  │ Login   │  │
│   │ Email    │  │ Password │  │ Timeout  │  │ Info    │  │
│   │ Full Name│  │ New Pwd  │  │ Dropdown │  │ History │  │
│   │ Photo    │  │ Confirm  │  │ Options  │  │ Log     │  │
│   │ Status   │  │ Security │  │ Save Btn │  │ Display │  │
│   │ Save Btn │  │ Tips     │  │          │  │         │  │
│   │          │  │ Save Btn │  │          │  │         │  │
│   └──────────┘  └──────────┘  └──────────┘  └─────────┘  │
│       │            │             │             │           │
│  updateAdmin    updateAdmin   updateAdmin   (Read-Only)   │
│  ProfileInfo()  Password()    SessionTime()              │
│                                                              │
└─────────────────────────────────────────────────────────────┘
```

---

## Session Timeout Options

```
Dropdown Menu:
├─ 5 minutes     (300 seconds)    ┐
├─ 10 minutes    (600 seconds)    │
├─ 30 minutes    (1,800 seconds)  ├─ Validation Range
├─ 1 hour        (3,600 seconds)  │ 300 - 86400 seconds
├─ 2 hours       (7,200 seconds)  │
├─ 4 hours       (14,400 seconds) │
├─ 8 hours       (28,800 seconds) │
└─ 24 hours      (86,400 seconds) ┘

Applied to: config/admin.php['session_lifetime']
Effective: On next login
Display: In Activity tab (formatted as "X hours Y minutes")
```

---

## Function Call Tree

```
admin/login.php
    │
    └─► recordAdminLogin()
        ├─► getAdminProfile()
        ├─► Increment login_count
        ├─► Update last_login timestamp
        └─► saveAdminProfile()

admin/edit-admin.php
    │
    ├─► POST action=update_profile
    │   └─► updateAdminProfileInfo()
    │       ├─► Validate username
    │       ├─► Validate email
    │       ├─► Validate full_name
    │       ├─► saveAdminProfile()
    │       └─► Return result
    │
    ├─► POST action=change_password
    │   └─► updateAdminPassword()
    │       ├─► Verify current password
    │       ├─► Validate new password
    │       ├─► Validate confirmation
    │       ├─► Hash new password
    │       ├─► saveAdminProfile()
    │       └─► Return result
    │
    ├─► POST action=update_session
    │   └─► updateSessionLifetime()
    │       ├─► Validate range (300-86400)
    │       ├─► saveAdminProfile()
    │       └─► Return result
    │
    └─► GET Display
        ├─► getAdminProfile()
        ├─► getAdminSessionInfo()
        ├─► formatAdminDate()
        ├─► formatSessionTime()
        └─► getFlashMessage()
```

---

## Validation Flowchart

### Username Validation
```
Input Username
    │
    ├─► Empty? ─► Error: "Required"
    │
    ├─► Length < 3? ─► Error: "3+ characters"
    │
    ├─► Invalid chars? ─► Error: "Alphanumeric/hyphen/underscore only"
    │
    └─► Valid ✓
        └─► Save
```

### Email Validation
```
Input Email
    │
    ├─► Empty? ─► Error: "Required"
    │
    ├─► Valid format? ─► Error: "Invalid email"
    │
    └─► Valid ✓
        └─► Save
```

### Password Validation
```
Input: Current, New, Confirm
    │
    ├─► Current matches? ─► Error: "Incorrect current password"
    │
    ├─► New length < 6? ─► Error: "6+ characters required"
    │
    ├─► New != Confirm? ─► Error: "Passwords don't match"
    │
    └─► Valid ✓
        ├─► Hash with BCRYPT
        └─► Save
```

### Session Timeout Validation
```
Input Seconds
    │
    ├─► < 300? ─► Error: "Minimum 5 minutes"
    │
    ├─► > 86400? ─► Error: "Maximum 24 hours"
    │
    └─► Valid ✓
        └─► Save
```

---

## Configuration Schema

```
config/admin.php
│
├─ Authentication
│  ├─ username (String, 3+ chars)
│  └─ password_hash (String, BCRYPT)
│
├─ Profile Info
│  ├─ email (String, valid email)
│  ├─ full_name (String, 2+ chars)
│  └─ admin_photo (String, file path)
│
├─ Session Management
│  └─ session_lifetime (Integer, seconds: 300-86400)
│
├─ Activity Tracking
│  ├─ login_count (Integer, counter)
│  ├─ last_login (Integer, Unix timestamp)
│  └─ created_date (Integer, Unix timestamp)
│
└─ Status
   └─ account_status (String: 'active'/'inactive')
```

---

## Error Response Structure

```
Function Returns
├─ Success Case
│  ├─ success: true
│  ├─ message: "Operation successful"
│  └─ errors: []
│
└─ Error Case
   ├─ success: false
   ├─ message: "Operation failed"
   └─ errors:
      ├─ field1: "Error message"
      ├─ field2: "Error message"
      └─ field3: "Error message"
```

---

## File Organization

```
portfolio/
│
├─ config/
│  └─ admin.php ◄─────────────────┐
│                                  │ Stores admin data
├─ admin/
│  │
│  ├─ login.php ◄─────────┬───────┤ Calls recordAdminLogin()
│  │                      │       │
│  ├─ index.php ◄─────────┼───────┤ Dashboard with card link
│  │                      │       │
│  ├─ edit-admin.php ◄────┼───────┤ Main profile UI
│  │                      │       │
│  ├─ includes/
│  │  ├─ functions.php ◄──┼───────┤ All helper functions
│  │  │                   │       │
│  │  ├─ header.php       │       │ Session management
│  │  │                   │       │
│  │  ├─ footer.php       │       │
│  │  │                   │       │
│  │  └─ media-picker.php └───────┤ Photo selection
│  │
│  └─ api/
│     ├─ assets.php
│     └─ upload.php
│
├─ data/
│  ├─ portfolio.json
│  ├─ update_log.txt ◄─────────────┤ Activity log
│  └─ backups/
│
└─ Documentation/
   ├─ ADMIN_PROFILE_SETUP.md ◄──────┤ Complete guide
   ├─ ADMIN_PROFILE_QUICK_REFERENCE.md ◄─ Quick start
   ├─ ADMIN_PROFILE_COMPLETION_REPORT.md ◄─ This Report
   └─ ADMIN_PROFILE_ARCHITECTURE.md ◄───────┐
                                            │ You are here
```

---

## Security Model

```
Authentication Layer
└─► Login Credentials (username + password)
    └─► BCRYPT Password Hash Verification
        └─► Session Created
            └─► recordAdminLogin() called
                └─► Activity Tracked

Authorization Layer
└─► Session Check in edit-admin.php
    └─► Only logged-in admins can access
        └─► config/admin.php protected
            └─► Only PHP can read

Validation Layer
└─► Input Validation (all fields)
    └─► Type Checking
        └─► Format Verification
            └─► Range Checking
                └─► Error Collection & Return
```

---

## Integration Points Summary

```
┌─────────────────────────────────────────────────────────────┐
│                   System Integration Map                     │
├─────────────────────────────────────────────────────────────┤
│                                                              │
│  1. admin/login.php                                         │
│     ├─ Includes: functions.php                             │
│     └─ Calls: recordAdminLogin()                           │
│                                                             │
│  2. admin/index.php                                         │
│     └─ Links: admin/edit-admin.php                         │
│                                                             │
│  3. admin/includes/functions.php                           │
│     ├─ Reads: config/admin.php                            │
│     └─ Updates: config/admin.php                          │
│                                                             │
│  4. admin/edit-admin.php                                   │
│     ├─ Includes: functions.php                            │
│     ├─ Includes: header.php (session)                     │
│     ├─ Includes: footer.php                               │
│     ├─ Uses: media-picker.php                             │
│     └─ Uses: getFlashMessage()                            │
│                                                             │
│  5. config/admin.php                                       │
│     ├─ Read by: all functions                             │
│     └─ Updated by: all functions                          │
│                                                             │
│  6. Session System                                         │
│     ├─ Started by: header.php                            │
│     ├─ Used by: flash messages                           │
│     └─ Updated by: recordAdminLogin()                   │
│                                                             │
└─────────────────────────────────────────────────────────────┘
```

---

## Usage Flow Diagram

```
User Journey:

1. Login
   admin/login.php → Validate credentials → recordAdminLogin() 
   → config/admin.php updated → Redirect to dashboard

2. View Dashboard
   admin/index.php → Display Admin Account card
   → Click "My Profile" button

3. Access Profile
   admin/edit-admin.php → Load profile data → Display 4 tabs
   → User selects tab

4. Update Profile
   Tab 1: Edit profile info → Submit → updateAdminProfileInfo()
   → Validate → Update config/admin.php → Success message

5. Change Password
   Tab 2: Enter passwords → Submit → updateAdminPassword()
   → Verify current → Validate new → Hash → Update → Success

6. Configure Session
   Tab 3: Select timeout → Submit → updateSessionLifetime()
   → Validate range → Update config/admin.php → Success

7. View Activity
   Tab 4: Display login history → Show recent activity log
   → Formatted display (read-only)

8. Logout
   Session ends → Next login increments counter
```

---

**Visual Architecture Complete**

This diagram shows how all components fit together in the admin profile system.

