<?php

require_once "../core/database.php";
require_once "../core/helpers.php";
require_once "../core/session.php";

$config = require "../config/app.php";

$APP_NAME = htmlspecialchars($config['APP_NAME'] ?? 'BitWealthBuilder', ENT_QUOTES, 'UTF-8');
$APP_ALIAS = htmlspecialchars($config['APP_ALIAS'] ?? 'BitW', ENT_QUOTES, 'UTF-8');

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title><?= $APP_NAME ?> — <?= $APP_ALIAS ?></title>
    <meta name="description" content="<?= $APP_NAME ?> (<?= $APP_ALIAS ?>) — A daily login mining economy and wallet-driven digital wealth system.">
    <link rel="stylesheet" href="assets/css/bitw.css">
    <style>
    :root{--bg:#050712;--surface:#091220;--surface-alt:#0f1a2d;--muted:#97a6c1;--text:#e4edf8;--accent:#0df4ff;--accent2:#7e4dff;--border:rgba(255,255,255,0.08)}
    *{box-sizing:border-box}
    body{margin:0;background:#020409;color:var(--text);font-family:Inter,ui-sans-serif,system-ui,Arial,sans-serif;-webkit-font-smoothing:antialiased}
    body::before{content:'';position:fixed;inset:0;background:radial-gradient(circle at top left,rgba(13,244,255,0.12),transparent 20%),radial-gradient(circle at bottom right,rgba(126,77,255,0.12),transparent 18%);pointer-events:none}
    .container{max-width:1200px;margin:0 auto;padding:32px}
    .header{display:flex;justify-content:space-between;align-items:center;gap:24px;padding:10px 0}
    .brand{display:flex;align-items:center;gap:14px}
    .logo{width:52px;height:52px;border-radius:16px;background:linear-gradient(135deg,var(--accent),var(--accent2));display:flex;align-items:center;justify-content:center;font-weight:800;color:#020c14;letter-spacing:0.04em;box-shadow:0 24px 80px rgba(13,244,255,0.12)}
    .brand-text{display:grid;line-height:1}
    .brand-text h1{margin:0;font-size:20px}
    .brand-text p{margin:2px 0 0;color:var(--muted);font-size:13px}
    .nav{display:flex;flex-wrap:wrap;gap:14px;align-items:center}
    .nav a{color:var(--muted);text-decoration:none;font-size:14px;padding:10px 14px;border-radius:12px;transition:all .18s}
    .nav a:hover{color:#fff;background:rgba(255,255,255,0.06)}
    .hero{display:grid;grid-template-columns:1.1fr 0.9fr;gap:36px;align-items:center;padding:60px 0}
    .hero-left{max-width:620px}
    .kicker{display:inline-flex;padding:10px 16px;border-radius:999px;background:rgba(13,244,255,0.12);color:var(--accent);font-weight:700;letter-spacing:0.06em;text-transform:uppercase;font-size:12px}
    .hero-left h2{font-size:48px;line-height:1.05;margin:22px 0 18px;color:#ffffff}
    .hero-left p{font-size:17px;line-height:1.8;color:var(--muted);max-width:640px;margin-bottom:30px}
    .cta{display:flex;flex-wrap:wrap;gap:14px}
    .btn{display:inline-flex;align-items:center;justify-content:center;gap:10px;padding:15px 22px;border-radius:14px;font-weight:700;text-decoration:none;transition:transform .18s,box-shadow .18s}
    .btn:hover{transform:translateY(-1px)}
    .btn-primary{background:linear-gradient(90deg,var(--accent),var(--accent2));color:#020d15;box-shadow:0 20px 60px rgba(13,244,255,0.18)}
    .btn-secondary{border:1px solid rgba(255,255,255,0.12);color:var(--text);background:rgba(255,255,255,0.03)}
    .hero-right{position:relative;padding:32px;border-radius:28px;background:rgba(15,26,45,0.95);border:1px solid var(--border);backdrop-filter:blur(16px);box-shadow:0 40px 120px rgba(0,0,0,0.35)}
    .hero-card{display:grid;gap:20px}
    .hero-card span{font-size:13px;text-transform:uppercase;letter-spacing:0.14em;color:var(--accent)}
    .hero-card h3{margin:0;font-size:28px;line-height:1.15}
    .hero-card p{margin:0;color:var(--muted);line-height:1.75}
    .hero-meta{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:16px;margin-top:22px}
    .hero-meta div{padding:18px;border:1px solid rgba(255,255,255,0.08);border-radius:20px;background:rgba(255,255,255,0.03)}
    .hero-meta strong{display:block;font-size:18px;color:#fff;margin-bottom:6px}
    .section-title{display:flex;justify-content:space-between;align-items:center;margin:72px 0 24px;gap:16px}
    .section-title h2{margin:0;font-size:32px}
    .section-title p{margin:0;color:var(--muted);max-width:640px}
    .grid-3{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:20px}
    .feature-card{padding:28px;border-radius:24px;background:linear-gradient(180deg,rgba(255,255,255,0.03),rgba(255,255,255,0.01));border:1px solid rgba(255,255,255,0.06)}
    .feature-card h3{margin:0 0 12px;font-size:20px}
    .feature-card p{margin:0;color:var(--muted);line-height:1.75}
    .plan-card{padding:28px;border-radius:24px;background:rgba(255,255,255,0.03);border:1px solid rgba(255,255,255,0.08)}
    .plan-card h3{margin:0 0 10px;font-size:22px}
    .plan-value{font-size:34px;font-weight:800;color:#fff;margin:12px 0}
    .plan-meta{display:flex;justify-content:space-between;color:var(--muted);font-size:14px;margin-top:18px}
    .plan-cta{margin-top:24px}
    .plan-cta .btn{width:100%}
    .footer{padding:38px 0 18px;border-top:1px solid rgba(255,255,255,0.06);color:var(--muted);font-size:14px;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px}
    .footer strong{color:#fff}
    .footer-note{max-width:620px}
    @media(max-width:1040px){.hero{grid-template-columns:1fr}.grid-3{grid-template-columns:1fr 1fr}.hero-right{padding:24px}}
    @media(max-width:760px){.container{padding:24px}.nav{justify-content:center}.section-title{flex-direction:column;align-items:flex-start}.grid-3{grid-template-columns:1fr}.hero-left h2{font-size:36px}.hero-right{padding:24px}}
    </style>
</head>
<body>
<div class="container">
<header class="header">
<div class="brand">
    <div class="logo">B</div>
    <div class="brand-text">
        <h1><?= $APP_NAME ?></h1>
        <p>BitW — Tomorrow's mining economy, today.</p>
    </div>
</div>
<nav class="nav">
    <a href="#features">Features</a>
    <a href="#plans">Plans</a>
    <a href="#how-it-works">How it Works</a>
    <a href="login.php">Login</a>
    <a class="btn btn-primary" href="register.php">Get Started</a>
</nav>
</header>

<main class="hero">
<div class="hero-background"></div>
<div class="hero-left">
    <div class="kicker">Daily login yields. Stone-powered economy.</div>
    <h2>Enter a futuristic wealth system built for engagement, yield, and momentum.</h2>
    <p class="lead"><?= $APP_NAME ?> blends daily mining, plan-based returns, and secure wallet infrastructure into one modular ecosystem. Join a platform designed for persistence, gamified rewards, and real financial control.</p>
    <div class="cta">
        <a class="btn btn-primary" href="register.php">Join BitW</a>
        <a class="btn btn-secondary" href="#features">Explore Features</a>
    </div>
</div>

<aside class="hero-right">
    <div class="hero-card">
        <span>Featured at launch</span>
        <h3>Stone mining meets smart finance</h3>
        <p>Mine daily, collect rewards, and grow your balance with modular investment stones. Every login unlocks yield potential.</p>
        <div class="hero-meta">
            <div>
                <strong>Daily Yield</strong>
                <span>Earn from every active plan</span>
            </div>
            <div>
                <strong>Wallet Control</strong>
                <span>Deposit, withdraw, and audit with confidence</span>
            </div>
        </div>
    </div>
</aside>
</main>

<section id="features" class="section-title">
    <div>
        <h2>Designed for the modern digital economy</h2>
        <p>BitW follows the BitW-Map vision: a modular, secure, and gamified system for daily engagement and wealth creation.</p>
    </div>
</section>

<div class="grid-3">
    <div class="feature-card">
        <h3>Daily Mining Engine</h3>
        <p>Log in each day to activate mining yield. Miss a day and only that day’s reward is lost—your plan remains active.</p>
    </div>
    <div class="feature-card">
        <h3>Stone Investment Plans</h3>
        <p>Choose from mythic stones like Astral Shard and Titan Ember, each with unique yield, duration, and engagement rewards.</p>
    </div>
    <div class="feature-card">
        <h3>Secure Wallet Hub</h3>
        <p>Manage deposits, withdrawals, and transactions in one dashboard built for clarity and auditability.</p>
    </div>
</div>

<section id="how-it-works" class="section-title">
    <div>
        <h2>How BitW works</h2>
        <p>Follow three simple steps to start mining, growing, and earning with a future-forward economy.</p>
    </div>
</section>

<div class="grid-3" style="margin-bottom:40px">
    <div class="feature-card">
        <h3>Step 1</h3>
        <p>Create your account and activate your first plan in minutes—no complex onboarding, just a fast start.</p>
    </div>
    <div class="feature-card">
        <h3>Step 2</h3>
        <p>Return each day to claim mining rewards and keep your streak alive. Daily engagement grows your stone yield.</p>
    </div>
    <div class="feature-card">
        <h3>Step 3</h3>
        <p>Scale with new stone plans, watch your wallet balance rise, and move up the rank tiers as you stay active.</p>
    </div>
</div>

<section id="plans" class="section-title">
    <div>
        <h2>Sample stone plans</h2>
        <p>Explore premium stones designed for different goals: quick returns, compounding growth, and long-term power.</p>
    </div>
</section>

<div class="grid-3">
    <div class="plan-card">
        <h3>Obsidian Stone</h3>
        <div class="plan-value">₦12,500</div>
        <p>Starter plan with solid daily yield and fast completion for early-stage miners.</p>
        <div class="plan-meta"><span>Yield 9%</span><span>Duration 12 days</span></div>
        <div class="plan-cta"><a class="btn btn-primary" href="register.php">Activate</a></div>
    </div>
    <div class="plan-card">
        <h3>Astral Shard</h3>
        <div class="plan-value">₦25,000</div>
        <p>Balanced plan for steady growth and daily yield. Great for users who want consistency.</p>
        <div class="plan-meta"><span>Yield 14%</span><span>Duration 18 days</span></div>
        <div class="plan-cta"><a class="btn btn-primary" href="register.php">Activate</a></div>
    </div>
    <div class="plan-card">
        <h3>Titan Ember</h3>
        <div class="plan-value">₦45,000</div>
        <p>High-tier stone for experienced users seeking premium yield and long-term rewards.</p>
        <div class="plan-meta"><span>Yield 18%</span><span>Duration 24 days</span></div>
        <div class="plan-cta"><a class="btn btn-primary" href="register.php">Activate</a></div>
    </div>
</div>

<section class="bottom-cta">
    <div class="bottom-cta-content">
        <div>
            <span>Ready to power up?</span>
            <h2>Launch your BitW journey with a single daily login.</h2>
        </div>
        <a class="btn btn-primary btn-cta" href="register.php">Start Mining Today</a>
    </div>
</section>

<footer class="footer">
    <div class="footer-note"><strong><?= $APP_NAME ?></strong> — a modular fintech experience built around daily mining, stone plans, rank progression, and secure wallet control.</div>
    <div>© <?= date('Y') ?> <?= $APP_NAME ?>. All rights reserved.</div>
</footer>
</div>
</body>
</html>