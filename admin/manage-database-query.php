<?php
/**
 * SQL Query Runner - Advanced database query interface
 */
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/../helpers/DatabaseManager.php';

$db = DatabaseManager::getInstance();
$flash = getFlashMessage();
$query = '';
$results = [];
$error = null;
$executionTime = 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $query = $_POST['query'] ?? '';
    
    if (!empty($query)) {
        $startTime = microtime(true);
        
        try {
            // Detect query type
            $queryType = strtoupper(substr(trim($query), 0, 6));
            
            // Prevent dangerous operations without confirmation
            $dangerousKeywords = ['DROP', 'TRUNCATE', 'DELETE'];
            $isDangerous = false;
            foreach ($dangerousKeywords as $keyword) {
                if (stripos($query, $keyword) !== false) {
                    $isDangerous = true;
                    break;
                }
            }
            
            if ($isDangerous && empty($_POST['confirm'])) {
                $error = "This query contains potentially dangerous operations. Please confirm by checking the 'Confirm' checkbox.";
            } else {
                $stmt = $db->getConnection()->prepare($query);
                $stmt->execute();
                
                // If SELECT query, fetch results
                if (in_array($queryType, ['SELECT', 'SHOW', 'DESCRI', 'EXPLAI'])) {
                    $results = $stmt->fetchAll();
                } else {
                    $rowCount = $stmt->rowCount();
                    setFlashMessage("Query executed successfully! Affected rows: $rowCount", 'success');
                }
                
                $executionTime = microtime(true) - $startTime;
            }
        } catch (PDOException $e) {
            $error = $e->getMessage();
        }
    }
}

// Sample queries
$samples = [
    'SELECT * FROM blog_posts LIMIT 10',
    'SELECT * FROM blog_posts WHERE status = "published" ORDER BY created_at DESC',
    'SELECT COUNT(*) as total_posts FROM blog_posts',
    'SELECT status, COUNT(*) as count FROM blog_posts GROUP BY status',
    'SHOW TABLES',
    'DESCRIBE blog_posts',
];
?>

<div class="section-header" style="margin-bottom: 30px;">
    <h1><i class="fas fa-code"></i> SQL Query Runner</h1>
    <a href="manage-database.php" class="btn-edit"><i class="fas fa-arrow-left"></i> Back to Database</a>
</div>

<?php if ($flash): ?>
    <div class="<?php echo $flash['type']; ?>-msg"><?php echo $flash['message']; ?></div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="error-msg">
        <strong>Query Error:</strong><br>
        <?php echo htmlspecialchars($error); ?>
    </div>
<?php endif; ?>

<!-- Query Form -->
<div class="editor-card" style="margin-bottom: 30px;">
    <h2 style="margin-bottom: 15px;">Execute SQL Query</h2>
    
    <form method="POST">
        <div class="form-group">
            <label for="query">SQL Query</label>
            <textarea id="query" name="query" rows="10" 
                      style="font-family: 'Courier New', monospace; font-size: 14px; background: #1e2d3d; color: #abb2bf;"
                      placeholder="Enter your SQL query here..."><?php echo htmlspecialchars($query); ?></textarea>
        </div>
        
        <div style="display: flex; gap: 15px; align-items: center; margin-bottom: 15px;">
            <label style="display: flex; align-items: center; gap: 8px; color: #abb2bf; cursor: pointer;">
                <input type="checkbox" name="confirm" value="1" style="width: auto;">
                <span>I confirm this query is safe to execute</span>
            </label>
        </div>
        
        <button type="submit" class="btn-login" style="width: auto; padding: 12px 30px;">
            <i class="fas fa-play"></i> Execute Query
        </button>
    </form>
</div>

<!-- Sample Queries -->
<div class="editor-card" style="margin-bottom: 30px;">
    <h3 style="margin-bottom: 15px;">Sample Queries</h3>
    <div style="display: flex; flex-direction: column; gap: 10px;">
        <?php foreach ($samples as $sample): ?>
            <button onclick="document.getElementById('query').value = '<?php echo addslashes($sample); ?>'" 
                    class="sample-query">
                <?php echo htmlspecialchars($sample); ?>
            </button>
        <?php endforeach; ?>
    </div>
</div>

<!-- Results -->
<?php if (!empty($results)): ?>
    <div class="editor-card">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
            <h2>Query Results</h2>
            <div style="display: flex; gap: 15px; align-items: center;">
                <span class="badge">Rows: <?php echo count($results); ?></span>
                <span class="badge">Time: <?php echo number_format($executionTime * 1000, 2); ?>ms</span>
            </div>
        </div>
        
        <div style="overflow-x: auto;">
            <table class="data-table">
                <thead>
                    <tr>
                        <?php foreach (array_keys($results[0]) as $column): ?>
                            <th><?php echo htmlspecialchars($column); ?></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($results as $row): ?>
                        <tr>
                            <?php foreach ($row as $value): ?>
                                <td><?php echo htmlspecialchars($value ?? 'NULL'); ?></td>
                            <?php endforeach; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>

<style>
.sample-query {
    padding: 12px 15px;
    background: rgba(255, 255, 255, 0.03);
    border: 1px solid #444;
    border-radius: 6px;
    color: #abb2bf;
    font-family: 'Courier New', monospace;
    font-size: 13px;
    cursor: pointer;
    text-align: left;
    transition: all 0.3s ease;
}

.sample-query:hover {
    background: rgba(199, 120, 221, 0.1);
    border-color: #c778dd;
    color: #c778dd;
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
    max-width: 300px;
    overflow: hidden;
    text-overflow: ellipsis;
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
</style>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
