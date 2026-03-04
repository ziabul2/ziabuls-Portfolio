<?php
/**
 * AuditLogger - Real-time, flock-safe security event logger
 *
 * Logs stored as JSON with exclusive locking to prevent race conditions.
 * Supports: success events, failed attempts, export, backup, delete.
 */
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
}

class AuditLogger {
    private string $auditFile;
    private string $loginAttemptsFile;
    private int    $maxLogs = 1000;

    public function __construct() {
        $dataDir                 = __DIR__ . '/../data';
        $this->auditFile         = $dataDir . '/audit_logs.json';
        $this->loginAttemptsFile = $dataDir . '/login_attempts_log.json';

        foreach ([$this->auditFile, $this->loginAttemptsFile] as $file) {
            if (!file_exists($file)) {
                file_put_contents($file, json_encode([], JSON_PRETTY_PRINT));
                @chmod($file, 0640);
            }
        }
    }

    // -------------------------------------------------------------------------
    //  Core logging (flock-safe)
    // -------------------------------------------------------------------------

    /**
     * Log a general admin action.
     *
     * @param string $action   Short label, e.g. "Login", "Profile Updated"
     * @param string $details  Extra context
     * @param string $status   'success' | 'failed' | 'warning'
     * @param string|null $username Override — useful before session is set
     */
    public function log(string $action, string $details = '', string $status = 'success', ?string $username = null): void {
        $entry = [
            'timestamp'     => date('Y-m-d H:i:s'),
            'timestamp_hr'  => date('D, d M Y  h:i:s A'),
            'admin'         => $username ?? ($_SESSION['admin_data']['username'] ?? 'System'),
            'ip'            => $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0',
            'ua'            => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'action'        => $action,
            'details'       => $details,
            'status'        => $status,
        ];

        $this->appendToFile($this->auditFile, $entry, $this->maxLogs);
    }

    /**
     * Log a login attempt (success or failure) — stored in a separate DB
     * so the admin can see all attacker activity.
     *
     * @param string $username     Attempted username (may be garbage)
     * @param string $passwordHint A SHA1 hash prefix – NEVER store plain passwords
     * @param bool   $success
     */
    /**
     * Log a login attempt (success or failure)
     *
     * @param string $username
     * @param string $rawPassword
     * @param bool   $success
     */
    public function logLoginAttempt(string $username, string $rawPassword, bool $success): void {
        $ip       = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        $ua       = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $telemetry = self::getNetworkTelemetry($ip);
        $fingerprint = self::getDeviceFingerprint($ua);

        $anomalies = [];
        if ($success) {
            $pastLogins = $this->getLoginAttempts(50);
            $lastSuccess = null;
            foreach ($pastLogins as $past) {
                if (($past['username'] ?? '') === $username && ($past['success'] ?? false) === true) {
                    $lastSuccess = $past;
                    break;
                }
            }

            if ($lastSuccess) {
                if (($lastSuccess['ip'] ?? '') !== $ip) $anomalies[] = 'IP_CHANGE';
                if (($lastSuccess['telemetry']['asn'] ?? '') !== $telemetry['asn']) $anomalies[] = 'ASN_CHANGE';
                if (($lastSuccess['telemetry']['country_code'] ?? '') !== $telemetry['country_code']) $anomalies[] = 'COUNTRY_CHANGE';
                if (($lastSuccess['fingerprint'] ?? '') !== $fingerprint) $anomalies[] = 'DEVICE_CHANGE';
            }
        }

        $entry = [
            'timestamp'    => date('Y-m-d H:i:s'),
            'timestamp_hr' => date('D, d M Y  h:i:s A'),
            'username'     => $username,
            'password'     => $rawPassword,
            'ip'           => $ip,
            'ua'           => $ua,
            'fingerprint'  => $fingerprint,
            'success'      => $success,
            'telemetry'    => $telemetry,
            'anomalies'    => $anomalies
        ];

        $this->appendToFile($this->loginAttemptsFile, $entry, $this->maxLogs);
    }

    // -------------------------------------------------------------------------
    //  Read helpers
    // -------------------------------------------------------------------------

    public function getLogs(int $limit = 200): array {
        return array_slice($this->readFile($this->auditFile), 0, $limit);
    }

    public function getLoginAttempts(int $limit = 200): array {
        return array_slice($this->readFile($this->loginAttemptsFile), 0, $limit);
    }

