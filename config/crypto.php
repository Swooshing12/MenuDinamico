<?php
// config/crypto.php

// Cifrado simétrico
define('CIPHER', 'aes-256-cbc');

// Tu clave secreta: debe mantenerse fuera de tu repositorio
define('SECRET_KEY', 'tu_clave_secreta_muy_segura');

// Encripta un identificador (p. ej. id_submenu) usando IV aleatorio
function encrypt_id(string $id): string {
    // Derivamos la clave a 32 bytes
    $key   = hash('sha256', SECRET_KEY, true);
    // Longitud del IV para el cifrado
    $ivlen = openssl_cipher_iv_length(CIPHER);
    // Generar IV aleatorio
    $iv    = openssl_random_pseudo_bytes($ivlen);
    // Cifrar en RAW
    $raw   = openssl_encrypt($id, CIPHER, $key, OPENSSL_RAW_DATA, $iv);
    // Concatena IV + texto cifrado y base64-encode
    $combined = $iv . $raw;
    // URL-safe: +/= → -_, 
    return strtr(base64_encode($combined), '+/=', '-_,');
}

// Desencripta el identificador leyendo primero el IV
function decrypt_id(string $hash): string {
    // Derivamos la misma clave
    $key   = hash('sha256', SECRET_KEY, true);
    // Revertir URL-safe y base64-decode
    $combined = base64_decode(strtr($hash, '-_,', '+/='));
    // Extraer IV y el texto cifrado
    $ivlen   = openssl_cipher_iv_length(CIPHER);
    $iv      = substr($combined, 0, $ivlen);
    $raw     = substr($combined, $ivlen);
    // Desencriptar
    $decr    = openssl_decrypt($raw, CIPHER, $key, OPENSSL_RAW_DATA, $iv);
    return $decr === false ? '' : $decr;
}
