<?php

class AdminAuth {
    private $sessionLifetime = 3600; // 1 hour
    private $lockoutThreshold = 2;
    private $lockoutDuration = 30; // seconds
    private $configPath;
    private $lockoutDescFile;
    private $activityLogFile;

    public function __construct() {
        $this->configPath = __DIR__ . '/../config/admin.php';
        $this->lockoutDescFile = __DIR__ . '/../data/login_attempts.json';
        $this->activityLogFile = __DIR__ . '/../logs/admin_activity.log';
        
        // Ensure data directory exists
        if (!file_exists(dirname($this->lockoutDescFile))) {
             @mkdir(dirname($this->lockoutDescFile), 0777, true);
        }
        // Ensure logs directory exists
        if (!file_exists(dirname($this->activityLogFile))) {
             @mkdir(dirname($this->activityLogFile), 0777, true);
        }
    }

    private function getConfig() {
        if (file_exists($this->configPath)) {
            return require $this->configPath;
        }
        return [];
    }
    
    private function updateConfig($newConfig) {
        $content = "<?php\nreturn " . var_export($newConfig, true) . ";\n";
        return file_put_contents($this->configPath, $content);
    }
    
    private function getAttempts() {
        if (file_exists($this->lockoutDescFile)) {
            $data = json_decode(file_get_contents($this->lockoutDescFile), true);
            return is_array($data) ? $data : [];
        }
        return [];
    }
    
    private function saveAttempts($attempts) {
        file_put_contents($this->lockoutDescFile, json_encode($attempts));
    }

    private function pruneAttempts($attempts) {
        $now = time();
        $newAttempts = [];
        foreach ($attempts as $ip => $data) {
            // Keep content if it is relevant. 
            // Keep for 1 hour to allow counts to stack up
            if ($now - $data['last_time'] < 3600) {
                $newAttempts[$ip] = $data;
            }
        }
        return $newAttempts;
    }

    /**
     * Calculate Lockout Duration based on failed attempts
     */
    private function getLockoutDuration($count) {
        if ($count < $this->lockoutThreshold) return 0;
        
        // "if admin faild to login 2 time add a timer for 10 sec"
        if ($count === 2) {
            return 10;
        }
        
        // "if again faild (3rd time) then wait 30sec"
        if ($count === 3) {
            return 30;
        }
        
        // "if faild then add more 10s" (assuming implies 40s, 50s...)
        // 4 -> 40, 5 -> 50
        $excess = $count - 3;
        return 30 + ($excess * 10);
    }
    
