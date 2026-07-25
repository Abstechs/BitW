<?php
// classes/AppSettings.php

class AppSettings 
{
    private static array $settings = [];
    private static bool $isLoaded = false;

    /**
     * Load settings into memory from the database and config file.
     */
    public static function load(?PDO $db = null): void 
    {
        if (self::$isLoaded) {
            return;
        }

        // 1. Load static file config if available
        $configFile = __DIR__ . '/../config/settings.php';
        if (file_exists($configFile)) {
            $fileSettings = include $configFile;
            if (is_array($fileSettings)) {
                self::$settings = $fileSettings;
            }
        }

        // 2. Load dynamic database settings (overrides/complements file settings)
        $pdo = $db ?? ($GLOBALS['pdo'] ?? null);
        if ($pdo instanceof PDO) {
            try {
                $stmt = $pdo->query("SELECT setting_key, setting_value FROM system_settings");
                if ($stmt) {
                    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                        self::$settings[$row['setting_key']] = $row['setting_value'];
                    }
                }
            } catch (PDOException $e) {
                // Table might not exist yet or query failed silently
                error_log("AppSettings DB load notice: " . $e->getMessage());
            }
        }

        self::$isLoaded = true;
    }

    /**
     * Retrieve all loaded settings array.
     */
    public static function all(): array 
    {
        if (!self::$isLoaded) {
            self::load();
        }
        return self::$settings;
    }

    /**
     * Retrieve a specific setting key with an optional fallback default.
     */
    public static function get(string $key, $default = null) 
    {
        if (!self::$isLoaded) {
            self::load();
        }
        return self::$settings[$key] ?? $default;
    }

    /**
     * Persist or update a setting in memory and in the database.
     */
    public static function set(string $key, $value, ?PDO $db = null): bool 
    {
        self::$settings[$key] = $value;

        $pdo = $db ?? ($GLOBALS['pdo'] ?? null);
        if (!$pdo instanceof PDO) {
            return false;
        }

        try {
            $stmt = $pdo->prepare("
                INSERT INTO system_settings (setting_key, setting_value) 
                VALUES (:key, :val) 
                ON DUPLICATE KEY UPDATE setting_value = :val_update
            ");

            return $stmt->execute([
                ':key'        => $key,
                ':val'        => (string)$value,
                ':val_update' => (string)$value,
            ]);
        } catch (PDOException $e) {
            error_log("AppSettings set error: " . $e->getMessage());
            return false;
        }
    }
}