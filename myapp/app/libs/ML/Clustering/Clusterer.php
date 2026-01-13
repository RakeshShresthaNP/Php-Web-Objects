<?php

declare(strict_types=1);

namespace ML\Clustering;

interface Clusterer
{
    public function cluster(array $samples): array;
}
