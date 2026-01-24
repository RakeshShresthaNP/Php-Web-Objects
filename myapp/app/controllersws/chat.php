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

final class cChat extends cController
{

    /**
     * Handles incoming chat messages and broadcasts them.
     * * @param array $params Data from the client
     *
     * @param WSSocket|null $server
     *            The socket server instance for broadcasting
     * @param int|null $senderId
     *            The unique socket ID of the sender
     */
    public function send(array $params = [], ?WSSocket $server = null, ?int $senderId = null): array
    {
        if (! $this->user) {
            throw new ApiException("Authentication required", 401);
        }

        $message = trim($params['message'] ?? '');
        if (empty($message)) {
            throw new ApiException("Message cannot be empty", 422);
        }

        $cleanMessage = htmlspecialchars($message, ENT_QUOTES, 'UTF-8');
        $db = DB::getContext();

        // 1. Insert into Aria-backed chat_logs table
        $stmt = $db->prepare("INSERT INTO chat_logs (user_id, message, d_created) VALUES (?, ?, UTC_TIMESTAMP())");
        $stmt->execute([
            $this->user->id,
            $cleanMessage
        ]);

        // 2. Capture the new ID
        $newId = (int) $db->lastInsertId();

        $chatData = [
            'id' => $newId, // Critical for JS element targeting
            'sender' => $this->user->realname ?? $this->user->c_name, // Using your mst_users columns
            'message' => $cleanMessage,
            'time' => date('H:i')
        ];

        // 3. Broadcast to others
        if ($server) {
            $server->broadcast([
                'type' => 'new_message',
                'data' => $chatData
            ], $senderId);
        }

        // 4. Return to sender
        return [
            'status' => 'success',
            'type' => 'chat_confirmation',
            'data' => $chatData
        ];
    }

    /**
     * Retrieve chat history joined with user names
     */
    public function history(array $params = []): array
    {
        // 1. Authorization Check
        if (! $this->user) {
            throw new ApiException("Unauthorized", 401);
        }

        // 2. Get DB Context
        $db = DB::getContext();

        $sql = "SELECT
            c.id,
            c.message,
            c.d_created as time,
            u.realname as sender
        FROM chat_logs c
        INNER JOIN mst_users u ON c.user_id = u.id
        ORDER BY c.id DESC
        LIMIT 50";

        try {
            $stmt = $db->query($sql);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Reverse so the oldest of the 'last 50' is at the top of the chat
            $history = array_reverse($rows);

            return [
                'status' => 'success',
                'type' => 'chat_history', // WSClient.js listens for 'ws_chat_history'
                'data' => $history
            ];
        } catch (Exception $e) {
            throw new ApiException("Could not retrieve chat history", 500);
        }
    }

    /**
     * Allows admins to delete a specific message
     */
    public function delete(array $params = [], ?WSSocket $server = null): array
    {
        // WSSocket::dispatch already verified 'admin' or 'superadmin' via sys_methods
        if (! $this->user) {
            throw new ApiException("Unauthorized", 401);
        }

        $messageId = (int) ($params['message_id'] ?? 0);

        if ($messageId <= 0) {
            throw new ApiException("Invalid message ID", 422);
        }

        // 2. Database Deletion
        $db = DB::getContext();
        $stmt = $db->prepare("DELETE FROM chat_logs WHERE id = ?");
        $stmt->execute([
            $messageId
        ]);

        // 3. Broadcast the Deletion
        // We tell everyone to remove this ID from their screen
        if ($server) {
            $server->broadcast([
                'type' => 'message_deleted',
                'data' => [
                    'id' => $messageId
                ]
            ]);
        }

        return [
            'status' => 'success',
            'message' => 'Message removed'
        ];
    }
}
