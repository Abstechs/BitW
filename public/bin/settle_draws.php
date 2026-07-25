<?php
// bin/settle_draws.php
if (php_sapi_name() !== 'cli') {
    exit("Access Denied: This script can only be run via CLI.\n");
}

require_once __DIR__ . '/../includes/db.php'; // Adjust path to your PDO connection file
require_once __DIR__ . '/../src/LottoDrawEngineService.php';

$drawDate = $argv[1] ?? date('Y-m-d');
echo "[INFO] Running Lotto Settlement Engine for Date: {$drawDate}...\n";

$engine = new LottoDrawEngineService($pdo);
$results = $engine->processDailyDraws($drawDate, [4, 6, 8], 'cron_scheduled');

foreach ($results as $length => $res) {
    echo "[{$length}-DIGIT] Status: {$res['status']} | Msg: " . ($res['message'] ?? 'Settled') . "\n";
}
//Crontab Setup:

//To automate draws daily at midnight, add this rule to your server's crontab (crontab -e):

//Bash
//0 0 * * * /usr/bin/php /path/to/your/project/bin/settle_draws.php >> /path/to/your/project/logs/draw_cron.log 2>&1