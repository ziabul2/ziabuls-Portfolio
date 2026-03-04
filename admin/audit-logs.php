<?php
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/../helpers/AuditLogger.php';
require_once __DIR__ . '/../config/security.php';

$audit = new AuditLogger();

// ── Handle POST actions ───────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
        http_response_code(403);
        die('Invalid CSRF token.');
    }

    $action = $_POST['action'] ?? '';

    if ($action === 'backup_audit') {
        $path = $audit->backup('audit');
        $audit->log('Log Backup Created', 'Audit log backed up to: ' . basename($path ?? ''), 'success');
        header('Location: audit-logs.php?msg=backup_ok&type=audit');
        exit;
    }

    if ($action === 'backup_attempts') {
        $path = $audit->backup('attempts');
        $audit->log('Log Backup Created', 'Login attempts log backed up', 'success');
        header('Location: audit-logs.php?msg=backup_ok&type=attempts');
        exit;
    }

    if ($action === 'clear_audit') {
        $audit->backup('audit'); // Auto-backup before clearing
        $audit->clearLogs('audit');
        $audit->log('Logs Cleared', 'Audit log cleared (auto-backed up first)', 'warning');
        header('Location: audit-logs.php?msg=cleared&type=audit');
        exit;
    }

    if ($action === 'clear_attempts') {
        $audit->backup('attempts'); // Auto-backup before clearing
        $audit->clearLogs('attempts');
        $audit->log('Login Attempts Cleared', 'Login attempts log cleared (auto-backed up first)', 'warning');
        header('Location: audit-logs.php?msg=cleared&type=attempts');
        exit;
    }

    if ($action === 'delete_backup' && !empty($_POST['filename'])) {
        $deleted = $audit->deleteBackup($_POST['filename']);
        $audit->log('Backup Deleted', 'Backup file deleted: ' . basename($_POST['filename']), $deleted ? 'warning' : 'failed');
        header('Location: audit-logs.php?tab=backups&msg=' . ($deleted ? 'deleted_ok' : 'delete_fail'));
        exit;
    }
}

// ── Handle GET export ─────────────────────────────────────────────────────────
if (isset($_GET['export'])) {
    $type   = in_array($_GET['export'], ['audit', 'attempts']) ? $_GET['export'] : 'audit';
    $format = in_array($_GET['format'] ?? '', ['csv', 'json']) ? $_GET['format'] : 'json';
    $file   = $_GET['file'] ?? null;

    if ($file) {
        // Security: Prevent path traversal
        $safeName = basename($file);
        $backupsDir = __DIR__ . '/../data/log_backups/';
        $filePath = realpath($backupsDir . $safeName);

        if ($filePath && strpos($filePath, realpath($backupsDir)) === 0 && file_exists($filePath)) {
            header('Content-Description: File Transfer');
            header('Content-Disposition: attachment; filename="' . $safeName . '"');
            header('Content-Type: application/json');
            readfile($filePath);
            exit;
        } else {
            header('Location: audit-logs.php?msg=file_not_found');
            exit;
        }
    }

    $label  = $type === 'attempts' ? 'login_attempts' : 'audit_logs';
    $fname  = $label . '_' . date('Ymd_His') . '.' . $format;

    header('Content-Description: File Transfer');
    header('Content-Disposition: attachment; filename="' . $fname . '"');

    if ($format === 'csv') {
        header('Content-Type: text/csv');
        echo $audit->exportCsv($type);
    } else {
        header('Content-Type: application/json');
        echo $audit->exportJson($type);
    }
    exit;
}

// ── Data ──────────────────────────────────────────────────────────────────────
$activeTab = in_array($_GET['tab'] ?? 'audit', ['audit', 'attempts', 'backups']) ? ($_GET['tab'] ?? 'audit') : 'audit';
$stats     = $audit->getStats();

// Pagination Config
$perPage = 5;

