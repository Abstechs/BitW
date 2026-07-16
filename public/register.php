<?php
require_once "../core/auth.php";
require_once "../core/config.php";
require_once "../core/ui.php";

$title = AppConfig::get("APP_ALIAS") . " Register";
$error = "";
$ref_error = "";
$referrer_name = "";

// 1. Grab referral code from GET parameter or fallback to POST form data
$ref_code = trim($_GET['ref'] ?? ($_POST['referral_code'] ?? ''));

// 2. Validate code against database if provided
if (!empty($ref_code)) {
    $referrer = getUserByReferralCode($ref_code);
    if ($referrer) {
        $referrer_name = $referrer['username'];
    } else {
        $ref_error = "Invalid referral code. Leaving field empty.";
        $ref_code = ""; // Safe fallback: ignore the code so it stays optional
    }
}

if ($_POST) {
    $data = [
        "username"      => trim($_POST['username']),
        "email"         => trim($_POST['email']),
        "phone"         => trim($_POST['phone']),
        "password"      => $_POST['password'],
        "pin"           => trim($_POST['pin']),
        "referral_code" => trim($_POST['referral_code'] ?? ''), 

        "q1" => $_POST['q1'],
        "a1" => $_POST['a1'],
        "q2" => $_POST['q2'],
        "a2" => $_POST['a2'],
        "q3" => $_POST['q3'],
        "a3" => $_POST['a3']
    ];

    if (empty($data['username']) || empty($data['email']) || empty($data['password'])) {
        $error = "Please fill in all required fields.";
    } else {
        if (registerUser($data)) {
            header("Location: login.php");
            exit;
        } else {
            $error = "Registration failed. Username or Email may already be taken.";
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
    <!-- Bootstrap Icons (just in case they aren't in uiHead) -->
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

        /* Completely overhauled, modern Inputs */
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
            padding: 12px 16px 12px 42px !important; /* Left padding makes room for icon */
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

        .btn-premium-outline {
            background: transparent;
            border: 1px solid var(--border-color);
            color: var(--text-high-contrast);
            font-weight: 600;
            padding: 12px;
            border-radius: 10px;
            transition: all 0.2s ease;
        }

        .btn-premium-outline:hover {
            background: rgba(255, 255, 255, 0.05);
            border-color: rgba(255, 255, 255, 0.2);
        }

        /* Progress Steps styling */
        .register-step {
            display: none;
            opacity: 0;
            transform: scale(0.98);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .register-step.active {
            display: block;
            opacity: 1;
            transform: scale(1);
        }

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
            <h3 class="fw-bold mb-1" style="letter-spacing: -0.02em; color: #ffffff;"><?= AppConfig::get("APP_ALIAS") ?></h3>
            <p class="small text-muted">Create your secure dashboard account</p>
            
            <!-- Progress Tracker -->
            <div class="step-indicator-wrapper d-flex gap-1 mt-3">
                <div id="ind-1" class="step-indicator active"></div>
                <div id="ind-2" class="step-indicator"></div>
            </div>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger py-2 px-3 small border-0 mb-4" style="background: rgba(239, 68, 68, 0.15); color: #fca5a5;">
                <i class="bi bi-shield-exclamation me-1"></i> <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST" id="regForm">
            
            <!-- STEP 1: Primary Credentials -->
            <div id="step-1" class="register-step active">
                <div class="mb-3">
                    <label class="premium-label">Username</label>
                    <div class="premium-input-group">
                        <input name="username" id="reg-username" class="form-control premium-control" placeholder="Choose a username" value="<?= htmlspecialchars($_POST['username'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required>
                        <i class="bi bi-person"></i>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="premium-label">Email Address</label>
                    <div class="premium-input-group">
                        <input name="email" id="reg-email" type="email" class="form-control premium-control" placeholder="name@example.com" value="<?= htmlspecialchars($_POST['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required>
                        <i class="bi bi-envelope"></i>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="premium-label">Phone Number</label>
                    <div class="premium-input-group">
                        <input name="phone" type="tel" class="form-control premium-control" placeholder="+1 (555) 000-0000" value="<?= htmlspecialchars($_POST['phone'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                        <i class="bi bi-telephone"></i>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="premium-label">Password</label>
                    <div class="premium-input-group">
                        <input name="password" id="reg-password" type="password" class="form-control premium-control" placeholder="Create secure password" required>
                        <i class="bi bi-lock"></i>
                    </div>
                </div>

                <!-- Referral Section -->
                <div class="mb-4">
                    <label class="premium-label">Referral Code (Optional)</label>
                    <div class="premium-input-group">
                        <input type="text" name="referral_code" class="form-control premium-control" placeholder="Enter code" value="<?= htmlspecialchars($ref_code, ENT_QUOTES, 'UTF-8') ?>">
                        <i class="bi bi-gift"></i>
                    </div>
                    
                    <?php if (!empty($referrer_name)): ?>
                        <div class="form-text text-success small mt-2 d-flex align-items-center">
                            <i class="bi bi-patch-check-fill me-1"></i> Invited by: <strong class="ms-1"><?= htmlspecialchars($referrer_name) ?></strong>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($ref_error)): ?>
                        <div class="form-text text-warning small mt-2 d-flex align-items-center">
                            <i class="bi bi-exclamation-circle-fill me-1"></i> <?= htmlspecialchars($ref_error) ?>
                        </div>
                    <?php endif; ?>
                </div>

                <button type="button" onclick="goToStep(2)" class="btn btn-premium w-100">
                    Continue <i class="bi bi-arrow-right ms-1"></i>
                </button>
            </div>


            <!-- STEP 2: Security & Identity Recovery -->
            <div id="step-2" class="register-step">
                <div class="mb-3">
                    <label class="premium-label d-block text-center mb-1">4-Digit Security PIN</label>
                    <div class="premium-input-group d-flex justify-content-center">
                        <!-- Centered PIN, removed the icon space for a crisp numeric input style -->
                        <input name="pin" id="reg-pin" class="form-control premium-control premium-control-no-icon text-center fs-4 fw-bold" placeholder="0000" maxlength="4" pattern="[0-9]{4}" value="<?= htmlspecialchars($_POST['pin'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required style="letter-spacing: 0.5em; width: 160px;">
                    </div>
                    <div class="form-text text-center text-muted small mt-2">Required for validating transfers and security updates.</div>
                </div>

                <hr class="my-4" style="border-color: var(--border-color);">
                <p class="small fw-semibold mb-3" style="color: var(--text-high-contrast);"><i class="bi bi-shield-key me-2 text-primary"></i> Account Security Questions</p>

                <div class="mb-3">
                    <input name="q1" class="form-control premium-control premium-control-no-icon mb-2" placeholder="Security Question 1" required>
                    <input name="a1" class="form-control premium-control premium-control-no-icon" placeholder="Your Answer" required>
                </div>

                <div class="mb-3">
                    <input name="q2" class="form-control premium-control premium-control-no-icon mb-2" placeholder="Security Question 2" required>
                    <input name="a2" class="form-control premium-control premium-control-no-icon" placeholder="Your Answer" required>
                </div>

                <div class="mb-4">
                    <input name="q3" class="form-control premium-control premium-control-no-icon mb-2" placeholder="Security Question 3" required>
                    <input name="a3" class="form-control premium-control premium-control-no-icon" placeholder="Your Answer" required>
                </div>

                <div class="d-flex gap-2">
                    <button type="button" onclick="goToStep(1)" class="btn btn-premium-outline w-50">
                        Back
                    </button>
                    <button type="submit" class="btn btn-premium w-50">
                        Complete Setup
                    </button>
                </div>
            </div>

            <!-- Footer Links -->
            <div class="d-flex justify-content-between mt-4 pt-3 border-top" style="border-color: var(--border-color) !important;">
                <a href="login.php" class="premium-link small">Already registered? <span class="text-primary font-semibold">Login</span></a>
                <a href="reset-password.php" class="premium-link small">Forgot password?</a>
            </div>
        </form>
    </div>
</div>

<script>
    function goToStep(step) {
        if (step === 2) {
            // Validate step 1 fields
            const username = document.getElementById('reg-username');
            const email = document.getElementById('reg-email');
            const password = document.getElementById('reg-password');

            if (!username.checkValidity() || !email.checkValidity() || !password.checkValidity()) {
                username.reportValidity() || email.reportValidity() || password.reportValidity();
                return;
            }

            document.getElementById('step-1').classList.remove('active');
            document.getElementById('step-2').classList.add('active');
            document.getElementById('ind-2').classList.add('active');
        } else if (step === 1) {
            document.getElementById('step-2').classList.remove('active');
            document.getElementById('step-1').classList.add('active');
            document.getElementById('ind-2').classList.remove('active');
        }
    }
</script>

</body>
</html>