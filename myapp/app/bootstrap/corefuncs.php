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

function clean(string $string = ''): string
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

function links(object $meta): string
{
    if ($meta->total_pages <= 1)
        return '';

    $html = '<nav aria-label="Page navigation"><ul class="pagination">';

    // 1. Get current URL parameters
    $params = $_GET;

    // 2. Ensure 'perpage' is explicitly set in the parameters from meta
    $params['perpage'] = $meta->per_page;

    // --- Previous Button ---
    $prevClass = ($meta->current_page <= 1) ? 'disabled' : '';
    $params['page'] = max(1, $meta->current_page - 1);
    $prevUrl = '?' . http_build_query($params);
    $html .= "<li class='page-item {$prevClass}'><a class='page-link' href='{$prevUrl}'>Previous</a></li>";

    // --- Page Numbers ---
    for ($i = 1; $i <= $meta->total_pages; $i ++) {
        $activeClass = ($i === $meta->current_page) ? 'active' : '';

        // Update current page for the loop
        $params['page'] = $i;
        $url = '?' . http_build_query($params);

        $html .= "<li class='page-item {$activeClass}'><a class='page-link' href='{$url}'>{$i}</a></li>";
    }

    // --- Next Button ---
    $nextClass = ($meta->current_page >= $meta->total_pages) ? 'disabled' : '';
    $params['page'] = min($meta->total_pages, $meta->current_page + 1);
    $nextUrl = '?' . http_build_query($params);
    $html .= "<li class='page-item {$nextClass}'><a class='page-link' href='{$nextUrl}'>Next</a></li>";

    $html .= '</ul></nav>';

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
