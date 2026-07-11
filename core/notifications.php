<?php
// core/notifications.php
require_once __DIR__ . '/database.php';

function addNotification($user_id, $title, $message = "") {
    global $pdo;
    $stmt = $pdo->prepare("INSERT INTO notifications (user_id, title, message) VALUES (?, ?, ?)");
    $stmt->execute([$user_id, $title, $message]);
}

function getNotifications($user_id, $limit = 5) {
    global $pdo;
    try {
        // Fetch specific individual alerts OR system broadcast logs (user_id IS NULL)
        $stmt = $pdo->prepare("SELECT * FROM notifications 
                               WHERE user_id = ? OR user_id IS NULL 
                               ORDER BY created_at DESC LIMIT ?");
        $stmt->bindValue(1, $user_id, PDO::PARAM_INT);
        $stmt->bindValue(2, $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("getNotifications Error: " . $e->getMessage());
        return [];
    }
}
?>