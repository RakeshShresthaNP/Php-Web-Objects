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
final class Session_Native
{

    public function __construct()
    {
        @session_start();

        if (SESS_TIMEOUT)
            $this->_verifyInactivity(SESS_TIMEOUT);
    }

    public function set(string $key, $value)
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

    private function _verifyInactivity(int $maxtime): void
    {
        if (! $this->get('activity_time')) {
            $this->set('activity_time', time());
        }

        if ((time() - $this->get('activity_time')) > $maxtime) {
            $this->destroy();
        } else {
            $this->set('activity_time', time());
        }
    }
}
