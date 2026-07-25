<?php
// core/Oracle.php

require_once __DIR__ . '/Settings.php';

class Oracle {
    /**
     * Create a new post in the Oracle system.
     */
    public static function createPost($pdo, $authorId, $title, $content, $type = 'premium_insight') {
        // Check if user is premium for insights
        if ($type === 'premium_insight') {
            if (!self::isPremium($pdo, $authorId)) {
                return ['success' => false, 'message' => 'Only premium users can post insights.'];
            }
        }

        $stmt = $pdo->prepare("INSERT INTO oracle_posts (author_id, title, content, post_type) VALUES (?, ?, ?, ?)");
        $stmt->execute([$authorId, $title, $content, $type]);
        
        return ['success' => true, 'id' => $pdo->lastInsertId()];
    }

    /**
     * Check if a user has an active premium subscription.
     */
    public static function isPremium($pdo, $userId) {
        if (Settings::get($pdo, 'premium_system_enabled', '0') === '0') return false;

        $stmt = $pdo->prepare("SELECT id FROM premium_subscriptions WHERE user_id = ? AND status = 'active' AND expiry_date > NOW()");
        $stmt->execute([$userId]);
        return (bool)$stmt->fetchColumn();
    }

    /**
     * Fetch the latest verified posts from the Oracle.
     */
    public static function getFeed($pdo, $limit = 10) {
        $stmt = $pdo->prepare("SELECT p.*, u.username 
                               FROM oracle_posts p 
                               JOIN users u ON p.author_id = u.id 
                               WHERE p.is_verified = 1 OR p.post_type = 'admin_blog'
                               ORDER BY p.created_at DESC LIMIT ?");
        $stmt->execute([$limit]);
        return $stmt->fetchAll();
    }
}
