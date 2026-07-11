<?php

declare(strict_types=1);

namespace Pest\Evals;

use Closure;
use Laravel\Ai\Contracts\Agent;
use Pest\Evals\Expectations\EvalExpectations;
use Pest\Evals\Scorers\Scorer;
use Pest\Evals\Support\Samples;
use Pest\Expectation;

$evals = new EvalExpectations;

expect()->extend('prompt', function (string $prompt, array $fake = [], array $attachments = []) use ($evals): Expectation {
    /** @var Expectation<string|Closure|Agent> $this */
    /** @var array<int, string> $fake */
    /** @var array<int, mixed> $attachments */
    return $evals->prompt($this, $prompt, $fake, $attachments);
});

expect()->extend('repeat', function (int $count) use ($evals): Expectation {
    /** @var Expectation<string> $this */
    return $evals->repeat($this, $count);
});

expect()->intercept('toContain', Samples::has(...), function (string $needle) use ($evals): void {
    $evals->toContain($needle);
});

expect()->intercept('toMatch', Samples::has(...), function (string $pattern) use ($evals): void {
    $evals->toMatch($pattern);
});

expect()->intercept('toBe', Samples::has(...), function (mixed $expected) use ($evals): void {
    $evals->toBe($expected);
});

expect()->intercept('toBeJson', Samples::has(...), function () use ($evals): void {
    $evals->toBeJson();
});

expect()->extend('toBeRelevant', function (float $threshold = Scorer::DEFAULT_THRESHOLD) use ($evals): Expectation {
    /** @var Expectation<string> $this */
    return $evals->toBeRelevant($this, $threshold);
});

expect()->extend('toBeSafe', function (float $threshold = Scorer::DEFAULT_THRESHOLD) use ($evals): Expectation {
    /** @var Expectation<string> $this */
    return $evals->toBeSafe($this, $threshold);
});

expect()->extend('toBeFactual', function (string $expected, float $threshold = Scorer::DEFAULT_THRESHOLD) use ($evals): Expectation {
    /** @var Expectation<string> $this */
    return $evals->toBeFactual($this, $expected, $threshold);
});

expect()->extend('toPassJudge', function (string $criteria, float $threshold = Scorer::DEFAULT_THRESHOLD) use ($evals): Expectation {
    /** @var Expectation<string> $this */
    return $evals->toPassJudge($this, $criteria, $threshold);
});

expect()->extend('toBeSimilar', function (string $expected, float $threshold = Scorer::DEFAULT_THRESHOLD) use ($evals): Expectation {
    /** @var Expectation<string> $this */
    return $evals->toBeSimilar($this, $expected, $threshold);
});

expect()->extend('toHaveToolCalls', function (array $expected, float $threshold = Scorer::DEFAULT_THRESHOLD) use ($evals): Expectation {
    /** @var Expectation<string> $this */
    /** @var array<string, array<string, mixed>|Closure> $expected */
    return $evals->toHaveToolCalls($this, $expected, $threshold);
});

expect()->extend('toFollowTrajectory', function (array $steps, float $threshold = Scorer::DEFAULT_THRESHOLD, bool $strictOrder = true) use ($evals): Expectation {
    /** @var Expectation<string> $this */
    /** @var array<int, string> $steps */
    return $evals->toFollowTrajectory($this, $steps, $threshold, $strictOrder);
});

expect()->extend('toPassScorer', function (Scorer $scorer, float $threshold = Scorer::DEFAULT_THRESHOLD, ?string $expected = null) use ($evals): Expectation {
    /** @var Expectation<string> $this */
    return $evals->toPassScorer($this, $scorer, $threshold, $expected);
});
