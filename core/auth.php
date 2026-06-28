<?php
// core/auth.php
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/helpers.php';

function loginUser($identifier, $password) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? OR phone = ? LIMIT 1");
        $stmt->execute([$identifier, $identifier]);
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

function registerUser($data) {
    global $pdo;
    
    try {
        $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);
        $referralCode = strtoupper(substr(generateRef('BITW'), 0, 8));
        $referredBy = null;

        if (!empty($data['referral_code'])) {
            $referrerStmt = $pdo->prepare("SELECT id FROM users WHERE referral_code = ? LIMIT 1");
            $referrerStmt->execute([$data['referral_code']]);
            $referrer = $referrerStmt->fetch(PDO::FETCH_ASSOC);
            if ($referrer) {
                $referredBy = $referrer['id'];
            }
        }
        
        $stmt = $pdo->prepare("INSERT INTO users (username, email, phone, password, pin, referral_code, referred_by, q1, a1, q2, a2, q3, a3) 
                               VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        
        $result = $stmt->execute([
            $data['username'],
            $data['email'],
            $data['phone'],
            $hashedPassword,
            $data['pin'],
            $referralCode,
            $referredBy,
            $data['q1'],
            $data['a1'],
            $data['q2'],
            $data['a2'],
            $data['q3'],
            $data['a3']
        ]);
        
        // Show error if query fails
        if (!$result) {
            $errorInfo = $stmt->errorInfo();
            die("Database Error: " . $errorInfo[2]);
        }
        
        return true;
        
    } catch (Exception $e) {
        die("Exception: " . $e->getMessage());
    }
}

function getUser($user_id) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? LIMIT 1");
        $stmt->execute([$user_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("GetUser Error: " . $e->getMessage());
        return false;
    }
}

function getUserByEmail($email) {
    global $pdo;

    try {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("GetUserByEmail Error: " . $e->getMessage());
        return false;
    }
}

function getUserByReferralCode($referralCode) {
    global $pdo;

    try {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE referral_code = ? LIMIT 1");
        $stmt->execute([$referralCode]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("GetUserByReferralCode Error: " . $e->getMessage());
        return false;
    }
}

function updateUserPassword($user_id, $password) {
    global $pdo;

    try {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
        return $stmt->execute([$hashedPassword, $user_id]);
    } catch (Exception $e) {
        error_log("UpdateUserPassword Error: " . $e->getMessage());
        return false;
    }
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}