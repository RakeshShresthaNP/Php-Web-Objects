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

final class QueueForgotPassword implements JobHandlerInterface
{

    public function handle(array $data): void
    {
        // 1. Setup Mailer
        $mail = new Mailer();

        // 2. Map data from the JSON payload
        $email = $data['email'] ?? null;
        $realname = $data['realname'] ?? 'User';
        $partner = $data['partner'] ?? [];

        if (! $email) {
            throw new Exception("Missing recipient email in job payload.");
        }

        // 3. Configure Email
        $mail->setFrom($partner['email'] ?? 'noreply@domain.com', $partner['c_name'] ?? 'System');
        $mail->setTO($email, $realname);
        $mail->setSubject('Password Reset Request');

        // 4. Set Body (Plain text or HTML)
        $body = "Hi $realname,\n\nClick the link below to reset your password:\n";
        $body .= "https://yourdomain.com/reset?email=" . urlencode($email);
        $mail->setMessage($body);

        // 5. Send and check for success
        if (! $mail->send()) {
            // Throwing an exception here triggers the Worker's retry logic
            throw new Exception("Mailer failed to send to $email. SMTP error.");
        }
    }
}
