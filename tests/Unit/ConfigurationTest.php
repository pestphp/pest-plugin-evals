<?php

declare(strict_types=1);

use Pest\Evals\Configuration;

beforeEach(function (): void {
    Configuration::flush();
});

afterEach(function (): void {
    Configuration::flush();
    putenv('EVAL_SCORING_PROVIDER');
    putenv('EVAL_SCORING_MODEL');
    putenv('EVAL_EMBEDDING_PROVIDER');
    putenv('EVAL_EMBEDDING_MODEL');
});

describe('resolved defaults', function (): void {
    it('resolves the hardcoded defaults when nothing is configured', function (): void {
        expect(Configuration::resolvedScoringProvider())->toBe('openai')
            ->and(Configuration::resolvedScoringModel())->toBe('gpt-5.4-nano')
            ->and(Configuration::resolvedEmbeddingProvider())->toBe('openai')
            ->and(Configuration::resolvedEmbeddingModel())->toBe('text-embedding-3-small');
    });
});

describe('fluent setters', function (): void {
    it('updates the matching resolved getter and returns itself', function (): void {
        $config = new Configuration();

        expect($config->scoringProvider('anthropic'))->toBe($config)
            ->and($config->scoringModel('claude'))->toBe($config)
            ->and($config->embeddingProvider('voyage'))->toBe($config)
            ->and($config->embeddingModel('voyage-3'))->toBe($config);

        expect(Configuration::resolvedScoringProvider())->toBe('anthropic')
            ->and(Configuration::resolvedScoringModel())->toBe('claude')
            ->and(Configuration::resolvedEmbeddingProvider())->toBe('voyage')
            ->and(Configuration::resolvedEmbeddingModel())->toBe('voyage-3');
    });

    it('is chainable', function (): void {
        new Configuration()
            ->scoringProvider('openai')
            ->scoringModel('gpt-5.4-nano')
            ->embeddingProvider('openai')
            ->embeddingModel('text-embedding-3-small');

        expect(Configuration::resolvedScoringModel())->toBe('gpt-5.4-nano');
    });
});

describe('environment variables', function (): void {
    it('honors env vars when no fluent value is set', function (): void {
        putenv('EVAL_SCORING_PROVIDER=azure');
        putenv('EVAL_SCORING_MODEL=gpt-env');
        putenv('EVAL_EMBEDDING_PROVIDER=cohere');
        putenv('EVAL_EMBEDDING_MODEL=embed-env');

        expect(Configuration::resolvedScoringProvider())->toBe('azure')
            ->and(Configuration::resolvedScoringModel())->toBe('gpt-env')
            ->and(Configuration::resolvedEmbeddingProvider())->toBe('cohere')
            ->and(Configuration::resolvedEmbeddingModel())->toBe('embed-env');
    });

    it('prefers a fluent value over an env var', function (): void {
        putenv('EVAL_SCORING_MODEL=gpt-env');

        new Configuration()->scoringModel('gpt-fluent');

        expect(Configuration::resolvedScoringModel())->toBe('gpt-fluent');
    });
});

describe('flush', function (): void {
    it('clears fluently configured state back to defaults', function (): void {
        new Configuration()->scoringModel('gpt-fluent');

        Configuration::flush();

        expect(Configuration::resolvedScoringModel())->toBe('gpt-5.4-nano');
    });
});
