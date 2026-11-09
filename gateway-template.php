<?php
declare(strict_types=1);

const SITE_ID     = '{{SITE_ID}}';
const SITE_SECRET = '{{SITE_SECRET}}';
const BACKEND     = '{{BACKEND_URL}}';
const HARD_TIMEOUT_MS = 3500;

$t0 = microtime(true);

$ip  = $_SERVER['HTTP_CF_CONNECTING_IP'] ?? $_SERVER['REMOTE_ADDR'] ?? '';
$ua  = $_SERVER['HTTP_USER_AGENT'] ?? '';
$gcl = $_GET['gclid'] ?? $_GET['wbraid'] ?? $_GET['gbraid'] ?? '';

// Cookie de decisión cacheada (2da visita = instantánea)
$forced = null;
$ck = '_d_' . SITE_ID;
if (!empty($_COOKIE[$ck])) {
    [$a, $e, $s] = explode('|', $_COOKIE[$ck] . '||');
    if (hash_equals(hash_hmac('sha256', "$a|$e", SITE_SECRET), $s) && (int)$e > time()) {
        $forced = $a;
    }
}

$payload = json_encode([
    'site'    => SITE_ID,
    'ip'      => $ip,
    'ua'      => $ua,
    'gclid'   => $gcl,
    'ref'     => $_SERVER['HTTP_REFERER'] ?? '',
    'lang'    => $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '',
    'ch_ua'   => $_SERVER['HTTP_SEC_CH_UA'] ?? '',
    'ch_mob'  => $_SERVER['HTTP_SEC_CH_UA_MOBILE'] ?? '',
    'ch_plat' => $_SERVER['HTTP_SEC_CH_UA_PLATFORM'] ?? '',
    'fp'      => (float)($_COOKIE['_fps'] ?? 0),
    'forced'  => $forced,
]);

$remainingMs = HARD_TIMEOUT_MS - (int)((microtime(true) - $t0) * 1000);
if ($remainingMs < 500) $remainingMs = 500;

$ch = curl_init(BACKEND . '/decide');
curl_setopt_array($ch, [
    CURLOPT_POST              => true,
    CURLOPT_POSTFIELDS        => $payload,
    CURLOPT_RETURNTRANSFER    => true,
    CURLOPT_TIMEOUT_MS        => $remainingMs,
    CURLOPT_CONNECTTIMEOUT_MS => min(1000, $remainingMs),
    CURLOPT_TCP_FASTOPEN      => true,
    CURLOPT_HTTP_VERSION      => CURL_HTTP_VERSION_2_0,
    CURLOPT_ENCODING          => 'gzip, br',
    CURLOPT_HTTPHEADER        => [
        'Content-Type: application/json',
        'X-Site: ' . SITE_ID,
        'X-Sig: '  . hash_hmac('sha256', $payload, SITE_SECRET),
    ],
]);
$resp = curl_exec($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($code !== 200 || !$resp) {
    http_response_code(200);
    header('Content-Type: text/html; charset=utf-8');
    echo '<!doctype html><html><body><script>setTimeout(()=>location.reload(),1500)</script></body></html>';
    exit;
}

$d = json_decode($resp, true) ?: [];

// Cookie de decisión (30 min)
if (!empty($d['action']) && in_array($d['action'], ['white','money'], true)) {
    $exp = time() + 1800;
    $sig = hash_hmac('sha256', "{$d['action']}|$exp", SITE_SECRET);
    setcookie($ck, "{$d['action']}|$exp|$sig", [
        'expires'  => $exp,
        'path'     => '/',
        'secure'   => !empty($_SERVER['HTTPS']),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
}

header('Content-Type: text/html; charset=utf-8');
header('Cache-Control: no-store, max-age=0');
header('X-Robots-Tag: noindex, nofollow');
header('Referrer-Policy: no-referrer');
header('Server-Timing: total;dur=' . round((microtime(true) - $t0) * 1000));
echo $d['html'] ?? '';
