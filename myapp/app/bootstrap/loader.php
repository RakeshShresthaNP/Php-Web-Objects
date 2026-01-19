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

spl_autoload_extensions('.php');
spl_autoload_register(array(
    'Loader',
    'load'
));

final class Loader
{

    public static function load(string $classname): void
    {
        $a = $classname[0];

        if ($a >= 'A' && $a <= 'Z') {
            if (str_starts_with($classname, 'Event')) {
                require_once EVENTS_DIR . mb_strtolower($classname) . '.php';
            } else if (str_starts_with($classname, 'Helper')) {
                require_once HELPERS_DIR . mb_strtolower($classname) . '.php';
            } else if (str_starts_with($classname, 'Queue')) {
                require_once QUEUES_DIR . mb_strtolower($classname) . '.php';
            } else {
                require_once LIBS_DIR . str_replace([
                    '\\',
                    '_'
                ], '/', $classname) . '.php';
            }
        } else {
            require_once MODS_DIR . mb_strtolower($classname) . '.php';
        }
    }
}
