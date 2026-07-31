<?php
// ══════════════════════════════════════════════════════════════
// s.php — CyberLumina Built-in URL Shortener
// Stores short codes in links.json — no external API needed.
// Short URLs: https://yourdomain.com/s/abc123
//
// Usage:
//   Shorten:  GET /s.php?url=https://...    → returns short URL
//   Redirect: GET /s/abc123                 → redirects to long URL
//             (routed via .htaccess)
// ══════════════════════════════════════════════════════════════

$dbFile  = __DIR__ . '/links.json';
$baseUrl = 'https://' . $_SERVER['HTTP_HOST'] . '/s/';

// ── REDIRECT MODE — called via .htaccess RewriteRule ──
$code = $_GET['c'] ?? '';
if ($code) {
    $code  = preg_replace('/[^a-zA-Z0-9]/', '', $code); // sanitize
    $links = [];
    if (file_exists($dbFile)) {
        $links = json_decode(file_get_contents($dbFile), true) ?: [];
    }
    if (isset($links[$code])) {
        header('Cache-Control: no-store, no-cache');
        header('Location: ' . $links[$code], true, 302);
    } else {
        header('Location: /');
    }
    exit;
}

// ── SHORTEN MODE ──
header('Content-Type: text/plain; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Cache-Control: no-store');

$url = isset($_GET['url']) ? trim($_GET['url']) : '';

if (!$url) {
    http_response_code(400); echo 'MISSING_URL'; exit;
}

// Auto-add scheme
if (!preg_match('/^https?:\/\//i', $url)) {
    $url = 'https://' . $url;
}

// Validate URL
if (!filter_var($url, FILTER_VALIDATE_URL)) {
    http_response_code(400); echo 'INVALID_URL'; exit;
}

// Load existing links
$links = [];
if (file_exists($dbFile)) {
    $links = json_decode(file_get_contents($dbFile), true) ?: [];
}

// Check if this URL was already shortened — return same code
foreach ($links as $c => $u) {
    if ($u === $url) {
        echo $baseUrl . $c;
        exit;
    }
}

// Generate a unique 7-char alphanumeric code
$chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
$len   = strlen($chars);
do {
    $code = '';
    for ($i = 0; $i < 7; $i++) {
        $code .= $chars[random_int(0, $len - 1)];
    }
} while (isset($links[$code]));

// Save the mapping
$links[$code] = $url;
$saved = file_put_contents($dbFile, json_encode($links, JSON_PRETTY_PRINT));

if ($saved === false) {
    // File write failed (permissions issue) — return original URL
    http_response_code(500);
    echo $url;
    exit;
}

echo $baseUrl . $code;
