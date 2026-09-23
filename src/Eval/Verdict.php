<?php

declare(strict_types=1);

namespace Pest\Evals\Eval;

final readonly class Verdict
{
    public function __construct(
        public float $score,
        public string $reasoning,
        public ?string $level = null,
    ) {}
}
