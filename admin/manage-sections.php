<?php
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/functions.php';

$sectionsFile = __DIR__ . '/../data/home_sections.json';
$sections = json_decode(file_get_contents($sectionsFile), true);
usort($sections, fn($a, $b) => ($a['order'] ?? 0) <=> ($b['order'] ?? 0));

$flash = getFlashMessage();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
        setFlashMessage('Invalid request token.', 'error');
        header('Location: manage-sections.php');
        exit;
    }

    $action = $_POST['action'] ?? '';
    $sectionId = $_POST['section_id'] ?? '';

    if ($action === 'move_up' || $action === 'move_down') {
        foreach ($sections as $index => $section) {
            if ($section['id'] === $sectionId) {
                if ($action === 'move_up' && $index > 0) {
                    $temp = $sections[$index - 1];
                    $sections[$index - 1] = $sections[$index];
                    $sections[$index] = $temp;
                } elseif ($action === 'move_down' && $index < count($sections) - 1) {
                    $temp = $sections[$index + 1];
                    $sections[$index + 1] = $sections[$index];
                    $sections[$index] = $temp;
                }
                break;
            }
        }
    } elseif ($action === 'toggle_visibility') {
        foreach ($sections as &$section) {
            if ($section['id'] === $sectionId) {
                $section['visible'] = !($section['visible'] ?? true);
                break;
            }
        }
    }

    // Re-assign orders
    foreach ($sections as $index => &$section) {
        $section['order'] = $index + 1;
    }

    file_put_contents($sectionsFile, json_encode(array_values($sections), JSON_PRETTY_PRINT));
    setFlashMessage('Section order/visibility updated.');
    header('Location: manage-sections.php');
    exit;
}
?>

<div class="section-header">
    <h1><i class="fas fa-layer-group"></i> Manage Home Sections</h1>
    <p>Reorder and toggle visibility of your portfolio sections.</p>
</div>

<?php if ($flash): ?>
    <div class="<?php echo $flash['type']; ?>-msg"><?php echo $flash['message']; ?></div>
<?php endif; ?>

<div class="editor-card">
    <div class="table-container">
        <table class="audit-table">
            <thead>
                <tr>
                    <th>Order</th>
                    <th>Section Name</th>
                    <th>Type</th>
                    <th>Visibility</th>
                    <th style="text-align:right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($sections as $index => $section): ?>
                <tr>
                    <td style="width: 80px;">
                        <span class="status-badge" style="background: rgba(199, 120, 221, 0.1); color: var(--primary-color);">
                            #<?php echo $section['order']; ?>
                        </span>
                    </td>
                    <td>
                        <div style="font-weight: 600; color: #fff;"><?php echo htmlspecialchars($section['title']); ?></div>
                        <div style="font-size: 0.8rem; color: #666;"><?php echo htmlspecialchars($section['id']); ?></div>
                    </td>
                    <td>
                        <span class="status-badge" style="background: rgba(97, 175, 239, 0.1); color: var(--accent-color);">
                            <?php echo strtoupper($section['type']); ?>
                        </span>
                    </td>
                    <td>
                        <?php if ($section['visible'] ?? true): ?>
                            <span class="status-badge success-badge"><i class="fas fa-eye"></i> Visible</span>
                        <?php else: ?>
                            <span class="status-badge error-badge"><i class="fas fa-eye-slash"></i> Hidden</span>
                        <?php endif; ?>
                    </td>
                    <td style="text-align:right;">
                        <div style="display:flex; justify-content: flex-end; gap: 10px;">
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                <input type="hidden" name="section_id" value="<?php echo $section['id']; ?>">
                                <input type="hidden" name="action" value="move_up">
                                <button type="submit" class="btn-edit btn-sm" <?php echo $index === 0 ? 'disabled' : ''; ?> title="Move Up">
                                    <i class="fas fa-arrow-up"></i>
                                </button>
                            </form>
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                <input type="hidden" name="section_id" value="<?php echo $section['id']; ?>">
                                <input type="hidden" name="action" value="move_down">
                                <button type="submit" class="btn-edit btn-sm" <?php echo $index === count($sections) - 1 ? 'disabled' : ''; ?> title="Move Down">
                                    <i class="fas fa-arrow-down"></i>
                                </button>
                            </form>
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                <input type="hidden" name="section_id" value="<?php echo $section['id']; ?>">
                                <input type="hidden" name="action" value="toggle_visibility">
                                <button type="submit" class="btn-edit btn-sm" title="Toggle Visibility">
                                    <i class="fas <?php echo ($section['visible'] ?? true) ? 'fa-eye-slash' : 'fa-eye'; ?>"></i>
                                </button>
                            </form>
                            
                            <?php if ($section['type'] === 'dynamic_list'): ?>
                                <a href="edit-dynamic-section.php?id=<?php echo $section['id']; ?>" class="btn-edit btn-sm" title="Edit Content">
                                    <i class="fas fa-edit"></i> Edit
                                </a>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div style="margin-top: 30px;">
    <div class="editor-card" style="border-left: 4px solid var(--accent-color);">
        <h3><i class="fas fa-plus-circle"></i> Add New Dynamic Section</h3>
        <p style="color: #888; margin-bottom: 20px;">You can add a new section that will use a dedicated JSON file for its content.</p>
        <form action="api/add_section.php" method="POST">
             <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
             <div style="display:grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                <div class="form-group">
                    <label>Section Title (e.g. CERTIFICATES)</label>
                    <input type="text" name="title" required placeholder="CERTIFICATES">
                </div>
                <div class="form-group">
                    <label>Icon (FontAwesome class)</label>
                    <input type="text" name="icon" placeholder="fas fa-certificate" value="fas fa-certificate">
                </div>
             </div>
             <button type="submit" class="btn-login" style="width: 200px;">Create Section</button>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
