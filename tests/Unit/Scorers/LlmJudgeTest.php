<?php

declare(strict_types=1);

use Pest\Evals\Configuration;
use Pest\Evals\Eval\Evaluation;
use Pest\Evals\Eval\Verdict;
use Pest\Evals\Scorers\LlmJudge;

beforeEach(fn () => Configuration::flush());

afterEach(fn () => Configuration::flush());

it('sends the input, output, and criteria to the judge', function (): void {
    $evaluations = [];

    pest()->evals()->judgeUsing(function (Evaluation $evaluation) use (&$evaluations): Verdict {
        $evaluations[] = $evaluation;

        return new Verdict(0.8, 'solid', 'Mostly meets the criteria with minor gaps');
    });

    $result = new LlmJudge(criteria: 'Mentions Paris.')->score('capital of France?', 'Paris');

    expect($result->score)->toBe(0.8)
        ->and($result->reasoning)->toBe('[Mostly meets the criteria with minor gaps] solid')
        ->and($evaluations[0]->state)->toBe(['input' => 'capital of France?', 'output' => 'Paris'])
        ->and($evaluations[0]->question)->toContain('Mentions Paris.');
});

it('includes the reference answer when an expected output is given', function (): void {
    $evaluations = [];

    pest()->evals()->judgeUsing(function (Evaluation $evaluation) use (&$evaluations): float {
        $evaluations[] = $evaluation;

        return 1.0;
    });

    new LlmJudge(criteria: 'Matches the reference.')->score('question', 'output', 'the reference answer');

    expect($evaluations[0]->state['expected'])->toBe('the reference answer');
});

it('clamps custom judge scores into the unit range', function (float $score, float $expected): void {
    pest()->evals()->judgeUsing(fn (Evaluation $evaluation): float => $score);

    expect(new LlmJudge('anything')->score('q', 'a')->score)->toBe($expected);
})->with([[1.5, 1.0], [-0.5, 0.0]]);
