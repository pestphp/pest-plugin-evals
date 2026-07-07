<?php

declare(strict_types=1);

namespace Pest\Evals;

use Closure;
use Laravel\Ai\Contracts\Agent;
use Pest\Evals\Eval\EvalExpectationContext;
use Pest\Evals\Eval\EvalReport;
use Pest\Evals\Scorers\AgentTrajectory;
use Pest\Evals\Scorers\Factuality;
use Pest\Evals\Scorers\LlmJudge;
use Pest\Evals\Scorers\Relevance;
use Pest\Evals\Scorers\Safety;
use Pest\Evals\Scorers\Scorer;
use Pest\Evals\Scorers\SemanticSimilarity;
use Pest\Evals\Scorers\ToolCallMatch;
use Pest\Expectation;
use RuntimeException;

/**
 * @param  list<string>  $fake
 * @param  list<mixed>  $attachments
 */
function expectAgent(
    string|Closure|Agent $agent,
    string $prompt,
    array $fake = [],
    array $attachments = [],
): mixed {
    $agentName = match (true) {
        $agent instanceof Closure => 'Task',
        $agent instanceof Agent => class_basename($agent),
        default => class_basename($agent),
    };

    $ctx = new EvalExpectationContext(
        prompt: $prompt,
        agentName: $agentName,
        fakedResponses: $fake,
        attachments: $attachments,
    );

    EvalExpectationContext::$current = $ctx;

    $outputs = $ctx->resolveOutputs($agent);

    return expect($outputs[0]);
}

expect()->extend('repeat', function (int $count): Expectation {
    /** @var Expectation<string> $this */
    $ctx = EvalExpectationContext::$current;

    if (! $ctx instanceof EvalExpectationContext) {
        throw new RuntimeException('repeat() requires expectAgent() to be called first.');
    }

    $additional = $ctx->resolveAdditionalOutputs($count - 1);
    $ctx->setSampleOutputs([$this->value, ...$additional]);

    return $this;
});

$hasSamples = fn (mixed $value): bool => is_string($value)
    && EvalExpectationContext::$current instanceof EvalExpectationContext
    && EvalExpectationContext::$current->getSampleOutputs() !== null
    && in_array($value, EvalExpectationContext::$current->getSampleOutputs(), true);

/**
 * @internal
 *
 * @return list<string>
 */
function currentSamples(): array
{
    $ctx = EvalExpectationContext::$current;

    if (! $ctx instanceof EvalExpectationContext) {
        return [];
    }

    return $ctx->getSampleOutputs() ?? [];
}

expect()->intercept('toContain', $hasSamples, function (string $needle): void {
    foreach (currentSamples() as $i => $output) {
        \PHPUnit\Framework\Assert::assertStringContainsString($needle, $output, 'Sample #'.($i + 1)." does not contain '{$needle}'.");
    }
});

expect()->intercept('toMatch', $hasSamples, function (string $pattern): void {
    foreach (currentSamples() as $i => $output) {
        \PHPUnit\Framework\Assert::assertMatchesRegularExpression($pattern, $output, 'Sample #'.($i + 1)." does not match '{$pattern}'.");
    }
});

expect()->intercept('toBe', $hasSamples, function (mixed $expected): void {
    foreach (currentSamples() as $i => $output) {
        \PHPUnit\Framework\Assert::assertSame($expected, $output, 'Sample #'.($i + 1).' does not match expected.');
    }
});

expect()->intercept('toBeJson', $hasSamples, function (): void {
    foreach (currentSamples() as $i => $output) {
        \PHPUnit\Framework\Assert::assertJson($output, 'Sample #'.($i + 1).' is not valid JSON.');
    }
});

/**
 * @internal
 */
