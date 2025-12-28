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
final class Cache_File
{

    private $_cachedir = '';

    private $_validdir = false;

    private $_lkeydata = array();

    public function __construct()
    {
        $cachedir = APP_DIR . 'logs/';
        if (is_dir($cachedir) && is_writable($cachedir)) {
            $this->_validdir = true;
        }
        $this->_cachedir = $cachedir;
    }

    public function set($key, $data, $ttl = 180)
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
    }

    public function get($key)
    {
        return isset($this->_lkeydata[sha1($key)]) ? $this->_lkeydata[sha1($key)] : null;
    }

    public function valid($key)
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