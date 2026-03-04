<?php
/**
 * LeadManager - Store and manage incoming inquiries
 */
class LeadManager {
    private $dataFile;

    public function __construct() {
        $this->dataFile = __DIR__ . '/../data/leads.json';
        if (!file_exists($this->dataFile)) {
            file_put_contents($this->dataFile, json_encode([], JSON_PRETTY_PRINT));
        }
    }

    public function getLeads() {
        $data = json_decode(file_get_contents($this->dataFile), true) ?: [];
        // Sort by newest first
        usort($data, function($a, $b) {
            return $b['timestamp'] - $a['timestamp'];
        });
        return $data;
    }

    public function addLead($name, $email, $subject, $message, $ip) {
        $data = $this->getLeads();
        $newLead = [
            'id' => uniqid('lead_'),
            'name' => $name,
            'email' => $email,
            'subject' => $subject,
            'message' => $message,
            'ip' => $ip,
            'timestamp' => time(),
            'status' => 'unread'
        ];
        $data[] = $newLead;
        return file_put_contents($this->dataFile, json_encode(array_values($data), JSON_PRETTY_PRINT)) !== false;
    }

    public function markAsRead($id) {
        $data = $this->getLeads();
        foreach ($data as &$lead) {
            if ($lead['id'] === $id) {
                $lead['status'] = 'read';
                break;
            }
        }
        return file_put_contents($this->dataFile, json_encode(array_values($data), JSON_PRETTY_PRINT)) !== false;
    }

    public function deleteLead($id) {
        $data = $this->getLeads();
        $filtered = array_filter($data, function($lead) use ($id) {
            return $lead['id'] !== $id;
        });
        return file_put_contents($this->dataFile, json_encode(array_values($filtered), JSON_PRETTY_PRINT)) !== false;
    }

    public function getUnreadCount() {
        $data = $this->getLeads();
        $count = 0;
        foreach ($data as $lead) {
            if ($lead['status'] === 'unread') $count++;
        }
        return $count;
    }
}
