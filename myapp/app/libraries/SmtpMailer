<?php

declare(strict_types=1);

class SmtpException extends Exception {}

final class SmtpMailer
{
    private $connection;
    private string $fromEmail;
    private string $fromName = 'Sender';
    private string $subject = '';
    private string $message = '';
    private array $to = [];
    private array $cc = [];
    private array $bcc = [];
    private array $attachments = [];
    private string $boundary;

    public function __construct(
        private readonly string $host,
        private readonly int $port,
        private readonly string $username,
        private readonly string $password,
        private readonly string $encryption = 'tls'
    ) {
        $this->fromEmail = $this->username;
        $this->boundary = "PHP_MIXED_" . md5((string)microtime(true));
    }

    public function setFrom(string $email, string $name = ''): self {
        $this->fromEmail = $email;
        $this->fromName = $name ?: $email;
        return $this;
    }

    public function addTo(string $email): self {
        $this->to[] = $email;
        return $this;
    }

    public function setCc(array $emails): self {
        $this->cc = $emails;
        return $this;
    }

    public function setBcc(array $emails): self {
        $this->bcc = $emails;
        return $this;
    }

    public function setSubject(string $subject): self {
        $this->subject = $subject;
        return $this;
    }

    public function setMessage(string $htmlMessage): self {
        $this->message = $htmlMessage;
        return $this;
    }

    public function addAttachment(string $filePath): self {
        if (!file_exists($filePath)) {
            throw new SmtpException("Attachment file not found: $filePath");
        }
        $this->attachments[] = $filePath;
        return $this;
    }

    public function send(): bool {
        try {
            $protocol = ($this->encryption === 'ssl') ? "ssl://{$this->host}" : $this->host;
            
            // Connect to server
            $this->connection = @fsockopen($protocol, $this->port, $errno, $errstr, 15);
            if (!$this->connection) {
                throw new SmtpException("Could not connect to host {$this->host}: $errstr ($errno)");
            }

            $this->verifyResponse(220, "Server greeting not received");
            $this->sendCommand("EHLO " . gethostname());

            // Start Encryption
            if ($this->encryption === 'tls') {
                $this->sendCommand("STARTTLS");
                $this->verifyResponse(220, "STARTTLS failed");
                if (!stream_socket_enable_crypto($this->connection, true, STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT | STREAM_CRYPTO_METHOD_TLSv1_3_CLIENT)) {
                    throw new SmtpException("TLS handshake failed");
                }
                $this->sendCommand("EHLO " . gethostname());
            }

            // Authentication
            $this->sendCommand("AUTH LOGIN");
            $this->verifyResponse(334, "AUTH LOGIN not accepted");
            
            $this->sendCommand(base64_encode($this->username));
            $this->verifyResponse(334, "Username rejected");
            
            $this->sendCommand(base64_encode($this->password));
            $this->verifyResponse(235, "Authentication failed (Invalid password/App Password)");

            // SMTP Protocol sequence diagram
            // 

            // Envelope
            $this->sendCommand("MAIL FROM: <{$this->fromEmail}>");
            $this->verifyResponse(250, "MAIL FROM rejected");

            foreach ($this->to as $email) {
                $this->sendCommand("RCPT TO: <$email>");
                $this->verifyResponse(250, "Recipient rejected: $email");
            }

            // Data Transmission
            $this->sendCommand("DATA");
            $this->verifyResponse(354, "Server not ready for data");

            $emailContent = $this->buildMimeMessage();
            $this->sendCommand($emailContent . "\r\n.");
            $this->verifyResponse(250, "Message body rejected");

            $this->sendCommand("QUIT");
            fclose($this->connection);
            return true;

        } catch (SmtpException $e) {
            if ($this->connection) fclose($this->connection);
            throw new ApiException ($e->getMessage(), 500); // Rethrow to be caught by the implementation script
        }
    }

    private function buildMimeMessage(): string {
        $headers = [
            "From: {$this->fromName} <{$this->fromEmail}>",
            "To: " . implode(', ', $this->to),
            "Cc: " . implode(', ', $this->cc),
            "Subject: {$this->subject}",
            "MIME-Version: 1.0",
            "Content-Type: multipart/mixed; boundary=\"{$this->boundary}\"",
            "Date: " . date('r'),
            "X-Mailer: PHP/" . phpversion()
        ];

        $body = implode("\r\n", $headers) . "\r\n\r\n";
        
        // HTML Body Part
        $body .= "--{$this->boundary}\r\n";
        $body .= "Content-Type: text/html; charset=UTF-8\r\n";
        $body .= "Content-Transfer-Encoding: 8bit\r\n\r\n";
        $body .= $this->message . "\r\n\r\n";

        // Attachments
        foreach ($this->attachments as $path) {
            $filename = basename($path);
            $content = chunk_split(base64_encode(file_get_contents($path)));
            $body .= "--{$this->boundary}\r\n";
            $body .= "Content-Type: application/octet-stream; name=\"$filename\"\r\n";
            $body .= "Content-Disposition: attachment; filename=\"$filename\"\r\n";
            $body .= "Content-Transfer-Encoding: base64\r\n\r\n";
            $body .= $content . "\r\n";
        }

        $body .= "--{$this->boundary}--";
        return $body;
    }

    private function sendCommand(string $cmd): void {
        fwrite($this->connection, $cmd . "\r\n");
    }

    private function verifyResponse(int $expectedCode, string $errorMessage): void {
        $response = "";
        while ($str = fgets($this->connection, 515)) {
            $response .= $str;
            if (substr($str, 3, 1) === " ") break;
        }
        $code = (int)substr($response, 0, 3);
        if ($code !== $expectedCode) {
            throw new SmtpException("$errorMessage. Server response: $response");
        }
    }
}
