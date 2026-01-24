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

require_once APP_DIR . 'config/config.php';
require_once APP_DIR . 'bootstrap/corefuncs.php';
require_once APP_DIR . 'bootstrap/loader.php';

unset($_REQUEST);

function setCurrentUser(?object &$userdata = null): void
{
    Session::getContext(SESS_TYPE)->set('authUser', $userdata);
}

function getCurrentUser(): ?object
{
    $authUser = Session::getContext(SESS_TYPE)->get('authUser');
    if ($authUser) {
        return $authUser;
    } else {
        return null;
    }
}

function getUrl(string $path = null): string
{
    if (PATH_URI != '/') {
        return SITE_URI . PATH_URI . '/' . $path;
    } else {
        return SITE_URI . '/' . $path;
    }
}

function getRequestIP(): string
{
    $ip = null;

    if (! empty($_SERVER['HTTP_CLIENT_IP'])) {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    } elseif (! empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else {
        $ip = $_SERVER['REMOTE_ADDR'];
    }

    return $ip;
}

function customError(int $errno, string $errstr, string $errfile, int $errline): void
{
    throw new ApiException($errstr . ' ' . $errfile . ' ' . $errline, $errno);
}

set_error_handler("customError");

// begin core classes
final class ApiException extends Exception
{
}

final class Request
{

    public string $pathprefix = '';

    public string $controller = '';

    public string $method = '';

    public ?stdClass $partner = null;

    public ?stdClass $user = null;

    public ?string $cusertype = null;

    public ?bool $apimode = null;

    private static $_context = null;

    public static function getContext(): object
    {
        if (self::$_context === null) {
            self::$_context = new self();
        }

        return self::$_context;
    }

    public function isAjax(): bool
    {
        return ! empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strcasecmp($_SERVER['HTTP_X_REQUESTED_WITH'], 'xmlhttprequest') === 0;
    }

    public function isPost(): bool
    {
        return ($_SERVER['REQUEST_METHOD'] === 'POST');
    }

    public function getPartner(string $hostname = ''): void
    {
        $cache = cache();

        if ($cache->valid($hostname)) {
            $pdata = $cache->get($hostname);
        } else {
            $data = new partner();
            $pdata = $data->getAllConfigByHost($hostname);

            $cache->set($hostname, $pdata);
        }

        $this->partner = $pdata;

        if (! isset($this->partner->id)) {
            throw new Exception('Invalid Domain Name', 503);
        }
    }

    public function getHeaders(): object
    {
        $headers = array();

        foreach ($_SERVER as $param => $value) {
            if (strpos($param, 'HTTP_') === 0) {
                $headerName = mb_substr($param, 5);
                $headerName = str_replace('_', ' ', mb_strtolower($headerName));
                $headerName = str_replace(' ', '-', mb_ucfirst($headerName));
                $headers[$headerName] = $value;
            }
        }

        unset($headers['Cookie']);
        unset($headers['Authorization']);

        return (object) $headers;
    }

    public function getDeviceInfo(): object
    {
        $headers = array();

        $Browser = new DeviceInfo();
        $headers = (object) $Browser->getAll($_SERVER['HTTP_USER_AGENT']);

        return $headers;
    }

    public function getToken(): string|null
    {
        $tokenheader = null;

        if (isset($_SERVER['Authorization'])) {
            $tokenheader = mb_trim($_SERVER['Authorization']);
        } else if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
            $tokenheader = mb_trim($_SERVER['HTTP_AUTHORIZATION']);
        }

        if (! empty($tokenheader)) {

            if (preg_match('/Bearer\s(\S+)/', $tokenheader, $matches)) {
                return $matches[1];
            }
        }

        return $tokenheader;
    }

    public function getPayloadData(): ?object
    {
        $jwt = $this->getToken();

        if (! $jwt) {
            return null;
        }

        if (isset($this->partner->settings[0]->secretkey)) {
            $secret_key = $this->partner->settings[0]->secretkey;
        } else {
            throw new ApiException('Invalid partner setting.', 401);
        }

        // split the token
        $tokenParts = explode('.', $jwt);
        if (count($tokenParts) !== 3) {
            throw new ApiException('Invalid token format.', 401);
        }

        $header = $tokenParts[0];
        $payload = $tokenParts[1];
        $signatureProvided = $tokenParts[2];

        // build a signature based on the header and payload using the secret
        $signature = hash_hmac('sha256', $header . "." . $payload, $secret_key, true);
        $base64UrlSignature = base64_jwt_encode($signature);

        // verify it matches the signature provided in the token
        if (! hash_equals($base64UrlSignature, $signatureProvided)) {
            throw new ApiException('Signature is not valid.', 401);
        }

        // decode and check the expiration time
        $payloadDecoded = base64_jwt_decode($payload);
        $payloadData = json_decode($payloadDecoded);
        if (! $payloadData || ! isset($payloadData->exp)) {
            throw new ApiException('Invalid token payload.', 401);
        }

        if (time() > $payloadData->exp) {
            throw new ApiException('Token has expired.', 401);
        }

        return $payloadData;
    }

    public function verifyController(string $pathprefix = '', string $controllername = ''): object
    {
        $this->pathprefix = mb_strtolower(mb_substr($pathprefix, 0, - 1));
        $this->controller = mb_strtolower($controllername);

        $controllerfile = CONT_DIR . mb_strtolower($this->controller) . '.php';
        if (! preg_match('#^[A-Za-z0-9_-]+$#', $this->controller) || ! is_readable($controllerfile)) {
            $this->controller = MAIN_CONTROLLER;
            $controllerfile = CONT_DIR . MAIN_CONTROLLER . '.php';
        }

        $cache = cache();

        if ($cache->valid($this->controller)) {
            $cdata = $cache->get($this->controller);
        } else {
            $data = new model('sys_modules');
            $cdata = $data->where('c_name', $this->controller)->first();

            $cache->set($this->controller, $cdata);
        }

        if ($cdata->status == 2) {
            if ($this->apimode) {
                throw new ApiException('Module Does not Exist', 503);
            } else {
                throw new Exception('Module Does not Exist', 503);
            }
        }

        $iscontollerallowed = false;

        if ($cdata->perms == "none") {
            $iscontollerallowed = true;
        } else {
            $p1 = explode(",", $this->cusertype);
            $p2 = explode(",", $cdata->perms);

            // array_intersect finds common values between the two arrays
            // If the resulting array is not empty, there is a match
            if (! empty(array_intersect($p1, $p2))) {
                $iscontollerallowed = true;
            } else {
                $iscontollerallowed = false;
            }
        }

        if (! $iscontollerallowed) {
            if ($this->apimode) {
                throw new ApiException('Access to module: ' . $this->controller . ' not allowed!', 503);
            } else {
                res()->redirect('login', '<div class="text-error-500">Access to module: ' . $this->controller . ' not allowed!</div>');
            }
        }

        $cont = 'c' . $this->controller;

        require_once $controllerfile;

        return new $cont();
    }

    public function verifyMethod(&$controller, string $methodname = ''): string
    {
        if (! method_exists($controller, $methodname)) {
            $this->method = MAIN_METHOD;
        } else {
            $this->method = mb_strtolower($methodname);
        }

        $cache = cache();

        if ($cache->valid($this->controller . '_' . $this->method)) {
            $mdata = $cache->get($this->controller . '_' . $this->method);
        } else {
            $data = new model('sys_methods');
            $mdata = $data->where('c_name', $this->controller . '_' . $this->method)->first();

            $cache->set($this->controller . '_' . $this->method, $mdata);
        }

        if ($mdata->status == 2) {
            if ($this->apimode) {
                throw new ApiException('Method Does not Exist', 503);
            } else {
                throw new Exception('Method Does not Exist', 503);
            }
        }

        $ismethodallowed = false;

        if ($mdata->perms == "none") {
            $ismethodallowed = true;
        } else {
            $p1 = explode(",", $this->cusertype);
            $p2 = explode(",", $mdata->perms);

            // array_intersect finds common values between the two arrays
            // If the resulting array is not empty, there is a match
            if (! empty(array_intersect($p1, $p2))) {
                $ismethodallowed = true;
            } else {
                $ismethodallowed = false;
            }
        }

        if (! $ismethodallowed) {
            if ($this->apimode) {
                throw new ApiException('Access to method: ' . $this->controller . '_' . $this->method . ' not allowed!', 503);
            } else {
                res()->redirect('login', '<div class="text-error-500">Access to module: ' . $this->controller . ' not allowed!</div>');
            }
        }

        return $this->method;
    }
}

