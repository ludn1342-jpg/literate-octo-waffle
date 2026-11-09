<?php
declare(strict_types=1);

return [
    // Ruta absoluta a la base de datos SQLite (se crea sola si no existe)
    'db_file' => __DIR__ . '/storage/sites.db',

    // Directorio de cache de HTML y assets
    'cache_dir' => __DIR__ . '/storage/cache',

    // TTL del cache HTML en segundos (5 minutos)
    'cache_ttl' => 300,

    // Archivo con los rangos IP de Google (lo actualiza cron/update_google.php)
    'ranges_file' => __DIR__ . '/storage/google_ranges.json',

    // Umbral de fingerprint (0.0 = bot, 1.0 = humano)
    'fp_threshold' => 0.65,

    // IPs permitidas para llamar al backend (whitelist)
    // Vacío = deshabilitar (útil en local)
    // En prod: pon las IPs públicas de tus gateways
    'allowed_ips' => [],

    // Si usas Cloudflare, leer CF-Connecting-IP en lugar de REMOTE_ADDR
    // En Render NO usamos Cloudflare, por eso es false.
    'behind_cf' => false,

    // Timeouts del fetch remoto (milisegundos)
    'fetch_timeout_ms' => 3000,
    'fetch_connect_ms' => 1500,

    // Clave admin para el endpoint admin_create.php (opcional)
    // Cámbiala por algo largo y aleatorio
    'admin_key' => 'k9Xm2pQv7Lw4Nz8RtY6Bc3Hj5Fd1Gs0Ae',
];
