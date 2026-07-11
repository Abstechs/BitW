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

        //$user = loginUser($email, $password);
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
<html>
<head>
    <?php uiHead(AppConfig::get("APP_ALIAS") . " Login"); ?>
</head>

<body>

<div class="d-flex justify-content-center align-items-center vh-100">

    <form method="POST" class="bitw-card" style="width:380px;">

        <h3 class="text-center mb-3">
            <?= AppConfig::get("APP_ALIAS") ?> Login
        </h3>

        <p class="text-center text-light mb-4">
            Welcome to <?= AppConfig::get("APP_NAME") ?>
        </p>

        <?php if ($success): ?>
            <div class="alert alert-success">
                <?= $success ?>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-danger">
                <?= $error ?>
            </div>
        <?php endif; ?>

        <input name="identifier" class="form-control mb-2" placeholder="Email or Phone" value="<?= htmlspecialchars($_POST['identifier'] ?? '') ?>">
        <input name="password" type="password" class="form-control mb-3" placeholder="Password">

        <button class="bitw-btn-primary w-100">
            Login
        </button>

        <div class="d-flex justify-content-between mt-3 small">
            <a href="register.php">Create an account</a>
            <a href="reset-password.php">Forgot password?</a>
        </div>

    </form>

</div>

</body>
</html>