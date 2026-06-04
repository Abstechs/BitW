<?php

class AppConfig {

    private static $config;

    public static function load() {
        self::$config = require __DIR__ . "/../config/app.php";
    }

    public static function get($key) {
        return self::$config[$key] ?? null;
    }
}

AppConfig::load();