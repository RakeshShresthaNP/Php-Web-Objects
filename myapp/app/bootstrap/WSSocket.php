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
    define('WS_CONT_DIR', APP_DIR . 'wscontrollers/');
}

final class WSSocket
{

    private ?\Socket $master = null;

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

        if (! $this->master instanceof \Socket) {
            throw new \RuntimeException("Failed to create socket: " . socket_strerror(socket_last_error()));
        }

        socket_set_option($this->master, SOL_SOCKET, SO_REUSEADDR, 1);

        if (! socket_bind($this->master, $this->host, $this->port)) {
            throw new \RuntimeException("Bind failed: " . socket_strerror(socket_last_error($this->master)));
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

            // In PHP 8.4, socket_select processes the array of Socket objects
            if (socket_select($read, $write, $except, 0, 10) === false) {
                continue;
            }

            // Handle New Connections
            if (in_array($this->master, $read)) {
                $newSocket = socket_accept($this->master);
                if ($newSocket instanceof \Socket) {
                    $this->connect($newSocket);
                }
                $key = array_search($this->master, $read);
                unset($read[$key]);
            }

            // Handle Incoming Data
            foreach ($read as $socket) {
                $this->process($socket);
            }

            usleep(5000); // 5ms sleep to prevent 100% CPU usage
        }
    }

    private function connect(\Socket $socket): void
    {
        $id = spl_object_id($socket); // The modern way to get a unique integer ID
        $this->clients[$id] = [
            'socket' => $socket,
            'handshake' => false
        ];
        echo "New client connected: #{$id}" . PHP_EOL;
    }

    private function process(\Socket $socket): void
    {
        $id = spl_object_id($socket);
        $bytes = @socket_recv($socket, $buffer, 2048, 0);

        if ($bytes === 0 || $bytes === false) {
            $this->disconnect($id);
            return;
        }

        if (! $this->clients[$id]['handshake']) {
            $this->doHandshake($id, $buffer);
        } else {
            $payload = $this->unmask($buffer);

            // Try to parse as MVC command
            $data = json_decode($payload, true);

            if ($data && isset($data['controller'], $data['method'])) {
                // Bridge to MVC Framework
                $this->dispatch($id, $data);
            } else {
                // Fallback to your original behavior
                echo "Message from #{$id}: {$payload}" . PHP_EOL;
                $this->send($id, "Server received: " . $payload);
            }
        }
    }

    /**
     * Bridge Method: Connects your Socket to your MVC Controllers
     */
    private function dispatch(int $id, array $data): void
    {
        try {
            // 1. Reset Global Request Context
            // This is critical for WebSockets to prevent User A's data
            // from being visible to User B's request in the same process.
            Request::resetContext();

            $req = Request::getContext();

            $req->controllerDir = APP_DIR . 'wscontrollers/';

            $params = $data['params'] ?? [];
            $headers = $data['headers'] ?? [];

            // 2. Set Virtual Context for the MVC lifecycle
            // We inject the params and potential headers from the socket message
            $req->apimode = true;
            $req->setVirtualContext($params, $headers);

            // 3. Set Partner / Domain Context
            // When using a Reverse Proxy (Nginx/Apache), we check for the forwarded host
            $allHeaders = $req->getHeaders();
            $hostname = $allHeaders->{'X-Forwarded-Host'} ?? $_SERVER['SERVER_NAME'] ?? 'localhost';
            $req->getPartner($hostname);

            // 4. Handle JWT Authentication
            // This uses your existing logic in Request::getPayloadData() to verify the token
            $user = $req->getPayloadData();
            if ($user) {
                $req->user = $user;
                $req->cusertype = $user->perms ?? 'none';
            } else {
                $req->user = null;
                $req->cusertype = 'none';
            }

            // 5. Verify Permissions and Get Controller Instance
            // This matches the 'controller' string to your sys_modules table
            $con = $req->verifyController('', $data['controller']);

            // 6. Verify and Get Method
            // This matches the 'method' string to your sys_methods table
            $met = $req->verifyMethod($con, $data['method']);

            // 7. Call the Controller Method
            // We pass the params array directly to the controller method
            $response = call_user_func_array([
                $con,
                $met
            ], [
                $params
            ]);

            // 8. If controller returns data (array or string), send it back to client
            if ($response !== null) {
                $this->send($id, $response);
            }
        } catch (ApiException $e) {
            // Specifically handle framework-level API exceptions (401, 403, 503, etc.)
            $this->send($id, [
                'status' => 'error',
                'message' => $e->getMessage(),
                'code' => $e->getCode()
            ]);
        } catch (\Exception $e) {
            // Handle unexpected PHP system errors
            echo "Framework Error: " . $e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine() . PHP_EOL;
            $this->send($id, [
                'status' => 'error',
                'message' => 'Internal logic error',
                'code' => 500
            ]);
        }
    }

    /**
     * Sends a message to all connected clients
     * * @param array|string $data The message to send
     *
     * @param int|null $excludeId
     *            The spl_object_id to skip (usually the sender)
     */
    public function broadcast(array|string $data, ?int $excludeId = null): void
    {
        $payload = is_array($data) ? json_encode($data) : $data;
        $maskedData = $this->mask($payload);

        foreach ($this->clients as $id => $client) {
            if ($excludeId !== null && $id === $excludeId) {
                continue;
            }

            // Use @ to suppress notice if a client disconnected during the loop
            @socket_write($client['socket'], $maskedData, strlen($maskedData));
        }
    }

    private function doHandshake(int $id, string $buffer): void
    {
        if (preg_match("/Sec-WebSocket-Key: (.*)\r\n/", $buffer, $match)) {
            $key = base64_encode(sha1($match[1] . '258EAFA5-E914-47DA-95CA-C5AB0DC85B11', true));
            $upgrade = "HTTP/1.1 101 Switching Protocols\r\n" . "Upgrade: websocket\r\n" . "Connection: Upgrade\r\n" . "Sec-WebSocket-Accept: $key\r\n\r\n";

            socket_write($this->clients[$id]['socket'], $upgrade, strlen($upgrade));
            $this->clients[$id]['handshake'] = true;
            echo "Handshake successful for client #{$id}" . PHP_EOL;
        }
    }

    private function send(int $id, array|string $data): void
    {
        // Handle both raw strings and framework arrays
        $text = is_array($data) ? json_encode($data) : $data;
        $response = $this->mask($text);
        @socket_write($this->clients[$id]['socket'], $response, strlen($response));
    }

    private function unmask(string $text): string
    {
        $length = ord($text[1]) & 127;
        if ($length == 126) {
            $masks = substr($text, 4, 4);
            $data = substr($text, 8);
        } elseif ($length == 127) {
            $masks = substr($text, 10, 4);
            $data = substr($text, 14);
        } else {
            $masks = substr($text, 2, 4);
            $data = substr($text, 6);
        }

        $decoded = "";
        for ($i = 0; $i < strlen($data); ++ $i) {
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
            socket_close($this->clients[$id]['socket']);
            unset($this->clients[$id]);
            echo "Client #{$id} disconnected" . PHP_EOL;
        }
    }
}
