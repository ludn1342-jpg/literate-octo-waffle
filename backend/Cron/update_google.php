<?php
// Cron: 0 3 * * * php /ruta/backend/cron/update_google.php
declare(strict_types=1);

$cfg = require __DIR__ . '/../config.php';

$url = 'https://developers.google.com/static/search/apis/ipranges/googlebot.json';
$ctx = stream_context_create(['http' => ['timeout' => 10]]);
$json = @file_get_contents($url, false, $ctx);

if ($json === false) {
    fwrite(STDERR, "No se pudo descargar rangos de Google\n");
    exit(1);
}

$data = json_decode($json, true);
$prefixes = $data['prefixes'] ?? [];
$out = [];

foreach ($prefixes as $p) {
    if (!empty($p['ipv4Prefix'])) {
        $out[] = $p['ipv4Prefix'];
    }
    // IPv6 omitido: ip2long no lo soporta
}

// Guardar atómico
$tmp = $cfg['ranges_file'] . '.tmp';
file_put_contents($tmp, json_encode($out, JSON_PRETTY_PRINT));
rename($tmp, $cfg['ranges_file']);

echo count($out) . " rangos de Google actualizados\n";