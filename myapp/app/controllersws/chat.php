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

if (! defined('PWO_DIR_ASSETS')) {
    $basePath = APP_DIR . "../";
    define('DIR_TEMP', $basePath . "public" . DIRECTORY_SEPARATOR . "assets" . DIRECTORY_SEPARATOR . "temp" . DIRECTORY_SEPARATOR);
    define('DIR_UPLOADS', $basePath . "public" . DIRECTORY_SEPARATOR . "assets" . DIRECTORY_SEPARATOR . "uploads" . DIRECTORY_SEPARATOR . "chat" . DIRECTORY_SEPARATOR);
    define('URL_BASE', 'public/assets/uploads/chat/');
    if (! is_dir(DIR_TEMP))
        @mkdir(DIR_TEMP, 0777, true);
    if (! is_dir(DIR_UPLOADS))
        @mkdir(DIR_UPLOADS, 0777, true);
}

final class cChat extends cController
{

    public function uploadchunk(array $params = [], ?WSSocket $server = null, ?int $senderId = null): void
    {
        try {
            $fileId = $params['file_id'] ?? null;
            $chunk = $params['chunk'] ?? null;
            $index = (int) ($params['index'] ?? 0);
            if (! $fileId || ! $chunk)
                return;
            if ($index === 0 && strpos($chunk, ',') !== false) {
                $chunk = explode(',', $chunk)[1];
            }
            $tempDir = DIR_TEMP . $fileId . DIRECTORY_SEPARATOR;
            if (! is_dir($tempDir) && ! mkdir($tempDir, 0777, true)) {
                if ($server)
                    $server->send($senderId, [
                        'type' => 'error',
                        'detail' => 'Folder creation failed'
                    ]);
                return;
            }
            $chunkName = str_pad((string) $index, 6, '0', STR_PAD_LEFT) . '.part';
            file_put_contents($tempDir . $chunkName, $chunk);

            if ($server) {
                $server->send($senderId, [
                    'type' => 'chunk_ack',
                    'detail' => [
                        'index' => $index,
                        'file_id' => $fileId
                    ]
                ]);
            }
        } catch (Throwable $t) {
            writeLog('chat_upload_error_' . date('Y_m_d'), "ID #$senderId: " . $t->getMessage());
        }
    }

    public function send(array $params = [], ?WSSocket $server = null, ?int $senderId = null): array
    {
        try {
            if (! $this->user)
                throw new ApiException("Auth Required", 401);
            $message = htmlspecialchars($params['message'] ?? '');
            $fileId = $params['file_id'] ?? null;
            $fileName = $params['file_name'] ?? null;
            $finalUrl = null;

            if ($fileId && $fileName) {
                $tempDir = DIR_TEMP . $fileId . DIRECTORY_SEPARATOR;
                $dateSub = date('Y') . DIRECTORY_SEPARATOR . date('m') . DIRECTORY_SEPARATOR;
                $fullDest = DIR_UPLOADS . $dateSub;
                if (! is_dir($fullDest))
                    mkdir($fullDest, 0777, true);
                $parts = glob($tempDir . "*.part");
                sort($parts);
                if (count($parts) > 0) {
                    $fullData = '';
                    foreach ($parts as $part) {
                        $fullData .= file_get_contents($part);
                        @unlink($part);
                    }
                    $safeName = time() . '_' . preg_replace("/[^a-zA-Z0-9\._-]/", "", $fileName);
                    $binary = base64_decode($fullData);
                    if (file_put_contents($fullDest . $safeName, $binary)) {
                        $finalUrl = URL_BASE . date('Y/m') . '/' . $safeName;
                    }
                    $this->recursiveRemove($tempDir);
                }
            }

            $chatLog = new model('chat_logs');
            $chatLog->sender_id = $this->user->id;
            $chatLog->message = $message;
            $chatLog->file_path = $finalUrl;
            $chatLog->file_name = $fileName;
            $chatLog->is_read = 0;
            $chatLog->save();

            $data = [
                'id' => $chatLog->id,
                'message' => $message,
                'file_path' => $finalUrl,
                'file_name' => $fileName,
                'sender' => $this->user->realname ?? 'User',
                'sender_id' => (int) $this->user->id,
                'is_read' => 0,
                'time' => date('H:i')
            ];

            if ($server) {
                $server->broadcast([
                    'type' => 'new_message',
                    'data' => $data
                ]);
            }

            return [
                'status' => 'success',
                'type' => 'chat_confirmation',
                'data' => $data
            ];
        } catch (Throwable $t) {
            writeLog('chat_send_error_' . date('Y_m_d'), "User #{$this->user->id}: " . $t->getMessage());
            return [
                'status' => 'error',
                'message' => 'Failed to send message'
            ];
        }
    }

