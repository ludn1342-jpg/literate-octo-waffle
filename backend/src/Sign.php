<?php
declare(strict_types=1);

class Sign {
    /**
     * Verifica que el HMAC del body coincida con la firma enviada por el gateway.
     * El secreto lo conoce solo el gateway (embebido en su index.php) y el backend (en BD).
     */
    public static function verify(string $body, string $secret, string $sig): bool {
        if ($secret === '' || $sig === '') return false;
        $expected = hash_hmac('sha256', $body, $secret);
        return hash_equals($expected, $sig);
    }
}