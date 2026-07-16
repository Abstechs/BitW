<?php
// public/index.php
require_once "../core/config.php";
require_once "../core/database.php";
require_once "../core/helpers.php";
require_once "../core/session.php";

$appConfig = AppConfig::get('HOME') ?: [];
$APP_NAME = htmlspecialchars(AppConfig::get('APP_NAME') ?? 'BitWealthBuilder', ENT_QUOTES, 'UTF-8');
$APP_ALIAS = htmlspecialchars(AppConfig::get('APP_ALIAS') ?? 'BitW', ENT_QUOTES, 'UTF-8');
$heroTitle = htmlspecialchars($appConfig['HERO_TITLE'] ?? 'Enter a futuristic wealth system built for engagement, yield, and momentum.', ENT_QUOTES, 'UTF-8');
$heroDescription = htmlspecialchars($appConfig['HERO_DESCRIPTION'] ?? 'BitW blends daily mining, plan-based returns, and secure wallet infrastructure into one modular ecosystem. Join a platform designed for persistence, gamified rewards, and real financial control.', ENT_QUOTES, 'UTF-8');
$tagline = htmlspecialchars($appConfig['TAGLINE'] ?? "Tomorrow's mining economy, today.", ENT_QUOTES, 'UTF-8');
$featuresSubtitle = htmlspecialchars($appConfig['FEATURES_SUBTITLE'] ?? 'BitW follows the BitW-Map vision: a modular, secure, and gamified system for daily engagement and wealth creation.', ENT_QUOTES, 'UTF-8');
$howItWorksTitle = htmlspecialchars($appConfig['HOW_IT_WORKS_TITLE'] ?? 'How BitW works', ENT_QUOTES, 'UTF-8');
$howItWorksSubtitle = htmlspecialchars($appConfig['HOW_IT_WORKS_SUBTITLE'] ?? 'Follow three simple steps to start mining, growing, and earning with a future-forward economy.', ENT_QUOTES, 'UTF-8');
$plansTitle = htmlspecialchars($appConfig['PLANS_TITLE'] ?? 'Sample stone plans', ENT_QUOTES, 'UTF-8');
$plansSubtitle = htmlspecialchars($appConfig['PLANS_SUBTITLE'] ?? 'Explore premium stones designed for different goals: quick returns, compounding growth, and long-term power.', ENT_QUOTES, 'UTF-8');
$ctaTitle = htmlspecialchars($appConfig['CTA_TITLE'] ?? 'Launch your BitW journey with a single daily login.', ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $APP_NAME ?> — <?= $APP_ALIAS ?></title>
    <meta name="description" content="<?= $APP_NAME ?> (<?= $APP_ALIAS ?>) — A daily login mining economy and wallet-driven digital wealth system.">
    
    <!-- Design Utilities Bundle -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:ital,wght@0,300..800;1,300..800&display=swap" rel="stylesheet">
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">

    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Plus Jakarta Sans', 'sans-serif'],
                    },
                    colors: {
                        brandBg: '#050712',
                        brandSurface: '#091220',
                        brandSurfaceAlt: '#0f1a2d',
                        brandAccent: '#0df4ff',
                        brandAccentDim: 'rgba(13, 244, 255, 0.12)',
                        brandPurple: '#7e4dff',
                    }
                }
            }
        }
    </script>
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        .glass { background: rgba(9, 18, 32, 0.7); backdrop-filter: blur(16px); border: 1px solid rgba(255, 255, 255, 0.06); }
        .glow-mesh { background: radial-gradient(circle at top left, rgba(13, 244, 255, 0.08), transparent 25%), radial-gradient(circle at bottom right, rgba(126, 77, 255, 0.08), transparent 25%); }
    </style>
