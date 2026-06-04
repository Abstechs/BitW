<?php

require_once "../core/auth.php";
require_once "../core/config.php";
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

                if (strcasecmp($answer1, $user['secret_a1']) !== 0 ||
                    strcasecmp($answer2, $user['secret_a2']) !== 0 ||
                    strcasecmp($answer3, $user['secret_a3']) !== 0) {
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
</head>

<body>

<div class="d-flex justify-content-center align-items-center vh-100">

    <div class="bitw-card" style="width:420px;">

        <h3 class="text-center mb-3">
            <?= AppConfig::get("APP_ALIAS") ?> Password Reset
        </h3>

        <p class="text-center text-light mb-4">
            Recover your <?= AppConfig::get("APP_NAME") ?> account securely.
        </p>

        <?php if ($error): ?>
            <div class="alert alert-danger">
                <?= $error ?>
            </div>
        <?php endif; ?>

        <?php if ($step === 1): ?>
            <form method="POST">
                <input type="hidden" name="action" value="find">
                <input name="email" type="email" class="form-control mb-2" placeholder="Email" value="<?= htmlspecialchars($email) ?>">
                <button class="bitw-btn-primary w-100">
                    Find Account
                </button>
            </form>
        <?php else: ?>
            <form method="POST">
                <input type="hidden" name="action" value="reset">
                <input type="hidden" name="user_id" value="<?= htmlspecialchars($user['id']) ?>">

                <div class="mb-2">
                    <label class="form-label text-light"><?= htmlspecialchars($user['secret_q1']) ?></label>
                    <input name="a1" class="form-control" placeholder="Answer">
                </div>
                <div class="mb-2">
                    <label class="form-label text-light"><?= htmlspecialchars($user['secret_q2']) ?></label>
                    <input name="a2" class="form-control" placeholder="Answer">
                </div>
                <div class="mb-2">
                    <label class="form-label text-light"><?= htmlspecialchars($user['secret_q3']) ?></label>
                    <input name="a3" class="form-control" placeholder="Answer">
                </div>

                <input name="password" type="password" class="form-control mb-2" placeholder="New Password">
                <input name="confirm_password" type="password" class="form-control mb-3" placeholder="Confirm Password">

                <button class="bitw-btn-primary w-100">
                    Reset Password
                </button>
            </form>
        <?php endif; ?>

        <div class="text-center mt-3">
            <a href="login.php" class="text-decoration-none">Back to Login</a>
            <span class="mx-2 text-secondary">|</span>
            <a href="register.php" class="text-decoration-none">Create an Account</a>
        </div>

    </div>

</div>

</body>
</html>
