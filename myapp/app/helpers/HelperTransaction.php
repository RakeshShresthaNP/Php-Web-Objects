<?php

/**
 # Copyright Rakesh Shrestha (rakesh.shrestha@gmail.com)
 # All rights reserved.

 Epoch: Starts Jan 1, 2026.

 Max Users: 230−1 (Over 1.07 Billion).

 Max Amount: 224−1 cents ($167,772.15).

 Max Time: 225−1 minutes (Approx. 63.8 Years).
 #
 # Redistribution and use in source and binary forms, with or without
 # modification, are permitted provided that the following conditions are
 # met:
 #
 # Redistributions must retain the above copyright notice.
 */
declare(strict_types = 1);

final class HelperTransaction
{

    private const CHARSET = "123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz";

    private const CUSTOM_EPOCH = 1767225600;

    private const SECRET_MASK = "10000000000000000000000000";

    public static function encode(int $userId, float $amount, int $currencyId, bool $secure = true): string
    {
        $minutes = (int) floor((time() - self::CUSTOM_EPOCH) / 60);
        $amtCents = (int) round($amount * 100);

        // Pack bits: Time(25) | User(30) | Amount(24) | Currency(8)
        $packed = gmp_init($minutes);
        $packed = gmp_mul($packed, gmp_pow(2, 30));
        $packed = gmp_add($packed, $userId);
        $packed = gmp_mul($packed, gmp_pow(2, 24));
        $packed = gmp_add($packed, $amtCents);
        $packed = gmp_mul($packed, gmp_pow(2, 8));
        $packed = gmp_add($packed, $currencyId);
        if ($secure)
            $packed = gmp_xor($packed, gmp_init(self::SECRET_MASK));
        $res = "";
        $base = gmp_init(60);
        while (gmp_cmp($packed, 0) > 0) {
            $rem = gmp_intval(gmp_mod($packed, $base));
            $res = self::CHARSET[$rem] . $res;
            $packed = gmp_div($packed, $base);
        }
        $dataStr = str_pad($res, 15, self::CHARSET[0], STR_PAD_LEFT);
        $sum = 0;
        for ($i = 0; $i < 15; $i ++) {
            $sum += strpos(self::CHARSET, $dataStr[$i]);
        }
        return $dataStr . self::CHARSET[$sum % 60];
    }

    public static function decode(string $id, bool $secure = true): ?array
    {
        if (strlen($id) !== 16)
            return null;
        $dataStr = substr($id, 0, 15);
        $sum = 0;
        for ($i = 0; $i < 15; $i ++) {
            $pos = strpos(self::CHARSET, $dataStr[$i]);
            if ($pos === false)
                return null;
            $sum += $pos;
        }
        if (self::CHARSET[$sum % 60] !== $id[15])
            return null;
        $num = gmp_init(0);
        for ($i = 0; $i < 15; $i ++) {
            $num = gmp_add(gmp_mul($num, 60), strpos(self::CHARSET, $dataStr[$i]));
        }
        if ($secure)
            $num = gmp_xor($num, gmp_init(self::SECRET_MASK));
        $currencyId = gmp_intval(gmp_mod($num, gmp_pow(2, 8)));
        $num = gmp_div($num, gmp_pow(2, 8));
        $amtCents = gmp_intval(gmp_mod($num, gmp_pow(2, 24)));
        $num = gmp_div($num, gmp_pow(2, 24));
        $userId = gmp_intval(gmp_mod($num, gmp_pow(2, 30)));
        $minutes = gmp_intval(gmp_div($num, gmp_pow(2, 30)));
        return [
            'userId' => $userId,
            'amount' => $amtCents / 100,
            'currencyId' => $currencyId,
            'date' => date('Y-m-d H:i', self::CUSTOM_EPOCH + ($minutes * 60))
        ];
    }
}
