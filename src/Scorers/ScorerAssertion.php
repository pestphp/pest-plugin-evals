<?php

declare(strict_types=1);

namespace Pest\Evals\Scorers;

use InvalidArgumentException;
use Pest\Evals\Configuration;
use Pest\Evals\Contracts\RequiresEmbeddings;
use Pest\Evals\Contracts\RequiresJudge;
use Pest\Evals\Eval\Context;
use Pest\Evals\Plugin;
use Pest\Evals\Support\VerbosePanel;

/**
 * @internal
 */
final class ScorerAssertion
{
    public function __construct(
        private readonly VerbosePanel $panel = new VerbosePanel,
    ) {}

    /**
     * @param  array<int, string>  $outputs
     */
    public function assert(Scorer $scorer, array $outputs, float $threshold, ?Context $context = null, ?string $expected = null): void
    {
        if ($threshold < 0.0 || $threshold > 1.0) {
            throw new InvalidArgumentException("The threshold must be between 0.0 and 1.0, [{$threshold}] given.");
        }

        if (! $this->shouldScore($scorer)) {
            expect($outputs)->each->toBeString();

            return;
        }

        $input = $context instanceof Context ? $context->prompt : '';
        $samples = count($outputs);

        foreach (array_values($outputs) as $index => $sampleOutput) {
            $result = $scorer->score($input, $sampleOutput, $expected);

            $scorerName = class_basename($result->scorer);
            $passed = $result->score >= $threshold;

            if (Plugin::isVerbose()) {
                $this->panel->render(
                    scorer: $scorerName,
                    threshold: $threshold,
                    passed: $passed,
                    input: $input,
                    output: $sampleOutput,
                    reasoning: $result->reasoning,
                    score: $result->score,
                    sample: $index + 1,
                    samples: $samples,
                );
            }

            $prefix = $samples > 1 ? 'Sample #'.($index + 1).': ' : '';

            expect($result->score)->toBeGreaterThanOrEqual(
                $threshold,
                "{$prefix}{$scorerName} scored {$result->score} (threshold: {$threshold}). {$result->reasoning}",
            );
        }
    }

    private function shouldScore(Scorer $scorer): bool
    {
        if (Plugin::isEvalMode()) {
            return true;
        }

        if ($scorer instanceof RequiresJudge && ! Configuration::usesStubbedJudge()) {
            return false;
        }

        return ! $scorer instanceof RequiresEmbeddings || ! Configuration::usesDefaultEmbeddings();
    }
}
