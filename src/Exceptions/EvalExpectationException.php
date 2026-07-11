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

    public static function promptNotCalled(): self
    {
        return new self('[prompt()] must be called before [repeat()].');
    }
}
