<?php

declare(strict_types=1);

namespace ML\Tokenization;

interface Tokenizer
{
    public function tokenize(string $text): array;
}