</head>
<body class="bg-brandBg text-[#e4edf8] min-h-screen relative glow-mesh antialiased selection:bg-brandAccent selection:text-black">

    <!-- NAVIGATION HEADER BAR -->
    <header class="sticky top-0 z-50 bg-brandBg/60 backdrop-blur-md border-b border-white/5 transition-all">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-20 flex items-center justify-between gap-4">
            <a href="#" class="flex items-center gap-3.5 group">
                <div class="w-11 h-11 rounded-xl bg-gradient-to-br from-brandAccent to-brandPurple flex items-center justify-center font-extrabold text-[#020c14] text-lg tracking-wider transition-transform group-hover:scale-[1.03] duration-300 shadow-lg shadow-brandAccent/10">
                    <?= substr($APP_ALIAS, 0, 1) ?>
                </div>
                <div class="grid leading-tight">
                    <span class="text-base font-bold text-white tracking-tight"><?= $APP_NAME ?></span>
                    <span class="text-xs text-[#97a6c1]/80 font-medium"><?= $APP_ALIAS ?> • <?= $tagline ?></span>
                </div>
            </a>
            
            <nav class="hidden md:flex items-center gap-1.5">
                <a href="#features" class="text-sm font-medium text-[#97a6c1] hover:text-white px-3.5 py-2 rounded-xl hover:bg-white/5 transition-all">Features</a>
                <a href="#how-it-works" class="text-sm font-medium text-[#97a6c1] hover:text-white px-3.5 py-2 rounded-xl hover:bg-white/5 transition-all">How it Works</a>
                <a href="#plans" class="text-sm font-medium text-[#97a6c1] hover:text-white px-3.5 py-2 rounded-xl hover:bg-white/5 transition-all">Plans</a>
            </nav>

            <div class="flex items-center gap-3">
                <a href="login.php" class="text-sm font-semibold text-white/90 hover:text-white px-4 py-2.5 rounded-xl hover:bg-white/5 transition-all">Login</a>
                <a href="register.php" class="px-5 py-2.5 bg-gradient-to-r from-brandAccent to-brandPurple hover:from-brandAccent hover:to-brandAccent text-[#020d15] text-sm font-bold rounded-xl transition-all shadow-md shadow-brandAccent/10 hover:shadow-brandAccent/20 hover:-translate-y-0.5">
                    Get Started
                </a>
            </div>
        </div>
    </header>

    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12 lg:py-20 space-y-24">
        
        <!-- HERO SEGMENT HERO CROSSGRID -->
        <section class="grid lg:grid-cols-12 gap-12 lg:gap-8 items-center">
            <div class="lg:col-span-7 space-y-6 text-center lg:text-left">
                <div class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full bg-brandAccentDim border border-brandAccent/20 text-brandAccent text-xs font-bold uppercase tracking-wider">
                    <i class="bx bx-bolt text-sm animate-pulse"></i> Daily login yields. Stone-powered economy.
                </div>
                <h2 class="text-4xl sm:text-5xl lg:text-6xl font-extrabold text-white tracking-tight leading-[1.1] text-balance">
                    <?= $heroTitle ?>
                </h2>
                <p class="text-base sm:text-lg text-[#97a6c1] leading-relaxed max-w-2xl mx-auto lg:mx-0">
                    <?= $heroDescription ?>
                </p>
                <div class="flex flex-wrap items-center justify-center lg:justify-start gap-4 pt-2">
                    <a href="register.php" class="px-7 py-4 bg-gradient-to-r from-brandAccent to-brandPurple text-[#020d15] text-base font-bold rounded-xl transition-all shadow-xl shadow-brandAccent/10 hover:shadow-brandAccent/20 hover:-translate-y-0.5">
                        Join <?= $APP_ALIAS ?> Network
                    </a>
                    <a href="#features" class="px-7 py-4 border border-white/10 hover:border-white/20 text-white text-base font-semibold rounded-xl bg-white/[0.02] hover:bg-white/[0.05] transition-all">
                        Explore Features
                    </a>
                </div>
            </div>

            <!-- ASIDE INTERACTIVE HERO CAPTURE PREVIEW -->
            <div class="lg:col-span-5 relative group">
                <div class="absolute -inset-1 rounded-3xl bg-gradient-to-r from-brandAccent/30 to-brandPurple/30 blur-xl opacity-30 group-hover:opacity-40 transition duration-1000"></div>
                <aside class="relative glass rounded-3xl p-6 sm:p-8 shadow-2xl space-y-6">
                    <div class="flex items-center justify-between border-b border-white/5 pb-4">
                        <span class="text-xs font-bold uppercase tracking-widest text-brandAccent flex items-center gap-1.5">
                            <span class="w-1.5 h-1.5 bg-brandAccent rounded-full animate-ping"></span> Featured Launch Matrix
                        </span>
                        <i class="bx bx-intersect text-xl text-brandPurple"></i>
                    </div>
                    <div class="space-y-3">
                        <h3 class="text-xl sm:text-2xl font-bold text-white tracking-tight">Stone mining meets smart finance</h3>
                        <p class="text-sm text-[#97a6c1] leading-relaxed">
                            Mine daily, collect modular rewards, and grow balances with programmatic investment stones. Every unique login cycle claims active parameters.
                        </p>
                    </div>
                    <div class="grid grid-cols-2 gap-4 pt-2">
                        <div class="p-4 border border-white/5 rounded-2xl bg-white/[0.01] hover:bg-white/[0.03] transition-colors">
                            <strong class="block text-lg font-bold text-white tracking-tight">Daily Yield</strong>
                            <span class="text-xs text-[#97a6c1]">Earn from active plans</span>
                        </div>
                        <div class="p-4 border border-white/5 rounded-2xl bg-white/[0.01] hover:bg-white/[0.03] transition-colors">
                            <strong class="block text-lg font-bold text-white tracking-tight">Total Control</strong>
                            <span class="text-xs text-[#97a6c1]">Auditable wallet hub</span>
                        </div>
                    </div>
                </aside>
            </div>
        </section>

        <!-- CORE FEATURES SECTION GRID -->
        <section id="features" class="space-y-10">
            <div class="max-w-3xl space-y-3 text-center md:text-left">
                <h2 class="text-2xl sm:text-3xl font-bold text-white tracking-tight">Designed for the modern digital economy</h2>
                <p class="text-sm sm:text-base text-[#97a6c1] leading-relaxed"><?= $featuresSubtitle ?></p>
            </div>
            <div class="grid md:grid-cols-3 gap-6">
                <div class="glass p-6 sm:p-8 rounded-2xl border border-white/5 hover:border-brandAccent/20 transition-all duration-300 bg-gradient-to-b from-white/[0.02] to-transparent group">
                    <div class="w-10 h-10 rounded-xl bg-brandAccentDim text-brandAccent flex items-center justify-center mb-5 group-hover:bg-brandAccent group-hover:text-black transition-all">
                        <i class="bx bx-cube-alt text-xl"></i>
                    </div>
                    <h3 class="text-lg font-bold text-white mb-2 tracking-tight">Daily Mining Engine</h3>
                    <p class="text-sm text-[#97a6c1] leading-relaxed">Log in each day to activate mining yield. Miss a day and only that day’s reward is lost—your base plan remains active.</p>
                </div>
                <div class="glass p-6 sm:p-8 rounded-2xl border border-white/5 hover:border-brandPurple/20 transition-all duration-300 bg-gradient-to-b from-white/[0.02] to-transparent group">
                    <div class="w-10 h-10 rounded-xl bg-brandPurple/10 text-brandPurple flex items-center justify-center mb-5 group-hover:bg-brandPurple group-hover:text-white transition-all">
                        <i class="bx bx-diamond text-xl"></i>
                    </div>
                    <h3 class="text-lg font-bold text-white mb-2 tracking-tight">Stone Investment Plans</h3>
                    <p class="text-sm text-[#97a6c1] leading-relaxed">Choose from mythic stone configurations like Astral Shard and Titan Ember, each with unique yield models and durations.</p>
                </div>
                <div class="glass p-6 sm:p-8 rounded-2xl border border-white/5 hover:border-brandAccent/20 transition-all duration-300 bg-gradient-to-b from-white/[0.02] to-transparent group">
                    <div class="w-10 h-10 rounded-xl bg-brandAccentDim text-brandAccent flex items-center justify-center mb-5 group-hover:bg-brandAccent group-hover:text-black transition-all">
                        <i class="bx bx-wallet text-xl"></i>
                    </div>
                    <h3 class="text-lg font-bold text-white mb-2 tracking-tight">Secure Wallet Hub</h3>
                    <p class="text-sm text-[#97a6c1] leading-relaxed">Manage asset distributions, deposits, and verification parameters inside one clean dashboard pipeline.</p>
                </div>
            </div>
        </section>

        <!-- PIPELINE WORKFLOW PIPELINE HOW IT WORKS -->
        <section id="how-it-works" class="space-y-10">
            <div class="max-w-3xl space-y-3 text-center md:text-left">
                <h2 class="text-2xl sm:text-3xl font-bold text-white tracking-tight"><?= $howItWorksTitle ?></h2>
                <p class="text-sm sm:text-base text-[#97a6c1] leading-relaxed"><?= $howItWorksSubtitle ?></p>
            </div>
            <div class="grid md:grid-cols-3 gap-6 relative">
                <div class="p-6 rounded-2xl border border-white/5 bg-white/[0.01] relative space-y-3">
                    <span class="text-xs font-mono font-bold text-brandAccent uppercase tracking-widest bg-brandAccentDim px-2.5 py-1 rounded">Step 01</span>
                    <h3 class="text-base font-bold text-white tracking-tight">Establish Account Matrix</h3>
                    <p class="text-sm text-[#97a6c1] leading-relaxed">Create your account architecture and activate your first stone tier config in minutes with direct onboarding parameters.</p>
                </div>
                <div class="p-6 rounded-2xl border border-white/5 bg-white/[0.01] relative space-y-3">
                    <span class="text-xs font-mono font-bold text-brandPurple uppercase tracking-widest bg-brandPurple/10 px-2.5 py-1 rounded">Step 02</span>
                    <h3 class="text-base font-bold text-white tracking-tight">Activate Daily Cycles</h3>
                    <p class="text-sm text-[#97a6c1] leading-relaxed">Return each day to claim resource mining returns and preserve parameters. Consistent engagement triggers steady operations.</p>
                </div>
                <div class="p-6 rounded-2xl border border-white/5 bg-white/[0.01] relative space-y-3">
                    <span class="text-xs font-mono font-bold text-brandAccent uppercase tracking-widest bg-brandAccentDim px-2.5 py-1 rounded">Step 03</span>
                    <h3 class="text-base font-bold text-white tracking-tight">Scale Resource Portfolios</h3>
                    <p class="text-sm text-[#97a6c1] leading-relaxed">Scale with compounding dynamic options, audit cash adjustments, and step up performance rankings safely over time.</p>
                </div>
            </div>
        </section>

        <!-- STONE PLANS MATRIX TARGET SELECTION -->
        <section id="plans" class="space-y-10">
            <div class="max-w-3xl space-y-3 text-center md:text-left">
                <h2 class="text-2xl sm:text-3xl font-bold text-white tracking-tight"><?= $plansTitle ?></h2>
                <p class="text-sm sm:text-base text-[#97a6c1] leading-relaxed"><?= $plansSubtitle ?></p>
            </div>
            
            <div class="grid md:grid-cols-3 gap-6">
                <!-- Obsidian Stone -->
                <div class="glass p-6 sm:p-8 rounded-2xl border border-white/5 flex flex-col justify-between hover:scale-[1.01] transition-transform bg-gradient-to-b from-white/[0.02] to-transparent">
                    <div class="space-y-4">
                        <div class="flex items-center justify-between">
                            <h3 class="text-lg font-bold text-white tracking-tight">Obsidian Stone</h3>
                            <span class="text-[10px] font-bold tracking-wider uppercase text-[#97a6c1] bg-white/5 px-2 py-0.5 rounded">Tier 1</span>
                        </div>
                        <div class="text-3xl font-sans font-extrabold text-white tracking-tight">Template: ₦12,500</div>
                        <p class="text-xs text-[#97a6c1] leading-relaxed">Starter plan optimized with solid daily multipliers and fast completion boundaries for early-stage miners.</p>
                        <div class="flex items-center justify-between text-xs font-medium text-[#97a6c1] border-t border-white/5 pt-4">
                            <span class="flex items-center gap-1"><i class="bx bx-trending-up text-brandAccent"></i> Yield 9%</span>
                            <span class="flex items-center gap-1"><i class="bx bx-time text-brandPurple"></i> 12 Days</span>
                        </div>
                    </div>
                    <div class="pt-6">
                        <a href="register.php" class="block w-full text-center py-2.5 border border-white/10 hover:border-brandAccent/40 bg-white/[0.02] hover:bg-brandAccent hover:text-black font-bold text-xs text-white rounded-xl transition-all">Activate Configuration</a>
                    </div>
                </div>

                <!-- Astral Shard -->
                <div class="glass p-6 sm:p-8 rounded-2xl border border-brandPurple/30 flex flex-col justify-between hover:scale-[1.01] transition-transform relative bg-gradient-to-b from-brandPurple/[0.03] to-transparent shadow-lg shadow-brandPurple/5">
                    <div class="absolute top-0 right-6 transform -translate-y-1/2 bg-brandPurple text-white text-[9px] font-black uppercase tracking-widest px-2.5 py-0.5 rounded-full shadow-md">Popular</div>
                    <div class="space-y-4">
                        <div class="flex items-center justify-between">
                            <h3 class="text-lg font-bold text-white tracking-tight">Astral Shard</h3>
                            <span class="text-[10px] font-bold tracking-wider uppercase text-brandPurple bg-brandPurple/10 px-2 py-0.5 rounded">Tier 2</span>
                        </div>
                        <div class="text-3xl font-sans font-extrabold text-white tracking-tight">Template: ₦25,000</div>
                        <p class="text-xs text-[#97a6c1] leading-relaxed">Balanced strategy mapping layout for steady system acceleration. Built for miners targeting predictable compounding values.</p>
                        <div class="flex items-center justify-between text-xs font-medium text-[#97a6c1] border-t border-white/5 pt-4">
                            <span class="flex items-center gap-1"><i class="bx bx-trending-up text-brandAccent"></i> Yield 14%</span>
                            <span class="flex items-center gap-1"><i class="bx bx-time text-brandPurple"></i> 18 Days</span>
                        </div>
                    </div>
                    <div class="pt-6">
                        <a href="register.php" class="block w-full text-center py-2.5 bg-gradient-to-r from-brandAccent to-brandPurple text-[#020d15] font-bold text-xs rounded-xl hover:opacity-90 transition-all shadow-md">Activate Configuration</a>
                    </div>
                </div>

                <!-- Titan Ember -->
                <div class="glass p-6 sm:p-8 rounded-2xl border border-white/5 flex flex-col justify-between hover:scale-[1.01] transition-transform bg-gradient-to-b from-white/[0.02] to-transparent">
                    <div class="space-y-4">
                        <div class="flex items-center justify-between">
                            <h3 class="text-lg font-bold text-white tracking-tight">Titan Ember</h3>
                            <span class="text-[10px] font-bold tracking-wider uppercase text-[#97a6c1] bg-white/5 px-2 py-0.5 rounded">Tier 3</span>
                        </div>
                        <div class="text-3xl font-sans font-extrabold text-white tracking-tight">Template: ₦45,000</div>
                        <p class="text-xs text-[#97a6c1] leading-relaxed">High-tier stone allocations configured for advanced miners seeking premium yields and higher long-term payouts.</p>
                        <div class="flex items-center justify-between text-xs font-medium text-[#97a6c1] border-t border-white/5 pt-4">
                            <span class="flex items-center gap-1"><i class="bx bx-trending-up text-brandAccent"></i> Yield 18%</span>
                            <span class="flex items-center gap-1"><i class="bx bx-time text-brandPurple"></i> 24 Days</span>
                        </div>
                    </div>
                    <div class="pt-6">
                        <a href="register.php" class="block w-full text-center py-2.5 border border-white/10 hover:border-brandAccent/40 bg-white/[0.02] hover:bg-brandAccent hover:text-black font-bold text-xs text-white rounded-xl transition-all">Activate Configuration</a>
                    </div>
                </div>
            </div>
        </section>

        <!-- PERSISTENT FOOTER ROW INVITATION CTA CARD -->
        <section class="relative rounded-3xl overflow-hidden border border-white/5 bg-gradient-to-r from-brandSurface to-brandSurfaceAlt p-8 sm:p-12 text-center md:text-left shadow-2xl">
            <div class="absolute inset-0 bg-radial-gradient from-brandAccent/5 to-transparent pointer-events-none"></div>
            <div class="relative flex flex-col md:flex-row md:items-center justify-between gap-8">
                <div class="space-y-3">
                    <span class="text-xs font-bold text-brandAccent uppercase tracking-wider">Ready to power up?</span>
                    <h2 class="text-2xl sm:text-3xl font-bold text-white tracking-tight max-w-xl text-balance"><?= $ctaTitle ?></h2>
                </div>
                <a href="register.php" class="whitespace-nowrap self-center md:self-auto px-7 py-4 bg-gradient-to-r from-brandAccent to-brandPurple text-[#020d15] text-base font-bold rounded-xl transition-all shadow-lg hover:shadow-brandAccent/20 hover:-translate-y-0.5">
                    Start Mining Today
                </a>
            </div>
        </section>
        
    </main>

    <!-- METRIC BASE FOOTER MATRIX -->
    <footer class="border-t border-white/5 bg-brandBg mt-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 flex flex-col sm:flex-row justify-between items-center gap-6 text-xs text-[#97a6c1]">
            <div class="max-w-xl text-center sm:text-left leading-relaxed">
                <strong class="text-white font-semibold"><?= $APP_NAME ?></strong> — A modular fintech infrastructure framework operating system elements across session yields, asset configuration stone layers, rank tiers, and ledger tools.
            </div>
            <div class="whitespace-nowrap font-mono text-slate-500">
                © <?= date('Y') ?> <?= $APP_NAME ?>. All rights reserved.
            </div>
        </div>
    </footer>

</body>
</html>