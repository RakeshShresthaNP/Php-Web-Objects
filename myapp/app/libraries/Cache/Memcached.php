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
final class Cache_Memcached
{

    private ?Memcached $_memcached = null;

    private bool $_memcached_enabled = false;

    private array $_lkeydata = array();

    public function __construct()
    {
        $this->_memcached_enabled = extension_loaded('memcached');
        if ($this->_memcached_enabled) {
            $this->_memcached = new Memcached();
            $this->_memcached->addserver('127.0.0.1', 11211);
        }
    }

    public function set(string $key, $data, int $ttl = 3600): bool
    {
        if ($this->_memcached_enabled) {
            return $this->_memcached->set(sha1($key), $data, time() + $ttl);
        }
        return false;
    }

    public function get(string $key)
    {
        $hashedKey = sha1($key);
        return isset($this->_lkeydata[$hashedKey]) ? $this->_lkeydata[$hashedKey] : '';
    }

    public function valid(string $key): bool
    {
        if ($this->_memcached_enabled) {
            $data = $this->_memcached->get(sha1($key));
            if ($data) {
                $this->_lkeydata[sha1($key)] = (is_array($data)) ? $data[0] : false;
                return true;
            }
        }
        return false;
    }
}
