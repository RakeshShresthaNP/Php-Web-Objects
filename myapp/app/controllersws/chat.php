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

// --- DEFINE CONSISTENT PATHS ---
if (! defined('PWO_DIR_ASSETS')) {
    // Exact physical root based on your diagnostics
    $basePath = APP_DIR . "../";

    define('DIR_TEMP', $basePath . "\\public\\assets\\temp\\");
    define('DIR_UPLOADS', $basePath . "\\public\\assets\\uploads\\chat\\");

    // Web URL Path (includes public because of your webroot setup)
    define('URL_BASE', 'public/assets/uploads/chat/');

    // Pre-flight check for folders
    if (! is_dir(DIR_TEMP))
        @mkdir(DIR_TEMP, 0777, true);
    if (! is_dir(DIR_UPLOADS))
        @mkdir(DIR_UPLOADS, 0777, true);
}

final class cChat extends cController
{

    /**
     * CHUNKING: Writes binary parts to the temp folder
     */
    public function uploadchunk(array $params = [], ?WSSocket $server = null, ?int $senderId = null): void
    {
        $fileId = $params['file_id'] ?? null;
        $chunk = $params['chunk'] ?? null;
        $index = (int) ($params['index'] ?? 0);

        if (! $fileId || ! $chunk)
            return;

        // Clean Base64 meta
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
    }

    /**
     * SEND: Merges chunks and saves message to DB
     */
    public function send(array $params = [], ?WSSocket $server = null, ?int $senderId = null): array
    {
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
                    // This is the URL stored in Database
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
        $chatLog->save();

        $data = [
            'id' => $chatLog->id,
            'message' => $message,
            'file_path' => $finalUrl,
            'file_name' => $fileName,
            'sender' => $this->user->realname ?? 'User',
            'time' => date('H:i')
        ];

        if ($server)
            $server->broadcast([
                'type' => 'new_message',
                'data' => $data
            ], $senderId);

        return [
            'status' => 'success',
            'type' => 'chat_confirmation',
            'data' => $data
        ];
    }

    /**
     * HISTORY: Retrieves last 50 messages
     */
    public function history(array $params = []): array
    {
        if (! $this->user)
            throw new ApiException("Auth Error", 401);

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

    /**
     * DELETE: Removes message and file
     */
    public function delete(array $params = [], ?WSSocket $server = null): array
    {
        $messageId = (int) ($params['message_id'] ?? 0);
        $hmodel = new model('chat_logs');
        $message = $hmodel->find($messageId);

        if ($message && $message->sender_id == $this->user->id) {
            if (! empty($message->file_path)) {
                // Construct physical path from DB path
                $base = "D:\\XAMPP\\www\\pwo\\myapp\\";
                $physical = $base . str_replace('/', DIRECTORY_SEPARATOR, $message->file_path);
                if (file_exists($physical))
                    @unlink($physical);
            }
            $message->delete();
            if ($server)
                $server->broadcast([
                    'type' => 'message_deleted',
                    'data' => [
                        'id' => $messageId
                    ]
                ]);
        }

        return [
            'status' => 'success',
            'message' => 'Removed'
        ];
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
