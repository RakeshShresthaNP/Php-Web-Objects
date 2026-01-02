<?php

declare(strict_types=1);

/**
 * Modernized SMPP implementation with full type safety.
 * No third-party dependencies required.
 */

class SMPPException extends Exception {}

final class GsmEncoder 
{
    /**
     * @param string $string UTF-8 input
     * @return string GSM 03.38 encoded string
     */
    public static function utf8ToGsm0338(string $string): string 
    {
        $dict = [
            '@' => "\x00", '£' => "\x01", '$' => "\x02", '¥' => "\x03", 'è' => "\x04", 'é' => "\x05",
            'ù' => "\x06", 'ì' => "\x07", 'ò' => "\x08", 'Ç' => "\x09", 'Ø' => "\x0B", 'ø' => "\x0C",
            'Å' => "\x0E", 'å' => "\x0F", 'Δ' => "\x10", '_' => "\x11", 'Φ' => "\x12", 'Γ' => "\x13",
            'Λ' => "\x14", 'Ω' => "\x15", 'Π' => "\x16", 'Ψ' => "\x17", 'Σ' => "\x18", 'Θ' => "\x19",
            'Ξ' => "\x1A", 'Æ' => "\x1C", 'æ' => "\x1D", 'ß' => "\x1E", 'É' => "\x1F", 'Ä' => "\x5B",
            'Ö' => "\x5C", 'Ñ' => "\x5D", 'Ü' => "\x5E", '§' => "\x5F", '¿' => "\x60", 'ä' => "\x7B",
            'ö' => "\x7C", 'ñ' => "\x7D", 'ü' => "\x7E", 'à' => "\x7F", '^' => "\x1B\x14", '{' => "\x1B\x28",
            '}' => "\x1B\x29", '\\' => "\x1B\x2F", '[' => "\x1B\x3C", '~' => "\x1B\x3D", ']' => "\x1B\x3E",
            '|' => "\x1B\x40", '€' => "\x1B\x65"
        ];
        $converted = strtr($string, $dict);
        return (string) preg_replace('/([\xC0-\xDF].)|([\xE0-\xEF]..)|([\xF0-\xFF]...)/m', '?', $converted);
    }
}

class SMPP 
{
    // Protocol Constants
    private const BIND_TRANSMITTER = 0x00000002;
    private const SUBMIT_SM        = 0x00000004;
    private const ENQUIRE_LINK     = 0x00000015;
    private const UNBIND           = 0x00000006;
    private const GENERIC_NACK     = 0x80000000;

    // Instance Properties with Types
    /** @var resource|null */
    private $socket = null;
    private int $sequence = 1;
    private string $host;
    private int $port;
    private float $lastEnquire = 0.0;
    private bool $debug = false;

