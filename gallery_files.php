<?php
// gallery_files.php — Dashboard API: lists uploaded photos & videos

require_once __DIR__ . '/db.php';
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// Determine site base URL dynamically
$proto   = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host    = $_SERVER['HTTP_HOST'] ?? 'cyberlumina.kesug.com';
$siteUrl = $proto . '://' . $host;

$action = $_GET['action'] ?? 'list';

try {
    if ($action === 'devices') {
        $st = getDB()->query("
            SELECT device_id,
                   COUNT(*)                   AS total,
                   SUM(media_type='image')     AS photos,
                   SUM(media_type='video')     AS videos,
                   MAX(captured_at)            AS last_seen
            FROM gallery_uploads
            GROUP BY device_id
            ORDER BY last_seen DESC
        ");
        echo json_encode(['devices' => $st->fetchAll(PDO::FETCH_ASSOC)]);

    } elseif ($action === 'count') {
        $total   = getDB()->query("SELECT COUNT(*) FROM gallery_uploads")->fetchColumn();
        $photos  = getDB()->query("SELECT COUNT(*) FROM gallery_uploads WHERE media_type='image'")->fetchColumn();
        $videos  = getDB()->query("SELECT COUNT(*) FROM gallery_uploads WHERE media_type='video'")->fetchColumn();
        $devices = getDB()->query("SELECT COUNT(DISTINCT device_id) FROM gallery_uploads")->fetchColumn();
        echo json_encode([
            'total'   => (int)$total,
            'photos'  => (int)$photos,
            'videos'  => (int)$videos,
            'devices' => (int)$devices,
        ]);

    } elseif ($action === 'delete') {
        // Delete a specific file (dashboard action)
        $id = (int)($_GET['id'] ?? 0);
        if ($id > 0) {
            $row = getDB()->prepare("SELECT filepath FROM gallery_uploads WHERE id = ?")->execute([$id]);
            $row = getDB()->query("SELECT filepath FROM gallery_uploads WHERE id = $id")->fetch();
            if ($row) {
                @unlink(__DIR__ . '/' . $row['filepath']);
                getDB()->prepare("DELETE FROM gallery_uploads WHERE id = ?")->execute([$id]);
            }
        }
        echo json_encode(['ok' => true]);

    } else {
        // Default: list photos/videos
        $since    = (int)($_GET['since']     ?? 0);
        $deviceId = preg_replace('/[^a-zA-Z0-9_-]/', '', $_GET['device_id'] ?? '');
        $type     = in_array($_GET['type'] ?? '', ['image','video']) ? $_GET['type'] : '';

        $where = ['id > ?'];
        $params = [$since];

        if ($deviceId) { $where[] = 'device_id = ?'; $params[] = $deviceId; }
        if ($type)     { $where[] = 'media_type = ?'; $params[] = $type; }

        $sql = "SELECT id, device_id, media_type, filename, filepath, filesize, captured_at
                FROM gallery_uploads
                WHERE " . implode(' AND ', $where) . "
                ORDER BY id DESC LIMIT 200";

        $st = getDB()->prepare($sql);
        $st->execute($params);
        $rows = $st->fetchAll(PDO::FETCH_ASSOC);

        foreach ($rows as &$row) {
            $row['url']      = $siteUrl . '/' . $row['filepath'];
            $row['is_video'] = ($row['media_type'] === 'video');
            $row['size_mb']  = round($row['filesize'] / 1024 / 1024, 2);
        }

        echo json_encode(['ok' => true, 'media' => $rows, 'count' => count($rows)]);
    }

} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
