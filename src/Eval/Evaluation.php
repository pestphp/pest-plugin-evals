<?php

declare(strict_types=1);

namespace Pest\Evals\Eval;

use InvalidArgumentException;

final readonly class Evaluation
{
    /**
     * @param  array<string, string>  $state  What is being judged, such as the input and output.
     * @param  string  $question  How to judge it.
     * @param  array<string, float>  $levels  Level descriptions mapped to their scores, ordered from worst to best.
     */
    public function __construct(
        public array $state,
        public string $question,
        public array $levels,
    ) {
        if (count($levels) < 2) {
            throw new InvalidArgumentException('An evaluation requires at least two levels.');
        }

        $previous = 0.0;

        foreach ($levels as $label => $score) {
            if ($score < $previous || $score > 1.0) {
                throw new InvalidArgumentException("Level [{$label}] must score between 0.0 and 1.0, ordered from worst to best.");
            }

            $previous = $score;
        }
    }

    /**
     * @return list<string>
     */
    public function labels(): array
    {
        return array_map(strval(...), array_keys($this->levels));
    }

    /**
     * @param  array<int, float>  $probabilities  Probabilities keyed by level index; missing levels count as zero.
     */
    public function weightedScore(array $probabilities): float
    {
        $total = array_sum($probabilities);

        if ($total <= 0.0) {
            return 0.0;
        }

        $score = 0.0;

        foreach (array_values($this->levels) as $index => $value) {
            $score += $value * ($probabilities[$index] ?? 0.0);
        }

        return $score / $total;
    }
}
