<?php

class AppSettings {
    private static $settings = null;
    private static $settingsFile = __DIR__ . '/../config/settings.php';

    public static function load() {
        if (self::$settings !== null) {
            return self::$settings;
        }

        if (!file_exists(self::$settingsFile)) {
            self::initialize();
        }

        self::$settings = require self::$settingsFile;
        if (!is_array(self::$settings)) {
            self::$settings = [];
        }
        return self::$settings;
    }

    public static function get($key, $default = null) {
        self::load();
        return self::$settings[$key] ?? $default;
    }

    public static function set($key, $value) {
        self::load();
        self::$settings[$key] = $value;
        return self::save();
    }

    public static function all() {
        self::load();
        return self::$settings;
    }

    public static function save() {
        $content = "<?php\n\nreturn [\n";
        foreach (self::$settings as $key => $value) {
            $escaped = var_export($value, true);
            $content .= "    '" . str_replace("'", "\\'", $key) . "' => $escaped,\n";
        }
        $content .= "];\n";

        return file_put_contents(self::$settingsFile, $content) !== false;
    }

    public static function initialize() {
        $defaultSettings = [
            'PAYSTACK_SECRET' => '',
            'PAYSTACK_PUBLIC' => '',
            'PAYSTACK_DEFAULT_ACCOUNT' => '3003728830',
            'PAYSTACK_DEFAULT_BANK' => 'Kuda Bank',
            'PAYSTACK_DEFAULT_ACCOUNT_NAME' => 'Abstech Integrated Services',
            'MANUAL_DEPOSIT_ENABLED' => true,
            'CRYPTO_DEPOSIT_ENABLED' => false,
            'DEFAULT_PLAN_IMAGE' => '/assets/images/default-plan.svg'
        ];

        if (!is_dir(dirname(self::$settingsFile))) {
            mkdir(dirname(self::$settingsFile), 0755, true);
        }

        $content = "<?php\n\nreturn [\n";
        foreach ($defaultSettings as $key => $value) {
            $content .= "    '" . str_replace("'", "\\'", $key) . "' => " . var_export($value, true) . ",\n";
        }
        $content .= "];\n";
        file_put_contents(self::$settingsFile, $content);
        self::$settings = $defaultSettings;
        return self::$settings;
    }
}

AppSettings::load();
