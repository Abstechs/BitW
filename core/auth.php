<?php
// core/auth.php - User & Admin authentication
require_once __DIR__ . '/../config/database.php';

function loginUser($email, $password) {
    global $pdo;
    
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['is_admin'] = $user['is_admin'] ?? 0;
        return $user;
    }
    return false;
}

function registerUser($username, $email, $phone, $password, $referred_by = null) {
    global $pdo;
    
    $hashed = password_hash($password, PASSWORD_DEFAULT);
    $referral_code = strtoupper(substr(md5(uniqid()), 0, 8));
    
    $stmt = $pdo->prepare("INSERT INTO users 
        (username, email, phone, password, referral_code, referred_by, is_admin) 
        VALUES (?, ?, ?, ?, ?, ?, 0)");
    $stmt->execute([$username, $email, $phone, $hashed, $referral_code, $referred_by]);
    
    $user_id = $pdo->lastInsertId();
    
    // Create wallet
    $stmt = $pdo->prepare("INSERT INTO wallets (user_id) VALUES (?)");
    $stmt->execute([$user_id]);
    
    return $user_id;
}

function getUserByEmail($email) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}
?>