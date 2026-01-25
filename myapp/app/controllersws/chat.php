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

    public function uploadchunk(array $params = []): void
    {
        $fileId = $params['file_id'] ?? null;
        $chunk = $params['chunk'] ?? null;
        $index = (int) ($params['index'] ?? 0);

        if (! $fileId || ! $chunk)
            return;

        // Strip metadata ONLY on the first chunk.
        if ($index === 0 && strpos($chunk, ',') !== false) {
            $chunk = explode(',', $chunk)[1];
        }

        $tempDir = APP_DIR . '../public/assets/temp/' . $fileId . '/';
        if (! is_dir($tempDir)) {
            mkdir($tempDir, 0777, true);
        }

        $chunkName = str_pad((string) $index, 5, '0', STR_PAD_LEFT) . '.part';

        // We save the RAW BASE64 STRING. Do NOT decode yet.
        file_put_contents($tempDir . $chunkName, $chunk);
    }

    /**
     * Reassembles chunks into a valid binary file and saves chat record.
     */
    public function send(array $params = [], ?WSSocket $server = null, ?int $senderId = null): array
    {
        if (! $this->user) {
            throw new ApiException("Authentication required", 401);
        }

        $message = trim($params['message'] ?? '');
        $fileId = $params['file_id'] ?? null;
        $fileName = $params['file_name'] ?? null;
        $finalDbPath = null;

        // --- REASSEMBLE CHUNKS ---
        if ($fileId && $fileName) {
            $tempDir = APP_DIR . '../public/assets/temp/' . $fileId . '/';
            // Define subfolder for DB storage (relative to public root)
            $uploadSubDir = 'public/assets/uploads/chat/' . date('Y/m') . '/';
            $fullDestPath = APP_DIR . '../' . $uploadSubDir;

            // Create the destination folder if it doesn't exist
            if (! is_dir($fullDestPath)) {
                mkdir($fullDestPath, 0777, true);
            }

            $safeName = time() . '_' . preg_replace("/[^a-zA-Z0-9\._-]/", "", $fileName);
            $targetFile = $fullDestPath . $safeName;

            $parts = glob($tempDir . "*.part");
            sort($parts); // Ensure 00000, 00001 order

            if (count($parts) > 0) {
                // 1. Join all chunks into one long Base64 string
                // This prevents corruption caused by splitting Base64 blocks
                $fullBase64String = '';
                foreach ($parts as $part) {
                    $fullBase64String .= file_get_contents($part);
                    @unlink($part); // Delete chunk file immediately after reading
                }

                // 2. Decode the entire complete string into binary data
                $binaryData = base64_decode($fullBase64String);

                if ($binaryData !== false) {
                    // 3. Save the binary data as a real image file
                    file_put_contents($targetFile, $binaryData);
                    $finalDbPath = $uploadSubDir . $safeName;
                }

                // --- ROBUST CLEANUP ---
                // Even if unlinks above failed, we scan the folder to ensure it's empty
                if (is_dir($tempDir)) {
                    $remainingFiles = array_diff(scandir($tempDir), array(
                        '.',
                        '..'
                    ));
                    foreach ($remainingFiles as $file) {
                        @unlink($tempDir . DIRECTORY_SEPARATOR . $file);
                    }
                    // Once empty, the folder can be removed
                    @rmdir($tempDir);
                }
            }
        }

        // --- DATABASE LOGIC ---
        $senderName = $this->user->realname ?? "User";
        $uid = $this->user->id ?? 0;

        $chatLog = new model('chat_logs');
        $chatLog->sender_id = $uid;
        $chatLog->message = htmlspecialchars($message);
        $chatLog->file_path = $finalDbPath; // Path starts with 'assets/...'
        $chatLog->file_name = $fileName;
        $chatLog->save();

        $chatData = [
            'id' => $chatLog->id,
            'sender' => $senderName,
            'message' => $chatLog->message,
            'file_path' => $finalDbPath,
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
        if (! $this->user || ! isset($this->user->id)) {
            throw new ApiException("Authentication Error: Valid user session required.", 401);
        }

        $hmodel = new model('chat_logs');
        $history = $hmodel->select('p.id, p.message, p.file_path, p.file_name, p.d_created as time, u.realname as sender')
            ->join('mst_users u', 'u.id', '=', 'p.sender_id', 'LEFT')
            ->where('p.sender_id', '=', (int) $this->user->id)
            ->orderBy('p.id', 'DESC')
            ->limit(50)
            ->find();

        return [
            'status' => 'success',
            'type' => 'chat_history',
            'data' => array_reverse($history)
        ];
    }

    public function delete(array $params = [], ?WSSocket $server = null): array
    {
        $messageId = (int) ($params['message_id'] ?? 0);
        $hmodel = new model('chat_logs');
        $message = $hmodel->find($messageId);

        if ($message) {
            if (! empty($message->file_path)) {
                $physicalPath = APP_DIR . '../public/' . $message->file_path;
                if (file_exists($physicalPath)) {
                    @unlink($physicalPath);
                }
            }
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
