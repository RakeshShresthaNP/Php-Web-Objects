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
final class cChat extends cController
{

    /**
     * Handles incoming chat messages
     * Expected JSON via WebSocket:
     * { "controller": "chat", "method": "send", "params": {"message": "...", "token": "..."} }
     */
    public function send(array $params = []): array
    {
        // 1. Authorization Check
        // $this->user is automatically populated by WSSocket::dispatch using the JWT token
        if (! $this->user) {
            throw new ApiException("Authentication required", 401);
        }

        // 2. Data Validation
        $message = mb_trim($params['message'] ?? '');

        if (empty($message)) {
            throw new ApiException("Message cannot be empty", 422);
        }

        // 3. Business Logic (Example: Sanitize and Log)
        $cleanMessage = htmlspecialchars($message, ENT_QUOTES, 'UTF-8');

        // Note: In a real app, you would save this to your database here:
        // $chatModel = new model('chat_logs');
        // $chatModel->insert([
        // 'user_id' => $this->user->id,
        // 'message' => $cleanMessage,
        // 'created_at' => date('Y-m-d H:i:s')
        // ]);

        // 4. Return Response
        // This array is sent back to the client that sent the message
        return [
            'status' => 'success',
            'type' => 'chat_confirmation',
            'data' => [
                'sender' => $this->user->name ?? 'User',
                'message' => $cleanMessage,
                'time' => date('H:i')
            ]
        ];
    }

    /**
     * Example method to get chat history
     */
    public function history(array $params = []): array
    {
        if (! $this->user) {
            throw new ApiException("Unauthorized", 401);
        }

        // Mock data - replace with actual DB call
        return [
            'status' => 'success',
            'history' => [
                [
                    'user' => 'System',
                    'msg' => 'Welcome to the chat!'
                ]
            ]
        ];
    }
}
