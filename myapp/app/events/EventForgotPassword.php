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

class EventForgotPassword
{

    private PDO $db;

    public function __construct(public readonly string $username, public readonly string $ip, public readonly bool $isSuccess, public readonly ?object $user = null, // Null if user not found
    public readonly ?object $partner = null) // Null if user not found
    {
        $this->db = db();
    }

    #[AsEventListener(self::class)]
    public function handle(): void
    {
        if (! $this->isSuccess) {
            // 1. Record failure in the same login_attempts table
            $stmt = $this->db->prepare("INSERT INTO sys_login_attempts (username, ip_address) VALUES (?, ?)");
            $stmt->execute([
                $this->username,
                $this->ip
            ]);

            // 2. Check for brute force (5 fails in 10 mins)
            $stmt = $this->db->prepare("
                SELECT COUNT(*) FROM sys_login_attempts
                WHERE ip_address = ? AND attempted_at > DATE_SUB(NOW(), INTERVAL 10 MINUTE)
            ");
            $stmt->execute([
                $this->ip
            ]);

            if ($stmt->fetchColumn() >= 5) {
                $this->db->prepare("INSERT IGNORE INTO sys_blocked_ips (ip_address, reason) VALUES (?, 'Forgot Pass Brute Force')")->execute([
                    $this->ip
                ]);
            }
            return;
        }

        // --- 2. THE RATE LIMITER (Handles email spam prevention) ---
        // Check if this specific email was requested in the last 5 minutes
        $limitCheck = $this->db->prepare("
            SELECT 1 FROM sys_job_queues
            WHERE task_name = 'send_forgot_password_email'
            AND payload LIKE ?
            AND created_at > DATE_SUB(NOW(), INTERVAL 5 MINUTE)
            LIMIT 1
        ");
        // We look for the email inside the JSON payload column
        $limitCheck->execute([
            '%"email":"' . $this->user->email . '"%'
        ]);

        if ($limitCheck->fetch()) {
            // We throw a specific exception so the controller can catch it
            throw new Exception("A reset link was recently sent. Please wait 5 minutes before trying again.");
        }

        // 3. SUCCESS Logic: Add to Job Queue for background mailing
        $payload = json_encode([
            'email' => $this->user->email,
            'realname' => $this->user->realname,
            'partner' => $this->partner
        ]);

        $this->db->prepare("INSERT INTO sys_job_queues (task_name, payload) VALUES ('send_forgot_password_email', ?)")->execute([
            $payload
        ]);
    }
}