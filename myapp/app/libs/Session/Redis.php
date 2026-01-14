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

final class RedisSessionHandler implements SessionHandlerInterface
{

    private ?Redis $_redis = null;

    private int $_ttl = 0;

    public function __construct()
    {
        if (extension_loaded('Redis')) {
            $this->_redis = new Redis();
            $this->_redis->connect('127.0.0.1', 6379);
            $this->_ttl = SESS_TIMEOUT;
        } else {
            throw new ApiException('Redis not loaded', 404);
        }
    }

    public function open(string $path = '', string $name = ''): bool
    {
        return true;
    }

    public function close(): bool
    {
        return true;
    }

    public function read(string $id): string
    {
        return $this->_redis->get("session:$id");
    }

    public function write(string $id, $data): bool
    {
        return $this->_redis->setex("session:$id", time() + $this->_ttl, $data);
    }

    public function destroy(string $id): bool
    {
        return $this->_redis->del("session:$id");
    }

    public function gc(int $max = 0): int|false
    {
        // Redis handles garbase collection automatically so nothing to be done
        return true;
    }
}

final class Session_Redis
{

    public function __construct()
    {
        $handler = new RedisSessionHandler();
        session_set_save_handler($handler, true);
        @session_start();
    }

    public function set(string $key, $value): void
    {
        $_SESSION[$key] = $value;
    }

    public function get(string $key)
    {
        return isset($_SESSION[$key]) ? $_SESSION[$key] : '';
    }

    public function getId(): string
    {
        return session_id();
    }

    public function delete(string $key): void
    {
        unset($_SESSION[$key]);
    }

    public function destroy(): void
    {
        session_destroy();
    }

    private function _verifyInactivity(int $maxtime = 0)
    {
        if (! $this->get('activity_time')) {
            $this->set('activity_time', time());
        }

        if ((time() - $this->get('activity_time')) > $maxtime) {
            $this->destroy($this->getId());
        } else {
            $this->set('activity_time', time());
        }
    }
}
