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

define('SYSTEM_TIMEZONE', 'UTC');

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

date_default_timezone_set(SYSTEM_TIMEZONE);

if (DEBUG) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
    ini_set('display_startup_errors', '1');
} else {
    error_reporting(0);
    ini_set('display_errors', '0');
    ini_set('display_startup_errors', '0');
}

ini_set('log_errors', '1');
ini_set('error_logs', APP_DIR . 'logs/app_errors.log');

ini_set('expose_php', '0');

// Strict timeout: Kill any script taking longer than 10 seconds
set_time_limit(10);

// Limit the memory a single script can use (e.g., 64 Megabytes)
ini_set('memory_limit', '64M');

// Ensure database/file writes finish even if the user closes their tab
ignore_user_abort(true);

// Limit how long PHP spends parsing file uploads
ini_set('max_input_time', '60');

