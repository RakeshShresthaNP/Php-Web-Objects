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
require_once APP_DIR . 'myfuncs.php';

// begin core functions
function req(): Request
{
    return Request::getContext();
}

function res(): Response
{
    return Response::getContext();
}

function db(): Pdo
{
    return DB::getContext();
}

function cache(): object
{
    return Cache::getContext(CACHE_TYPE);
}

function setCurrentUser(?user &$userdata = null): void
{
    Session::getContext(SESS_TYPE)->set('authUser', $userdata?->getData());
}

function getCurrentUser(): ?object
{
    $authUser = Session::getContext(SESS_TYPE)->get('authUser');
    if ($authUser) {
        return (object) $authUser;
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

function getDataDiff(array &$arr1, array &$arr2): array
{
    $changes = [];

    foreach ($arr1 as $key => $val) {
        if ($val != $arr2[$key]) {
            $changes[$key] = [
                'from' => $val,
                'to' => $arr2[$key]
            ];
        }
    }

    return $changes;
}

function clean(string $string = null): string
{
    return strip_tags(mb_trim($string));
}

function cleanHtml(mixed $html = ''): mixed
{
    static $allowed_tags = array(
        'a',
        'em',
        'strong',
        'cite',
        'code',
        'ul',
        'ol',
        'li',
        'dl',
        'dt',
        'dd',
        'table',
        'tr',
        'td',
        'br',
        'b',
        'i',
        'p'
    );

    if (is_string($html)) {
        return strip_tags($html, $allowed_tags);
    } else {
        return $html;
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

function genUID(): string
{
    $bytes = random_bytes(16);
    $hex = bin2hex($bytes);
    return mb_substr($hex, 0, 12);
}

function genGUID(): string
{
    $data = random_bytes(16);
    $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
    $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
}

function createDir(string $path, $mode = 0777, bool $rec = true): bool
{
    if (! is_dir($path)) {
        $oldumask = umask(0);
        if (! mkdir($path, $mode, $rec)) {
            umask($oldumask);
            return false;
        }
        umask($oldumask);
    }
    return true;
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

if (! function_exists('bool_array_search')) {

    function bool_array_search(string $string = '', array &$aval = array()): bool
    {
        foreach ($aval as $val) {
            if (strstr($val, "'" . $string . "'")) {
                return true;
            }
        }
        return false;
    }
}

if (! function_exists('mb_ucwords')) {

    function mb_ucwords(string $str = ''): string
    {
        return mb_convert_case($str, MB_CASE_TITLE, "UTF-8");
    }
}

if (! function_exists('mb_str_replace')) {

    function mb_str_replace($needle, $replacement, $haystack): string
    {
        return str_replace($needle, $replacement, $haystack);
    }
}

if (! function_exists('mb_trim')) {

    function mb_trim(string $string): string
    {
        return preg_replace("/(^\s+)|(\s+$)/us", "", $string);
    }
}

function base64_jwt_encode(string $text): string
{
    return mb_str_replace([
        '+',
        '/',
        '='
    ], [
        '-',
        '_',
        ''
    ], base64_encode($text));
}

function base64_jwt_decode(string $text): string
{
    $data = mb_str_replace([
        '-',
        '_'
    ], [
        '+',
        '/'
    ], $text);
    $mod4 = strlen($data) % 4;
    if ($mod4) {
        $data .= mb_substr('====', $mod4);
    }
    return base64_decode($data);
}

function url_encode(string $string = ''): string
{
    return urlencode($string);
}

function url_decode(string $string = ''): string
{
    return urldecode($string);
}

function my_mime_content_type(string $filename): string
{
    $mime_types = array(
        'txt' => 'text/plain',
        'htm' => 'text/html',
        'html' => 'text/html',
        'php' => 'text/html',
        'css' => 'text/css',
        'js' => 'application/javascript',
        'json' => 'application/json',
        'xml' => 'application/xml',
        'swf' => 'application/x-shockwave-flash',
        'flv' => 'video/x-flv',
        // images
        'png' => 'image/png',
        'jpe' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'jpg' => 'image/jpeg',
        'gif' => 'image/gif',
        'bmp' => 'image/bmp',
        'ico' => 'image/vnd.microsoft.icon',
        'tiff' => 'image/tiff',
        'tif' => 'image/tiff',
        'svg' => 'image/svg+xml',
        'svgz' => 'image/svg+xml',
        // archives
        'zip' => 'application/zip',
        'rar' => 'application/x-rar-compressed',
        'exe' => 'application/x-msdownload',
        'msi' => 'application/x-msdownload',
        'cab' => 'application/vnd.ms-cab-compressed',
        // audio/video
        'mp3' => 'audio/mpeg',
        'qt' => 'video/quicktime',
        'mov' => 'video/quicktime',
        // adobe
        'pdf' => 'application/pdf',
        'psd' => 'image/vnd.adobe.photoshop',
        'ai' => 'application/postscript',
        'eps' => 'application/postscript',
        'ps' => 'application/postscript',
        // ms office
        'doc' => 'application/msword',
        'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'rtf' => 'application/rtf',
        'xls' => 'application/vnd.ms-excel',
        'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'ppt' => 'application/vnd.ms-powerpoint',
        'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        // open office
        'odt' => 'application/vnd.oasis.opendocument.text',
        'ods' => 'application/vnd.oasis.opendocument.spreadsheet'
    );

    $temporary = explode(".", $filename);
    $ext = mb_strtolower(end($temporary));

    if (array_key_exists($ext, $mime_types)) {
        return $mime_types[$ext];
    } else {
        return 'application/octet-stream';
    }
}

if (! function_exists('array_map_recursive')) {

    function array_map_recursive(array &$arr, string $fn): array
    {
        $rarr = array();
        foreach ($arr as $k => $v) {
            $rarr[$k] = is_array($v) ? array_map_recursive($v, $fn) : $fn($v);
        }
        return $rarr;
    }
}

function customError(int $errno, string $errstr, string $errfile, int $errline): void
{
    throw new ApiException($errstr . ' ' . $errfile . ' ' . $errline, $errno);
}

set_error_handler("customError");

// begin core classes
unset($_REQUEST);

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
            require_once LIBS_DIR . mb_str_replace([
                '\\',
                '_'
            ], '/', $classname) . '.php';
        } else {
            require_once MODS_DIR . mb_strtolower($classname) . '.php';
        }
    }
}

final class ApiException extends Exception
{
}

final class Cache
{

    private static $_context = null;

    public static function getContext(string $cachetype): object
    {
        if (self::$_context === null) {
            $classname = 'Cache_' . $cachetype;
            self::$_context = new $classname();
        }

        return self::$_context;
    }
}

final class DB
{

    private static $_context = null;

    public static function getContext(): object
    {
        if (self::$_context) {
            return self::$_context;
        }

        list ($dbtype, $host, $user, $pass, $dbname) = unserialize(DB_CON);

        $dsn = $dbtype . ':host=' . $host . ';dbname=' . $dbname;

        try {
            self::$_context = new PDO($dsn, $user, $pass);
            self::$_context->exec('SET NAMES utf8');
            self::$_context->setAttribute(PDO::ATTR_PERSISTENT, true);
            self::$_context->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_OBJ);
            self::$_context->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            self::$_context->setAttribute(PDO::ATTR_EMULATE_PREPARES, true);
        } catch (PDOException $ex) {
            throw new Exception($ex->getMessage(), $ex->getCode());
        }

        return self::$_context;
    }
}

final class Session
{

    private static $_context = null;

    public static function getContext(string $sesstype): object
    {
        if (self::$_context === null) {
            $classname = 'Session_' . $sesstype;
            self::$_context = new $classname();
        }

        return self::$_context;
    }
}

final class View
{

    public static function assign(array &$vars = array(), string $viewname = ''): string
    {
        $req = req();
        if (is_array($vars)) {
            extract($vars);
        }
        ob_start();
        if ($viewname == null) {
            $viewname = $req->controller . '/' . $req->method;
        }
        include VIEW_DIR . mb_strtolower($viewname) . '.php';
        return ob_get_clean();
    }

    public static function display(array &$vars = array(), string $viewname = ''): void
    {
        $req = req();
        if ($viewname == null) {
            $viewname = mb_strtolower($req->controller . '/' . $req->method);
        }
        if (! isset($vars['layout'])) {
            $playout = 'layouts/' . $req->pathprefix . 'layout';
            $vars['mainregion'] = self::assign($vars, $viewname);
        } else {
            if ($vars['layout']) {
                $playout = $vars['layout'];
            } else {
                $playout = $viewname;
            }
        }
        if (is_array($vars)) {
            extract($vars);
        }
        include VIEW_DIR . mb_strtolower($playout) . '.php';
    }
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
                $headerName = mb_str_replace('_', ' ', mb_strtolower($headerName));
                $headerName = mb_str_replace(' ', '-', mb_ucwords($headerName));
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
            $cdata = $data->where('c_name', $this->controller)->find();

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
                res()->redirect('login', '<div style="font-size:13px; color:#ff0000; margin-bottom:4px; margin-top:8px;">Access to module: ' . $this->controller . ' not allowed!</div>');
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
            $mdata = $data->where('c_name', $this->controller . '_' . $this->method)->find();

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
                res()->redirect('login', '<div style="font-size:13px; color:#ff0000; margin-bottom:4px; margin-top:8px;">Access to module: ' . $this->controller . ' not allowed!</div>');
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

        $uriparts = explode('/', mb_str_replace(array(
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

        $request->method = ($c = array_shift($uriparts)) ? $request->pathprefix . mb_str_replace($pathPrefixes, '', $c) : $request->pathprefix . MAIN_METHOD;

        $con = $request->verifyController($request->pathprefix, $request->controller);

        $met = $request->verifyMethod($con, $request->method);

        $args = (isset($uriparts[0])) ? $uriparts : array();

        call_user_func_array(array(
            $con,
            $met
        ), $args);
    }
}

