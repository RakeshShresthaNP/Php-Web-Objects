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

final class Cache_File
{

    private string $_cachedir = '';

    private bool $_validdir = false;

    private array $_lkeydata = array();

    public function __construct()
    {
        $cachedir = APP_DIR . 'cache/';
        if (is_dir($cachedir) && is_writable($cachedir)) {
            $this->_validdir = true;
        }
        $this->_cachedir = $cachedir;
    }

    public function set(string $key, $data, int $ttl = 180): bool
    {
        if ($this->_validdir) {
            $data = serialize(array(
                time() + $ttl,
                $data
            ));
            $file = $this->_cachedir . sha1($key);
            if (file_exists($file)) {
                unlink($file);
            }

            @file_put_contents($file, $data);
        }
        return $this->_validdir;
    }

    public function get(string $key)
    {
        return isset($this->_lkeydata[sha1($key)]) ? $this->_lkeydata[sha1($key)] : '';
    }

    public function valid(string $key): bool
    {
        if ($this->_validdir) {
            $file1 = glob($this->_cachedir . sha1($key));
            array_shift($file1);
            $filename = $this->_cachedir . sha1($key);
            if (file_exists($filename)) {
                $data1 = @file_get_contents($filename);
                $data = unserialize($data1);

                if (time() > $data[0]) {
                    unlink($filename);
                    return false;
                } else {
                    $this->_lkeydata[sha1($key)] = $data[1];
                    return true;
                }
            }
        }
        return false;
    }
}
