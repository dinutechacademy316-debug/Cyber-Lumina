<?php
ob_start();
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    ob_clean(); echo json_encode(['error'=>'Not authenticated']); exit;
}

require_once 'db.php';
initDB();

$txhash  = trim($_POST['txhash']   ?? '');
$planId  = trim($_POST['plan_id']  ?? '');
$crypto  = trim($_POST['crypto']   ?? '');
$userId  = (int)$_SESSION['user_id'];

if (!$txhash || !$planId || !$crypto) {
    ob_clean(); echo json_encode(['error'=>'Missing required fields']); exit;
}

// ── Load plan price & credits from DB (never trust client) ───
$planDefaults = [
    'starter' => ['credits'=>500,  'usd'=>1.70,  'name'=>'Starter'],
    'pro'     => ['credits'=>1500, 'usd'=>4.00,  'name'=>'Pro'],
    'elite'   => ['credits'=>5000, 'usd'=>10.00, 'name'=>'Elite'],
];
if (!array_key_exists($planId, $planDefaults)) {
    ob_clean(); echo json_encode(['error'=>'Invalid plan selected']); exit;
}
$pdo      = getDB();
$amtUSD   = (float)(getSiteSetting('plan_'.$planId.'_usd',   (string)$planDefaults[$planId]['usd']));
$credits  = (int)getSiteSetting(   'plan_'.$planId.'_credits',(string)$planDefaults[$planId]['credits']);
$planName = $planDefaults[$planId]['name'];
if ($amtUSD <= 0 || $credits <= 0) {
    ob_clean(); echo json_encode(['error'=>'Plan config error. Contact support.']); exit;
}

// ── Save receipt screenshot ───────────────────────────────────
$receiptPath = '';
if (!empty($_FILES['receipt']['tmp_name']) && $_FILES['receipt']['error'] === UPLOAD_ERR_OK) {
    $allowed = ['image/jpeg','image/png','image/webp','image/gif'];
    $mime    = mime_content_type($_FILES['receipt']['tmp_name']);
    if (in_array($mime, $allowed) && $_FILES['receipt']['size'] <= 5*1024*1024) {
        $ext   = preg_replace('/[^a-z0-9]/i','',strtolower(pathinfo($_FILES['receipt']['name'],PATHINFO_EXTENSION))) ?: 'jpg';
        $dir   = __DIR__.'/uploads/receipts/';
        if (!is_dir($dir)) mkdir($dir, 0755, true);
        $fname = 'rcpt_'.$userId.'_'.preg_replace('/[^a-zA-Z0-9]/','',substr($txhash,0,12)).'_'.time().'.'.$ext;
        if (move_uploaded_file($_FILES['receipt']['tmp_name'], $dir.$fname)) {
            $receiptPath = 'uploads/receipts/'.$fname;
        }
    }
}

// ── Receipt is REQUIRED ───────────────────────────────────────────────────────
if (!$receiptPath) {
    ob_clean();
    echo json_encode(['error' => 'Payment receipt screenshot is required. Please upload a screenshot of your payment.']);
    exit;
}


