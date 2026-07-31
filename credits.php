<?php
// credits.php — Dedicated credit deduction endpoint
// Kept minimal to avoid any output corruption
ob_start();
error_reporting(0);
ini_set('display_errors', 0);

session_start();

// Set JSON header and clear buffer immediately
ob_clean();
header('Content-Type: application/json; charset=utf-8');

// Must be logged in
if (empty($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Not logged in']);
    exit;
}

require_once __DIR__ . '/db.php';

$feature = trim($_POST['feature'] ?? '');
$costs   = ['generate_link' => 20];

if (!isset($costs[$feature])) {
    echo json_encode(['error' => 'Unknown feature: ' . $feature]);
    exit;
}

$userId = (int)$_SESSION['user_id'];
$cost   = $costs[$feature];

// Atomic deduct
$ok = deductCredits($userId, $cost);

if (!$ok) {
    echo json_encode([
        'error'   => 'insufficient_credits',
        'credits' => getCredits($userId)
    ]);
    exit;
}

$remaining = getCredits($userId);
$response  = ['success' => true, 'deducted' => $cost, 'credits' => $remaining];

if ($remaining <= 0) {
    $response['auto_signout'] = true;
}

echo json_encode($response);
exit; // prevent any stray output after JSON
