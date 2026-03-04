<?php
/**
 * manage-assets.php - Admin panel view for managing the /assets directory
 */
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/../helpers/AuditLogger.php';

$audit = new AuditLogger();
$assetsDir = realpath(__DIR__ . '/../assets/');
$flash = getFlashMessage();

// Security Check: ensure assets dir exists
if (!is_dir($assetsDir)) {
    mkdir($assetsDir, 0755, true);
}

// Handle File Delete
if (isset($_GET['delete'])) {
    $token = $_GET['csrf_token'] ?? '';
    if (!validateCSRFToken($token)) {
        die('CSRF token validation failed.');
    }

    $fileToDelete = basename($_GET['delete']); // prevent directory traversal
    $filePath = $assetsDir . '/' . $fileToDelete;
    
    // Check if it's a file and within the assets directory
    if (is_file($filePath) && strpos(realpath($filePath), $assetsDir) === 0) {
        if (unlink($filePath)) {
            $audit->log("Delete Asset", "File: $fileToDelete");
            setFlashMessage("File '$fileToDelete' deleted successfully!");
        } else {
            $audit->log("Delete Asset Failed", "File: $fileToDelete", "failed");
            setFlashMessage("Failed to delete file '$fileToDelete'.", "error");
        }
    } else {
        setFlashMessage("Invalid file specified for deletion.", "error");
    }
    header('Location: manage-assets.php');
    exit;
}

// Handle File Upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['asset_file'])) {
    $token = $_POST['csrf_token'] ?? '';
    if (!validateCSRFToken($token)) {
        die('CSRF token validation failed.');
    }

    $uploadResult = handleFileUpload($_FILES['asset_file'], '../assets/');
    if (is_array($uploadResult) && isset($uploadResult['error'])) {
        $audit->log("Upload Asset Failed", "Error: " . $uploadResult['error'], "failed");
        setFlashMessage($uploadResult['error'], 'error');
    } else {
        $audit->log("Upload Asset", "File: " . basename($uploadResult));
        setFlashMessage('File uploaded successfully!');
    }
    header('Location: manage-assets.php');
    exit;
}

// Get all files in assets dir
$files = [];
$iterator = new DirectoryIterator($assetsDir);
foreach ($iterator as $fileinfo) {
    if (!$fileinfo->isDot() && $fileinfo->isFile()) {
        $files[] = [
            'name' => $fileinfo->getFilename(),
            'path' => 'assets/' . $fileinfo->getFilename(), // relative to root
            'size' => $fileinfo->getSize(),
            'modified' => $fileinfo->getMTime(),
            'extension' => strtolower($fileinfo->getExtension())
        ];
    }
}

// Sort alphabetically by name
usort($files, function($a, $b) {
    return strcasecmp($a['name'], $b['name']);
});

// Format bytes
function formatBytes($bytes, $precision = 2) { 
    $units = array('B', 'KB', 'MB', 'GB', 'TB'); 
    $bytes = max($bytes, 0); 
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024)); 
    $pow = min($pow, count($units) - 1); 
    $bytes /= (1 << (10 * $pow)); 
    return round($bytes, $precision) . ' ' . $units[$pow]; 
} 

?>

<div class="section-header" style="margin-bottom: 30px;">
    <h1>Manage Assets</h1>
    <div style="display:flex; gap:10px;">
        <a href="index.php" class="btn-edit"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
    </div>
</div>

<?php if ($flash): ?>
    <div class="<?php echo $flash['type']; ?>-msg"><?php echo $flash['message']; ?></div>
<?php endif; ?>

<div class="dashboard-grid" style="grid-template-columns: 1fr;">
    <!-- Upload Section -->
    <div class="stat-card" style="margin-bottom: 30px;">
        <h3><i class="fas fa-upload"></i> Upload New Asset</h3>
        <p style="margin-bottom:15px; color:#888;">Allowed formats: JPG, PNG, WEBP, GIF, SVG</p>
        <form method="POST" enctype="multipart/form-data" style="display:flex; gap:10px; align-items:center;">
            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
            <input type="file" name="asset_file" required style="flex:1; padding: 10px; background: #222; border: 1px solid #444; border-radius: 4px; color: white;">
            <button type="submit" class="btn-login" style="width: auto;"><i class="fas fa-cloud-upload-alt"></i> Upload File</button>
        </form>
    </div>

    <!-- Files List -->
    <div class="editor-card">
        <h3 style="margin-bottom: 20px;"><i class="fas fa-folder-open"></i> Assets Folder Contents (<?php echo count($files); ?> files)</h3>
        
        <table style="width:100%; border-collapse: collapse;">
            <thead>
                <tr style="border-bottom: 1px solid #444; text-align: left;">
                    <th style="padding: 15px;">Preview / Icon</th>
                    <th style="padding: 15px;">Filename</th>
                    <th style="padding: 15px;">Size</th>
                    <th style="padding: 15px;">Last Modified</th>
                    <th style="padding: 15px; text-align: right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($files)): ?>
                    <tr>
                        <td colspan="5" style="padding: 30px; text-align: center; color: #888;">No files found in the assets folder.</td>
                    </tr>
                <?php else: ?>
                    <?php 
                    $imageExts = ['jpg', 'jpeg', 'png', 'webp', 'gif', 'svg'];
                    foreach ($files as $file): 
                        $isImage = in_array($file['extension'], $imageExts);
                    ?>
                        <tr style="border-bottom: 1px solid #222;">
                            <td style="padding: 15px; width: 80px;">
                                <?php if ($isImage): ?>
                                    <div style="width: 50px; height: 50px; background: #333; border-radius: 4px; display:flex; align-items:center; justify-content:center; overflow: hidden;">
                                        <img src="../<?php echo htmlspecialchars($file['path']); ?>" style="width: 100%; height: 100%; object-fit: cover;">
                                    </div>
                                <?php else: ?>
                                    <div style="width: 50px; height: 50px; background: #333; border-radius: 4px; display:flex; align-items:center; justify-content:center; color:#888; font-size: 24px;">
                                        <i class="fas fa-file"></i>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td style="padding: 15px;">
                                <strong><?php echo htmlspecialchars($file['name']); ?></strong>
                                <br><small style="color: #61afef; cursor: pointer;" onclick="navigator.clipboard.writeText('<?php echo htmlspecialchars($file['path']); ?>').then(() => alert('Copied path to clipboard!'))">
                                    <i class="fas fa-copy"></i> <?php echo htmlspecialchars($file['path']); ?>
                                </small>
                            </td>
                            <td style="padding: 15px; font-family: monospace; color: #888;">
                                <?php echo formatBytes($file['size']); ?>
                            </td>
                            <td style="padding: 15px; color: #888;">
                                <?php echo date('M d, Y H:i', $file['modified']); ?>
                            </td>
                            <td style="padding: 15px; text-align: right;">
                                <a href="../<?php echo htmlspecialchars($file['path']); ?>" target="_blank" class="btn-edit" style="margin-right: 10px;" title="View in new tab"><i class="fas fa-external-link-alt"></i></a>
                                <a href="manage-assets.php?delete=<?php echo urlencode($file['name']); ?>&csrf_token=<?php echo generateCSRFToken(); ?>" class="btn-remove" style="position:static;" onclick="return confirm('Are you sure you want to permanently delete \'<?php echo htmlspecialchars($file['name']); ?>\'? This action cannot be undone.')"><i class="fas fa-trash"></i></a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
