# Admin Workflow: Clean Portfolio Content Update System

## Overview
This document defines a clean, atomic admin workflow for updating portfolio content where changes are immediately reflected on the frontend without page reload inconsistencies or partial updates.

## Architecture Principles

### 1. **Atomic Operations**
- All data changes happen in a single transaction
- Either everything succeeds or nothing changes
- No partial states possible

### 2. **Data Integrity**
- Automatic backups before any write
- Validation before persistence
- Rollback capability on failure

### 3. **Cache Management**
- Frontend reads from single JSON source
- No stale data issues
- Changes immediately visible

### 4. **User Experience**
- Clear validation feedback
- Confirmation messages
- Error recovery options

---

## Workflow Phases

### Phase 1: Data Loading
**When:** Admin page loads or user navigates to edit section
**Action:** Load current portfolio.json data

```
GET /admin/api/read.php?section=hero
```

**Returns:**
```json
{
  "success": true,
  "data": { /* current hero section */ },
  "timestamp": 1234567890,
  "version": "1.0"
}
```

**Guarantees:**
- Always read fresh from disk
- No stale cached data
- Atomic read (file lock if needed)

---

### Phase 2: User Editing
**When:** Admin modifies form fields
**Action:** Collect form input without validation yet

**What happens:**
1. User fills form fields
2. Client-side preview updates (optional, for rich content)
3. Form data collected but not saved

**Key Points:**
- Form validation happens BEFORE submission
- No database writes yet
- User can abandon without side effects

---

### Phase 3: Validation
**When:** User clicks "Save"
**Action:** Multi-layer validation

**Server-Side Validation:**
```php
// In /admin/api/validate.php
- Data type checking (string, array, integer, etc.)
- Required field checking
- URL format validation
- Image path validation
- Special character sanitization
- Array structure validation (if nested)
```

**Client-Side Validation (optional):**
```javascript
- Real-time field validation
- Format checking
- Length limits
```

**Process:**
1. Send form data to `/admin/api/validate.php`
2. Server validates each field
3. Return validation errors or "ready to save"
4. If errors: Display to user, allow corrections
5. If valid: Proceed to Phase 4

**Validation Response:**
```json
{
  "valid": true,
  "errors": {},
  "timestamp": 1234567890
}
```

Or if invalid:
```json
{
  "valid": false,
  "errors": {
    "hero_name": "Name is required",
    "hero_image": "Image path must point to assets folder"
  },
  "timestamp": 1234567890
}
```

---

### Phase 4: Backup Creation
**When:** Validation passes, user confirms save
**Action:** Create timestamped backup of current portfolio.json

**Backup Details:**
- Filename: `portfolio_YYYY-MM-DD_HH-mm-ss.json`
- Location: `/data/backups/`
- Triggered BEFORE any write to main file
- Automatic using BackupManager::createBackup()

**Purpose:**
- Rollback safety net
- Audit trail
- Disaster recovery

**Guarantees:**
- Backup created successfully before main file touched
- If backup fails, entire operation aborts
- User notified if backup creation fails

---

### Phase 5: Overwrite Execution
**When:** Backup complete
**Action:** Atomically merge new data with existing portfolio.json

**Merge Strategy:**
1. Load entire current portfolio.json into memory
2. Update only the target section (hero, skills, projects, etc.)
3. Validate final merged data structure
4. Write to disk using atomic file operation

**Atomic Write Process:**
```php
// Using BackupManager::saveSafely()
1. Create temporary file with new data
2. Verify file write success
3. Atomic rename: temp file → portfolio.json
4. Verify file exists and is readable
```

**Key Points:**
- No partial writes possible
- No corruption if write interrupted
- Original file preserved if write fails
- Uses file locks if necessary (Windows)

**Return Value:**
```json
{
  "success": true,
  "message": "Hero section updated successfully",
  "section": "hero",
  "backup_file": "portfolio_2026-01-22_14-30-45.json",
  "timestamp": 1234567890
}
```

