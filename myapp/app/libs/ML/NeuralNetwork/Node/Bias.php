<?php

declare(strict_types=1);

namespace ML\NeuralNetwork\Node;

use ML\NeuralNetwork\Node;

class Bias implements Node
{
    public function getOutput(): float
    {
        return 1.0;
    }
}
