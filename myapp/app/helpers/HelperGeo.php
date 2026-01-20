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

final class HelperGeo
{

    public static array $postalPatterns = [
        // Asia & Oceania
        'NP' => '/^\d{5}$/', // Nepal
        'IN' => '/^\d{6}$/', // India
        'CN' => '/^\d{6}$/', // China
        'JP' => '/^\d{3}-\d{4}$/', // Japan
        'SG' => '/^\d{6}$/', // Singapore
        'AU' => '/^\d{4}$/', // Australia
        'PK' => '/^\d{5}$/', // Pakistan
        'BD' => '/^\d{4}$/', // Bangladesh
        'MY' => '/^\d{5}$/', // Malaysia

        // Europe
        'GB' => '/^[A-Z]{1,2}\d[A-Z\d]? \d[A-Z]{2}$/i', // UK
        'DE' => '/^\d{5}$/', // Germany
        'FR' => '/^\d{5}$/', // France
        'IT' => '/^\d{5}$/', // Italy
        'ES' => '/^\d{5}$/', // Spain
        'NL' => '/^\d{4}\s?[A-Z]{2}$/i', // Netherlands
        'CH' => '/^\d{4}$/', // Switzerland
        'IE' => '/^[A-Z]\d{2}\s?[A-Z0-9]{4}$/i', // Ireland

        // Americas
        'US' => '/^\d{5}(-\d{4})?$/', // USA
        'CA' => '/^[A-Z]\d[A-Z]\s?\d[A-Z]\d$/i', // Canada
        'BR' => '/^\d{5}-\d{3}$/', // Brazil
        'MX' => '/^\d{5}$/', // Mexico

        // Africa & Middle East
        'ZA' => '/^\d{4}$/', // South Africa
        'NG' => '/^\d{6}$/', // Nigeria
        'TR' => '/^\d{5}$/', // Turkey
        'AE' => '/^00000$/', // UAE

        'DEFAULT' => '/^[A-Z0-9\s-]{3,12}$/i'
    ];

    public static array $phonePatterns = [
        // Asia & Oceania
        'NP' => [
            'prefix' => '977',
            'regex' => '/^9[678]\d{8}$/'
        ],
        'IN' => [
            'prefix' => '91',
            'regex' => '/^[6-9]\d{9}$/'
        ],
        'CN' => [
            'prefix' => '86',
            'regex' => '/^1[3-9]\d{9}$/'
        ],
        'JP' => [
            'prefix' => '81',
            'regex' => '/^[789]0\d{8}$/'
        ],
        'SG' => [
            'prefix' => '65',
            'regex' => '/^[89]\d{7}$/'
        ],
        'AU' => [
            'prefix' => '61',
            'regex' => '/^4\d{8}$/'
        ],
        'PK' => [
            'prefix' => '92',
            'regex' => '/^3\d{9}$/'
        ],
        'BD' => [
            'prefix' => '880',
            'regex' => '/^1[3-9]\d{8}$/'
        ],

        // Europe
        'GB' => [
            'prefix' => '44',
            'regex' => '/^7\d{9}$/'
        ],
        'DE' => [
            'prefix' => '49',
            'regex' => '/^1[5-7]\d{8,9}$/'
        ],
        'FR' => [
            'prefix' => '33',
            'regex' => '/^[67]\d{8}$/'
        ],
        'IT' => [
            'prefix' => '39',
            'regex' => '/^3\d{8,9}$/'
        ],
        'ES' => [
            'prefix' => '34',
            'regex' => '/^[67]\d{8}$/'
        ],
        'IE' => [
            'prefix' => '353',
            'regex' => '/^8[3-9]\d{7}$/'
        ],

        // Americas
        'US' => [
            'prefix' => '1',
            'regex' => '/^[2-9]\d{9}$/'
        ],
        'CA' => [
            'prefix' => '1',
            'regex' => '/^[2-9]\d{9}$/'
        ],
        'BR' => [
            'prefix' => '55',
            'regex' => '/^[1-9]{2}9\d{8}$/'
        ],
        'MX' => [
            'prefix' => '52',
            'regex' => '/^[1-9]\d{9,10}$/'
        ],

        // Middle East & Africa
        'AE' => [
            'prefix' => '971',
            'regex' => '/^5[024568]\d{7}$/'
        ],
        'SA' => [
            'prefix' => '966',
            'regex' => '/^5\d{8}$/'
        ],
        'ZA' => [
            'prefix' => '27',
            'regex' => '/^[678]\d{8}$/'
        ],

        'DEFAULT' => [
            'prefix' => '',
            'regex' => '/^\d{7,15}$/'
        ]
    ];

    // Constant list of countries that don't require a ZIP
    public const NO_ZIP_COUNTRIES = [
        'AE',
        'QA',
        'HK',
        'BS',
        'FJ',
        'MU',
        'PA',
        'KY'
    ];

    public static function getJsonConfig(): string
    {
        return json_encode([
            'postalPatterns' => self::$postalPatterns,
            'phonePatterns' => self::$phonePatterns,
            'noZipCountries' => self::NO_ZIP_COUNTRIES
        ], JSON_UNESCAPED_SLASHES);
    }
}
