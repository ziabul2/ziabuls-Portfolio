<?php

require_once __DIR__ . '/../helpers/JsonDbHelper.php';
require_once __DIR__ . '/../helpers/EncryptionHelper.php';

// Load Composer autoload for RobThree/TwoFactorAuth
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
}

use RobThree\Auth\TwoFactorAuth;

class AdminAuth {
    private int    $sessionLifetime    = 3600;
    private int    $lockoutThreshold   = 2;
    private int    $recaptchaThreshold = 1;
    private int    $otpMaxAttempts     = 5;
    private int    $otpLockDuration    = 30; // seconds

    private string $configPath;
    private string $lockoutDescFile;
    private string $activityLogFile;
    private string $usersFile;
    private string $activeSessionsFile;

    private JsonDbHelper    $db;
    private EncryptionHelper $enc;
    private TwoFactorAuth   $tfa;

    public function __construct() {
        $this->configPath      = __DIR__ . '/../config/admin.php';
        $this->lockoutDescFile = __DIR__ . '/../data/login_attempts.json';
        $this->activityLogFile = __DIR__ . '/../logs/admin_activity.log';
        $dataDir                  = __DIR__ . '/../data';
        $this->usersFile          = $dataDir . '/users.json';
        $this->activeSessionsFile = $dataDir . '/active_sessions.json';

        foreach ([
            dirname($this->lockoutDescFile),
            dirname($this->activityLogFile),
            dirname($this->activeSessionsFile)
        ] as $dir) {
            if (!is_dir($dir)) {
                @mkdir($dir, 0750, true);
            }
        }

        $this->db  = new JsonDbHelper($this->usersFile);
        $this->enc = new EncryptionHelper();
        $this->tfa = new TwoFactorAuth('MyCV Admin');
    }

    // -------------------------------------------------------------------------
    //  User DB helpers
    // -------------------------------------------------------------------------

    /** Return the single admin user record (always id=1). */
    public function getUser(): ?array {
        return $this->db->findBy('id', 1);
    }

    /** Persist a (possibly modified) user record back to JSON. */
    public function saveUser(array $user): bool {
        return $this->db->save($user);
    }

    // -------------------------------------------------------------------------
    //  Login-attempt tracking (per-IP lockout for bad passwords)
    // -------------------------------------------------------------------------

    private function getAttempts(): array {
        if (file_exists($this->lockoutDescFile)) {
            $data = json_decode(file_get_contents($this->lockoutDescFile), true);
            return is_array($data) ? $data : [];
        }
        return [];
    }

    private function saveAttempts(array $attempts): void {
        file_put_contents($this->lockoutDescFile, json_encode($attempts));
    }

    private function pruneAttempts(array $attempts): array {
        $now = time();
        return array_filter($attempts, function ($data) use ($now) {
            $duration  = $this->getLockoutDuration($data['count'] ?? 0);
            $keepTime  = max(3600, $duration);
            return $now - $data['last_time'] < $keepTime;
        });
    }

    /**
     * Progressive lockout durations:
     *  2 fails → 2 min, 3 → 4 min, 4 → 8 min, 5+ → 1h doubling
     */
    private function getLockoutDuration(int $count): int {
        if ($count < $this->lockoutThreshold) return 0;
        if ($count === 2) return 120;
        if ($count === 3) return 240;
        if ($count === 4) return 480;
        return 3600 * (int) pow(2, $count - 5);
    }

    public function getFailureCount(string $ip): int {
        $attempts = $this->getAttempts();
        return isset($attempts[$ip]) ? (int) $attempts[$ip]['count'] : 0;
    }

    public function isLockedOut(string $ip): bool {
        $attempts = $this->getAttempts();
        if (!isset($attempts[$ip])) return false;
        $data = $attempts[$ip];
        if ($data['count'] < $this->lockoutThreshold) return false;
        return (time() - $data['last_time']) < $this->getLockoutDuration($data['count']);
    }

    public function getLockoutRemaining(string $ip): int {
        $attempts = $this->getAttempts();
        if (!isset($attempts[$ip])) return 0;
        $data     = $attempts[$ip];
        $remaining = $this->getLockoutDuration($data['count']) - (time() - $data['last_time']);
        return max(0, $remaining);
    }

