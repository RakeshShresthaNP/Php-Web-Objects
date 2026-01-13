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

final class Cache_NULL
{

    public function __construct()
    {}

    public function set(string $key, $data, int $ttl = 180): bool
    {
        return true;
    }

    public function get(string $key)
    {
        return '';
    }

    public function valid(string $key): bool
    {
        return false;
    }
}
