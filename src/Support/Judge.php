<?php

declare(strict_types=1);

namespace Pest\Evals\Support;

use Pest\Evals\Configuration;
use Pest\Evals\Eval\Evaluation;
use Pest\Evals\Scorers\ScorerResult;

final class Judge
{
    /**
     * @param  class-string  $scorer
     */
    public static function evaluate(string $scorer, Evaluation $evaluation): ScorerResult
    {
        $verdict = Configuration::resolvedJudge()->judge($evaluation);

        return new ScorerResult(
            score: max(0.0, min(1.0, $verdict->score)),
            reasoning: $verdict->level === null ? $verdict->reasoning : "[{$verdict->level}] {$verdict->reasoning}",
            scorer: $scorer,
        );
    }
}
