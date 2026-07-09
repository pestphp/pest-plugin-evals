<?php

declare(strict_types=1);

namespace Pest\Evals\Drivers;

use Closure;
use Pest\Evals\Contracts\JudgeDriver;

/**
 * @internal
 */
final readonly class ClosureJudge implements JudgeDriver
{
    /**
     * @param  Closure(string, string): string  $callback
     */
    public function __construct(
        private Closure $callback,
    ) {}

    public function generate(string $instructions, string $prompt): string
    {
        return (string) ($this->callback)($instructions, $prompt);
    }
}
