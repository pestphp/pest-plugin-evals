<?php

declare(strict_types=1);

namespace Pest\Evals\Concerns;

trait ParsesToolCalls
{
    /**
     * @return list<array{name: string, arguments: array<string, mixed>}>|null
     */
    private function parseToolCallsFromOutput(string $output): ?array
    {
        /** @var mixed $decoded */
        $decoded = json_decode($output, true);

        return is_array($decoded) ? $this->parseToolCallsFromDecoded($decoded) : null;
    }

    /**
     * @param  array<array-key, mixed>  $decoded
     * @return list<array{name: string, arguments: array<string, mixed>}>|null
     */
    private function parseToolCallsFromDecoded(array $decoded): ?array
    {
        if (isset($decoded[0]) && is_array($decoded[0]) && isset($decoded[0]['name'])) {
            return $this->extractToolCalls($decoded);
        }

        if (isset($decoded['name']) && is_string($decoded['name'])) {
            /** @var array<string, mixed> $arguments */
            $arguments = isset($decoded['arguments']) && is_array($decoded['arguments']) ? $decoded['arguments'] : [];

            return [[
                'name' => $decoded['name'],
                'arguments' => $arguments,
            ]];
        }

        if (isset($decoded['tool_calls']) && is_array($decoded['tool_calls'])) {
            return $this->extractToolCalls($decoded['tool_calls']);
        }

        return null;
    }

    /**
     * @param  array<int|string, mixed>  $items
     * @return list<array{name: string, arguments: array<string, mixed>}>
     */
    private function extractToolCalls(array $items): array
    {
        $result = [];

        foreach ($items as $item) {
            if (! is_array($item)) {
                continue;
            }
            if (! isset($item['name'])) {
                continue;
            }
            if (! is_string($item['name'])) {
                continue;
            }
            /** @var array<string, mixed> $arguments */
            $arguments = isset($item['arguments']) && is_array($item['arguments']) ? $item['arguments'] : [];

            $result[] = [
                'name' => $item['name'],
                'arguments' => $arguments,
            ];
        }

        return $result;
    }

    /**
     * @return list<string>|null
     */
    private function parseToolNamesFromOutput(string $output): ?array
    {
        /** @var mixed $decoded */
        $decoded = json_decode($output, true);

        if (! is_array($decoded)) {
            return null;
        }

        if (isset($decoded[0]) && is_string($decoded[0])) {
            return array_values(array_filter($decoded, is_string(...)));
        }

        $toolCalls = $this->parseToolCallsFromDecoded($decoded);

        if ($toolCalls === null) {
            return null;
        }

        return array_map(fn (array $call): string => $call['name'], $toolCalls);
    }
}
