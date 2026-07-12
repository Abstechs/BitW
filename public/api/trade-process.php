<?php
// public/api/trade-process.php
header('Content-Type: application/json');
session_start();

require_once __DIR__ . '/../../core/config.php';
require_once __DIR__ . '/../../core/database.php';
require_once __DIR__ . '/../../core/auth.php';
require_once __DIR__ . '/../../core/EcosystemEngine.php';

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized session access.']);
    exit;
}

$userId = $_SESSION['user_id'];
$data = json_decode(file_get_contents('php://input'), true);

$assetId = intval($data['asset_id'] ?? 0);
$type = $data['type'] ?? ''; // 'buy' or 'sell'
$amount = floatval($data['amount'] ?? 0);

if ($amount <= 0 || !$assetId || !in_array($type, ['buy', 'sell'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid transaction payload parameters.']);
    exit;
}

try {
    $pdo->beginTransaction();

    // 1. Fetch live metrics for target asset
    $stmt = $pdo->prepare("SELECT * FROM trade_assets WHERE id = ? FOR UPDATE");
    $stmt->execute([$assetId]);
    $asset = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$asset) {
        throw new Exception('Target ecosystem asset resource not found.');
    }

    // 2. Fetch User Wallet Data
    $walletStmt = $pdo->prepare("SELECT balance FROM wallets WHERE user_id = ? FOR UPDATE");
    $walletStmt->execute([$userId]);
    $userBalance = floatval($walletStmt->fetchColumn() ?: 0);

    // 3. Fetch or initialize the user's specific Asset Portfolio Balance
    // Ensure you have a 'user_assets' table tracking user holdings: (user_id, asset_id, units)
    $portfolioStmt = $pdo->prepare("SELECT units FROM user_assets WHERE user_id = ? AND asset_id = ? FOR UPDATE");
    $portfolioStmt->execute([$userId, $assetId]);
    $userAssetUnits = floatval($portfolioStmt->fetchColumn() ?: 0);

    $spotPrice = floatval($asset['current_price']);
    
    if ($type === 'buy') {
        // Platform Charges Brokerage Fee on Buys
        $feePercent = floatval($asset['buy_fee_percent'] ?? 1.00); 
        $grossCost = $amount * $spotPrice;
        $feeAmount = $grossCost * ($feePercent / 100);
        $totalCost = $grossCost + $feeAmount;

        if ($userBalance < $totalCost) {
            throw new Exception('Insufficient funds in wallet to cover asset price + platform fees.');
        }

        // Deduct Cash, Credit Portfolio Units
        $deductCash = $pdo->prepare("UPDATE wallets SET balance = balance - ? WHERE user_id = ?");
        $deductCash->execute([$totalCost, $userId]);

        $checkRow = $pdo->prepare("SELECT id FROM user_assets WHERE user_id = ? AND asset_id = ?");
        $checkRow->execute([$userId, $assetId]);
        if ($checkRow->fetch()) {
            $updatePortfolio = $pdo->prepare("UPDATE user_assets SET units = units + ? WHERE user_id = ? AND asset_id = ?");
            $updatePortfolio->execute([$amount, $userId, $assetId]);
        } else {
            $insertPortfolio = $pdo->prepare("INSERT INTO user_assets (user_id, asset_id, units) VALUES (?, ?, ?)");
            $insertPortfolio->execute([$userId, $assetId, $amount]);
        }

        $logMessage = "Purchased {$amount} {$asset['ticker']} at \${$spotPrice} (Fee: \${$feeAmount})";

    } else {
        // TYPE IS SELL (Selling directly back to Platform at a 3% Liquidity Spread Discount)
        $platformSpreadDiscount = 3.00; // Platform buys it 3% cheaper than current market price
        $sellPrice = $spotPrice * (1 - ($platformSpreadDiscount / 100));
        
        $feePercent = floatval($asset['sell_fee_percent'] ?? 1.50);
        $grossCredit = $amount * $sellPrice;
        $feeAmount = $grossCredit * ($feePercent / 100);
        $finalPayout = $grossCredit - $feeAmount;

        if ($userAssetUnits < $amount) {
            throw new Exception('Insufficient asset inventory units available to liquidate.');
        }

        // Deduct Portfolio Units, Credit Cash Wallet
        $deductUnits = $pdo->prepare("UPDATE user_assets SET units = units - ? WHERE user_id = ? AND asset_id = ?");
        $deductUnits->execute([$amount, $userId, $assetId]);

        $creditCash = $pdo->prepare("UPDATE wallets SET balance = balance + ? WHERE user_id = ?");
        $creditCash->execute([$finalPayout, $userId]);

        $logMessage = "Liquidated {$amount} {$asset['ticker']} to platform at \${$sellPrice} (Processing Fee: \${$feeAmount})";
    }

    // 4. Log Financial Transaction Matrix Ledger Record
    $txRef = 'TRD-' . strtoupper(bin2hex(random_bytes(5)));
    $logTx = $pdo->prepare("INSERT INTO transactions (user_id, reference, amount, type, status, gateway, description, created_at) VALUES (?, ?, ?, ?, 'completed', 'marketplace', ?, NOW())");
    $logTx->execute([$userId, $txRef, ($type === 'buy' ? $totalCost : $finalPayout), $type, $logMessage]);

    // 5. Trigger the Volatility Price Adjustment Engine to alter system rates organically
    $newMarketPrice = EcosystemEngine::adjustPrice($pdo, $assetId, $type, $amount);

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Trade sequence processed successfully.',
        'new_price' => number_format($newMarketPrice, 4),
        'log' => $logMessage
    ]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) { $pdo->rollBack(); }
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}