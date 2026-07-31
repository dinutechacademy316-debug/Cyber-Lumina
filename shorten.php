<?php
// shorten.php — Built-in URL shortener
// POST url=<long_url> → returns { short: "https://..." }
// Used as reliable fallback when external shorteners reject InfinityFree domain

require_once __DIR__ . '/db.php';
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// Auto-create table
try {
    getDB()->exec("CREATE TABLE IF NOT EXISTS short_urls (
        id         INT AUTO_INCREMENT PRIMARY KEY,
        code       VARCHAR(10)   NOT NULL UNIQUE,
        long_url   TEXT          NOT NULL,
        hits       INT           DEFAULT 0,
        created_at TIMESTAMP     DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_code (code)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
} catch (Exception $e) { /* already exists */ }

$longUrl = trim($_POST['url'] ?? '');
if (!$longUrl) {
    echo json_encode(['error' => 'No URL provided']); exit;
}
if (!filter_var($longUrl, FILTER_VALIDATE_URL)) {
    echo json_encode(['error' => 'Invalid URL']); exit;
}

// Check if this exact URL was already shortened
$st = getDB()->prepare("SELECT code FROM short_urls WHERE long_url = ? LIMIT 1");
$st->execute([$longUrl]);
$existing = $st->fetchColumn();
if ($existing) {
    echo json_encode(['short' => SITE_URL . '/go/' . $existing]);
    exit;
}

// Generate a unique 7-char alphanumeric code
$chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
$code  = '';
for ($i = 0; $i < 7; $i++) $code .= $chars[random_int(0, strlen($chars) - 1)];

// Insert (retry on collision up to 5 times)
$ok = false;
for ($t = 0; $t < 5; $t++) {
    try {
        getDB()->prepare("INSERT INTO short_urls (code, long_url) VALUES (?, ?)")
               ->execute([$code, $longUrl]);
        $ok = true;
        break;
    } catch (Exception $e) {
        // Code collision → regenerate
        $code = '';
        for ($i = 0; $i < 7; $i++) $code .= $chars[random_int(0, strlen($chars) - 1)];
    }
}

if (!$ok) {
    echo json_encode(['error' => 'Could not generate code']); exit;
}

echo json_encode(['short' => SITE_URL . '/go/' . $code]);
