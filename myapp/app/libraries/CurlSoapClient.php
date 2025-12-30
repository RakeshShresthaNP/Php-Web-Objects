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
class NTLMStream
{

    private $path;

    private $mode;

    private $options;

    private $opened_path;

    private $buffer;

    private $pos;

    private static $user;

    private static $password;

    private $ch;

    public function stream_open($path, $mode, $options, $opened_path)
    {
        $this->path = $path;
        $this->mode = $mode;
        $this->options = $options;
        $this->opened_path = $opened_path;

        $this->createBuffer($path);

        return true;
    }

    public function stream_close()
    {
        curl_close($this->ch);
    }

    public function stream_read($count)
    {
        if (strlen($this->buffer) == 0) {
            return false;
        }

        $read = substr($this->buffer, $this->pos, $count);

        $this->pos += $count;

        return $read;
    }

    public function stream_write($data)
    {
        if (strlen($this->buffer) == 0) {
            return false;
        }

        return true;
    }

    public function stream_eof()
    {
        if ($this->pos > strlen($this->buffer)) {
            echo "true \n";
            return true;
        }

        return false;
    }

    public function stream_tell()
    {
        return $this->pos;
    }

    public function stream_flush()
    {
        $this->buffer = null;
        $this->pos = null;
    }

    public function stream_stat()
    {
        $this->createBuffer($this->path);
        $stat = array(
            'size' => strlen($this->buffer)
        );

        return $stat;
    }

    public function url_stat($path, $flags)
    {
        $this->createBuffer($path);
        $stat = array(
            'size' => strlen($this->buffer)
        );

        return $stat;
    }

    private function createBuffer($path)
    {
        if ($this->buffer) {
            return;
        }

        $this->ch = curl_init($path);
        curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($this->ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
        curl_setopt($this->ch, CURLOPT_HTTPAUTH, CURLAUTH_NTLM);
        curl_setopt($this->ch, CURLOPT_USERPWD, self::$user . ':' . self::$password);
        $this->buffer = curl_exec($this->ch);

        $this->pos = 0;
    }
}

class CurlSoapClient extends SoapClient
{

    private $options = [];

    public function __construct($url, $data)
    {
        $this->options = $data;

        if (empty($data['ntlm_username']) && empty($data['ntlm_password'])) {
            parent::__construct($url, $data);
        } else {
            $this->use_ntlm = true;
            NTLMStream::$user = $data['ntlm_username'];
            NTLMStream::$password = $data['ntlm_password'];

            stream_wrapper_unregister('http');
            stream_wrapper_unregister('https');

            stream_wrapper_register('http', 'NTLMStream');
            stream_wrapper_register('https', 'NTLMStream');

            parent::__construct($url, $data);

            stream_wrapper_restore('http');
            stream_wrapper_restore('https');
        }
    }

    public function __doRequest($request, $location, $action, $version, $one_way = 0)
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
        $response = curl_exec($ch);

        return $response;
    }
}
