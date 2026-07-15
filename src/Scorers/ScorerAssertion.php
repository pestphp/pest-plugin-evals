<?php

declare(strict_types=1);

namespace Pest\Evals\Scorers;

use Pest\Evals\Configuration;
use Pest\Evals\Eval\EvalExpectationContext;
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

    public function assert(Scorer $scorer, string $output, float $threshold, ?EvalExpectationContext $context = null, ?string $expected = null): void
    {
        if (! Plugin::isEvalMode() && Configuration::usesDefaultDrivers()) {
            expect($output)->toBeString();

            return;
        }

        $outputs = $context?->getSampleOutputs() ?? [$output];
        $input = $context instanceof EvalExpectationContext ? $context->prompt : '';

        foreach ($outputs as $sampleOutput) {
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
                );
            }

            expect($result->score)->toBeGreaterThanOrEqual(
                $threshold,
                "{$scorerName} scored {$result->score} (threshold: {$threshold}). {$result->reasoning}",
            );
        }
    }
}