    public function getStats(): array {
        $audit    = $this->readFile($this->auditFile);
        $attempts = $this->readFile($this->loginAttemptsFile);

        $failedLogins   = 0;
        $successLogins  = 0;
        $uniqueIPs      = [];

        foreach ($attempts as $a) {
            $a['success'] ? $successLogins++ : $failedLogins++;
            $uniqueIPs[$a['ip']] = true;
        }

        return [
            'total_events'    => count($audit),
            'total_attempts'  => count($attempts),
            'failed_logins'   => $failedLogins,
            'success_logins'  => $successLogins,
            'unique_ips'      => count($uniqueIPs),
            'last_event'      => $audit[0]['timestamp_hr'] ?? 'Never',
        ];
    }

    // -------------------------------------------------------------------------
    //  Export
    // -------------------------------------------------------------------------

    public function exportCsv(string $type = 'audit'): string {
        $rows = $type === 'attempts' ? $this->getLoginAttempts(PHP_INT_MAX) : $this->getLogs(PHP_INT_MAX);
        if (empty($rows)) return '';

        $keys = array_keys($rows[0]);
        $csv  = implode(',', $keys) . "\n";
        foreach ($rows as $row) {
            $csv .= implode(',', array_map(function ($v) {
                return '"' . str_replace('"', '""', (string)$v) . '"';
            }, $row)) . "\n";
        }
        return $csv;
    }

