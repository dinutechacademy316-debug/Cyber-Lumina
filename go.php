<?php
// go.php — Short URL redirect handler
// /go/abc123 → looks up DB → 302 redirect to original long URL

require_once __DIR__ . '/db.php';

// Support both /go/CODE (via .htaccess) and /go.php?r=CODE
$code = '';
$uri  = $_SERVER['REQUEST_URI'] ?? '';

// Try to extract from path: /go/CODE
if (preg_match('#/go/([A-Za-z0-9_-]+)#', $uri, $m)) {
    $code = $m[1];
}
// Fallback: ?r=CODE query param
if (!$code) {
    $code = trim($_GET['r'] ?? '');
}

if (!$code) {
    header('Location: /');
    exit;
}

try {
    $st = getDB()->prepare("SELECT long_url FROM short_urls WHERE code = ? LIMIT 1");
    $st->execute([$code]);
    $url = $st->fetchColumn();

    if ($url) {
        // Count the hit
        getDB()->prepare("UPDATE short_urls SET hits = hits + 1 WHERE code = ?")
               ->execute([$code]);
        header('Location: ' . $url, true, 302);
    } else {
        header('Location: /');
    }
} catch (Exception $e) {
    header('Location: /');
}
exit;
