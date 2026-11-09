<?php
// Cron: */2 * * * * php /ruta/backend/cron/warm.php
declare(strict_types=1);

require __DIR__ . '/../src/Sites.php';
require __DIR__ . '/../src/Proxy.php';

$cfg = require __DIR__ . '/../config.php';

Proxy::init(
    $cfg['cache_dir'],
    $cfg['cache_ttl'],
    $cfg['fetch_timeout_ms'],
    $cfg['fetch_connect_ms']
);

$sites = new Sites($cfg['db_file']);
$count = 0;

foreach ($sites->all() as $s) {
    if (empty($s['active'])) continue;
    Proxy::fetch($s['white_url'], true);
    Proxy::fetch($s['money_url'], true);
    echo "✓ warm: {$s['site_id']}\n";
    $count++;
}

echo "Warmed $count sites\n";