<?php
declare(strict_types=1);

class Proxy {
    private static string $cacheDir = '';
    private static int $ttl        = 300;
    private static int $timeoutMs  = 2000;
    private static int $connectMs  = 800;

    public static function init(
        string $cacheDir,
        int $ttl,
        int $timeoutMs,
        int $connectMs
    ): void {
        self::$cacheDir  = $cacheDir;
        self::$ttl       = $ttl;
        self::$timeoutMs = $timeoutMs;
        self::$connectMs = $connectMs;
        @mkdir($cacheDir, 0750, true);
    }

    /**
     * Fetch con cache de disco.
     * - Cache hit → devuelve instantáneo
     * - Cache miss → cURL con timeout
     * - Fallo → devuelve cache stale (aunque expirado) o ''
     */
    public static function fetch(string $url, bool $useCache = true): string {
        $key  = md5($url);
        $file = self::$cacheDir . '/' . $key . '.html';

        if ($useCache && is_file($file) && (time() - filemtime($file)) < self::$ttl) {
            $html = file_get_contents($file);
            if ($html !== false && $html !== '') return $html;
        }

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER    => true,
            CURLOPT_TIMEOUT_MS        => self::$timeoutMs,
            CURLOPT_CONNECTTIMEOUT_MS => self::$connectMs,
            CURLOPT_FOLLOWLOCATION    => true,
            CURLOPT_MAXREDIRS         => 3,
            CURLOPT_ENCODING          => 'gzip, br',
            CURLOPT_TCP_FASTOPEN      => true,
            CURLOPT_HTTP_VERSION      => CURL_HTTP_VERSION_2_0,
            CURLOPT_USERAGENT         => $_SERVER['HTTP_USER_AGENT'] ?? 'Mozilla/5.0',
            CURLOPT_HTTPHEADER        => [
                'Accept-Language: ' . ($_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? 'en-US,en;q=0.9'),
                'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            ],
        ]);
        $html = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        // Fallo → intentar cache stale
        if ($html === false || $html === '' || $code >= 400) {
            return is_file($file) ? (string)file_get_contents($file) : '';
        }

        // Guardar atómico
        $tmp = $file . '.tmp.' . getmypid();
        if (@file_put_contents($tmp, $html) !== false) {
            @rename($tmp, $file);
        }

        return $html;
    }

    /**
     * Reescribe URLs absolutas y relativas del HTML para que pasen por el backend.
     * El visitante nunca ve la URL real del offer/white page.
     */
    public static function rewriteAssets(string $html): string {
        if ($html === '') return '';

        // 1. Absolutas: src="https://..." → src="/_r?u=base64"
        $html = preg_replace_callback(
            '#(src|href|action)=(["\'])(https?://[^"\']+)\2#i',
            function ($m) {
                return $m[1] . '=' . $m[2]
                     . '/_r?u=' . urlencode(base64_encode($m[3]))
                     . $m[2];
            },
            $html
        );

        // 2. Protocol-relative: src="//cdn..." → src="/_r?u=base64(https://cdn...)"
        $html = preg_replace_callback(
            '#(src|href|action)=(["\'])(//[^"\']+)\2#i',
            function ($m) {
                return $m[1] . '=' . $m[2]
                     . '/_r?u=' . urlencode(base64_encode('https:' . $m[3]))
                     . $m[2];
            },
            $html
        );

        return $html;
    }

    /** Elimina fragmento (#...) de una URL, conserva query */
    public static function cleanUrl(string $url): string {
        $p = parse_url($url);
        if ($p === false) return $url;
        $out = ($p['scheme'] ?? 'https') . '://' . ($p['host'] ?? '');
        if (!empty($p['port'])) $out .= ':' . $p['port'];
        if (!empty($p['path'])) $out .= $p['path'];
        if (!empty($p['query'])) $out .= '?' . $p['query'];
        return $out;
    }
}