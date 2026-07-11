<?php

declare(strict_types=1);

namespace Pest\Evals\Support;

use Pest\Evals\Eval\EvalExpectationContext;

/**
 * @internal
 */
final class Samples
{
    public static function has(mixed $value): bool
    {
        $samples = EvalExpectationContext::$current?->getSampleOutputs();

        return is_string($value)
            && $samples !== null
            && in_array($value, $samples, true);
    }

    /**
     * @return array<int, string>
     */
    public static function all(): array
    {
        return EvalExpectationContext::$current?->getSampleOutputs() ?? [];
    }
}