---

### Phase 6: Confirmation Feedback
**When:** Data successfully written to disk
**Action:** Notify user and update UI

**Feedback Types:**

**1. Success Message**
```
✓ Hero section updated successfully
Backup created: portfolio_2026-01-22_14-30-45.json
```

**2. Frontend Update**
- Display updated data from portfolio.json
- Clear form or redirect to dashboard
- Flash message appears on next page load

**3. Activity Log**
- Record update in security logs
- Track: who updated, what changed, when, backup location

**4. Optional: Real-time Sync**
- If AJAX: Update preview in real-time
- If Form: Redirect to success page, display backup info

---

## Request Flow Diagram

```
User Submits Form
    ↓
[Phase 1] Load Current Data
    ↓
[Phase 2] Collect User Input
    ↓
[Phase 3] Client-Side Validation
    ↓ (Valid)
Send to Server
    ↓
[Phase 3] Server-Side Validation
    ↓ (Valid)
[Phase 4] Create Backup
    ↓ (Success)
[Phase 5] Merge & Write Atomically
    ↓ (Success)
[Phase 6] Return Success + Log Activity
    ↓
Update Frontend Display
    ↓
✓ Done (User sees confirmation)
```

---

## Critical Implementation Details

### 1. **No Page Reload Issues**

**Problem:** If page reloads during save, data could be partially written

**Solution:**
- All writes are atomic (all or nothing)
- User cannot refresh until response received
- Form locked during save operation
- Server returns new data in response (no need to re-fetch)

**Code Example:**
```php
// In save endpoint
function saveSection($section, $data) {
    // 1. Validate
    if (!validate($data)) {
        return error('Validation failed');
    }
    
    // 2. Load current full data
    $current = getPortfolioData();
    
    // 3. Update section
    $current[$section] = $data;
    
    // 4. Backup current
    $backupMgr = new BackupManager(...);
    $backup = $backupMgr->createBackup();
    if (!$backup) {
        return error('Backup failed');
    }
    
    // 5. Write new data atomically
    if (!$backupMgr->saveSafely($current)) {
        return error('Save failed');
    }
    
    // 6. Verify write
    $verify = getPortfolioData();
    if ($verify[$section] !== $data) {
        return error('Verification failed');
    }
    
    return success($verify, $backup);
}
```

### 2. **Validation Rules**

**Standard Field Rules:**
```php
'hero_name' => ['required', 'string', 'max:100'],
'hero_image' => ['required', 'starts_with:assets/'],
'hero_description' => ['required', 'string', 'max:500'],
'social_url' => ['required', 'url'],
'project_link' => ['url', 'nullable'],
```

**Custom Rules:**
```php
'date_field' => [
    'regex:/^\d{4}-\d{2}-\d{2}$/',
    'date_format:Y-m-d'
],
'file_path' => [
    'required',
    'not_contains:../',
    'starts_with:assets/ or data/'
]
```

### 3. **Error Handling**

**Validation Errors** → Display to user, allow retry
**Backup Errors** → Abort entire operation, alert user
**Write Errors** → Abort, show error, suggest manual recovery
**Verification Errors** → Severe: Rollback from backup, alert admin

### 4. **Recovery Procedures**

**If Save Fails:**
1. User sees error message
2. User can retry immediately (no data loss)
3. Admin has access to restore previous backup

**If Write Interrupted:**
1. Original portfolio.json untouched
2. Backup file exists for recovery
3. User can try again or contact admin

---

## API Endpoints

### GET /admin/api/read.php?section=SECTION
**Purpose:** Load current data for a section
**Returns:** JSON with current data and metadata

### POST /admin/api/validate.php
**Purpose:** Validate form data before save
**Input:** form data
**Returns:** Validation result with errors if any

### POST /admin/api/save.php
**Purpose:** Save data atomically with backup
**Input:** section + validated data
**Returns:** Success/failure with backup info

