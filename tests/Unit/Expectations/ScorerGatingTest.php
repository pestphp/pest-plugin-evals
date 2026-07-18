<?php

declare(strict_types=1);

use Pest\Evals\Configuration;
use Pest\Evals\Eval\Context;
use Pest\Evals\Plugin;
use Pest\Evals\Scorers\Scorer;
use Pest\Evals\Scorers\ScorerResult;
use PHPUnit\Framework\ExpectationFailedException;

beforeEach(function (): void {
    Context::reset();
    Configuration::flush();
    Plugin::resetEvalMode();
});

afterEach(function (): void {
    Configuration::flush();
    Plugin::resetEvalMode();
});

describe('outside eval mode', function (): void {
    it('does not run judge scorers when only an embeddings driver is configured', function (): void {
        pest()->evals()->embeddingsUsing(fn (array $inputs): array => [[1.0], [1.0]]);

        expect('any hardcoded string')->toBeSafe()->toBeRelevant();
    });

    it('does not run embeddings scorers when only a judge driver is configured', function (): void {
        pest()->evals()->judgeUsing(fn (string $instructions, string $prompt): string => '{"score": 1.0}');

        expect('any hardcoded string')->toBeSimilar('anything');
    });

    it('runs judge scorers when a custom judge driver is configured', function (): void {
        $calls = 0;

        pest()->evals()->judgeUsing(function (string $instructions, string $prompt) use (&$calls): string {
            $calls++;

            return '{"score": 1.0, "reasoning": "ok"}';
        });

        expect('output')->toBeRelevant();

        expect($calls)->toBe(1);
    });

    it('runs embeddings scorers when a custom embeddings driver is configured', function (): void {
        $calls = 0;

        pest()->evals()->embeddingsUsing(function (array $inputs) use (&$calls): array {
            $calls++;

            return array_fill(0, count($inputs), [1.0, 0.0]);
        });

        expect('output')->toBeSimilar('output');

        expect($calls)->toBeGreaterThan(0);
    });

    it('always runs deterministic tool call scorers', function (): void {
        expect(fn () => expect('not a tool call payload')->toHaveToolCalls(['GetWeather' => []]))
            ->toThrow(ExpectationFailedException::class);

        expect('[{"name": "GetWeather", "arguments": {"city": "Lisbon"}}]')
            ->toHaveToolCalls(['GetWeather' => ['city' => 'Lisbon']]);
    });

    it('always runs deterministic trajectory scorers', function (): void {
        expect(fn () => expect('not a tool call payload')->toFollowTrajectory(['Search']))
            ->toThrow(ExpectationFailedException::class);

        expect('["Search", "Summarize"]')->toFollowTrajectory(['Search', 'Summarize']);
    });

    it('always runs custom scorers without driver markers', function (): void {
        $tracker = new stdClass;
        $tracker->calls = 0;

        $scorer = new class($tracker) implements Scorer
        {
            public function __construct(private readonly stdClass $tracker) {}

            public function score(string $input, string $output, ?string $expected = null): ScorerResult
            {
                $this->tracker->calls++;

                return new ScorerResult(score: 1.0, reasoning: 'ok', scorer: self::class);
            }
        };

        expect('output')->toPassScorer($scorer);

        expect($tracker->calls)->toBe(1);
    });
});

describe('threshold validation', function (): void {
    it('rejects a threshold above one', function (): void {
        expect(fn () => expect('output')->toBeSafe(1.5))
            ->toThrow(InvalidArgumentException::class, 'The threshold must be between 0.0 and 1.0, [1.5] given.');
    });

    it('rejects a negative threshold', function (): void {
        expect(fn () => expect('output')->toBeRelevant(-0.1))
            ->toThrow(InvalidArgumentException::class);
    });

    it('accepts the boundaries', function (): void {
        pest()->evals()->judgeUsing(fn (string $instructions, string $prompt): string => '{"score": 1.0, "reasoning": "ok"}');

        expect('output')->toBeRelevant(0.0)->toBeRelevant(1.0);
    });
});