final class Response
{

    private static $_context = null;

    private array $_statusCode = array(
        200 => "200 OK",
        201 => "201 Resource Created",
        204 => "204 No Content",
        301 => "301 Moved Permanently",
        302 => "302 Found",
        303 => "303 See Other",
        307 => "307 Temporary Redirect",
        400 => "400 Bad Request",
        401 => "401 Unauthorized",
        403 => "403 Forbidden",
        404 => "404 Not Found",
        405 => "405 Method Not Allowed",
        422 => "422 Validation Error",
        429 => "429 Too Many Requests",
        500 => "500 Internal Server Error",
        503 => "503 Service Unavailable"
    );

    public static function getContext(): object
    {
        if (self::$_context === null) {
            self::$_context = new self();
        }

        return self::$_context;
    }

    public function setHeader(string $type = ''): void
    {
        header($type);
    }

    public function setStatus(int $status = 200): void
    {
        http_response_code($status);
    }

    public function redirect(string $path = '', string $alertmsg = ''): void
    {
        if ($alertmsg) {
            $this->addSplashMsg($alertmsg);
        }

        $redir = getUrl($path);

        header("Location: $redir");
        exit();
    }

    public function view(array &$data = array(), string $viewname = ''): void
    {
        View::display($data, $viewname);
    }