### GET /admin/api/backup-info.php
**Purpose:** Get list of available backups
**Returns:** Array of backup files with metadata

---

## Frontend Form Template

```html
<form id="edit-form" method="POST" action="/admin/api/save.php">
    <!-- Hidden field for section name -->
    <input type="hidden" name="section" value="hero">
    
    <!-- Form fields -->
    <div class="form-group">
        <label for="hero_name">Name *</label>
        <input 
            type="text" 
            id="hero_name" 
            name="hero_name"
            required
            data-validate="required|string|max:100"
        >
        <span class="error-msg" data-field="hero_name"></span>
    </div>
    
    <!-- More fields... -->
    
    <!-- Submit controls -->
    <div class="form-actions">
        <button type="submit" id="save-btn" class="btn-primary">Save Changes</button>
        <button type="reset" id="reset-btn" class="btn-secondary">Reset Form</button>
        <a href="index.php" class="btn-secondary">Cancel</a>
    </div>
    
    <!-- Status messages -->
    <div id="status-message" class="hidden"></div>
</form>

<script>
// Prevent form submission during save
document.getElementById('edit-form').addEventListener('submit', async (e) => {
    e.preventDefault();
    
    // 1. Client-side validation
    const errors = validateForm();
    if (Object.keys(errors).length > 0) {
        displayErrors(errors);
        return;
    }
    
    // 2. Disable submit button
    document.getElementById('save-btn').disabled = true;
    document.getElementById('save-btn').textContent = 'Saving...';
    
    try {
        // 3. Send to server
        const response = await fetch('/admin/api/save.php', {
            method: 'POST',
            body: new FormData(e.target)
        });
        
        const result = await response.json();
        
        if (result.success) {
            showSuccess(result.message);
            // Redirect after 2 seconds
            setTimeout(() => {
                window.location.href = 'index.php';
            }, 2000);
        } else {
            showError(result.error);
        }
    } catch (err) {
        showError('Network error: ' + err.message);
    } finally {
        // 4. Re-enable button
        document.getElementById('save-btn').disabled = false;
        document.getElementById('save-btn').textContent = 'Save Changes';
    }
});
</script>
```

---

## Checklist for New Admin Features

When adding a new editable section (Skills, Projects, etc.), ensure:

- [ ] Section has dedicated edit-SECTION.php file
- [ ] Data loading uses getPortfolioData()
- [ ] Form includes all required fields
- [ ] Server-side validation for all fields
- [ ] Backup created before write
- [ ] Atomic write using BackupManager::saveSafely()
- [ ] Verification after write completes
- [ ] User feedback on success/failure
- [ ] Form locked during save operation
- [ ] Error recovery instructions shown
- [ ] Changes immediately visible on frontend

---

## Testing Procedure

### Test 1: Normal Save Flow
1. Edit a field
2. Click save
3. Verify backup created
4. Verify data written
5. Refresh page
6. Verify data persisted

### Test 2: Validation Error
1. Enter invalid data
2. Click save
3. Verify error message shown
4. Verify no backup created
5. Verify no data changed
6. Fix and retry

### Test 3: Network Interrupt (simulated)
1. Edit field
2. Click save
3. Kill server before save completes
4. Verify form still shows old data
5. Restart server
6. Verify original data intact
7. Retry save

### Test 4: Concurrent Edits
1. Open edit-SECTION.php in two tabs
2. Edit in tab 1, save
3. Edit in tab 2, save
4. Verify tab 2 gets latest data from tab 1
5. Manual merge or "reload" button to get tab 1 changes

---

## Summary

**Key Guarantees:**
- ✓ All writes atomic (no partial updates)
- ✓ Backup created before any write
- ✓ Validation before persistence
- ✓ Changes immediately visible on frontend
- ✓ No page reload inconsistencies
- ✓ Rollback capability on failure
- ✓ Activity audit trail

**Result:** Users can confidently edit portfolio content knowing changes will either fully succeed or fully fail, with automatic backup protection and immediate frontend reflection.
