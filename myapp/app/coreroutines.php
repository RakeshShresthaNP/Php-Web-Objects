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
require_once APP_DIR . 'config/config.php';

// begin core functions
function req()
{
    return Request::getContext();
}

function res()
{
    return Response::getContext();
}

function db()
{
    return DB::getContext();
}

function cache()
{
    return Cache::getContext(CACHE_TYPE);
}

function setCurrentUser(array $userdata = array())
{
    Session::getContext(SESS_TYPE)->set('authUser', $userdata);
}

function getCurrentUser()
{
    return Session::getContext(SESS_TYPE)->get('authUser');
}

function getCurrentUserID()
{
    $authUser = getCurrentUser();
    return isset($authUser['id']) ? $authUser['id'] : '';
}

function getCurrentUserType()
{
    $authUser = getCurrentUser();
    return isset($authUser['perms']) ? $authUser['perms'] : '';
}

function getUrl($path = null)
{
    if (PATH_URI != '/') {
        return SITE_URI . PATH_URI . '/' . $path;
    } else {
        return SITE_URI . '/' . $path;
    }
}

function clean($string = null)
{
    return strip_tags(mb_trim($string));
}

function cleanHtml($html = null)
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
        'tbody',
        'thead',
        'th',
        'br',
        'b',
        'i',
        'p'
    );
    return preg_replace_callback('/<\/?([^>\s]+)[^>]*>/i', function ($matches) use ($allowed_tags) {
        return in_array(mb_strtolower($matches[1]), $allowed_tags, true) ? $matches[0] : '';
    }, $html);
}

function getRequestIP()
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

function genUID()
{
    $bytes = random_bytes(16);
    $hex = bin2hex($bytes);
    return mb_substr($hex, 0, 12);
}

function genGUID()
{
    $data = random_bytes(16);
    $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
    $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
}

function createDir($path, $mode = 0777, $rec = true)
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

function writeLog($type = 'mylog', $msg = null)
{
    $file = APP_DIR . 'logs/' . $type . '.txt';
    $datetime = date('Y-m-d H:i:s');
    $logmsg = '###' . $datetime . '### ' . $msg . "\r\n";
    file_put_contents($file, $logmsg, FILE_APPEND | LOCK_EX);
}

mb_internal_encoding('UTF-8');
mb_http_output('UTF-8');
mb_regex_encoding('UTF-8');

if (! function_exists('bool_array_search')) {

    function bool_array_search($string = '', array $aval = array())
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

    function mb_ucwords($str = null)
    {
        return mb_convert_case($str, MB_CASE_TITLE, "UTF-8");
    }
}

if (! function_exists('mb_str_replace')) {

    function mb_str_replace($needle, $replacement, $haystack)
    {
        return str_replace($needle, $replacement, $haystack);
    }
}

if (! function_exists('mb_trim')) {

    function mb_trim($string)
    {
        $string = preg_replace("/(^\s+)|(\s+$)/us", "", $string);

        return $string;
    }
}

function base64_jwt_encode($text)
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

function base64_jwt_decode($text)
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

function url_encode($string = null)
{
    return urlencode($string);
}

function url_decode($string = null)
{
    return urldecode($string);
}

function my_mime_content_type($filename)
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

    function array_map_recursive($arr, $fn)
    {
        $rarr = array();
        foreach ($arr as $k => $v) {
            $rarr[$k] = is_array($v) ? array_map_recursive($v, $fn) : $fn($v);
        }
        return $rarr;
    }
}

function customError($errno, $errstr, $errfile, $errline)
{
    $emsg = "";
    $emsg .= "<div class='error' style='text-align:left'>";
    $emsg .= "<b>Custom error:</b> [$errno] $errstr<br />";
    $emsg .= "Error on line $errline in $errfile<br />";
    $emsg .= "Ending Script";
    $emsg .= "</div>";
    writeLog('error_' . date('Y_m_d'), $emsg);
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

    public static function load($classname)
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

    public static function getContext($cachetype)
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

    public static function getContext()
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
            throw $ex;
        }

        return self::$_context;
    }
}

