<?php

declare(strict_types=1);

namespace Pest\Evals\Support;

/**
 * @internal
 */
final class ToolCallParser
{
    /**
     * @return array<int, array{name: string, arguments: array<string, mixed>}>|null
     */
    public static function fromOutput(string $output): ?array
    {
        /** @var mixed $decoded */
        $decoded = json_decode($output, true);

        return is_array($decoded) ? self::fromDecoded($decoded) : null;
    }

    /**
     * @return array<int, string>|null
     */
    public static function namesFromOutput(string $output): ?array
    {
        /** @var mixed $decoded */
        $decoded = json_decode($output, true);

        if (! is_array($decoded)) {
            return null;
        }

        if (array_is_list($decoded) && $decoded !== []) {
            $names = [];

            foreach ($decoded as $item) {
                if (is_string($item)) {
                    $names[] = $item;
                } elseif (is_array($item) && isset($item['name']) && is_string($item['name'])) {
                    $names[] = $item['name'];
                }
            }

            return $names === [] ? null : $names;
        }

        $toolCalls = self::fromDecoded($decoded);

        if ($toolCalls === null) {
            return null;
        }

        return array_map(fn (array $call): string => $call['name'], $toolCalls);
    }

    /**
     * @param  array<array-key, mixed>  $decoded
     * @return array<int, array{name: string, arguments: array<string, mixed>}>|null
     */
    private static function fromDecoded(array $decoded): ?array
    {
        if (isset($decoded[0]) && is_array($decoded[0]) && isset($decoded[0]['name'])) {
            return self::extract($decoded);
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
            return self::extract($decoded['tool_calls']);
        }

        return null;
    }

    /**
     * @param  array<int|string, mixed>  $items
     * @return array<int, array{name: string, arguments: array<string, mixed>}>
     */
    private static function extract(array $items): array
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
}
