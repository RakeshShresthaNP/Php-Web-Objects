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

final class HelperCurrency
{

    private array $dictionary = [
        0 => 'zero',
        1 => 'one',
        2 => 'two',
        3 => 'three',
        4 => 'four',
        5 => 'five',
        6 => 'six',
        7 => 'seven',
        8 => 'eight',
        9 => 'nine',
        10 => 'ten',
        11 => 'eleven',
        12 => 'twelve',
        13 => 'thirteen',
        14 => 'fourteen',
        15 => 'fifteen',
        16 => 'sixteen',
        17 => 'seventeen',
        18 => 'eighteen',
        19 => 'nineteen',
        20 => 'twenty',
        30 => 'thirty',
        40 => 'forty',
        50 => 'fifty',
        60 => 'sixty',
        70 => 'seventy',
        80 => 'eighty',
        90 => 'ninety'
    ];

    private array $currencies = [
        'USD' => [
            'main' => 'Dollars',
            'minor' => 'Cents'
        ],
        'GBP' => [
            'main' => 'Pounds',
            'minor' => 'Pence'
        ]
    ];

    /**
     * Dynamically add a new currency configuration
     */
    public function addCurrency(string $code, string $main, string $minor): void
    {
        $this->currencies[strtoupper($code)] = [
            'main' => $main,
            'minor' => $minor
        ];
    }

    /**
     * Core logic to convert numbers to words (Recursive)
     */
    public function toWords(int $number): string
    {
        if ($number < 20)
            return _t($this->dictionary[$number]);

        if ($number < 100) {
            $tens = ((int) ($number / 10)) * 10;
            $units = $number % 10;
            return _t($this->dictionary[$tens]) . ($units ? '-' . _t($this->dictionary[$units]) : '');
        }

        if ($number < 1000) {
            $hundreds = (int) ($number / 100);
            $remainder = $number % 100;
            $res = _t($this->dictionary[$hundreds]) . ' ' . _t('hundred');
            return $remainder ? $res . ' ' . _t('and') . ' ' . $this->toWords($remainder) : $res;
        }

        // Logic for Thousands and Millions
        foreach ([
            1000000 => 'million',
            1000 => 'thousand'
        ] as $unit => $word) {
            if ($number >= $unit) {
                $count = (int) ($number / $unit);
                $remainder = $number % $unit;
                $res = $this->toWords($count) . ' ' . _t($word);
                if ($remainder) {
                    $separator = ($remainder < 100 ? ' ' . _t('and') . ' ' : ', ');
                    $res .= $separator . $this->toWords($remainder);
                }
                return $res;
            }
        }

        return (string) $number;
    }

    /**
     * Format the final string
     */
    public function format(float $amount, string $code = 'USD'): string
    {
        $code = strtoupper($code);
        if (! isset($this->currencies[$code])) {
            throw new Exception(_t('invalid_domain')); // Reusing key for 'unregistered/invalid' context
        }

        $config = $this->currencies[$code];
        $parts = explode('.', number_format($amount, 2, '.', ''));

        $mainWords = $this->toWords((int) $parts[0]) . ' ' . _t(strtolower($config['main']));
        $minorWords = ((int) $parts[1] > 0) ? ' ' . _t('and') . ' ' . $this->toWords((int) $parts[1]) . ' ' . _t(strtolower($config['minor'])) : '';

        return ucfirst($mainWords . $minorWords) . ' ' . _t('only') . '.';
    }
}
