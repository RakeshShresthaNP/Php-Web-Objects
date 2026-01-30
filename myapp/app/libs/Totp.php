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

// Totp.php
final class Totp
{

    private static function base32_decode(string $base32): string
    {
        $base32chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $base32charsFlipped = array_flip(str_split($base32chars));
        $output = '';
        $v = 0;
        $vbits = 0;

        foreach (str_split(strtoupper($base32)) as $char) {
            if (! isset($base32charsFlipped[$char]))
                continue;
            $v = ($v << 5) | $base32charsFlipped[$char];
            $vbits += 5;
            if ($vbits >= 8) {
                $vbits -= 8;
                $output .= chr(($v >> $vbits) & 255);
            }
        }
        return $output;
    }

    public static function generate(string $secret, int $digits = 6, int $period = 30, ?int $timestamp = null): string
    {
        if (empty($secret)) {
            throw new ApiException('The secret key cannot be empty.', 401);
        }

        // CRITICAL FIX: Decode the Base32 secret
        $binary_secret = self::base32_decode($secret);

        if ($timestamp === null) {
            $timestamp = time();
        }

        $counter = floor($timestamp / $period);
        $binary_counter = pack('N*', 0) . pack('N*', $counter);

        // Use the binary secret here
        $hash = hash_hmac('sha1', $binary_counter, $binary_secret, true);

        $offset = ord($hash[19]) & 0xf;
        $truncated_hash = (((ord($hash[$offset]) & 0x7f) << 24) | ((ord($hash[$offset + 1]) & 0xff) << 16) | ((ord($hash[$offset + 2]) & 0xff) << 8) | (ord($hash[$offset + 3]) & 0xff));
        $otp = $truncated_hash % pow(10, $digits);
        return str_pad((string) $otp, $digits, '0', STR_PAD_LEFT);
    }

    // Change period default from 40 to 30
    // Totp.php
    // Totp.php
    public static function verify(string $input, string $secret, int $digits = 6, int $period = 30, int $window = 1): bool
    {
        $timestamp = time();
        $input = trim($input); // Remove invisible spaces

        for ($i = - $window; $i <= $window; $i ++) {
            $checkTime = $timestamp + ($i * $period);
            $generated = self::generate($secret, $digits, $period, $checkTime);

            if (hash_equals($generated, $input)) {
                writeLog('test', "MATCH FOUND at window $i!");
                writeLog('test', "--- END TOTP DEBUG (TRUE) ---");
                return true;
            }
        }

        return false;
    }
}


