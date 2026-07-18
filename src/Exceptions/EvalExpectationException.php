<?php

declare(strict_types=1);

namespace Pest\Evals\Exceptions;

use RuntimeException;

/**
 * @internal
 */
final class EvalExpectationException extends RuntimeException
{
    public static function missingPrompt(): self
    {
        return new self('[repeat()] requires [prompt()] to be called first.');
    }

    public static function invalidRepeatCount(int $count): self
    {
        return new self("[repeat()] requires at least 1 sample, [{$count}] given.");
    }

    public static function repeatAlreadyCalled(): self
    {
        return new self('[repeat()] may only be called once per [prompt()].');
    }
}
