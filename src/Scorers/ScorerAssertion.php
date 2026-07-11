<?php

declare(strict_types=1);

namespace Pest\Evals\Scorers;

use Pest\Evals\Eval\EvalExpectationContext;
use Pest\Evals\Eval\EvalReport;
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
        $outputs = $context?->getSampleOutputs() ?? [$output];
        $input = $context instanceof EvalExpectationContext ? $context->prompt : '';
        $agent = $context instanceof EvalExpectationContext ? $context->agentName : 'Direct';

        foreach ($outputs as $sampleOutput) {
            $result = $scorer->score($input, $sampleOutput, $expected);

            EvalReport::instance()->addScorerResult($agent, $result->scorer, $result->score, $threshold);

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
