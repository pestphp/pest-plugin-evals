<?php

declare(strict_types=1);

namespace Pest\Evals\Expectations;

use Closure;
use Laravel\Ai\Contracts\Agent;
use Pest\Evals\Eval\Context;
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
use Pest\Mixins\Expectation as MatcherExpectation;
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

        $context = Context::for($expectation);
        $agent = $context instanceof Context ? $context->agent : $expectation->value;

        $context = new Context(
            agent: $agent,
            prompt: $prompt,
            agentName: $agent instanceof Closure ? 'Task' : class_basename($agent),
            attachments: $attachments,
        );

        Context::bind($expectation, $context);

        $expectation->value = $context->resolveOutputs()[0];

        return $expectation;
    }

    /**
     * @param  Expectation<string>  $expectation
     * @return Expectation<string>
     */
    public function repeat(Expectation $expectation, int $count): Expectation
    {
        $context = Context::for($expectation);

        if (! $context instanceof Context) {
            throw EvalExpectationException::missingPrompt();
        }

        $first = $expectation->value;

        $expectation->value = new Samples([ // @phpstan-ignore assign.propertyType
            $first,
            ...$context->resolveAdditionalOutputs($count - 1),
        ]);

        return $expectation;
    }

    /**
     * @param  MatcherExpectation<Samples>  $expectation
     */
    public function toContain(MatcherExpectation $expectation, string $needle): void
    {
        foreach ($this->outputs($expectation) as $index => $output) {
            Assert::assertStringContainsString($needle, $output, 'Sample #'.($index + 1)." does not contain '{$needle}'.");
        }
    }

    /**
     * @param  MatcherExpectation<Samples>  $expectation
     */
    public function toMatch(MatcherExpectation $expectation, string $pattern): void
    {
        foreach ($this->outputs($expectation) as $index => $output) {
            Assert::assertMatchesRegularExpression($pattern, $output, 'Sample #'.($index + 1)." does not match '{$pattern}'.");
        }
    }

    /**
     * @param  MatcherExpectation<Samples>  $expectation
     */
    public function toBe(MatcherExpectation $expectation, mixed $expected): void
    {
        foreach ($this->outputs($expectation) as $index => $output) {
            Assert::assertSame($expected, $output, 'Sample #'.($index + 1).' does not match expected.');
        }
    }

    /**
     * @param  MatcherExpectation<Samples>  $expectation
     */
    public function toBeJson(MatcherExpectation $expectation): void
    {
        foreach ($this->outputs($expectation) as $index => $output) {
            Assert::assertJson($output, 'Sample #'.($index + 1).' is not valid JSON.');
        }
    }

    /**
     * @param  Expectation<string>  $expectation
     * @return Expectation<string>
     */
    public function toBeRelevant(Expectation $expectation, float $threshold = Scorer::DEFAULT_THRESHOLD): Expectation
    {
        $this->scorerAssertion->assert(new Relevance, $this->outputs($expectation), $threshold, Context::for($expectation));

        return $expectation;
    }

    /**
     * @param  Expectation<string>  $expectation
     * @return Expectation<string>
     */
    public function toBeSafe(Expectation $expectation, float $threshold = Scorer::DEFAULT_THRESHOLD): Expectation
    {
        $this->scorerAssertion->assert(new Safety, $this->outputs($expectation), $threshold, Context::for($expectation));

        return $expectation;
    }

    /**
     * @param  Expectation<string>  $expectation
     * @return Expectation<string>
     */
    public function toBeFactual(Expectation $expectation, string $expected, float $threshold = Scorer::DEFAULT_THRESHOLD): Expectation
    {
        $this->scorerAssertion->assert(new Factuality, $this->outputs($expectation), $threshold, Context::for($expectation), $expected);

        return $expectation;
    }

    /**
     * @param  Expectation<string>  $expectation
     * @return Expectation<string>
     */
    public function toPassJudge(Expectation $expectation, string $criteria, float $threshold = Scorer::DEFAULT_THRESHOLD): Expectation
    {
        $this->scorerAssertion->assert(new LlmJudge(criteria: $criteria), $this->outputs($expectation), $threshold, Context::for($expectation));

        return $expectation;
    }

    /**
     * @param  Expectation<string>  $expectation
     * @return Expectation<string>
     */
    public function toBeSimilar(Expectation $expectation, string $expected, float $threshold = Scorer::DEFAULT_THRESHOLD): Expectation
    {
        $this->scorerAssertion->assert(new SemanticSimilarity, $this->outputs($expectation), $threshold, Context::for($expectation), $expected);

        return $expectation;
    }

    /**
     * @param  Expectation<string>  $expectation
     * @param  array<string, array<string, mixed>|Closure>  $expected
     * @return Expectation<string>
     */
    public function toHaveToolCalls(Expectation $expectation, array $expected, float $threshold = Scorer::DEFAULT_THRESHOLD): Expectation
    {
        $this->scorerAssertion->assert(new ToolCallMatch(tools: $expected), $this->outputs($expectation), $threshold, Context::for($expectation));

        return $expectation;
    }

    /**
     * @param  Expectation<string>  $expectation
     * @param  array<int, string>  $steps
     * @return Expectation<string>
     */
    public function toFollowTrajectory(Expectation $expectation, array $steps, float $threshold = Scorer::DEFAULT_THRESHOLD, bool $strictOrder = true): Expectation
    {
        $this->scorerAssertion->assert(new AgentTrajectory(sequence: $steps, strictOrder: $strictOrder), $this->outputs($expectation), $threshold, Context::for($expectation));

        return $expectation;
    }

    /**
     * @param  Expectation<string>  $expectation
     * @return Expectation<string>
     */
    public function toPassScorer(Expectation $expectation, Scorer $scorer, float $threshold = Scorer::DEFAULT_THRESHOLD, ?string $expected = null): Expectation
    {
        $this->scorerAssertion->assert($scorer, $this->outputs($expectation), $threshold, Context::for($expectation), $expected);

        return $expectation;
    }

    /**
     * @param  Expectation<string>|MatcherExpectation<Samples>  $expectation
     * @return array<int, string>
     */
    private function outputs(Expectation|MatcherExpectation $expectation): array
    {
        $value = $expectation->value;

        return $value instanceof Samples ? $value->outputs : [$value];
    }
}
