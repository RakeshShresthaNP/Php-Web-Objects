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
final class Email
{

    private $_crlf = "\r\n";

    private $_wrap = 78;

    private $_to = array();

    private $_subject;

    private $_message;

    private $_headers = array();

    private $_parameters = '-f';

    private $_attachments = array();

    private $_attachmentsPath = array();

    private $_attachmentsFilename = array();

    public function __construct()
    {
        $this->reset();
    }

    public function reset()
    {
        $this->_to = array();
        $this->_headers = array();
        $this->_subject = null;
        $this->_message = null;
        $this->_wrap = 78;
        $this->_parameters = null;
        $this->_attachments = array();
        $this->_attachmentsPath = array();
        $this->_attachmentsFilename = array();

        return $this;
    }

    public function setTo($email, $name)
    {
        $this->_to[] = $this->formatHeader($email, $name);

        return $this;
    }

    public function getTo()
    {
        return $this->_to;
    }

    public function setCC($email, $name = '')
    {
        $this->addMailHeader('Cc', $email, $name);

        return $this;
    }

    public function setBCC($email, $name = '')
    {
        $this->addMailHeader('Bcc', $email, $name);

        return $this;
    }

    public function setSubject($subject)
    {
        $this->_subject = $this->filterOther($subject);

        return $this;
    }

    public function getSubject()
    {
        return $this->_subject;
    }

    public function setMessage($message)
    {
        $this->_message = mb_str_replace("\n.", "\n..", $message);

        return $this;
    }

    public function getMessage()
    {
        return $this->_message;
    }

    public function addAttachment($path, $filename = null)
    {
        $filename = empty($filename) ? basename($path) : $filename;

        $this->addAttachmentPath($path);
        $this->addAttachmentFilename($filename);

        $this->_attachments[] = $this->getAttachmentData($path);

        return $this;
    }

    public function addAttachmentPath($path)
    {
        $this->_attachmentsPath[] = $path;

        return $this;
    }

    public function addAttachmentFilename($filename)
    {
        $this->_attachmentsFilename[] = $filename;

        return $this;
    }

    public function getAttachmentData($path)
    {
        $filesize = filesize($path);
        $handle = fopen($path, "r");
        $attachment = fread($handle, $filesize);
        fclose($handle);

        return chunk_split(base64_encode($attachment));
    }

    public function setFrom($email, $name)
    {
        $this->addMailHeader('From', $email, $name);

        return $this;
    }

    public function addMailHeader($header, $email = null, $name = null)
    {
        $address = $this->formatHeader($email, $name);

        $this->_headers[] = sprintf("%s: %s", $header, $address);

        return $this;
    }

    public function addGenericHeader($header, $value)
    {
        $this->_headers[] = "$header: $value";

        return $this;
    }

    public function getHeaders()
    {
        return $this->_headers;
    }

    public function setParameters($additionalParameters)
    {
        $this->_parameters = $additionalParameters;

        return $this;
    }

    public function getParameters()
    {
        return $this->_parameters;
    }

    public function setWrap($wrap = 78)
    {
        $this->_wrap = $wrap;

        return $this;
    }

    public function getWrap()
    {
        return $this->_wrap;
    }

    public function hasAttachments()
    {
        return ! empty($this->_attachments);
    }

    public function assembleAttachmentHeaders()
    {
        $u = md5(uniqid(time()));

        $h = '';
        $h .= "\r\nMIME-Version: 1.0\r\n";
        $h .= "Content-Type: multipart/mixed; boundary=\"" . $u . "\"\r\n\r\n";
        $h .= "This is a multi-part message in MIME format.\r\n";
        $h .= "--" . $u . "\r\n";
        $h .= "Content-type:text/html; charset=\"utf-8\"\r\n";
        $h .= "Content-Transfer-Encoding: 7bit\r\n\r\n";
        $h .= $this->_message . "\r\n\r\n";
        $h .= "--" . $u . "\r\n";

        foreach ($this->_attachmentsFilename as $k => $v) {
            $h .= "Content-Type: application/octet-stream; name=\"" . $v . "\"\r\n";
            $h .= "Content-Transfer-Encoding: base64\r\n";
            $h .= "Content-Disposition: attachment; filename=\"" . $v . "\"\r\n\r\n";
            $h .= $this->_attachments[$k] . "\r\n\r\n";
            $h .= "--" . $u . "\r\n";
        }

        return $h;
    }

    public function send()
    {
        $headers = (! empty($this->_headers)) ? join($this->_crlf, $this->_headers) : array();
        $to = (is_array($this->_to) && ! empty($this->_to)) ? join(", ", $this->_to) : false;

        if ($this->hasAttachments()) {
            $headers .= $this->assembleAttachmentHeaders();

            return mail($to, $this->_subject, "", $headers, $this->_parameters);
        }

        $message = wordwrap($this->_message, $this->_wrap);

        return mail($to, $this->_subject, $message, $headers, $this->_parameters);
    }

    public function formatHeader($email, $name = null)
    {
        $email = $this->filterEmail($email);

        if (is_null($name)) {
            return $email;
        }

        $name = $this->filterName($name);

        return sprintf('%s <%s>', $name, $email);
    }

    public function filterEmail($email)
    {
        $rule = array(
            "\r" => '',
            "\n" => '',
            "\t" => '',
            '"' => '',
            ',' => '',
            '<' => '',
            '>' => ''
        );

        $email = strtr($email, $rule);
        $email = filter_var($email, FILTER_SANITIZE_EMAIL);

        return $email;
    }

    public function filterName($name)
    {
        $rule = array(
            "\r" => '',
            "\n" => '',
            "\t" => '',
            '"' => "'",
            '<' => '[',
            '>' => ']'
        );

        // FILTER_SANITIZE_STRING is deprecated in PHP 8.1+, use htmlspecialchars instead
        $sanitized = htmlspecialchars($name, ENT_QUOTES | ENT_HTML5, 'UTF-8', false);
        return mb_trim(strtr($sanitized, $rule));
    }

    public function filterOther($data)
    {
        $rule = array(
            "\r" => '',
            "\n" => '',
            "\t" => ''
        );

        // FILTER_SANITIZE_STRING is deprecated in PHP 8.1+, use htmlspecialchars instead
        $sanitized = htmlspecialchars($data, ENT_QUOTES | ENT_HTML5, 'UTF-8', false);
        return strtr($sanitized, $rule);
    }
}
