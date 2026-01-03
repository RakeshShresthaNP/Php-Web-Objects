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

final class S3
{

    // ACL flags
    public const ACL_PRIVATE = 'private';

    public const ACL_PUBLIC_READ = 'public-read';

    public const ACL_PUBLIC_READ_WRITE = 'public-read-write';

    public const ACL_AUTHENTICATED_READ = 'authenticated-read';

    public const STORAGE_CLASS_STANDARD = 'STANDARD';

    public const STORAGE_CLASS_RRS = 'REDUCED_REDUNDANCY';

    public const STORAGE_CLASS_STANDARD_IA = 'STANDARD_IA';

    public const SSE_NONE = '';

    public const SSE_AES256 = 'AES256';

    private static ?string $__accessKey = null;

    private static ?string $__secretKey = null;

    public static ?string $defDelimiter = null;

    public static string $endpoint = 's3.amazonaws.com';

    public static string $region = '';

    public static ?array $proxy = null;

    public static bool $useSSL = false;

    public static bool $useSSLValidation = true;

    public static int $useSSLVersion = CURL_SSLVERSION_TLSv1;

    public static bool $useExceptions = false;

    private static int $__timeOffset = 0;

    public static ?string $sslKey = null;

    public static ?string $sslCert = null;

    public static ?string $sslCACert = null;

    private static ?string $__signingKeyPairId = null;

    /** @var \OpenSSLAsymmetricKey|resource|bool */
    private static $__signingKeyResource = false;

    public static $progressFunction = null;

    public function __construct(?string $accessKey = null, ?string $secretKey = null, bool $useSSL = false, string $endpoint = 's3.amazonaws.com', string $region = '')
    {
        if ($accessKey !== null && $secretKey !== null)
            self::setAuth($accessKey, $secretKey);
        self::$useSSL = $useSSL;
        self::$endpoint = $endpoint;
        self::$region = $region;
    }

    public function setEndpoint(string $host): void
    {
        self::$endpoint = $host;
    }

    public function setRegion(string $region): void
    {
        self::$region = $region;
    }

    public static function getRegion(): string
    {
        $region = self::$region;
        if (empty($region)) {
            if (preg_match("/s3[.-](?:website-|dualstack\.)?(.+)\.amazonaws\.com/i", self::$endpoint, $match) !== 0 && strtolower($match[1]) !== "external-1") {
                $region = $match[1];
            }
        }
        return empty($region) ? 'us-east-1' : $region;
    }

    public static function setAuth(string $accessKey, string $secretKey): void
    {
        self::$__accessKey = $accessKey;
        self::$__secretKey = $secretKey;
    }

    public static function hasAuth(): bool
    {
        return (self::$__accessKey !== null && self::$__secretKey !== null);
    }

    public static function setSSL(bool $enabled, bool $validate = true): void
    {
        self::$useSSL = $enabled;
        self::$useSSLValidation = $validate;
    }

    public static function setSSLAuth(?string $sslCert = null, ?string $sslKey = null, ?string $sslCACert = null): void
    {
        self::$sslCert = $sslCert;
        self::$sslKey = $sslKey;
        self::$sslCACert = $sslCACert;
    }

    public static function setProxy(string $host, ?string $user = null, ?string $pass = null, int $type = CURLPROXY_SOCKS5): void
    {
        self::$proxy = [
            'host' => $host,
            'type' => $type,
            'user' => $user,
            'pass' => $pass
        ];
    }

    public static function setExceptions(bool $enabled = true): void
    {
        self::$useExceptions = $enabled;
    }

    public static function setTimeCorrectionOffset(int $offset = 0): void
    {
        if ($offset == 0) {
            $rest = new S3Request('HEAD');
            $response = $rest->getResponse();
            $awstime = $response->headers['date'] ?? time();
            $systime = time();
            $offset = $systime > $awstime ? - ($systime - $awstime) : ($awstime - $systime);
        }
        self::$__timeOffset = $offset;
    }

    public static function setSigningKey(string $keyPairId, string $signingKey, bool $isFile = true): bool
    {
        self::$__signingKeyPairId = $keyPairId;
        $content = $isFile ? file_get_contents($signingKey) : $signingKey;
        if ((self::$__signingKeyResource = openssl_pkey_get_private($content)) !== false)
            return true;
        self::__triggerError('S3::setSigningKey(): Unable to load private key', __FILE__, __LINE__);
        return false;
    }

