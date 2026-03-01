<?php
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/../helpers/DatabaseAdmin.php';

$dbAdmin = new DatabaseAdmin();
$status = $dbAdmin->getConnectionStatus();
$flash = getFlashMessage();

// Load Profiles
$profilesFile = __DIR__ . '/../../data/db_profiles.json';
$profiles = file_exists($profilesFile) ? json_decode(file_get_contents($profilesFile), true) : [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'save_config') {
        // Save current form to config/database.php
        $newConfig = [
            'host' => $_POST['host'],
            'database' => $_POST['database'],
            'username' => $_POST['username'],
            'password' => $_POST['password'],
            'charset' => 'utf8mb4',
            'options' => [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]
        ];
        
        if ($dbAdmin->updateConfigFile($newConfig)) {
            setFlashMessage('Database configuration updated & activated!');
            header('Location: database-config.php');
            exit;
        } else {
            setFlashMessage('Failed to update configuration file.', 'error');
        }
    }
    
    elseif ($action === 'save_profile') {
        // Save to profiles.json
        $name = trim($_POST['profile_name']);
        if ($name) {
             $profiles[$name] = [
                'host' => $_POST['host'],
                'database' => $_POST['database'],
                'username' => $_POST['username'],
                'password' => $_POST['password']
             ];
             file_put_contents($profilesFile, json_encode($profiles));
             setFlashMessage("Profile '$name' saved.");
             header('Location: database-config.php');
             exit;
        }
    }
    
    elseif ($action === 'load_profile') {
        // Load profile into active config
        $name = $_POST['profile_name'];
        if (isset($profiles[$name])) {
             $p = $profiles[$name];
             $newConfig = [
                'host' => $p['host'],
                'database' => $p['database'],
                'username' => $p['username'],
                'password' => $p['password'],
                'charset' => 'utf8mb4',
                'options' => [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]
            ];
            $dbAdmin->updateConfigFile($newConfig);
            setFlashMessage("Switched to profile '$name'.");
            header('Location: database-config.php');
            exit;
        }
    }
    
    elseif ($action === 'delete_profile') {
        $name = $_POST['profile_name'];
        if (isset($profiles[$name])) {
            unset($profiles[$name]);
            file_put_contents($profilesFile, json_encode($profiles));
            setFlashMessage("Profile '$name' deleted.");
            header('Location: database-config.php');
            exit;
        }
    }

    elseif ($action === 'create_db') {
        $name = trim($_POST['new_db_name']);
        if ($dbAdmin->createDatabase($name)) {
            setFlashMessage("Database '$name' created successfully!");
        } else {
            setFlashMessage("Failed to create database '$name'. Check permissions.", 'error');
        }
        header('Location: database-config.php');
        exit;
    }
}

// Load Current Config Values for Form
$currentConfig = require __DIR__ . '/../config/database.php';
?>

<div class="section-header" style="margin-bottom: 30px;">
    <h1>Database Connection Manager</h1>
    <a href="index.php" class="btn-edit"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
</div>

<?php if ($flash): ?>
    <div class="<?php echo $flash['type']; ?>-msg"><?php echo $flash['message']; ?></div>
<?php endif; ?>

