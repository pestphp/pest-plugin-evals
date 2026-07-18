<?php

declare(strict_types=1);

use Pest\Evals\Scorers\ToolCallMatch;

it('matches when the expected arguments are a subset of the actual arguments', function (): void {
    $scorer = new ToolCallMatch(tools: ['GetWeather' => ['city' => 'Lisbon']]);

    $result = $scorer->score('', '[{"name": "GetWeather", "arguments": {"city": "Lisbon", "units": "metric"}}]');

    expect($result->score)->toBe(1.0)
        ->and($result->reasoning)->toBe('All expected tool calls matched.');
});

it('matches nested argument subsets', function (): void {
    $scorer = new ToolCallMatch(tools: ['Search' => ['filters' => ['lang' => 'en']]]);

    $result = $scorer->score('', '[{"name": "Search", "arguments": {"filters": {"lang": "en", "safe": true}}}]');

    expect($result->score)->toBe(1.0);
});

it('requires exact arguments in strict mode', function (): void {
    $scorer = new ToolCallMatch(tools: ['GetWeather' => ['city' => 'Lisbon']], strict: true);

    $result = $scorer->score('', '[{"name": "GetWeather", "arguments": {"city": "Lisbon", "units": "metric"}}]');

    expect($result->score)->toBe(0.0)
        ->and($result->reasoning)->toContain('Missing tool calls: GetWeather');
});

it('matches through a closure', function (): void {
    $scorer = new ToolCallMatch(tools: [
        'GetWeather' => fn (array $arguments): bool => $arguments['city'] === 'Lisbon',
    ]);

    $result = $scorer->score('', '[{"name": "GetWeather", "arguments": {"city": "Lisbon"}}]');

    expect($result->score)->toBe(1.0);
});

it('scores partially when some tool calls are missing', function (): void {
    $scorer = new ToolCallMatch(tools: [
        'GetWeather' => [],
        'SendEmail' => [],
    ]);

    $result = $scorer->score('', '[{"name": "GetWeather", "arguments": {}}]');

    expect($result->score)->toBe(0.5)
        ->and($result->reasoning)->toContain('Missing tool calls: SendEmail')
        ->and($result->reasoning)->toContain('Matched: GetWeather');
});

it('scores zero when the output cannot be parsed', function (): void {
    $scorer = new ToolCallMatch(tools: ['GetWeather' => []]);

    $result = $scorer->score('', 'plain text output');

    expect($result->score)->toBe(0.0)
        ->and($result->reasoning)->toBe('Could not parse tool calls from output.');
});
