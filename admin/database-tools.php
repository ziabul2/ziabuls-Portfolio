<?php
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/../helpers/DatabaseAdmin.php';

$dbAdmin = new DatabaseAdmin();
$flash = getFlashMessage();
$queryResult = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // START EXPORT
    if (isset($_POST['action']) && $_POST['action'] === 'export') {
        $sql = $dbAdmin->exportDatabase();
        $filename = 'db_backup_' . date('Y-m-d_H-i') . '.sql';
        
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . strlen($sql));
        echo $sql;
        exit;
    }
    
    // START IMPORT
    if (isset($_POST['action']) && $_POST['action'] === 'import') {
        if (isset($_FILES['sql_file']) && $_FILES['sql_file']['error'] === UPLOAD_ERR_OK) {
            $sql = file_get_contents($_FILES['sql_file']['tmp_name']);
            // Crude split for statements, ideally we use executeSQL logic or allow multi-query if driver permits
            // PDO execute might fail on multiple queries depending on config.
            // Best to cycle through if possible or try one big exec.
            try {
                 $dbAdmin->executeQuery($sql); // Basic attempt
                 setFlashMessage('Import executed successfully (check database for verification).');
            } catch (Exception $e) {
                 setFlashMessage('Import failed: ' . $e->getMessage(), 'error');
            }
        } else {
             setFlashMessage('Please upload a valid SQL file.', 'error');
        }
    }
    
    // START RAW QUERY
    if (isset($_POST['action']) && $_POST['action'] === 'query') {
        $sql = trim($_POST['sql_query']);
        if ($sql) {
            $queryResult = $dbAdmin->executeQuery($sql);
            if ($queryResult['type'] === 'error') {
                 setFlashMessage('Query Error: ' . $queryResult['message'], 'error');
            } else {
                 setFlashMessage('Query executed successfully.');
            }
        }
    }
    // START MIGRATION
    if (isset($_POST['action']) && $_POST['action'] === 'migrate') {
        $sourceName = $_POST['source_profile'];
        $targetName = $_POST['target_profile'];
        
        $profilesFile = __DIR__ . '/../../data/db_profiles.json';
        $profiles = file_exists($profilesFile) ? json_decode(file_get_contents($profilesFile), true) : [];
        
        if (isset($profiles[$sourceName]) && isset($profiles[$targetName])) {
            $result = $dbAdmin->migrateDatabase($profiles[$sourceName], $profiles[$targetName]);
            if ($result['status'] === 'success') {
                setFlashMessage("Migration Complete! " . count($result['log']) . " operations executed.", 'success');
            } else {
                setFlashMessage("Migration Failed: " . $result['message'], 'error');
            }
        } else {
            setFlashMessage("Invalid profiles selected.", 'error');
        }
    }

    // START MAINTENANCE
    if (isset($_POST['action']) && $_POST['action'] === 'optimize') {
        $results = $dbAdmin->optimizeAllTables();
        setFlashMessage("Optimization run on " . count($results) . " tables.", 'success');
    }
}

// Load Profiles for Migration Dropdown
$profilesFile = __DIR__ . '/../../data/db_profiles.json';
$profiles = file_exists($profilesFile) ? json_decode(file_get_contents($profilesFile), true) : [];
?>

<div class="section-header" style="margin-bottom: 30px;">
    <h1>Database Tools</h1>
    <div style="display:flex; gap:10px;">
        <a href="database-config.php" class="btn-edit">Connection Manager</a>
        <a href="index.php" class="btn-edit"><i class="fas fa-arrow-left"></i> Back</a>
    </div>
</div>

<?php if ($flash): ?>
    <div class="<?php echo $flash['type']; ?>-msg"><?php echo $flash['message']; ?></div>
<?php endif; ?>

