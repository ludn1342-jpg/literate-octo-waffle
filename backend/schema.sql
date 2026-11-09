-- Tabla principal: cada site es un gateway generado desde el panel
CREATE TABLE IF NOT EXISTS sites (
    site_id      TEXT PRIMARY KEY,
    secret       TEXT NOT NULL,
    white_url    TEXT NOT NULL,
    money_url    TEXT NOT NULL,
    active       INTEGER NOT NULL DEFAULT 1,
    created_at   TEXT NOT NULL DEFAULT (datetime('now'))
);

CREATE INDEX IF NOT EXISTS idx_sites_active ON sites(active);

-- Logs de cada decisión (útil para auditar y para tu presentación)
CREATE TABLE IF NOT EXISTS logs (
    id          INTEGER PRIMARY KEY AUTOINCREMENT,
    created_at  TEXT NOT NULL DEFAULT (datetime('now')),
    site_id     TEXT NOT NULL,
    ip          TEXT,
    ua          TEXT,
    action      TEXT,
    reason      TEXT,
    gclid       TEXT
);

CREATE INDEX IF NOT EXISTS idx_logs_site ON logs(site_id, created_at);