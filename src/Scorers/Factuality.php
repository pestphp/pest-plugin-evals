<?php

declare(strict_types=1);

namespace Pest\Evals\Scorers;

use InvalidArgumentException;
use Pest\Evals\Contracts\RequiresJudge;
use Pest\Evals\Eval\Evaluation;
use Pest\Evals\Support\Judge;

/**
 * @internal
 */
final class Factuality implements RequiresJudge, Scorer
{
    public function score(string $input, string $output, ?string $expected = null): ScorerResult
    {
        if ($expected === null) {
            throw new InvalidArgumentException('The [Factuality] scorer requires a reference answer to compare against.');
        }

        return Judge::evaluate(self::class, new Evaluation(
            state: ['input' => $input, 'output' => $output, 'expected' => $expected],
            question: 'Classify the factual relationship between the output and the expected reference answer.',
            levels: [
                'Disagreement: the output contradicts the reference' => 0.0,
                'Subset: the output contains some but not all reference facts' => 0.6,
                'Superset: the output contains all reference facts plus additional correct facts' => 0.8,
                'Approximately equal: the output is very close, with minor wording differences' => 0.9,
                'Equal: the output contains the same facts as the reference' => 1.0,
            ],
        ));
    }
}
