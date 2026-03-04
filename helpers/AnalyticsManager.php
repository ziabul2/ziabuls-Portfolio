<?php
/**
 * AnalyticsManager - Advanced visitor tracking with device & page insights
 */
class AnalyticsManager {
    private $dataFile;

    public function __construct() {
        $this->dataFile = __DIR__ . '/../data/analytics.json';
        if (!file_exists($this->dataFile)) {
            $this->initData();
        }
    }

    private function initData() {
        $initial = [
            'total_visits' => 0,
            'unique_visitors' => 0,
            'daily_stats' => [],
            'referrers' => [],
            'pages' => [],
            'browsers' => [],
            'devices' => [],
            'ips' => []
        ];
        file_put_contents($this->dataFile, json_encode($initial, JSON_PRETTY_PRINT));
    }

    public function trackVisit() {
        if (!file_exists($this->dataFile)) $this->initData();
        
        $data = json_decode(file_get_contents($this->dataFile), true);
        
        // Ensure all fields exist for legacy data
        $fields = ['total_visits', 'unique_visitors', 'daily_stats', 'referrers', 'pages', 'browsers', 'devices', 'ips'];
        foreach ($fields as $field) {
            if (!isset($data[$field])) $data[$field] = ($field === 'total_visits' || $field === 'unique_visitors') ? 0 : [];
        }

        $ip = $_SERVER['REMOTE_ADDR'];
        $hashedIp = hash('sha256', $ip);
        $today = date('Y-m-d');
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $pagePath = $_SERVER['REQUEST_URI'] ?? '/';

        $data['total_visits']++;

        if (!in_array($hashedIp, $data['ips'])) {
            $data['ips'][] = $hashedIp;
            $data['unique_visitors']++;
        }

        $data['daily_stats'][$today] = ($data['daily_stats'][$today] ?? 0) + 1;

        // Track Page Path
        $data['pages'][$pagePath] = ($data['pages'][$pagePath] ?? 0) + 1;

        // Track Referrer
        $referrer = $_SERVER['HTTP_REFERER'] ?? 'Direct';
        $refHost = parse_url($referrer, PHP_URL_HOST) ?: 'Direct';
        // Clean up internal referrers
        if (isset($_SERVER['HTTP_HOST']) && strpos($refHost, $_SERVER['HTTP_HOST']) !== false) {
            $refHost = 'Internal';
        }
        $data['referrers'][$refHost] = ($data['referrers'][$refHost] ?? 0) + 1;

        // Simple User Agent Parsing
        $browser = $this->getBrowser($userAgent);
        $os = $this->getOS($userAgent);
        
        $data['browsers'][$browser] = ($data['browsers'][$browser] ?? 0) + 1;
        $data['devices'][$os] = ($data['devices'][$os] ?? 0) + 1;

        // Optional: Limit IP list size to prevent JSON bloat (keep last 5000)
        if (count($data['ips']) > 5000) {
            $data['ips'] = array_slice($data['ips'], -5000);
        }

        file_put_contents($this->dataFile, json_encode($data, JSON_PRETTY_PRINT));
    }

    private function getBrowser($ua) {
        if (preg_match('/Edge/i', $ua)) return 'Edge';
        if (preg_match('/Firefox/i', $ua)) return 'Firefox';
        if (preg_match('/Chrome/i', $ua)) return 'Chrome';
        if (preg_match('/Safari/i', $ua)) return 'Safari';
        if (preg_match('/Opera|OPR/i', $ua)) return 'Opera';
        return 'Other';
    }

    private function getOS($ua) {
        if (preg_match('/windows|win32/i', $ua)) return 'Windows';
        if (preg_match('/macintosh|mac os x/i', $ua)) return 'Mac OS';
        if (preg_match('/linux/i', $ua)) return 'Linux';
        if (preg_match('/iphone/i', $ua)) return 'iPhone';
        if (preg_match('/android/i', $ua)) return 'Android';
        return 'Other';
    }

    public function getStats() {
        if (!file_exists($this->dataFile)) return [];
        $stats = json_decode(file_get_contents($this->dataFile), true);
        
        // Sort pages and referrers by popularity for easier viewing
        if (isset($stats['pages'])) arsort($stats['pages']);
        if (isset($stats['referrers'])) arsort($stats['referrers']);
        
        return $stats;
    }
}
