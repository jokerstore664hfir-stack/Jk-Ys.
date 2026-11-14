<?php
declare(strict_types=1);
require __DIR__ . '/config.php';

session_set_cookie_params([
    'lifetime' => 0, 'path' => '/',
    'secure'   => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    'httponly' => true, 'samesite' => 'Strict',
]);
session_start();

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');

$action = $_GET['action'] ?? '';
$input  = json_decode((string)file_get_contents('php://input'), true) ?: [];

/* ============================================================
   1) فحص الصيانة + جلب رابط التلغرام (عام)
   ============================================================ */
if ($action === 'check_maintenance') {
    $g = getGistContent();
    echo json_encode([
        'active'   => !empty($g['maintenance']['active']),
        'telegram' => $g['telegram'] ?? TELEGRAM_URL,
    ]);
    exit;
}

/* ============================================================
   2) التحقق من مفتاح
   ============================================================ */
if ($action === 'verify_key') {
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
        http_response_code(405); echo json_encode(['success'=>false,'error'=>'method']); exit;
    }

    $key      = strtoupper(trim((string)($input['key'] ?? '')));
    $deviceId = trim((string)($input['device_id'] ?? ''));

    if ($key === '' || $deviceId === '') {
        echo json_encode(['success'=>false,'error'=>'Invalid input']); exit;
    }

    $g = getGistContent(true);
    if (!$g) { echo json_encode(['success'=>false,'error'=>'Server error']); exit; }

    // صيانة؟
    if (!empty($g['maintenance']['active'])) {
        echo json_encode(['success'=>false,'error'=>'Maintenance']); exit;
    }

    // البحث عن المفتاح (مطور → موزع → عادي)
    $foundType = null;
    $foundKey  = null;
    foreach (['developers','resellers','keys'] as $bucket) {
        if (!empty($g[$bucket][$key])) {
            $foundType = $bucket;
            $foundKey  = $g[$bucket][$key];
            break;
        }
    }

    if (!$foundType) {
        echo json_encode(['success'=>false,'error'=>'Invalid key']); exit;
    }

    // المطور = وصول غير محدود
    if ($foundType === 'developers') {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
        echo json_encode(['success'=>true, 'data'=>[
            'role'       => 'developer',
            'csrf'       => $_SESSION['csrf'],
            'telegram'   => $g['telegram'] ?? TELEGRAM_URL,
        ]]);
        exit;
    }

    // فحص الانتهاء
    $now = time();
    if (!empty($foundKey['expires_at']) && (int)$foundKey['expires_at'] < $now) {
        echo json_encode(['success'=>false,'error'=>'Expired']); exit;
    }

    // فحص حد الأجهزة
    $maxDev = (int)($foundKey['max_devices'] ?? 1);
    $devices = $foundKey['devices'] ?? [];
    if (!is_array($devices)) $devices = [];

    $alreadyBound = in_array($deviceId, $devices, true);
    if (!$alreadyBound) {
        if (count($devices) >= $maxDev) {
            echo json_encode(['success'=>false,'error'=>'Device limit reached']); exit;
        }
        // ربط الجهاز
        $devices[] = $deviceId;
        $g[$foundType][$key]['devices'] = $devices;
        saveGistContent($g);
    }

    $_SESSION['csrf'] = bin2hex(random_bytes(32));

    echo json_encode(['success'=>true, 'data'=>[
        'role'           => $foundType === 'resellers' ? 'reseller' : 'key',
        'csrf'           => $_SESSION['csrf'],
        'telegram'       => $g['telegram'] ?? TELEGRAM_URL,
        'created_at'     => $foundKey['created_at']   ?? null,
        'expires_at'     => $foundKey['expires_at']   ?? null,
        'max_devices'    => $maxDev,
        'duration_days'  => $foundKey['duration_days'] ?? null,
    ]]);
    exit;
}

/* ============================================================
   3) جلب رابط الاشتراك (CSRF + XHR + Same-Origin فقط)
   ============================================================ */
if ($action === 'get_sub') {
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
        http_response_code(405); echo json_encode(['ok'=>false]); exit;
    }
    if (strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') !== 'xmlhttprequest') {
        http_response_code(403); echo json_encode(['ok'=>false]); exit;
    }
    $token = $input['csrf'] ?? '';
    if (empty($_SESSION['csrf']) || !hash_equals($_SESSION['csrf'], (string)$token)) {
        http_response_code(403); echo json_encode(['ok'=>false,'error'=>'csrf']); exit;
    }
    $host = $_SERVER['HTTP_HOST'] ?? '';
    $ref  = parse_url($_SERVER['HTTP_REFERER'] ?? '', PHP_URL_HOST) ?: '';
    if ($ref !== '' && $ref !== $host) {
        http_response_code(403); echo json_encode(['ok'=>false,'error'=>'origin']); exit;
    }

    echo json_encode(['ok'=>true, 'url'=>SECRET_SUB_URL]);
    exit;
}

http_response_code(400);
echo json_encode(['error'=>'unknown_action']);