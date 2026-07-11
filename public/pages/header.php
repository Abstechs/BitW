<?php
require_once __DIR__ . '/../../core/config.php';

if (!isset($pageTitle)) {
    $pageTitle = AppConfig::get('APP_ALIAS') ?: 'BitW';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        :root {
            color-scheme: dark;
            --bg: #050c17;
            --surface: rgba(10, 18, 35, 0.76);
            --surface-strong: rgba(15, 23, 42, 0.88);
            --border: rgba(255, 255, 255, 0.08);
            --text: #e6efff;
            --muted: #9fb7d6;
            --accent: #60a5fa;
            --accent-soft: rgba(96, 165, 250, 0.18);
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            background: radial-gradient(circle at top, rgba(96, 165, 250, 0.14), transparent 32%),
                        linear-gradient(180deg, #02050e 0%, #050c17 100%);
            color: var(--text);
            font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
        }

        .glass-card {
            background: var(--surface);
            border: 1px solid var(--border);
            box-shadow: 0 32px 100px rgba(0, 0, 0, 0.22);
            backdrop-filter: blur(18px);
            border-radius: 28px;
        }

        .glass-card.strong {
            background: var(--surface-strong);
        }

        .section-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            margin-bottom: 1.15rem;
        }

        .section-header h2 {
            margin: 0;
            font-size: 1.25rem;
            letter-spacing: -0.03em;
        }

        .badge {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            padding: 0.45rem 0.85rem;
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.06);
            color: var(--muted);
            font-size: 0.72rem;
            text-transform: uppercase;
            letter-spacing: 0.16em;
            font-weight: 600;
        }

        .icon-box {
            width: 50px;
            height: 50px;
            border-radius: 18px;
            display: grid;
            place-items: center;
            background: rgba(255, 255, 255, 0.06);
            color: var(--accent);
            flex-shrink: 0;
        }

        .mini-card-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 1rem;
        }

        .plan-grid,
        .detail-grid {
            display: grid;
            gap: 1rem;
        }

        .action-button,
        .dashboard-link {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.65rem;
            width: 100%;
            padding: 0.95rem 1rem;
            border-radius: 18px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            background: rgba(96, 165, 250, 0.14);
            color: var(--text);
            font-weight: 700;
            text-decoration: none;
            transition: transform 0.2s ease, background-color 0.2s ease, border-color 0.2s ease;
        }

        .action-button:hover,
        .dashboard-link:hover {
            transform: translateY(-1px);
            background: rgba(96, 165, 250, 0.2);
            border-color: rgba(255, 255, 255, 0.18);
        }

        .dashboard-grid {
            display: grid;
            gap: 1.5rem;
        }

        .dashboard-main,
        .dashboard-sidebar {
            display: grid;
            gap: 1.5rem;
        }

        .dashboard-sidebar {
            min-width: 320px;
        }

        .dashboard-table {
            width: 100%;
            border-collapse: collapse;
            min-width: 100%;
        }

        .dashboard-table th,
        .dashboard-table td {
            padding: 0.95rem 1rem;
            text-align: left;
            border-bottom: 1px solid rgba(255, 255, 255, 0.07);
            font-size: 0.93rem;
            color: var(--muted);
        }

        .dashboard-table th {
            color: #cbd5e1;
            font-weight: 700;
        }

        .dashboard-table tbody tr:last-child td {
            border-bottom: none;
        }

        .dashboard-table td strong {
            color: var(--text);
        }

        .status-pill {
            display: inline-flex;
            align-items: center;
            padding: 0.3rem 0.65rem;
            border-radius: 999px;
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.12em;
            font-weight: 700;
        }

        .status-success {
            background: rgba(34, 197, 94, 0.18);
            color: #a7f3d0;
        }

        .status-warning {
            background: rgba(251, 191, 36, 0.16);
            color: #fde68a;
        }

        .status-muted {
            background: rgba(148, 163, 184, 0.16);
            color: #cbd5e1;
        }

        .alert {
            padding: 1rem 1.1rem;
            border-radius: 20px;
            border: 1px solid rgba(255, 255, 255, 0.08);
            background: rgba(255, 255, 255, 0.04);
            color: var(--text);
        }

        .alert-success {
            border-color: rgba(34, 197, 94, 0.3);
            background: rgba(34, 197, 94, 0.14);
            color: #d1fae5;
        }

        .alert-error {
            border-color: rgba(248, 113, 113, 0.3);
            background: rgba(248, 113, 113, 0.14);
            color: #fecaca;
        }

        .form-field {
            width: 100%;
            padding: 0.95rem 1rem;
            border-radius: 18px;
            border: 1px solid rgba(255, 255, 255, 0.12);
            background: rgba(255, 255, 255, 0.04);
            color: var(--text);
            font-size: 0.95rem;
        }

        .form-field::placeholder {
            color: var(--muted);
        }

        .form-row {
            display: grid;
            gap: 0.9rem;
        }

        @media (min-width: 768px) {
            .dashboard-grid {
                grid-template-columns: 1.6fr 0.9fr;
            }

            .plan-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (min-width: 1024px) {
            .dashboard-grid {
                gap: 2rem;
            }
        }
    </style>
</head>
<body class="min-h-screen p-8">