// Audit Logs Pagination
$allLogs    = $audit->getLogs(1000); // Fetch more for pagination
$totalLogs  = count($allLogs);
$auditPage  = max(1, (int)($_GET['p_audit'] ?? 1));
$auditTotalPages = ceil($totalLogs / $perPage);
if ($auditPage > $auditTotalPages && $auditTotalPages > 0) $auditPage = $auditTotalPages;
$logs = array_slice($allLogs, ($auditPage - 1) * $perPage, $perPage);

// Login Attempts Pagination
$allAttempts    = $audit->getLoginAttempts(1000);
$totalAttempts  = count($allAttempts);
$attemptsPage   = max(1, (int)($_GET['p_attempts'] ?? 1));
$attemptsTotalPages = ceil($totalAttempts / $perPage);
if ($attemptsPage > $attemptsTotalPages && $attemptsTotalPages > 0) $attemptsPage = $attemptsTotalPages;
$attempts = array_slice($allAttempts, ($attemptsPage - 1) * $perPage, $perPage);
$backups   = $audit->listBackups();
$csrfToken = generateCSRFToken();

// Notification messages
$msgs = [
    'backup_ok'   => ['type' => 'success', 'text' => '✅ Backup created successfully.'],
    'cleared'     => ['type' => 'success', 'text' => '🗑️ Log cleared (auto-backup was created first).'],
    'deleted_ok'  => ['type' => 'success', 'text' => '🗑️ Backup file deleted.'],
    'delete_fail' => ['type' => 'error',   'text' => '❌ Could not delete backup file.'],
    'file_not_found' => ['type' => 'error', 'text' => '❌ Backup file not found.'],
];
$msgKey  = $_GET['msg'] ?? '';
$message = $msgs[$msgKey] ?? null;

function statusBadge(mixed $status): string {
    if ($status === true || $status === 'success') return '<span style="background:#1a4730;color:#98c379;padding:3px 10px;border-radius:20px;font-size:0.75rem;font-weight:600;">SUCCESS</span>';
    if ($status === 'warning' || $status === false) return '<span style="background:#3d3019;color:#e5c07b;padding:3px 10px;border-radius:20px;font-size:0.75rem;font-weight:600;">' . ($status === false ? 'FAILED' : 'WARNING') . '</span>';
    return '<span style="background:#3d1a1a;color:#e06c75;padding:3px 10px;border-radius:20px;font-size:0.75rem;font-weight:600;">FAILED</span>';
}

function renderPagination(int $currentPage, int $totalPages, string $paramName, string $tabName): void {
    if ($totalPages <= 1) return;
    
    echo '<div class="pagination" style="display:flex; gap:8px; margin-top:20px; justify-content:center; align-items:center;">';
    
    // Previous
    if ($currentPage > 1) {
        echo '<a href="?tab=' . $tabName . '&' . $paramName . '=' . ($currentPage - 1) . '" class="btn-sm"><i class="fas fa-chevron-left"></i></a>';
    } else {
        echo '<span class="btn-sm" style="opacity:0.3; cursor:default;"><i class="fas fa-chevron-left"></i></span>';
    }
    
    // Page Numbers (Last 2 + Current + Next 2)
    $start = max(1, $currentPage - 2);
    $end   = min($totalPages, $currentPage + 2);
    
    if ($start > 1) echo '<span style="color:#555;">...</span>';
    
    for ($i = $start; $i <= $end; $i++) {
        $activeClass = ($i === $currentPage) ? 'primary' : '';
        echo '<a href="?tab=' . $tabName . '&' . $paramName . '=' . $i . '" class="btn-sm ' . $activeClass . '">' . $i . '</a>';
    }
    
    if ($end < $totalPages) echo '<span style="color:#555;">...</span>';
    
    // Next
    if ($currentPage < $totalPages) {
        echo '<a href="?tab=' . $tabName . '&' . $paramName . '=' . ($currentPage + 1) . '" class="btn-sm"><i class="fas fa-chevron-right"></i></a>';
    } else {
        echo '<span class="btn-sm" style="opacity:0.3; cursor:default;"><i class="fas fa-chevron-right"></i></span>';
    }
    
    echo '<span style="font-size:0.8rem; color:#555; margin-left:10px;">Page ' . $currentPage . ' of ' . $totalPages . '</span>';
    echo '</div>';
}
?>

