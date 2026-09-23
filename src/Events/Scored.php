<?php

declare(strict_types=1);

namespace Pest\Evals\Events;

use Pest\Evals\Scorers\ScorerResult;

final readonly class Scored
{
    public bool $passed;

    public function __construct(
        public ScorerResult $result,
        public float $threshold,
        public string $input,
        public string $output,
        public ?string $expected,
        public int $sample,
        public int $samples,
    ) {
        $this->passed = $result->passed($threshold);
    }
}
