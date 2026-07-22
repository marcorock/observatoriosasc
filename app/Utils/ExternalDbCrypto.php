<?php

if (!function_exists('externalDbCryptoKey')) {
    function externalDbCryptoKey(): string
    {
        $key = trim((string) ($_ENV['EXTERNAL_DB_CRYPT_KEY'] ?? ''));

        if ($key === '') {
            throw new RuntimeException('A chave EXTERNAL_DB_CRYPT_KEY nao foi configurada.');
        }

        return hash('sha256', $key, true);
    }
}

if (!function_exists('externalDbEncrypt')) {
    function externalDbEncrypt(string $plainText): string
    {
        $cipher = 'AES-256-CBC';
        $ivLength = openssl_cipher_iv_length($cipher);
        $iv = random_bytes($ivLength);
        $encrypted = openssl_encrypt($plainText, $cipher, externalDbCryptoKey(), OPENSSL_RAW_DATA, $iv);

        if ($encrypted === false) {
            throw new RuntimeException('Nao foi possivel criptografar a senha da base externa.');
        }

        return base64_encode($iv . $encrypted);
    }
}

if (!function_exists('externalDbDecrypt')) {
    function externalDbDecrypt(string $encryptedValue): string
    {
        $cipher = 'AES-256-CBC';
        $decoded = base64_decode($encryptedValue, true);

        if ($decoded === false) {
            throw new RuntimeException('Senha criptografada invalida.');
        }

        $ivLength = openssl_cipher_iv_length($cipher);
        $iv = substr($decoded, 0, $ivLength);
        $payload = substr($decoded, $ivLength);
        $decrypted = openssl_decrypt($payload, $cipher, externalDbCryptoKey(), OPENSSL_RAW_DATA, $iv);

        if ($decrypted === false) {
            throw new RuntimeException('Nao foi possivel descriptografar a senha da base externa.');
        }

        return $decrypted;
    }
}