<style>
.stat-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(150px,1fr)); gap:16px; margin-bottom:28px; }
.stat-box { background:#1a1a1a; border:1px solid #2a2a2a; border-radius:10px; padding:18px; text-align:center; }
.stat-box .num { font-size:2rem; font-weight:700; }
.stat-box .lbl { font-size:0.75rem; color:#666; margin-top:4px; }
.tab-bar { display:flex; gap:4px; margin-bottom:20px; border-bottom: 1px solid #2a2a2a; }
.tab-btn { padding:10px 20px; background:none; border:none; color:#888; cursor:pointer; font-size:0.9rem; border-bottom:3px solid transparent; transition:all .2s; }
.tab-btn.active { color:#61afef; border-color:#61afef; }
.tab-btn:hover { color:#ddd; }
.tab-pane { display:none; }
.tab-pane.active { display:block; }
.log-table { width:100%; border-collapse:collapse; font-size:0.87rem; }
.log-table th { padding:12px 14px; text-align:left; border-bottom:2px solid #333; color:#888; font-weight:600; white-space:nowrap; }
.log-table td { padding:11px 14px; border-bottom:1px solid #1e1e1e; vertical-align:top; }
.log-table tr:hover td { background:rgba(255,255,255,0.02); }
.action-row { display:flex; gap:10px; flex-wrap:wrap; align-items:center; margin-bottom:16px; }
.btn-sm { padding:7px 14px; border-radius:6px; font-size:0.82rem; cursor:pointer; border:1px solid #444; background:#222; color:#aaa; text-decoration:none; display:inline-flex; align-items:center; gap:6px; }
.btn-sm:hover { background:#333; color:#ddd; }
.btn-sm.danger { border-color:#e06c75; color:#e06c75; }
.btn-sm.danger:hover { background:rgba(224,108,117,0.15); }
.btn-sm.primary { border-color:#61afef; color:#61afef; }
.btn-sm.primary:hover { background:rgba(97,175,239,0.12); }
.backup-row { display:flex; align-items:center; justify-content:space-between; padding:12px 16px; background:#111; border-radius:8px; margin-bottom:8px; gap:10px; flex-wrap:wrap; }
.ip-mono { font-family:monospace; font-size:0.85rem; }
.realtime-dot { display:inline-block; width:8px; height:8px; background:#98c379; border-radius:50%; margin-right:6px; animation: pulse 1.5s infinite; }
@keyframes pulse { 0%,100%{opacity:1;} 50%{opacity:0.3;} }
#search-input { background:#111; border:1px solid #333; color:#ddd; padding:8px 12px; border-radius:6px; font-size:0.85rem; width:280px; }
.failed-row td { background: rgba(224,108,117,0.04); }
</style>

<div class="section-header" style="margin-bottom:20px;">
    <h1>Security Audit Logs</h1>
    <a href="index.php" class="btn-edit"><i class="fas fa-arrow-left"></i> Dashboard</a>
</div>

<?php if ($message): ?>
    <div class="<?php echo $message['type']; ?>-msg" style="margin-bottom:20px;"><?php echo $message['text']; ?></div>
<?php endif; ?>

<!-- ── Stats ─────────────────────────────────────────────────────── -->
<div class="stat-grid">
    <div class="stat-box">
        <div class="num" style="color:#61afef"><?php echo $stats['total_events']; ?></div>
        <div class="lbl">Audit Events</div>
    </div>
    <div class="stat-box">
        <div class="num" style="color:#e5c07b"><?php echo $stats['total_attempts']; ?></div>
        <div class="lbl">Login Attempts</div>
    </div>
    <div class="stat-box">
        <div class="num" style="color:#e06c75"><?php echo $stats['failed_logins']; ?></div>
        <div class="lbl">Failed Logins</div>
    </div>
    <div class="stat-box">
        <div class="num" style="color:#98c379"><?php echo $stats['success_logins']; ?></div>
        <div class="lbl">Successful Logins</div>
    </div>
    <div class="stat-box">
        <div class="num" style="color:#c678dd"><?php echo $stats['unique_ips']; ?></div>
        <div class="lbl">Unique IPs</div>
    </div>
    <div class="stat-box" style="grid-column:span 2;">
        <div style="font-size:0.85rem; color:#61afef; font-weight:600;"><?php echo $stats['last_event']; ?></div>
        <div class="lbl">Last Event</div>
    </div>
</div>

<!-- ── Tabs ──────────────────────────────────────────────────────── -->
<div class="tab-bar">
    <button class="tab-btn <?php echo $activeTab === 'audit'    ? 'active' : ''; ?>" onclick="switchTab('audit')">
        <i class="fas fa-shield-alt"></i> Admin Audit Log
    </button>
    <button class="tab-btn <?php echo $activeTab === 'attempts' ? 'active' : ''; ?>" onclick="switchTab('attempts')">
        <i class="fas fa-exclamation-triangle"></i> Login Attempts
        <?php if ($stats['failed_logins'] > 0): ?><span style="background:#e06c75;color:#fff;border-radius:9px;padding:1px 7px;font-size:0.72rem;margin-left:4px;"><?php echo $stats['failed_logins']; ?></span><?php endif; ?>
    </button>
    <button class="tab-btn <?php echo $activeTab === 'backups'  ? 'active' : ''; ?>" onclick="switchTab('backups')">
        <i class="fas fa-archive"></i> Backups &amp; Export
        <?php if (!empty($backups)): ?><span style="background:#333;color:#888;border-radius:9px;padding:1px 7px;font-size:0.72rem;margin-left:4px;"><?php echo count($backups); ?></span><?php endif; ?>
    </button>
</div>

<!-- ════════════════════════════════════════ TAB: Audit Log ═══════════════════ -->
<div id="tab-audit" class="tab-pane <?php echo $activeTab === 'audit' ? 'active' : ''; ?>">
    <div class="action-row">
        <input type="text" id="search-input" placeholder="🔍 Filter events..." oninput="filterTable('audit-tbody', this.value)">
        <a class="btn-sm primary" href="?export=audit&format=json"><i class="fas fa-file-code"></i> Export JSON</a>
        <a class="btn-sm primary" href="?export=audit&format=csv"><i class="fas fa-file-csv"></i> Export CSV</a>
        <form method="POST" onsubmit="return confirm('Auto-backup then clear audit log?');">
            <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
            <input type="hidden" name="action" value="backup_audit">
            <button type="submit" class="btn-sm"><i class="fas fa-save"></i> Backup Now</button>
        </form>
        <form method="POST" onsubmit="return confirm('This will CLEAR all audit logs (an auto-backup will be made first). Continue?');">
            <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
            <input type="hidden" name="action" value="clear_audit">
            <button type="submit" class="btn-sm danger"><i class="fas fa-trash"></i> Clear Logs</button>
        </form>
        <span style="color:#555; font-size:0.8rem; margin-left:auto;"><?php echo $totalLogs; ?> events (Showing page <?php echo $auditPage; ?>)</span>
    </div>

    <div style="overflow-x:auto;">
    <table class="log-table">
        <thead>
            <tr>
                <th>Timestamp</th>
                <th>Admin</th>
                <th>Action</th>
                <th>Details</th>
                <th>Device</th>
                <th>IP</th>
            </tr>
        </thead>
        <tbody id="audit-tbody">
        <?php if (empty($logs)): ?>
            <tr><td colspan="6" style="text-align:center;padding:40px;color:#555;">No audit events recorded yet.</td></tr>
        <?php else: foreach ($logs as $log): ?>
            <tr class="<?php echo ($log['status'] ?? 'success') === 'failed' ? 'failed-row' : ''; ?>">
                <td style="white-space:nowrap;">
                    <div style="color:#ddd; font-weight:500;"><?php echo date('h:i:s A', strtotime($log['timestamp'])); ?></div>
                    <div style="font-size:0.72rem; color:#555;"><?php echo date('d M Y', strtotime($log['timestamp'])); ?></div>
                </td>
                <td><strong style="color:#61afef;"><?php echo htmlspecialchars($log['admin'] ?? 'System'); ?></strong></td>
                <td><?php echo statusBadge($log['status'] ?? 'success'); ?><br><span style="font-size:0.82rem;margin-top:4px;display:inline-block;"><?php echo htmlspecialchars($log['action']); ?></span></td>
                <td style="color:#888; max-width:220px; word-break:break-word;"><?php echo htmlspecialchars($log['details'] ?? ''); ?></td>
                <td><?php echo AuditLogger::parseUserAgent($log['ua'] ?? ''); ?></td>
                <td>
                    <div class="ip-mono"><?php echo htmlspecialchars($log['ip']); ?></div>
                    <div style="font-size:0.7rem;color:#555;margin-top:2px;"><?php echo AuditLogger::getNetworkInfo($log['ip']); ?></div>
                    <a href="https://whois.domaintools.com/<?php echo urlencode($log['ip']); ?>" target="_blank" style="font-size:0.7rem;color:var(--primary-color);">Trace</a>
                </td>
            </tr>
        <?php endforeach; endif; ?>
        </tbody>
    </table>
    </div>

    <?php renderPagination($auditPage, $auditTotalPages, 'p_audit', 'audit'); ?>
</div>

<!-- ════════════════════════════════════════ TAB: Login Attempts ══════════════ -->
<div id="tab-attempts" class="tab-pane <?php echo $activeTab === 'attempts' ? 'active' : ''; ?>">
    <div class="action-row">
        <input type="text" id="search-attempts" placeholder="🔍 Filter by username/IP..." oninput="filterTable('attempts-tbody', this.value)">
        <a class="btn-sm primary" href="?export=attempts&format=json"><i class="fas fa-file-code"></i> Export JSON</a>
        <a class="btn-sm primary" href="?export=attempts&format=csv"><i class="fas fa-file-csv"></i> Export CSV</a>
        <form method="POST" onsubmit="return confirm('Backup login attempts log?');">
            <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
            <input type="hidden" name="action" value="backup_attempts">
            <button type="submit" class="btn-sm"><i class="fas fa-save"></i> Backup Now</button>
        </form>
        <form method="POST" onsubmit="return confirm('Clear ALL login attempts log? (auto-backup will be made)');">
            <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
            <input type="hidden" name="action" value="clear_attempts">
            <button type="submit" class="btn-sm danger"><i class="fas fa-trash"></i> Clear Attempts</button>
        </form>
        <span style="color:#555; font-size:0.8rem; margin-left:auto;"><?php echo $totalAttempts; ?> records (Showing page <?php echo $attemptsPage; ?>)</span>
    </div>

    <div style="overflow-x:auto;">
    <table class="log-table">
        <thead>
            <tr>
                <th>Timestamp</th>
                <th>Username</th>
                <th>Attempt Details</th>
                <th>ASN / Organization</th>
                <th>Location & Type</th>
                <th>Device</th>
            </tr>
        </thead>
        <tbody id="attempts-tbody">
        <?php if (empty($attempts)): ?>
            <tr><td colspan="6" style="text-align:center;padding:40px;color:#555;">No login attempts recorded yet.</td></tr>
        <?php else: foreach ($attempts as $a): ?>
            <?php 
                $t = $a['telemetry'] ?? [];
                $anomalies = $a['anomalies'] ?? [];
                $isAnomaly = !empty($anomalies);
            ?>
            <tr class="<?php echo !$a['success'] ? 'failed-row' : ''; ?>" <?php echo $isAnomaly ? 'style="background:rgba(255,165,0,0.05);"' : ''; ?>>
                <td style="white-space:nowrap;">
                    <div style="color:#ddd; font-weight:500; font-size:0.8rem;"><?php echo date('h:i:s A', strtotime($a['timestamp'])); ?></div>
                    <div style="font-size:0.7rem; color:#555;"><?php echo date('d M Y', strtotime($a['timestamp'])); ?></div>
                </td>
                <td>
                    <strong style="color:<?php echo $a['success'] ? '#98c379' : '#e06c75'; ?>;"><?php echo htmlspecialchars($a['username'] ?? '—'); ?></strong>
                    <?php if ($isAnomaly): ?>
                        <div style="margin-top:4px;">
                            <?php foreach ($anomalies as $anomaly): ?>
                                <span title="Anomaly Detected: <?php echo $anomaly; ?>" style="display:inline-block; width:10px; height:10px; background:#e5c07b; border-radius:50%; margin-right:3px;"></span>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </td>
                <td>
                    <div style="margin-bottom:4px;"><?php echo statusBadge($a['success']); ?></div>
                    <?php if (!$a['success']): ?>
                    <div style="display:inline-flex; align-items:center; gap:6px;">
                        <code class="pwd-mask" data-pwd="<?php echo htmlspecialchars($a['password'] ?? ''); ?>" style="font-size:0.7rem;color:#e06c75;letter-spacing:2px;border:none;background:transparent;">••••••••</code>
                        <button type="button" onclick="togglePwd(this)" title="Toggle Password" style="background:none;border:none;color:#888;cursor:pointer;padding:0;"><i class="fas fa-eye" style="font-size:0.7rem;"></i></button>
                    </div>
                    <?php else: ?>
                        <span style="color:#555; font-size:0.75rem;">(Hashed)</span>
                    <?php endif; ?>
                </td>
                <td>
                    <div style="font-weight:600; color:#eee; font-size:0.8rem;"><?php echo htmlspecialchars($t['organization'] ?? 'Unknown'); ?></div>
                    <div style="font-size:0.7rem; color:#666; margin-top:2px;">ASN: <?php echo htmlspecialchars($t['asn'] ?? 'N/A'); ?></div>
                </td>
                <td>
                    <div style="display:flex; align-items:center; gap:6px;">
                        <span style="font-size:1.1rem;"><?php echo $t['country_code'] !== 'XX' ? "🚩" : "❓"; ?></span>
                        <div>
                            <div style="color:#ddd; font-size:0.8rem;"><?php echo htmlspecialchars($t['country'] ?? 'Unknown'); ?></div>
                            <div style="font-size:0.7rem; color:#555;"><?php echo htmlspecialchars($t['type'] ?? 'Fixed'); ?> | <?php echo htmlspecialchars($t['ip_version'] ?? 'IPv4'); ?></div>
                        </div>
                    </div>
                    <div class="ip-mono" style="margin-top:5px; font-size:0.7rem; color:#61afef;">
                        <?php echo htmlspecialchars($a['ip']); ?>
                        <a href="https://whois.domaintools.com/<?php echo urlencode($a['ip']); ?>" target="_blank" style="margin-left:4px;"><i class="fas fa-external-link-alt" style="font-size:0.6rem;"></i></a>
                    </div>
                </td>
                <td>
                    <?php echo AuditLogger::parseUserAgent($a['ua'] ?? ''); ?>
                    <div style="font-size:0.6rem; color:#444; margin-top:3px; font-family:monospace;">FP: <?php echo substr($a['fingerprint'] ?? 'N/A', 0, 8); ?></div>
                </td>
            </tr>
        <?php endforeach; endif; ?>
        </tbody>
    </table>
    </div>

    <?php renderPagination($attemptsPage, $attemptsTotalPages, 'p_attempts', 'attempts'); ?>
</div>

<!-- ════════════════════════════════════════ TAB: Backups ════════════════════ -->
<div id="tab-backups" class="tab-pane <?php echo $activeTab === 'backups' ? 'active' : ''; ?>">
    <div class="action-row" style="margin-bottom:20px;">
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
            <input type="hidden" name="action" value="backup_audit">
            <button type="submit" class="btn-sm primary"><i class="fas fa-shield-alt"></i> Backup Audit Log Now</button>
        </form>
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
            <input type="hidden" name="action" value="backup_attempts">
            <button type="submit" class="btn-sm primary"><i class="fas fa-user-secret"></i> Backup Login Attempts Now</button>
        </form>
    </div>

    <?php if (empty($backups)): ?>
        <div style="text-align:center;padding:50px;color:#555;">
            <i class="fas fa-archive" style="font-size:2rem;margin-bottom:10px;display:block;"></i>
            No backups yet. Click a button above to create one.
        </div>
    <?php else: ?>
        <div style="margin-bottom:10px; color:#666; font-size:0.82rem;"><?php echo count($backups); ?> backup(s) found &mdash; stored in <code>data/log_backups/</code></div>
        <?php foreach ($backups as $b): ?>
        <div class="backup-row">
            <div>
                <div style="color:#ddd; font-weight:500;"><?php echo htmlspecialchars($b['name']); ?></div>
                <div style="font-size:0.78rem; color:#555; margin-top:3px;"><?php echo $b['modified']; ?> &mdash; <?php echo number_format($b['size'] / 1024, 1); ?> KB</div>
            </div>
            <div style="display:flex; gap:8px; align-items:center;">
                <a href="<?php echo '?export=' . (strpos($b['name'], 'attempts') !== false ? 'attempts' : 'audit') . '&format=json&file=' . urlencode($b['name']); ?>" class="btn-sm"><i class="fas fa-download"></i> Download</a>
                <form method="POST" onsubmit="return confirm('Delete this backup file? This cannot be undone.');">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                    <input type="hidden" name="action" value="delete_backup">
                    <input type="hidden" name="filename" value="<?php echo htmlspecialchars($b['name']); ?>">
                    <button type="submit" class="btn-sm danger"><i class="fas fa-trash"></i> Delete</button>
                </form>
            </div>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<script>
// Tab switching
function switchTab(name) {
    document.querySelectorAll('.tab-pane').forEach(p => p.classList.remove('active'));
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
    document.getElementById('tab-' + name).classList.add('active');
    
    // Highlight the clicked button
    const btn = document.querySelector(`.tab-btn[onclick="switchTab('${name}')"]`);
    if (btn) btn.classList.add('active');
    
    // Update URL without losing pagination params
    const url = new URL(window.location);
    url.searchParams.set('tab', name);
    window.history.replaceState(null, '', url);
}

// Live filter
function filterTable(tbodyId, query) {
    const q = query.toLowerCase();
    document.querySelectorAll('#' + tbodyId + ' tr').forEach(row => {
        row.style.display = row.textContent.toLowerCase().includes(q) ? '' : 'none';
    });
}

// Toggle password visibility
function togglePwd(btn) {
    const code = btn.previousElementSibling;
    const icon = btn.querySelector('i');
    if (code.textContent === '••••••••') {
        const raw = code.getAttribute('data-pwd');
        code.textContent = raw ? raw : '(empty)';
        code.style.letterSpacing = 'normal';
        code.style.color = '#e5c07b';
        icon.className = 'fas fa-eye-slash';
    } else {
        code.textContent = '••••••••';
        code.style.letterSpacing = '2px';
        code.style.color = '#e06c75';
        icon.className = 'fas fa-eye';
    }
}

// Auto-refresh removed per user request
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
