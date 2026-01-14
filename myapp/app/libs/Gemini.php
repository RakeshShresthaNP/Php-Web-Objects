<?php
declare(strict_types = 1);

use Gemini\Factory;

final class Gemini
{

    /**
     * Creates a new factory instance to configure a custom Gemini Client
     */
    public static function factory(): Factory
    {
        return new Factory();
    }
}