    private function recordLoginFailure(string $ip): void {
        $attempts = $this->pruneAttempts($this->getAttempts());
        if (!isset($attempts[$ip])) {
            $attempts[$ip] = ['count' => 0, 'last_time' => 0];
        }
        $attempts[$ip]['count']++;
        $attempts[$ip]['last_time'] = time();
        $this->saveAttempts($attempts);
    }

    // -------------------------------------------------------------------------
    //  reCAPTCHA
    // -------------------------------------------------------------------------

    public function verifyRecaptcha(?string $token): bool {
        $config    = $this->getConfig();
        $secretKey = $config['recaptcha_secret_key'] ?? '';
        if (empty($secretKey) || $secretKey === 'YOUR_RECAPTCHA_SECRET_KEY') return true;
        if (empty($token)) return false;

        $response = @file_get_contents(
            'https://www.google.com/recaptcha/api/siteverify?secret=' . urlencode($secretKey)
            . '&response=' . urlencode($token)
            . '&remoteip=' . urlencode($_SERVER['REMOTE_ADDR'] ?? '')
        );
        if ($response === false) return true; // fail-open on network error
        $data = json_decode($response, true);
        return !empty($data['success']);
    }

    // -------------------------------------------------------------------------
    //  Main login flow
    // -------------------------------------------------------------------------

    /**
     * Attempt password-based login.
     *
     * Returns array with keys:
     *   success       bool
     *   error         string (on failure)
     *   requires_2fa  bool   (when 2FA pending)
     *   lockout       bool
     *   wait          int    (seconds remaining)
     *   show_captcha  bool
     *   failures      int
     */
    public function login(
        string  $username,
        string  $password,
        string  $ip,
        string  $userAgent,
        ?string $recaptchaToken  = null,
        ?string $mathAns         = null,
        ?string $expectedMathAns = null
    ): array {
        if ($this->isLockedOut($ip)) {
            return ['success' => false, 'error' => 'Account locked.', 'lockout' => true, 'wait' => $this->getLockoutRemaining($ip)];
        }

        $failures = $this->getFailureCount($ip);

        // Captcha gating
        if ($failures >= $this->recaptchaThreshold) {
            if (!$this->verifyRecaptcha($recaptchaToken)) {
                $this->recordLoginFailure($ip);
                return ['success' => false, 'error' => 'Please complete the reCAPTCHA.', 'lockout' => $this->isLockedOut($ip), 'show_captcha' => true, 'wait' => $this->getLockoutRemaining($ip)];
            }
        } else {
            if ($mathAns === null || $expectedMathAns === null || (int) $mathAns !== (int) $expectedMathAns) {
                $this->recordLoginFailure($ip);
                $newCount = $this->getFailureCount($ip);
                return ['success' => false, 'error' => 'Incorrect math verification.', 'lockout' => $this->isLockedOut($ip), 'show_captcha' => $newCount >= $this->recaptchaThreshold, 'wait' => $this->getLockoutRemaining($ip), 'failures' => $newCount];
            }
        }

        // Credential check against users.json
        $user = $this->getUser();
        if ($user === null) {
            // Fallback to legacy config for the first run
            $user = $this->buildUserFromConfig();
        }

        if (
            ($user['username'] ?? '') === $username
            && password_verify($password, $user['password'])
        ) {
            $this->logActivity('LOGIN', 'Admin logged in', $ip, $userAgent);

            // Build session base
            $token = bin2hex(random_bytes(32));
            $_SESSION['admin_token'] = $token;
            $_SESSION['admin_data']  = [
                'id'           => $user['id'],
                'username'     => $user['username'],
                'display_name' => $user['display_name'] ?? 'Administrator',
                'email'        => $user['email'] ?? '',
                'avatar'       => $user['avatar'] ?? '',
                'password_hash'=> $user['password'],
            ];
            $_SESSION['start_time']    = time();
            $_SESSION['last_activity'] = time();

            // Clear failed attempts
            $attempts = $this->getAttempts();
            unset($attempts[$ip]);
            $this->saveAttempts($attempts);

            // 2FA gate
            if (!empty($user['twofa_enabled'])) {
                $_SESSION['2fa_pending'] = true;
                return ['success' => true, 'token' => $token, 'requires_2fa' => true];
            }

            $_SESSION['logged_in'] = true;
            $this->registerSession($token, $user['id'], $ip, $userAgent);
            return ['success' => true, 'token' => $token, 'requires_2fa' => false];

        } else {
            $this->recordLoginFailure($ip);
            $newCount  = $this->getFailureCount($ip);
            if ($this->isLockedOut($ip)) {
                return ['success' => false, 'error' => 'Too many failed attempts. Account locked.', 'lockout' => true, 'wait' => $this->getLockoutRemaining($ip)];
            }
            $remaining = max(0, $this->lockoutThreshold - $newCount);
            return ['success' => false, 'error' => "Invalid credentials. {$remaining} attempt(s) before lockout.", 'lockout' => false, 'show_captcha' => $newCount >= $this->recaptchaThreshold, 'failures' => $newCount];
        }
    }

