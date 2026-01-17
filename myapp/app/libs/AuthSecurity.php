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

final class AuthSecurity
{

    public static function verifyAndUpgrade(string $password, string $storedHash, int $userId): bool
    {
        $db = db();

        $isValid = false;
        $shouldRehash = false;

        // 1. Detect Legacy MD5 (Length 32)
        if (strlen($storedHash) === 32 && ! str_contains($storedHash, '$')) {
            if (hash_equals($storedHash, md5($password))) {
                $isValid = true;
                $shouldRehash = true;
            }
        } // 2. Handle Modern Hash (password_hash)
        else {
            if (password_verify($password, $storedHash)) {
                $isValid = true;
                // Check if hash settings (cost/algo) have changed
                if (password_needs_rehash($storedHash, PASSWORD_DEFAULT)) {
                    $shouldRehash = true;
                }
            }
        }

        // 3. Perform the Silent Upgrade
        if ($isValid && $shouldRehash) {
            $newSecureHash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $db->prepare("UPDATE mst_users SET password = ? WHERE id = ?");
            $stmt->execute([
                $newSecureHash,
                $userId
            ]);
        }

        return $isValid;
    }
}
