<?php
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/../helpers/AdminAuth.php';

// Ensure we are using the new auth system
if (!isset($_SESSION['admin_token'])) {
    // Fallback or force relogin
    header('Location: logout.php');
    exit;
}

$auth = new AdminAuth();
$sessionData = $auth->validateSession($_SESSION['admin_token']);

if (!$sessionData) {
    header('Location: logout.php');
    exit;
}

$adminId = $sessionData['admin_id'];
// Get admin data from session or config, since we are using file-based auth now
$admin = [
    'username' => $sessionData['username'],
    'display_name' => $sessionData['display_name'],
    'email' => $sessionData['email'],
    'avatar' => $sessionData['avatar'],
    'password_hash' => $sessionData['password_hash']
];
// $admin = $auth->db->getConnection()->query("SELECT * FROM admins WHERE id = $adminId")->fetch();
$currentSessionInfo = $sessionData;

// Handle Form Submissions
$message = '';
$msgType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $ip = $_SERVER['REMOTE_ADDR'];
    $ua = $_SERVER['HTTP_USER_AGENT'];

    if ($action === 'update_profile') {
        $data = [
            'display_name' => $_POST['display_name'],
            'email' => $_POST['email'],
            'username' => $admin['username'], // Keep existing unless changed via specific form
            'avatar' => $_POST['avatar']
        ];
        
        // Basic validation
        if ($auth->updateProfile($adminId, $data, $ip, $ua)) {
            $message = "Profile updated successfully.";
            $msgType = "success";
            // Refresh admin data
            // Refresh admin data logic handled by session/config reload on next page load
            // For now, manually update local array to reflect changes immediately
            $admin = array_merge($admin, $data);
        } else {
            $message = "Failed to update profile.";
            $msgType = "error";
        }
    }
    elseif ($action === 'change_username') {
        $newUsername = trim($_POST['new_username']);
        $password = $_POST['password_confirm'];
        
        // Verify password first
        if (password_verify($password, $admin['password_hash'])) {
            // Check uniqueness
            // Check uniqueness (File based: we only have 1 admin, so just check against current?)
            // If we support multiple admins in file (unlikely for now), we would check config.
            // Since there is only one admin, we just proceed.
            if (false) { 
                 // Placeholder if we ever add multi-admin file support
            } else {
                $data = [
                    'display_name' => $admin['display_name'],
                    'email' => $admin['email'],
                    'username' => $newUsername,
                    'avatar' => $admin['avatar']
                ];
                if ($auth->updateProfile($adminId, $data, $ip, $ua)) {
                    $message = "Username changed. You need to login again.";
                    $msgType = "success";
                    // Update local var just in case
                    $admin['username'] = $newUsername;
                }
            }
        } else {
            $message = "Incorrect password.";
            $msgType = "error";
        }
    }
    elseif ($action === 'change_password') {
        $old = $_POST['old_password'];
        $new = $_POST['new_password'];
        $confirm = $_POST['confirm_password'];

        if ($new !== $confirm) {
            $message = "New passwords do not match.";
            $msgType = "error";
        } elseif (strlen($new) < 8) {
            $message = "Password must be at least 8 characters.";
            $msgType = "error";
        } else {
            if ($auth->changePassword($adminId, $old, $new, $ip, $ua)) {
                $message = "Password changed. All other sessions invalidated.";
                $msgType = "success";
            } else {
                $message = "Incorrect old password.";
                $msgType = "error";
            }
        }
    }
    elseif ($action === 'logout_session') {
        $tokenToKill = $_POST['session_token'];
        $auth->logout($tokenToKill);
        $message = "Session terminated.";
        $msgType = "success";
        if ($tokenToKill === $_SESSION['admin_token']) {
            header('Location: login.php');
            exit;
        }
    }
    elseif ($action === 'logout_all') {
        $auth->logoutAll($adminId);
        header('Location: login.php');
        exit;
    }
}

// Fetch Lists
$activeSessions = $auth->getActiveSessions($adminId);
$activityLog = $auth->getActivityLog($adminId);

?>

