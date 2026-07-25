<?php
// classes/AppSettings.php

class AppSettings 
{
    private static array $settings = [];
    private static bool $isLoaded = false;

    /**
     * Load settings into memory from the database and/or config file.
     */
    public static function load(?PDO $db = null): void 
    {
        if (self::$isLoaded) {
            return;
        }

        // Load static file config if available
        $configFile = __DIR__ . '/../config/settings.php';
        if (file_exists($configFile)) {
            $fileSettings = include $configFile;
            if (is_array($fileSettings)) {
                self::$settings = $fileSettings;
            }
        }

        // Load database settings if PDO instance provided or available globally
        $pdo = $db ?? ($GLOBALS['pdo'] ?? null);
        if ($pdo instanceof PDO) {
            try {
                $stmt = $pdo->query("SELECT setting_key, setting_value FROM settings");
                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    self::$settings[$row['setting_key']] = $row['setting_value'];
                }
            } catch (PDOException $e) {
                // Handle or log error if settings table doesn't exist yet
                error_log("AppSettings DB load failed: " . $e->getMessage());
            }
        }

        self::$isLoaded = true;
    }

    /**
     * Retrieve a setting by key, with optional default fallback.
     */
    public static function get(string $key, $default = null) 
    {
        return self::$settings[$key] ?? $default;
    }

    /**
     * Update a setting in memory and in the database.
     */
    public static function set(PDO $db, string $key, $value): bool 
    {
        self::$settings[$key] = $value;

        $stmt = $db->prepare("
            INSERT INTO settings (setting_key, setting_value) 
            VALUES (:key, :val) 
            ON DUPLICATE KEY UPDATE setting_value = :val_update
        ");

        return $stmt->execute([
            ':key'        => $key,
            ':val'        => $value,
            ':val_update' => $value,
        ]);
    }
}