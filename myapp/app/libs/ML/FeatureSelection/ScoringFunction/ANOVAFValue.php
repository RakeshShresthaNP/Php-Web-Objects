<?php

declare(strict_types=1);

namespace ML\FeatureSelection\ScoringFunction;

use ML\FeatureSelection\ScoringFunction;
use ML\Math\Statistic\ANOVA;

final class ANOVAFValue implements ScoringFunction
{
    public function score(array $samples, array $targets): array
    {
        $grouped = [];
        foreach ($samples as $index => $sample) {
            $grouped[$targets[$index]][] = $sample;
        }

        return ANOVA::oneWayF(array_values($grouped));
    }
}
