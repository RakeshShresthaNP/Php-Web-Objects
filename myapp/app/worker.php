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

define('APP_DIR', realpath(dirname(__FILE__)) . '/');

require_once APP_DIR . 'config/config.php';
require_once APP_DIR . 'bootstrap/corefuncs.php';
require_once APP_DIR . 'bootstrap/loader.php';

interface JobHandlerInterface
{

    public function handle(array $payload): void;
}

echo "Worker started. Press Ctrl+C to stop.\n";

$worker = new QueueWorker();
$worker->process();

if (memory_get_usage() > 64 * 1024 * 1024) { // 64MB limit
    echo "Memory limit reached. Exiting for restart...\n";
    exit();
}

