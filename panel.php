<?php
declare(strict_types=1);

// ============================================================
// CONFIG — Rutas ajustadas para XAMPP
// ============================================================
const BACKEND_DB      = __DIR__ . '/backend/storage/sites.db';
const BACKEND_URL     = 'http://localhost/cloaker/backend/public'; // <-- Ruta local de XAMPP
const GATEWAY_TEMPLATE = __DIR__ . '/gateway-template.php';

// ============================================================
// HANDLER: generar y descargar
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $white = trim($_POST['white'] ?? '');
        $money = trim($_POST['money'] ?? '');

        if (!$white || !$money) {
            throw new Exception('Faltan URLs');
        }
        foreach ([$white, $money] as $u) {
            if (!filter_var($u, FILTER_VALIDATE_URL)) {
                throw new Exception("URL inválida: $u");
            }
        }
        if ($white === $money) {
            throw new Exception('White y Money deben ser distintas');
        }
        if (!is_file(BACKEND_DB)) {
            throw new Exception('No se encuentra la BD del backend. Asegúrate de haber abierto decide.php al menos una vez para crearla: ' . BACKEND_DB);
        }
        if (!is_file(GATEWAY_TEMPLATE)) {
            throw new Exception('No se encuentra la plantilla: ' . GATEWAY_TEMPLATE);
        }

        // 1. Generar credenciales
        $siteId = bin2hex(random_bytes(8));   // 16 hex chars
        $secret = bin2hex(random_bytes(32));  // 64 hex chars

        // 2. Insertar en la BD del backend
        $pdo = new PDO('sqlite:' . BACKEND_DB);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->prepare(
            'INSERT INTO sites (site_id, secret, white_url, money_url) VALUES (?,?,?,?)'
        )->execute([$siteId, $secret, $white, $money]);

        // 3. Cargar plantilla y reemplazar placeholders
        $code = file_get_contents(GATEWAY_TEMPLATE);
        $code = str_replace(
            ['{{SITE_ID}}', '{{SITE_SECRET}}', '{{BACKEND_URL}}'],
            [$siteId, $secret, rtrim(BACKEND_URL, '/')],
            $code
        );

        // 4. Enviar como descarga directa
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="index.php"');
        header('Content-Length: ' . strlen($code));
        header('Cache-Control: no-store');
        header('X-Site-Id: ' . $siteId);
        echo $code;
        exit;

    } catch (Throwable $e) {
        $error = $e->getMessage();
    }
}
?><!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Cloaker Generator</title>
<style>
* { box-sizing: border-box; }
body {
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
    background: #0d1117;
    color: #e6edf3;
    margin: 0;
    padding: 40px 20px;
    min-height: 100vh;
}
.wrap { max-width: 640px; margin: 0 auto; }
h1 {
    font-size: 24px;
    margin: 0 0 8px;
    display: flex;
    align-items: center;
    gap: 10px;
}
.subtitle {
    color: #8b949e;
    font-size: 14px;
    margin: 0 0 32px;
}
.card {
    background: #161b22;
    border: 1px solid #30363d;
    border-radius: 12px;
    padding: 28px;
}
label {
    display: block;
    font-size: 13px;
    color: #8b949e;
    margin-bottom: 6px;
    font-weight: 500;
}
label small {
    color: #6e7681;
    font-weight: 400;
    display: block;
    margin-top: 2px;
}
input[type=url] {
    width: 100%;
    padding: 12px 14px;
    background: #0d1117;
    border: 1px solid #30363d;
    border-radius: 8px;
    color: #e6edf3;
    font-family: ui-monospace, 'SF Mono', Consolas, monospace;
    font-size: 14px;
    margin-bottom: 20px;
    transition: border-color 0.15s;
}
input[type=url]:focus {
    outline: none;
    border-color: #58a6ff;
}
button {
    width: 100%;
    padding: 14px;
    background: #238636;
    color: #fff;
    border: 0;
    border-radius: 8px;
    font-size: 15px;
    font-weight: 600;
    cursor: pointer;
    transition: background 0.15s;
}
button:hover:not(:disabled) { background: #2ea043; }
button:disabled { background: #1f6feb44; cursor: wait; }
.error {
    background: #f8514915;
    border: 1px solid #f85149;
    color: #f85149;
    padding: 12px 16px;
    border-radius: 8px;
    margin-bottom: 20px;
    font-size: 13px;
}
.info {
    background: #1f6feb15;
    border: 1px solid #1f6feb;
    color: #79c0ff;
    padding: 12px 16px;
    border-radius: 8px;
    margin-top: 20px;
    font-size: 13px;
    line-height: 1.5;
}
.info code {
    background: #0d1117;
    padding: 2px 6px;
    border-radius: 4px;
    font-family: ui-monospace, monospace;
    font-size: 12px;
}
.hint {
    color: #6e7681;
    font-size: 12px;
    margin-top: -16px;
    margin-bottom: 20px;
}
</style>
</head>
<body>
<div class="wrap">

<h1>🕵️ Cloaker Generator</h1>
<p class="subtitle">Genera el <code>index.php</code> para tu landing. Las URLs viven en el backend, no en el archivo.</p>

<?php if (!empty($error)): ?>
    <div class="error">❌ <?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<div class="card">
    <form method="post">
        <label>
            White Page URL
            <small>Lo que verá Google (contenido aprobable)</small>
        </label>
        <input
            type="url"
            name="white"
            placeholder="https://mi-white-page.com"
            required
            value="<?= htmlspecialchars($_POST['white'] ?? '') ?>"
        >

        <label>
            Money Page URL
            <small>Lo que verá el visitante real (tu oferta)</small>
        </label>
        <input
            type="url"
            name="money"
            placeholder="https://mi-offer-page.com"
            required
            value="<?= htmlspecialchars($_POST['money'] ?? '') ?>"
        >

        <button type="submit" id="btn">
            ⚡ Generar y descargar index.php
        </button>
    </form>

    <div class="info">
        <strong>📦 Cómo usar:</strong><br>
        1. Pega las dos URLs arriba<br>
        2. Click en generar → se descarga <code>index.php</code><br>
        3. Sube <code>index.php</code> a la raíz de tu dominio de landing<br>
        4. Listo. El archivo no contiene URLs — solo un ID que consulta al backend
    </div>
</div>

</div>
<script>
document.querySelector('form').addEventListener('submit', e => {
    const btn = document.getElementById('btn');
    btn.disabled = true;
    btn.textContent = '⏳ Generando...';
});
</script>
</body>
</html>