    public static function freeSigningKey(): void
    {
        self::$__signingKeyResource = false;
    }

    public static function setProgressFunction(?callable $func = null): void
    {
        self::$progressFunction = $func;
    }

    private static function __triggerError(string $message, string $file, int $line, int $code = 0): void
    {
        if (self::$useExceptions)
            throw new S3Exception($message, $file, $line, $code);
        else
            trigger_error($message, E_USER_WARNING);
    }

    /**
     *
     * @return array|false
     */
    public static function listBuckets(bool $detailed = false)
    {
        $rest = new S3Request('GET', '', '', self::$endpoint);
        $rest = $rest->getResponse();
        if ($rest->error === false && $rest->code !== 200)
            $rest->error = [
                'code' => $rest->code,
                'message' => 'Unexpected HTTP status'
            ];
        if ($rest->error !== false) {
            self::__triggerError(sprintf("S3::listBuckets(): [%s] %s", $rest->error['code'], $rest->error['message']), __FILE__, __LINE__);
            return false;
        }
        $results = [];
        if (! isset($rest->body->Buckets))
            return $results;

        if ($detailed) {
            if (isset($rest->body->Owner, $rest->body->Owner->ID, $rest->body->Owner->DisplayName))
                $results['owner'] = [
                    'id' => (string) $rest->body->Owner->ID,
                    'name' => (string) $rest->body->Owner->DisplayName
                ];
            $results['buckets'] = [];
            foreach ($rest->body->Buckets->Bucket as $b)
                $results['buckets'][] = [
                    'name' => (string) $b->Name,
                    'time' => strtotime((string) $b->CreationDate)
                ];
        } else {
            foreach ($rest->body->Buckets->Bucket as $b)
                $results[] = (string) $b->Name;
        }
        return $results;
    }

    /**
     *
     * @return array|false
     */
    public static function getBucket(string $bucket, ?string $prefix = null, ?string $marker = null, ?int $maxKeys = null, ?string $delimiter = null, bool $returnCommonPrefixes = false)
    {
        $rest = new S3Request('GET', $bucket, '', self::$endpoint);
        if ($maxKeys === 0)
            $maxKeys = null;
        if (! empty($prefix))
            $rest->setParameter('prefix', $prefix);
        if (! empty($marker))
            $rest->setParameter('marker', $marker);
        if ($maxKeys !== null)
            $rest->setParameter('max-keys', (string) $maxKeys);
        if (! empty($delimiter))
            $rest->setParameter('delimiter', $delimiter);
        else if (! empty(self::$defDelimiter))
            $rest->setParameter('delimiter', self::$defDelimiter);

        $response = $rest->getResponse();
        if ($response->error === false && $response->code !== 200)
            $response->error = [
                'code' => $response->code,
                'message' => 'Unexpected HTTP status'
            ];

        if ($response->error !== false) {
            self::__triggerError(sprintf("S3::getBucket(): [%s] %s", $response->error['code'], $response->error['message']), __FILE__, __LINE__);
            return false;
        }

        $results = [];
        $nextMarker = null;
        if (isset($response->body->Contents)) {
            foreach ($response->body->Contents as $c) {
                $results[(string) $c->Key] = [
                    'name' => (string) $c->Key,
                    'time' => strtotime((string) $c->LastModified),
                    'size' => (int) $c->Size,
                    'hash' => trim((string) $c->ETag, '"')
                ];
                $nextMarker = (string) $c->Key;
            }
        }

        if ($returnCommonPrefixes && isset($response->body->CommonPrefixes)) {
            foreach ($response->body->CommonPrefixes as $c)
                $results[(string) $c->Prefix] = [
                    'prefix' => (string) $c->Prefix
                ];
        }

        return $results;
    }

    public static function putBucket(string $bucket, string $acl = self::ACL_PRIVATE, $location = false): bool
    {
        $rest = new S3Request('PUT', $bucket, '', self::$endpoint);
        $rest->setAmzHeader('x-amz-acl', $acl);
        if ($location === false)
            $location = self::getRegion();

        if ($location !== "us-east-1") {
            $dom = new DOMDocument();
            $conf = $dom->createElement('CreateBucketConfiguration');
            $conf->appendChild($dom->createElement('LocationConstraint', (string) $location));
            $dom->appendChild($conf);
            $rest->data = $dom->saveXML();
            $rest->size = strlen($rest->data);
            $rest->setHeader('Content-Type', 'application/xml');
        }
        $rest = $rest->getResponse();
        return $rest->error === false;
    }

