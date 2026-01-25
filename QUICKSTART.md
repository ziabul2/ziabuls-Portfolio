# Hybrid Content System - Quick Start Guide

## 🚀 Quick Setup (3 Steps)

### Step 1: Test Database Connection
```bash
cd c:\xampp\htdocs\cv
php database\pdo_test.php
```

**Expected Output:**
```
Testing PDO...
PDO MySQL Available: YES
Attempting connection...
SUCCESS! Connected to database.
Query test: PASSED
```

---

### Step 2: Create Database Tables
```bash
php database\test_connection.php
```

**Expected Output:**
```
Testing Database Connection...
✓ Database connection successful!
✓ Connected to: ezyro_40986489_aboutblogs

Creating database schema...
✓ Executed: CREATE TABLE IF NOT EXISTS `blog_posts`...
✓ Database schema created successfully!
```

---

### Step 3: Migrate Existing Blog Posts
```bash
php database\migrate_blog_to_db.php
```

**Expected Output:**
```
=== Blog Migration Script ===
Found 2 blog post(s) to migrate

Creating backup before migration...
✓ Backup created: portfolio_2026-01-25_11-15-30.json

Migrating: Demo post (ID: 01)... ✓ SUCCESS
Migrating: 2nd post (ID: 2nd-post)... ✓ SUCCESS

✓ Database transaction committed
✓ Blog posts removed from JSON file

=== Migration Summary ===
Migrated: 2 post(s)
✓ Migration completed successfully!
```

---

## ✅ What's Now Working

### File-Based Storage (Unchanged - Still Works):
- ✅ `/admin/edit-profile.php` - Edit profile
- ✅ `/admin/edit-skills.php` - Edit skills
- ✅ `/admin/edit-contact.php` - Edit contact info
- ✅ `/admin/edit-seo.php` - Edit SEO settings
- ✅ `/admin/edit-projects.php` - Edit projects
- ✅ `/admin/manage-backups.php` - View/restore backups

**All automatic backups still work!**

### Database-Driven Blog (New):
- ✅ `/admin/manage-blog.php` - View all blog posts
- ✅ `/admin/edit-post.php` - Create/edit posts
- ✅ `/blog.php` - Public blog listing
- ✅ `/post.php?id={slug}` - Individual post view
- ✅ Homepage blog preview (latest 3 posts)

---

## 🧪 Testing Your Setup

### 1. Test Admin Login
```
1. Open browser: http://localhost/cv/admin/login.php
2. Login with your credentials
3. Verify you see dashboard
```

### 2. Test Blog Management
```
1. Navigate to "Manage Blog Posts"
2. Click "Create New Post"
3. Fill in:
   - Title: "Test Post"
   - Summary: "This is a test"
   - Content: "<h1>Hello World</h1><p>Test content</p>"
   - Status: Published
4. Click "Save Post"
5. Verify post appears in list
```

### 3. Test Frontend
```
1. Open: http://localhost/cv/blog.php
2. Verify test post appears
3. Click "Read More"
4. Verify post content displays correctly
```

### 4. Test Static Content (File-Based)
```
1. Edit Profile: http://localhost/cv/admin/edit-profile.php
2. Change name
3. Click Save
4. Navigate to: http://localhost/cv/admin/manage-backups.php
5. Verify new backup created
6. Test rollback by clicking "Restore" on older backup
```

---

## 📁 System Overview

```
Hybrid Content System
├─ STATIC CONTENT (File Storage)
│  ├─ data/portfolio.json (main file)
│  ├─ data/backups/ (automatic backups)
│  ├─ Managed by: FileStorageManager + BackupManager
│  └─ Content: Profile, Skills, Projects, Contact, SEO
│
└─ DYNAMIC CONTENT (Database)
   ├─ MySQL: ezyro_40986489_aboutblogs
   ├─ Tables: blog_posts, media_library, blog_categories
   ├─ Managed by: DatabaseManager
   └─ Content: Blog Posts, Media Files
```

---

## 🔧 Troubleshooting

### Issue: "PDO MySQL extension not available"
**Solution:**
1. Open `php.ini` file
2. Find `;extension=pdo_mysql`
3. Remove the semicolon: `extension=pdo_mysql`
4. Restart Apache/XAMPP

### Issue: "Database connection failed"
**Solutions:**
1. Verify internet connection (remote MySQL host)
2. Check firewall allows port 3306
3. Verify credentials in `config/database.php`

### Issue: "Migration shows 0 posts"
**Explanation:** Blog posts already migrated or `blog_posts` array empty in portfolio.json
**Action:** Skip migration, create posts via admin panel

### Issue: "Blog page shows no posts"
**Check:**
1. Are posts set to "published" status?
2. Run: `php database/pdo_test.php` to verify DB connection
3. Check error logs in browser console

---

## 📚 Important Files

### Configuration:
- `config/database.php` - MySQL credentials
- `config/admin.php` - Admin login credentials
- `config/security.php` - Security functions

### Managers:
- `helpers/DatabaseManager.php` - Database operations
- `helpers/FileStorageManager.php` - File operations
- `helpers/BackupManager.php` - Backup operations

### Database:
- `database/schema.sql` - Table structures
- `database/migrate_blog_to_db.php` - Migration script
- `database/pdo_test.php` - Connection test

---

## 🎯 Next Steps

1. **Run migration** (if not done)
2. **Create a test blog post** via admin
3. **Verify blog displays** on frontend
4. **Optional:** Add rich text editor (TinyMCE)
5. **Optional:** Build media library manager
6. **Optional:** Implement categories

---

## 💡 Tips

- **CSRF Protection:** All admin forms now have CSRF tokens
- **Session Timeout:** Auto-logout after 1 hour of inactivity
- **Automatic Backups:** Every static content save creates a backup
- **Database Security:** All queries use prepared statements
- **Atomic Writes:** File operations are atomic (no partial corruption)

**Your existing setup is completely intact - nothing was broken!** ✨
