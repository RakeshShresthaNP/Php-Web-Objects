<?php

declare(strict_types=1);

namespace ML\Dataset;

interface Dataset
{
    public function getSamples(): array;

    public function getTargets(): array;
}