    // -------------------------------------------------------------------------
    //  2FA — Secret management
    // -------------------------------------------------------------------------

    /** Generate a new TOTP secret (plain Base32). */
    public function generateTfaSecret(): string {
        return $this->tfa->createSecret(160); // 160-bit = 32-char Base32
    }

    /** Return a data-URI QR code for the given plain secret. */
    public function getQrCodeDataUri(string $label, string $plainSecret, int $size = 200): string {
        return $this->tfa->getQRCodeImageAsDataUri($label, $plainSecret, $size);
    }

    /**
     * Encrypt and save the 2FA secret, backup codes hashes, enable the flag.
     *
     * @param string   $plainSecret  Raw Base32 secret returned by generateTfaSecret()
     * @param string[] $backupHashes Already hashed backup codes
     */
    public function enableTfa(string $plainSecret, array $backupHashes): bool {
        $user = $this->getUser();
        if ($user === null) return false;

        $user['twofa_enabled'] = true;
        $user['twofa_secret']  = $this->enc->encrypt($plainSecret);
        $user['backup_codes']  = $backupHashes;
        $user['otp_attempts']  = 0;
        $user['otp_lock_until']= null;

        return $this->saveUser($user);
    }

    /** Verify a plain TOTP code against the stored encrypted secret. */
    public function verifyTfaCode(string $code): bool {
        $user = $this->getUser();
        if (empty($user['twofa_secret'])) return false;
        $plainSecret = $this->enc->decrypt($user['twofa_secret']);
        if ($plainSecret === false) return false;
        return $this->tfa->verifyCode($plainSecret, $code, 2);
    }

    /**
     * Try to consume a backup recovery code.
     * Removes the used code from storage if found.
     *
     * @param string $rawCode Plain-text code entered by user
     * @return bool True if a matching code was found and consumed
     */
    public function consumeBackupCode(string $rawCode): bool {
        $user = $this->getUser();
        if (empty($user['backup_codes'])) return false;

        foreach ($user['backup_codes'] as $i => $hash) {
            if (password_verify($rawCode, $hash)) {
                // Remove used code
                array_splice($user['backup_codes'], $i, 1);
                $user['backup_codes'] = array_values($user['backup_codes']);
                $this->saveUser($user);
                return true;
            }
        }
        return false;
    }

    // -------------------------------------------------------------------------
    //  2FA — Rate limiting
    // -------------------------------------------------------------------------

    /** Returns true if OTP is currently locked out. */
    public function isOtpLockedOut(): bool {
        $user = $this->getUser();
        if (empty($user['otp_lock_until'])) return false;
        return time() < (int) $user['otp_lock_until'];
    }

    /** Seconds until OTP lockout expires (0 if not locked). */
    public function getOtpLockRemaining(): int {
        $user = $this->getUser();
        if (empty($user['otp_lock_until'])) return 0;
        return max(0, (int) $user['otp_lock_until'] - time());
    }

    /** Increment the OTP failure counter; lock after threshold. */
    public function recordOtpFailure(): void {
        $user = $this->getUser();
        if ($user === null) return;
        $user['otp_attempts'] = ($user['otp_attempts'] ?? 0) + 1;
        if ($user['otp_attempts'] >= $this->otpMaxAttempts) {
            $user['otp_lock_until'] = time() + $this->otpLockDuration;
            $user['otp_attempts']   = 0; // reset counter after locking
        }
        $this->saveUser($user);
    }

    /** Reset OTP counters on successful verification. */
    public function clearOtpFailures(): void {
        $user = $this->getUser();
        if ($user === null) return;
        $user['otp_attempts']  = 0;
        $user['otp_lock_until']= null;
        $this->saveUser($user);
    }

    // -------------------------------------------------------------------------
    //  Disable 2FA
    // -------------------------------------------------------------------------

