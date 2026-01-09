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

final class Mailer
{

    private string $_crlf = "\r\n";

    private int $_wrap = 78;

    /** @var string[] */
    private array $_to = [];

    private string $_subject = '';

    private string $_message = '';

    /** @var string[] */
    private array $_headers = [];

    private string $_parameters = '-f';

    /** @var string[] */
    private array $_attachments = [];

    /** @var string[] */
    private array $_attachmentsPath = [];

    /** @var string[] */
    private array $_attachmentsFilename = [];

    public function __construct()
    {
        $this->reset();
    }

    /**
     * Resets all properties to default values.
     * Returns 'static' to support method chaining.
     */
    public function reset(): static
    {
        $this->_to = [];
        $this->_headers = [];
        $this->_subject = '';
        $this->_message = '';
        $this->_wrap = 78;
        $this->_parameters = '';
        $this->_attachments = [];
        $this->_attachmentsPath = [];
        $this->_attachmentsFilename = [];

        return $this;
    }

    public function setTo(string $email, string $name): static
    {
        $this->_to[] = $this->formatHeader($email, $name);
        return $this;
    }

    /**
     *
     * @return string[]
     */
    public function getTo(): array
    {
        return $this->_to;
    }

    public function setCC(string $email, string $name = ''): static
    {
        $this->addMailHeader('Cc', $email, $name);
        return $this;
    }

    public function setBCC(string $email, string $name = ''): static
    {
        $this->addMailHeader('Bcc', $email, $name);
        return $this;
    }

    public function setSubject(string $subject): static
    {
        $this->_subject = $this->filterOther($subject);
        return $this;
    }

    public function getSubject(): string
    {
        return $this->_subject;
    }

    public function setMessage(string $message): static
    {
        // str_replace is sufficient for basic character swaps
        $this->_message = str_replace("\n.", "\n..", $message);
        return $this;
    }

    public function getMessage(): string
    {
        return $this->_message;
    }

    public function addAttachment(string $path, string $filename = ''): static
    {
        $filename = empty($filename) ? basename($path) : $filename;

        $this->addAttachmentPath($path);
        $this->addAttachmentFilename($filename);
        $this->_attachments[] = $this->getAttachmentData($path);

        return $this;
    }

    public function addAttachmentPath(string $path): static
    {
        $this->_attachmentsPath[] = $path;
        return $this;
    }

    public function addAttachmentFilename(string $filename): static
    {
        $this->_attachmentsFilename[] = $filename;
        return $this;
    }

    /**
     * Reads file and returns base64 encoded chunked string.
     */
    public function getAttachmentData(string $path): string
    {
        if (! is_readable($path)) {
            throw new RuntimeException("File not readable: $path");
        }

        $attachment = file_get_contents($path);
        return chunk_split(base64_encode($attachment));
    }

    public function setFrom(string $email, string $name): static
    {
        $this->addMailHeader('From', $email, $name);
        return $this;
    }

    public function addMailHeader(string $header, string $email = '', string $name = ''): static
    {
        $address = $this->formatHeader($email, $name);
        $this->_headers[] = sprintf("%s: %s", $header, $address);
        return $this;
    }

    public function addGenericHeader(string $header, string $value): static
    {
        $this->_headers[] = "$header: $value";
        return $this;
    }

    /**
     *
     * @return string[]
     */
    public function getHeaders(): array
    {
        return $this->_headers;
    }

    public function setParameters(string $additionalParameters): static
    {
        $this->_parameters = $additionalParameters;
        return $this;
    }

    public function getParameters(): string
    {
        return $this->_parameters;
    }

    public function setWrap(int $wrap = 78): static
    {
        $this->_wrap = $wrap;
        return $this;
    }

    public function getWrap(): int
    {
        return $this->_wrap;
    }

    public function hasAttachments(): bool
    {
        return ! empty($this->_attachments);
    }

    public function assembleAttachmentHeaders(): string
    {
        $u = md5(uniqid((string) time(), true));

        $h = "\r\nMIME-Version: 1.0\r\n";
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

    public function send(): bool
    {
        $headers = (! empty($this->_headers)) ? implode($this->_crlf, $this->_headers) : '';
        $to = (! empty($this->_to)) ? implode(", ", $this->_to) : '';

        if (empty($to)) {
            return false;
        }

        if ($this->hasAttachments()) {
            $headers .= $this->assembleAttachmentHeaders();
            return mail($to, $this->_subject, "", $headers, $this->_parameters);
        }

        $message = wordwrap($this->_message, $this->_wrap);
        return mail($to, $this->_subject, $message, $headers, $this->_parameters);
    }

    public function formatHeader(string $email, ?string $name = ''): string
    {
        $email = $this->filterEmail($email);

        if (empty($name)) {
            return $email;
        }

        $name = $this->filterName($name);
        return sprintf('%s <%s>', $name, $email);
    }

    public function filterEmail(string $email): string
    {
        $rule = [
            "\r" => '',
            "\n" => '',
            "\t" => '',
            '"' => '',
            ',' => '',
            '<' => '',
            '>' => ''
        ];
        $email = strtr($email, $rule);
        return (string) filter_var($email, FILTER_SANITIZE_EMAIL);
    }

    public function filterName(string $name): string
    {
        $rule = [
            "\r" => '',
            "\n" => '',
            "\t" => '',
            '"' => "'",
            '<' => '[',
            '>' => ']'
        ];
        $sanitized = htmlspecialchars($name, ENT_QUOTES | ENT_HTML5, 'UTF-8', false);
        return trim(strtr($sanitized, $rule));
    }

    public function filterOther(string $data): string
    {
        $rule = [
            "\r" => '',
            "\n" => '',
            "\t" => ''
        ];
        $sanitized = htmlspecialchars($data, ENT_QUOTES | ENT_HTML5, 'UTF-8', false);
        return strtr($sanitized, $rule);
    }
}