    public function history(array $params = []): array
    {
        try {
            if (! $this->user)
                throw new ApiException("Auth Error", 401);
            $hmodel = new model('chat_logs');
            $history = $hmodel->select('p.id, p.message, p.file_path, p.file_name, p.d_created as time, u.realname as sender, p.sender_id, p.is_read')
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
        } catch (Throwable $t) {
            writeLog('chat_history_error_' . date('Y_m_d'), $t->getMessage());
            return [
                'status' => 'error',
                'data' => []
            ];
        }
    }

    public function markread(array $params = [], ?WSSocket $server = null, ?int $senderId = null): void
    {
        try {
            if (! $this->user || ! $server)
                return;
            $userRole = $this->user->perms ?? 'user';
            $partnerId = ($userRole === 'admin' || $userRole === 'superadmin') ? (int) ($params['target_user_id'] ?? 0) : 0;
            
            $hmodel = new model('chat_logs');
            $hmodel->where('sender_id', '=', $partnerId)
            ->where('is_read', '=', 0)
            ->updateWhere(['is_read' => 1]);
            
            $server->broadcast([
                'type' => 'message_read',
                'data' => [
                    'reader_id' => (int) $this->user->id,
                    'target_id' => $partnerId
                ]
            ]);
        } catch (Throwable $t) {
            writeLog('chat_read_error_' . date('Y_m_d'), $t->getMessage());
        }
    }

    public function typing(array $params = [], ?WSSocket $server = null, ?int $senderId = null): void
    {
        try {
            if (! $this->user || ! $server || ! $senderId)
                return;
            $server->broadcast([
                'type' => 'typing',
                'data' => [
                    'sender_id' => (int) $this->user->id
                ]
            ], $senderId);
        } catch (Throwable $t) {
            writeLog('chat_typing_error_' . date('Y_m_d'), $t->getMessage());
        }
    }

    public function delete(array $params = [], ?WSSocket $server = null): array
    {
        try {
            $messageId = (int) ($params['message_id'] ?? 0);
            $hmodel = new model('chat_logs');
            
            // 1. Find the message data first so we can get the file path
            $message = $hmodel->where('id', '=', $messageId)->first();
            
            // 2. Check ownership before doing anything
            if ($message && (int)$message->sender_id === (int)$this->user->id) {
                
                // 3. Handle Physical File Deletion
                if (!empty($message->file_path)) {
                    // Adjust base path to your 'public' folder
                    $base = APP_DIR . "../public/";
                    $physical = $base . str_replace('/', DIRECTORY_SEPARATOR, $message->file_path);
                    
                    if (file_exists($physical)) {
                        @unlink($physical);
                    }
                }
                
                // 4. Delete the Database Record using deleteWhere
                // This is safer because it doesn't rely on the object's internal state
                $hmodel->where('id', '=', $messageId)->deleteWhere();
                
                if ($server) {
                    $server->broadcast([
                        'type' => 'message_deleted',
                        'data' => ['id' => $messageId]
                    ]);
                }
            }
            
            return [
                'status' => 'success',
                'message' => 'Removed'
            ];
        } catch (Throwable $t) {
            writeLog('chat_delete_error_' . date('Y_m_d'), $t->getMessage());
            return [
                'status' => 'error',
                'message' => 'Delete failed: ' . $t->getMessage()
            ];
        }
    }
    
    private function recursiveRemove($dir): void
    {
        if (! is_dir($dir))
            return;
        $files = array_diff(scandir($dir), [
            '.',
            '..'
        ]);
        foreach ($files as $file) {
            (is_dir("$dir/$file")) ? $this->recursiveRemove("$dir/$file") : @unlink("$dir/$file");
        }
        @rmdir($dir);
    }
}
