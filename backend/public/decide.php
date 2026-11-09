<?php
declare(strict_types=1);

$t0 = microtime(true);

require __DIR__ . '/../src/Sites.php';
require __DIR__ . '/../src/GoogleCheck.php';
require __DIR__ . '/../src/Proxy.php';
require __DIR__ . '/../src/Decision.php';
require __DIR__ . '/../src/Sign.php';

$cfg = require __DIR__ . '/../config.php';

// ---------- Auth por IP (opcional) ----------
if (!empty($cfg['allowed_ips'])) {
    $clientIp = $cfg['behind_cf']
        ? ($_SERVER['HTTP_CF_CONNECTING_IP'] ?? $_SERVER['REMOTE_ADDR'] ?? '')
        : ($_SERVER['REMOTE_ADDR'] ?? '');
    if (!in_array($clientIp, $cfg['allowed_ips'], true)) {
        http_response_code(403);
        exit('forbidden');
    }
}

// ---------- Leer headers ----------
$body   = file_get_contents('php://input');
$siteId = $_SERVER['HTTP_X_SITE'] ?? '';
$sig    = $_SERVER['HTTP_X_SIG']  ?? '';

if ($body === '' || $siteId === '' || $sig === '') {
    http_response_code(400);
    exit('missing params');
}

// ---------- Cargar site ----------
$sites = new Sites($cfg['db_file']);
$site  = $sites->get($siteId);
if (!$site) {
    http_response_code(404);
    exit('unknown site');
}

// ---------- Validar HMAC ----------
if (!Sign::verify($body, $site['secret'], $sig)) {
    http_response_code(403);
    exit('bad signature');
}

// ---------- Init ----------
GoogleCheck::load($cfg['ranges_file']);
Proxy::init(
    $cfg['cache_dir'],
    $cfg['cache_ttl'],
    $cfg['fetch_timeout_ms'],
    $cfg['fetch_connect_ms']
);

// ---------- Parse request ----------
$req = json_decode($body, true);
if (!is_array($req)) {
    http_response_code(400);
    exit('bad json');
}

// ---------- Decidir ----------
$decision = new Decision($cfg['fp_threshold']);
$result   = $decision->decide($req, $site);

// ---------- Fetch del contenido ----------
$url  = $result['action'] === 'money' ? $site['money_url'] : $site['white_url'];
$html = Proxy::fetch($url, true);
$html = Proxy::rewriteAssets($html);

// ---------- Log ----------
$sites->log(
    $siteId,
    $req['ip']    ?? '',
    $req['ua']    ?? '',
    $result['action'],
    $result['reason'],
    $req['gclid'] ?? ''
);

// ---------- Responder ----------
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('Server-Timing: total;dur=' . round((microtime(true) - $t0) * 1000));

echo json_encode([
    'action' => $result['action'],
    'reason' => $result['reason'],
    'html'   => $html,
    'ttl'    => $cfg['cache_ttl'],
]);