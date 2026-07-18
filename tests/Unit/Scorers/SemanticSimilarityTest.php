<?php

declare(strict_types=1);

use Pest\Evals\Configuration;
use Pest\Evals\Scorers\SemanticSimilarity;

beforeEach(fn () => Configuration::flush());

afterEach(fn () => Configuration::flush());

it('requires an expected output', function (): void {
    new SemanticSimilarity()->score('input', 'output');
})->throws(InvalidArgumentException::class, 'The [SemanticSimilarity] scorer requires an expected output to compare against.');

it('scores identical vectors as one', function (): void {
    pest()->evals()->embeddingsUsing(fn (array $inputs): array => [[3.0, 4.0], [3.0, 4.0]]);

    $result = new SemanticSimilarity()->score('input', 'output', 'expected');

    expect($result->score)->toBe(1.0);
});

it('scores orthogonal vectors as zero', function (): void {
    pest()->evals()->embeddingsUsing(fn (array $inputs): array => [[1.0, 0.0], [0.0, 1.0]]);

    $result = new SemanticSimilarity()->score('input', 'output', 'expected');

    expect($result->score)->toBe(0.0);
});

it('clamps negative similarity to zero', function (): void {
    pest()->evals()->embeddingsUsing(fn (array $inputs): array => [[1.0], [-1.0]]);

    $result = new SemanticSimilarity()->score('input', 'output', 'expected');

    expect($result->score)->toBe(0.0);
});

it('scores zero vectors as zero instead of dividing by zero', function (): void {
    pest()->evals()->embeddingsUsing(fn (array $inputs): array => [[0.0, 0.0], [0.0, 0.0]]);

    $result = new SemanticSimilarity()->score('input', 'output', 'expected');

    expect($result->score)->toBe(0.0);
});

it('rejects a driver that returns the wrong number of vectors', function (): void {
    pest()->evals()->embeddingsUsing(fn (array $inputs): array => [[1.0]]);

    new SemanticSimilarity()->score('input', 'output', 'expected');
})->throws(RuntimeException::class, 'The embeddings driver returned [1] vectors for [2] inputs.');

it('rejects vectors of mismatched dimensions', function (): void {
    pest()->evals()->embeddingsUsing(fn (array $inputs): array => [[1.0, 1.0, 1.0], [1.0]]);

    new SemanticSimilarity()->score('input', 'output', 'expected');
})->throws(RuntimeException::class, 'The embeddings driver returned vectors of different dimensions ([3] and [1]).');

it('only embeds the expected output once across samples', function (): void {
    $batches = [];

    pest()->evals()->embeddingsUsing(function (array $inputs) use (&$batches): array {
        $batches[] = $inputs;

        return array_fill(0, count($inputs), [1.0, 0.0]);
    });

    $scorer = new SemanticSimilarity();
    $scorer->score('input', 'first output', 'expected');
    $scorer->score('input', 'second output', 'expected');

    expect($batches)->toBe([
        ['first output', 'expected'],
        ['second output'],
    ]);
});

it('re-embeds when the expected output changes', function (): void {
    $batches = [];

    pest()->evals()->embeddingsUsing(function (array $inputs) use (&$batches): array {
        $batches[] = $inputs;

        return array_fill(0, count($inputs), [1.0, 0.0]);
    });

    $scorer = new SemanticSimilarity();
    $scorer->score('input', 'output', 'expected');
    $scorer->score('input', 'output', 'other expected');

    expect($batches)->toBe([
        ['output', 'expected'],
        ['output', 'other expected'],
    ]);
});
