<?php

declare(strict_types=1);

namespace Pest\Evals\Drivers;

use Closure;
use InvalidArgumentException;
use Pest\Evals\Contracts\JudgeDriver;
use Pest\Evals\Eval\Evaluation;
use Pest\Evals\Eval\Verdict;

/**
 * @internal
 */
final readonly class ClosureJudge implements JudgeDriver
{
    /**
     * @param  Closure(Evaluation): mixed  $callback  Returns a float score or a Verdict.
     */
    public function __construct(
        private Closure $callback,
    ) {}

    public function judge(Evaluation $evaluation): Verdict
    {
        $verdict = ($this->callback)($evaluation);

        if ($verdict instanceof Verdict) {
            return $verdict;
        }

        if (! is_int($verdict) && ! is_float($verdict)) {
            throw new InvalidArgumentException('A judge closure must return a float score or a ['.Verdict::class.'], got ['.get_debug_type($verdict).'].');
        }

        return new Verdict((float) $verdict, 'Scored by a custom judge.');
    }
}
