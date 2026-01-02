<?php

declare(strict_types=1);

namespace ML\Preprocessing\Imputer\Strategy;

use ML\Math\Statistic\Mean;
use ML\Preprocessing\Imputer\Strategy;

class MostFrequentStrategy implements Strategy
{
    /**
     * @return float|mixed
     */
    public function replaceValue(array $currentAxis)
    {
        return Mean::mode($currentAxis);
    }
}
