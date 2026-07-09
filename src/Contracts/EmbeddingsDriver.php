<?php

declare(strict_types=1);

namespace Pest\Evals\Contracts;

interface EmbeddingsDriver
{
    /**
     * @param  array<int, string>  $inputs
     * @return array<int, array<int, float>> One vector per input, in input order.
     */
    public function embed(array $inputs): array;
}
