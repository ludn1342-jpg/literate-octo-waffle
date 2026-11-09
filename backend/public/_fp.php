<?php
declare(strict_types=1);

require __DIR__ . '/../src/Fingerprint.php';

$body = json_decode(file_get_contents('php://input'), true) ?: [];
$score = Fingerprint::score($body);

setcookie('_fps', (string)$score, [
    'expires'  => time() + 1800,
    'path'     => '/',
    'secure'   => !empty($_SERVER['HTTPS']),
    'httponly' => true,
    'samesite' => 'Lax',
]);

header('Content-Type: application/json');
echo json_encode(['score' => $score]);