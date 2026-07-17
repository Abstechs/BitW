<?php
// core/Settings.php

class Settings {
    private static $cache = [];

    /**
     * Get a system setting value by key.
     */
    public static function get($pdo, $key, $default = null) {
        if (isset(self::$cache[$key])) {
            return self::$cache[$key];
        }

        $stmt = $pdo->prepare("SELECT setting_value FROM system_settings WHERE setting_key = ?");
        $stmt->execute([$key]);
        $value = $stmt->fetchColumn();

        if ($value === false) {
            return $default;
        }

        // Auto-decode JSON if applicable
        $decoded = json_decode($value, true);
        $finalValue = (json_last_error() === JSON_ERROR_NONE) ? $decoded : $value;
        
        self::$cache[$key] = $finalValue;
        return $finalValue;
    }

    /**
     * Set or update a system setting.
     */
    public static function set($pdo, $key, $value, $group = 'general', $description = '') {
        $finalValue = is_array($value) ? json_encode($value) : $value;
        
        $stmt = $pdo->prepare("INSERT INTO system_settings (setting_key, setting_value, setting_group, description) 
                               VALUES (?, ?, ?, ?) 
                               ON DUPLICATE KEY UPDATE setting_value = ?, setting_group = ?, description = ?");
        $stmt->execute([$key, $finalValue, $group, $description, $finalValue, $group, $description]);
        
        self::$cache[$key] = $value;
        return true;
    }
}
