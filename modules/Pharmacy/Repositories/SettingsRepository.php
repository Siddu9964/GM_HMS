<?php
namespace GM_HMS\Modules\Pharmacy\Repositories;

use GM_HMS\Database\SecureDatabase;

class SettingsRepository {
    private $db;

    public function __construct() {
        $this->db = SecureDatabase::getInstance();
    }

    public function getSetting(string $key, string $default = ''): string {
        $row = $this->db->fetchOne("SELECT setting_value FROM ph_settings WHERE setting_key = ?", [$key]);
        return $row ? (string)$row['setting_value'] : $default;
    }

    public function getAll(): array {
        return $this->db->fetchAll("SELECT * FROM ph_settings");
    }

    public function set(string $key, string $value): void {
        $exists = $this->db->fetchOne("SELECT 1 FROM ph_settings WHERE setting_key = ?", [$key]);
        if ($exists) {
            $this->db->execute("UPDATE ph_settings SET setting_value = ? WHERE setting_key = ?", [$value, $key]);
        } else {
            $this->db->execute("INSERT INTO ph_settings (setting_key, setting_value) VALUES (?, ?)", [$key, $value]);
        }
    }
}