    /**
     * @param string $host IP or Domain of SMSC
     * @param int $port Port (usually 5018 or 2775)
     * @param int $timeout Connection timeout in seconds
     */
    public function __construct(string $host, int $port, int $timeout = 10) 
    {
        $this->host = $host;
        $this->port = $port;
        $this->socket = @fsockopen($host, $port, $errno, $errstr, (float)$timeout);

        if (!$this->socket) {
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
        $pdu = pack('a'.(strlen($user)+1).'a'.(strlen($pass)+1).'a4CCCx', 
            $user, $pass, "WWW", 0x34, 0, 0);
        
        $status = $this->sendCommand(self::BIND_TRANSMITTER, $pdu);
        return $status === 0;
    }

    /**
     * Send a Short Message
     * @return int Command status (0 = Success)
     */
    public function sendSMS(string $from, string $to, string $message): int 
    {
        $message = GsmEncoder::utf8ToGsm0338($message);
        $msgLen = strlen($message);

        // PDU layout for SUBMIT_SM
        $pdu = pack('a1cca'.(strlen($from)+1).'cca'.(strlen($to)+1).'ccca1a1ccccca'.$msgLen,
            '',      // service_type
            0, 0,    // source_addr_ton, source_addr_npi
            $from,   // source_addr
            0, 0,    // dest_addr_ton, dest_addr_npi
            $to,     // destination_addr
            0, 0, 0, // esm_class, protocol_id, priority_flag
            '', '',  // schedule_delivery_time, validity_period
            0, 0,    // registered_delivery, replace_if_present_flag
            0, 0,    // data_coding, sm_default_msg_id
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
        
        $sn = $this->sequence++;
        $length = strlen($body) + 16;
        $header = pack("NNNN", $length, $id, 0, $sn);
        
        if (fwrite($this->socket, $header . $body) === false) {
            throw new SMPPException("Failed to write to socket.");
        }
        
        $response = $this->readPDU();
        return (int) ($response['status'] ?? -1);
    }

    /**
     * Reads and parses a PDU from the stream
     */
    private function readPDU(): array 
    {
        $header = fread($this->socket, 16);
        if (!$header || strlen($header) < 16) {
            throw new SMPPException("Incomplete PDU header received.");
        }
        
        $data = unpack("Nlen/Nid/Nstatus/Nsn", $header);
        $body = "";
        
        if ($data['len'] > 16) {
            $body = fread($this->socket, $data['len'] - 16);
        }

        return [
            'id' => (int)$data['id'],
            'status' => (int)$data['status'],
            'sn' => (int)$data['sn'],
            'body' => $body
        ];
    }

    private function keepAlive(): void 
    {
        // Send Enquire Link every 30 seconds to avoid idle timeout
        if (microtime(true) - $this->lastEnquire > 30.0) {
            $this->lastEnquire = microtime(true);
            $header = pack("NNNN", 16, self::ENQUIRE_LINK, 0, $this->sequence++);
            fwrite($this->socket, $header);
        }
    }

    public function close(): void 
    {
        if (is_resource($this->socket)) {
            $header = pack("NNNN", 16, self::UNBIND, 0, $this->sequence++);
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

// --- Implementation Example ---
try {
    $smpp = new SMPP('192.168.1.90', 5018);
    
    if ($smpp->bindTransmitter("my_user", "my_pass")) {
        $status = $smpp->sendSMS("Sender", "123456789", "Typed PHP is safer!");
        echo "Status: " . ($status === 0 ? "Success" : "Error $status");
    }
} catch (SMPPException $e) {
    echo "SMPP Error: " . $e->getMessage();
} catch (Exception $e) {
    echo "General Error: " . $e->getMessage();
}
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
        );
        $converted = strtr($string, $dict);

        // Replace unconverted UTF-8 chars from codepages U+0080-U+07FF, U+0080-U+FFFF and U+010000-U+10FFFF with a single ?
        return preg_replace('/([\\xC0-\\xDF].)|([\\xE0-\\xEF]..)|([\\xF0-\\xFF]...)/m', '?', $converted);
    }

    /**
     * Count the number of GSM 03.38 chars a conversion would contain.
     * It's about 3 times faster to count than convert and do strlen() if conversion is not required.
     *
     * @param string $utf8String
     * @return integer
     */
    public static function countGsm0338Length($utf8String)
    {
        $len = mb_strlen($utf8String, 'utf-8');
        $len += preg_match_all('/[\\^{}\\\~€|\\[\\]]/mu', $utf8String, $m);
        return $len;
    }

    /**
     * Pack an 8-bit string into 7-bit GSM format
     * Returns the packed string in binary format
     *
     * @param string $data
     * @return string
     */
    public static function pack7bit($data)
    {
        $l = strlen($data);
        $currentByte = 0;
        $offset = 0;
        $packed = '';
        for ($i = 0; $i < $l; $i ++) {
            // cap off any excess bytes
            $septet = ord($data[$i]) & 0x7f;
            // append the septet and then cap off excess bytes
            $currentByte |= ($septet << $offset) & 0xff;
            // update offset
            $offset += 7;

            if ($offset > 7) {
                // the current byte is full, add it to the encoded data.
                $packed .= chr($currentByte);
                // shift left and append the left shifted septet to the current byte
                $currentByte = $septet = $septet >> (7 - ($offset - 8));
                // update offset
                $offset -= 8; // 7 - (7 - ($offset - 8))
            }
        }
        if ($currentByte > 0)
            $packed .= chr($currentByte); // append the last byte

        return $packed;
    }
}

final class SMPP
{

    // Command ids - SMPP v3.4 - 5.1.2.1 page 110-111
    const GENERIC_NACK = 0x80000000;

    const BIND_RECEIVER = 0x00000001;

    const BIND_RECEIVER_RESP = 0x80000001;

    const BIND_TRANSMITTER = 0x00000002;

    const BIND_TRANSMITTER_RESP = 0x80000002;

    const QUERY_SM = 0x00000003;

    const QUERY_SM_RESP = 0x80000003;

    const SUBMIT_SM = 0x00000004;

    const SUBMIT_SM_RESP = 0x80000004;

    const DELIVER_SM = 0x00000005;

    const DELIVER_SM_RESP = 0x80000005;

    const UNBIND = 0x00000006;

    const UNBIND_RESP = 0x80000006;

    const REPLACE_SM = 0x00000007;

    const REPLACE_SM_RESP = 0x80000007;

    const CANCEL_SM = 0x00000008;

    const CANCEL_SM_RESP = 0x80000008;

    const BIND_TRANSCEIVER = 0x00000009;

    const BIND_TRANSCEIVER_RESP = 0x80000009;

    const OUTBIND = 0x0000000B;

    const ENQUIRE_LINK = 0x00000015;

    const ENQUIRE_LINK_RESP = 0x80000015;

    // Command status - SMPP v3.4 - 5.1.3 page 112-114
    const ESME_ROK = 0x00000000;

    // No Error
    const ESME_RINVMSGLEN = 0x00000001;

    // Message Length is invalid
    const ESME_RINVCMDLEN = 0x00000002;

    // Command Length is invalid
    const ESME_RINVCMDID = 0x00000003;

    // Invalid Command ID
    const ESME_RINVBNDSTS = 0x00000004;

    // Incorrect BIND Status for given command
    const ESME_RALYBND = 0x00000005;

    // ESME Already in Bound State
    const ESME_RINVPRTFLG = 0x00000006;

    // Invalid Priority Flag
    const ESME_RINVREGDLVFLG = 0x00000007;

    // Invalid Registered Delivery Flag
    const ESME_RSYSERR = 0x00000008;

    // System Error
    const ESME_RINVSRCADR = 0x0000000A;

    // Invalid Source Address
    const ESME_RINVDSTADR = 0x0000000B;

    // Invalid Dest Addr
    const ESME_RINVMSGID = 0x0000000C;

    // Message ID is invalid
    const ESME_RBINDFAIL = 0x0000000D;

    // Bind Failed
    const ESME_RINVPASWD = 0x0000000E;

    // Invalid Password
    const ESME_RINVSYSID = 0x0000000F;

    // Invalid System ID
    const ESME_RCANCELFAIL = 0x00000011;

    // Cancel SM Failed
    const ESME_RREPLACEFAIL = 0x00000013;

    // Replace SM Failed
    const ESME_RMSGQFUL = 0x00000014;

    // Message Queue Full
    const ESME_RINVSERTYP = 0x00000015;

    // Invalid Service Type
    const ESME_RINVNUMDESTS = 0x00000033;

    // Invalid number of destinations
    const ESME_RINVDLNAME = 0x00000034;

    // Invalid Distribution List name
    const ESME_RINVDESTFLAG = 0x00000040;

    // Destination flag (submit_multi)
    const ESME_RINVSUBREP = 0x00000042;

    // Invalid ‘submit with replace’ request (i.e. submit_sm with replace_if_present_flag set)
    const ESME_RINVESMSUBMIT = 0x00000043;

    // Invalid esm_SUBMIT field data
    const ESME_RCNTSUBDL = 0x00000044;

    // Cannot Submit to Distribution List
    const ESME_RSUBMITFAIL = 0x00000045;

    // submit_sm or submit_multi failed
    const ESME_RINVSRCTON = 0x00000048;

    // Invalid Source address TON
    const ESME_RINVSRCNPI = 0x00000049;

    // Invalid Source address NPI
    const ESME_RINVDSTTON = 0x00000050;

    // Invalid Destination address TON
    const ESME_RINVDSTNPI = 0x00000051;

    // Invalid Destination address NPI
    const ESME_RINVSYSTYP = 0x00000053;

    // Invalid system_type field
    const ESME_RINVREPFLAG = 0x00000054;

    // Invalid replace_if_present flag
    const ESME_RINVNUMMSGS = 0x00000055;

    // Invalid number of messages
    const ESME_RTHROTTLED = 0x00000058;

    // Throttling error (ESME has exceeded allowed message limits)
    const ESME_RINVSCHED = 0x00000061;

    // Invalid Scheduled Delivery Time
    const ESME_RINVEXPIRY = 0x00000062;

    // Invalid message (Expiry time)
    const ESME_RINVDFTMSGID = 0x00000063;

    // Predefined Message Invalid or Not Found
    const ESME_RX_T_APPN = 0x00000064;

    // ESME Receiver Temporary App Error Code
    const ESME_RX_P_APPN = 0x00000065;

    // ESME Receiver Permanent App Error Code
    const ESME_RX_R_APPN = 0x00000066;

    // ESME Receiver Reject Message Error Code
    const ESME_RQUERYFAIL = 0x00000067;

    // query_sm request failed
    const ESME_RINVOPTPARSTREAM = 0x000000C0;

    // Error in the optional part of the PDU Body.
    const ESME_ROPTPARNOTALLWD = 0x000000C1;

    // Optional Parameter not allowed
    const ESME_RINVPARLEN = 0x000000C2;

    // Invalid Parameter Length.
    const ESME_RMISSINGOPTPARAM = 0x000000C3;

    // Expected Optional Parameter missing
    const ESME_RINVOPTPARAMVAL = 0x000000C4;

    // Invalid Optional Parameter Value
    const ESME_RDELIVERYFAILURE = 0x000000FE;

    // Delivery Failure (data_sm_resp)
    const ESME_RUNKNOWNERR = 0x000000FF;

    // Unknown Error

    // SMPP bind parameters
    var $system_type = "WWW";

    var $interface_version = 0x34;

    var $addr_ton = 0;

    var $addr_npi = 0;

    var $address_range = "";

    // Timeout of enquirelink, default 10 seconds
    var $enquirelink_timeout = 10;

    // ESME transmitter parameters
    var $sms_service_type = "";

    var $sms_source_addr_ton = 0;

    var $sms_source_addr_npi = 0;

    var $sms_dest_addr_ton = 0;

    var $sms_dest_addr_npi = 0;

    var $sms_esm_class = 0;

    var $sms_protocol_id = 0;

    var $sms_priority_flag = 0;

    var $sms_schedule_delivery_time = "";

    var $sms_validity_period = "";

    var $sms_registered_delivery_flag = 0;

    var $sms_replace_if_present_flag = 0;

    var $sms_data_coding = 0;

    var $sms_sm_default_msg_id = 0;

    function __construct($host, $port)
    {

        // Last time the of the enquirelink
        $this->lastenquire = 0;
        // internal parameters
        $this->sequence_number = 1;
        $this->debug = false;
        $this->pdu_queue = array();
        $this->host = $host;
        $this->port = intval($port);
        $this->state = "closed";
        // open the socket
        $this->socket = @fsockopen($this->host, $this->port, $errno, $errstr, 10);
        if ($this->socket)
            $this->state = "open";
    }

    // Function to know if is necessary send the enquirelink to SMSC
    public function checkForEnquire()
    {
        $now = microtime(true);

        $duration = $now - $this->lastenquire;
        $hours = (int) ($duration / 60 / 60);
        $minutes = (int) ($duration / 60) - $hours * 60;
        $seconds = (int) $duration - $hours * 60 * 60 - $minutes * 60;

        if ($seconds >= $this->enquirelink_timeout) {
            $this->enquirelink();
        }
    }

    public function bindReceiver($login, $pass)
    {
        if ($this->state != "open")
            return false;
        $status = $this->_bind($login, $pass, SMPP::BIND_RECEIVER);
        if ($status === 0)
            $this->state = "bind_rx";
        return ($status === 0);
    }

    public function bindTransmitter($login, $pass)
    {
        if ($this->state != "open")
            return false;
        $status = $this->_bind($login, $pass, SMPP::BIND_TRANSMITTER);
        if ($status === 0)
            $this->state = "bind_tx";

        return ($status === 0);
    }

    /**
     * Closes the session on the SMSC server.
     */
    public function close()
    {
        if ($this->state == "closed")
            return;
        $status = $this->sendCommand(SMPP::UNBIND, "");
        fclose($this->socket);
        $this->state = "closed";
    }

    public function readSMS()
    {
        if ($this->state != "bind_rx")
            return false;
        $command_id = SMPP::DELIVER_SM;
        // check the queue
        for ($i = 0; $i < count($this->pdu_queue); $i ++) {
            $pdu = $this->pdu_queue[$i];
            if ($pdu['id'] == $command_id) {
                // remove response
                array_splice($this->pdu_queue, $i, 1);
                return parseSMS($pdu);
            }
        }
        // read pdu
        do {
            $pdu = $this->readPDU();
            // check for enquire link command
            if ($pdu['id'] == SMPP::ENQUIRE_LINK) {
                $this->sendPDU(SMPP::ENQUIRE_LINK_RESP, "", $pdu['sn']);
                return false;
            }
            array_push($this->pdu_queue, $pdu);
        } while ($pdu && $pdu['id'] != $command_id);
        if ($pdu) {
            array_pop($this->pdu_queue);
            return $this->parseSMS($pdu);
        }
        return false;
    }

    public function sendSMS($from, $to, $message)
    {
        if (strlen($from) > 20 || strlen($to) > 20 || strlen($message) > 160)
            return false;
        if ($this->state != "bind_tx")
            return false;

        // TON
        $this->sms_source_addr_ton = $this->setTon($from);
        $this->sms_dest_addr_ton = $this->setTon($to);

        // NPI
        $this->sms_source_addr_npi = $this->setNPI($from);
        $this->sms_dest_addr_npi = $this->setNPI($to);

        $pdu = pack('a1cca' . (strlen($from) + 1) . 'cca' . (strlen($to) + 1) . 'ccca1a1ccccca' . (strlen($message) + 1), $this->sms_service_type, $this->sms_source_addr_ton, $this->sms_source_addr_npi, $from, // source_addr
        $this->sms_dest_addr_ton, $this->sms_dest_addr_npi, $to, // destination_addr
        $this->sms_esm_class, $this->sms_protocol_id, $this->sms_priority_flag, $this->sms_schedule_delivery_time, $this->sms_validity_period, $this->sms_registered_delivery_flag, $this->sms_replace_if_present_flag, $this->sms_data_coding, $this->sms_sm_default_msg_id, strlen($message), // sm_length
        $message // short_message
        );

        // Before each message verify if necessary send the enquirelink to SMSC
        $this->checkForEnquire();
        $status = $this->sendCommand(SMPP::SUBMIT_SM, $pdu);
        return $status;
    }

    // Get text of error
    public function getStatusMessage($statuscode)
    {
        if (is_bool($statuscode))
            return 'Connection Error';

        switch ($statuscode) {
            case SMPP::ESME_ROK:
                return 'OK';
            case SMPP::ESME_RINVMSGLEN:
                return 'Message Length is invalid';
            case SMPP::ESME_RINVCMDLEN:
                return 'Command Length is invalid';
            case SMPP::ESME_RINVCMDID:
                return 'Invalid Command ID';
            case SMPP::ESME_RINVBNDSTS:
                return 'Incorrect BIND Status for given command';
            case SMPP::ESME_RALYBND:
                return 'ESME Already in Bound State';
            case SMPP::ESME_RINVPRTFLG:
                return 'Invalid Priority Flag';
            case SMPP::ESME_RINVREGDLVFLG:
                return 'Invalid Registered Delivery Flag';
            case SMPP::ESME_RSYSERR:
                return 'System Error';
            case SMPP::ESME_RINVSRCADR:
                return 'Invalid Source Address';
            case SMPP::ESME_RINVDSTADR:
                return 'Invalid Dest Addr';
            case SMPP::ESME_RINVMSGID:
                return 'Message ID is invalid';
            case SMPP::ESME_RBINDFAIL:
                return 'Bind Failed';
            case SMPP::ESME_RINVPASWD:
                return 'Invalid Password';
            case SMPP::ESME_RINVSYSID:
                return 'Invalid System ID';
            case SMPP::ESME_RCANCELFAIL:
                return 'Cancel SM Failed';
            case SMPP::ESME_RREPLACEFAIL:
                return 'Replace SM Failed';
            case SMPP::ESME_RMSGQFUL:
                return 'Message Queue Full';
            case SMPP::ESME_RINVSERTYP:
                return 'Invalid Service Type';
            case SMPP::ESME_RINVNUMDESTS:
                return 'Invalid number of destinations';
            case SMPP::ESME_RINVDLNAME:
                return 'Invalid Distribution List name';
            case SMPP::ESME_RINVDESTFLAG:
                return 'Destination flag (submit_multi)';
            case SMPP::ESME_RINVSUBREP:
                return 'Invalid ‘submit with replace’ request (i.e. submit_sm with replace_if_present_flag set)';
            case SMPP::ESME_RINVESMSUBMIT:
                return 'Invalid esm_SUBMIT field data';
            case SMPP::ESME_RCNTSUBDL:
                return 'Cannot Submit to Distribution List';
            case SMPP::ESME_RSUBMITFAIL:
                return 'submit_sm or submit_multi failed';
            case SMPP::ESME_RINVSRCTON:
                return 'Invalid Source address TON';
            case SMPP::ESME_RINVSRCNPI:
                return 'Invalid Source address NPI';
            case SMPP::ESME_RINVDSTTON:
                return 'Invalid Destination address TON';
            case SMPP::ESME_RINVDSTNPI:
                return 'Invalid Destination address NPI';
            case SMPP::ESME_RINVSYSTYP:
                return 'Invalid system_type field';
            case SMPP::ESME_RINVREPFLAG:
                return 'Invalid replace_if_present flag';
            case SMPP::ESME_RINVNUMMSGS:
                return 'Invalid number of messages';
            case SMPP::ESME_RTHROTTLED:
                return 'Throttling error (ESME has exceeded allowed message limits)';
            case SMPP::ESME_RINVSCHED:
                return 'Invalid Scheduled Delivery Time';
            case SMPP::ESME_RINVEXPIRY:
                return 'Invalid message (Expiry time)';
            case SMPP::ESME_RINVDFTMSGID:
                return 'Predefined Message Invalid or Not Found';
            case SMPP::ESME_RX_T_APPN:
                return 'ESME Receiver Temporary App Error Code';
            case SMPP::ESME_RX_P_APPN:
                return 'ESME Receiver Permanent App Error Code';
            case SMPP::ESME_RX_R_APPN:
                return 'ESME Receiver Reject Message Error Code';
            case SMPP::ESME_RQUERYFAIL:
                return 'query_sm request failed';
            case SMPP::ESME_RINVOPTPARSTREAM:
                return 'Error in the optional part of the PDU Body.';
            case SMPP::ESME_ROPTPARNOTALLWD:
                return 'Optional Parameter not allowed';
            case SMPP::ESME_RINVPARLEN:
                return 'Invalid Parameter Length.';
            case SMPP::ESME_RMISSINGOPTPARAM:
                return 'Expected Optional Parameter missing';
            case SMPP::ESME_RINVOPTPARAMVAL:
                return 'Invalid Optional Parameter Value';
            case SMPP::ESME_RDELIVERYFAILURE:
                return 'Delivery Failure (data_sm_resp)';
            case SMPP::ESME_RUNKNOWNERR:
                return 'Unknown Error';
            default:
                return 'Unknown error';
        }
    }

    // //////////////private functions///////////////

    /*
     * TON:
     * Unknown = 0,
     * International = 1,
     * National = 2,
     * NetworkSpecific = 3,
     * SubscriberNumber = 4,
     * Alphanumeric = 5,
     * Abbreviated = 6,
     *
     */
    private function setTon($address)
    {

        // Check if empty or only number
        if (empty($address) || ctype_digit($address)) {
            $NationalNumberLenght = 8;

            // If length is 8 then TON is National (2)
            if (strlen($address) == $NationalNumberLenght)
                return 2; // National

            // If empty and length is > 8 then TON is International (1)
            if (empty($address) || strlen($address) > $NationalNumberLenght)
                return 1; // International
        }

        // If address is alphanumeric then TON is Alphanumeric (5)
        if (! ctype_digit($address))
            return 5; // Alphanumeric

        // If no apply condition then TON is Unknown (0)
        return 0; // Unknown
    }

    private function setNPI($address)
    {

        // If address is alphanumeric then NPI is Unknown(0)
        if (! ctype_digit($address))
            return 0; // Unknown

        // If no apply condition then NPI is ISDN (1)
        return 1; // ISDN
    }

    // Send the enquirelink
    public function enquireLink()
    {
        $response = $this->sendCommand(SMPP::ENQUIRE_LINK, "");
        if ($response == 0) {
            $this->lastenquire = microtime(true);
        }
        return $response;
    }

    private function _bind($login, $pass, $command_id)
    {
        // make PDU
        $pdu = pack('a' . (strlen($login) + 1) . 'a' . (strlen($pass) + 1) . 'a' . (strlen($this->system_type) + 1) . 'CCCa' . (strlen($this->address_range) + 1), $login, $pass, $this->system_type, $this->interface_version, $this->addr_ton, $this->addr_npi, $this->address_range);
        $status = $this->sendCommand($command_id, $pdu);
        return $status;
    }

    private function parseSMS($pdu)
    {
        // check command id
        if ($pdu['id'] != SMPP::DELIVER_SM)
            return false;
        // unpack PDU
        $ar = unpack("C*", $pdu['body']);

        $sms = array(
            'service_type' => $this->getString($ar, 6),
            'source_addr_ton' => array_shift($ar),
            'source_addr_npi' => array_shift($ar),
            'source_addr' => $this->getString($ar, 21),
            'dest_addr_ton' => array_shift($ar),
            'dest_addr_npi' => array_shift($ar),
            'destination_addr' => $this->getString($ar, 21),
            'esm_class' => array_shift($ar),
            'protocol_id' => array_shift($ar),
            'priority_flag' => array_shift($ar),
            'schedule_delivery_time' => array_shift($ar),
            'validity_period' => array_shift($ar),
            'registered_delivery' => array_shift($ar),
            'replace_if_present_flag' => array_shift($ar),
            'data_coding' => array_shift($ar),
            'sm_default_msg_id' => array_shift($ar),
            'sm_length' => array_shift($ar),
            'short_message' => $this->getString($ar, 255)
        );

        // send response of recieving sms
        $this->sendPDU(SMPP::DELIVER_SM_RESP, "\0", $pdu['sn']);
        return $sms;
    }

    private function sendCommand($command_id, $pdu)
    {
        if ($this->state == "closed")
            return false;
        $this->sendPDU($command_id, $pdu, $this->sequence_number);
        $status = $this->readPDU_resp($this->sequence_number, $command_id);
        $this->sequence_number = $this->sequence_number + 1;
        return $status;
    }

    private function sendPDU($command_id, $pdu, $seq_number)
    {
        $length = strlen($pdu) + 16;
        $header = pack("NNNN", $length, $command_id, 0, $seq_number);

        $writed = @fwrite($this->socket, $header . $pdu, $length);

        // Close conection if not bind
        if ($writed == FALSE) {
            exit();
        }
    }

    private function readPDU_resp($seq_number, $command_id)
    {
        // create response id
        $command_id = $command_id | SMPP::GENERIC_NACK;
        // check queue
        for ($i = 0; $i < count($this->pdu_queue); $i ++) {
            $pdu = $this->pdu_queue[$i];
            if ($pdu['sn'] == $seq_number && $pdu['id'] == $command_id) {
                // remove response
                array_splice($this->pdu_queue, $i, 1);
                return $pdu['status'];
            }
        }
        // read pdu
        do {
            $pdu = $this->readPDU();
            if ($pdu)
                array_push($this->pdu_queue, $pdu);
        } while ($pdu && ($pdu['sn'] != $seq_number || $pdu['id'] != $command_id));
        // remove response from queue
        if ($pdu) {
            array_pop($this->pdu_queue);
            return $pdu['status'];
        }
        return false;
    }

    private function readPDU()
    {
        // read PDU length
        $tmp = fread($this->socket, 4);
        if (! $tmp)
            return false;
        extract(unpack("Nlength", $tmp));
        // read PDU headers
        $tmp2 = fread($this->socket, 12);
        if (! $tmp2)
            return false;
        extract(unpack("Ncommand_id/Ncommand_status/Nsequence_number", $tmp2));
        // read PDU body
        if ($length - 16 > 0) {
            $body = fread($this->socket, $length - 16);
            if (! $body)
                return false;
        } else {
            $body = "";
        }

        $pdu = array(
            'id' => $command_id,
            'status' => $command_status,
            'sn' => $sequence_number,
            'body' => $body
        );
        return $pdu;
    }

    private function getString(&$ar, $maxlen = 255)
    {
        $s = "";
        $i = 0;
        do {
            $c = array_shift($ar);
            if ($c != 0)
                $s .= chr($c);
            $i ++;
        } while ($i < $maxlen && $c != 0);
        return $s;
    }

    private function printHex($pdu)
    {
        $ar = unpack("C*", $pdu);
        foreach ($ar as $v) {
            $s = dechex($v);
            if (strlen($s) < 2)
                $s = "0$s";
            print "$s ";
        }
        print "\n";
    }
}

