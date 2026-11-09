<?php
declare(strict_types=1);

class Decision {
    public function __construct(
        private float $fpThreshold = 0.65
    ) {}

    /**
     * Devuelve ['action' => 'white'|'money', 'reason' => string]
     * Reglas en orden de prioridad:
     *  1. Cookie cacheada válida → respetar
     *  2. Google confirmado → white
     *  3. UA de bot → white
     *  4. Datacenter → white
     *  5. Sin gclid → white
     *  6. Fingerprint bajo → white
     *  7. Todo OK → money
     */
    public function decide(array $req, array $site): array {
        // 1. Decisión cacheada (cookie firmada por el gateway)
        $forced = $req['forced'] ?? null;
        if (in_array($forced, ['white', 'money'], true)) {
            return ['action' => $forced, 'reason' => 'cookie_cached'];
        }

        $ip  = (string)($req['ip']    ?? '');
        $ua  = (string)($req['ua']    ?? '');
        $gcl = (string)($req['gclid'] ?? '');

        // 2. Google confirmado
        if (GoogleCheck::isGoogle($ip, $ua)) {
            return ['action' => 'white', 'reason' => 'google_confirmed'];
        }

        // 3. UA de bot
        if (preg_match('/(bot|crawl|spider|headless|selenium|puppeteer|playwright|phantom)/i', $ua)) {
            return ['action' => 'white', 'reason' => 'bot_ua'];
        }

        // 4. Datacenter
        if (GoogleCheck::isDatacenter($ip)) {
            return ['action' => 'white', 'reason' => 'datacenter'];
        }

        // 5. Sin click ID → white (tráfico orgánico no nos interesa)
        if ($gcl === '') {
            return ['action' => 'white', 'reason' => 'no_click_id'];
        }

        // 6. Fingerprint bajo
        $fp = (float)($req['fp'] ?? 0);
        if ($fp < $this->fpThreshold) {
            return ['action' => 'white', 'reason' => 'low_fp'];
        }

        // 7. Pasa todo → money
        return ['action' => 'money', 'reason' => 'passed'];
    }
}