# Database Management Admin Panel - Documentation

## Overview

The Database Management Admin Panel provides comprehensive control over your MySQL database directly from the admin interface.

## Features

### 1. Database Information Dashboard
**URL:** `/admin/manage-database.php`

**Displays:**
- **Connection Status** - Real-time connection indicator
- **Host & Database** - Server and database name
- **Username** - Database user
- **Total Tables** - Count of all tables
- **Database Size** - Total storage used
- **Table List** - Clickable grid of all tables

**Table Viewer:**
- Click any table to view its structure and data
- Shows table structure (fields, types, keys, defaults)
- Displays table data (up to 50 rows per page)
- Pagination for large tables
- Export individual tables as SQL

---

### 2. SQL Query Runner
**URL:** `/admin/manage-database-query.php`

**Features:**
- Execute custom SQL queries
- Sample queries for common operations
- Results displayed in formatted table
- Execution time tracking
- Safety confirmation for dangerous queries (DROP, DELETE, TRUNCATE)

**Sample Queries Provided:**
```sql
SELECT * FROM blog_posts LIMIT 10
SELECT * FROM blog_posts WHERE status = 'published' ORDER BY created_at DESC
SELECT COUNT(*) as total_posts FROM blog_posts
SELECT status, COUNT(*) as count FROM blog_posts GROUP BY status
SHOW TABLES
DESCRIBE blog_posts
```

---

### 3. Advanced Database Controls

#### Test Connection
- Verifies database connectivity
- Shows success/failure message

#### Optimize Tables
- Optimizes all database tables
- Reduces fragmentation
- Improves query performance

#### Backup Database
- Creates full SQL dump of all tables
- Saves to `/database/backups/` directory
- Timestamped filenames: `backup_YYYY-MM-DD_HH-MM-SS.sql`

#### Export Tables
- **Single Table:** Export individual table with structure and data
- **All Tables:** Export complete database as SQL dump
- Downloads as `.sql` file

#### Import SQL
- Upload and execute SQL files
- Restore from backups

---

## Usage Examples

### Viewing Table Data

1. Navigate to `/admin/manage-database.php`
2. Click on any table (e.g., `blog_posts`)
3. View table structure and data
4. Use pagination if table has many rows
5. Click "Export" to download table as SQL

### Running SQL Queries

1. Navigate to `/admin/manage-database-query.php`
2. Enter your SQL query or click a sample query
3. Check "Confirm" checkbox for safety
4. Click "Execute Query"
5. View results below

### Backing Up Database

1. Navigate to `/admin/manage-database.php`
2. Scroll to "Advanced Database Controls"
3. Click "Backup Database"
4. Backup file created in `/database/backups/`
5. Success message shows filename

### Exporting Tables

**Single Table:**
1. Navigate to a table view
2. Click "Export" button
3. SQL file downloads automatically

**All Tables:**
1. Navigate to Advanced Controls
2. Click "Export All Tables"
3. Complete database SQL downloads

---

## Security Features

- ✅ **Authentication Required** - All database management pages require admin login
- ✅ **Read-Only by Default** - Viewing data is safe
- ✅ **Confirmation for Dangerous Operations** - DELETE, DROP, TRUNCATE require confirmation
- ✅ **Prepared Statements** - All queries use PDO prepared statements
- ✅ **Error Handling** - Database errors are caught and safely displayed

---

## Files Created

| File | Purpose |
|------|---------|
| `admin/manage-database.php` | Main database management dashboard |
| `admin/manage-database-query.php` | SQL query runner interface |
| `admin/database-export.php` | Table/database export utility |
| `admin/database-actions.php` | Handler for test, optimize, backup actions |

---

## Accessing the Database Manager

1. **From Admin Dashboard:**
   - Login to admin panel
   - Find "Database Manager" card (highlighted in purple/green gradient)
   - Click "Manage Database" or "SQL Query"

2. **Direct URL:**
   - Database Dashboard: `http://localhost/cv/admin/manage-database.php`
   - SQL Query Runner: `http://localhost/cv/admin/manage-database-query.php`

---

## Tips & Best Practices

### Before Running Queries:
- Always backup your database first
- Test queries on a development environment
- Use SELECT queries to preview data before UPDATE/DELETE

### Regular Maintenance:
- Run "Optimize Tables" monthly for better performance
- Create backups before major changes
- Export tables you're about to modify

### Managing Large Tables:
- Use LIMIT in queries to avoid timeouts
- Export large tables during off-peak hours
- Use pagination in table viewer

---

## Troubleshooting

### "Database connection failed"
- Check `config/database.php` credentials
- Verify internet connection (remote MySQL host)
- Ensure PDO MySQL extension enabled

### "No tables shown"
- Verify database has tables
- Check database name in config
- Run migration: `php database/test_connection.php`

### "Query execution failed"
- Check SQL syntax
- Verify table/column names exist
- Review error message for details

---

## Database Structure Reference

### Current Tables

**blog_posts:**
- id (VARCHAR(50), PRIMARY KEY)
- title (VARCHAR(255))
- summary (TEXT)
- content (LONGTEXT)
- image (VARCHAR(255))
- status (ENUM: 'draft', 'published')
- created_at (DATETIME)
- updated_at (DATETIME)

**media_library:**
- id (INT, AUTO_INCREMENT, PRIMARY KEY)
- filename (VARCHAR(255), UNIQUE)
- filepath (VARCHAR(255))
- filetype (VARCHAR(50))
- filesize (BIGINT)
- uploaded_at (DATETIME)

**blog_categories:**
- id (INT, AUTO_INCREMENT, PRIMARY KEY)
- name (VARCHAR(100), UNIQUE)
- slug (VARCHAR(100), UNIQUE)
- description (TEXT)

**test:** (Your custom table)
- *Structure varies based on your creation*

---

## Next Steps

1. ✅ Database management panel is ready to use
2. ✅ Test connection to verify everything works
3. ✅ View your "test" table to see structure and data
4. ✅ Try sample SQL queries
5. ✅ Create a backup for safety

**Ready to manage your database with full control!** 🎉
