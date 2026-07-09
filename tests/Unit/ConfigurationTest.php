<?php

declare(strict_types=1);

use Pest\Evals\Configuration;
use Pest\Evals\Contracts\EmbeddingsDriver;
use Pest\Evals\Contracts\JudgeDriver;
use Pest\Evals\Drivers\LaravelAiEmbeddings;
use Pest\Evals\Drivers\LaravelAiJudge;

beforeEach(function (): void {
    Configuration::flush();
});

afterEach(function (): void {
    Configuration::flush();
});

describe('resolved defaults', function (): void {
    it('defaults to the laravel/ai drivers when nothing is configured', function (): void {
        expect(Configuration::resolvedJudge())->toBeInstanceOf(LaravelAiJudge::class)
            ->and(Configuration::resolvedEmbeddings())->toBeInstanceOf(LaravelAiEmbeddings::class);
    });
});

describe('fluent setters', function (): void {
    it('registers a judge driver and returns itself', function (): void {
        $config = new Configuration();
        $judge = new class implements JudgeDriver
        {
            public function generate(string $instructions, string $prompt): string
            {
                return '{"score": 1.0}';
            }
        };

        expect($config->judgeUsing($judge))->toBe($config)
            ->and(Configuration::resolvedJudge())->toBe($judge);
    });

    it('registers an embeddings driver and returns itself', function (): void {
        $config = new Configuration();
        $embeddings = new class implements EmbeddingsDriver
        {
            public function embed(array $inputs): array
            {
                return [[1.0]];
            }
        };

        expect($config->embeddingsUsing($embeddings))->toBe($config)
            ->and(Configuration::resolvedEmbeddings())->toBe($embeddings);
    });

    it('is chainable', function (): void {
        $config = new Configuration()
            ->judgeUsing(fn (string $instructions, string $prompt): string => '{"score": 1.0}')
            ->embeddingsUsing(fn (array $inputs): array => [[1.0]]);

        expect($config)->toBeInstanceOf(Configuration::class);
    });
});

describe('flush', function (): void {
    it('clears configured drivers back to the defaults', function (): void {
        new Configuration()
            ->judgeUsing(fn (string $instructions, string $prompt): string => '{"score": 1.0}')
            ->embeddingsUsing(fn (array $inputs): array => [[1.0]]);

        Configuration::flush();

        expect(Configuration::resolvedJudge())->toBeInstanceOf(LaravelAiJudge::class)
            ->and(Configuration::resolvedEmbeddings())->toBeInstanceOf(LaravelAiEmbeddings::class);
    });
});
