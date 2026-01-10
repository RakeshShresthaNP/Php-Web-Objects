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

if ($_SERVER['SERVER_NAME'] == 'localhost') {
    define('DEBUG', '1');
    define('DB_CON', serialize(array(
        'mysql',
        'localhost',
        'root',
        '',
        'pwo'
    )));
} else {
    define('DEBUG', '0');
    define('DB_CON', serialize(array(
        'mysql',
        'localhost',
        '',
        '',
        ''
    )));
}

$siteuri = $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['SERVER_NAME'];
if ($_SERVER["SERVER_PORT"] != 80 && $_SERVER["SERVER_PORT"] != 443) {
    $siteuri .= ":" . $_SERVER["SERVER_PORT"];
}

define('SITE_URI', $siteuri);
define('PATH_URI', dirname($_SERVER["SCRIPT_NAME"]));

define('PATH_PREFIX', serialize(array(
    'dashboard',
    'manage',
    'api'
)));

define('MAIN_CONTROLLER', 'home');
define('MAIN_METHOD', 'index');

define('CACHE_TYPE', 'Redis');

define('SESS_TIMEOUT', 1800);
define('SESS_TYPE', 'Memcached');

define('CONT_DIR', APP_DIR . 'controllers/');
define('LIBS_DIR', APP_DIR . 'libraries/');
define('VIEW_DIR', APP_DIR . 'views/');
define('MODS_DIR', APP_DIR . 'models/');
