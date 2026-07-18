<?php

declare(strict_types=1);

use Pest\Evals\Scorers\ScorerResult;

it('passes when the score meets the threshold', function (): void {
    $result = new ScorerResult(score: 0.7, reasoning: 'ok', scorer: 'Example');

    expect($result->passed())->toBeTrue()
        ->and($result->passed(0.7))->toBeTrue()
        ->and($result->passed(0.71))->toBeFalse();
});

it('uses the default threshold', function (): void {
    expect(new ScorerResult(score: 0.69, reasoning: 'ok', scorer: 'Example')->passed())->toBeFalse();
});