    public static function deleteBucket(string $bucket): bool
    {
        $rest = new S3Request('DELETE', $bucket, '', self::$endpoint);
        $response = $rest->getResponse();
        return $response->code === 204;
    }

    public static function inputFile(string $file, $md5sum = true)
    {
        if (! file_exists($file) || ! is_readable($file))
            return false;
        return [
            'file' => $file,
            'size' => filesize($file),
            'md5sum' => $md5sum ? base64_encode(md5_file($file, true)) : '',
            'sha256sum' => hash_file('sha256', $file)
        ];
    }

    public static function inputResource(&$resource, $bufferSize = false, string $md5sum = '')
    {
        if (! is_resource($resource))
            return false;
        if ($bufferSize === false) {
            fseek($resource, 0, SEEK_END);
            $bufferSize = ftell($resource);
            fseek($resource, 0);
        }
        return [
            'size' => $bufferSize,
            'md5sum' => $md5sum,
            'fp' => &$resource
        ];
    }

    public static function putObject($input, string $bucket, string $uri, string $acl = self::ACL_PRIVATE, array &$metaHeaders = [], array &$requestHeaders = [], string $storageClass = self::STORAGE_CLASS_STANDARD, string $serverSideEncryption = self::SSE_NONE): bool
    {
        if ($input === false)
            return false;
        $rest = new S3Request('PUT', $bucket, $uri, self::$endpoint);

        if (! is_array($input)) {
            $input = [
                'data' => $input,
                'size' => strlen($input),
                'md5sum' => base64_encode(md5($input, true)),
                'sha256sum' => hash('sha256', $input)
            ];
        }

        if (isset($input['fp']))
            $rest->fp = &$input['fp'];
        elseif (isset($input['file']))
            $rest->fp = fopen($input['file'], 'rb');
        elseif (isset($input['data']))
            $rest->data = $input['data'];

        $rest->size = $input['size'] ?? 0;
        foreach ($requestHeaders as $h => $v)
            strpos($h, 'x-amz-') === 0 ? $rest->setAmzHeader($h, $v) : $rest->setHeader($h, $v);

        $rest->setAmzHeader('x-amz-acl', $acl);
        if ($storageClass !== self::STORAGE_CLASS_STANDARD)
            $rest->setAmzHeader('x-amz-storage-class', $storageClass);
        if ($serverSideEncryption !== self::SSE_NONE)
            $rest->setAmzHeader('x-amz-server-side-encryption', $serverSideEncryption);

        $response = $rest->getResponse();
        return $response->error === false;
    }

    public static function getObject(string $bucket, string $uri, $saveTo = false)
    {
        $rest = new S3Request('GET', $bucket, $uri, self::$endpoint);
        if ($saveTo !== false) {
            if (is_resource($saveTo))
                $rest->fp = &$saveTo;
            else
                $rest->fp = fopen($saveTo, 'wb');
        }
        return $rest->getResponse();
    }

    public static function deleteObject(string $bucket, string $uri): bool
    {
        $rest = new S3Request('DELETE', $bucket, $uri, self::$endpoint);
        $response = $rest->getResponse();
        return $response->code === 204;
    }

    public static function getAuthenticatedURL(string $bucket, string $uri, int $lifetime, bool $hostBucket = false, bool $https = false): string
    {
        $expires = self::__getTime() + $lifetime;
        $uri = str_replace([
            '%2F',
            '%2B'
        ], [
            '/',
            '+'
        ], rawurlencode($uri));
        $host = $hostBucket ? $bucket : self::$endpoint . '/' . $bucket;
        $sig = urlencode(self::__getHash("GET\n\n\n{$expires}\n/{$bucket}/{$uri}"));
        return sprintf(($https ? 'https' : 'http') . '://%s/%s?AWSAccessKeyId=%s&Expires=%u&Signature=%s', $host, $uri, self::$__accessKey, $expires, $sig);
    }

