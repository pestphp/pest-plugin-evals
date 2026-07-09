<?php

declare(strict_types=1);

namespace Pest\Evals\Drivers;

use Closure;
use Pest\Evals\Contracts\EmbeddingsDriver;

/**
 * @internal
 */
final readonly class ClosureEmbeddings implements EmbeddingsDriver
{
    /**
     * @param  Closure(array<int, string>): array<int, array<int, float>>  $callback
     */
    public function __construct(
        private Closure $callback,
    ) {}

    public function embed(array $inputs): array
    {
        return ($this->callback)($inputs);
    }
}
