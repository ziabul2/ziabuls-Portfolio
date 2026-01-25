# Admin Workflow Implementation Guide

## Overview
This guide explains how to integrate the clean admin workflow into your existing portfolio edit pages to ensure atomic updates with no inconsistencies.

## Files Added/Created

1. **ADMIN_WORKFLOW.md** - Complete workflow documentation
2. **helpers/AtomicUpdateController.php** - Core update logic with validation
3. **admin/api/save.php** - Unified save endpoint
4. **admin/api/read.php** - Load section data endpoint
5. **admin/api/validate.php** - Server-side validation endpoint
6. **admin/js/workflow.js** - Client-side workflow manager

## Quick Start (5 Steps)

### Step 1: Update Edit Page Header

For each edit page (e.g., `edit-profile.php`, `edit-skills.php`), update the script include:

```php
<?php
// At the top of edit-*.php file
require_once __DIR__ . '/includes/header.php';

// Remove old getPortfolioData() calls - API will handle it
// Or keep for backward compatibility:
$data = getPortfolioData();
```

### Step 2: Update Form Structure

Ensure form has `id="edit-form"` and `data-section` attribute:

```html
<form id="edit-form" method="POST" data-section="hero">
    <!-- Form fields -->
    
    <!-- Status messages -->
    <div id="status-message" class="hidden"></div>
    
    <!-- Submit buttons -->
    <button type="submit" class="btn-primary">Save Changes</button>
    <button type="reset" class="btn-secondary">Reset Form</button>
    <a href="index.php" class="btn-secondary">Cancel</a>
</form>
```

### Step 3: Add Validation Attributes

Add `data-validate` to important fields:

```html
<input 
    type="text" 
    id="hero_name" 
    name="hero_name"
    required
    data-validate="required|string|max:100"
    placeholder="Full name"
>

<textarea 
    id="hero_desc" 
    name="hero_desc" 
    required
    data-validate="required|max:500"
></textarea>
```

### Step 4: Add JavaScript Include

In the footer or before closing body tag:

```html
<script src="js/workflow.js"></script>
<script>
    // Initialize workflow for this page
    document.addEventListener('DOMContentLoaded', () => {
        const updater = new PortfolioUpdater('hero'); // Use section name
        updater.init();
    });
</script>
```

### Step 5: Add CSS for Error Display

Add to `admin/css/admin-style.css`:

```css
/* Error states */
input.has-error,
textarea.has-error,
select.has-error {
    border-color: #e74c3c;
    background-color: #fadbd8;
}

/* Error message */
.error-msg {
    color: #e74c3c;
    font-size: 0.85em;
    margin-top: 5px;
    display: block;
}

.error-msg.hidden {
    display: none;
}

/* Status messages */
#status-message {
    padding: 15px;
    margin: 15px 0;
    border-radius: 4px;
    border-left: 4px solid;
    font-weight: 500;
}

#status-message.success-msg {
    background-color: #d4edda;
    border-color: #28a745;
    color: #155724;
}

#status-message.error-msg {
    background-color: #f8d7da;
    border-color: #e74c3c;
    color: #721c24;
}

#status-message.hidden {
    display: none;
}
```

## Complete Example: Edit Profile Page

Here's a complete example of integrating the workflow into `edit-profile.php`:

