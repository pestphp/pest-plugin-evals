<?php

declare(strict_types=1);

use Pest\Evals\Configuration;
use Pest\Evals\Scorers\LlmJudge;

beforeEach(fn () => Configuration::flush());

afterEach(fn () => Configuration::flush());

it('sends the input, output, and criteria to the judge', function (): void {
    $prompts = [];

    pest()->evals()->judgeUsing(function (string $instructions, string $prompt) use (&$prompts): string {
        $prompts[] = $prompt;

        return '{"score": 0.8, "reasoning": "solid"}';
    });

    $result = new LlmJudge(criteria: 'Mentions Paris.')->score('capital of France?', 'Paris');

    expect($result->score)->toBe(0.8)
        ->and($result->reasoning)->toBe('solid')
        ->and($prompts[0])->toContain('capital of France?')
        ->and($prompts[0])->toContain('Paris')
        ->and($prompts[0])->toContain('Mentions Paris.')
        ->and($prompts[0])->not->toContain('Expected Output');
});

it('includes the reference answer when an expected output is given', function (): void {
    $prompts = [];

    pest()->evals()->judgeUsing(function (string $instructions, string $prompt) use (&$prompts): string {
        $prompts[] = $prompt;

        return '{"score": 1.0, "reasoning": "ok"}';
    });

    new LlmJudge(criteria: 'Matches the reference.')->score('question', 'output', 'the reference answer');

    expect($prompts[0])->toContain('Expected Output')
        ->and($prompts[0])->toContain('the reference answer');
});
