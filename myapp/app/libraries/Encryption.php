<?php

/**
 # Copyright Rakesh Shrestha (rakesh.shrestha@gmail.com)
 # All rights reserved.
 #
 # Redistribution and use in source and binary forms, with or without
 # modification, are permitted provided that the following conditions are
 # met:
 #
 # Redistributions must retain the above copyright notice.
 */
declare(strict_types = 1);

final class Encryption
{

    private string $_skey = "ThisIsCOOL";

    private const CIPHER_METHOD = 'AES-256-CBC';

    private const IV_LENGTH = 16;

    public function encode(string $value): string
    {
        if (! $value) {
            return '';
        }
        $text = $value;

        // Generate a random IV
        $iv = openssl_random_pseudo_bytes(self::IV_LENGTH);

        // Encrypt using OpenSSL
        $crypttext = openssl_encrypt($text, self::CIPHER_METHOD, $this->_skey, OPENSSL_RAW_DATA, $iv);

        // Combine IV and encrypted text, then encode
        $encrypted = base64_encode($iv . $crypttext);

        return mb_trim(base64_url_encode($encrypted));
    }

    public function decode(string $value): string
    {
        if (! $value) {
            return '';
        }
        $encrypted = base64_url_decode($value);
        $data = base64_decode($encrypted);

        // Extract IV and encrypted text
        $iv = substr($data, 0, self::IV_LENGTH);
        $crypttext = substr($data, self::IV_LENGTH);

        // Decrypt using OpenSSL
        $decrypttext = openssl_decrypt($crypttext, self::CIPHER_METHOD, $this->_skey, OPENSSL_RAW_DATA, $iv);

        return mb_trim($decrypttext);
    }
}
