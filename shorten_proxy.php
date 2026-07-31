<?php
// shorten_proxy.php — Server-side shortener proxy
// Uses cURL (more reliable than file_get_contents on InfinityFree)

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$longUrl = trim($_POST['url'] ?? '');
if (!$longUrl || !filter_var($longUrl, FILTER_VALIDATE_URL)) {
    echo json_encode(['error' => 'invalid url']); exit;
}

$encoded = urlencode($longUrl);

// ── cURL helper ───────────────────────────────────────────────────
function curlGet(string $url): string|false {
    if (!function_exists('curl_init')) return false;
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => 0,
        CURLOPT_TIMEOUT        => 10,
        CURLOPT_CONNECTTIMEOUT => 6,
        CURLOPT_USERAGENT      => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
        CURLOPT_HTTPHEADER     => ['Accept: application/json, text/plain, */*'],
    ]);
    $res = curl_exec($ch);
    curl_close($ch);
    return $res ?: false;
}

// ── 1. shrtco.de (CORS-enabled, permissive, no API key) ──────────
$res = curlGet('https://api.shrtco.de/v2/shorten?url=' . $encoded);
if ($res) {
    $d = json_decode($res, true);
    if (!empty($d['ok']) && !empty($d['result']['full_short_link'])) {
        echo json_encode(['short' => $d['result']['full_short_link'], 'service' => 'shrtcode']);
        exit;
    }
}

// ── 2. TinyURL ────────────────────────────────────────────────────
$res = curlGet('https://tinyurl.com/api-create.php?url=' . $encoded);
if ($res && str_starts_with(trim($res), 'https://tinyurl.com/')) {
    echo json_encode(['short' => trim($res), 'service' => 'tinyurl']); exit;
}

// ── 3. is.gd ─────────────────────────────────────────────────────
$res = curlGet('https://is.gd/create.php?format=simple&url=' . $encoded);
if ($res && str_starts_with(trim($res), 'https://is.gd/')) {
    echo json_encode(['short' => trim($res), 'service' => 'isgd']); exit;
}

// ── 4. v.gd ──────────────────────────────────────────────────────
$res = curlGet('https://v.gd/create.php?format=simple&url=' . $encoded);
if ($res && str_starts_with(trim($res), 'https://v.gd/')) {
    echo json_encode(['short' => trim($res), 'service' => 'vgd']); exit;
}

// ── All failed ────────────────────────────────────────────────────
echo json_encode(['error' => 'all_failed']);
