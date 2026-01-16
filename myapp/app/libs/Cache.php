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

final class Cache
{
    
    private static $_context = null;
    
    public static function getContext(string $cachetype): object
    {
        if (self::$_context === null) {
            $classname = 'Cache_' . $cachetype;
            self::$_context = new $classname();
        }
        
        return self::$_context;
    }
}
