<?php

declare(strict_types=1);

use Pest\Evals\Support\JudgeResponse;

it('parses a plain JSON response', function (): void {
    $response = new JudgeResponse('ExampleScorer', '{"score": 0.8, "reasoning": "solid"}');

    expect($response->failed())->toBeFalse()
        ->and($response->score())->toBe(0.8)
        ->and($response->reasoning())->toBe('solid');
});

it('strips markdown code fences', function (): void {
    $response = new JudgeResponse('ExampleScorer', "```json\n{\"score\": 0.5, \"reasoning\": \"ok\"}\n```");

    expect($response->failed())->toBeFalse()
        ->and($response->score())->toBe(0.5);
});

it('strips bare code fences', function (): void {
    $response = new JudgeResponse('ExampleScorer', "```\n{\"score\": 1.0}\n```");

    expect($response->failed())->toBeFalse()
        ->and($response->score())->toBe(1.0);
});

it('clamps scores into the unit range', function (): void {
    expect(new JudgeResponse('ExampleScorer', '{"score": 1.5}')->score())->toBe(1.0)
        ->and(new JudgeResponse('ExampleScorer', '{"score": -0.5}')->score())->toBe(0.0);
});

it('accepts numeric string scores', function (): void {
    expect(new JudgeResponse('ExampleScorer', '{"score": "0.75"}')->score())->toBe(0.75);
});

it('defaults the reasoning when missing', function (): void {
    expect(new JudgeResponse('ExampleScorer', '{"score": 1.0}')->reasoning())
        ->toBe('No reasoning provided.');
});

it('fails on invalid JSON', function (): void {
    $response = new JudgeResponse('ExampleScorer', 'not json');

    expect($response->failed())->toBeTrue()
        ->and($response->score())->toBe(0.0);
});

it('fails when the score is missing or not numeric', function (): void {
    expect(new JudgeResponse('ExampleScorer', '{"reasoning": "no score"}')->failed())->toBeTrue()
        ->and(new JudgeResponse('ExampleScorer', '{"score": "high"}')->failed())->toBeTrue();
});

it('exposes extra string fields with a default', function (): void {
    $response = new JudgeResponse('ExampleScorer', '{"score": 1.0, "category": "equal"}');

    expect($response->string('category', 'unknown'))->toBe('equal')
        ->and($response->string('missing', 'unknown'))->toBe('unknown');
});

it('builds a failure result that includes the raw response', function (): void {
    $result = new JudgeResponse('ExampleScorer', 'garbage output')->result('safety');

    expect($result->score)->toBe(0.0)
        ->and($result->reasoning)->toBe('Failed to parse safety response: garbage output')
        ->and($result->scorer)->toBe('ExampleScorer');
});

it('builds a successful result', function (): void {
    $result = new JudgeResponse('ExampleScorer', '{"score": 0.9, "reasoning": "great"}')->result();

    expect($result->score)->toBe(0.9)
        ->and($result->reasoning)->toBe('great')
        ->and($result->scorer)->toBe('ExampleScorer');
});
