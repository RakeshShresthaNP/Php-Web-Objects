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
/*
 * $wsdlUrl = 'http://dominio.tld/webservice.aspx?wsdl';
 * $options = [
 * 'ntlm_username' => 'domain\username',
 * 'ntlm_password' => 'password'
 * ];
 *
 * $client = new CurlSoapClient($wsdlUrl, $options);
 *
 * $param = [
 * 'field1' => 'value1',
 * 'field2' => 'value2'
 * ];
 *
 * try {
 * $response = $client->YourMethodName($param);
 * } catch (Exception $ex) {
 * // Treat SOAP exception here
 * }
 */
declare(strict_types = 1);

final class NTLMStream
{

    private string $path;

    private int $mode;

    private int $options;

    private ?string $opened_path;

    private ?string $buffer = null;

    private int $pos = 0;

    private static string $user = '';

    private static string $password = '';

    /** @var resource|\CurlHandle|null */
    private $ch = null;

    public function stream_open(string $path, int $mode, int $options, ?string &$opened_path): bool
    {
        $this->path = $path;
        $this->mode = $mode;
        $this->options = $options;
        $this->opened_path = $opened_path;

        $this->createBuffer($path);

        return true;
    }

    public function stream_close(): void
    {
        if ($this->ch) {
            curl_close($this->ch);
        }
    }

    public function stream_read(int $count): string|bool
    {
        if (empty($this->buffer)) {
            return false;
        }

        $read = substr($this->buffer, $this->pos, $count);
        $this->pos += $count;

        return $read;
    }

    public function stream_write(string $data): bool
    {
        return ! empty($this->buffer);
    }

    public function stream_eof(): bool
    {
        return $this->pos >= strlen((string) $this->buffer);
    }

    public function stream_tell(): int
    {
        return $this->pos;
    }

    public function stream_flush(): bool
    {
        $this->buffer = null;
        $this->pos = 0;
        return true;
    }

    /**
     *
     * @return array<string, int>
     */
    public function stream_stat(): array
    {
        $this->createBuffer($this->path);
        return [
            'size' => strlen((string) $this->buffer)
        ];
    }

    /**
     *
     * @return array<string, int>
     */
    public function url_stat(string $path, int $flags): array
    {
        $this->createBuffer($path);
        return [
            'size' => strlen((string) $this->buffer)
        ];
    }

    private function createBuffer(string $path): void
    {
        if ($this->buffer !== null) {
            return;
        }

        $this->ch = curl_init($path);
        curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($this->ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
        curl_setopt($this->ch, CURLOPT_HTTPAUTH, CURLAUTH_NTLM);
        curl_setopt($this->ch, CURLOPT_USERPWD, self::$user . ':' . self::$password);

        $result = curl_exec($this->ch);
        $this->buffer = ($result === false) ? '' : (string) $result;
        $this->pos = 0;
    }

    public static function setCredentials(string $user, string $password): void
    {
        self::$user = $user;
        self::$password = $password;
    }
}

class CurlSoapClient extends SoapClient
{

    private array $options = [];

    private bool $use_ntlm = false;

    public function __construct(string $url, array &$data)
    {
        $this->options = $data;

        $hasUser = ! empty($data['ntlm_username']);
        $hasPass = ! empty($data['ntlm_password']);

        if (! $hasUser && ! $hasPass) {
            parent::__construct($url, $data);
        } else {
            $this->use_ntlm = true;
            NTLMStream::setCredentials((string) $data['ntlm_username'], (string) $data['ntlm_password']);

            stream_wrapper_unregister('http');
            stream_wrapper_unregister('https');

            stream_wrapper_register('http', NTLMStream::class);
            stream_wrapper_register('https', NTLMStream::class);

            parent::__construct($url, $data);

            stream_wrapper_restore('http');
            stream_wrapper_restore('https');
        }
    }

    /**
     *
     * @param string $request
     * @param string $location
     * @param string $action
     * @param int $version
     * @param int $one_way
     * @return string|null
     */
    public function __doRequest(string $request, string $location, string $action, int $version = 1, int $one_way = 0): ?string
    {
        $this->__last_request = $request;

        $ch = curl_init($location);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Method: POST',
            'User-Agent: PHP-SOAP-CURL',
            'Content-Type: text/xml; charset=utf-8',
            'SOAPAction: "' . $action . '"'
        ]);

        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $request);
        curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);

        if (! empty($this->options['ntlm_username']) && ! empty($this->options['ntlm_password'])) {
            curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_NTLM);
            curl_setopt($ch, CURLOPT_USERPWD, $this->options['ntlm_username'] . ':' . $this->options['ntlm_password']);
        }

        /** @var string|bool $response */
        $response = curl_exec($ch);
        curl_close($ch);

        return $response === false ? null : (string) $response;
    }
}

