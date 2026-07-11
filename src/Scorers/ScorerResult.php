<?php

declare(strict_types=1);

namespace Pest\Evals\Scorers;

final readonly class ScorerResult
{
    public function __construct(
        public float $score,
        public string $reasoning,
        public string $scorer,
    ) {}

    public function passed(float $threshold = Scorer::DEFAULT_THRESHOLD): bool
    {
        return $this->score >= $threshold;
    }
}