<!-- SQL CONSOLE (MOVED TO TOP) -->
<div class="editor-card" style="margin-bottom:30px;">
    <h2><i class="fas fa-terminal"></i> SQL Console</h2>
    <p style="color:#888; margin-bottom:10px;">Think like a Database Engineer. Run raw queries.</p>
    
    <!-- Quick Templates -->
    <div style="margin-bottom:15px; padding:10px; background:rgba(0,0,0,0.3); border-radius:4px;">
        <label style="display:block; margin-bottom:5px; color:#888;">Quick Templates:</label>
        <select id="sqlTemplate" onchange="loadTemplate()" style="width:100%; padding:8px; background:#111; color:#fff; border:1px solid #444;">
            <option value="">-- Select a template --</option>
            <option value="show_tables">Show All Tables</option>
            <option value="show_databases">Show All Databases</option>
            <option value="create_table">Create Table Example</option>
            <option value="select">SELECT Query</option>
            <option value="insert">INSERT Query</option>
            <option value="update">UPDATE Query</option>
            <option value="delete">DELETE Query</option>
            <option value="alter_add">ALTER TABLE - Add Column</option>
            <option value="create_index">CREATE INDEX</option>
            <option value="show_processlist">Show Process List</option>
        </select>
    </div>
    
    <form method="POST">
        <input type="hidden" name="action" value="query">
        <textarea id="sqlQuery" name="sql_query" style="width:100%; height:150px; background:#1e1e1e; color:#00ff9d; font-family:monospace; padding:15px; border:1px solid #444; margin-bottom:15px;" placeholder="SELECT * FROM ..."></textarea>
        <button type="submit" class="btn-login">Execute Query</button>
    </form>

    <?php if ($queryResult && $queryResult['type'] === 'result'): ?>
        <div style="margin-top:20px; overflow-x:auto;">
            <h3>Results (<?php echo count($queryResult['data']); ?> rows)</h3>
            <?php if (empty($queryResult['data'])): ?>
                <p>Empty result set.</p>
            <?php else: ?>
                <table style="width:100%; border-collapse: collapse; margin-top:10px;">
                    <thead>
                        <tr style="border-bottom:1px solid #444; color:#888;">
                            <?php foreach (array_keys($queryResult['data'][0]) as $col): ?>
                                <th style="padding:10px;"><?php echo htmlspecialchars($col); ?></th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($queryResult['data'] as $row): ?>
                            <tr style="border-bottom:1px solid #222;">
                                <?php foreach ($row as $val): ?>
                                    <td style="padding:8px;"><?php echo htmlspecialchars($val ?? 'NULL'); ?></td>
                                <?php endforeach; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    <?php endif; ?>
    
    <?php if ($queryResult && $queryResult['type'] === 'affected'): ?>
        <div style="margin-top:20px; padding:15px; background:rgba(0,255,157,0.1); color:#00ff9d; border-radius:4px;">
            <i class="fas fa-check"></i> Query OK, <?php echo $queryResult['count']; ?> rows affected.
        </div>
    <?php endif; ?>
</div>

<script>
const templates = {
    show_tables: "SHOW TABLES;",
    show_databases: "SHOW DATABASES;",
    create_table: `CREATE TABLE example_table (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);`,
    select: "SELECT * FROM table_name WHERE condition = 'value' LIMIT 10;",
    insert: "INSERT INTO table_name (column1, column2) VALUES ('value1', 'value2');",
    update: "UPDATE table_name SET column1 = 'new_value' WHERE id = 1;",
    delete: "DELETE FROM table_name WHERE id = 1;",
    alter_add: "ALTER TABLE table_name ADD COLUMN new_column VARCHAR(255);",
    create_index: "CREATE INDEX idx_column_name ON table_name(column_name);",
    show_processlist: "SHOW FULL PROCESSLIST;"
};

function loadTemplate() {
    const select = document.getElementById('sqlTemplate');
    const textarea = document.getElementById('sqlQuery');
    const template = templates[select.value];
    if (template) {
        textarea.value = template;
    }
}
</script>

<div class="tools-grid" style="display:grid; grid-template-columns: 1fr 1fr; gap: 30px; margin-bottom: 30px;">
    
    <!-- EXPORT -->
    <div class="editor-card">
        <h2><i class="fas fa-download"></i> Export Database</h2>
        <p style="color:#888; margin-bottom:15px;">Download a full SQL dump of structure and data.</p>
        <form method="POST">
            <input type="hidden" name="action" value="export">
            <button type="submit" class="btn-login" style="width:100%;">Download .sql Backup</button>
        </form>
    </div>

    <!-- IMPORT -->
    <div class="editor-card">
        <h2><i class="fas fa-upload"></i> Import Database</h2>
        <p style="color:#888; margin-bottom:15px;">Execute SQL file on connected database.</p>
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="action" value="import">
            <div style="margin-bottom:15px;">
                <input type="file" name="sql_file" accept=".sql" required style="background:#222; padding:10px; border-radius:4px; width:100%;">
            </div>
            <button type="submit" class="btn-edit" style="width:100%;">Run Import</button>
        </form>
    </div>
    
</div>