final class Session
{

    private static $_context = null;

    public static function getContext($sesstype)
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

    public static function assign(array &$vars = array(), $viewname = null)
    {
        $req = req();
        if (is_array($vars)) {
            extract($vars);
        }
        ob_start();
        if ($viewname == null) {
            $viewname = mb_strtolower($req->getController() . '/' . $req->getMethod());
        }
        include VIEW_DIR . mb_strtolower($viewname) . '.php';
        return ob_get_clean();
    }

    public static function display(array &$vars = array(), $viewname = null)
    {
        $req = req();
        if ($viewname == null) {
            $viewname = mb_strtolower($req->getController() . '/' . $req->getMethod());
        }
        if (! isset($vars['layout'])) {
            $playout = 'layouts/' . $req->getPathPrefix() . 'layout';
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

    private $_pathprefix = null;

    private $_controller = null;

    private $_method = null;

    private static $_context = null;

    public static function getContext()
    {
        if (self::$_context === null) {
            self::$_context = new self();
        }

        return self::$_context;
    }

    public function isAjax()
    {
        return ! empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strcasecmp($_SERVER['HTTP_X_REQUESTED_WITH'], 'xmlhttprequest') === 0;
    }

    public function isPost()
    {
        return ($_SERVER['REQUEST_METHOD'] === 'POST');
    }

    public function getPathPrefix()
    {
        return $this->_pathprefix;
    }

    public function getController()
    {
        return mb_strtolower($this->_controller);
    }

    public function getMethod()
    {
        return mb_strtolower($this->_method);
    }

    public function getHeaders()
    {
        $headers = array();

        $Browser = new DeviceInfo();
        $headers = (object) $Browser->getAll($_SERVER['HTTP_USER_AGENT']);

        return $headers;
    }

    public function getToken()
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

    public function getPayloadData()
    {
        $jwt = $this->getToken();

        if (! $jwt) {
            throw new ApiException('Invalid Access.', 403);
        }

        // split the token
        $tokenParts = explode('.', $jwt);
        if (count($tokenParts) !== 3) {
            throw new ApiException('Invalid token format.', 403);
        }

        $header = $tokenParts[0];
        $payload = $tokenParts[1];
        $signatureProvided = $tokenParts[2];

        // build a signature based on the header and payload using the secret
        $signature = hash_hmac('sha256', $header . "." . $payload, SECRET_KEY, true);
        $base64UrlSignature = base64_jwt_encode($signature);

        // verify it matches the signature provided in the token
        if (! hash_equals($base64UrlSignature, $signatureProvided)) {
            throw new ApiException('Signature is not valid.', 403);
        }

        // decode and check the expiration time
        $payloadDecoded = base64_jwt_decode($payload);
        $payloadData = json_decode($payloadDecoded);
        if (! $payloadData || ! isset($payloadData->exp)) {
            throw new ApiException('Invalid token payload.', 403);
        }

        return $payloadData;
    }

    public function setPathPrefix($pathprefix = null)
    {
        $this->_pathprefix = $pathprefix;
    }

    public function setController($controllername = null)
    {
        $this->_controller = mb_strtolower($controllername);
    }

    public function setMethod($methodname = null)
    {
        $this->_method = mb_strtolower($methodname);
    }
}

final class Response
{

    private static $_context = null;

    private $_statusCode = array(
        200 => "200 OK",
        301 => "301 Moved Permanently",
        302 => "302 Found",
        303 => "303 See Other",
        307 => "307 Temporary Redirect",
        400 => "400 Bad Request",
        403 => "403 Forbidden",
        404 => "404 Not Found",
        405 => "405 Method Not Allowed",
        500 => "500 Internal Server Error",
        503 => "503 Service Unavailable"
    );

    public static function getContext()
    {
        if (self::$_context === null) {
            self::$_context = new self();
        }

        return self::$_context;
    }

    public function setHeader($type = '')
    {
        header($type);
    }

    public function setStatus($status = 200)
    {
        http_response_code($status);
    }

    public function redirect($path = null, $alertmsg = null)
    {
        if ($alertmsg) {
            $this->addSplashMsg($alertmsg);
        }

        $redir = getUrl($path);

        header("Location: $redir");
        exit();
    }

    public function display(array &$data = array(), $viewname = null)
    {
        View::display($data, $viewname);
    }

    public function json(array &$data = array())
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
                $data['message'] = 'ok';
            } else if ($data['code'] >= 400 && $data['code'] <= 500) {
                $data['message'] = 'apiexception';
            } else {
                $data['message'] = 'error';
            }
        }

