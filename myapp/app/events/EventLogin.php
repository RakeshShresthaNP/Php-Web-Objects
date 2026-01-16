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

final class EventLogin
{

    private PDO $db;

    public function __construct(public string $username, public string $ip, public bool $isSuccess)
    {
        $this->db = db();
    }

    // The logic is now inside the Event class itself
    #[AsEventListener(self::class)]
    public function handle(): void
    {
        if ($this->isSuccess) {
            // Success: Reset the counter for this IP
            $this->db->prepare("DELETE FROM sys_login_attempts WHERE ip_address = ?")->execute([
                $this->ip
            ]);
            return;
        }

        // Failure: Record attempt
        $this->db->prepare("INSERT INTO sys_login_attempts (username, ip_address) VALUES (?, ?)")->execute([
            $this->username,
            $this->ip
        ]);

        // Check for block (5 fails in 10 mins)
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM sys_login_attempts WHERE ip_address = ? AND attempted_at > DATE_SUB(UTC_TIMESTAMP(), INTERVAL 10 MINUTE)");
        $stmt->execute([
            $this->ip
        ]);

        if ($stmt->fetchColumn() >= 5) {
            $this->db->prepare("INSERT IGNORE INTO sys_blocked_ips (ip_address, reason) VALUES (?, 'Brute force')")->execute([
                $this->ip
            ]);
        }
    }
}
