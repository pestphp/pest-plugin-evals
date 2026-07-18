<?php

declare(strict_types=1);

use Pest\Evals\Support\ToolCallParser;

describe('fromOutput', function (): void {
    it('parses a list of tool call objects', function (): void {
        $calls = ToolCallParser::fromOutput('[{"name": "Search", "arguments": {"q": "pest"}}, {"name": "Log"}]');

        expect($calls)->toBe([
            ['name' => 'Search', 'arguments' => ['q' => 'pest']],
            ['name' => 'Log', 'arguments' => []],
        ]);
    });

    it('parses a single tool call object', function (): void {
        expect(ToolCallParser::fromOutput('{"name": "Search", "arguments": {"q": "pest"}}'))
            ->toBe([['name' => 'Search', 'arguments' => ['q' => 'pest']]]);
    });

    it('parses a tool_calls wrapper', function (): void {
        expect(ToolCallParser::fromOutput('{"tool_calls": [{"name": "Search"}]}'))
            ->toBe([['name' => 'Search', 'arguments' => []]]);
    });

    it('skips malformed entries', function (): void {
        expect(ToolCallParser::fromOutput('[{"name": "Search"}, {"nope": true}, "text", {"name": 42}]'))
            ->toBe([['name' => 'Search', 'arguments' => []]]);
    });

    it('returns null for invalid JSON', function (): void {
        expect(ToolCallParser::fromOutput('plain text'))->toBeNull();
    });

    it('returns null for unrecognized shapes', function (): void {
        expect(ToolCallParser::fromOutput('{"foo": "bar"}'))->toBeNull()
            ->and(ToolCallParser::fromOutput('"just a string"'))->toBeNull();
    });
});

describe('namesFromOutput', function (): void {
    it('parses a list of tool names', function (): void {
        expect(ToolCallParser::namesFromOutput('["Search", "Summarize"]'))
            ->toBe(['Search', 'Summarize']);
    });

    it('parses a list of tool call objects', function (): void {
        expect(ToolCallParser::namesFromOutput('[{"name": "Search"}, {"name": "Summarize"}]'))
            ->toBe(['Search', 'Summarize']);
    });

    it('parses mixed lists of names and objects', function (): void {
        expect(ToolCallParser::namesFromOutput('["Search", {"name": "Summarize"}]'))
            ->toBe(['Search', 'Summarize']);
    });

    it('parses a tool_calls wrapper', function (): void {
        expect(ToolCallParser::namesFromOutput('{"tool_calls": [{"name": "Search"}]}'))
            ->toBe(['Search']);
    });

    it('parses a single tool call object', function (): void {
        expect(ToolCallParser::namesFromOutput('{"name": "Search"}'))->toBe(['Search']);
    });

    it('returns null when a list holds no recognizable entries', function (): void {
        expect(ToolCallParser::namesFromOutput('[1, 2, 3]'))->toBeNull()
            ->and(ToolCallParser::namesFromOutput('[]'))->toBeNull();
    });

    it('returns null for invalid JSON', function (): void {
        expect(ToolCallParser::namesFromOutput('plain text'))->toBeNull();
    });
});
