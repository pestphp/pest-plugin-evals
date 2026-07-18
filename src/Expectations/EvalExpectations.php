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
use Pest\Expectations\OppositeExpectation;
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
     * @param  Expectation<string|Samples>  $expectation
     * @return Expectation<string|Samples>
     */
    public function repeat(Expectation $expectation, int $count): Expectation
    {
        if ($count < 1) {
            throw EvalExpectationException::invalidRepeatCount($count);
        }

        $context = Context::for($expectation);

        if (! $context instanceof Context) {
            throw EvalExpectationException::missingPrompt();
        }

        if ($expectation->value instanceof Samples) {
            throw EvalExpectationException::repeatAlreadyCalled();
        }

        $first = $expectation->value;

        $expectation->value = new Samples([
            $first,
            ...$context->resolveAdditionalOutputs($count - 1),
        ]);

        return $expectation;
    }

    /**
     * @param  MatcherExpectation<Samples>  $expectation
     */
    public function toContain(MatcherExpectation $expectation, string ...$needles): void
    {
        $outputs = $this->outputs($expectation);

        if ($this->isNegated()) {
            foreach ($outputs as $output) {
                foreach ($needles as $needle) {
                    if (str_contains($output, $needle)) {
                        return;
                    }
                }
            }

            Assert::fail('No sample contains any of the given needles.');
        }

        foreach ($outputs as $index => $output) {
            foreach ($needles as $needle) {
                Assert::assertStringContainsString($needle, $output, 'Sample #'.($index + 1)." does not contain '{$needle}'.");
            }
        }
    }

    /**
     * @param  MatcherExpectation<Samples>  $expectation
     */
    public function toMatch(MatcherExpectation $expectation, string $pattern, string $message = ''): void
    {
        $outputs = $this->outputs($expectation);

        if ($this->isNegated()) {
            foreach ($outputs as $output) {
                if (preg_match($pattern, $output) === 1) {
                    return;
                }
            }

            Assert::fail("No sample matches '{$pattern}'.");
        }

        foreach ($outputs as $index => $output) {
            Assert::assertMatchesRegularExpression($pattern, $output, $this->message($message, 'Sample #'.($index + 1)." does not match '{$pattern}'."));
        }
    }

    /**
     * @param  MatcherExpectation<Samples>  $expectation
     */
    public function toBe(MatcherExpectation $expectation, mixed $expected, string $message = ''): void
    {
        $outputs = $this->outputs($expectation);

        if ($this->isNegated()) {
            foreach ($outputs as $output) {
                if ($output === $expected) {
                    return;
                }
            }

            Assert::fail('No sample matches the expected value.');
        }

        foreach ($outputs as $index => $output) {
            Assert::assertSame($expected, $output, $this->message($message, 'Sample #'.($index + 1).' does not match expected.'));
        }
    }

    /**
     * @param  MatcherExpectation<Samples>  $expectation
     */
    public function toBeJson(MatcherExpectation $expectation, string $message = ''): void
    {
        $outputs = $this->outputs($expectation);

        if ($this->isNegated()) {
            foreach ($outputs as $output) {
                if (json_validate($output)) {
                    return;
                }
            }

            Assert::fail('No sample is valid JSON.');
        }

        foreach ($outputs as $index => $output) {
            Assert::assertJson($output, $this->message($message, 'Sample #'.($index + 1).' is not valid JSON.'));
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

    private function isNegated(): bool
    {
        foreach (debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 12) as $frame) {
            if (($frame['class'] ?? null) === OppositeExpectation::class) {
                return true;
            }
        }

        return false;
    }

    private function message(string $custom, string $default): string
    {
        return $custom === '' ? $default : "{$custom} {$default}";
    }
}
