<?php

declare(strict_types=1);

namespace Pest\Evals\Drivers;

use Laravel\Ai\Embeddings;
use Pest\Evals\Contracts\EmbeddingsDriver;
use RuntimeException;

/**
 * @internal
 */
final class LaravelAiEmbeddings implements EmbeddingsDriver
{
    public readonly string $provider;

    public readonly string $model;

    public function __construct(?string $provider = null, ?string $model = null)
    {
        $this->provider = $provider ?? (getenv('PEST_EVALS_LARAVEL_EMBEDDING_PROVIDER') ?: 'openai');
        $this->model = $model ?? (getenv('PEST_EVALS_LARAVEL_EMBEDDING_MODEL') ?: 'text-embedding-3-small');
    }

    public function embed(array $inputs): array
    {
        if (! class_exists(Embeddings::class)) {
            throw new RuntimeException(
                'The default embeddings driver requires the [laravel/ai] package. '
                .'Install it with [composer require laravel/ai], or configure a '
                .'custom driver via pest()->evals()->embeddingsUsing(...).'
            );
        }

        $embeddings = Embeddings::for($inputs)->generate(
            $this->provider,
            $this->model,
        )->embeddings;

        return array_map(array_values(...), $embeddings);
    }
}
