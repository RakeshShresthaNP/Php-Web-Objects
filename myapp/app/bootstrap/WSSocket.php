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

if (! defined('WS_CONT_DIR')) {
    define('WS_CONT_DIR', APP_DIR . 'controllersws/');
}

final class WSSocket
{

    private ?Socket $master = null;

    private array $clients = [];

    private string $host;

    private int $port;

    public function __construct(string $host = '127.0.0.1', int $port = 8080)
    {
        $this->host = $host;
        $this->port = $port;
        $this->initSocket();
    }

    private function initSocket(): void
    {
        $this->master = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);

        if (! $this->master instanceof Socket) {
            throw new RuntimeException("Failed to create socket: " . socket_strerror(socket_last_error()));
        }

        socket_set_option($this->master, SOL_SOCKET, SO_REUSEADDR, 1);

        if (! socket_bind($this->master, $this->host, $this->port)) {
            throw new RuntimeException("Bind failed: " . socket_strerror(socket_last_error($this->master)));
        }

        socket_listen($this->master, 128);
        socket_set_nonblock($this->master);

        echo "🚀 WebSocket Server started on {$this->host}:{$this->port}" . PHP_EOL;
    }

    public function listen(): void
    {
        while (true) {
            $read = [];
            $read[] = $this->master;

            foreach ($this->clients as $client) {
                $read[] = $client['socket'];
            }

            $write = $except = null;

            if (socket_select($read, $write, $except, 0, 10) === false) {
                continue;
            }

            if (in_array($this->master, $read)) {
                $newSocket = socket_accept($this->master);
                if ($newSocket instanceof Socket) {
                    $this->connect($newSocket);
                }
                $key = array_search($this->master, $read);
                unset($read[$key]);
            }

            foreach ($read as $socket) {
                $this->process($socket);
            }

            usleep(5000);
        }
    }

    private function connect(Socket $socket): void
    {
        $id = spl_object_id($socket);
        $this->clients[$id] = [
            'socket' => $socket,
            'handshake' => false,
            'last_seen' => time(),
            'ip' => '0.0.0.0'
        ];
        echo "New client connected: #{$id}" . PHP_EOL;
    }

    private function process(Socket $socket): void
    {
        $id = spl_object_id($socket);

        /**
         * Buffer size: 300 KB (307200 bytes)
         * This accommodates:
         * - 256 KB of Base64 chunk data
         * - ~10 KB of JSON metadata (controller, method, token)
         * - WebSocket frame headers
         */
        $maxBuffer = 307200;
        $bytes = @socket_recv($socket, $buffer, $maxBuffer, 0);

        if ($bytes === 0 || $bytes === false) {
            $this->disconnect($id);
            return;
        }

        // 1. Handle Handshake
        if (! $this->clients[$id]['handshake']) {
            $this->doHandshake($id, $buffer);
            return;
        }

        // 2. Unmask the WebSocket Frame
        $payload = $this->unmask($buffer);

        if (! $payload) {
            return; // Skip empty or malformed frames
        }

        // 3. Decode JSON Data
        $data = json_decode($payload, true);

        // Update activity timestamp
        $this->clients[$id]['last_seen'] = time();

        // 4. Handle Internal Heartbeat (Ping/Pong)
        if ($data && isset($data['method']) && $data['method'] === 'ping') {
            try {
                // Keeps the MySQL connection from timing out
                DB::getContext();
            } catch (Exception $e) {
                writeLog('error', "DB Heartbeat failed: " . $e->getMessage());
            }

            $this->send($id, [
                'status' => 'success',
                'type' => 'pong',
                'time' => time()
            ]);
            return;
        }

        // 5. MVC Dispatcher Bridge
        if ($data && isset($data['controller'], $data['method'])) {
            $this->dispatch($id, $data);
        }
    }

    private function dispatch(int $id, array $data): void
    {
        // writeLog('debug', "FULL DATA FROM JS: " . json_encode($data));
        try {
            Request::resetContext();
            $req = Request::getContext();
            $req->controllerDir = WS_CONT_DIR;

            $params = $data['params'] ?? [];
            $headers = $data['headers'] ?? [];

            // 1. EXTRACT TOKEN FROM PARAMS
            // If the JS sent { params: { token: 'xyz' } }, we must put it in headers
            if (isset($params['token']) && ! empty($params['token'])) {
                $headers['Authorization'] = 'Bearer ' . $params['token'];
            }

            $hostname = $headers['X-Forwarded-Host'];

            $req->apimode = true;

            // 2. SET VIRTUAL CONTEXT (This fills $req->virtualHeaders)
            $req->setVirtualContext($params, $headers);

            // 3. LOAD PARTNER
            $req->getPartner($hostname);

            // 4. AUTHENTICATE (Now getToken() will find it!)
            $user = $req->getPayloadData();
            if ($user) {
                $req->user = $user;
                $req->cusertype = $user->perms ?? 'none';
            }

            $con = $req->verifyController('', $data['controller']);
            $met = $req->verifyMethod($con, $data['method']);

            $response = call_user_func_array([
                $con,
                $met
            ], [
                $params,
                $this,
                $id
            ]);

            if ($response !== null) {
                $this->send($id, $response);
            }
        } catch (ApiException $e) {
            writeLog('error', "WS Dispatch Error: " . $e->getMessage());
            $this->send($id, [
                'status' => 'error',
                'message' => $e->getMessage(),
                'code' => 401
            ]);
        }
    }

    public function broadcast(array|string $data, ?int $excludeId = null): void
    {
        $payload = is_array($data) ? json_encode($data) : $data;
        $maskedData = $this->mask($payload);

        foreach ($this->clients as $id => $client) {
            if ($excludeId !== null && $id === $excludeId)
                continue;
            @socket_write($client['socket'], $maskedData, strlen($maskedData));
        }
    }

    private function doHandshake(int $id, string $buffer): void
    {
        $remoteIp = '0.0.0.0';
        if (preg_match("/X-Forwarded-For: (.*)\r\n/", $buffer, $ips)) {
            $remoteIp = trim(explode(',', $ips[1])[0]);
        }

        $this->clients[$id]['ip'] = $remoteIp;

        if (preg_match("/Sec-WebSocket-Key: (.*)\r\n/", $buffer, $match)) {
            $key = base64_encode(sha1($match[1] . '258EAFA5-E914-47DA-95CA-C5AB0DC85B11', true));
            $upgrade = "HTTP/1.1 101 Switching Protocols\r\n" . "Upgrade: websocket\r\n" . "Connection: Upgrade\r\n" . "Sec-WebSocket-Accept: $key\r\n\r\n";

            socket_write($this->clients[$id]['socket'], $upgrade, strlen($upgrade));
            $this->clients[$id]['handshake'] = true;
            echo "Handshake OK #{$id} from {$remoteIp}" . PHP_EOL;
        }
    }

    private function send(int $id, array|string $data): void
    {
        if (! isset($this->clients[$id]))
            return;
        $text = is_array($data) ? json_encode($data) : $data;
        $response = $this->mask($text);
        @socket_write($this->clients[$id]['socket'], $response, strlen($response));
    }

    private function unmask(string $text): string
    {
        if (strlen($text) < 2)
            return "";

        $length = ord($text[1]) & 127;

        if ($length == 126) {
            // Extended payload: 2 bytes for length + 4 bytes for mask
            $masks = substr($text, 4, 4);
            $data = substr($text, 8);
        } elseif ($length == 127) {
            // Huge payload: 8 bytes for length + 4 bytes for mask
            $masks = substr($text, 10, 4);
            $data = substr($text, 14);
        } else {
            // Standard payload: 4 bytes for mask
            $masks = substr($text, 2, 4);
            $data = substr($text, 6);
        }

        $decoded = "";
        $dataLength = strlen($data);
        for ($i = 0; $i < $dataLength; ++ $i) {
            $decoded .= $data[$i] ^ $masks[$i % 4];
        }
        return $decoded;
    }

    private function mask(string $text): string
    {
        $b1 = 0x80 | (0x1 & 0x0f);
        $length = strlen($text);
        if ($length <= 125)
            $header = pack('CC', $b1, $length);
        elseif ($length > 125 && $length < 65536)
            $header = pack('CCn', $b1, 126, $length);
        else
            $header = pack('CCNN', $b1, 127, $length);
        return $header . $text;
    }

    private function disconnect(int $id): void
    {
        if (isset($this->clients[$id])) {
            @socket_close($this->clients[$id]['socket']);
            unset($this->clients[$id]);
            echo "Client #{$id} disconnected" . PHP_EOL;
        }
    }
}