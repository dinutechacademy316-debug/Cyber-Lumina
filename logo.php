<?php
// logo.php — Serves the Aegis-7 agent logo from database
// Admin saves logo → DB (site_settings key=agent_logo_data)
// This file reads it and outputs as image

require_once __DIR__ . '/db.php';

$data = getSiteSetting('agent_logo_data', '');

if ($data && strpos($data, 'data:image/') === 0) {
    // Parse: data:image/jpeg;base64,<base64>
    if (preg_match('#^data:(image/[\w+]+);base64,(.+)$#s', $data, $m)) {
        $mime   = $m[1];
        $binary = base64_decode($m[2]);
        if ($binary) {
            header('Content-Type: ' . $mime);
            header('Cache-Control: public, max-age=300');
            header('Content-Length: ' . strlen($binary));
            echo $binary;
            exit;
        }
    }
}

// Fallback: serve aegis7.png from file
$file = __DIR__ . '/aegis7.png';
if (file_exists($file)) {
    header('Content-Type: image/png');
    header('Cache-Control: public, max-age=300');
    readfile($file);
} else {
    http_response_code(404);
    echo 'Logo not found';
}
