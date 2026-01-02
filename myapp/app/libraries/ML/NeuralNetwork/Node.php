<?php

declare(strict_types=1);

namespace ML\NeuralNetwork;

interface Node
{
    public function getOutput(): float;
}
