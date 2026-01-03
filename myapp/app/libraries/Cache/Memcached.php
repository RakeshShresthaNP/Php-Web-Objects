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

final class Cache_Memcached
{

    private ?Memcached $_memcached = null;

    private array $_lkeydata = array();

    public function __construct()
    {
        $this->_memcached = new Memcached();
        $this->_memcached->addserver('127.0.0.1', 11211);
    }

    public function set(string $key, $data, int $ttl = 3600): bool
    {
        return $this->_memcached->set(sha1($key), $data, time() + $ttl);
    }

    public function get(string $key)
    {
        $hashedKey = sha1($key);
        return isset($this->_lkeydata[$hashedKey]) ? $this->_lkeydata[$hashedKey] : '';
    }

    public function valid(string $key): bool
    {
        $data = $this->_memcached->get(sha1($key));
        if ($data) {
            $this->_lkeydata[sha1($key)] = $data;
            return true;
        } else {
            return false;
        }
    }
}
