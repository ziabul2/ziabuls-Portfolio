<?php
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/../helpers/LeadManager.php';
require_once __DIR__ . '/../helpers/AuditLogger.php';

$leadManager = new LeadManager();
$audit = new AuditLogger();
$flash = getFlashMessage();

// Handle Actions
if (isset($_GET['action'])) {
    $token = $_GET['csrf_token'] ?? '';
    if (!validateCSRFToken($token)) {
        die('CSRF token validation failed.');
    }

    $id = $_GET['id'] ?? '';
    if ($_GET['action'] === 'delete') {
        if ($leadManager->deleteLead($id)) {
            $audit->log("Delete Lead", "ID: $id");
            setFlashMessage('Message deleted successfully!');
        } else {
            setFlashMessage('Error deleting message.', 'error');
        }
    } elseif ($_GET['action'] === 'read') {
        $leadManager->markAsRead($id);
    }
    header('Location: manage-leads.php');
    exit;
}

$leads = $leadManager->getLeads();
?>

<div class="section-header" style="margin-bottom: 30px;">
    <h1><i class="fas fa-inbox"></i> Unified Lead Manager</h1>
    <a href="index.php" class="btn-edit"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
</div>

<?php if ($flash): ?>
    <div class="<?php echo $flash['type']; ?>-msg"><?php echo $flash['message']; ?></div>
<?php endif; ?>

<div class="editor-card">
    <div style="overflow-x: auto;">
        <table style="width: 100%; border-collapse: collapse;">
            <thead>
                <tr style="border-bottom: 1px solid #444; text-align: left;">
                    <th style="padding: 15px;">Status</th>
                    <th style="padding: 15px;">Sender</th>
                    <th style="padding: 15px;">Subject / Message</th>
                    <th style="padding: 15px;">Date</th>
                    <th style="padding: 15px; text-align: right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($leads)): ?>
                    <tr>
                        <td colspan="5" style="padding: 30px; text-align: center; color: #888;">No leads found.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($leads as $lead): ?>
                        <tr style="border-bottom: 1px solid #222; background: <?php echo $lead['status'] === 'unread' ? 'rgba(97, 175, 239, 0.03)' : 'transparent'; ?>">
                            <td style="padding: 15px; width: 80px;">
                                <?php if ($lead['status'] === 'unread'): ?>
                                    <span style="background: #e06c75; color: white; padding: 2px 8px; border-radius: 10px; font-size: 0.7rem;">NEW</span>
                                <?php else: ?>
                                    <span style="color: #555;"><i class="fas fa-check"></i></span>
                                <?php endif; ?>
                            </td>
                            <td style="padding: 15px;">
                                <div style="font-weight: bold;"><?php echo htmlspecialchars($lead['name']); ?></div>
                                <div style="font-size: 0.8rem; color: #888;"><?php echo htmlspecialchars($lead['email']); ?></div>
                                <div style="font-size: 0.7rem; color: #555;">IP: <?php echo htmlspecialchars($lead['ip']); ?></div>
                            </td>
                            <td style="padding: 15px; max-width: 400px;">
                                <div style="font-weight: bold; color: var(--accent-color); margin-bottom: 5px;"><?php echo htmlspecialchars($lead['subject']); ?></div>
                                <div style="color: #ccc; font-size: 0.9rem; line-height: 1.4;">
                                    <?php echo nl2br(htmlspecialchars($lead['message'])); ?>
                                </div>
                            </td>
                            <td style="padding: 15px; color: #888; font-size: 0.8rem;">
                                <?php echo date('M d, Y', $lead['timestamp']); ?><br>
                                <?php echo date('H:i', $lead['timestamp']); ?>
                            </td>
                            <td style="padding: 15px; text-align: right;">
                                <?php if ($lead['status'] === 'unread'): ?>
                                    <a href="manage-leads.php?action=read&id=<?php echo $lead['id']; ?>&csrf_token=<?php echo generateCSRFToken(); ?>" class="btn-edit" title="Mark as Read"><i class="fas fa-envelope-open"></i></a>
                                <?php endif; ?>
                                <a href="manage-leads.php?action=delete&id=<?php echo $lead['id']; ?>&csrf_token=<?php echo generateCSRFToken(); ?>" class="btn-remove" style="position:static;" onclick="return confirm('Delete this lead?')" title="Delete"><i class="fas fa-trash"></i></a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
