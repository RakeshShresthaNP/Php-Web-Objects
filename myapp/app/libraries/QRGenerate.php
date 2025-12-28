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
final class QRGenerate
{

    private $_size;

    private $_data;

    private $_encoding;

    private $_errorCorrectionLevel;

    private $_marginInRows;

    public function __construct($data = '', $size = '300', $encoding = 'UTF-8', $errorCorrectionLevel = 'L', $marginInRows = 4, $debug = false)
    {
        $this->_data = url_encode($data);
        $this->_size = ($size > 100 && $size < 800) ? $size : 300;
        $this->_encoding = ($encoding == 'Shift_JIS' || $encoding == 'ISO-8859-1' || $encoding == 'UTF-8') ? $encoding : 'UTF-8';
        $this->_errorCorrectionLevel = ($errorCorrectionLevel == 'L' || $errorCorrectionLevel == 'M' || $errorCorrectionLevel == 'Q' || $errorCorrectionLevel == 'H') ? $errorCorrectionLevel : 'L';
        $this->_marginInRows = ($marginInRows > 0 && $marginInRows < 10) ? $marginInRows : 4;
    }

    public function generate()
    {
        $QRLink = "https://chart.googleapis.com/chart?cht=qr&chs=" . $this->_size . "x" . $this->_size . "&chl=" . $this->_data . "&choe=" . $this->_encoding . "&chld=" . $this->_errorCorrectionLevel . "|" . $this->_marginInRows;
        return $QRLink;
    }
}