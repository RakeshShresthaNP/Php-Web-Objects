<?php
declare(strict_types = 1);

final class QueueWorker
{

    private PDO $db;

    private array $handlers = [];

    private int $maxAttempts = 3;

    public function __construct()
    {
        try {
            $this->db = db();
            $this->handlers['send_forgot_password_email'] = new QueueForgotPassword();
        } catch (Throwable $e) {
            writeLog('queue_fatal_' . date('Y_m_d'), "Init Failed: " . $e->getMessage());
            throw $e;
        }
    }

    public function process(): void
    {
        while (true) {
            try {
                $job = $this->fetchNextJob();
                if ($job) {
                    $this->run($job);
                } else {
                    sleep(5);
                }
            } catch (Throwable $e) {
                writeLog('queue_loop_error_' . date('Y_m_d'), $e->getMessage());
                sleep(10);
            }
        }
    }

    private function fetchNextJob(): ?array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT * FROM sys_job_queues
                WHERE status = 'pending'
                AND available_at <= UTC_TIMESTAMP()
                AND attempts < ?
                ORDER BY created_at ASC LIMIT 1
            ");
            $stmt->execute([
                $this->maxAttempts
            ]);
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        } catch (Throwable $e) {
            writeLog('queue_db_error_' . date('Y_m_d'), "Fetch Failed: " . $e->getMessage());
            return null;
        }
    }

    private function run(array $job): void
    {
        try {
            $this->updateStatus($job['id'], 'processing');
            $handler = $this->handlers[$job['task_name']] ?? null;
            if (! $handler)
                throw new \Exception("Handler missing: " . $job['task_name']);
            $handler->handle(json_decode($job['payload'], true));
            $this->updateStatus($job['id'], 'completed');
        } catch (Throwable $e) {
            $this->handleFailure($job, $e->getMessage());
        }
    }

    private function handleFailure(array $job, string $error): void
    {
        try {
            $nextAttempt = $job['attempts'] + 1;
            if ($nextAttempt >= $this->maxAttempts) {
                $this->updateStatus($job['id'], 'failed', "Max attempts reached: $error");
                writeLog('queue_permanent_failure_' . date('Y_m_d'), "Job #{$job['id']}: $error");
            } else {
                $delayMinutes = $nextAttempt * 5;
                $stmt = $this->db->prepare("
                    UPDATE sys_job_queues
                    SET status = 'pending',
                        attempts = ?,
                        error_message = ?,
                        available_at = DATE_ADD(UTC_TIMESTAMP(), INTERVAL ? MINUTE)
                    WHERE id = ?
                ");
                $stmt->execute([
                    $nextAttempt,
                    $error,
                    $delayMinutes,
                    $job['id']
                ]);
                writeLog('queue_retry_' . date('Y_m_d'), "Job #{$job['id']} retrying in $delayMinutes mins: $error");
            }
        } catch (Throwable $e) {
            writeLog('queue_failure_handler_error_' . date('Y_m_d'), $e->getMessage());
        }
    }

    private function updateStatus(int $id, string $status, ?string $error = null): void
    {
        try {
            $this->db->prepare("UPDATE sys_job_queues SET status = ?, error_message = ? WHERE id = ?")->execute([
                $status,
                $error,
                $id
            ]);
        } catch (Throwable $e) {
            writeLog('queue_db_error_' . date('Y_m_d'), "Update Status Failed #$id: " . $e->getMessage());
        }
    }
}
