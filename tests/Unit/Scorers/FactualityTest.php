<?php

declare(strict_types=1);

use Pest\Evals\Configuration;
use Pest\Evals\Eval\Evaluation;
use Pest\Evals\Scorers\Factuality;

beforeEach(fn () => Configuration::flush());

afterEach(fn () => Configuration::flush());

it('requires a reference answer', function (): void {
    new Factuality()->score('input', 'output');
})->throws(InvalidArgumentException::class, 'The [Factuality] scorer requires a reference answer to compare against.');

it('sends the reference answer and scores each category', function (): void {
    $evaluation = null;

    pest()->evals()->judgeUsing(function (Evaluation $given) use (&$evaluation): float {
        $evaluation = $given;

        return 1.0;
    });

    new Factuality()->score('input', 'output', 'expected');

    expect($evaluation->state)->toBe(['input' => 'input', 'output' => 'output', 'reference answer' => 'expected'])
        ->and(array_values($evaluation->levels))->toBe([0.0, 0.6, 0.8, 0.9, 1.0]);
});