<!-- MIGRATION WIZARD -->
<div class="editor-card" style="border-left:4px solid #c778dd; margin-bottom:30px;">
    <h2><i class="fas fa-random"></i> Database Migration Wizard</h2>
    <p style="color:#aaa; font-size:0.9em; margin-bottom:20px;">Move entire database schema and data between environments.</p>
    
    <form method="POST" onsubmit="return confirm('WARNING: This will WIPE the Target Database and replace it with Source data. Are you sure?');">
        <input type="hidden" name="action" value="migrate">
        <div style="display:grid; grid-template-columns: 1fr 50px 1fr; align-items:center; gap:10px;">
            <div class="form-group">
                <label>Source (Copy From)</label>
                <select name="source_profile" required style="width:100%; background:#111; padding:10px; color:#fff; border:1px solid #444;">
                    <option value="">Select Profile...</option>
                    <?php foreach($profiles as $name => $p): ?>
                        <option value="<?php echo htmlspecialchars($name); ?>"><?php echo htmlspecialchars($name); ?> (<?php echo $p['host']; ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div style="text-align:center; font-size:24px; color:#666;">
                <i class="fas fa-arrow-right"></i>
            </div>
            
            <div class="form-group">
                <label>Target (Wipe & Replace)</label>
                <select name="target_profile" required style="width:100%; background:#111; padding:10px; color:#fff; border:1px solid #444;">
                    <option value="">Select Profile...</option>
                    <?php foreach($profiles as $name => $p): ?>
                        <option value="<?php echo htmlspecialchars($name); ?>"><?php echo htmlspecialchars($name); ?> (<?php echo $p['host']; ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <button type="submit" class="btn-login" style="margin-top:20px; background:#c778dd;">Start Migration</button>
    </form>
</div>

<!-- MAINTENANCE & PROCESSES -->
<div class="editor-card" style="margin-bottom:30px;">
    <div style="display:flex; justify-content:space-between; align-items:center;">
        <h2><i class="fas fa-microchip"></i> Server Operations</h2>
        <form method="POST">
            <input type="hidden" name="action" value="optimize">
            <button type="submit" class="btn-edit"><i class="fas fa-magic"></i> Optimize All Tables</button>
        </form>
    </div>
    
    <h3 style="margin-top:20px; font-size:1.1em; color:#888;">Active Processes (Important Only)</h3>
    <div style="overflow-x:auto; margin-top:10px; max-height:300px; overflow-y:auto; border:1px solid #333;">
        <table style="width:100%; border-collapse:collapse; font-size:0.85em;">
            <thead style="background:#222; position:sticky; top:0;">
                <tr>
                    <th style="padding:8px; text-align:left;">ID</th>
                    <th style="padding:8px; text-align:left;">User</th>
                    <th style="padding:8px; text-align:left;">Host</th>
                    <th style="padding:8px; text-align:left;">DB</th>
                    <th style="padding:8px; text-align:left;">Command</th>
                    <th style="padding:8px; text-align:left;">Time</th>
                    <th style="padding:8px; text-align:left;">State</th>
                    <th style="padding:8px; text-align:left;">Info</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $processes = $dbAdmin->getProcessList();
                // Filter: Show only important processes (not Sleep, exclude system)
                $important = array_filter($processes, function($p) {
                    return $p['Command'] !== 'Sleep' && $p['Time'] > 0;
                });
                
                if (empty($important)): ?>
                    <tr><td colspan="8" style="padding:20px; text-align:center; color:#666;">No active queries running</td></tr>
                <?php else:
                    foreach(array_slice($important, 0, 10) as $proc): ?>
                    <tr style="border-bottom:1px solid #333;">
                        <td style="padding:8px;"><?php echo $proc['Id']; ?></td>
                        <td style="padding:8px; color:#61afef;"><?php echo $proc['User']; ?></td>
                        <td style="padding:8px; font-size:0.8em;"><?php echo $proc['Host']; ?></td>
                        <td style="padding:8px; color:var(--accent-color);"><?php echo $proc['db'] ?? 'NULL'; ?></td>
                        <td style="padding:8px; font-weight:bold;"><?php echo $proc['Command']; ?></td>
                        <td style="padding:8px; <?php echo $proc['Time'] > 10 ? 'color:red; font-weight:bold;' : 'color:#98c379;'; ?>"><?php echo $proc['Time']; ?>s</td>
                        <td style="padding:8px; font-size:0.8em; color:#888;"><?php echo $proc['State'] ?? '-'; ?></td>
                        <td style="padding:8px; font-family:monospace; color:#aaa; max-width:200px; overflow:hidden; text-overflow:ellipsis;"><?php echo substr($proc['Info'] ?? 'NULL', 0, 60); ?></td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
