<?php

/**
 # Copyright Rakesh Shrestha (rakesh.shrestha@gmail.com)
 # All rights reserved.
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
            'last_seen' => time()
        ];
        echo "New client connected: #{$id}" . PHP_EOL;
    }

    private function process(Socket $socket): void
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
            $data = json_decode($payload, true);

            $this->clients[$id]['last_seen'] = time();

            // Internal Fast-Track Ping
            if ($data && isset($data['method']) && $data['method'] === 'ping') {
                try {
                    DB::getContext();
                } catch (Exception $e) {
                    echo "⚠️ DB Reconnect failed during ping: " . $e->getMessage() . PHP_EOL;
                }
                $this->send($id, [
                    'status' => 'success',
                    'type' => 'pong',
                    'time' => time()
                ]);
                return;
            }

            if ($data && isset($data['controller'], $data['method'])) {
                $this->dispatch($id, $data);
            } else {
                $this->send($id, "Server received: " . $payload);
            }
        }
    }

    private function dispatch(int $id, array $data): void
    {
        try {
            Request::resetContext();
            $req = Request::getContext();
            $req->controllerDir = WS_CONT_DIR;

            // 1. Prepare Virtual Environment
            $params = $data['params'] ?? [];

            /**
             * GLOBAL IP INJECTION:
             * We pull the Real IP captured during doHandshake and inject it into headers.
             * This allows sys_auditlogs to see the real user, not 127.0.0.1.
             */
            $headers = $data['headers'] ?? [];
            $headers['X-Forwarded-For'] = $this->clients[$id]['ip'] ?? '127.0.0.1';

            $req->apimode = true;
            $req->setVirtualContext($params, $headers);

            // 2. Identify Partner/Tenant (Uses mst_partners table)
            $allHeaders = $req->getHeaders();
            $hostname = $allHeaders->{'X-Forwarded-Host'} ?? $_SERVER['SERVER_NAME'] ?? 'localhost';
            $req->getPartner($hostname);

            // 3. Authenticate User (Uses sys_sessions & mst_users)
            $user = $req->getPayloadData();
            if ($user) {
                $req->user = $user;
                $req->cusertype = $user->perms ?? 'none';
            }

            /**
             * 4.
             * Framework Security Verification
             * verifyController checks 'sys_modules'
             * verifyMethod checks 'sys_methods'
             */
            $con = $req->verifyController('', $data['controller']);
            $met = $req->verifyMethod($con, $data['method']);

            /**
             * 5.
             * Call Controller with "Global" Access
             * We pass ($params, $this, $id).
             * This gives the controller the ability to call $server->broadcast()
             */
            $response = call_user_func_array([
                $con,
                $met
            ], [
                $params,
                $this, // Pass the WSSocket instance for global broadcasting
                $id // Pass the unique sender ID
            ]);

            if ($response !== null) {
                $this->send($id, $response);
            }
        } catch (ApiException $e) {
            $this->send($id, [
                'status' => 'error',
                'message' => $e->getMessage(),
                'code' => $e->getCode()
            ]);
        } catch (Exception $e) {
            $this->send($id, [
                'status' => 'error',
                'message' => 'Internal logic error: ' . $e->getMessage(),
                'code' => 500
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
        // Capture Real IP from Proxy (Nginx/Apache)
        $remoteIp = '0.0.0.0';
        if (preg_match("/X-Forwarded-For: (.*)\r\n/", $buffer, $ips)) {
            $remoteIp = trim(explode(',', $ips[1])[0]);
        } elseif (preg_match("/X-Real-IP: (.*)\r\n/", $buffer, $ra)) {
            $remoteIp = trim($ra[1]);
        }

        // Store IP in the client metadata for global use
        $this->clients[$id]['ip'] = $remoteIp;
        $this->clients[$id]['full_headers'] = $buffer;

        if (preg_match("/Sec-WebSocket-Key: (.*)\r\n/", $buffer, $match)) {
            $key = base64_encode(sha1($match[1] . '258EAFA5-E914-47DA-95CA-C5AB0DC85B11', true));
            $upgrade = "HTTP/1.1 101 Switching Protocols\r\n" . "Upgrade: websocket\r\n" . "Connection: Upgrade\r\n" . "Sec-WebSocket-Accept: $key\r\n\r\n";

            socket_write($this->clients[$id]['socket'], $upgrade, strlen($upgrade));
            $this->clients[$id]['handshake'] = true;
            echo "Handshake successful for #{$id} from IP: {$remoteIp}" . PHP_EOL;
        }
    }

    private function send(int $id, array|string $data): void
    {
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
            @socket_close($this->clients[$id]['socket']);
            unset($this->clients[$id]);
            echo "Client #{$id} disconnected" . PHP_EOL;
        }
    }
}
