<?php
// core/notifications.php
require_once __DIR__ . '/database.php';

function addNotification($user_id, $title, $message = "") {
    global $pdo;
    $stmt = $pdo->prepare("INSERT INTO notifications (user_id, title, message) VALUES (?, ?, ?)");
    $stmt->execute([$user_id, $title, $message]);
}

function getNotifications($user_id, $limit = 10) {
    global $pdo;
    $limit = (int) $limit;
    $stmt = $pdo->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT " . $limit);
    $stmt->execute([$user_id]);
    return $stmt->fetchAll();
}
?>