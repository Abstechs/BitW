<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

class Session {

    public static function set($key, $value) {
        $_SESSION[$key] = $value;
    }

    public static function get($key) {
        return $_SESSION[$key] ?? null;
    }

    public static function has($key) {
        return isset($_SESSION[$key]);
    }

    public static function forget() {
        session_destroy();
    }
}