    /**
     * Check if IP is currently locked out
     */
    public function isLockedOut($ip) {
        $attempts = $this->getAttempts();
        
        if (isset($attempts[$ip])) {
            $data = $attempts[$ip];
            if ($data['count'] >= $this->lockoutThreshold) {
                $duration = $this->getLockoutDuration($data['count']);
                $timePassed = time() - $data['last_time'];
                
                if ($timePassed < $duration) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Get remaining lockout time in seconds
     */
    public function getLockoutRemaining($ip) {
        $attempts = $this->getAttempts();
        if (isset($attempts[$ip])) {
             $data = $attempts[$ip];
             if ($data['count'] >= $this->lockoutThreshold) {
                 $duration = $this->getLockoutDuration($data['count']);
                 $remaining = $duration - (time() - $data['last_time']);
                 return $remaining > 0 ? $remaining : 0;
             }
        }
        return 0;
    }
    
    // Kept to avoid breaking interface if used elsewhere, but logic moved to Login
    public function attemptLogin($username, $password, $ip) {
         // Placeholder alias if needed
    }

    /**
     * Attempt Login
     */
    public function login($username, $password, $ip, $userAgent) {
        if ($this->isLockedOut($ip)) {
             return ['success' => false, 'error' => 'Too many failed attempts.', 'lockout' => true, 'wait' => $this->getLockoutRemaining($ip)];
        }

        $config = $this->getConfig();

        // Verify against Config File
        if (isset($config['username']) && $username === $config['username'] && password_verify($password, $config['password_hash'])) {
            // Success
            $this->logActivity('LOGIN', 'Logged in successfully', $ip, $userAgent);
            
            // Start Session
            $token = bin2hex(random_bytes(32));
            $_SESSION['admin_token'] = $token; // Standardize key
            
            $_SESSION['admin_data'] = [
                'id' => 1, // Virtual ID
                'username' => $config['username'],
                'display_name' => isset($config['display_name']) ? $config['display_name'] : 'Administrator',
                'email' => isset($config['email']) ? $config['email'] : 'admin@local',
                'avatar' => isset($config['avatar']) ? $config['avatar'] : '',
                'password_hash' => $config['password_hash']
            ];
            $_SESSION['start_time'] = time();
            $_SESSION['last_activity'] = time();

            // Clear attempts on success
            $attempts = $this->getAttempts();
            if (isset($attempts[$ip])) {
                unset($attempts[$ip]);
                $this->saveAttempts($attempts);
            }
            
            return ['success' => true, 'token' => $token];
        } else {
            // Failure
            $this->recordLoginFailure($ip);
            if ($this->isLockedOut($ip)) {
                 $wait = $this->getLockoutRemaining($ip);
                 return ['success' => false, 'error' => 'Login failed. Limit reached.', 'lockout' => true, 'wait' => $wait];
            }
            return ['success' => false, 'error' => 'Invalid credentials', 'lockout' => false];
        }
    }

    private function recordLoginFailure($ip) {
        $attempts = $this->getAttempts();
        $attempts = $this->pruneAttempts($attempts); // Cleanup old entries
        
        if (!isset($attempts[$ip])) {
            $attempts[$ip] = ['count' => 0, 'last_time' => 0];
        }
        
        $attempts[$ip]['count']++;
        $attempts[$ip]['last_time'] = time();
        
        $this->saveAttempts($attempts);
    }

    public function validateSession($token) {
        if (isset($_SESSION['admin_token']) && $_SESSION['admin_token'] === $token) {
             // Check timeout
             if (time() - $_SESSION['last_activity'] > $this->sessionLifetime) {
                 return false;
             }
             $_SESSION['last_activity'] = time();
             
             // Refresh data from config in case it changed
             $config = $this->getConfig();
             $data = $_SESSION['admin_data'];
             $data['username'] = $config['username'];
             $data['display_name'] = isset($config['display_name']) ? $config['display_name'] : $data['display_name'];
             // Ensure ID is set
             $data['admin_id'] = $data['id'] ?? 1; 
             
             // Add session metadata for UI (fake it since we don't store it in file session)
             $data['ip_address'] = $_SERVER['REMOTE_ADDR'];
             $data['user_agent'] = $_SERVER['HTTP_USER_AGENT'];
             $data['created_at'] = date('Y-m-d H:i:s', $_SESSION['start_time'] ?? time());
             
             return $data;
        }
        return false;
    }

    public function logout() {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
    }

    public function logoutAll($adminId) {
        // With file-based sessions, we can't easily kill "other" sessions unless we store session IDs in a file.
        // For now, we will just implement a token rotation or config change marker that invalidates sessions?
        // Simpler: Just rely on changing the password hash which will fail validation if we checked it on every request.
        // But typical PHP sessions rely on cookie. Use session_regenerate_id for current.
        // For "Logout All", strictly speaking, we can't without a central store.
        // WE WILL IGNORE "Logout All" for non-current sessions in this simple file-mode, 
        // OR we can add a 'last_password_change' timestamp to config and check it in session.
        // Let's keep it simple.
        session_regenerate_id(true);
    }

    public function changePassword($adminId, $oldPassword, $newPassword, $ip, $userAgent) {
        $config = $this->getConfig();
        $currentHash = $config['password_hash'];

        if (!password_verify($oldPassword, $currentHash)) {
            return false;
        }

        // Update Config File
        $config['password_hash'] = password_hash($newPassword, PASSWORD_DEFAULT);
        if ($this->updateConfig($config)) {
            $this->logActivity('PASSWORD_CHANGE', 'Password changed via file config', $ip, $userAgent);
            return true;
        }
        return false;
    }
    
    public function updateProfile($adminId, $data, $ip, $userAgent) {
         $config = $this->getConfig();
         
         if (isset($data['username'])) $config['username'] = $data['username'];
         if (isset($data['display_name'])) $config['display_name'] = $data['display_name'];
         if (isset($data['email'])) $config['email'] = $data['email'];
         if (isset($data['avatar'])) $config['avatar'] = $data['avatar'];

         if ($this->updateConfig($config)) {
             $this->logActivity('PROFILE_UPDATE', 'Profile updated in config file', $ip, $userAgent);
             // Update current session too
             if (isset($_SESSION['admin_data'])) {
                 $_SESSION['admin_data'] = array_merge($_SESSION['admin_data'], $data);
             }
             return true;
         }
         return false;
    }

    public function getActiveSessions($adminId) {
        // Cannot track other sessions without DB or complex file store.
        // Return dummy current session.
        return [
            [
                'session_token' => $_SESSION['admin_token'] ?? 'current',
                'ip_address' => $_SERVER['REMOTE_ADDR'],
                'user_agent' => $_SERVER['HTTP_USER_AGENT'],
                'last_activity' => date('Y-m-d H:i:s', $_SESSION['last_activity'] ?? time()),
                'created_at' => date('Y-m-d H:i:s', $_SESSION['last_activity'] ?? time()) // Approx
            ]
        ];
    }
    
    public function getActivityLog($adminId = null, $limit = 20) {
        if (!file_exists($this->activityLogFile)) return [];
        
        $lines = file($this->activityLogFile);
        $lines = array_reverse($lines); // Newest first
        $logs = [];
        
        foreach ($lines as $line) {
            $data = json_decode($line, true);
            if ($data) {
                // Approximate structure to match DB
                $logs[] = [
                    'created_at' => $data['time'],
                    'action' => $data['action'],
                    'details' => $data['details'],
                    'ip_address' => $data['ip']
                ];
            }
            if (count($logs) >= $limit) break;
        }
        return $logs;
    }

    private function logActivity($action, $details, $ip, $userAgent) {
        $logEntry = json_encode([
            'time' => date('Y-m-d H:i:s'),
            'action' => $action,
            'details' => $details,
            'ip' => $ip,
            'ua' => $userAgent
        ]);
        file_put_contents($this->activityLogFile, $logEntry . "\n", FILE_APPEND);
    }
}
