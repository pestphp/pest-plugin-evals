<?php

declare(strict_types=1);

use Pest\Evals\Configuration;
use Pest\Evals\Scorers\Factuality;

beforeEach(fn () => Configuration::flush());

afterEach(fn () => Configuration::flush());

it('requires a reference answer', function (): void {
    new Factuality()->score('input', 'output');
})->throws(InvalidArgumentException::class, 'The [Factuality] scorer requires a reference answer to compare against.');

it('derives the score from the category, not the judge score', function (): void {
    pest()->evals()->judgeUsing(
        fn (string $instructions, string $prompt): string => '{"score": 0.95, "category": "subset", "reasoning": "partial"}',
    );

    $result = new Factuality()->score('input', 'output', 'expected');

    expect($result->score)->toBe(0.6)
        ->and($result->reasoning)->toBe('[subset] partial');
});

it('maps every category deterministically', function (string $category, float $score): void {
    pest()->evals()->judgeUsing(
        fn (string $instructions, string $prompt): string => json_encode([
            'score' => 0.5,
            'category' => $category,
            'reasoning' => 'because',
        ], JSON_THROW_ON_ERROR),
    );

    expect(new Factuality()->score('input', 'output', 'expected')->score)->toBe($score);
})->with([
    ['equal', 1.0],
    ['approximately_equal', 0.9],
    ['superset', 0.8],
    ['subset', 0.6],
    ['disagreement', 0.0],
]);

it('falls back to the judge score for unknown categories', function (): void {
    pest()->evals()->judgeUsing(
        fn (string $instructions, string $prompt): string => '{"score": 0.42, "category": "sideways", "reasoning": "odd"}',
    );

    expect(new Factuality()->score('input', 'output', 'expected')->score)->toBe(0.42);
});

it('reports a failed result when the judge response cannot be parsed', function (): void {
    pest()->evals()->judgeUsing(
        fn (string $instructions, string $prompt): string => 'not json at all',
    );

    $result = new Factuality()->score('input', 'output', 'expected');

    expect($result->score)->toBe(0.0)
        ->and($result->reasoning)->toContain('Failed to parse factuality response');
});
