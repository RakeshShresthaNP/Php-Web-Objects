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

class QueueWorker
{

    private PDO $db;

    private array $handlers = [];

    private int $maxAttempts = 3;

    public function __construct()
    {
        $this->db = db();
        $this->handlers['send_forgot_password_email'] = new QueuePasswordForgot();
    }

    public function run(): void
    {
        while (true) {
            $job = $this->fetchNextJob();

            if ($job) {
                $this->process($job);
            } else {
                sleep(5);
            }
        }
    }

    private function fetchNextJob(): ?array
    {
        // Only fetch jobs where status is pending AND available_at has passed
        $stmt = $this->db->prepare("
            SELECT * FROM sys_job_queue
            WHERE status = 'pending'
            AND available_at <= NOW()
            AND attempts < ?
            ORDER BY created_at ASC LIMIT 1
        ");
        $stmt->execute([
            $this->maxAttempts
        ]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    private function process(array $job): void
    {
        try {
            $this->updateStatus($job['id'], 'processing');

            $handler = $this->handlers[$job['task_name']] ?? null;
            if (! $handler)
                throw new \Exception("Handler missing");

            $handler->handle(json_decode($job['payload'], true));

            $this->updateStatus($job['id'], 'completed');
        } catch (\Throwable $e) {
            $this->handleFailure($job, $e->getMessage());
        }
    }

    private function handleFailure(array $job, string $error): void
    {
        $nextAttempt = $job['attempts'] + 1;

        if ($nextAttempt >= $this->maxAttempts) {
            // Permanent failure
            $this->updateStatus($job['id'], 'failed', "Max attempts reached: $error");
        } else {
            // Temporary failure: Re-queue and delay by 5 minutes (Exponential backoff)
            $delayMinutes = $nextAttempt * 5;

            $stmt = $this->db->prepare("
                UPDATE sys_job_queue
                SET status = 'pending',
                    attempts = ?,
                    error_message = ?,
                    available_at = DATE_ADD(NOW(), INTERVAL ? MINUTE)
                WHERE id = ?
            ");
            $stmt->execute([
                $nextAttempt,
                $error,
                $delayMinutes,
                $job['id']
            ]);

            writeLog('queuefailed_' . date('Y_m_d'), "Job #{$job['id']} failed. Retrying in $delayMinutes mins...\n");
        }
    }

    private function updateStatus(int $id, string $status, ?string $error = null): void
    {
        $this->db->prepare("UPDATE sys_job_queue SET status = ?, error_message = ? WHERE id = ?")->execute([
            $status,
            $error,
            $id
        ]);
    }
}
