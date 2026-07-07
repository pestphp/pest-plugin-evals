<?php

declare(strict_types=1);

namespace Pest\Evals\Scorers;

use Closure;
use Pest\Evals\Concerns\ParsesToolCalls;

final class ToolCallMatch implements Scorer
{
    use ParsesToolCalls;

    /**
     * @param  array<string, array<string, mixed>|Closure>  $tools  Expected tool calls: ['ToolName' => ['arg' => 'value'] | callable]
     */
    public function __construct(
        private array $tools = [],
        private bool $strict = false,
    ) {}

    public function score(string $input, string $output, ?string $expected = null): ScorerResult
    {
        if ($this->tools === []) {
            return new ScorerResult(
                score: 0.0,
                reasoning: 'No expected tool calls provided.',
                scorer: self::class,
            );
        }

        $toolCalls = $this->parseToolCallsFromOutput($output);

        if ($toolCalls === null) {
            return new ScorerResult(
                score: 0.0,
                reasoning: 'Could not parse tool calls from output.',
                scorer: self::class,
            );
        }

        $matched = [];
        $missing = [];

        foreach ($this->tools as $toolName => $expectedArgs) {
            if ($this->findMatchingCall($toolCalls, $toolName, $expectedArgs)) {
                $matched[] = $toolName;
            } else {
                $missing[] = $toolName;
            }
        }

        $score = count($matched) / count($this->tools);

        return new ScorerResult(
            score: $score,
            reasoning: $missing === []
                ? 'All expected tool calls matched.'
                : 'Missing tool calls: '.implode(', ', $missing).'. Matched: '.implode(', ', $matched).'.',
            scorer: self::class,
        );
    }

    /**
     * @param  array<int, array{name: string, arguments: array<string, mixed>}>  $toolCalls
     * @param  array<string, mixed>|Closure  $expectedArgs
     */
    private function findMatchingCall(array $toolCalls, string $toolName, array|Closure $expectedArgs): bool
    {
        foreach ($toolCalls as $call) {
            if ($call['name'] !== $toolName) {
                continue;
            }

            if ($expectedArgs instanceof Closure) {
                if ($expectedArgs($call['arguments'])) {
                    return true;
                }
            } elseif ($this->strict) {
                if ($call['arguments'] === $expectedArgs) {
                    return true;
                }
            } elseif ($this->containsSubset($call['arguments'], $expectedArgs)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array<string, mixed>  $haystack
     * @param  array<string, mixed>  $needle
     */
    private function containsSubset(array $haystack, array $needle): bool
    {
        foreach ($needle as $key => $value) {
            if (! array_key_exists($key, $haystack)) {
                return false;
            }

            if (is_array($value) && is_array($haystack[$key])) {
                /** @var array<string, mixed> $haystackChild */
                $haystackChild = $haystack[$key];
                /** @var array<string, mixed> $needleChild */
                $needleChild = $value;

                if (! $this->containsSubset($haystackChild, $needleChild)) {
                    return false;
                }
            } elseif ($haystack[$key] !== $value) {
                return false;
            }
        }

        return true;
    }
}
