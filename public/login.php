<?php

require_once "../core/auth.php";
require_once "../core/config.php";
require_once "../core/ui.php";

$error = "";
$success = "";

if (isset($_GET['reset']) && $_GET['reset'] === 'success') {
    $success = "Your password has been reset successfully. Please login.";
}

if ($_POST) {
    $identifier = trim($_POST['identifier'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!$identifier || !$password) {
        $error = "Enter your email/phone and password.";
    } else {
        $user = loginUser($identifier, $password);

        if ($user) {
            if ($user['is_admin'] == 1) {
                header("Location: admin/index.php");
            } else {
                header("Location: dashboard.php");
            }
            exit;
        }

        $error = "Invalid email/phone or password.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <?php uiHead(AppConfig::get("APP_ALIAS") . " Login"); ?>
    
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
            padding: 12px 16px 12px 42px !important; /* Spacing for the icon */
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
    <div class="premium-card p-4 p-md-5" style="width: 400px;">
        
        <!-- Header -->
        <div class="text-center mb-4">
            <h3 class="fw-bold mb-1" style="letter-spacing: -0.02em; color: #ffffff;"><?= AppConfig::get("APP_ALIAS") ?> Login</h3>
            <p class="small text-muted">Welcome to <?= AppConfig::get("APP_NAME") ?></p>
        </div>

        <!-- System Alerts -->
        <?php if ($success): ?>
            <div class="alert alert-success py-2 px-3 small border-0 mb-4" style="background: rgba(16, 185, 129, 0.15); color: #a7f3d0;">
                <i class="bi bi-check-circle-fill me-1"></i> <?= $success ?>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-danger py-2 px-3 small border-0 mb-4" style="background: rgba(239, 68, 68, 0.15); color: #fca5a5;">
                <i class="bi bi-shield-exclamation me-1"></i> <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <!-- Form -->
        <form method="POST">
            
            <div class="mb-3">
                <label class="premium-label">Email or Phone</label>
                <div class="premium-input-group">
                    <input name="identifier" class="form-control premium-control" placeholder="Enter email or phone" value="<?= htmlspecialchars($_POST['identifier'] ?? '') ?>" required>
                    <i class="bi bi-envelope"></i>
                </div>
            </div>

            <div class="mb-4">
                <label class="premium-label">Password</label>
                <div class="premium-input-group">
                    <input name="password" type="password" class="form-control premium-control" placeholder="Enter password" required>
                    <i class="bi bi-lock"></i>
                </div>
            </div>

            <button type="submit" class="btn btn-premium w-100 py-2">
                Sign In <i class="bi bi-box-arrow-in-right ms-1"></i>
            </button>

            <!-- Footer Links -->
            <div class="d-flex justify-content-between mt-4 pt-3 border-top" style="border-color: var(--border-color) !important;">
                <a href="register.php" class="premium-link small">Create an account</a>
                <a href="reset-password.php" class="premium-link small">Forgot password?</a>
            </div>
            
        </form>
    </div>
</div>

</body>
</html>