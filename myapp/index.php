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

define('APP_DIR', realpath(dirname(__FILE__)) . '/app/');

$siteuri = $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['SERVER_NAME'];
if ($_SERVER["SERVER_PORT"] != 80 && $_SERVER["SERVER_PORT"] != 443) {
    $siteuri .= ":" . $_SERVER["SERVER_PORT"];
}

define('SITE_URI', $siteuri);
define('PATH_URI', dirname($_SERVER["SCRIPT_NAME"]));

require_once APP_DIR . 'bootstrap/coreclasses.php';

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

    $response->view($data, 'errors/exception');
}

exit();

