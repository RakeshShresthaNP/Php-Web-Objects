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

    public function send(array $params = [], ?WSSocket $server = null, ?int $senderId = null): array
    {
        if (! $this->user) {
            throw new ApiException("Authentication required", 401);
        }

        $message = trim($params['message'] ?? '');
        if (empty($message)) {
            throw new ApiException("Message cannot be empty", 422);
        }

        // Determine identity
        $senderName = ($this->user && isset($this->user->realname)) ? $this->user->realname : "Visitor #" . $senderId;
        $uid = ($this->user && isset($this->user->id)) ? $this->user->id : 0;

        $chatLog = new model('chat_logs');
        $chatLog->user_id = $uid;
        $chatLog->message = htmlspecialchars($message);

        $chatLog->save();

        $chatData = [
            'id' => $chatLog->id, // model auto-captures lastInsertId
            'sender' => $senderName,
            'message' => $chatLog->message,
            'time' => date('H:i')
        ];

        if ($server) {
            $server->broadcast([
                'type' => 'new_message',
                'data' => $chatData
            ], $senderId);
        }

        return [
            'status' => 'success',
            'type' => 'chat_confirmation',
            'data' => $chatData
        ];
    }

    public function history(array $params = []): array
    {
        // 1. Validate Partner Context
        if (! $this->partner) {
            throw new ApiException("Security Error: Partner context not initialized.", 401);
        }

        // 2. Validate Secret Key Presence
        $sKey = $this->partner->settings[0]->secretkey ?? null;
        if (! $sKey) {
            throw new ApiException("Security Error: Secret key missing for this partner.", 401);
        }

        // 3. Validate Authenticated User
        if (! $this->user || ! isset($this->user->id)) {
            throw new ApiException("Authentication Error: Valid user session required.", 401);
        }

        // 4. Initialize Model
        $hmodel = new model('chat_logs');

        /**
         * Query Builder Logic:
         * We pass 'u.id' and 'chat_logs.user_id' as separate arguments
         * because your model->join() concatenates them internally.
         */
        $history = $hmodel->select('p.id, p.message, p.d_created as time, u.realname as sender')
            ->join('mst_users u', 'u.id', '=', 'p.user_id', 'LEFT')
            ->where('p.user_id', '=', (int) $this->user->id)
            ->orderBy('p.id', 'DESC')
            ->limit(50)
            ->find();

        // 5. Format and Return
        return [
            'status' => 'success',
            'type' => 'chat_history',
            // Reversing ensures the oldest messages are at the top and newest at the bottom
            'data' => array_reverse($history)
        ];
    }

    public function delete(array $params = [], ?WSSocket $server = null): array
    {
        $messageId = (int) ($params['message_id'] ?? 0);

        $hmodel = new model('chat_logs');
        $message = $hmodel->find($messageId);

        if ($message) {
            $message->delete();

            if ($server) {
                $server->broadcast([
                    'type' => 'message_deleted',
                    'data' => [
                        'id' => $messageId
                    ]
                ]);
            }
        }

        return [
            'status' => 'success',
            'message' => 'Message removed'
        ];
    }
}