        $this->setHeader('Content-Type: application/json; charset=utf-8');

        echo json_encode($data, JSON_UNESCAPED_SLASHES);
    }

    public function assign(array &$data = array(), $viewname = null)
    {
        return View::assign($data, $viewname);
    }

    public static function addSplashMsg($msg = null)
    {
        Session::getContext(SESS_TYPE)->set('splashmessage', $msg);
    }

    public static function getSplashMsg()
    {
        $sess = Session::getContext(SESS_TYPE);
        $msg = $sess->get('splashmessage');
        $sess->set('splashmessage', null);
        return $msg;
    }

    private function _get_status_message($code)
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

    public $req = null;

    public $res = null;

    public $headers = null;

    public function __construct()
    {
        $this->req = req();
        $this->res = res();

        $this->headers = $this->req->getHeaders();
    }
}

abstract class cAuthController extends cController
{

    public $user = null;

    public function __construct()
    {
        parent::__construct();

        $payloadData = $this->req->getPayloadData();

        if (time() > $payloadData->exp) {
            throw new ApiException('Token has expired.');
        }

        $this->user = $payloadData;
    }
}

abstract class cAdminController extends cController
{

    public $user = null;

    public function __construct()
    {
        parent::__construct();

        $pathprefix = $this->req->getPathPrefix();

        $this->user = getCurrentUser();

        $cusertype = getCurrentUserType();

        if ($pathprefix == 'manage' && $cusertype != 'superadmin') {
            $this->res->redirect('login', 'Invalid Access');
        }

        if ($pathprefix == 'dashboard' && (empty($cusertype) || $cusertype == 'superadmin')) {
            $this->res->redirect('login', 'Invalid Access');
        }
    }
}

// application bootstrap class
final class Application
{

    public static function run(Request &$request, Response &$response)
    {
        $uriparts = explode('/', mb_str_replace(SITE_URI . PATH_URI, '', SITE_URI . $_SERVER['REQUEST_URI']));
        $uriparts = array_filter($uriparts);

        $controller = ($c = array_shift($uriparts)) ? $c : MAIN_CONTROLLER;
        $pathprefix = '';

        if (in_array($controller, unserialize(PATH_PREFIX))) {
            $pathprefix = mb_strtolower($controller) . '_';
            $controller = ($c = array_shift($uriparts)) ? $c : MAIN_CONTROLLER;
        }

        $controllerfile = CONT_DIR . mb_strtolower($controller) . '.php';
        if (! preg_match('#^[A-Za-z0-9_-]+$#', $controller) || ! is_readable($controllerfile)) {
            $controller = MAIN_CONTROLLER;
            $controllerfile = CONT_DIR . MAIN_CONTROLLER . '.php';
        }

        $cont = 'c' . $controller;
        $pathPrefixes = unserialize(PATH_PREFIX);
        $method = ($c = array_shift($uriparts)) ? $pathprefix . mb_str_replace($pathPrefixes, '', $c) : $pathprefix . MAIN_METHOD;
        $args = (isset($uriparts[0])) ? $uriparts : array();

        require_once $controllerfile;

        $cont = new $cont();

        if (! method_exists($cont, $method)) {
            $method = MAIN_METHOD;
        }

        $request->setPathPrefix(mb_substr($pathprefix, 0, - 1));
        $request->setController($controller);
        $request->setMethod($method);

        call_user_func_array(array(
            $cont,
            $method
        ), $args);
    }
}
