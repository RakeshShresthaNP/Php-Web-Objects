<?php

declare(strict_types=1);

namespace ML\Tree\Node;

use ML\Tree\Node;

interface PurityNode extends Node
{
    public function impurity(): float;

    public function samplesCount(): int;
}
