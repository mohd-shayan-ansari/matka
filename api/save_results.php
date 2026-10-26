<?php
/**
 * save_results.php
 * Called by the admin panel to persist result overrides.
 * Place this in /api/ on your server (both main + subdomain can share it via absolute URL).
 *
 * IMPORTANT: Set ADMIN_SECRET to a strong random string and update admin.html to match.
 */

define('ADMIN_SECRET', 'CHANGE_THIS_TO_A_STRONG_SECRET_KEY_123');
define('RESULTS_FILE', __DIR__ . '/../results.json');

// CORS: allow requests from your domains only
$allowed = ['https://matka.xyz', 'https://sbhada.matka.xyz', 'http://localhost', 'http://127.0.0.1'];
$origin  = $_SERVER['HTTP_ORIGIN'] ?? '';
if (in_array($origin, $allowed)) {
    header("Access-Control-Allow-Origin: $origin");
}
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Admin-Secret');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); echo json_encode(['ok'=>false,'error'=>'Method not allowed']); exit; }

// Validate secret
$secret = $_SERVER['HTTP_X_ADMIN_SECRET'] ?? '';
if ($secret !== ADMIN_SECRET) { http_response_code(403); echo json_encode(['ok'=>false,'error'=>'Forbidden']); exit; }

// Read & validate body
$body = file_get_contents('php://input');
$data = json_decode($body, true);
if (!$data || !isset($data['slots'], $data['categories'])) {
    http_response_code(400); echo json_encode(['ok'=>false,'error'=>'Invalid data']); exit;
}

// Sanitize numbers
foreach ($data['slots'] as &$slot) {
    foreach ($slot['results'] as &$num) {
        $num = max(0, min(99, (int)$num));
    }
}
unset($slot, $num);

$data['lastUpdated'] = date('c');

if (file_put_contents(RESULTS_FILE, json_encode($data, JSON_PRETTY_PRINT)) === false) {
    http_response_code(500); echo json_encode(['ok'=>false,'error'=>'Could not write file']); exit;
}

echo json_encode(['ok'=>true,'lastUpdated'=>$data['lastUpdated']]);
