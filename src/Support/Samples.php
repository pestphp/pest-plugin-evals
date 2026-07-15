<?php

declare(strict_types=1);

namespace Pest\Evals\Support;

/**
 * @internal
 */
final class Samples
{
    /**
     * @param  array<int, string>  $outputs
     */
    public function __construct(
        public readonly array $outputs,
    ) {}
}
