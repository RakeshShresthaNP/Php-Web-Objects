<?php

declare(strict_types=1);

namespace ML\Preprocessing\Imputer\Strategy;

use ML\Math\Statistic\Mean;
use ML\Preprocessing\Imputer\Strategy;

class MeanStrategy implements Strategy
{
    public function replaceValue(array $currentAxis): float
    {
        return Mean::arithmetic($currentAxis);
    }
}
