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

final class PdoSessionHandler implements SessionHandlerInterface
{

    private ?Pdo $_db = null;

    public function __construct()
    {
        $this->_db = db();
    }

    public function open(string $path = '', string $name = ''): bool
    {
        if ($this->_db) {
            return true;
        }
        return false;
    }

    public function close(): bool
    {
        return true;
    }

    public function read(string $id): string
    {
        $stmt = $this->_db->prepare('SELECT sdata FROM sys_sessions WHERE id = ? ');
        $stmt->bindValue(1, $id);
        $stmt->execute();

        if ($stmt->rowCount() == 1) {
            $row = $stmt->fetch();
            return $row->sdata;
        } else {
            return '';
        }
    }

    public function write(string $id, $data): bool
    {
        $access = time();

        $stmt = $this->_db->prepare('REPLACE INTO sys_sessions VALUES (?, ?, ?) ');
        $stmt->bindValue(1, $id);
        $stmt->bindValue(2, $data);
        $stmt->bindValue(3, $access);

        if ($stmt->execute()) {
            return true;
        }
        return false;
    }

    public function destroy(string $id): bool
    {
        $stmt = $this->_db->prepare('DELETE FROM sys_sessions WHERE id = ? ');
        $stmt->bindValue(1, $id);

        session_regenerate_id(TRUE);

        $_SESSION = array();

        if ($stmt->execute()) {
            return true;
        }
        return false;
    }

    public function gc(int $max = 0): int|false
    {
        $old = time() - $max;

        // Fixed SQL syntax: DELETE doesn't use * (that's SELECT syntax)
        $stmt = $this->_db->prepare('DELETE FROM sys_sessions WHERE lastaccessed < ? ');
        $stmt->bindValue(1, $old);

        if ($stmt->execute()) {
            return true;
        }
        return false;
    }
}

final class Session_Database
{

    public function __construct()
    {
        $handler = new PdoSessionHandler();
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