function assertScorerResult(Scorer $scorer, string $output, float $threshold, ?string $expected = null): void
{
    $ctx = EvalExpectationContext::$current;
    $outputs = ($ctx instanceof EvalExpectationContext ? $ctx->getSampleOutputs() : null) ?? [$output];
    $input = EvalExpectationContext::currentPrompt();
    $agent = EvalExpectationContext::currentAgentName();

    foreach ($outputs as $sampleOutput) {
        $result = $scorer->score($input, $sampleOutput, $expected);

        EvalReport::instance()->addScorerResult($agent, $result->scorer, $result->score, $threshold);

        $scorerName = class_basename($result->scorer);
        $passed = $result->score >= $threshold;

        if (Plugin::isVerbose()) {
            $icon = $passed ? '<fg=green>✓ PASS</>' : '<fg=red>✗ FAIL</>';
            $score = number_format($result->score * 100);

            /** @var \Symfony\Component\Console\Output\OutputInterface $console */
            $console = \Pest\Support\Container::getInstance()->get(\Symfony\Component\Console\Output\OutputInterface::class);

            $lines = [
                '',
                '  <fg=gray>'.str_repeat('─', 60).'</>',
                "  Assertion: <fg=white>{$scorerName}</> (threshold: {$threshold})",
                '',
                "  {$icon}",
                '',
                '  <fg=gray>Input:</>',
                '  <fg=white>"'.mb_strimwidth($input, 0, 120, '...').'"</>',
                '',
                '  <fg=gray>Output:</>',
                '  <fg=white>"'.mb_strimwidth($sampleOutput, 0, 200, '...').'"</>',
                '',
                '  <fg=gray>Reasoning:</>',
                '  <fg=white>"'.$result->reasoning.'"</>',
                '',
                "  Score: <fg=white>{$score} / 100</>",
                '  <fg=gray>'.str_repeat('─', 60).'</>',
            ];

            foreach ($lines as $line) {
                $console->writeln($line);
            }
        }

        expect($result->score)->toBeGreaterThanOrEqual(
            $threshold,
            "{$scorerName} scored {$result->score} (threshold: {$threshold}). {$result->reasoning}",
        );
    }
}

expect()->extend('toBeRelevant', function (float $threshold = 0.7): Expectation {
    /** @var Expectation<string> $this */
    assertScorerResult(new Relevance(), $this->value, $threshold);

    return $this;
});

expect()->extend('toBeSafe', function (float $threshold = 0.7): Expectation {
    /** @var Expectation<string> $this */
    assertScorerResult(new Safety(), $this->value, $threshold);

    return $this;
});

expect()->extend('toBeFactual', function (float $threshold = 0.7, ?string $expected = null): Expectation {
    /** @var Expectation<string> $this */
    assertScorerResult(new Factuality(), $this->value, $threshold, $expected);

    return $this;
});

expect()->extend('toPassJudge', function (string $criteria, float $threshold = 0.7): Expectation {
    /** @var Expectation<string> $this */
    assertScorerResult(new LlmJudge(criteria: $criteria), $this->value, $threshold);

    return $this;
});

expect()->extend('toBeSimilar', function (string $expected, float $threshold = 0.7): Expectation {
    /** @var Expectation<string> $this */
    assertScorerResult(new SemanticSimilarity(), $this->value, $threshold, $expected);

    return $this;
});

/**
 * @param  array<string, array<string, mixed>|Closure>  $expected
 */
expect()->extend('toHaveToolCalls', function (array $expected, float $threshold = 0.7): Expectation {
    /** @var Expectation<string> $this */
    /** @var array<string, array<string, mixed>|Closure> $expected */
    assertScorerResult(new ToolCallMatch(tools: $expected), $this->value, $threshold);

    return $this;
});

/**
 * @param  list<string>  $steps
 */
expect()->extend('toFollowTrajectory', function (array $steps, float $threshold = 0.7, bool $strictOrder = true): Expectation {
    /** @var Expectation<string> $this */
    /** @var list<string> $steps */
    assertScorerResult(new AgentTrajectory(sequence: $steps, strictOrder: $strictOrder), $this->value, $threshold);

    return $this;
});

expect()->extend('toPassScorer', function (Scorer $scorer, float $threshold = 0.7, ?string $expected = null): Expectation {
    /** @var Expectation<string> $this */
    assertScorerResult($scorer, $this->value, $threshold, $expected);

    return $this;
});
