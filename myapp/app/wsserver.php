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

require_once APP_DIR . 'bootstrap/coreclasses.php';
require_once APP_DIR . 'bootstrap/WSSocket.php';

define('PWO_START', microtime(true));

$server = new WSSocket('127.0.0.1', 8080);
$server->listen();