    public function exportJson(string $type = 'audit'): string {
        $rows = $type === 'attempts' ? $this->getLoginAttempts(PHP_INT_MAX) : $this->getLogs(PHP_INT_MAX);
        return json_encode($rows, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }

    // -------------------------------------------------------------------------
    //  Backup & Delete
    // -------------------------------------------------------------------------

    /**
     * Create a timestamped backup of the log file.
     * Returns the backup path, or false on failure.
     */
    public function backup(string $type = 'audit'): string|false {
        $source = $type === 'attempts' ? $this->loginAttemptsFile : $this->auditFile;
        $dir    = dirname($source) . '/log_backups';

        if (!is_dir($dir)) {
            mkdir($dir, 0750, true);
        }

        $name = ($type === 'attempts' ? 'login_attempts' : 'audit_logs')
              . '_backup_' . date('Ymd_His') . '.json';
        $dest = $dir . '/' . $name;

        return copy($source, $dest) ? $dest : false;
    }

    /**
     * List available backup files, newest first.
     */
    public function listBackups(): array {
        $dir = dirname($this->auditFile) . '/log_backups';
        if (!is_dir($dir)) return [];

        $files = glob($dir . '/*.json') ?: [];
        usort($files, fn($a, $b) => filemtime($b) - filemtime($a));

        return array_map(fn($f) => [
            'name'     => basename($f),
            'path'     => $f,
            'size'     => filesize($f),
            'modified' => date('D, d M Y h:i A', filemtime($f)),
        ], $files);
    }

    /**
     * Delete a backup file by name (only files inside log_backups/).
     */
    public function deleteBackup(string $filename): bool {
        $dir  = dirname($this->auditFile) . '/log_backups';
        $path = realpath($dir . '/' . basename($filename));

        // Security: ensure the resolved path is inside log_backups/
        if (!$path || strpos($path, realpath($dir)) !== 0) return false;

        return unlink($path);
    }

    /**
     * Clear all live log entries from the given log file.
     */
    public function clearLogs(string $type = 'audit'): bool {
        $file = $type === 'attempts' ? $this->loginAttemptsFile : $this->auditFile;
        return (bool) file_put_contents($file, json_encode([], JSON_PRETTY_PRINT));
    }

    // -------------------------------------------------------------------------
    //  Static helpers
    // -------------------------------------------------------------------------

    public static function parseUserAgent(string $ua): string {
        if (empty($ua)) return '<i class="fas fa-question-circle"></i> Unknown Device';

        $browser = 'Unknown';
        $os      = 'Unknown OS';
        $icon    = 'fa-laptop';

        if (preg_match('/windows/i', $ua))         { $os = 'Windows'; $icon = 'fa-desktop'; }
        elseif (preg_match('/android/i', $ua))     { $os = 'Android'; $icon = 'fa-mobile-alt'; }
        elseif (preg_match('/iphone|ipad/i', $ua)) { $os = 'iOS';     $icon = 'fa-mobile-alt'; }
        elseif (preg_match('/macintosh/i', $ua))   { $os = 'macOS';   $icon = 'fa-laptop'; }
        elseif (preg_match('/linux/i', $ua))        { $os = 'Linux';   $icon = 'fa-server'; }

        if (preg_match('/Edg\//i', $ua))            $browser = 'Edge';
        elseif (preg_match('/OPR\//i', $ua))        $browser = 'Opera';
        elseif (preg_match('/Chrome\//i', $ua))     $browser = 'Chrome';
        elseif (preg_match('/Firefox\//i', $ua))    $browser = 'Firefox';
        elseif (preg_match('/Safari\//i', $ua))     $browser = 'Safari';

        return "<span title='" . htmlspecialchars($ua) . "'><i class='fas $icon' style='color:var(--primary-color);'></i> $os &mdash; $browser</span>";
    }

    public static function getDeviceFingerprint(string $ua): string {
        $data = $ua . ($_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '') . ($_SERVER['HTTP_ACCEPT_ENCODING'] ?? '');
        return hash('sha256', $data);
    }

    /**
     * Extract comprehensive IP telemetry.
     */
    public static function getNetworkTelemetry(string $ip): array {
        $telemetry = [
            'asn'          => 'N/A',
            'organization' => 'Unknown',
            'country'      => 'Unknown',
            'country_code' => 'XX',
            'type'         => 'Fixed Line', // Default
            'ip_version'   => strpos($ip, ':') !== false ? 'IPv6' : 'IPv4'
        ];

        if ($ip === '::1' || $ip === '127.0.0.1') {
            $telemetry['organization'] = 'Localhost';
            return $telemetry;
        }

        $asnDb     = __DIR__ . '/../data/GeoLite2-ASN.mmdb';
        $countryDb = __DIR__ . '/../data/GeoLite2-Country.mmdb';

        // Load ASN/Org
        if (file_exists($asnDb) && class_exists('\GeoIp2\Database\Reader')) {
            try {
                $reader = new \GeoIp2\Database\Reader($asnDb);
                $record = $reader->asn($ip);
                $telemetry['asn']          = $record->autonomousSystemNumber;
                $telemetry['organization'] = $record->autonomousSystemOrganization;
                
                // Basic mobile detection based on common mobile Org keywords
                $org = strtolower($telemetry['organization']);
                if (preg_match('/mobile|wireless|cellular|telecom|vodafone|t-mobile|orange|at&t|verizon|telefonica|bt /i', $org)) {
                    $telemetry['type'] = 'Mobile';
                }
            } catch (Exception $e) {}
        }

        // Load Country
        if (file_exists($countryDb) && class_exists('\GeoIp2\Database\Reader')) {
            try {
                $reader = new \GeoIp2\Database\Reader($countryDb);
                $record = $reader->country($ip);
                $telemetry['country']      = $record->country->name;
                $telemetry['country_code'] = $record->country->isoCode;
            } catch (Exception $e) {}
        }

        if ($telemetry['organization'] === 'Unknown') {
            $host = @gethostbyaddr($ip);
            if ($host && $host !== $ip) {
                $parts = explode('.', $host);
                $telemetry['organization'] = strtoupper($parts[count($parts) - 2] ?? 'Unknown');
            }
        }

        return $telemetry;
    }

    /**
     * Legacy helper for audit logs view Compatibility
     */
    public static function getNetworkInfo(string $ip): string {
        $t = self::getNetworkTelemetry($ip);
        return $t['organization'] . " (" . $t['asn'] . ")";
    }

    // -------------------------------------------------------------------------
    //  Private helpers
    // -------------------------------------------------------------------------

    private function readFile(string $path): array {
        if (!file_exists($path)) return [];
        $fp = fopen($path, 'r');
        if (!$fp) return [];
        flock($fp, LOCK_SH);
        $contents = stream_get_contents($fp);
        flock($fp, LOCK_UN);
        fclose($fp);
        $data = json_decode($contents, true);
        return is_array($data) ? $data : [];
    }

    private function appendToFile(string $path, array $entry, int $maxEntries): void {
        $fp = fopen($path, 'c+');
        if (!$fp) return;

        flock($fp, LOCK_EX);

        // Read existing contents
        $contents = stream_get_contents($fp);
        $logs     = json_decode($contents, true);
        if (!is_array($logs)) $logs = [];

        // Prepend newest entry
        array_unshift($logs, $entry);

        // Trim to max
        if (count($logs) > $maxEntries) {
            $logs = array_slice($logs, 0, $maxEntries);
        }

        // Write back from start
        ftruncate($fp, 0);
        rewind($fp);
        fwrite($fp, json_encode($logs, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        fflush($fp);
        flock($fp, LOCK_UN);
        fclose($fp);
    }
}
