<?php

declare(strict_types=1);

namespace App\Service;

final readonly class SecretCipher
{
    public function __construct(
        private string $appSecret,
    ) {
    }

    public function encrypt(string $plainText): string
    {
        $iv = random_bytes(12);
        $tag = '';
        $cipherText = openssl_encrypt(
            $plainText,
            'aes-256-gcm',
            $this->key(),
            OPENSSL_RAW_DATA,
            $iv,
            $tag,
        );

        if ($cipherText === false) {
            throw new \RuntimeException('Impossible de chiffrer le secret mailer.');
        }

        return base64_encode($iv.$tag.$cipherText);
    }

    public function decrypt(string $encryptedText): string
    {
        $payload = base64_decode($encryptedText, true);

        if ($payload === false || strlen($payload) <= 28) {
            throw new \RuntimeException('Secret mailer invalide.');
        }

        $iv = substr($payload, 0, 12);
        $tag = substr($payload, 12, 16);
        $cipherText = substr($payload, 28);
        $plainText = openssl_decrypt(
            $cipherText,
            'aes-256-gcm',
            $this->key(),
            OPENSSL_RAW_DATA,
            $iv,
            $tag,
        );

        if ($plainText === false) {
            throw new \RuntimeException('Impossible de dechiffrer le secret mailer.');
        }

        return $plainText;
    }

    private function key(): string
    {
        return hash('sha256', $this->appSecret, true);
    }
}
