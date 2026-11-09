<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require 'backend/src/Sites.php';

$ruta = __DIR__ . '/backend/storage/sites.db';
echo "Ruta a crear: " . $ruta . "<br><br>";

try {
    if (!is_dir(__DIR__ . '/backend/storage')) {
        mkdir(__DIR__ . '/backend/storage', 0777, true);
        echo "Carpeta storage creada.<br>";
    }
    
    $db = new Sites($ruta);
    echo "✅ Base de datos instanciada correctamente.<br>";
    
    if (file_exists($ruta)) {
        echo "✅ El archivo sites.db EXISTE.<br>";
    } else {
        echo "❌ El archivo sites.db NO existe.<br>";
    }
} catch (Throwable $e) {
    echo "❌ ERROR FATAL: " . $e->getMessage();
}