    /**
     * Disables 2FA after verifying password and current OTP.
     */
    public function disableTfa(string $password, string $code): bool {
        $user = $this->getUser();
        if ($user === null) return false;
        if (!password_verify($password, $user['password'])) return false;
        if (!$this->verifyTfaCode($code)) return false;

        $user['twofa_enabled'] = false;
        $user['twofa_secret']  = '';
        $user['backup_codes']  = [];
        $user['otp_attempts']  = 0;
        $user['otp_lock_until']= null;

        return $this->saveUser($user);
    }

    // -------------------------------------------------------------------------
    //  Session validation
    // -------------------------------------------------------------------------

    public function validateSession(string $token): array|false {
        if (!isset($_SESSION['admin_token']) || $_SESSION['admin_token'] !== $token) return false;
        
        // Active session check (Central DB)
        $sessions = $this->readSessions();
        if (!isset($sessions[$token])) {
            $this->logout();
            return false;
        }

        if (time() - ($_SESSION['last_activity'] ?? 0) > $this->sessionLifetime) {
            $this->logout();
            return false;
        }

        $_SESSION['last_activity'] = time();
        
        // Update last activity in central DB
        $sessions[$token]['last_activity'] = time();
        $this->writeSessions($sessions);

        // Block access while 2FA is pending
        if (!empty($_SESSION['2fa_pending'])) {
            if (basename($_SERVER['PHP_SELF'] ?? '') !== 'verify-2fa.php') {
                return false;
            }
        }

        $data = $_SESSION['admin_data'] ?? [];
        $user = $this->getUser();
        if ($user) {
            $data['display_name'] = $user['display_name'] ?? $data['display_name'];
            $data['email']        = $user['email'] ?? $data['email'];
            $data['avatar']       = $user['avatar']  ?? $data['avatar'];
        }

        $data['admin_id']   = $data['id'] ?? 1;
        $data['ip_address'] = $_SERVER['REMOTE_ADDR'];
        $data['user_agent'] = $_SERVER['HTTP_USER_AGENT'];
        $data['created_at'] = date('Y-m-d H:i:s', $_SESSION['start_time'] ?? time());

        return $data;
    }

    // -------------------------------------------------------------------------
    //  Profile / password management
    // -------------------------------------------------------------------------

    public function changePassword(int $adminId, string $oldPassword, string $newPassword, string $ip, string $userAgent): bool {
        $user = $this->getUser();
        if (!$user || !password_verify($oldPassword, $user['password'])) return false;
        $user['password'] = password_hash($newPassword, PASSWORD_DEFAULT);
        if ($this->saveUser($user)) {
            $this->logActivity('PASSWORD_CHANGE', 'Password changed', $ip, $userAgent);
            return true;
        }
        return false;
    }

    public function updateProfile(int $adminId, array $data, string $ip, string $userAgent): bool {
        $user = $this->getUser();
        if (!$user) return false;
        foreach (['username', 'display_name', 'email', 'avatar'] as $field) {
            if (isset($data[$field])) $user[$field] = $data[$field];
        }
        if ($this->saveUser($user)) {
            $this->logActivity('PROFILE_UPDATE', 'Profile updated', $ip, $userAgent);
            if (isset($_SESSION['admin_data'])) {
                $_SESSION['admin_data'] = array_merge($_SESSION['admin_data'], $data);
            }
            return true;
        }
        return false;
    }

    // -------------------------------------------------------------------------
    //  Legacy config helpers (keep backward compat)
    // -------------------------------------------------------------------------

    private function getConfig(): array {
        return file_exists($this->configPath) ? (require $this->configPath) : [];
    }

    /**
     * Build a user-shaped array from the legacy config/admin.php on first run.
     * Saves it to users.json so future logins go through the new DB.
     */
    private function buildUserFromConfig(): array {
        $c = $this->getConfig();
        $user = [
            'id'            => 1,
            'email'         => $c['email']        ?? 'admin@local',
            'username'      => $c['username']      ?? 'admin',
            'password'      => $c['password_hash'] ?? '',
            'display_name'  => $c['display_name']  ?? 'Administrator',
            'avatar'        => $c['avatar']        ?? '',
            'twofa_enabled' => false,
            'twofa_secret'  => '',
            'backup_codes'  => [],
            'otp_attempts'  => 0,
            'otp_lock_until'=> null,
        ];
        $this->saveUser($user);
        return $user;
    }

    // -------------------------------------------------------------------------
    //  Misc
    // -------------------------------------------------------------------------

