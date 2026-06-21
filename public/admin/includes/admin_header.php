<?php
// admin/includes/admin_header.php
$appAlias = AppConfig::get('APP_ALIAS');
$appName = AppConfig::get('APP_NAME');
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($appAlias) ?> Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet" />
    <style>
        body { background: #050816; color: #e5e7eb; min-height: 100vh; }
        .admin-shell { display:grid; grid-template-columns: 280px 1fr; min-height:100vh; }
        .admin-sidebar { background: rgba(10, 16, 35, 0.96); border-right:1px solid rgba(255,255,255,0.08); padding: 2rem; }
        .admin-sidebar h1 { font-size: 1.65rem; margin-bottom: 1.75rem; letter-spacing: -.03em; }
        .admin-link { display:block; margin-bottom: .75rem; padding:.85rem 1rem; border-radius: 16px; color:#e5e7eb; text-decoration:none; transition:.2s ease; }
        .admin-link:hover, .admin-link.active { background: rgba(96, 165, 250, 0.15); color:#e0f2fe; }
        .admin-main { padding:2rem; }
        .admin-top { display:flex; justify-content:space-between; align-items:center; gap:1rem; margin-bottom:2rem; }
        .admin-card { background: rgba(15, 23, 42, 0.92); border:1px solid rgba(255,255,255,0.08); border-radius:24px; padding:1.5rem; box-shadow:0 24px 64px rgba(0,0,0,0.22); }
        .form-field { width:100%; padding:1rem 1.1rem; border-radius:18px; border:1px solid rgba(255,255,255,0.08); background: rgba(255,255,255,0.03); color:#f8fafc; }
        .form-label { display:block; margin-bottom:.55rem; color:#cbd5e1; font-size:.95rem; }
        .btn-primary { display:inline-flex; align-items:center; gap:.65rem; justify-content:center; padding:1rem 1.2rem; border-radius:18px; border:none; background: linear-gradient(135deg,#60a5fa,#7c3aed); color:#fff; cursor:pointer; }
        .btn-secondary { display:inline-flex; align-items:center; gap:.5rem; justify-content:center; padding:.95rem 1.1rem; border-radius:18px; border:1px solid rgba(255,255,255,0.12); background: transparent; color:#cbd5e1; cursor:pointer; }
        .table { width:100%; border-collapse:collapse; margin-top:1rem; }
        .table th, .table td { padding:1rem 1rem; text-align:left; border-bottom:1px solid rgba(255,255,255,0.08); }
        .table th { color:#cbd5e1; font-weight:700; }
        .table tbody tr:hover { background: rgba(255,255,255,0.03); }
        .badge { display:inline-flex; align-items:center; gap:.4rem; padding:.45rem .75rem; border-radius:999px; background: rgba(96, 165, 250, 0.14); color: #dbeafe; font-size:.8rem; text-transform:uppercase; letter-spacing:.12em; }
        .image-preview { width:72px; height:72px; object-fit:cover; border-radius:18px; border:1px solid rgba(255,255,255,0.12); }
    </style>
</head>
<body>
<div class="admin-shell">
    <aside class="admin-sidebar">
        <h1><?= htmlspecialchars($appAlias) ?> Admin</h1>
        <nav>
            <a class="admin-link<?= $currentPage === 'index.php' ? ' active' : '' ?>" href="index.php">Dashboard</a>
            <a class="admin-link<?= $currentPage === 'plans.php' ? ' active' : '' ?>" href="plans.php">Stone Plans</a>
            <a class="admin-link<?= $currentPage === 'deposits.php' ? ' active' : '' ?>" href="deposits.php">Deposits</a>
            <a class="admin-link<?= $currentPage === 'transactions.php' ? ' active' : '' ?>" href="transactions.php">Transactions</a>
            <a class="admin-link<?= $currentPage === 'users.php' ? ' active' : '' ?>" href="users.php">Users</a>
            <a class="admin-link<?= $currentPage === 'settings.php' ? ' active' : '' ?>" href="settings.php">Settings</a>
            <a class="admin-link" href="logout.php">Logout</a>
        </nav>
    </aside>
    <main class="admin-main">
