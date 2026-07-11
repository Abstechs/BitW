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
        // Start transaction to protect balance adjustments from failure states
        $pdo->beginTransaction();

        // Ensure user account fields do not already conflict
        $checkStmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ? LIMIT 1");
        $checkStmt->execute([$data['username'], $data['email']]);
        if ($checkStmt->fetch()) {
            $pdo->rollBack();
            return false;
        }

        $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);
        // Generates an 8 character token string
        $myNewReferralCode = strtoupper(substr(generateRef('BITW'), 0, 8)); 
        $referredBy = null;

        // Verify the referrer ID exists
        if (!empty($data['referral_code'])) {
            $referrerStmt = $pdo->prepare("SELECT id FROM users WHERE referral_code = ? LIMIT 1");
            $referrerStmt->execute([$data['referral_code']]);
            $referrer = $referrerStmt->fetch(PDO::FETCH_ASSOC);
            if ($referrer) {
                $referredBy = $referrer['id'];
            }
        }
        
        // 1. Create the registering user
        $stmt = $pdo->prepare("INSERT INTO users (username, email, phone, password, pin, referral_code, referred_by, q1, a1, q2, a2, q3, a3) 
                               VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        
        $stmt->execute([
            $data['username'], $data['email'], $data['phone'], $hashedPassword, $data['pin'],
            $myNewReferralCode, $referredBy,
            $data['q1'], $data['a1'], $data['q2'], $data['a2'], $data['q3'], $data['a3']
        ]);
        
        $newUserId = $pdo->lastInsertId();

        // 2. Initialize a blank wallet setup for the new user (Removed non-existent created_at column)
        $initWallet = $pdo->prepare("INSERT INTO wallets (user_id, balance) VALUES (?, 0.00)");
        $initWallet->execute([$newUserId]);

        // 3. Process referral bonuses if a valid sponsor exists
        if ($referredBy) {
            // Fetch bonus configuration value specified by admin settings configurations
            $settings = include __DIR__ . '/../config/settings.php';
            $referralBonusAmount = floatval($settings['REFERRAL_BONUS_AMOUNT'] ?? 500); // Defaults to ₦500 if unassigned

            if ($referralBonusAmount > 0) {
                // Update Referrer's wallet balance
                $creditWallet = $pdo->prepare("UPDATE wallets SET balance = balance + ? WHERE user_id = ?");
                $creditWallet->execute([$referralBonusAmount, $referredBy]);

                // Create clean log references for tracking inside recent activity / logs
                $txReference = 'REF-' . strtoupper(bin2hex(random_bytes(6)));
                $logTx = $pdo->prepare("INSERT INTO transactions (user_id, reference, amount, type, status, gateway, created_at) 
                                       VALUES (?, ?, ?, 'deposit', 'completed', 'referral_bonus', NOW())");
                $logTx->execute([$referredBy, $txReference, $referralBonusAmount]);

                // 3. Insert notification dispatch for the sponsor
                // Insert notification dispatch for the sponsor (Including both title and message fields)
                $notifMsg = "You earned ₦" . number_format($referralBonusAmount, 2) . " from inviting user " . htmlspecialchars($data['username']);
                $addNotif = $pdo->prepare("INSERT INTO notifications (user_id, title, message, created_at) VALUES (?, 'Referral Bonus', ?, NOW())");
                $addNotif->execute([$referredBy, $notifMsg]);
            }
        }
        
        // Commit everything safely
        $pdo->commit();
        return true;
        
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log("Registration Exception: " . $e->getMessage());
        return false;
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