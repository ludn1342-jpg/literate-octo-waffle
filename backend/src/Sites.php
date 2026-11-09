<?php
declare(strict_types=1);

class Sites {
    private PDO $db;

    public function __construct(string $dbFile) {
        $isNew = !is_file($dbFile);
        $this->db = new PDO('sqlite:' . $dbFile);
        $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->db->exec('PRAGMA journal_mode = WAL');
        $this->db->exec('PRAGMA synchronous = NORMAL');
        $this->db->exec('PRAGMA busy_timeout = 2000');

        if ($isNew) {
            $schema = file_get_contents(__DIR__ . '/../schema.sql');
            $this->db->exec($schema);
        }
    }

    /** Devuelve el site o null */
    public function get(string $siteId): ?array {
        $st = $this->db->prepare('SELECT * FROM sites WHERE site_id = ? AND active = 1');
        $st->execute([$siteId]);
        return $st->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    /** Crea un site nuevo (lo llama el panel) */
    public function create(string $siteId, string $secret, string $white, string $money): void {
        $st = $this->db->prepare(
            'INSERT INTO sites (site_id, secret, white_url, money_url) VALUES (?, ?, ?, ?)'
        );
        $st->execute([$siteId, $secret, $white, $money]);
    }

    /** Actualiza URLs sin regenerar el index.php (las URLs no están en el archivo) */
    public function update(string $siteId, ?string $white = null, ?string $money = null): void {
        if ($white !== null) {
            $this->db->prepare('UPDATE sites SET white_url = ? WHERE site_id = ?')
                ->execute([$white, $siteId]);
        }
        if ($money !== null) {
            $this->db->prepare('UPDATE sites SET money_url = ? WHERE site_id = ?')
                ->execute([$money, $siteId]);
        }
    }

    /** Desactiva un site (el gateway devuelve 404) */
    public function deactivate(string $siteId): void {
        $this->db->prepare('UPDATE sites SET active = 0 WHERE site_id = ?')
            ->execute([$siteId]);
    }

    /** Lista todos (para el cron de warm) */
    public function all(): array {
        return $this->db->query('SELECT * FROM sites ORDER BY created_at DESC')
            ->fetchAll(PDO::FETCH_ASSOC);
    }

    /** Registra una decisión (best-effort, no rompe si falla) */
    public function log(
        string $siteId,
        string $ip,
        string $ua,
        string $action,
        string $reason,
        string $gclid
    ): void {
        try {
            $st = $this->db->prepare(
                'INSERT INTO logs (site_id, ip, ua, action, reason, gclid) VALUES (?, ?, ?, ?, ?, ?)'
            );
            $st->execute([
                $siteId,
                $ip,
                substr($ua, 0, 200),
                $action,
                $reason,
                substr($gclid, 0, 100),
            ]);
        } catch (Throwable $e) {
            // No interrumpir la respuesta por un fallo de log
        }
    }
}