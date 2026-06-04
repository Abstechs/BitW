<?php

require_once "../core/auth.php";

if (!isLoggedIn()) {
    header("Location: login.php");
    exit;
}
?>

<link href="https://cdn.tailwindcss.com" rel="stylesheet">

<div class="bg-[#0b0f1a] text-white min-h-screen p-6">

    <h1 class="text-2xl font-bold">
        Welcome, <?= $_SESSION['username'] ?>
    </h1>

    <p class="text-gray-400">BitW Dashboard Active</p>

</div>