<?php

declare(strict_types=1);

namespace ML\Preprocessing\Imputer\Strategy;

use ML\Math\Statistic\Mean;
use ML\Preprocessing\Imputer\Strategy;

class MedianStrategy implements Strategy
{
    public function replaceValue(array $currentAxis): float
    {
        return Mean::median($currentAxis);
    }
}
