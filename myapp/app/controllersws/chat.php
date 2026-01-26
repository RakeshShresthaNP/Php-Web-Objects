<?php
/**
 # PWO Support - SERVER MASTER 2026
 # High-performance WebSocket Chat Controller
 # Logic: Handles chunked uploads, database persistence, and cross-browser synchronization.
 */
declare(strict_types = 1);

// --- DEFINE CONSISTENT PATHS ---
if (! defined('PWO_DIR_ASSETS')) {
    $basePath = APP_DIR . "../";

    define('DIR_TEMP', $basePath . "public" . DIRECTORY_SEPARATOR . "assets" . DIRECTORY_SEPARATOR . "temp" . DIRECTORY_SEPARATOR);
    define('DIR_UPLOADS', $basePath . "public" . DIRECTORY_SEPARATOR . "assets" . DIRECTORY_SEPARATOR . "uploads" . DIRECTORY_SEPARATOR . "chat" . DIRECTORY_SEPARATOR);

    // Web URL Path
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
     * Works for both Chrome (webm) and Firefox (ogg/webm)
     */
    public function uploadchunk(array $params = [], ?WSSocket $server = null, ?int $senderId = null): void
    {
        $fileId = $params['file_id'] ?? null;
        $chunk = $params['chunk'] ?? null;
        $index = (int) ($params['index'] ?? 0);

        if (! $fileId || ! $chunk)
            return;

        // Chrome/Firefox Base64 Compatibility: Strip header if present
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
     * SEND: Merges chunks, saves to DB, and broadcasts to ALL user tabs
     */
    public function send(array $params = [], ?WSSocket $server = null, ?int $senderId = null): array
    {
        if (! $this->user)
            throw new ApiException("Auth Required", 401);

        $message = htmlspecialchars($params['message'] ?? '');
        $fileId = $params['file_id'] ?? null;
        $fileName = $params['file_name'] ?? null;
        $finalUrl = null;

        // Handle File Assembly if a file_id exists
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

        // Save to Database
        $chatLog = new model('chat_logs');
        $chatLog->sender_id = $this->user->id;
        $chatLog->message = $message;
        $chatLog->file_path = $finalUrl;
        $chatLog->file_name = $fileName;
        $chatLog->save();

        // Data packet for Javascript
        $data = [
            'id' => $chatLog->id,
            'message' => $message,
            'file_path' => $finalUrl,
            'file_name' => $fileName,
            'sender' => $this->user->realname ?? 'User',
            'sender_id' => (int) $this->user->id,
            'time' => date('H:i')
        ];

        if ($server) {
            // THE SYNC FIX:
            // We broadcast to EVERYONE. We do NOT exclude the $senderId.
            // This ensures Chrome hears what Firefox said instantly.
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
    }

    /**
     * HISTORY: Retrieves history including sender_id for UI positioning
     */
    public function history(array $params = []): array
    {
        if (! $this->user)
            throw new ApiException("Auth Error", 401);

        $hmodel = new model('chat_logs');
        // Ensure p.sender_id is selected so Javascript knows if the message is "mine"
        $history = $hmodel->select('p.id, p.message, p.file_path, p.file_name, p.d_created as time, u.realname as sender, p.sender_id')
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
     * DELETE: Removes from DB and broadcasts deletion to all tabs
     */
    public function delete(array $params = [], ?WSSocket $server = null): array
    {
        $messageId = (int) ($params['message_id'] ?? 0);
        $hmodel = new model('chat_logs');
        $message = $hmodel->find($messageId);

        if ($message && $message->sender_id == $this->user->id) {
            if (! empty($message->file_path)) {
                $base = APP_DIR . "../";
                $physical = $base . str_replace('/', DIRECTORY_SEPARATOR, $message->file_path);
                if (file_exists($physical))
                    @unlink($physical);
            }

            $message->delete();

            if ($server) {
                // Tells all browsers (Chrome/Firefox) to remove this bubble
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