<div style="display:grid; grid-template-columns: 2fr 1fr; gap: 30px;">
    
    <!-- Left Column: Active Connection -->
    <div>
        <div class="editor-card">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
                <h2>Active Connection</h2>
                <?php if($status['status'] === 'connected'): ?>
                    <span class="badge badge-success" style="background:rgba(0,255,157,0.1); color:#00ff9d; padding:5px 10px; border-radius:4px;">
                        <i class="fas fa-check-circle"></i> Connected
                    </span>
                <?php else: ?>
                    <span class="badge" style="background:rgba(255,107,107,0.1); color:#ff6b6b; padding:5px 10px; border-radius:4px;">
                        <i class="fas fa-times-circle"></i> Error: <?php echo htmlspecialchars($status['message']); ?>
                    </span>
                <?php endif; ?>
            </div>

            <?php if($status['status'] === 'connected'): ?>
                <div style="background:rgba(255,255,255,0.05); padding:15px; border-radius:4px; margin-bottom:20px; font-family:monospace; font-size:0.9em;">
                    Server: <?php echo $status['server_info']; ?><br>
                    Version: <?php echo $status['version']; ?>
                </div>
            <?php endif; ?>

            <form method="POST">
                <input type="hidden" name="action" value="save_config">
                
                <div class="form-group">
                    <label>Datbase Host</label>
                    <input type="text" name="host" value="<?php echo htmlspecialchars($currentConfig['host']); ?>" required>
                </div>
                
                <div class="form-group">
                    <label>Database Name</label>
                    <input type="text" name="database" value="<?php echo htmlspecialchars($currentConfig['database']); ?>" required>
                </div>
                
                <div class="form-group">
                    <label>Username</label>
                    <input type="text" name="username" value="<?php echo htmlspecialchars($currentConfig['username']); ?>" required>
                </div>
                
                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="password" value="<?php echo htmlspecialchars($currentConfig['password']); ?>" placeholder="Leave empty if none">
                </div>

                <div style="display:flex; justify-content:space-between; align-items:center; margin-top:20px;">
                    <button type="submit" class="btn-login" onclick="return confirm('Warning: Changing this will overwrite config/database.php. Continue?')">Update & Connect</button>
                    
                    <!-- Save as Profile Inline -->
                    <div style="display:flex; gap:10px;">
                        <button type="button" class="btn-edit" onclick="document.getElementById('saveProfileBox').style.display='block'">Save as Profile</button>
                    </div>
                </div>
            </form>
            
            <div id="saveProfileBox" style="display:none; margin-top:15px; padding:15px; background:rgba(0,0,0,0.3); border-radius:4px;">
                <form method="POST">
                    <input type="hidden" name="action" value="save_profile">
                    <!-- Echo hidden fields to pass current values to profile save -->
                    <input type="hidden" name="host" id="p_host">
                    <input type="hidden" name="database" id="p_db">
                    <input type="hidden" name="username" id="p_user">
                    <input type="hidden" name="password" id="p_pass">
                    
                    <div style="display:flex; gap:10px;">
                        <input type="text" name="profile_name" placeholder="Profile Name (e.g. Remote DB)" required style="flex:1;">
                        <button type="submit" class="btn-add" onclick="syncFields()">Save</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Right Column: Profiles -->
    <div>
        <div class="editor-card">
            <h2>Saved Profiles</h2>
            <p style="color:#888; font-size:0.9em; margin-bottom:15px;">Switch between environments instantly.</p>
            
            <?php if(empty($profiles)): ?>
                <div style="text-align:center; padding:20px; color:#666;">No profiles saved yet.</div>
            <?php else: ?>
                <div style="display:flex; flex-direction:column; gap:10px;">
                    <?php foreach($profiles as $name => $p): ?>
                        <div style="background:rgba(255,255,255,0.05); padding:15px; border-radius:4px; display:flex; justify-content:space-between; align-items:center;">
                            <div>
                                <strong><?php echo htmlspecialchars($name); ?></strong><br>
                                <span style="font-size:0.8em; color:#888;"><?php echo htmlspecialchars($p['host']); ?> / <?php echo htmlspecialchars($p['database']); ?></span>
                            </div>
                            <div style="display:flex; gap:5px;">
                                <form method="POST">
                                    <input type="hidden" name="action" value="load_profile">
                                    <input type="hidden" name="profile_name" value="<?php echo htmlspecialchars($name); ?>">
                                    <button type="submit" class="btn-edit" title="Load this profile"><i class="fas fa-plug"></i></button>
                                </form>
                                <form method="POST" onsubmit="return confirm('Delete profile?');">
                                    <input type="hidden" name="action" value="delete_profile">
                                    <input type="hidden" name="profile_name" value="<?php echo htmlspecialchars($name); ?>">
                                    <button type="submit" class="btn-remove" style="position:static" title="Delete"><i class="fas fa-trash"></i></button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="editor-card" style="margin-top:20px;">
             <h3>Quick Tools</h3>
             <ul style="list-style:none; padding:0; line-height:2;">
                 <li><a href="database-tables.php" style="color:var(--accent-color); text-decoration:none;"><i class="fas fa-table"></i> Check Tables</a></li>
                 <li><a href="database-tools.php" style="color:var(--accent-color); text-decoration:none;"><i class="fas fa-file-export"></i> Export / Import</a></li>
             </ul>
        </div>
    </div>
</div>

<script>
function syncFields() {
    document.getElementById('p_host').value = document.querySelector('input[name="host"]').value;
    document.getElementById('p_db').value = document.querySelector('input[name="database"]').value;
    document.getElementById('p_user').value = document.querySelector('input[name="username"]').value;
    document.getElementById('p_pass').value = document.querySelector('input[name="password"]').value;
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
