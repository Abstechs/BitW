<?php
// core/auth.php
require_once __DIR__ . '/database.php';

function loginUser($email, $password) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['is_admin'] = $user['is_admin'] ?? 0;
            
            // Auto redirect admin
            if ($user['is_admin'] == 1) {
                header("Location: ../admin/index.php");
                exit;
            }
            return $user;
        }
        return false;
    } catch (Exception $e) {
        error_log("Login Error: " . $e->getMessage());
        return false;
    }
}
?>