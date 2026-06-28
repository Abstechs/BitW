<?php

require_once "../core/auth.php";
require_once "../core/config.php";
require_once "../core/ui.php";

$title = AppConfig::get("APP_ALIAS") . " Register";
$error = "";

if ($_POST) {

    $data = [
        "username" => $_POST['username'],
        "email" => $_POST['email'],
        "phone" => $_POST['phone'],
        "password" => $_POST['password'],
        "pin" => $_POST['pin'],
        "referral_code" => $_GET['ref'] ?? ($_POST['referral_code'] ?? ''),

        "q1" => $_POST['q1'],
        "a1" => $_POST['a1'],
        "q2" => $_POST['q2'],
        "a2" => $_POST['a2'],
        "q3" => $_POST['q3'],
        "a3" => $_POST['a3']
    ];

    if (registerUser($data)) {
        header("Location: login.php");
        exit;
    } else {
        $error = "Registration failed. Please check your details and try again.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <?php uiHead($title); ?>
</head>

<body>

<div class="d-flex justify-content-center align-items-center vh-100">

    <form method="POST" class="bitw-card" style="width:420px;">

        <h3 class="text-center mb-3">
            <?= AppConfig::get("APP_ALIAS") ?> Registration
        </h3>

        <p class="text-center text-light mb-4">
            Create your <?= AppConfig::get("APP_NAME") ?> account
        </p>

        <?php if ($error): ?>
            <div class="alert alert-danger">
                <?= $error ?>
            </div>
        <?php endif; ?>

        <input name="username" class="form-control mb-2" placeholder="Username" value="<?= htmlspecialchars(
            $_POST['username'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
        <input name="email" class="form-control mb-2" placeholder="Email" value="<?= htmlspecialchars(
            $_POST['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
        <input name="phone" class="form-control mb-2" placeholder="Phone" value="<?= htmlspecialchars(
            $_POST['phone'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
        <input name="password" type="password" class="form-control mb-2" placeholder="Password">
        <input name="pin" class="form-control mb-2" placeholder="4-digit PIN" value="<?= htmlspecialchars(
            $_POST['pin'] ?? '', ENT_QUOTES, 'UTF-8') ?>">

        <hr class="my-3 text-secondary">

        <input name="q1" class="form-control mb-2" placeholder="Security Question 1">
        <input name="a1" class="form-control mb-2" placeholder="Answer">

        <input name="q2" class="form-control mb-2" placeholder="Security Question 2">
        <input name="a2" class="form-control mb-2" placeholder="Answer">

        <input name="q3" class="form-control mb-2" placeholder="Security Question 3">
        <input name="a3" class="form-control mb-2" placeholder="Answer">

        <button class="bitw-btn-primary w-100 mt-2">
            Create Account
        </button>

        <div class="d-flex justify-content-between mt-3 small">
            <a href="login.php">Already have an account?</a>
            <a href="reset-password.php">Forgot password?</a>
        </div>

    </form>

</div>

</body>
</html>