```php
<?php
require_once __DIR__ . '/includes/header.php';

// Load current data for display
$data = getPortfolioData();
$flash = getFlashMessage();
?>

<div class="section-header">
    <h1>Edit Profile & About</h1>
    <a href="index.php" class="btn-edit"><i class="fas fa-arrow-left"></i> Back</a>
</div>

<!-- Flash message (if page reload) -->
<?php if ($flash): ?>
    <div class="<?php echo htmlspecialchars($flash['type']); ?>-msg">
        <?php echo htmlspecialchars($flash['message']); ?>
        <?php if (isset($flash['backup'])): ?>
            <br><small>Backup: <?php echo htmlspecialchars($flash['backup']); ?></small>
        <?php endif; ?>
    </div>
<?php endif; ?>

<!-- Main form with workflow -->
<form id="edit-form" method="POST" data-section="hero" data-action="api/save.php">
    
    <div class="editor-card">
        <h2>Hero Section</h2>
        
        <div class="form-group">
            <label for="hero_name">Display Name *</label>
            <input 
                type="text" 
                id="hero_name" 
                name="hero_name" 
                value="<?php echo htmlspecialchars($data['hero']['name'] ?? ''); ?>"
                required
                data-validate="required|max:100"
                placeholder="Your display name"
            >
            <span class="error-msg" data-field="hero_name"></span>
        </div>
        
        <div class="form-group">
            <label for="hero_image">Hero Image *</label>
            <img id="hero_img_preview" 
                src="../<?php echo htmlspecialchars($data['hero']['image'] ?? ''); ?>" 
                class="image-preview" 
                alt="Hero image preview">
            <input 
                type="text" 
                id="hero_image" 
                name="hero_image" 
                value="<?php echo htmlspecialchars($data['hero']['image'] ?? ''); ?>"
                required
                data-validate="required"
                readonly 
                style="background:#111;"
            >
            <button type="button" class="btn-edit" onclick="openMediaPicker('hero_image', 'hero_img_preview')">
                Change Image
            </button>
            <span class="error-msg" data-field="hero_image"></span>
        </div>
        
        <div class="form-group">
            <label for="hero_desc">Short Description *</label>
            <textarea 
                id="hero_desc" 
                name="hero_desc" 
                required
                data-validate="required|max:500"
                placeholder="Brief description of yourself"
            ><?php echo htmlspecialchars($data['hero']['description'] ?? ''); ?></textarea>
            <span class="error-msg" data-field="hero_desc"></span>
            <small><span id="hero_desc_count">0</span>/500 characters</small>
        </div>
        
        <div class="form-group">
            <label for="hero_quote">Quote</label>
            <input 
                type="text" 
                id="hero_quote" 
                name="hero_quote" 
                value="<?php echo htmlspecialchars($data['hero']['quote'] ?? ''); ?>"
                data-validate="max:200"
                placeholder="Your favorite quote"
            >
            <span class="error-msg" data-field="hero_quote"></span>
        </div>
        
        <div class="form-group">
            <label for="hero_author">Quote Author</label>
            <input 
                type="text" 
                id="hero_author" 
                name="hero_author" 
                value="<?php echo htmlspecialchars($data['hero']['quote_author'] ?? ''); ?>"
                placeholder="Author of the quote"
            >
            <span class="error-msg" data-field="hero_author"></span>
        </div>
    </div>
    
    <!-- Status message area -->
    <div id="status-message" class="hidden"></div>
    
    <!-- Form actions -->
    <div class="form-actions" style="margin-top: 30px; display: flex; gap: 10px;">
        <button type="submit" id="save-btn" class="btn-primary">
            <i class="fas fa-save"></i> Save Changes
        </button>
        <button type="reset" id="reset-btn" class="btn-secondary">
            <i class="fas fa-redo"></i> Reset Form
        </button>
        <a href="index.php" class="btn-secondary" style="text-decoration: none; display: inline-flex; align-items: center; gap: 5px;">
            <i class="fas fa-times"></i> Cancel
        </a>
    </div>
</form>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

<!-- JavaScript for workflow -->
<script src="js/workflow.js"></script>
<script>
    // Character counter
    const descField = document.getElementById('hero_desc');
    const descCount = document.getElementById('hero_desc_count');
    
    if (descField && descCount) {
        descField.addEventListener('input', () => {
            descCount.textContent = descField.value.length;
        });
        // Set initial count
        descCount.textContent = descField.value.length;
    }
</script>
```

## API Usage Examples

### JavaScript: Load Section Data

```javascript
// Load current section data
fetch('/admin/api/read.php?section=hero')
    .then(r => r.json())
    .then(data => {
        console.log('Current hero section:', data.data);
    });
```

