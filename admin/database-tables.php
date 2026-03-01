<?php
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/../helpers/DatabaseAdmin.php';

$dbAdmin = new DatabaseAdmin();
$flash = getFlashMessage();

$action = $_GET['action'] ?? 'list';
$table = $_GET['table'] ?? '';

// Handle Actions (Drop, Empty)
if (isset($_GET['do']) && $table) {
    if ($_GET['do'] === 'drop') {
        $dbAdmin->executeQuery("DROP TABLE IF EXISTS `$table`");
        setFlashMessage("Table '$table' dropped.");
        header('Location: database-tables.php');
        exit;
    }
    if ($_GET['do'] === 'empty') {
        $dbAdmin->executeQuery("TRUNCATE TABLE `$table`");
        setFlashMessage("Table '$table' truncated.");
        header('Location: database-tables.php');
        exit;
    }
}

?>

<div class="section-header" style="margin-bottom: 30px;">
    <h1>Database Inspector</h1>
    <div style="display:flex; gap:10px;">
        <a href="database-config.php" class="btn-edit">Connection Manager</a>
        <a href="index.php" class="btn-edit"><i class="fas fa-arrow-left"></i> Back</a>
    </div>
</div>

<?php if ($flash): ?>
    <div class="<?php echo $flash['type']; ?>-msg"><?php echo $flash['message']; ?></div>
<?php endif; ?>

<?php if ($action === 'list'): ?>
    <?php $tables = $dbAdmin->getTables(); ?>
    <div class="editor-card">
        <h2>Tables in Database</h2>
        <?php if (empty($tables)): ?>
            <p>No tables found or not connected.</p>
        <?php else: ?>
            <table style="width:100%; border-collapse: collapse; margin-top:20px;">
                <thead>
                    <tr style="border-bottom:1px solid #444; color:#888; text-align:left;">
                        <th style="padding:10px;">Table</th>
                        <th style="padding:10px;">Rows</th>
                        <th style="padding:10px;">Engine</th>
                        <th style="padding:10px;">Collation</th>
                        <th style="padding:10px; text-align:right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($tables as $t): 
                        $name = $t['Name'] ?? $t['name']; // Adjust casing if needed
                        $rows = $t['Rows'] ?? $t['rows'] ?? 0;
                        $engine = $t['Engine'] ?? $t['engine'] ?? '-';
                        $collation = $t['Collation'] ?? $t['collation'] ?? '-';
                    ?>
                    <tr style="border-bottom:1px solid #222;">
                        <td style="padding:10px; font-weight:bold; color:var(--success-color);"><?php echo htmlspecialchars($name); ?></td>
                        <td style="padding:10px;"><?php echo number_format($rows); ?></td>
                        <td style="padding:10px; color:#888;"><?php echo htmlspecialchars($engine); ?></td>
                        <td style="padding:10px; color:#888; font-size:0.9em;"><?php echo htmlspecialchars($collation); ?></td>
                        <td style="padding:10px; text-align:right;">
                            <a href="database-tables.php?action=browse&table=<?php echo $name; ?>" class="btn-edit" style="padding:5px 10px;">Browse</a>
                            <a href="database-tables.php?action=structure&table=<?php echo $name; ?>" class="btn-edit" style="padding:5px 10px;">Structure</a>
                            <a href="database-tables.php?do=empty&table=<?php echo $name; ?>" class="btn-remove" onclick="return confirm('Clear all data in <?php echo $name; ?>?')" style="position:static; padding:5px 10px;">Empty</a>
                            <a href="database-tables.php?do=drop&table=<?php echo $name; ?>" class="btn-remove" onclick="return confirm('DELETE table <?php echo $name; ?>?')" style="position:static; padding:5px 10px;">Drop</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

<?php elseif ($action === 'browse'): ?>
    <?php $data = $dbAdmin->getTableData($table); ?>
    <div class="editor-card" style="overflow-x:auto;">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
            <h2>Browsing: <?php echo htmlspecialchars($table); ?></h2>
            <a href="database-tables.php" class="btn-edit">Back to List</a>
        </div>
        
        <?php if (empty($data)): ?>
            <p>No data found.</p>
        <?php else: ?>
            <table style="width:100%; border-collapse: collapse; min-width:800px;">
                <thead>
                    <tr style="border-bottom:1px solid #444; color:#888; text-align:left;">
                        <?php foreach (array_keys($data[0]) as $col): ?>
                            <th style="padding:10px; white-space:nowrap;"><?php echo htmlspecialchars($col); ?></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($data as $row): ?>
                        <tr style="border-bottom:1px solid #222;">
                            <?php foreach ($row as $val): ?>
                                <td style="padding:10px; max-width:200px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">
                                    <?php echo htmlspecialchars(substr($val ?? 'NULL', 0, 50)); ?>
                                </td>
                            <?php endforeach; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

<?php elseif ($action === 'structure'): ?>
    <?php $columns = $dbAdmin->getTableStructure($table); ?>
    <div class="editor-card">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
            <h2>Structure: <?php echo htmlspecialchars($table); ?></h2>
            <a href="database-tables.php" class="btn-edit">Back to List</a>
        </div>
        
        <table style="width:100%; border-collapse: collapse;">
            <thead>
                <tr style="border-bottom:1px solid #444; color:#888; text-align:left;">
                    <th style="padding:10px;">Field</th>
                    <th style="padding:10px;">Type</th>
                    <th style="padding:10px;">Null</th>
                    <th style="padding:10px;">Key</th>
                    <th style="padding:10px;">Default</th>
                    <th style="padding:10px;">Extra</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($columns as $col): ?>
                    <tr style="border-bottom:1px solid #222;">
                        <td style="padding:10px; font-weight:bold;"><?php echo htmlspecialchars($col['Field']); ?></td>
                        <td style="padding:10px; color:#61afef;"><?php echo htmlspecialchars($col['Type']); ?></td>
                        <td style="padding:10px;"><?php echo htmlspecialchars($col['Null']); ?></td>
                        <td style="padding:10px; color:var(--accent-color);"><?php echo htmlspecialchars($col['Key']); ?></td>
                        <td style="padding:10px;"><?php echo htmlspecialchars($col['Default'] ?? 'NULL'); ?></td>
                        <td style="padding:10px; font-style:italic;"><?php echo htmlspecialchars($col['Extra']); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