    public function logout(?string $token = null): void {
        $tokenToRevoke = $token ?? ($_SESSION['admin_token'] ?? null);
        if ($tokenToRevoke) {
            $this->revokeSession($tokenToRevoke);
        }
        
        // If we are logging out the CURRENT session, destroy it
        if ($token === null || (isset($_SESSION['admin_token']) && $token === $_SESSION['admin_token'])) {
            if (session_status() === PHP_SESSION_ACTIVE) {
                session_unset();
                session_destroy();
            }
        }
    }

    private function readSessions(): array {
        if (!file_exists($this->activeSessionsFile)) return [];
        $data = json_decode(file_get_contents($this->activeSessionsFile), true);
        return is_array($data) ? $data : [];
    }

    private function writeSessions(array $sessions): void {
        // Prune expired sessions
        $now = time();
        $sessions = array_filter($sessions, function($s) use ($now) {
            return ($now - $s['last_activity']) < $this->sessionLifetime;
        });
        file_put_contents($this->activeSessionsFile, json_encode($sessions, JSON_PRETTY_PRINT));
    }

    public function registerSession(string $token, int $adminId, string $ip, string $userAgent): void {
        $sessions = $this->readSessions();
        
        require_once __DIR__ . '/AuditLogger.php';
        $telemetry = AuditLogger::getNetworkTelemetry($ip);

        $sessions[$token] = [
            'admin_id'      => $adminId,
            'ip_address'    => $ip,
            'user_agent'    => $userAgent,
            'last_activity' => time(),
            'created_at'    => time(),
            'telemetry'     => $telemetry
        ];
        $this->writeSessions($sessions);
    }

    public function getActiveSessions(int $adminId): array {
        $sessions = $this->readSessions();
        $list = [];
        foreach ($sessions as $token => $s) {
            if ($s['admin_id'] === $adminId) {
                $s['session_token'] = $token;
                $list[] = $s;
            }
        }
        usort($list, fn($a, $b) => $b['last_activity'] - $a['last_activity']);
        return $list;
    }

    public function revokeSession(string $token): void {
        $sessions = $this->readSessions();
        if (isset($sessions[$token])) {
            unset($sessions[$token]);
            $this->writeSessions($sessions);
        }
    }

    public function logoutAll(int $adminId): void {
        $sessions = $this->readSessions();
        foreach ($sessions as $token => $s) {
            if ($s['admin_id'] === $adminId) {
                unset($sessions[$token]);
            }
        }
        $this->writeSessions($sessions);
        // The instruction implies that logout.php and profile.php will be updated
        // to use these methods. The provided snippet for profile.php shows
        // $auth->revokeSession() and $auth->logoutAll() being used.
        // The logout() call here is likely intended to clear the *current* session
        // if it belongs to the adminId whose sessions are being cleared.
        // However, the instruction only asks to update AdminAuth::logout, logout.php, and profile.php.
        // The provided snippet for logoutAll is fragmented and seems to be a mix.
        // Keeping the existing logout() call here as it's part of the original code.
        $this->logout();
    }

    public function getActivityLog(int $adminId, int $limit = 50): array {
        if (!file_exists($this->activityLogFile)) return [];
        $lines = file($this->activityLogFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if (!$lines) return [];
        $logs = array_map(fn($l) => json_decode($l, true), $lines);
        $logs = array_filter($logs);
        usort($logs, function($a, $b) {
            $ta = strtotime($a['created_at'] ?? ($a['time'] ?? '0'));
            $tb = strtotime($b['created_at'] ?? ($b['time'] ?? '0'));
            return $tb - $ta;
        });
        return array_slice($logs, 0, $limit);
    }

    private function logActivity(string $action, string $details, string $ip, string $userAgent): void {
        $dir = dirname($this->activityLogFile);
        if (!is_dir($dir)) mkdir($dir, 0750, true);
        
        $entry = [
            'created_at' => date('Y-m-d H:i:s'), 
            'action'     => $action, 
            'details'    => $details, 
            'ip_address' => $ip, 
            'ua'         => $userAgent
        ];

        file_put_contents(
            $this->activityLogFile,
            json_encode($entry) . "\n",
            FILE_APPEND
        );

        // Also log to AuditLogger for central security view
        require_once __DIR__ . '/AuditLogger.php';
        (new AuditLogger())->log($action, $details, 'success');
    }
}
