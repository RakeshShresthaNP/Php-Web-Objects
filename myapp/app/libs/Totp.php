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

final class Totp
{

    public static function generate(string $secret, int $digits = 6, int $period = 40, ?int $timestamp = null): string
    {
        if (empty($secret)) {
            throw new ApiException('The secret key cannot be empty.', 401);
        }

        if ($timestamp === null) {
            $timestamp = time();
        }

        $counter = floor($timestamp / $period);
        $binary_counter = pack('N*', 0) . pack('N*', $counter);
        $hash = hash_hmac('sha1', $binary_counter, $secret, true);
        $offset = ord($hash[19]) & 0xf;
        $truncated_hash = (((ord($hash[$offset]) & 0x7f) << 24) | ((ord($hash[$offset + 1]) & 0xff) << 16) | ((ord($hash[$offset + 2]) & 0xff) << 8) | (ord($hash[$offset + 3]) & 0xff));
        $otp = $truncated_hash % pow(10, $digits);
        return str_pad((string) $otp, $digits, '0', STR_PAD_LEFT);
    }

    public static function verify(string $input, string $secret, int $digits = 6, int $period = 40, int $window = 1): bool
    {
        $timestamp = time();

        for ($i = - $window; $i <= $window; $i ++) {
            $checkTime = $timestamp + ($i * $period);
            $generated = self::generate($secret, $digits, $period, $checkTime);

            if (hash_equals($generated, $input)) {
                return true;
            }
        }

        return false;
    }
}