    /* --- CLOUDFRONT METHODS --- */
    public static function createDistribution(string $bucket, bool $enabled = true, array &$cnames = [], ?string $comment = null): bool|array
    {
        $useSSL = self::$useSSL;
        self::$useSSL = true;
        $rest = new S3Request('POST', '', '2010-11-01/distribution', 'cloudfront.amazonaws.com');
        $rest->data = self::__getCloudFrontDistributionConfigXML($bucket . '.s3.amazonaws.com', $enabled, (string) $comment, (string) microtime(true), $cnames);
        $rest->size = strlen($rest->data);
        $rest->setHeader('Content-Type', 'application/xml');
        $response = self::__getCloudFrontResponse($rest);
        self::$useSSL = $useSSL;
        return ($response->error === false) ? self::__parseCloudFrontDistributionConfig($response->body) : false;
    }

    private static function __getCloudFrontResponse(S3Request &$rest): stdClass
    {
        $response = $rest->getResponse();
        if ($response->error === false && is_string($response->body) && str_starts_with($response->body, '<?xml')) {
            $response->body = simplexml_load_string($response->body);
        }
        return $response;
    }

    private static function __getCloudFrontDistributionConfigXML(string $bucket, bool $enabled, string $comment, string $callerReference, array &$cnames): string
    {
        $dom = new DOMDocument('1.0', 'UTF-8');
        $conf = $dom->createElement('DistributionConfig');
        $conf->setAttribute('xmlns', 'http://cloudfront.amazonaws.com/doc/2010-11-01/');
        $origin = $dom->createElement('S3Origin');
        $origin->appendChild($dom->createElement('DNSName', $bucket));
        $conf->appendChild($origin);
        $conf->appendChild($dom->createElement('CallerReference', $callerReference));
        foreach ($cnames as $c)
            $conf->appendChild($dom->createElement('CNAME', $c));
        if ($comment !== '')
            $conf->appendChild($dom->createElement('Comment', $comment));
        $conf->appendChild($dom->createElement('Enabled', $enabled ? 'true' : 'false'));
        $dom->appendChild($conf);
        return $dom->saveXML();
    }

    private static function __parseCloudFrontDistributionConfig(SimpleXMLElement $node): array
    {
        if (isset($node->DistributionConfig))
            return self::__parseCloudFrontDistributionConfig($node->DistributionConfig);
        return [
            'id' => (string) $node->Id,
            'status' => (string) $node->Status,
            'domain' => (string) $node->DomainName,
            'enabled' => (string) $node->Enabled === 'true'
        ];
    }

    /* --- INTERNAL SIGNING --- */
    public static function __getSignatureV4(array &$amzHeaders, array &$headers, string $method, string $uri, array &$parameters): string
    {
        $service = 's3';
        $region = self::getRegion();
        $algorithm = 'AWS4-HMAC-SHA256';
        $amzDateStamp = substr($amzHeaders['x-amz-date'], 0, 8);

        $combinedHeaders = [];
        foreach (array_merge($headers, $amzHeaders) as $k => $v)
            $combinedHeaders[strtolower($k)] = trim((string) $v);
        ksort($combinedHeaders);

        $parameters = array_map('strval', $parameters);
        ksort($parameters);
        $queryString = http_build_query($parameters, '', '&', PHP_QUERY_RFC3986);

        $signedHeaders = implode(';', array_keys($combinedHeaders));
        $canonicalHeaders = '';
        foreach ($combinedHeaders as $k => $v)
            $canonicalHeaders .= $k . ':' . $v . "\n";

        $canonicalRequest = implode("\n", [
            $method,
            (strpos($uri, '?') === false ? $uri : substr($uri, 0, strpos($uri, '?'))),
            $queryString,
            $canonicalHeaders,
            $signedHeaders,
            $amzHeaders['x-amz-content-sha256']
        ]);

        $scope = "$amzDateStamp/$region/$service/aws4_request";
        $stringToSign = implode("\n", [
            $algorithm,
            $amzHeaders['x-amz-date'],
            $scope,
            hash('sha256', $canonicalRequest)
        ]);

        $kDate = hash_hmac('sha256', $amzDateStamp, 'AWS4' . self::$__secretKey, true);
        $kRegion = hash_hmac('sha256', $region, $kDate, true);
        $kService = hash_hmac('sha256', $service, $kRegion, true);
        $kSigning = hash_hmac('sha256', 'aws4_request', $kService, true);

        return $algorithm . ' ' . implode(',', [
            'Credential=' . self::$__accessKey . '/' . $scope,
            'SignedHeaders=' . $signedHeaders,
            'Signature=' . hash_hmac('sha256', $stringToSign, $kSigning)
        ]);
    }

