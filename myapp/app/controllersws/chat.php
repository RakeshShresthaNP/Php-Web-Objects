<?php
declare(strict_types = 1);

final class cChat extends cController
{

    private $max_size = 5242880;

    // 5MB in bytes

    /**
     * Helper to ensure user is authenticated.
     * In some WebSocket setups, $this->user is not auto-populated from session,
     * so we use the token passed in $params.
     */
    private function authenticate(array $params): void
    {
        $token = $params['token'] ?? null;

        // If user isn't set, try to authenticate via token here if your framework supports it
        // e.g., $this->user = Auth::getUserByToken($token);

        if (! $this->user || ! isset($this->user->id)) {
            throw new ApiException("Authentication Required", 401);
        }
    }

    /**
     * renamed from uploadchunk to upload_chunk to match sys_methods
     */
    /**
     * Changed back to uploadchunk (no underscore) to match your preference
     */
    public function uploadchunk(array $params = []): array
    {
        // This creates a file named 'debug_upload.txt' in your 'public' or 'myapp' folder
        file_put_contents('debug_upload.txt', "Method called at: " . date('Y-m-d H:i:s') . PHP_EOL, FILE_APPEND);
        
        //$this->authenticate($params);

        $fileId = $params['file_id'] ?? null;
        $chunk = $params['chunk'] ?? '';
        $index = (int) ($params['index'] ?? 0);

        if (! $fileId)
            return [
                'status' => 'error',
                'message' => 'Missing File ID'
            ];

        $tempDir = APP_DIR . '../uploads/temp/' . $fileId . '/';
        if (! is_dir($tempDir))
            mkdir($tempDir, 0777, true);

        if ($index === 0 && strpos($chunk, ',') !== false) {
            $chunk = explode(',', $chunk)[1];
        }

        file_put_contents($tempDir . $index, base64_decode($chunk));
        return [
            'status' => 'chunk_received',
            'index' => $index
        ];
    }

    public function send(array $params = [], ?WSSocket $server = null, ?int $senderId = null): array
    {
        $this->authenticate($params);

        $text = $params['message'] ?? '';
        $fileId = $params['file_id'] ?? null;
        $fileName = $params['file_name'] ?? 'attachment';
        $finalUrl = null;

        if ($fileId) {
            $tempDir = APP_DIR . '../uploads/temp/' . $fileId . '/';
            $uploadDir = APP_DIR . '../uploads/chat/';
            if (! is_dir($uploadDir))
                mkdir($uploadDir, 0777, true);

            $chunks = glob($tempDir . '*');
            $totalSize = array_sum(array_map('filesize', $chunks));

            if ($totalSize > $this->max_size) {
                foreach ($chunks as $f)
                    unlink($f);
                @rmdir($tempDir);
                return [
                    'status' => 'error',
                    'message' => 'File too large'
                ];
            }

            $extension = pathinfo($fileName, PATHINFO_EXTENSION);
            $savePath = $uploadDir . time() . '_' . bin2hex(random_bytes(4)) . '.' . $extension;

            $finalFile = fopen($savePath, 'wb');
            natsort($chunks);
            foreach ($chunks as $chunkFile) {
                fwrite($finalFile, file_get_contents($chunkFile));
                unlink($chunkFile);
            }
            fclose($finalFile);
            @rmdir($tempDir);

            // Update this to your actual project URL
            $finalUrl = 'http://localhost/pwo/myapp/' . $savePath;
        }

        $structuredMessage = json_encode([
            'text' => htmlspecialchars($text),
            'file' => $finalUrl,
            'file_name' => $fileName
        ]);

        $chatLog = new model('chat_logs');
        $chatLog->user_id = (int) $this->user->id;
        $chatLog->message = $structuredMessage;
        $chatLog->save();

        $broadcastData = [
            'sender' => $this->user->realname ?? "User",
            'message' => $structuredMessage,
            'time' => date('H:i')
        ];

        if ($server) {
            $server->broadcast([
                'type' => 'new_message',
                'data' => $broadcastData
            ], $senderId);
        }

        return [
            'status' => 'success',
            'type' => 'chat_confirmation',
            'data' => $broadcastData
        ];
    }

    public function history(array $params = []): array
    {
        $this->authenticate($params);

        $hmodel = new model('chat_logs');
        $history = $hmodel->select('p.id, p.message, p.d_created as time, u.realname as sender')
            ->join('mst_users u', 'u.id', '=', 'p.user_id', 'LEFT')
            ->where('p.user_id', '=', (int) $this->user->id)
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
     * New Method for the Trash Icon
     */
    public function clear_history(array $params = []): array
    {
        $this->authenticate($params);

        $db = new model('chat_logs');
        $db->where('user_id', '=', (int) $this->user->id)->delete();

        return [
            'status' => 'success',
            'message' => 'History cleared'
        ];
    }

    public function delete(array $params = []): array
    {
        $this->authenticate($params);
        $messageId = (int) ($params['message_id'] ?? 0);
        $hmodel = new model('chat_logs');
        $hmodel->where('id', '=', $messageId)
            ->where('user_id', '=', $this->user->id)
            ->delete();

        return [
            'status' => 'success',
            'message' => 'Message removed'
        ];
    }
}
