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
        "referral_code" => trim($_POST['referral_code'] ?? ''), // Read hidden/submitted field

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
</head>
<body>

<div class="d-flex justify-content-center align-items-center vh-100 my-5">
    <form method="POST" class="bitw-card" style="width:420px;">
        <h3 class="text-center mb-3">
            <?= AppConfig::get("APP_ALIAS") ?> Registration
        </h3>
        <p class="text-center text-light mb-4">
            Create your <?= AppConfig::get("APP_NAME") ?> account
        </p>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <input name="username" class="form-control mb-2" placeholder="Username" value="<?= htmlspecialchars($_POST['username'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required>
        <input name="email" type="email" class="form-control mb-2" placeholder="Email" value="<?= htmlspecialchars($_POST['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required>
        <input name="phone" class="form-control mb-2" placeholder="Phone" value="<?= htmlspecialchars($_POST['phone'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
        <input name="password" type="password" class="form-control mb-2" placeholder="Password" required>
        <input name="pin" class="form-control mb-2" placeholder="4-digit PIN" maxlength="4" value="<?= htmlspecialchars($_POST['pin'] ?? '', ENT_QUOTES, 'UTF-8') ?>">

        <!-- REFERRAL FIELDS -->
        <div class="mb-2">
            <!-- Visible, editable field for manual input or auto-populated review -->
            <input type="text" name="referral_code" class="form-control" placeholder="Referral Code (Optional)" value="<?= htmlspecialchars($ref_code, ENT_QUOTES, 'UTF-8') ?>">
            
            <?php if (!empty($referrer_name)): ?>
                <small class="text-success d-block mt-1">Invited by: <strong><?= htmlspecialchars($referrer_name) ?></strong></small>
            <?php endif; ?>
            
            <?php if (!empty($ref_error)): ?>
                <small class="text-warning d-block mt-1"><?= htmlspecialchars($ref_error) ?></small>
            <?php endif; ?>
        </div>

        <hr class="my-3 text-secondary">

        <input name="q1" class="form-control mb-2" placeholder="Security Question 1">
        <input name="a1" class="form-control mb-2" placeholder="Answer">
        <input name="q2" class="form-control mb-2" placeholder="Security Question 2">
        <input name="a2" class="form-control mb-2" placeholder="Answer">
        <input name="q3" class="form-control mb-2" placeholder="Security Question 3">
        <input name="a3" class="form-control mb-2" placeholder="Answer">

        <button class="bitw-btn-primary w-100 mt-2">Create Account</button>

        <div class="d-flex justify-content-between mt-3 small">
            <a href="login.php">Already have an account?</a>
            <a href="reset-password.php">Forgot password?</a>
        </div>
    </form>
</div>

</body>
</html>