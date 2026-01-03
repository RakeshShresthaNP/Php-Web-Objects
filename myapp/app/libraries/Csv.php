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
final class Csv
{

    private string $enclosure = '"';

    private string $delimiter = ',';

    private string $escape = '\\';

    private int $numFields = 0;

    /**
     *
     * @param string $file
     *            Path to the file
     * @param bool $headersOnly
     *            If true, returns only the header array
     * @return Generator|array|int Returns a Generator for rows, array for headers, or 0 on failure
     */
    public function load(string $file, bool $headersOnly = false): mixed
    {
        if (! is_readable($file)) {
            return 0;
        }

        $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));

        // Auto-detect delimiter based on extension
        if ($ext === 'txt') {
            $this->delimiter = "\t";
        }

        $handle = fopen($file, 'r');
        $rowIdx = 1;
        $headers = [];

        try {
            while (($data = fgetcsv($handle, 0, $this->delimiter, $this->enclosure, $this->escape)) !== false) {
                // Process Headers
                if ($rowIdx === 1) {
                    $headers = array_map(fn ($v) => mb_strtolower(trim($v)), $data);
                    $this->numFields = count($headers);

                    if ($this->numFields < 2) {
                        return 0;
                    }

                    if ($headersOnly) {
                        return $headers;
                    }

                    $rowIdx ++;
                    continue;
                }

                // Combine headers with current row data
                if (count($data) === $this->numFields) {
                    yield array_combine($headers, $data);
                }

                $rowIdx ++;
            }
        } finally {
            fclose($handle);
        }
    }

    /**
     * Writes CSV data directly to the output stream
     */
    public function write(array &$headers = [], array &$rows = [], ?string $delimiter = null): void
    {
        $delimiter ??= $this->delimiter;

        // Open php://output to write directly to the buffer
        $handle = fopen('php://output', 'w');

        // Write Headers
        if (! empty($headers)) {
            $cleanHeaders = array_map(fn ($v) => mb_strtolower($v), $headers);
            fputcsv($handle, $cleanHeaders, $delimiter, $this->enclosure, $this->escape);
        }

        // Write Rows
        foreach ($rows as $row) {
            fputcsv($handle, $row, $delimiter, $this->enclosure, $this->escape);
        }

        fclose($handle);
    }
}
