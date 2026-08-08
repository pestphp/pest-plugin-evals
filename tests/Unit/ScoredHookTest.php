<?php

declare(strict_types=1);

use Pest\Evals\Configuration;
use Pest\Evals\Contracts\RequiresJudge;
use Pest\Evals\Events\Scored;
use Pest\Evals\Scorers\Relevance;
use Pest\Evals\Scorers\Scorer;
use Pest\Evals\Scorers\ScorerAssertion;
use Pest\Evals\Scorers\ScorerResult;
use PHPUnit\Framework\ExpectationFailedException;

beforeEach(function (): void {
    Configuration::flush();
    Pest\Evals\Plugin::resetEvalMode();
});

afterEach(function (): void {
    Configuration::flush();
    Pest\Evals\Plugin::resetEvalMode();
});

function scorerReturning(float $score, string $reasoning = 'because'): Scorer
{
    return new readonly class($score, $reasoning) implements Scorer
    {
        public function __construct(
            private float $score,
            private string $reasoning,
        ) {}

        public function score(string $input, string $output, ?string $expected = null): ScorerResult
        {
            return new ScorerResult($this->score, $this->reasoning, self::class);
        }
    };
}

it('reports a passing custom scorer result with complete context', function (): void {
    $events = [];

    pest()->evals()->afterScored(function (Scored $event) use (&$events): void {
        $events[] = $event;
    });

    (new ScorerAssertion)->assert(
        scorer: scorerReturning(0.91),
        outputs: ['Paris'],
        threshold: 0.8,
        expected: 'Paris',
    );

    expect($events)->toHaveCount(1)
        ->and($events[0]->result->score)->toBe(0.91)
        ->and($events[0]->threshold)->toBe(0.8)
        ->and($events[0]->input)->toBe('')
        ->and($events[0]->output)->toBe('Paris')
        ->and($events[0]->expected)->toBe('Paris')
        ->and($events[0]->sample)->toBe(1)
        ->and($events[0]->samples)->toBe(1)
        ->and($events[0]->passed)->toBeTrue();
});

it('reports a score equal to the threshold as passing', function (): void {
    $events = [];

    pest()->evals()->afterScored(function (Scored $event) use (&$events): void {
        $events[] = $event;
    });

    (new ScorerAssertion)->assert(scorerReturning(0.8), ['output'], 0.8);

    expect($events)->toHaveCount(1)
        ->and($events[0]->passed)->toBeTrue();
});

it('reports a failing result before the threshold assertion fails', function (): void {
    $events = [];

    pest()->evals()->afterScored(function (Scored $event) use (&$events): void {
        $events[] = $event;
    });

    expect(fn () => (new ScorerAssertion)->assert(
        scorer: scorerReturning(0.2, 'not relevant'),
        outputs: ['Madrid'],
        threshold: 0.7,
    ))->toThrow(ExpectationFailedException::class);

    expect($events)->toHaveCount(1)
        ->and($events[0]->passed)->toBeFalse()
        ->and($events[0]->result->reasoning)->toBe('not relevant');
});

it('reports every repeated sample in order through the public expectation API', function (): void {
    $events = [];
    $output = 0;

    pest()->evals()->afterScored(function (Scored $event) use (&$events): void {
        $events[] = $event;
    });

    $_SERVER['PEST_EVALS'] = '1';

    expect(function (string $prompt) use (&$output): string {
        $output++;

        return "{$prompt} {$output}";
    })->prompt('sample')->repeat(3)->toPassScorer(scorerReturning(1.0));

    expect(array_map(fn (Scored $event): array => [
        $event->input,
        $event->output,
        $event->sample,
        $event->samples,
    ], $events))->toBe([
        ['sample', 'sample 1', 1, 3],
        ['sample', 'sample 2', 2, 3],
        ['sample', 'sample 3', 3, 3],
    ]);
});

it('reports built-in scorer results through the public expectation API', function (): void {
    $events = [];

    pest()->evals()
        ->judgeUsing(fn (): string => '{"score":0.88,"reasoning":"direct answer"}')
        ->afterScored(function (Scored $event) use (&$events): void {
            $events[] = $event;
        });

    expect('Four')->toBeRelevant(0.8);

    expect($events)->toHaveCount(1)
        ->and($events[0]->result->scorer)->toBe(Relevance::class)
        ->and($events[0]->result->score)->toBe(0.88)
        ->and($events[0]->passed)->toBeTrue();
});

it('does not report when scoring is disabled', function (): void {
    $events = [];

    pest()->evals()->afterScored(function (Scored $event) use (&$events): void {
        $events[] = $event;
    });

    $scorer = new class implements RequiresJudge, Scorer
    {
        public function score(string $input, string $output, ?string $expected = null): ScorerResult
        {
            throw new RuntimeException('The scorer must not run.');
        }
    };

    (new ScorerAssertion)->assert($scorer, ['not scored'], 0.7);

    expect($events)->toBe([]);
});

it('propagates callback exceptions before the threshold assertion', function (): void {
    pest()->evals()->afterScored(function (): never {
        throw new RuntimeException('Recorder unavailable.');
    });

    expect(fn () => (new ScorerAssertion)->assert(
        scorer: scorerReturning(0.1),
        outputs: ['failing output'],
        threshold: 0.9,
    ))->toThrow(RuntimeException::class, 'Recorder unavailable.');
});

it('notifies multiple callbacks in registration order', function (): void {
    $calls = [];

    pest()->evals()->afterScored(function () use (&$calls): void {
        $calls[] = 'first';
    });

    pest()->evals()->afterScored(function () use (&$calls): void {
        $calls[] = 'second';
    });

    (new ScorerAssertion)->assert(scorerReturning(1.0), ['output'], 0.7);

    expect($calls)->toBe(['first', 'second']);
});

it('clears registered callbacks when the configuration is flushed', function (): void {
    $calls = 0;

    pest()->evals()->afterScored(function () use (&$calls): void {
        $calls++;
    });

    Configuration::flush();

    (new ScorerAssertion)->assert(scorerReturning(1.0), ['output'], 0.7);

    expect($calls)->toBe(0);
});
