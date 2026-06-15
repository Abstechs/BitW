<?php
// core/database.php
session_start(); // Start session early

$config = require __DIR__ . '/../config/database.php';

if (!is_array($config)) {
    die("❌ Config file did not return an array. Check config/database.php");
}

try {
    $pdo = new PDO(
        "mysql:host={$config['host']};dbname={$config['dbname']};charset=utf8mb4",
        $config['user'],
        $config['pass']
    );

    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    global $pdo;   // Make it available everywhere

} catch (PDOException $e) {
    die("❌ DB Connection Failed: " . $e->getMessage() . "<br><br>Check your password in config/database.php");
}
?>