    private static function __getHash(string $string): string
    {
        return base64_encode(hash_hmac('sha1', $string, (string) self::$__secretKey, true));
    }

    public static function __getTime(): int
    {
        return time() + self::$__timeOffset;
    }
}

final class S3Request
{

    private string $endpoint;

    private string $verb;

    private string $bucket;

    private string $uri;

    private string $resource = '';

    private array $parameters = [];

    private array $amzHeaders = [];

    private array $headers = [
        'Host' => '',
        'Date' => '',
        'Content-MD5' => '',
        'Content-Type' => ''
    ];

    public $fp = null;

    public int $size = 0;

    public $data = false;

    public stdClass $response;

    public function __construct(string $verb, string $bucket = '', string $uri = '', string $endpoint = 's3.amazonaws.com')
    {
        $this->endpoint = $endpoint;
        $this->verb = $verb;
        $this->bucket = $bucket;
        $this->uri = $uri !== '' ? '/' . str_replace('%2F', '/', rawurlencode($uri)) : '/';
        $this->headers['Host'] = ($this->bucket !== '') ? $this->bucket . '.' . $this->endpoint : $this->endpoint;
        $this->resource = ($this->bucket !== '') ? '/' . $this->bucket . $this->uri : $this->uri;
        $this->headers['Date'] = gmdate('D, d M Y H:i:s T');
        $this->response = new stdClass();
        $this->response->error = false;
        $this->response->body = '';
        $this->response->headers = [];
    }

    public function setParameter(string $key, ?string $value): void
    {
        $this->parameters[$key] = $value;
    }

    public function setHeader(string $key, string $value): void
    {
        $this->headers[$key] = $value;
    }

    public function setAmzHeader(string $key, string $value): void
    {
        $this->amzHeaders[$key] = $value;
    }

    public function getResponse(): stdClass
    {
        $url = (S3::$useSSL ? 'https://' : 'http://') . $this->headers['Host'] . $this->uri;
        $curl = curl_init($url);

        $httpHeaders = [];
        if (S3::hasAuth()) {
            $this->amzHeaders['x-amz-date'] = gmdate('Ymd\THis\Z');
            $this->amzHeaders['x-amz-content-sha256'] = hash('sha256', is_string($this->data) ? $this->data : '');
            foreach ($this->amzHeaders as $h => $v)
                $httpHeaders[] = "$h: $v";
            foreach ($this->headers as $h => $v)
                if (! empty($v))
                    $httpHeaders[] = "$h: $v";
            $httpHeaders[] = 'Authorization: ' . S3::__getSignatureV4($this->amzHeaders, $this->headers, $this->verb, $this->uri, $this->parameters);
        }

        curl_setopt($curl, CURLOPT_HTTPHEADER, $httpHeaders);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $this->verb);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, false);
        curl_setopt($curl, CURLOPT_WRITEFUNCTION, [
            $this,
            '__responseWriteCallback'
        ]);
        curl_setopt($curl, CURLOPT_HEADERFUNCTION, [
            $this,
            '__responseHeaderCallback'
        ]);

        if (is_resource($this->fp)) {
            if ($this->verb === 'PUT')
                curl_setopt($curl, CURLOPT_UPLOAD, true);
            curl_setopt($curl, CURLOPT_INFILE, $this->fp);
        } elseif ($this->data !== false) {
            curl_setopt($curl, CURLOPT_POSTFIELDS, $this->data);
        }

        curl_exec($curl);
        $this->response->code = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);
        return $this->response;
    }

    public function __responseWriteCallback(\CurlHandle $curl, string $data): int
    {
        if (is_resource($this->fp))
            return (int) fwrite($this->fp, $data);
        $this->response->body .= $data;
        return strlen($data);
    }

    public function __responseHeaderCallback(\CurlHandle $curl, string $data): int
    {
        $parts = explode(': ', $data, 2);
        if (count($parts) === 2)
            $this->response->headers[strtolower($parts[0])] = trim($parts[1]);
        return strlen($data);
    }
}

class S3Exception extends Exception
{

    public function __construct(string $message, string $file, int $line, int $code = 0)
    {
        parent::__construct($message, $code);
        $this->file = $file;
        $this->line = $line;
    }
}
