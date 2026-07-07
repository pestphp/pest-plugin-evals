<?php

declare(strict_types=1);

namespace Pest\Evals\Exceptions;

use RuntimeException;

final class EvalExpectationException extends RuntimeException
{
    public static function missingPrompt(): self
    {
        return new self('[repeat()] requires [prompt()] to be called first.');
    }

    public static function outputsNotResolved(): self
    {
        return new self('[resolveOutputs()] must be called before [resolveAdditionalOutputs()].');
    }
}