<style>
    .profile-container {
        display: grid;
        grid-template-columns: 250px 1fr;
        gap: 20px;
        margin-top: 20px;
    }
    .profile-sidebar {
        background: rgba(255,255,255,0.03);
        border-radius: 8px;
        overflow: hidden;
    }
    .tab-btn {
        display: block;
        width: 100%;
        padding: 15px 20px;
        text-align: left;
        background: none;
        border: none;
        color: #aaa;
        cursor: pointer;
        transition: 0.3s;
        border-bottom: 1px solid rgba(255,255,255,0.05);
    }
    .tab-btn:hover, .tab-btn.active {
        background: rgba(255,255,255,0.05);
        color: var(--accent-color);
        border-left: 3px solid var(--accent-color);
    }
    .tab-content {
        display: none;
    }
    .tab-content.active {
        display: block;
    }
    .card {
        background: rgba(255,255,255,0.03);
        padding: 25px;
        border-radius: 8px;
        margin-bottom: 20px;
        border: 1px solid rgba(255,255,255,0.05);
    }
    .session-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 15px;
        border-bottom: 1px solid rgba(255,255,255,0.05);
    }
    .session-item:last-child { border-bottom: none; }
    .badge {
        padding: 4px 8px;
        border-radius: 4px;
        font-size: 0.8em;
    }
    .badge-success { background: rgba(0,255,157,0.1); color: #00ff9d; }
    .log-table { width: 100%; border-collapse: collapse; }
    .log-table th, .log-table td { padding: 10px; text-align: left; border-bottom: 1px solid rgba(255,255,255,0.05); }
    .log-table th { color: #888; font-weight: normal; }
    .danger-zone { border: 1px solid rgba(255, 107, 107, 0.3); }
    .danger-zone h3 { color: #ff6b6b; }
</style>

<div class="section-header">
    <h1>Account Settings</h1>
    <span class="badge badge-success">Admin</span>
</div>

<?php if ($message): ?>
    <div class="<?php echo $msgType; ?>-msg" style="margin-bottom: 20px;"><?php echo htmlspecialchars($message); ?></div>
<?php endif; ?>

<div class="profile-container">
    <div class="profile-sidebar">
        <button class="tab-btn active" onclick="openTab('overview')"><i class="fas fa-id-card"></i> Overview</button>
        <button class="tab-btn" onclick="openTab('edit')"><i class="fas fa-user-edit"></i> Edit Profile</button>
        <button class="tab-btn" onclick="openTab('security')"><i class="fas fa-lock"></i> Security</button>
        <button class="tab-btn" onclick="openTab('sessions')"><i class="fas fa-desktop"></i> Sessions</button>
        <button class="tab-btn" onclick="openTab('activity')"><i class="fas fa-history"></i> Activity Log</button>
    </div>

    <div class="profile-content">
        <!-- OVERVIEW -->
        <div id="overview" class="tab-content active">
            <div class="card" style="display: flex; gap: 20px; align-items: center;">
                <div style="width: 100px; height: 100px; border-radius: 50%; overflow: hidden; background: #222;">
                    <?php if ($admin['avatar']): ?>
                        <img src="<?php echo htmlspecialchars($admin['avatar']); ?>" style="width:100%; height:100%; object-fit:cover;">
                    <?php else: ?>
                        <div style="width:100%; height:100%; display:flex; align-items:center; justify-content:center; font-size: 30px; color: #555;">
                            <i class="fas fa-user"></i>
                        </div>
                    <?php endif; ?>
                </div>
                <div>
                    <h2><?php echo htmlspecialchars($admin['display_name']); ?></h2>
                    <p style="color: #888;">@<?php echo htmlspecialchars($admin['username']); ?></p>
                    <p style="margin-top: 5px;"><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($admin['email']); ?></p>
                </div>
            </div>

            <div class="card">
                <h3>Current Session</h3>
                <div style="margin-top: 15px; display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                    <div>
                        <small style="color:#888;">IP Address</small>
                        <div><?php echo htmlspecialchars($currentSessionInfo['ip_address']); ?></div>
                    </div>
                    <div>
                        <small style="color:#888;">Device</small>
                        <div style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"><?php echo htmlspecialchars($currentSessionInfo['user_agent']); ?></div>
                    </div>
                    <div>
                        <small style="color:#888;">Logged in</small>
                        <div><?php echo time_ago($currentSessionInfo['created_at']); ?></div>
                    </div>
                    <div>
                        <small style="color:#888;">Last Activity</small>
                        <div><?php echo time_ago($currentSessionInfo['last_activity']); ?></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- EDIT PROFILE -->
        <div id="edit" class="tab-content">
            <div class="card">
                <h3>Edit Details</h3>
                <form method="POST">
                    <input type="hidden" name="action" value="update_profile">
                    
                    <div class="form-group">
                        <label>Display Name</label>
                        <input type="text" name="display_name" value="<?php echo htmlspecialchars($admin['display_name']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Email Address</label>
                        <input type="email" name="email" value="<?php echo htmlspecialchars($admin['email']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label>Avatar URL</label>
                        <div style="display:flex; gap:10px;">
                            <input type="text" id="admin_avatar" name="avatar" value="<?php echo htmlspecialchars($admin['avatar']); ?>" placeholder="Select or enter URL...">
                            <button type="button" class="btn-edit" style="width:auto; white-space:nowrap;" onclick="openMediaPicker('admin_avatar')">Select Photo</button>
                        </div>
                        <?php if(!empty($admin['avatar'])): ?>
                            <div style="margin-top:10px;">
                                <img src="<?php echo htmlspecialchars($admin['avatar']); ?>" style="width:60px; height:60px; border-radius:4px; object-fit:cover; border:1px solid #444;">
                            </div>
                        <?php endif; ?>
                    </div>

                    <button type="submit" class="btn-edit">Update Profile</button>
                </form>
            </div>
        </div>

        <!-- SECURITY -->
        <div id="security" class="tab-content">
            <div class="card">
                <h3>Change Password</h3>
                <form method="POST">
                    <input type="hidden" name="action" value="change_password">
                    
                    <div class="form-group">
                        <label>Current Password</label>
                        <input type="password" name="old_password" required>
                    </div>
                    
                    <div class="form-group">
                        <label>New Password</label>
                        <input type="password" name="new_password" required minlength="8">
                    </div>

                    <div class="form-group">
                        <label>Confirm New Password</label>
                        <input type="password" name="confirm_password" required>
                    </div>

                    <button type="submit" class="btn-login">Change Password</button>
                </form>
            </div>

            <div class="card danger-zone">
                <h3><i class="fas fa-exclamation-triangle"></i> Administrative</h3>
                <p style="margin-bottom: 20px; color: #bbb;">Changing your username will require re-login.</p>
                
                <form method="POST" style="margin-bottom: 20px;">
                    <input type="hidden" name="action" value="change_username">
                    <div class="form-group">
                        <label>New Username</label>
                        <input type="text" name="new_username" value="<?php echo htmlspecialchars($admin['username']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Confirm Password</label>
                        <input type="password" name="password_confirm" required placeholder="Enter password to confirm">
                    </div>
                    <button type="submit" class="btn-edit" style="border-color: #ff6b6b; color: #ff6b6b;">Update Username</button>
                </form>
            </div>
        </div>

        <!-- SESSIONS -->
        <div id="sessions" class="tab-content">
            <div class="card">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
                    <h3>Active Sessions</h3>
                    <form method="POST" onsubmit="return confirm('Are you sure you want to log out all devices?');">
                        <input type="hidden" name="action" value="logout_all">
                        <button type="submit" class="btn-remove">Log Out All Devices</button>
                    </form>
                </div>

                <?php foreach ($activeSessions as $s): ?>
                    <div class="session-item">
                        <div>
                            <div style="font-weight: bold;">
                                <?php echo htmlspecialchars($s['ip_address']); ?>
                                <?php if ($s['session_token'] === $_SESSION['admin_token']): ?>
                                    <span class="badge badge-success">Current</span>
                                <?php endif; ?>
                            </div>
                            <div style="font-size: 0.9em; color: #888; margin-top: 5px;">
                                <?php echo htmlspecialchars($s['user_agent']); ?>
                            </div>
                            <div style="font-size: 0.8em; color: #666; margin-top: 5px;">
                                Last active: <?php echo time_ago($s['last_activity']); ?>
                            </div>
                        </div>
                        <?php if ($s['session_token'] !== $_SESSION['admin_token']): ?>
                            <form method="POST">
                                <input type="hidden" name="action" value="logout_session">
                                <input type="hidden" name="session_token" value="<?php echo $s['session_token']; ?>">
                                <button type="submit" class="btn-remove" style="padding: 5px 10px; font-size: 0.8em;">Revoke</button>
                            </form>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- ACTIVITY -->
        <div id="activity" class="tab-content">
            <div class="card">
                <h3>Recent Activity</h3>
                <table class="log-table">
                    <thead>
                        <tr>
                            <th>Time</th>
                            <th>Action</th>
                            <th>Details</th>
                            <th>IP</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($activityLog as $log): ?>
                            <tr>
                                <td><?php echo time_ago($log['created_at']); ?></td>
                                <td><span class="badge" style="background:rgba(255,255,255,0.1);"><?php echo htmlspecialchars($log['action']); ?></span></td>
                                <td><?php echo htmlspecialchars($log['details']); ?></td>
                                <td style="font-size:0.9em; color:#888;"><?php echo htmlspecialchars($log['ip_address']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
function openTab(tabName) {
    // Hide all tabs
    document.querySelectorAll('.tab-content').forEach(el => el.classList.remove('active'));
    document.querySelectorAll('.tab-btn').forEach(el => el.classList.remove('active'));
    
    // Show selected
    document.getElementById(tabName).classList.add('active');
    // Highlight button - find button with onclick matching
    document.querySelector(`button[onclick="openTab('${tabName}')"]`).classList.add('active');
}

// Helper for relative time (simple JS version for live updates if needed, but PHP is handling it now)
</script>

<?php
function time_ago($timestamp) {
    if (!ctype_digit($timestamp)) {
        $timestamp = strtotime($timestamp);
    }
    $diff = time() - $timestamp;
    
    if ($diff < 60) return "Just now";
    if ($diff < 3600) return floor($diff/60) . "m ago";
    if ($diff < 86400) return floor($diff/3600) . "h ago";
    return date("M j, Y", $timestamp);
}

require_once __DIR__ . '/includes/media-picker.php';
require_once __DIR__ . '/includes/footer.php';
?>
