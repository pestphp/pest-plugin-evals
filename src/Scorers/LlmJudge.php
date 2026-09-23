<?php

declare(strict_types=1);

namespace Pest\Evals\Scorers;

use Pest\Evals\Contracts\RequiresJudge;
use Pest\Evals\Eval\Evaluation;
use Pest\Evals\Support\Judge;

/**
 * @internal
 */
final class LlmJudge implements RequiresJudge, Scorer
{
    public function __construct(
        private readonly string $criteria = '',
    ) {}

    public function score(string $input, string $output, ?string $expected = null): ScorerResult
    {
        return Judge::evaluate(self::class, new Evaluation(
            state: array_filter(['input' => $input, 'output' => $output, 'expected' => $expected], fn (?string $value): bool => $value !== null),
            question: "Evaluate the output against these criteria: {$this->criteria}",
            levels: [
                'Completely fails to meet the criteria' => 0.0,
                'Mostly fails to meet the criteria' => 0.25,
                'Partially meets the criteria' => 0.5,
                'Mostly meets the criteria with minor gaps' => 0.75,
                'Fully meets all criteria' => 1.0,
            ],
        ));
    }
}
