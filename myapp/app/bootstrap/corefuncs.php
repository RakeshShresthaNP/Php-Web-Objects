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

mb_internal_encoding('UTF-8');
mb_http_output('UTF-8');
mb_regex_encoding('UTF-8');

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

function getRequestData(): array
{
    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';

    // Clean the content type (removes ;charset=utf-8)
    $cleanType = strtolower(explode(';', $contentType)[0]);

    if ($cleanType === 'application/json') {
        $raw = file_get_contents('php://input');
        return json_decode($raw, true) ?? [];
    }

    return $_POST;
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

function _t(string $key): string
{
    static $translations = null;

    if ($translations === null) {
        $lang = $_SESSION['lang'] ?? DEFAULT_LANG;
        $file = APP_DIR . 'lang/' . $lang . '.php';

        if (file_exists($file)) {
            $translations = require $file;
        } else {
            $fallback = APP_DIR . 'lang/en.php';
            $translations = file_exists($fallback) ? require $fallback : [];
        }
    }

    return $translations[$key] ?? $key;
}

function _t_json(array $keys): string
{
    $translations = [];
    foreach ($keys as $key) {
        $translations[$key] = _t($key);
    }
    return json_encode($translations, JSON_UNESCAPED_UNICODE);
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

function writeLog(string $type = 'mylog', mixed $msg = ''): void
{
    $file = APP_DIR . 'logs/' . $type . '.txt';
    $datetime = date('Y-m-d H:i:s');
    $logmsg = '###' . $datetime . '### ' . json_encode($msg, JSON_PRETTY_PRINT) . "\r\n";
    file_put_contents($file, $logmsg, FILE_APPEND | LOCK_EX);
}

function links(object $meta): string
{
    if ($meta->total_pages <= 1)
        return '';

    // Container matching your dark theme
    $html = '<nav aria-label="Pagination" class="flex items-center gap-2">';

    // 1. Get current URL parameters
    $params = $_GET;

    // 2. Explicitly set perpage
    $params['perpage'] = $meta->per_page;

    // --- Previous Button ---
    $isFirst = ($meta->current_page <= 1);
    $params['page'] = max(1, $meta->current_page - 1);
    $prevUrl = '?' . http_build_query($params);

    $prevClass = $isFirst ? 'opacity-20 cursor-not-allowed pointer-events-none' : 'hover:bg-white/10 hover:text-white border-white/10';

    $html .= "<a href='{$prevUrl}' class='flex items-center justify-center w-10 h-10 rounded-xl border text-gray-400 transition-all {$prevClass}'>
                <svg class='w-5 h-5' fill='none' stroke='currentColor' viewBox='0 0 24 24'>
                    <path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M15 19l-7-7 7-7'></path>
                </svg>
              </a>";

    // --- Page Numbers ---
    for ($i = 1; $i <= $meta->total_pages; $i ++) {
        $isActive = ($i === $meta->current_page);

        $params['page'] = $i;
        $url = '?' . http_build_query($params);

        if ($isActive) {
            // Active page: Your brand blue
            $html .= "<span class='flex items-center justify-center w-10 h-10 rounded-xl bg-[#4d7cfe] text-white font-bold shadow-lg shadow-blue-500/30'>{$i}</span>";
        } else {
            // Inactive page: Subtle border
            $html .= "<a href='{$url}' class='flex items-center justify-center w-10 h-10 rounded-xl border border-white/5 text-gray-400 hover:bg-white/10 hover:text-white transition-all font-medium'>{$i}</a>";
        }
    }

    // --- Next Button ---
    $isLast = ($meta->current_page >= $meta->total_pages);
    $params['page'] = min($meta->total_pages, $meta->current_page + 1);
    $nextUrl = '?' . http_build_query($params);

    $nextClass = $isLast ? 'opacity-20 cursor-not-allowed pointer-events-none' : 'hover:bg-white/10 hover:text-white border-white/10';

    $html .= "<a href='{$nextUrl}' class='flex items-center justify-center w-10 h-10 rounded-xl border text-gray-400 transition-all {$nextClass}'>
                <svg class='w-5 h-5' fill='none' stroke='currentColor' viewBox='0 0 24 24'>
                    <path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M9 5l7 7-7 7'></path>
                </svg>
              </a>";

    $html .= '</nav>';

    return $html;
}

function base64_jwt_encode(string $text): string
{
    return str_replace([
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
    $data = str_replace([
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
