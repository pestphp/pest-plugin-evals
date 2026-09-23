<?php

declare(strict_types=1);

use Pest\Evals\Drivers\LaravelAiClassifier;
use Pest\Evals\Drivers\LaravelAiEmbeddings;
use Pest\Evals\Drivers\LaravelAiJudge;

afterEach(function (): void {
    putenv('PEST_EVALS_LARAVEL_SCORING_PROVIDER');
    putenv('PEST_EVALS_LARAVEL_SCORING_MODEL');
    putenv('PEST_EVALS_LARAVEL_EMBEDDING_PROVIDER');
    putenv('PEST_EVALS_LARAVEL_EMBEDDING_MODEL');
    putenv('PEST_EVALS_LARAVEL_CLASSIFICATION_PROVIDER');
    putenv('PEST_EVALS_LARAVEL_CLASSIFICATION_MODEL');
});

describe('LaravelAiJudge', function (): void {
    it('uses the hardcoded defaults when nothing is provided', function (): void {
        $judge = new LaravelAiJudge();

        expect($judge->provider)->toBe('openai')
            ->and($judge->model)->toBe('gpt-6-luna');
    });

    it('accepts an explicit provider and model', function (): void {
        $judge = new LaravelAiJudge(provider: 'anthropic', model: 'claude');

        expect($judge->provider)->toBe('anthropic')
            ->and($judge->model)->toBe('claude');
    });

    it('falls back to environment variables', function (): void {
        putenv('PEST_EVALS_LARAVEL_SCORING_PROVIDER=azure');
        putenv('PEST_EVALS_LARAVEL_SCORING_MODEL=gpt-env');

        $judge = new LaravelAiJudge();

        expect($judge->provider)->toBe('azure')
            ->and($judge->model)->toBe('gpt-env');
    });

    it('prefers an explicit value over an env var', function (): void {
        putenv('PEST_EVALS_LARAVEL_SCORING_MODEL=gpt-env');

        expect(new LaravelAiJudge(model: 'gpt-explicit')->model)->toBe('gpt-explicit');
    });
});

describe('LaravelAiEmbeddings', function (): void {
    it('uses the hardcoded defaults when nothing is provided', function (): void {
        $embeddings = new LaravelAiEmbeddings();

        expect($embeddings->provider)->toBe('openai')
            ->and($embeddings->model)->toBe('text-embedding-3-small');
    });

    it('accepts an explicit provider and model', function (): void {
        $embeddings = new LaravelAiEmbeddings(provider: 'voyage', model: 'voyage-3');

        expect($embeddings->provider)->toBe('voyage')
            ->and($embeddings->model)->toBe('voyage-3');
    });

    it('falls back to environment variables', function (): void {
        putenv('PEST_EVALS_LARAVEL_EMBEDDING_PROVIDER=cohere');
        putenv('PEST_EVALS_LARAVEL_EMBEDDING_MODEL=embed-env');

        $embeddings = new LaravelAiEmbeddings();

        expect($embeddings->provider)->toBe('cohere')
            ->and($embeddings->model)->toBe('embed-env');
    });
});

describe('LaravelAiClassifier', function (): void {
    it('defers to the laravel ai defaults when nothing is provided', function (): void {
        $classifier = new LaravelAiClassifier();

        expect($classifier->provider)->toBeNull()
            ->and($classifier->model)->toBeNull();
    });

    it('accepts an explicit provider and model', function (): void {
        $classifier = new LaravelAiClassifier(provider: 'typesafe', model: 'jev-latest');

        expect($classifier->provider)->toBe('typesafe')
            ->and($classifier->model)->toBe('jev-latest');
    });

    it('falls back to environment variables', function (): void {
        putenv('PEST_EVALS_LARAVEL_CLASSIFICATION_PROVIDER=typesafe');
        putenv('PEST_EVALS_LARAVEL_CLASSIFICATION_MODEL=jev-env');

        $classifier = new LaravelAiClassifier();

        expect($classifier->provider)->toBe('typesafe')
            ->and($classifier->model)->toBe('jev-env');
    });

    it('prefers an explicit value over an env var', function (): void {
        putenv('PEST_EVALS_LARAVEL_CLASSIFICATION_MODEL=jev-env');

        expect(new LaravelAiClassifier(model: 'jev-explicit')->model)->toBe('jev-explicit');
    });
});
