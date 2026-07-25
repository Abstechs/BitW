# BitW Evolution Log

This document tracks the architectural and mathematical evolution of the BitW Sovereign Ecosystem.

## Evolution 1.0: The Sovereign Foundation

### 1. Dynamic System Settings
**Objective:** Remove all hardcoded values and provide a central "Nervous System" for the admin to control the platform.
- **Table:** `system_settings`
- **Feature:** Admin can toggle features, adjust market math constants, and set global fees without touching code.

### 2. Stochastic Ecosystem Engine (Completed)
**Objective:** Replace basic linear price drift with a high-level mathematical model.
- **Model:** Stochastic Differential Equation (SDE) based on Mean Reversion (Ornstein-Uhlenbeck style).
- **Logic:** Prices react to P2P volume but naturally "breathe" around a central gravity point, with Gaussian noise for realism.

### 3. Sovereign Ledger & P2P (Completed)
**Objective:** Establish a high-integrity financial core.
- **Ledger:** Triple-entry accounting with SHA-256 checksums for every entry.
- **P2P:** Internal transfer system with dynamic fees.
- **Gateway Adapter:** Abstracted payment layer ready for future native "BitW Gateway" integration.

### 4. Social Oracle & Premium Tier (Completed)
**Objective:** Create a social layer for verified market intelligence.
- **Oracle:** A system for admin blogs and premium user "insights."
- **Verification:** Only verified or admin posts appear in the main feed, maintaining high quality.
- **Premium:** Subscription infrastructure to monetize advanced market participation.

## Evolution 2.0: Gamification & Scarcity

### 1. Strategic Scarcity (Completed)
**Objective:** Drive continuous reinvestment through purchasing power limits and expiration.
- **Expiration:** Mining plans now automatically "complete" once the `end_date` is reached.
- **Limits:** Added `purchase_limit` to plans to prevent whale domination and encourage diversification.

### 2. Lotto-Sovereign Engine (Completed)
**Objective:** A gamified "Lucky Number" system designed for platform sustainability.
- **Algorithm:** Picks numbers that are either unpicked (Missing) or have the lowest real-money liability.
- **Demo Mode:** Encourages participation by allowing demo users to "win" more frequently to drive real conversion.

### 3. Social Prediction Market (Completed)
**Objective:** P2P betting markets created by premium users.
- **Commission:** The platform takes a customizable percentage of the total pool.
- **Settlement:** High-integrity distribution of the pool to winning predictors.

---

## Local Testing Instructions
1. **Database:** Run `migrations/evolution_1_0.sql` AND `migrations/evolution_2_0.sql`.
2. **Seeding:** Run `core/seeders/SystemSeeder.php`.
3. **Core:** Use `LottoEngine.php` for the game logic and `PredictionMarket.php` for P2P betting.
