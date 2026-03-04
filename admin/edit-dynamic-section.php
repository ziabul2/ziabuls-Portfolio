<?php
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/functions.php';

$sectionId = $_GET['id'] ?? '';
$sectionsFile = __DIR__ . '/../data/home_sections.json';
$sections = json_decode(file_get_contents($sectionsFile), true);

$targetSection = null;
foreach ($sections as $section) {
    if ($section['id'] === $sectionId) {
        $targetSection = $section;
        break;
    }
}

if (!$targetSection || $targetSection['type'] !== 'dynamic_list') {
    header('Location: manage-sections.php');
    exit;
}

$dataFile = __DIR__ . '/../' . $targetSection['data_file'];
$items = [];
if (file_exists($dataFile)) {
    $items = json_decode(file_get_contents($dataFile), true);
}

$flash = getFlashMessage();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
        setFlashMessage('Invalid request token.', 'error');
        header('Location: edit-dynamic-section.php?id=' . $sectionId);
        exit;
    }

    $action = $_POST['action'] ?? '';
    if ($action === 'save_item') {
        $index = $_POST['index'] ?? -1;
        $newItem = [
            'org' => sanitizeInput($_POST['org']),
            'period' => sanitizeInput($_POST['period']),
            'role' => sanitizeInput($_POST['role'] ?? ''),
            'degree' => sanitizeInput($_POST['degree'] ?? ''),
            'details' => sanitizeInput($_POST['details'] ?? ''),
            'points' => array_filter(array_map('sanitizeInput', $_POST['points'] ?? []))
        ];
        
        // Remove empty role/degree/details keys
        if (empty($newItem['role'])) unset($newItem['role']);
        if (empty($newItem['degree'])) unset($newItem['degree']);
        if (empty($newItem['details'])) unset($newItem['details']);

        if ($index >= 0 && isset($items[$index])) {
            $items[$index] = $newItem;
        } else {
            array_unshift($items, $newItem); // Insert at top (Newest First)
        }
    } elseif ($action === 'delete_item') {
        $index = $_POST['index'] ?? -1;
        if (isset($items[$index])) {
            array_splice($items, $index, 1);
        }
    }

    file_put_contents($dataFile, json_encode($items, JSON_PRETTY_PRINT));
    setFlashMessage('Section content updated successfully!');
    header('Location: edit-dynamic-section.php?id=' . $sectionId);
    exit;
}
?>

<div class="section-header">
    <h1>Edit <?php echo htmlspecialchars($targetSection['title']); ?></h1>
    <a href="manage-sections.php" class="btn-edit"><i class="fas fa-arrow-left"></i> Back to Sections</a>
</div>

<?php if ($flash): ?>
    <div class="<?php echo $flash['type']; ?>-msg"><?php echo $flash['message']; ?></div>
<?php endif; ?>

