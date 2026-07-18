<?php

declare(strict_types=1);

use Pest\Evals\Scorers\AgentTrajectory;

it('scores a full strict-order match', function (): void {
    $scorer = new AgentTrajectory(sequence: ['Search', 'Summarize']);

    $result = $scorer->score('', '["Search", "Summarize"]');

    expect($result->score)->toBe(1.0)
        ->and($result->reasoning)->toBe('Tool sequence matches: Search -> Summarize');
});

it('ignores interleaved calls in strict order', function (): void {
    $scorer = new AgentTrajectory(sequence: ['Search', 'Summarize']);

    $result = $scorer->score('', '["Search", "Log", "Summarize"]');

    expect($result->score)->toBe(1.0);
});

it('scores partially when the order is broken', function (): void {
    $scorer = new AgentTrajectory(sequence: ['Search', 'Summarize']);

    $result = $scorer->score('', '["Summarize", "Search"]');

    expect($result->score)->toBe(0.5)
        ->and($result->reasoning)->toContain('Matched 1/2');
});

it('ignores order when strictOrder is disabled', function (): void {
    $scorer = new AgentTrajectory(sequence: ['Search', 'Summarize'], strictOrder: false);

    $result = $scorer->score('', '["Summarize", "Search"]');

    expect($result->score)->toBe(1.0)
        ->and($result->reasoning)->toBe('All expected tools were called.');
});

it('reports missing tools in subset mode', function (): void {
    $scorer = new AgentTrajectory(sequence: ['Search', 'SendEmail'], strictOrder: false);

    $result = $scorer->score('', '["Search"]');

    expect($result->score)->toBe(0.5)
        ->and($result->reasoning)->toBe('Missing tools: SendEmail.');
});

it('parses tool names from object payloads', function (): void {
    $scorer = new AgentTrajectory(sequence: ['Search']);

    $result = $scorer->score('', '[{"name": "Search", "arguments": {}}]');

    expect($result->score)->toBe(1.0);
});

it('scores zero when the output cannot be parsed', function (): void {
    $scorer = new AgentTrajectory(sequence: ['Search']);

    $result = $scorer->score('', 'plain text output');

    expect($result->score)->toBe(0.0)
        ->and($result->reasoning)->toBe('Could not parse tool calls from output.');
});
