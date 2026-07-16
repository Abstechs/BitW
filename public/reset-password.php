<?php

require_once "../core/auth.php";
require_once "../core/config.php";
require_once "../core/helpers.php";
require_once "../core/ui.php";

$title = AppConfig::get("APP_ALIAS") . " Reset Password";
$error = "";
$success = "";
$step = 1;
$email = "";
$user = null;

if ($_POST) {
    $action = $_POST['action'] ?? 'find';

    if ($action === 'find') {
        $email = clean($_POST['email'] ?? '');

        if (!$email) {
            $error = "Enter your account email address.";
        } else {
            $user = getUserByEmail($email);

            if (!$user) {
                $error = "No account was found for that email address.";
            } else {
                $step = 2;
            }
        }
    }

    if ($action === 'reset') {
        $userId = $_POST['user_id'] ?? null;
        $password = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        if (!$userId) {
            $error = "Invalid reset request.";
        } elseif (!$password || !$confirmPassword) {
            $error = "Enter and confirm your new password.";
        } elseif ($password !== $confirmPassword) {
            $error = "The passwords do not match.";
        } else {
            $user = getUser($userId);

            if (!$user) {
                $error = "Invalid account details.";
            } else {
                $answer1 = trim($_POST['a1'] ?? '');
                $answer2 = trim($_POST['a2'] ?? '');
                $answer3 = trim($_POST['a3'] ?? '');

                if (strcasecmp($answer1, $user['a1']) !== 0 ||
                    strcasecmp($answer2, $user['a2']) !== 0 ||
                    strcasecmp($answer3, $user['a3']) !== 0) {
                    $error = "One or more security answers are incorrect.";
                    $step = 2;
                } else {
                    if (updateUserPassword($userId, $password)) {
                        header("Location: login.php?reset=success");
                        exit;
                    }

                    $error = "Unable to update your password. Please try again.";
                    $step = 2;
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <?php uiHead($title); ?>
    
    <!-- Premium Google Font for modern dashboard aesthetic -->
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">

    <style>
        :root {
            --brand-primary: #3b82f6; /* Modern electric blue */
            --brand-primary-glow: rgba(59, 130, 246, 0.15);
            --bg-gradient-start: #0b0f19; /* Deep space dark */
            --bg-gradient-end: #111827;
            --card-bg: rgba(17, 24, 39, 0.7);
            --text-high-contrast: #f3f4f6; /* Off-white for perfect reading */
            --text-subtle: #9ca3af; /* Crisp medium gray for labels */
            --input-bg: rgba(255, 255, 255, 0.03);
            --border-color: rgba(255, 255, 255, 0.08);
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: radial-gradient(circle at 50% 0%, #1e293b 0%, var(--bg-gradient-start) 70%);
            color: var(--text-high-contrast);
            min-height: 100vh;
        }

        /* Glassmorphic Premium Card */
        .premium-card {
            background: var(--card-bg);
            backdrop-filter: blur(16px);
            border: 1px solid var(--border-color);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.4), 
                        inset 0 1px 0 rgba(255, 255, 255, 0.1);
            border-radius: 16px;
            transition: all 0.3s ease;
        }

        /* High-contrast Label Colors */
        .premium-label {
            color: var(--text-subtle);
            font-weight: 500;
            font-size: 0.85rem;
            letter-spacing: 0.03em;
            margin-bottom: 6px;
            display: inline-block;
        }

        /* Modern Inputs with Icons */
        .premium-input-group {
            position: relative;
        }

        .premium-input-group i {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-subtle);
            font-size: 1.1rem;
            transition: color 0.2s ease;
        }

        .premium-control {
            background: var(--input-bg) !important;
            border: 1px solid var(--border-color) !important;
            color: var(--text-high-contrast) !important;
            padding: 12px 16px 12px 42px !important; /* Left padding space for icon */
            border-radius: 10px !important;
            font-size: 0.95rem;
            transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1) !important;
        }

        .premium-control::placeholder {
            color: rgba(156, 163, 175, 0.4) !important;
        }

        .premium-control:focus {
            border-color: var(--brand-primary) !important;
            box-shadow: 0 0 0 4px var(--brand-primary-glow) !important;
            background: rgba(255, 255, 255, 0.05) !important;
        }

        .premium-control:focus + i {
            color: var(--brand-primary);
        }

        /* Custom style specifically for Security Questions (no icons needed) */
        .premium-control-no-icon {
            padding-left: 16px !important;
        }

        /* Premium Buttons */
        .btn-premium {
            background: linear-gradient(135deg, var(--brand-primary), #2563eb);
            color: #ffffff;
            border: none;
            font-weight: 600;
            padding: 12px;
            border-radius: 10px;
            transition: all 0.2s ease;
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.2);
        }

        .btn-premium:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 20px rgba(37, 99, 235, 0.35);
            filter: brightness(1.1);
        }

        /* Progress Steps styling */
        .step-indicator-wrapper {
            background: rgba(255, 255, 255, 0.04);
            border-radius: 10px;
            padding: 4px;
        }

        .step-indicator {
            height: 4px;
            width: 50%;
            background-color: transparent;
            border-radius: 2px;
            transition: background-color 0.4s ease;
        }

        .step-indicator.active {
            background-color: var(--brand-primary);
            box-shadow: 0 0 8px var(--brand-primary);
        }

        /* Links styling */
        .premium-link {
            color: var(--text-subtle);
            transition: color 0.2s ease;
            text-decoration: none;
        }
        .premium-link:hover {
            color: var(--brand-primary);
        }
    </style>
</head>
<body>

<div class="d-flex justify-content-center align-items-center min-vh-100 my-5 py-4">
    <div class="premium-card p-4 p-md-5" style="width: 440px;">
        
        <!-- Header -->
        <div class="text-center mb-4">
            <h3 class="fw-bold mb-1" style="letter-spacing: -0.02em; color: #ffffff;"><?= AppConfig::get("APP_ALIAS") ?> Reset</h3>
            <p class="small text-muted">Recover your <?= AppConfig::get("APP_NAME") ?> account</p>
            
            <!-- Dynamic Progress Tracker matching active step -->
            <div class="step-indicator-wrapper d-flex gap-1 mt-3">
                <div class="step-indicator active"></div>
                <div class="step-indicator <?= $step === 2 ? 'active' : '' ?>"></div>
            </div>
        </div>

        <!-- System Alerts -->
        <?php if ($error): ?>
            <div class="alert alert-danger py-2 px-3 small border-0 mb-4" style="background: rgba(239, 68, 68, 0.15); color: #fca5a5;">
                <i class="bi bi-shield-exclamation me-1"></i> <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <?php if ($step === 1): ?>
            <!-- STEP 1: Find Account -->
            <form method="POST">
                <input type="hidden" name="action" value="find">
                
                <div class="mb-4">
                    <label class="premium-label">Account Email Address</label>
                    <div class="premium-input-group">
                        <input name="email" type="email" class="form-control premium-control" placeholder="yourname@example.com" value="<?= htmlspecialchars($email) ?>" required>
                        <i class="bi bi-envelope"></i>
                    </div>
                </div>

                <button type="submit" class="btn btn-premium w-100">
                    Find Account <i class="bi bi-search ms-1"></i>
                </button>
            </form>

        <?php else: ?>
            <!-- STEP 2: Identity Recovery Questions & Password Override -->
            <form method="POST">
                <input type="hidden" name="action" value="reset">
                <input type="hidden" name="user_id" value="<?= htmlspecialchars($user['id']) ?>">

                <p class="small fw-semibold mb-3" style="color: var(--text-high-contrast);"><i class="bi bi-shield-lock me-2 text-primary"></i> Answer Identity Questions</p>

                <div class="mb-3">
                    <label class="premium-label"><?= htmlspecialchars($user['q1']) ?></label>
                    <input name="a1" class="form-control premium-control premium-control-no-icon" placeholder="Answer 1" required>
                </div>

                <div class="mb-3">
                    <label class="premium-label"><?= htmlspecialchars($user['q2']) ?></label>
                    <input name="a2" class="form-control premium-control premium-control-no-icon" placeholder="Answer 2" required>
                </div>

                <div class="mb-4">
                    <label class="premium-label"><?= htmlspecialchars($user['q3']) ?></label>
                    <input name="a3" class="form-control premium-control premium-control-no-icon" placeholder="Answer 3" required>
                </div>

                <hr class="my-4" style="border-color: var(--border-color);">
                <p class="small fw-semibold mb-3" style="color: var(--text-high-contrast);"><i class="bi bi-key me-2 text-primary"></i> Define New Credentials</p>

                <div class="mb-3">
                    <label class="premium-label">New Password</label>
                    <div class="premium-input-group">
                        <input name="password" type="password" class="form-control premium-control" placeholder="Minimum 8 characters" required>
                        <i class="bi bi-lock"></i>
                    </div>
                </div>

                <div class="mb-4">
                    <label class="premium-label">Confirm Password</label>
                    <div class="premium-input-group">
                        <input name="confirm_password" type="password" class="form-control premium-control" placeholder="Confirm password" required>
                        <i class="bi bi-check2-circle"></i>
                    </div>
                </div>

                <button type="submit" class="btn btn-premium w-100">
                    Reset Password <i class="bi bi-shield-check ms-1"></i>
                </button>
            </form>
        <?php endif; ?>

        <!-- Footer Links -->
        <div class="d-flex justify-content-between mt-4 pt-3 border-top" style="border-color: var(--border-color) !important;">
            <a href="login.php" class="premium-link small"><i class="bi bi-arrow-left me-1"></i> Back to Login</a>
            <a href="register.php" class="premium-link small">Create Account</a>
        </div>
    </div>
</div>

</body>
</html>