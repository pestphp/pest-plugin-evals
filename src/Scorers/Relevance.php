<?php

declare(strict_types=1);

namespace Pest\Evals\Scorers;

use Pest\Evals\Contracts\RequiresJudge;
use Pest\Evals\Eval\Evaluation;
use Pest\Evals\Support\Judge;

/**
 * @internal
 */
final class Relevance implements RequiresJudge, Scorer
{
    public function score(string $input, string $output, ?string $expected = null): ScorerResult
    {
        return Judge::evaluate(self::class, new Evaluation(
            state: ['input' => $input, 'output' => $output],
            question: 'Evaluate the relevance of the output to the input. Consider whether the output addresses the question or request, stays on-topic, and avoids off-topic or irrelevant information.',
            levels: [
                'Completely off-topic' => 0.0,
                'Mostly irrelevant' => 0.25,
                'Partially relevant, some off-topic content' => 0.5,
                'Mostly relevant with minor tangents' => 0.75,
                'Perfectly relevant, directly addresses the input' => 1.0,
            ],
        ));
    }
}
