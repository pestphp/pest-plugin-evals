<?php

declare(strict_types=1);

namespace Pest\Evals\Expectations;

use Closure;
use Laravel\Ai\Contracts\Agent;
use Pest\Evals\Eval\EvalExpectationContext;
use Pest\Evals\Exceptions\EvalExpectationException;
use Pest\Evals\Plugin;
use Pest\Evals\Scorers\AgentTrajectory;
use Pest\Evals\Scorers\Factuality;
use Pest\Evals\Scorers\LlmJudge;
use Pest\Evals\Scorers\Relevance;
use Pest\Evals\Scorers\Safety;
use Pest\Evals\Scorers\Scorer;
use Pest\Evals\Scorers\ScorerAssertion;
use Pest\Evals\Scorers\SemanticSimilarity;
use Pest\Evals\Scorers\ToolCallMatch;
use Pest\Evals\Support\Samples;
use Pest\Expectation;
use PHPUnit\Framework\Assert;

/**
 * @internal
 */
final class EvalExpectations
{
    public function __construct(
        private readonly ScorerAssertion $scorerAssertion = new ScorerAssertion,
    ) {}

    /**
     * @param  Expectation<string|Closure|Agent>  $expectation
     * @param  array<int, mixed>  $attachments
     * @return Expectation<string|Closure|Agent>
     */
    public function prompt(Expectation $expectation, string $prompt, array $attachments = []): Expectation
    {
        if (! Plugin::isEvalMode()) {
            Assert::markTestSkipped('Eval skipped. Run with [--evals] to evaluate against a real model.');
        }

        $agent = $expectation->value;

        $context = new EvalExpectationContext(
            prompt: $prompt,
            agentName: $agent instanceof Closure ? 'Task' : class_basename($agent),
            attachments: $attachments,
        );

        EvalExpectationContext::bind($expectation, $context);

        $expectation->value = $context->resolveOutputs($agent)[0];

        return $expectation;
    }

    /**
     * @param  Expectation<string>  $expectation
     * @return Expectation<string>
     */
    public function repeat(Expectation $expectation, int $count): Expectation
    {
        $context = EvalExpectationContext::for($expectation);

        if (! $context instanceof EvalExpectationContext) {
            throw EvalExpectationException::missingPrompt();
        }

        $context->setSampleOutputs([$expectation->value, ...$context->resolveAdditionalOutputs($count - 1)]);

        return $expectation;
    }

    public function toContain(string $needle): void
    {
        foreach (Samples::all() as $index => $output) {
            Assert::assertStringContainsString($needle, $output, 'Sample #'.($index + 1)." does not contain '{$needle}'.");
        }
    }

    public function toMatch(string $pattern): void
    {
        foreach (Samples::all() as $index => $output) {
            Assert::assertMatchesRegularExpression($pattern, $output, 'Sample #'.($index + 1)." does not match '{$pattern}'.");
        }
    }

    public function toBe(mixed $expected): void
    {
        foreach (Samples::all() as $index => $output) {
            Assert::assertSame($expected, $output, 'Sample #'.($index + 1).' does not match expected.');
        }
    }

    public function toBeJson(): void
    {
        foreach (Samples::all() as $index => $output) {
            Assert::assertJson($output, 'Sample #'.($index + 1).' is not valid JSON.');
        }
    }

    /**
     * @param  Expectation<string>  $expectation
     * @return Expectation<string>
     */
    public function toBeRelevant(Expectation $expectation, float $threshold = Scorer::DEFAULT_THRESHOLD): Expectation
    {
        $this->scorerAssertion->assert(new Relevance, $expectation->value, $threshold, EvalExpectationContext::for($expectation));

        return $expectation;
    }

    /**
     * @param  Expectation<string>  $expectation
     * @return Expectation<string>
     */
    public function toBeSafe(Expectation $expectation, float $threshold = Scorer::DEFAULT_THRESHOLD): Expectation
    {
        $this->scorerAssertion->assert(new Safety, $expectation->value, $threshold, EvalExpectationContext::for($expectation));

        return $expectation;
    }

    /**
     * @param  Expectation<string>  $expectation
     * @return Expectation<string>
     */
    public function toBeFactual(Expectation $expectation, string $expected, float $threshold = Scorer::DEFAULT_THRESHOLD): Expectation
    {
        $this->scorerAssertion->assert(new Factuality, $expectation->value, $threshold, EvalExpectationContext::for($expectation), $expected);

        return $expectation;
    }

    /**
     * @param  Expectation<string>  $expectation
     * @return Expectation<string>
     */
    public function toPassJudge(Expectation $expectation, string $criteria, float $threshold = Scorer::DEFAULT_THRESHOLD): Expectation
    {
        $this->scorerAssertion->assert(new LlmJudge(criteria: $criteria), $expectation->value, $threshold, EvalExpectationContext::for($expectation));

        return $expectation;
    }

    /**
     * @param  Expectation<string>  $expectation
     * @return Expectation<string>
     */
    public function toBeSimilar(Expectation $expectation, string $expected, float $threshold = Scorer::DEFAULT_THRESHOLD): Expectation
    {
        $this->scorerAssertion->assert(new SemanticSimilarity, $expectation->value, $threshold, EvalExpectationContext::for($expectation), $expected);

        return $expectation;
    }

    /**
     * @param  Expectation<string>  $expectation
     * @param  array<string, array<string, mixed>|Closure>  $expected
     * @return Expectation<string>
     */
    public function toHaveToolCalls(Expectation $expectation, array $expected, float $threshold = Scorer::DEFAULT_THRESHOLD): Expectation
    {
        $this->scorerAssertion->assert(new ToolCallMatch(tools: $expected), $expectation->value, $threshold, EvalExpectationContext::for($expectation));

        return $expectation;
    }

    /**
     * @param  Expectation<string>  $expectation
     * @param  array<int, string>  $steps
     * @return Expectation<string>
     */
    public function toFollowTrajectory(Expectation $expectation, array $steps, float $threshold = Scorer::DEFAULT_THRESHOLD, bool $strictOrder = true): Expectation
    {
        $this->scorerAssertion->assert(new AgentTrajectory(sequence: $steps, strictOrder: $strictOrder), $expectation->value, $threshold, EvalExpectationContext::for($expectation));

        return $expectation;
    }

    /**
     * @param  Expectation<string>  $expectation
     * @return Expectation<string>
     */
    public function toPassScorer(Expectation $expectation, Scorer $scorer, float $threshold = Scorer::DEFAULT_THRESHOLD, ?string $expected = null): Expectation
    {
        $this->scorerAssertion->assert($scorer, $expectation->value, $threshold, EvalExpectationContext::for($expectation), $expected);

        return $expectation;
    }
}