    public function json(array &$data = array()): void
    {
        if (! isset($data['code'])) {
            $data['code'] = 200;
        }

        $this->setStatus($data['code']);

        if (! isset($data['data'])) {
            $data['data'] = null;
        }

        if (! isset($data['error'])) {
            $data['error'] = null;
        }

        if (! isset($data['message'])) {
            if ($data['code'] == 200) {
                $data['code'] = 0;
                $data['message'] = 'ok';
            } else if ($data['code'] >= 400 && $data['code'] <= 500) {
                $data['message'] = 'apiexception';
            } else {
                $data['message'] = 'error';
            }
        }

        $this->setHeader('Content-Type: application/json; charset=utf-8');

        echo json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_NUMERIC_CHECK | JSON_INVALID_UTF8_SUBSTITUTE | JSON_FORCE_OBJECT);
    }

    public function assign(array &$data = array(), string $viewname = ''): string
    {
        return View::assign($data, $viewname);
    }

    public static function addSplashMsg(string $msg = ''): void
    {
        Session::getContext(SESS_TYPE)->set('splashmessage', $msg);
    }

    public static function getSplashMsg(): ?string
    {
        $sess = Session::getContext(SESS_TYPE);
        $msg = $sess->get('splashmessage');
        if ($msg) {
            $sess->set('splashmessage', null);
            return $msg;
        } else {
            return null;
        }
    }

    private function _get_status_message(int $code = 200): string
    {
        if (! isset($this->_statusCode[$code])) {
            return $this->_statusCode[500];
        }
        return $this->_statusCode[$code];
    }
}

// begin controller class
abstract class cController
{

    protected DispatcherEvent $dispatcher;

    public ?Request $req = null;

    public ?Response $res = null;

    public ?stdClass $headers = null;

    public ?stdClass $deviceinfo = null;

    public ?stdClass $partner = null;

    public ?stdClass $user = null;

    public ?string $cusertype = null;

    public string $currenthost = '';

    public string $currentuserip = '';

    public function __construct()
    {
        $this->dispatcher = new DispatcherEvent();

        $this->req = req();
        $this->res = res();

        $this->headers = $this->req->getHeaders();
        $this->deviceinfo = $this->req->getDeviceInfo();

        $this->partner = $this->req->partner;

        $this->user = $this->req->user;
        $this->cusertype = isset($this->user->perms) ? $this->user->perms : 'none';

        $this->currenthost = $this->headers->Host;
        $this->currentuserip = getRequestIP();
    }
}

// application bootstrap class
final class Application
{

    public static function process(Request &$request, Response &$response): void
    {
        $request->cusertype = 'none';

        $uriparts = explode('/', str_replace(array(
            SITE_URI . PATH_URI,
            '?' . $_SERVER['QUERY_STRING']
        ), '', SITE_URI . $_SERVER['REQUEST_URI']));
        $uriparts = array_filter($uriparts);

        $request->getPartner($_SERVER['SERVER_NAME']);

        $user = $request->getPayloadData();

        if ($user) {
            $request->user = $user;
            $request->cusertype = $user->perms;
            $request->apimode = true;
        } else {
            $request->user = getCurrentUser();
            if ($request->user) {
                $request->cusertype = $request->user->perms;
            }
            $request->apimode = false;
        }

        $pathPrefixes = unserialize(PATH_PREFIX);

        $request->pathprefix = '';
        $request->controller = ($c = array_shift($uriparts)) ? mb_strtolower($c) : MAIN_CONTROLLER;

        if (in_array($request->controller, $pathPrefixes)) {
            $request->pathprefix = mb_strtolower($request->controller) . '_';
            $request->controller = ($c = array_shift($uriparts)) ? $c : MAIN_CONTROLLER;
        }

        $request->method = ($c = array_shift($uriparts)) ? $request->pathprefix . str_replace($pathPrefixes, '', $c) : $request->pathprefix . MAIN_METHOD;

        $con = $request->verifyController($request->pathprefix, $request->controller);

        $met = $request->verifyMethod($con, $request->method);

        $args = (isset($uriparts[0])) ? $uriparts : array();

        call_user_func_array(array(
            $con,
            $met
        ), $args);
    }
}

