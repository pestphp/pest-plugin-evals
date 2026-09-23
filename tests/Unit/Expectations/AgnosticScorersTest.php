<?php

declare(strict_types=1);

use Pest\Evals\Configuration;
use Pest\Evals\Contracts\EmbeddingsDriver;
use Pest\Evals\Contracts\JudgeDriver;
use Pest\Evals\Drivers\LaravelAiEmbeddings;
use Pest\Evals\Drivers\LaravelAiJudge;
use Pest\Evals\Eval\Context;
use Pest\Evals\Eval\Evaluation;
use Pest\Evals\Eval\Verdict;
use Pest\Evals\Plugin;
use Pest\Evals\Scorers\SemanticSimilarity;

beforeEach(function (): void {
    Context::reset();
    Configuration::flush();
    Plugin::resetEvalMode();
    $_SERVER['PEST_EVALS'] = '1';
});

afterEach(function (): void {
    Configuration::flush();
    Plugin::resetEvalMode();
});

describe('custom judge driver', function (): void {
    it('scores through an injected closure without laravel/ai', function (): void {
        $calls = [];

        pest()->evals()->judgeUsing(function (Evaluation $evaluation) use (&$calls): float {
            $calls[] = $evaluation;

            return 1.0;
        });

        expect(fn (string $input): string => 'The capital of France is Paris.')
            ->prompt('What is the capital of France?')
            ->toBeRelevant()
            ->toPassJudge('Mentions Paris.');

        expect($calls)->toHaveCount(2);
    });

    it('accepts a contract instance', function (): void {
        $driver = new class implements JudgeDriver
        {
            public function judge(Evaluation $evaluation): Verdict
            {
                return new Verdict(1.0, 'matches');
            }
        };

        pest()->evals()->judgeUsing($driver);

        expect(fn (string $input): string => 'Tokyo')
            ->prompt('Capital of Japan?')
            ->toBeFactual(expected: 'Tokyo')
            ->toBeSafe();
    });
});

describe('fluent aliases', function (): void {
    it('aliases toPassJudge as toSatisfy', function (): void {
        $calls = [];

        pest()->evals()->judgeUsing(function (Evaluation $evaluation) use (&$calls): float {
            $calls[] = $evaluation;

            return 1.0;
        });

        expect(fn (string $input): string => 'The capital of France is Paris.')
            ->prompt('What is the capital of France?')
            ->toSatisfy('Mentions Paris.');

        expect($calls)->toHaveCount(1);
    });

    it('aliases toBeFactual as toBeCorrect', function (): void {
        $driver = new class implements JudgeDriver
        {
            public function judge(Evaluation $evaluation): Verdict
            {
                return new Verdict(1.0, 'matches');
            }
        };

        pest()->evals()->judgeUsing($driver);

        expect(fn (string $input): string => 'Tokyo')
            ->prompt('Capital of Japan?')
            ->toBeCorrect(expected: 'Tokyo');
    });
});

describe('custom embeddings driver', function (): void {
    it('scores similarity through an injected closure without laravel/ai', function (): void {
        pest()->evals()->embeddingsUsing(fn (array $inputs): array => [[1.0, 0.0, 0.0], [1.0, 0.0, 0.0]]);

        expect(fn (string $input): string => 'Berlin')
            ->prompt('Capital of Germany?')
            ->toBeSimilar('Berlin');
    });

    it('accepts a contract instance', function (): void {
        $driver = new class implements EmbeddingsDriver
        {
            public function embed(array $inputs): array
            {
                return [[1.0, 0.0], [0.0, 1.0]];
            }
        };

        pest()->evals()->embeddingsUsing($driver);

        $result = new SemanticSimilarity()->score('prompt', 'anything', 'other');

        expect($result->score)->toBe(0.0);
    });
});

describe('driver resolution', function (): void {
    it('defaults to the laravel/ai drivers', function (): void {
        expect(Configuration::resolvedJudge())->toBeInstanceOf(LaravelAiJudge::class)
            ->and(Configuration::resolvedEmbeddings())->toBeInstanceOf(LaravelAiEmbeddings::class);
    });

    it('resets injected drivers back to the defaults on flush', function (): void {
        pest()->evals()
            ->judgeUsing(fn (Evaluation $evaluation): float => 1.0)
            ->embeddingsUsing(fn (array $inputs): array => [[1.0], [1.0]]);

        Configuration::flush();

        expect(Configuration::resolvedJudge())->toBeInstanceOf(LaravelAiJudge::class)
            ->and(Configuration::resolvedEmbeddings())->toBeInstanceOf(LaravelAiEmbeddings::class);
    });
});