<div class="row">
    <div class="col-md-7">
        <div class="editor-card">
            <h3>Items List</h3>
            <?php if (empty($items)): ?>
                <p style="color:#666; text-align:center; padding: 40px;">No items found. Add one on the right.</p>
            <?php else: ?>
                <?php foreach ($items as $index => $item): ?>
                <div class="audit-table-row" style="background: rgba(255,255,255,0.02); border: 1px solid #333; padding: 15px; border-radius: 8px; margin-bottom: 15px; display: flex; justify-content: space-between; align-items: flex-start;">
                    <div>
                        <h4 style="margin:0; color:var(--primary-color);"><?php echo htmlspecialchars($item['org']); ?></h4>
                        <div style="font-size:0.9rem; color:#fff; margin:5px 0;"><?php echo htmlspecialchars($item['role'] ?? $item['degree'] ?? 'Item'); ?></div>
                        <div style="font-size:0.8rem; color:#666;"><?php echo htmlspecialchars($item['period']); ?></div>
                    </div>
                    <div style="display:flex; gap:10px;">
                        <button class="btn-edit btn-sm" onclick="editItem(<?php echo $index; ?>, <?php echo htmlspecialchars(json_encode($item)); ?>)">
                            <i class="fas fa-pencil-alt"></i>
                        </button>
                        <form method="POST" onsubmit="return confirm('Are you sure?')">
                            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                            <input type="hidden" name="action" value="delete_item">
                            <input type="hidden" name="index" value="<?php echo $index; ?>">
                            <button type="submit" class="btn-login btn-sm" style="background:var(--error-color); width: auto;">
                                <i class="fas fa-trash"></i>
                            </button>
                        </form>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="col-md-5">
        <div class="editor-card" id="form-card">
            <h3 id="form-title">Add New Item</h3>
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                <input type="hidden" name="action" value="save_item">
                <input type="hidden" name="index" id="item-index" value="-1">
                
                <div class="form-group">
                    <label>Organization / Institution</label>
                    <input type="text" name="org" id="item-org" required placeholder="e.g. Rangpur High School">
                </div>
                
                <div class="form-group">
                    <label>Period / Duration</label>
                    <input type="text" name="period" id="item-period" required placeholder="e.g. 2023 - Present">
                </div>

                <div class="form-group">
                    <label>Role / Degree</label>
                    <input type="text" name="role" id="item-role" placeholder="Leave empty if not applicable">
                </div>

                <div class="form-group">
                    <label>Details / Summary</label>
                    <textarea name="details" id="item-details" rows="3" placeholder="Additional info..."></textarea>
                </div>

                <div class="form-group">
                    <label>Bullet Points (for Experience)</label>
                    <div id="points-container">
                        <div style="display:flex; gap:10px; margin-bottom:10px;">
                            <input type="text" name="points[]" class="point-input" placeholder="Point 1">
                            <button type="button" class="btn-edit" onclick="removePoint(this)" style="width: auto;"><i class="fas fa-times"></i></button>
                        </div>
                    </div>
                    <button type="button" class="btn-edit" onclick="addPoint()" style="width: 100%; margin-top: 5px;">
                        <i class="fas fa-plus"></i> Add Point
                    </button>
                </div>

                <div style="display:flex; gap:10px; margin-top:20px;">
                    <button type="submit" class="btn-login" style="flex:1;">Save Item</button>
                    <button type="button" class="btn-edit" style="flex:1;" onclick="resetForm()">Cancel</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function addPoint(value = '') {
    const container = document.getElementById('points-container');
    const div = document.createElement('div');
    div.style.display = 'flex';
    div.style.gap = '10px';
    div.style.marginBottom = '10px';
    div.innerHTML = `
        <input type="text" name="points[]" class="point-input" value="${value}" placeholder="New point">
        <button type="button" class="btn-edit" onclick="removePoint(this)" style="width: auto;"><i class="fas fa-times"></i></button>
    `;
    container.appendChild(div);
}

function removePoint(btn) {
    btn.parentElement.remove();
}

function editItem(index, item) {
    document.getElementById('form-title').innerText = 'Edit Item';
    document.getElementById('item-index').value = index;
    document.getElementById('item-org').value = item.org || '';
    document.getElementById('item-period').value = item.period || '';
    document.getElementById('item-role').value = item.role || item.degree || '';
    document.getElementById('item-details').value = item.details || '';
    
    const container = document.getElementById('points-container');
    container.innerHTML = '';
    if (item.points && item.points.length > 0) {
        item.points.forEach(p => addPoint(p));
    } else {
        addPoint();
    }
    
    window.scrollTo({ top: document.getElementById('form-card').offsetTop - 100, behavior: 'smooth' });
}

function resetForm() {
    document.getElementById('form-title').innerText = 'Add New Item';
    document.getElementById('item-index').value = '-1';
    document.getElementById('item-org').value = '';
    document.getElementById('item-period').value = '';
    document.getElementById('item-role').value = '';
    document.getElementById('item-details').value = '';
    document.getElementById('points-container').innerHTML = '';
    addPoint();
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
