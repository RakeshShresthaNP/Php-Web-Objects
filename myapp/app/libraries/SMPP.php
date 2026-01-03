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
class SMPPException extends Exception
{
}

final class GsmEncoder
{

    /**
     *
     * @param string $string
     *            UTF-8 input
     * @return string GSM 03.38 encoded string
     */
    public static function utf8ToGsm0338(string $string): string
    {
        $dict = [
            '@' => "\x00",
            '£' => "\x01",
            '$' => "\x02",
            '¥' => "\x03",
            'è' => "\x04",
            'é' => "\x05",
            'ù' => "\x06",
            'ì' => "\x07",
            'ò' => "\x08",
            'Ç' => "\x09",
            'Ø' => "\x0B",
            'ø' => "\x0C",
            'Å' => "\x0E",
            'å' => "\x0F",
            'Δ' => "\x10",
            '_' => "\x11",
            'Φ' => "\x12",
            'Γ' => "\x13",
            'Λ' => "\x14",
            'Ω' => "\x15",
            'Π' => "\x16",
            'Ψ' => "\x17",
            'Σ' => "\x18",
            'Θ' => "\x19",
            'Ξ' => "\x1A",
            'Æ' => "\x1C",
            'æ' => "\x1D",
            'ß' => "\x1E",
            'É' => "\x1F",
            'Ä' => "\x5B",
            'Ö' => "\x5C",
            'Ñ' => "\x5D",
            'Ü' => "\x5E",
            '§' => "\x5F",
            '¿' => "\x60",
            'ä' => "\x7B",
            'ö' => "\x7C",
            'ñ' => "\x7D",
            'ü' => "\x7E",
            'à' => "\x7F",
            '^' => "\x1B\x14",
            '{' => "\x1B\x28",
            '}' => "\x1B\x29",
            '\\' => "\x1B\x2F",
            '[' => "\x1B\x3C",
            '~' => "\x1B\x3D",
            ']' => "\x1B\x3E",
            '|' => "\x1B\x40",
            '€' => "\x1B\x65"
        ];
        $converted = strtr($string, $dict);
        return (string) preg_replace('/([\xC0-\xDF].)|([\xE0-\xEF]..)|([\xF0-\xFF]...)/m', '?', $converted);
    }
}

class SMPP
{

    // Protocol Constants
    private const BIND_TRANSMITTER = 0x00000002;

    private const SUBMIT_SM = 0x00000004;

    private const ENQUIRE_LINK = 0x00000015;

    private const UNBIND = 0x00000006;

    private const GENERIC_NACK = 0x80000000;

    // Instance Properties with Types
    /** @var resource|null */
    private $socket = null;

    private int $sequence = 1;

    private string $host;

    private int $port;

    private float $lastEnquire = 0.0;

    private bool $debug = false;

    /**
     *
     * @param string $host
     *            IP or Domain of SMSC
     * @param int $port
     *            Port (usually 5018 or 2775)
     * @param int $timeout
     *            Connection timeout in seconds
     */
    public function __construct(string $host, int $port, int $timeout = 10)
    {
        $this->host = $host;
        $this->port = $port;
        $this->socket = @fsockopen($host, $port, $errno, $errstr, (float) $timeout);

        if (! $this->socket) {
            throw new SMPPException("Connection Failed: $errstr ($errno)");
        }
        stream_set_timeout($this->socket, $timeout);
    }

    /**
     * Authenticate with the SMSC
     */
    public function bindTransmitter(string $user, string $pass): bool
    {
        // PDU: system_id, password, system_type, interface_version, addr_ton, addr_npi, address_range
        $pdu = pack('a' . (strlen($user) + 1) . 'a' . (strlen($pass) + 1) . 'a4CCCx', $user, $pass, "WWW", 0x34, 0, 0);

        $status = $this->sendCommand(self::BIND_TRANSMITTER, $pdu);
        return $status === 0;
    }

    /**
     * Send a Short Message
     *
     * @return int Command status (0 = Success)
     */
    public function sendSMS(string $from, string $to, string $message): int
    {
        $message = GsmEncoder::utf8ToGsm0338($message);
        $msgLen = strlen($message);

        // PDU layout for SUBMIT_SM
        $pdu = pack('a1cca' . (strlen($from) + 1) . 'cca' . (strlen($to) + 1) . 'ccca1a1ccccca' . $msgLen, '', // service_type
        0, 0, // source_addr_ton, source_addr_npi
        $from, // source_addr
        0, 0, // dest_addr_ton, dest_addr_npi
        $to, // destination_addr
        0, 0, 0, // esm_class, protocol_id, priority_flag
        '', '', // schedule_delivery_time, validity_period
        0, 0, // registered_delivery, replace_if_present_flag
        0, 0, // data_coding, sm_default_msg_id
        $msgLen, // sm_length
        $message // short_message
        );

        return $this->sendCommand(self::SUBMIT_SM, $pdu);
    }

    /**
     * internal PDU transmission logic
     */
    private function sendCommand(int $id, string $body): int
    {
        $this->keepAlive();

        $sn = $this->sequence ++;
        $length = strlen($body) + 16;
        $header = pack("NNNN", $length, $id, 0, $sn);

        if (fwrite($this->socket, $header . $body) === false) {
            throw new SMPPException("Failed to write to socket.");
        }

        $response = $this->readPDU();
        return (int) ($response['status'] ?? - 1);
    }

    /**
     * Reads and parses a PDU from the stream
     */
    private function readPDU(): array
    {
        $header = fread($this->socket, 16);
        if (! $header || strlen($header) < 16) {
            throw new SMPPException("Incomplete PDU header received.");
        }

        $data = unpack("Nlen/Nid/Nstatus/Nsn", $header);
        $body = "";

        if ($data['len'] > 16) {
            $body = fread($this->socket, $data['len'] - 16);
        }

        return [
            'id' => (int) $data['id'],
            'status' => (int) $data['status'],
            'sn' => (int) $data['sn'],
            'body' => $body
        ];
    }

    private function keepAlive(): void
    {
        // Send Enquire Link every 30 seconds to avoid idle timeout
        if (microtime(true) - $this->lastEnquire > 30.0) {
            $this->lastEnquire = microtime(true);
            $header = pack("NNNN", 16, self::ENQUIRE_LINK, 0, $this->sequence ++);
            fwrite($this->socket, $header);
        }
    }

    public function close(): void
    {
        if (is_resource($this->socket)) {
            $header = pack("NNNN", 16, self::UNBIND, 0, $this->sequence ++);
            fwrite($this->socket, $header);
            fclose($this->socket);
            $this->socket = null;
        }
    }

    public function __destruct()
    {
        $this->close();
    }
}
