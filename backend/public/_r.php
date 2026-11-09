<?php
declare(strict_types=1);

$cfg = require __DIR__ . '/../config.php';

// El gateway reescribe src="https://..." → src="/_r?u=base64(https://...)"
$enc = $_GET['u'] ?? '';
$url = base64_decode($enc, true);

if (!$url || !preg_match('#^https?://#i', $url)) {
    http_response_code(400);
    exit;
}

// Cache en disco por hash
$cacheDir = $cfg['cache_dir'] . '/assets';
@mkdir($cacheDir, 0750, true);

$key  = md5($url);
$file = $cacheDir . '/' . $key;
$meta = $cacheDir . '/' . $key . '.meta';

$ttl = 3600; // 1 hora

// Cache hit
if (is_file($file) && is_file($meta) && (time() - filemtime($file)) < $ttl) {
    $m = json_decode((string)file_get_contents($meta), true) ?: [];
    header('Content-Type: ' . ($m['type'] ?? 'application/octet-stream'));
    header('Cache-Control: public, max-age=3600');
    header('Content-Length: ' . filesize($file));
    readfile($file);
    exit;
}

// Cache miss → fetch
$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER    => true,
    CURLOPT_TIMEOUT_MS        => 3000,
    CURLOPT_CONNECTTIMEOUT_MS => 1000,
    CURLOPT_FOLLOWLOCATION    => true,
    CURLOPT_ENCODING          => 'gzip, br',
    CURLOPT_TCP_FASTOPEN      => true,
    CURLOPT_USERAGENT         => $_SERVER['HTTP_USER_AGENT'] ?? 'Mozilla/5.0',
]);
$body = curl_exec($ch);
$type = curl_getinfo($ch, CURLINFO_CONTENT_TYPE) ?: 'application/octet-stream';
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($body === false || $code >= 400 || $body === '') {
    http_response_code(404);
    exit;
}

// Guardar en cache
@file_put_contents($file, $body);
@file_put_contents($meta, json_encode(['type' => $type]));

header('Content-Type: ' . $type);
header('Cache-Control: public, max-age=3600');
header('Content-Length: ' . strlen($body));
echo $body;