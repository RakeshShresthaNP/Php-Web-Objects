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

final class QRGenerate
{

    private int $_size;

    private string $_data;

    private string $_encoding;

    private string $_errorCorrectionLevel;

    private int $_marginInRows;

    private string $_color;

    // Added: Hex color (e.g., 000000)
    private string $_bgcolor;

    // Added: Background color (e.g., ffffff)
    public function __construct(string $data = '', int $size = 300, string $encoding = 'UTF-8', string $errorCorrectionLevel = 'L', int $marginInRows = 4, string $color = '000000', string $bgcolor = 'ffffff')
    {
        $this->_data = urlencode($data);

        // Validation logic
        $this->_size = ($size >= 100 && $size <= 1000) ? $size : 300;
        $this->_encoding = in_array($encoding, [
            'Shift_JIS',
            'ISO-8859-1',
            'UTF-8'
        ]) ? $encoding : 'UTF-8';
        $this->_errorCorrectionLevel = in_array($errorCorrectionLevel, [
            'L',
            'M',
            'Q',
            'H'
        ]) ? $errorCorrectionLevel : 'L';
        $this->_marginInRows = ($marginInRows >= 0 && $marginInRows <= 10) ? $marginInRows : 4;

        // Extension: Strip '#' from colors if present
        $this->_color = ltrim($color, '#');
        $this->_bgcolor = ltrim($bgcolor, '#');
    }

    /**
     * Legacy Google Chart Implementation
     */
    public function generateGoogleChart(): string
    {
        return "https://chart.googleapis.com/chart?cht=qr" . "&chs=" . $this->_size . "x" . $this->_size . "&chl=" . $this->_data . "&choe=" . $this->_encoding . "&chld=" . $this->_errorCorrectionLevel . "|" . $this->_marginInRows;
    }

    /**
     * Extension: Modern API (goqr.me) with color support
     * Highly recommended as a more stable alternative in 2026.
     */
    public function generate(): string
    {
        return "https://api.qrserver.com/v1/create-qr-code/" . "?size=" . $this->_size . "x" . $this->_size . "&data=" . $this->_data . "&ecc=" . $this->_errorCorrectionLevel . "&margin=" . $this->_marginInRows . "&color=" . $this->_color . "&bgcolor=" . $this->_bgcolor . "&charset=" . $this->_encoding;
    }
}

