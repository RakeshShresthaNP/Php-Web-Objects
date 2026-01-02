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
final class Cache_Redis
{

    private ?Redis $_redis = null;

    private array $_lkeydata = array();

    public function __construct()
    {
        $this->_redis = new Redis();
        $this->_redis->connect('127.0.0.1', 6379);
    }

    public function set(string $key, $data, int $ttl = 3600): bool
    {
        return $this->_redis->setex(sha1($key), $ttl, $data);
    }

    public function get(string $key)
    {
        $hashedKey = sha1($key);
        return isset($this->_lkeydata[$hashedKey]) ? $this->_lkeydata[$hashedKey] : '';
    }

    public function valid(string $key): bool
    {
        $data = $this->_redis->get(sha1($key));
        if ($data) {
            $this->_lkeydata[sha1($key)] = (is_array($data)) ? $data[0] : false;
            return true;
        }
    }
}
