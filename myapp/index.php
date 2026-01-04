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

$time = microtime(true);

define('APP_DIR', realpath(dirname(__FILE__)) . '/app/');

require_once APP_DIR . 'coreroutines.php';

date_default_timezone_set(SYSTEM_TIMEZONE);

if (DEBUG) {
    error_reporting(E_ALL);
} else {
    error_reporting(0);
}

$request = req();
$response = res();

try {
    Application::process($request, $response);
} catch (ApiException $e) {
    $data['code'] = $e->getCode();
    $data['error'] = $e->getMessage();

    writeLog('apiexception_' . date('Y_m_d'), $data);

    $response->json($data);
} catch (Exception $e) {
    $data['message'] = $e->getMessage();

    if ($request->isAjax()) {
        $data['layout'] = false;
    }

    writeLog('exception_' . date('Y_m_d'), $data);

    $response->display($data, 'errors/exception');
}

echo sprintf("Execution time: %f seconds\nMemory usage: %f MB\n\n", microtime(true) - $time, memory_get_usage(true) / 1024 / 1024);

