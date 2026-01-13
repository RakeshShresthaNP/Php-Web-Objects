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

define('SYSTEM_TIMEZONE', 'Asia/Kathmandu');

define('DEBUG', '1');

define('DB_CON', serialize(array(
    'mysql',
    'localhost',
    'root',
    '',
    'pwo'
)));

define('PATH_PREFIX', serialize(array(
    'dashboard',
    'manage',
    'api'
)));

define('MAIN_CONTROLLER', 'home');
define('MAIN_METHOD', 'index');

define('CACHE_TYPE', 'NULL');

define('SESS_TIMEOUT', 1800);
define('SESS_TYPE', 'Native');

define('CONT_DIR', APP_DIR . 'controllers/');
define('LIBS_DIR', APP_DIR . 'libs/');
define('VIEW_DIR', APP_DIR . 'views/');
define('MODS_DIR', APP_DIR . 'models/');
define('EVENTS_DIR', APP_DIR . 'events/');
define('QUEUES_DIR', APP_DIR . 'queues/');
