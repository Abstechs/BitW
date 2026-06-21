<?php
if (!isset($pageTitle)) {
    $pageTitle = 'BitW';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { background: linear-gradient(to bottom, #0a0f1c, #02040a); color: #e0f0ff; font-family: system-ui; }
        .card { background: rgba(17, 24, 39, 0.95); border: 1px solid #374151; }
    </style>
</head>
<body class="min-h-screen p-8">
