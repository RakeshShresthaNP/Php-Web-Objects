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

define('APP_DIR', realpath(dirname(__FILE__)) . '/../app/');

require_once APP_DIR . 'config/config.php';

function db(): Pdo
{
    return DB::getContext();
}

function writeLog(string $type = 'mylog', mixed $msg): void
{
    $file = APP_DIR . 'logs/' . $type . '.txt';
    $datetime = date('Y-m-d H:i:s');
    $logmsg = '###' . $datetime . '### ' . json_encode($msg, JSON_PRETTY_PRINT) . "\r\n";
    file_put_contents($file, $logmsg, FILE_APPEND | LOCK_EX);
}

mb_internal_encoding('UTF-8');
mb_http_output('UTF-8');
mb_regex_encoding('UTF-8');

interface JobHandlerInterface
{
    public function handle(array $payload): void;
}

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
            if (str_starts_with($classname, 'Queue')) {
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

echo "Worker started. Press Ctrl+C to stop.\n";

$worker = new QueueWorker();
$worker->run();

if (memory_get_usage() > 64 * 1024 * 1024) { // 64MB limit
    echo "Memory limit reached. Exiting for restart...\n";
    exit();
}

