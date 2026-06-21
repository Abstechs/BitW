<?php
// public/admin/index.php
session_start();
require_once __DIR__ . '/../../core/database.php';
require_once __DIR__ . '/../../core/auth.php';

if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    header("Location: ../login.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>BitW Admin Dashboard</title>
    <!-- Tailwind CDN or your existing styles -->
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-900 text-white">
    <div class="flex h-screen">
        <!-- Sidebar -->
        <div class="w-64 bg-black p-4">
            <h1 class="text-2xl font-bold mb-8">BitW Admin</h1>
            <nav>
                <a href="index.php" class="block py-2 px-4 bg-gray-800 rounded">Dashboard</a>
                <a href="users.php" class="block py-2 px-4 hover:bg-gray-800">Users</a>
                <a href="plans.php" class="block py-2 px-4 hover:bg-gray-800">Plans</a>
                <a href="mining.php" class="block py-2 px-4 hover:bg-gray-800">Mining</a>
                <a href="referrals.php" class="block py-2 px-4 hover:bg-gray-800">Referrals</a>
                <a href="transactions.php" class="block py-2 px-4 hover:bg-gray-800">Transactions</a>
            </nav>
        </div>
        
        <!-- Main Content -->
        <div class="flex-1 p-8 overflow-auto">
            <h2 class="text-3xl font-bold mb-6">Admin Overview</h2>
            <p>Welcome, Admin! System management panel is ready.</p>
            <!-- Stats cards, recent activity etc. will go here -->
        </div>
    </div>
</body>
</html>