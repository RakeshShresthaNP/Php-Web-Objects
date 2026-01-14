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

final class AES
{

    public static function encrypt(mixed $value, string $password, int $hashIterations = 100000): string
    {
        $iv = openssl_random_pseudo_bytes(16);
        $salt = openssl_random_pseudo_bytes(16);
        $key = hash_pbkdf2("sha256", $password, $salt, $hashIterations, 64, true);
        $cipher = openssl_encrypt(json_encode($value), 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv);
        return str_pad($hashIterations, 10, "0", STR_PAD_LEFT) . bin2hex($iv) . bin2hex($salt) . bin2hex($cipher);
    }

    public static function decrypt(string $encryptedValue, string $password): mixed
    {
        return json_decode(openssl_decrypt(hex2bin(substr($encryptedValue, 74)), 'aes-256-cbc', hash_pbkdf2("sha256", $password, hex2bin(substr($encryptedValue, 42, 32)), (int) substr($encryptedValue, 0, 10), 64, true), OPENSSL_RAW_DATA, hex2bin(substr($encryptedValue, 10, 32))), true);
    }
}
