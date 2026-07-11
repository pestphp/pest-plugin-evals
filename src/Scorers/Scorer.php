<?php

declare(strict_types=1);

namespace Pest\Evals\Scorers;

interface Scorer
{
    public const float DEFAULT_THRESHOLD = 0.7;

    /**
     * @return ScorerResult A result with a score between 0.0 and 1.0
     */
    public function score(string $input, string $output, ?string $expected = null): ScorerResult;
}
