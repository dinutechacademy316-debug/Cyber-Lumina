<?php
// ══════════════════════════════════════════════════════════════
// proxy.php — CyberLumina Disguise Proxy
// Fetches target site server-side, strips X-Frame-Options/CSP
// so ANY website can appear in the iframe without blocking
// ══════════════════════════════════════════════════════════════

$url = isset($_GET['url']) ? trim($_GET['url']) : '';
if (!$url) { http_response_code(400); exit('NO_URL'); }
if (!preg_match('/^https?:\/\//i', $url)) { $url = 'https://' . $url; }
if (!filter_var($url, FILTER_VALIDATE_URL)) { http_response_code(400); exit('INVALID_URL'); }

$ua = 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.0 Mobile/15E148 Safari/604.1';
$html = false;
$finalUrl = $url;

// ── cURL (primary) ────────────────────────────────────────────
if (function_exists('curl_init')) {
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL            => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 12,
        CURLOPT_CONNECTTIMEOUT => 8,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS      => 5,
        CURLOPT_USERAGENT      => $ua,
        CURLOPT_HTTPHEADER     => [
            'Accept: text/html,application/xhtml+xml,*/*',
            'Accept-Language: en-US,en;q=0.9',
            'Accept-Encoding: identity',
        ],
    ]);
    $html     = curl_exec($ch);
    $finalUrl = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL) ?: $url;
    curl_close($ch);
}

// ── file_get_contents fallback ────────────────────────────────
if (!$html && ini_get('allow_url_fopen')) {
    $ctx = stream_context_create([
        'http' => [
            'timeout'         => 10,
            'follow_location' => 1,
            'user_agent'      => $ua,
            'ignore_errors'   => true,
        ],
        'ssl' => ['verify_peer' => false, 'verify_peer_name' => false],
    ]);
    $html = @file_get_contents($url, false, $ctx);
}

// ── Both failed — signal JS to use redirect instead ──────────
if (!$html) {
    http_response_code(503);
    exit('PROXY_FAILED');
}

// ── Rewrite relative URLs → absolute ─────────────────────────
$parsed = parse_url($finalUrl);
$origin = $parsed['scheme'] . '://' . $parsed['host'];

// src="/..." href="/..." → absolute
$html = preg_replace_callback(
    '/(src|href|action)=(["\'])\/(?!\/)/',
    function($m) use ($origin) { return $m[1] . '=' . $m[2] . $origin . '/'; },
    $html
);
// url(/...) in inline styles
$html = preg_replace('/url\((["\']?)\/(?!\/)/', 'url($1' . $origin . '/', $html);

// ── Remove headers that block iframe embedding ─────────────────
header_remove('X-Frame-Options');
header_remove('Content-Security-Policy');
header_remove('X-Content-Type-Options');

// ── Inject <base> tag so remaining relative paths resolve ──────
$baseTag = '<base href="' . htmlspecialchars($origin . '/') . '">';
$html = preg_replace('/<head([^>]*)>/i', '<head$1>' . $baseTag, $html, 1);

// ── Anti frame-bust: override top === self checks ─────────────
$antiFrameBust = '<script>try{Object.defineProperty(window,"top",{get:function(){return window;},configurable:true});}catch(e){}</script>';
$html = preg_replace('/<head([^>]*)>/i', '<head$1>' . $antiFrameBust, $html, 1);

header('Content-Type: text/html; charset=utf-8');
header('Cache-Control: no-store');
echo $html;
