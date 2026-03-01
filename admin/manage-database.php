<?php
/**
 * Database Management Admin Panel
 * Shows connection info, tables, data, and advanced controls
 */
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/../helpers/DatabaseManager.php';

$db = DatabaseManager::getInstance();
$flash = getFlashMessage();
$config = require __DIR__ . '/../config/database.php';

// Get database statistics
$stats = [];
$tables = [];
$selectedTable = $_GET['table'] ?? null;
$tableData = [];
$tableStructure = [];

try {
    // Get all tables
    $stmt = $db->getConnection()->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    // Get database size
    $stmt = $db->getConnection()->query("
        SELECT 
            SUM(data_length + index_length) as size,
            COUNT(*) as table_count
        FROM information_schema.TABLES 
        WHERE table_schema = '{$config['database']}'
    ");
    $stats = $stmt->fetch();
    
    // If table selected, get its data and structure
    if ($selectedTable && in_array($selectedTable, $tables)) {
        // Get table structure
        $stmt = $db->getConnection()->query("DESCRIBE `$selectedTable`");
        $tableStructure = $stmt->fetchAll();
        
        // Get row count
        $stmt = $db->getConnection()->query("SELECT COUNT(*) as count FROM `$selectedTable`");
        $rowCount = $stmt->fetch()['count'];
        
        // Get table data (limited to 50 rows)
        $limit = $_GET['limit'] ?? 50;
        $offset = $_GET['offset'] ?? 0;
        $stmt = $db->getConnection()->query("SELECT * FROM `$selectedTable` LIMIT $limit OFFSET $offset");
        $tableData = $stmt->fetchAll();
    }
    
} catch (PDOException $e) {
    setFlashMessage('Database error: ' . $e->getMessage(), 'error');
}

// Format bytes
function formatBytes($bytes) {
    $units = ['B', 'KB', 'MB', 'GB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    return round($bytes / (1024 ** $pow), 2) . ' ' . $units[$pow];
}
?>

<div class="section-header" style="margin-bottom: 30px;">
    <h1>Database Management</h1>
    <a href="index.php" class="btn-edit"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
</div>

<?php if ($flash): ?>
    <div class="<?php echo $flash['type']; ?>-msg"><?php echo $flash['message']; ?></div>
<?php endif; ?>

<!-- Database Connection Info -->
<div class="editor-card" style="margin-bottom: 30px;">
    <h2 style="margin-bottom: 20px;"><i class="fas fa-database"></i> Connection Information</h2>
    
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px;">
        <div class="stat-box">
            <div class="stat-label">Host</div>
            <div class="stat-value"><?php echo htmlspecialchars($config['host']); ?></div>
        </div>
        
        <div class="stat-box">
            <div class="stat-label">Database Name</div>
            <div class="stat-value"><?php echo htmlspecialchars($config['database']); ?></div>
        </div>
        
        <div class="stat-box">
            <div class="stat-label">Username</div>
            <div class="stat-value"><?php echo htmlspecialchars($config['username']); ?></div>
        </div>
        
        <div class="stat-box">
            <div class="stat-label">Status</div>
            <div class="stat-value" style="color: #00ff9d;">
                <?php echo $db->testConnection() ? '✓ Connected' : '✗ Disconnected'; ?>
            </div>
        </div>
        
        <div class="stat-box">
            <div class="stat-label">Total Tables</div>
            <div class="stat-value"><?php echo count($tables); ?></div>
        </div>
        
        <div class="stat-box">
            <div class="stat-label">Database Size</div>
            <div class="stat-value"><?php echo formatBytes($stats['size'] ?? 0); ?></div>
        </div>
    </div>
</div>

<!-- Tables List -->
<div class="editor-card" style="margin-bottom: 30px;">
    <h2 style="margin-bottom: 20px;"><i class="fas fa-table"></i> Database Tables</h2>
    
    <div class="table-grid">
        <?php foreach ($tables as $table): ?>
            <a href="?table=<?php echo urlencode($table); ?>" 
               class="table-card <?php echo $selectedTable === $table ? 'active' : ''; ?>">
                <i class="fas fa-table"></i>
                <span><?php echo htmlspecialchars($table); ?></span>
            </a>
        <?php endforeach; ?>
    </div>
</div>

<!-- Table Details -->
<?php if ($selectedTable): ?>
    <div class="editor-card" style="margin-bottom: 30px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h2><i class="fas fa-info-circle"></i> Table: <?php echo htmlspecialchars($selectedTable); ?></h2>
            <div style="display: flex; gap: 10px;">
                <span class="badge">Rows: <?php echo $rowCount ?? 0; ?></span>
                <a href="database-export.php?table=<?php echo urlencode($selectedTable); ?>&action=export" class="btn-edit btn-sm">
                    <i class="fas fa-download"></i> Export
                </a>
            </div>
        </div>
        
        <!-- Table Structure -->
        <h3 style="margin: 20px 0 10px;">Structure</h3>
        <div style="overflow-x: auto;">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Field</th>
                        <th>Type</th>
                        <th>Null</th>
                        <th>Key</th>
                        <th>Default</th>
                        <th>Extra</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($tableStructure as $field): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($field['Field']); ?></strong></td>
                            <td><?php echo htmlspecialchars($field['Type']); ?></td>
                            <td><?php echo htmlspecialchars($field['Null']); ?></td>
                            <td><?php echo htmlspecialchars($field['Key']); ?></td>
                            <td><?php echo htmlspecialchars($field['Default'] ?? 'NULL'); ?></td>
                            <td><?php echo htmlspecialchars($field['Extra']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Table Data -->
        <?php if (!empty($tableData)): ?>
            <h3 style="margin: 30px 0 10px;">Data Preview (<?php echo count($tableData); ?> rows)</h3>
            <div style="overflow-x: auto;">
                <table class="data-table">
                    <thead>
                        <tr>
                            <?php foreach (array_keys($tableData[0]) as $column): ?>
                                <th><?php echo htmlspecialchars($column); ?></th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($tableData as $row): ?>
                            <tr>
                                <?php foreach ($row as $value): ?>
                                    <td><?php echo htmlspecialchars(substr($value ?? 'NULL', 0, 100)); ?></td>
                                <?php endforeach; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination -->
            <?php if ($rowCount > 50): ?>
                <div style="margin-top: 20px; text-align: center;">
                    <?php 
                    $currentPage = floor($offset / $limit) + 1;
                    $totalPages = ceil($rowCount / $limit);
                    ?>
                    <?php if ($offset > 0): ?>
                        <a href="?table=<?php echo urlencode($selectedTable); ?>&offset=<?php echo max(0, $offset - $limit); ?>" 
                           class="btn-edit btn-sm">Previous</a>
                    <?php endif; ?>
                    <span style="margin: 0 15px;">Page <?php echo $currentPage; ?> of <?php echo $totalPages; ?></span>
                    <?php if ($offset + $limit < $rowCount): ?>
                        <a href="?table=<?php echo urlencode($selectedTable); ?>&offset=<?php echo $offset + $limit; ?>" 
                           class="btn-edit btn-sm">Next</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        <?php else: ?>
            <p style="margin-top: 20px; color: #888;">No data in this table.</p>
        <?php endif; ?>
    </div>
<?php endif; ?>

<!-- Advanced Controls -->
<div class="editor-card">
    <h2 style="margin-bottom: 20px;"><i class="fas fa-tools"></i> Advanced Database Controls</h2>
    
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
        <a href="manage-database-query.php" class="control-btn">
            <i class="fas fa-code"></i>
            <span>Run SQL Query</span>
        </a>
        
        <a href="database-actions.php?action=test_connection" class="control-btn">
            <i class="fas fa-stethoscope"></i>
            <span>Test Connection</span>
        </a>
        
        <a href="database-actions.php?action=optimize_tables" class="control-btn">
            <i class="fas fa-compress-alt"></i>
            <span>Optimize Tables</span>
        </a>
        
        <a href="database-actions.php?action=backup_db" class="control-btn">
            <i class="fas fa-database"></i>
            <span>Backup Database</span>
        </a>
        
        <a href="manage-database-import.php" class="control-btn">
            <i class="fas fa-file-import"></i>
            <span>Import SQL</span>
        </a>
        
        <a href="database-export.php?action=export_all" class="control-btn">
            <i class="fas fa-file-export"></i>
            <span>Export All Tables</span>
        </a>
    </div>
</div>

<style>
.stat-box {
    background: rgba(255, 255, 255, 0.03);
    border: 1px solid #444;
    border-radius: 8px;
    padding: 15px;
}

.stat-label {
    color: #888;
    font-size: 12px;
    margin-bottom: 5px;
    text-transform: uppercase;
}

.stat-value {
    color: #fff;
    font-size: 18px;
    font-weight: bold;
}

.table-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 15px;
}

.table-card {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 15px;
    background: rgba(255, 255, 255, 0.03);
    border: 1px solid #444;
    border-radius: 8px;
    color: #abb2bf;
    text-decoration: none;
    transition: all 0.3s ease;
}

.table-card:hover {
    background: rgba(199, 120, 221, 0.1);
    border-color: #c778dd;
    transform: translateY(-2px);
}

.table-card.active {
    background: rgba(199, 120, 221, 0.2);
    border-color: #c778dd;
    color: #c778dd;
}

.table-card i {
    font-size: 20px;
}

.data-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 13px;
}

.data-table th {
    background: rgba(255, 255, 255, 0.05);
    padding: 10px;
    text-align: left;
    border: 1px solid #444;
    font-weight: bold;
    color: #c778dd;
}

.data-table td {
    padding: 8px 10px;
    border: 1px solid #333;
    color: #abb2bf;
}

.data-table tbody tr:hover {
    background: rgba(255, 255, 255, 0.03);
}

.badge {
    background: rgba(199, 120, 221, 0.2);
    color: #c778dd;
    padding: 5px 12px;
    border-radius: 12px;
    font-size: 12px;
}

.control-btn {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 10px;
    padding: 20px;
    background: rgba(255, 255, 255, 0.03);
    border: 1px solid #444;
    border-radius: 8px;
    color: #abb2bf;
    text-decoration: none;
    transition: all 0.3s ease;
    text-align: center;
}

.control-btn:hover {
    background: rgba(199, 120, 221, 0.1);
    border-color: #c778dd;
    color: #c778dd;
    transform: translateY(-2px);
}

.control-btn i {
    font-size: 24px;
}

.control-btn span {
    font-size: 13px;
    font-weight: 500;
}
</style>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