// ── Ensure DB tables ─────────────────────────────────────────
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS payment_verifications (
        id INT AUTO_INCREMENT PRIMARY KEY,
        txhash VARCHAR(200) NOT NULL UNIQUE,
        user_id INT NOT NULL,
        plan_id VARCHAR(64) NOT NULL,
        credits INT NOT NULL,
        crypto VARCHAR(32) NOT NULL,
        amount DECIMAL(20,6) DEFAULT 0,
        auto_verified TINYINT(1) DEFAULT 0,
        verified_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $pdo->exec("CREATE TABLE IF NOT EXISTS payment_requests (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL, plan_id VARCHAR(32) NOT NULL, plan_name VARCHAR(64) NOT NULL,
        credits INT NOT NULL, crypto VARCHAR(32) NOT NULL, amount_usd DECIMAL(10,2) DEFAULT 0,
        txhash VARCHAR(250) NOT NULL, note TEXT, receipt_path VARCHAR(500) DEFAULT '',
        status ENUM('pending','approved','rejected') DEFAULT 'pending',
        reviewed_at TIMESTAMP NULL DEFAULT NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
} catch(Exception $e){}

// ── Add receipt_path column if missing in old DB ──────────────
try {
    $chkCol = $pdo->query("SHOW COLUMNS FROM payment_requests LIKE 'receipt_path'")->fetchAll();
    if (empty($chkCol)) {
        $pdo->exec("ALTER TABLE payment_requests ADD COLUMN receipt_path VARCHAR(500) DEFAULT '' AFTER note");
    }
} catch(Exception $e){}



// ── Prevent double-spend ─────────────────────────────────────
$c1 = $pdo->prepare("SELECT id FROM payment_verifications WHERE txhash=?");
$c1->execute([$txhash]);
if ($c1->fetch()) { ob_clean(); echo json_encode(['error'=>'This transaction has already been used. Each payment can only be applied once.']); exit; }
$c2 = $pdo->prepare("SELECT id FROM payment_requests WHERE txhash=? AND status IN ('pending','approved')");
$c2->execute([$txhash]);
if ($c2->fetch()) { ob_clean(); echo json_encode(['error'=>'This transaction has already been submitted.']); exit; }

// ── Wallet address ────────────────────────────────────────────
$walletAddr = getSiteSetting('crypto_'.$crypto, '');
if (!$walletAddr) {
    ob_clean(); echo json_encode(['error'=>'Wallet address not configured for this crypto. Please contact support.']); exit;
}

// ══════════════════════════════════════════════════════════════
// HELPER FUNCTIONS
// ══════════════════════════════════════════════════════════════

function httpGet(string $url): string|false {
    return @file_get_contents($url, false, stream_context_create(['http'=>[
        'timeout'       => 14,
        'ignore_errors' => true,
        'header'        => "User-Agent: Mozilla/5.0\r\nAccept: application/json\r\n",
    ]]));
}

function httpPost(string $url, string $body, string $ct='application/json'): string|false {
    return @file_get_contents($url, false, stream_context_create(['http'=>[
        'method'        => 'POST',
        'timeout'       => 14,
        'ignore_errors' => true,
        'header'        => "User-Agent: Mozilla/5.0\r\nContent-Type: {$ct}\r\nAccept: application/json\r\n",
        'content'       => $body,
    ]]));
}

// CoinGecko price — no API key needed
function getCryptoPrice(string $coin): float {
    $map = ['btc'=>'bitcoin','eth'=>'ethereum','bnb'=>'binancecoin','ltc'=>'litecoin','sol'=>'solana'];
    $id  = $map[$coin] ?? null;
    if (!$id) return 1.0; // stablecoin
    $r = httpGet("https://api.coingecko.com/api/v3/simple/price?ids={$id}&vs_currencies=usd");
    if (!$r) return 0.0;
    $d = json_decode($r, true);
    return (float)($d[$id]['usd'] ?? 0);
}

// ══════════════════════════════════════════════════════════════
// BLOCKCHAIN VERIFICATION FUNCTIONS
// Return: ['verified'=>bool, 'amount'=>float] | ['verified'=>false, 'error'=>string] | ['verified'=>false, 'pending'=>true, 'error'=>string]
// ══════════════════════════════════════════════════════════════

// ── USDT TRC-20 ───────────────────────────────────────────────
function verify_usdt_trc20(string $tx, string $wallet, float $amtUSD): array {
    $USDT_TRC20 = 'TR7NHqjeKQxGTCi8q8ZY4pL8otSzgjLj6t';

    // Method 1: TronScan
    $r = httpGet('https://apilist.tronscanapi.com/api/transaction-info?hash='.urlencode($tx));
    if ($r) {
        $d = json_decode($r, true);
        if (!empty($d['trc20TransferInfo'])) {
            foreach ($d['trc20TransferInfo'] as $tf) {
                $sym    = strtoupper($tf['tokenInfo']['tokenAbbr'] ?? $tf['symbol'] ?? '');
                $toAddr = strtolower($tf['to_address'] ?? '');
                $amount = (float)($tf['amount'] ?? 0) / 1e6;
                if ($sym === 'USDT' && strtolower($wallet) === $toAddr && $amount >= $amtUSD * 0.98) {
                    if (isset($d['confirmed']) && !$d['confirmed'])
                        return ['verified'=>false,'error'=>'Transaction found but not yet confirmed. Wait a moment and try again.'];
                    return ['verified'=>true,'amount'=>$amount];
                }
            }
        }
    }
    // Method 2: TronGrid
    $r2 = httpGet('https://api.trongrid.io/v1/transactions/'.urlencode($tx).'/events');
    if ($r2) {
        $d2 = json_decode($r2, true);
        foreach ($d2['data'] ?? [] as $ev) {
            if (strtolower($ev['contract_address'] ?? '') !== strtolower($USDT_TRC20)) continue;
            if ($ev['event_name'] !== 'Transfer') continue;
            $to  = strtolower($ev['result']['to'] ?? '');
            $val = (float)($ev['result']['value'] ?? 0) / 1e6;
            if (strtolower($wallet) === $to && $val >= $amtUSD * 0.98)
                return ['verified'=>true,'amount'=>$val];
        }
    }
    return ['verified'=>false,'error'=>'USDT TRC-20 transaction not found or not confirmed. Please wait a moment and try again.'];
}

// ── Bitcoin (Blockstream — no API key) ───────────────────────
function verify_btc(string $tx, string $wallet, float $amtUSD): array {
    $r = httpGet('https://blockstream.info/api/tx/'.urlencode($tx));
    if (!$r) {
        // Fallback: blockchain.info
        $r = httpGet('https://blockchain.info/rawtx/'.urlencode($tx).'?cors=true');
        if ($r) {
            $d = json_decode($r, true);
            if (!$d) return ['verified'=>false,'pending'=>true,'error'=>'Bitcoin network temporarily unavailable. Saved for review.'];
            $price = getCryptoPrice('btc');
            if ($price <= 0) return ['verified'=>false,'pending'=>true,'error'=>'BTC price feed unavailable. Saved for review.'];
            $confirmations = $d['confirmations'] ?? 0;
            if ($confirmations < 1) return ['verified'=>false,'error'=>'Bitcoin transaction not yet confirmed. Please wait for 1+ confirmations and try again.'];
            $expectedBTC = $amtUSD / $price;
            foreach ($d['out'] as $out) {
                if (strtolower($out['addr'] ?? '') === strtolower($wallet)) {
                    $sent = (float)$out['value'] / 1e8;
                    if ($sent >= $expectedBTC * 0.95) return ['verified'=>true,'amount'=>$sent];
                }
            }
            return ['verified'=>false,'error'=>'BTC amount insufficient or wrong wallet address.'];
        }
        return ['verified'=>false,'pending'=>true,'error'=>'Bitcoin network temporarily unavailable. Payment saved for manual review.'];
    }
    $d = json_decode($r, true);
    if (!$d || isset($d['error'])) return ['verified'=>false,'error'=>'Transaction not found on Bitcoin blockchain.'];
    if (!($d['status']['confirmed'] ?? false)) return ['verified'=>false,'error'=>'Bitcoin transaction not yet confirmed. Please wait for at least 1 confirmation and try again.'];
    $price = getCryptoPrice('btc');
    if ($price <= 0) return ['verified'=>false,'pending'=>true,'error'=>'BTC price feed unavailable. Payment saved for manual review.'];
    $expectedBTC = $amtUSD / $price;
    foreach ($d['vout'] as $out) {
        if (strtolower($out['scriptpubkey_address'] ?? '') === strtolower($wallet)) {
            $sent = (float)$out['value'] / 1e8;
            if ($sent >= $expectedBTC * 0.95) return ['verified'=>true,'amount'=>$sent];
        }
    }
    return ['verified'=>false,'error'=>'BTC amount does not match plan price or wallet address mismatch. Ensure you sent the correct amount.'];
}

// ── Ethereum (Etherscan public) ───────────────────────────────
function verify_eth(string $tx, string $wallet, float $amtUSD): array {
    $r = httpGet('https://api.etherscan.io/api?module=proxy&action=eth_getTransactionByHash&txhash='.urlencode($tx));
    if (!$r) return ['verified'=>false,'pending'=>true,'error'=>'Ethereum network temporarily unavailable. Saved for review.'];
    $d  = json_decode($r, true);
    $t  = $d['result'] ?? null;
    if (!$t || $t === 'null') return ['verified'=>false,'error'=>'Transaction not found on Ethereum blockchain.'];
    if (empty($t['blockNumber'])) return ['verified'=>false,'error'=>'ETH transaction is pending. Please wait for confirmation and try again.'];
    if (strtolower($t['to'] ?? '') !== strtolower($wallet))
        return ['verified'=>false,'error'=>'ETH was not sent to our wallet address.'];
    $price = getCryptoPrice('eth');
    if ($price <= 0) return ['verified'=>false,'pending'=>true,'error'=>'ETH price unavailable. Saved for review.'];
    $ethSent = hexdec($t['value']) / 1e18;
    if ($ethSent >= ($amtUSD / $price) * 0.95) return ['verified'=>true,'amount'=>$ethSent];
    return ['verified'=>false,'error'=>'ETH amount sent is less than the plan price. Please send the correct amount.'];
}

// ── USDT ERC-20 (Etherscan token transfer logs) ───────────────
function verify_usdt_erc20(string $tx, string $wallet, float $amtUSD): array {
    $USDT_CONTRACT  = '0xdac17f958d2ee523a2206206994597c13d831ec7';
    $TRANSFER_TOPIC = '0xddf252ad1be2c89b69c2b068fc378daa952ba7f163c4a11628f55a4df523b3ef';
    $r = httpGet('https://api.etherscan.io/api?module=proxy&action=eth_getTransactionReceipt&txhash='.urlencode($tx));
    if (!$r) return ['verified'=>false,'pending'=>true,'error'=>'Ethereum network temporarily unavailable. Saved for review.'];
    $d  = json_decode($r, true);
    $rc = $d['result'] ?? null;
    if (!$rc || $rc === 'null') return ['verified'=>false,'error'=>'Transaction not found or not yet confirmed on Ethereum.'];
    if (($rc['status'] ?? '') === '0x0') return ['verified'=>false,'error'=>'Ethereum transaction failed on blockchain.'];
    foreach ($rc['logs'] ?? [] as $log) {
        if (strtolower($log['address']) !== $USDT_CONTRACT) continue;
        if (($log['topics'][0] ?? '') !== $TRANSFER_TOPIC) continue;
        $toAddr = '0x'.substr($log['topics'][2] ?? '', 26);
        if (strtolower($toAddr) !== strtolower($wallet)) continue;
        $amount = hexdec($log['data']) / 1e6;
        if ($amount >= $amtUSD * 0.98) return ['verified'=>true,'amount'=>$amount];
    }
    return ['verified'=>false,'error'=>'USDT ERC-20 transfer to our wallet not found. Ensure you used the correct ERC-20 network and wallet address.'];
}

// ── BNB BEP-20 (BscScan public) ──────────────────────────────
function verify_bnb(string $tx, string $wallet, float $amtUSD): array {
    $r = httpGet('https://api.bscscan.com/api?module=proxy&action=eth_getTransactionByHash&txhash='.urlencode($tx));
    if (!$r) return ['verified'=>false,'pending'=>true,'error'=>'BSC network temporarily unavailable. Saved for review.'];
    $d  = json_decode($r, true);
    $t  = $d['result'] ?? null;
    if (!$t || $t === 'null') return ['verified'=>false,'error'=>'Transaction not found on BNB Smart Chain.'];
    if (empty($t['blockNumber'])) return ['verified'=>false,'error'=>'BNB transaction is pending. Please wait for confirmation.'];
    if (strtolower($t['to'] ?? '') !== strtolower($wallet))
        return ['verified'=>false,'error'=>'BNB was not sent to our wallet address.'];
    $price = getCryptoPrice('bnb');
    if ($price <= 0) return ['verified'=>false,'pending'=>true,'error'=>'BNB price unavailable. Saved for review.'];
    $bnbSent = hexdec($t['value']) / 1e18;
    if ($bnbSent >= ($amtUSD / $price) * 0.95) return ['verified'=>true,'amount'=>$bnbSent];
    return ['verified'=>false,'error'=>'BNB amount sent is less than the plan price.'];
}

// ── Litecoin (Blockcypher — no API key) ───────────────────────
function verify_ltc(string $tx, string $wallet, float $amtUSD): array {
    $r = httpGet('https://api.blockcypher.com/v1/ltc/main/txs/'.urlencode($tx));
    if (!$r) return ['verified'=>false,'pending'=>true,'error'=>'Litecoin network temporarily unavailable. Saved for review.'];
    $d = json_decode($r, true);
    if (!$d || isset($d['error'])) return ['verified'=>false,'error'=>'Transaction not found on Litecoin blockchain.'];
    if (($d['confirmations'] ?? 0) < 1) return ['verified'=>false,'error'=>'LTC transaction not yet confirmed. Please wait for 1+ confirmations.'];
    $price = getCryptoPrice('ltc');
    if ($price <= 0) return ['verified'=>false,'pending'=>true,'error'=>'LTC price unavailable. Saved for review.'];
    $expectedLTC = $amtUSD / $price;
    foreach ($d['outputs'] ?? [] as $out) {
        $addrs = array_map('strtolower', $out['addresses'] ?? []);
        if (in_array(strtolower($wallet), $addrs)) {
            $sent = (float)$out['value'] / 1e8;
            if ($sent >= $expectedLTC * 0.95) return ['verified'=>true,'amount'=>$sent];
        }
    }
    return ['verified'=>false,'error'=>'LTC amount does not match plan price or wrong wallet address.'];
}

// ── Solana (Public RPC — no API key) ─────────────────────────
function verify_sol(string $tx, string $wallet, float $amtUSD): array {
    $payload = json_encode(['jsonrpc'=>'2.0','id'=>1,'method'=>'getTransaction',
        'params'=>[$tx,['encoding'=>'json','maxSupportedTransactionVersion'=>0]]]);
    $r = httpPost('https://api.mainnet-beta.solana.com', $payload);
    if (!$r) return ['verified'=>false,'pending'=>true,'error'=>'Solana network temporarily unavailable. Saved for review.'];
    $d  = json_decode($r, true);
    $t  = $d['result'] ?? null;
    if (!$t) return ['verified'=>false,'error'=>'Transaction not found on Solana blockchain.'];
    if ($t['meta']['err'] !== null) return ['verified'=>false,'error'=>'Solana transaction failed on blockchain.'];
    $price = getCryptoPrice('sol');
    if ($price <= 0) return ['verified'=>false,'pending'=>true,'error'=>'SOL price unavailable. Saved for review.'];
    $expectedSOL  = $amtUSD / $price;
    $accounts     = $t['transaction']['message']['accountKeys'] ?? [];
    $preBal       = $t['meta']['preBalances']  ?? [];
    $postBal      = $t['meta']['postBalances'] ?? [];
    foreach ($accounts as $i => $acc) {
        $key = is_array($acc) ? ($acc['pubkey'] ?? '') : $acc;
        if (strtolower($key) === strtolower($wallet)) {
            $change = (($postBal[$i] ?? 0) - ($preBal[$i] ?? 0)) / 1e9;
            if ($change >= $expectedSOL * 0.95) return ['verified'=>true,'amount'=>$change];
        }
    }
    return ['verified'=>false,'error'=>'SOL amount sent does not match plan price or wrong wallet address.'];
}

// ══════════════════════════════════════════════════════════════
// ROUTE TO CORRECT VERIFIER
// ══════════════════════════════════════════════════════════════
$result = match($crypto) {
    'usdt_trc20' => verify_usdt_trc20($txhash, $walletAddr, $amtUSD),
    'usdt_erc20' => verify_usdt_erc20($txhash, $walletAddr, $amtUSD),
    'btc'        => verify_btc($txhash, $walletAddr, $amtUSD),
    'eth'        => verify_eth($txhash, $walletAddr, $amtUSD),
    'bnb'        => verify_bnb($txhash, $walletAddr, $amtUSD),
    'ltc'        => verify_ltc($txhash, $walletAddr, $amtUSD),
    'sol'        => verify_sol($txhash, $walletAddr, $amtUSD),
    default      => ['verified'=>false,'error'=>'Unsupported cryptocurrency.'],
};

// ══════════════════════════════════════════════════════════════
// PROCESS RESULT
// ══════════════════════════════════════════════════════════════
$note        = trim($_POST['note'] ?? '');
$verifiedAmt = (float)($result['amount'] ?? 0);

if ($result['verified']) {
    // ── ✅ AUTO-VERIFIED: Add credits immediately ─────────────
    try {
        $pdo->prepare("INSERT INTO payment_verifications (txhash,user_id,plan_id,credits,crypto,amount,auto_verified) VALUES (?,?,?,?,?,?,1)")
            ->execute([$txhash,$userId,$planId,$credits,$crypto,$verifiedAmt]);

        $pdo->prepare("UPDATE users SET credits = credits + ? WHERE id = ?")
            ->execute([$credits, $userId]);

        // Record in payment_requests for admin visibility
        $pdo->prepare("INSERT IGNORE INTO payment_requests (user_id,plan_id,plan_name,credits,crypto,amount_usd,txhash,note,receipt_path,status,reviewed_at) VALUES (?,?,?,?,?,?,?,?,?,'approved',NOW())")
            ->execute([$userId,$planId,$planName,$credits,$crypto,round($verifiedAmt,6),$txhash,$note,$receiptPath]);

        logActivity($userId,'payment_auto_verified',
            'AUTO-VERIFIED '.strtoupper($crypto).' | TX:'.$txhash.' | Amount:'.$verifiedAmt.' | +'.$credits.' credits'.($receiptPath?' | Receipt:'.$receiptPath:''));

        ob_clean();
        echo json_encode(['verified'=>true,'credits_added'=>$credits,'amount'=>round($verifiedAmt,6)]);

    } catch(Exception $e) {
        ob_clean();
        echo json_encode(['error'=>'Payment verified but credits failed to apply. Contact support with TX: '.$txhash]);
    }

} elseif (!empty($result['pending'])) {
    // ── ⏳ API TEMPORARILY DOWN: Save for manual review ───────
    try {
        $pdo->prepare("INSERT IGNORE INTO payment_requests (user_id,plan_id,plan_name,credits,crypto,amount_usd,txhash,note,receipt_path,status) VALUES (?,?,?,?,?,?,?,?,?,'pending')")
            ->execute([$userId,$planId,$planName,$credits,$crypto,$amtUSD,$txhash,$note,$receiptPath]);
        logActivity($userId,'plan_request','Manual review: '.$planName.' via '.strtoupper($crypto).' | TX:'.$txhash);
    } catch(Exception $e){}
    ob_clean();
    echo json_encode(['verified'=>false,'manual'=>true,'message'=>'Payment received. Blockchain temporarily slow — being processed within 24 hours.']);

} else {
    // ── ❌ VERIFICATION FAILED: Real error ────────────────────
    ob_clean();
    echo json_encode(['verified'=>false,'error'=>$result['error'] ?? 'Verification failed. Check your TX hash and try again.']);
}
