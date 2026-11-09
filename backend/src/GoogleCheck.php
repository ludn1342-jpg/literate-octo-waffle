<?php
declare(strict_types=1);

class GoogleCheck {
    private static ?array $ranges = null;

    private static array $uaPatterns = [
        'AdsBot-Google',
        'AdsBot-Google-Mobile',
        'AdsBot-Google-Mobile-Apps',
        'Googlebot',
        'Googlebot-Image',
        'Googlebot-News',
        'Googlebot-Video',
        'Google-InspectionTool',
        'Google-Ads-Quality-Score',
        'Mediapartners-Google',
        'FeedFetcher-Google',
        'APIs-Google',
        'Google-Safety',
        'Storebot-Google',
        'GoogleOther',
        'Google-CloudVertexBot',
        'Google-Favicon',
        'Google-PageRenderer',
        'Google-Site-Verification',
        'Google-Read-Aloud',
    ];

    /** Carga la lista de rangos IP desde archivo (lazy) */
    public static function load(string $file): void {
        if (self::$ranges === null) {
            self::$ranges = is_file($file)
                ? (json_decode(file_get_contents($file), true) ?: [])
                : [];
        }
    }

    /**
     * Detección triple:
     *  1. UA contiene patrón de Google (rápido, falsificable)
     *  2. IP está en rango oficial de Google
     *  3. Reverse DNS + forward DNS confirman la identidad (anti-spoof)
     */
    public static function isGoogle(string $ip, string $ua): bool {
        // 1. UA
        foreach (self::$uaPatterns as $p) {
            if (stripos($ua, $p) !== false) {
                return true;
            }
        }

        // 2. Rango IP
        $long = ip2long($ip);
        if ($long === false) return false;

        foreach (self::$ranges ?? [] as $cidr) {
            [$net, $mask] = explode('/', $cidr . '/32');
            $netLong = ip2long($net);
            if ($netLong === false) continue;
            $mask = (int)$mask;
            $m = -1 << (32 - $mask);
            if (($long & $m) === ($netLong & $m)) {
                // 3. Reverse DNS + forward DNS
                $host = @gethostbyaddr($ip);
                if ($host
                    && preg_match('/\.(googlebot|google)\.com$/i', $host)
                    && @gethostbyname($host) === $ip
                ) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Detección heurística de datacenter / hosting.
     * Los verificadores de Google Ads suelen venir de GCP/AWS.
     * Lista mínima por prefijo IPv4 (sin GeoIP DB).
     */
    public static function isDatacenter(string $ip): bool {
        $knownPrefixes = [
            // AWS
            '3.', '13.', '15.', '18.', '52.', '54.',
            // GCP
            '34.', '35.',
            // Azure
            '20.', '40.', '13.',
            // DigitalOcean
            '104.131.', '104.236.', '138.197.', '159.203.', '167.99.', '174.138.',
            // Hetzner
            '5.9.', '78.46.', '88.99.', '116.202.', '136.243.',
            // Vultr
            '45.32.', '45.63.', '95.179.', '108.61.', '149.28.',
            // Linode
            '45.33.', '45.56.', '45.79.', '96.126.', '139.162.',
            // OVH
            '51.38.', '51.68.', '51.75.', '51.77.', '51.79.', '51.83.',
            // Contabo
            '5.189.', '144.91.', '173.212.', '207.180.',
        ];
        foreach ($knownPrefixes as $prefix) {
            if (str_starts_with($ip, $prefix)) {
                return true;
            }
        }
        return false;
    }
}