### JavaScript: Validate Before Save

```javascript
// Validate data
const formData = new FormData();
formData.append('section', 'hero');
formData.append('data', JSON.stringify({
    hero_name: 'John Doe',
    hero_image: 'assets/photo.jpg'
}));

fetch('/admin/api/validate.php', { method: 'POST', body: formData })
    .then(r => r.json())
    .then(result => {
        if (result.valid) {
            console.log('Valid!');
        } else {
            console.log('Errors:', result.errors);
        }
    });
```

### JavaScript: Save with Automatic Backup

```javascript
// Save data atomically
const formData = new FormData();
formData.append('section', 'hero');
formData.append('hero_name', 'John Doe');
formData.append('hero_image', 'assets/photo.jpg');

fetch('/admin/api/save.php', { method: 'POST', body: formData })
    .then(r => r.json())
    .then(result => {
        if (result.success) {
            console.log('Saved! Backup:', result.backup_file);
        } else {
            console.log('Error:', result.error);
        }
    });
```

### PHP: Direct Controller Usage

```php
<?php
require_once 'helpers/AtomicUpdateController.php';

$controller = new AtomicUpdateController();

// Single update
$result = $controller->update('hero', [
    'name' => 'John Doe',
    'image' => 'assets/photo.jpg',
    'description' => 'A developer...',
    'roles' => ['Developer', 'Designer']
]);

// Check result
if ($result['success']) {
    echo "Saved! Backup: " . $result['backup_file'];
} else {
    echo "Error: " . $result['error'];
}
```

## Migration from Old System

If you have existing edit pages using the old form submission method:

### Before (Old):
```php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = getPortfolioData();
    $data['hero']['name'] = $_POST['hero_name'];
    savePortfolioData($data);
    header('Location: edit-profile.php');
}
```

### After (New):
```html
<form id="edit-form" data-section="hero" method="POST">
    <input type="text" name="hero_name" value="...">
    <button type="submit">Save</button>
    <div id="status-message"></div>
</form>
<script src="js/workflow.js"></script>
```

The workflow.js handles everything! No server-side PHP processing needed in the form handler.

## Validation Rule Format

### Built-in Validation Rules

```
required      - Field must have a value
email         - Field must be valid email
url           - Field must be valid URL
max:N         - Maximum N characters
min:N         - Minimum N characters
pattern:REGEX - Must match regex pattern
```

### Usage in HTML

```html
<!-- Single rule -->
<input data-validate="required">

<!-- Multiple rules -->
<input data-validate="required|email|max:100">

<!-- With parameters -->
<textarea data-validate="required|max:500"></textarea>
```

## Troubleshooting

### Issue: Form still submits normally

**Solution:** Make sure `id="edit-form"` is present and `js/workflow.js` is included

### Issue: Validation doesn't work

**Solution:** Check browser console for errors. Ensure data attributes are set correctly:
```html
<input name="hero_name" required data-validate="required|max:100">
```

### Issue: Save fails with "Verification failed"

**Solution:** Check `data/portfolio.json` permissions. Make sure it's writable:
```bash
chmod 644 data/portfolio.json
chmod 755 data/
```

### Issue: Backups not created

**Solution:** Check `data/backups/` folder exists and is writable:
```bash
chmod 755 data/backups/
```

## Testing the Integration

1. **Edit a field** - Change value in form
2. **Click Save** - Should validate, backup, and save atomically
3. **Refresh page** - Should show updated value
4. **Check backup** - File should exist in `data/backups/`
5. **Try invalid input** - Should show error without saving
6. **Test network interrupt** - Kill server, restart, verify no partial data

## Next Steps

1. Update all edit-*.php pages with new form structure
2. Add validation attributes to all important fields
3. Test each page thoroughly
4. Monitor backup directory to ensure backups are created
5. Document any custom validation rules needed

---

**Result:** Atomic, validated, backed-up portfolio updates with instant frontend reflection!
