<?php

declare(strict_types=1);

namespace Pest\Evals;

/**
 * @internal
 */
final class Configuration
{
    private static ?string $scoringProvider = null;

    private static ?string $scoringModel = null;

    private static ?string $embeddingProvider = null;

    private static ?string $embeddingModel = null;

    public static function resolvedScoringProvider(): string
    {
        return self::$scoringProvider ?? (getenv('EVAL_SCORING_PROVIDER') ?: 'openai');
    }

    public static function resolvedScoringModel(): string
    {
        return self::$scoringModel ?? (getenv('EVAL_SCORING_MODEL') ?: 'gpt-5.4-nano');
    }

    public static function resolvedEmbeddingProvider(): string
    {
        return self::$embeddingProvider ?? (getenv('EVAL_EMBEDDING_PROVIDER') ?: 'openai');
    }

    public static function resolvedEmbeddingModel(): string
    {
        return self::$embeddingModel ?? (getenv('EVAL_EMBEDDING_MODEL') ?: 'text-embedding-3-small');
    }

    public static function flush(): void
    {
        self::$scoringProvider = null;
        self::$scoringModel = null;
        self::$embeddingProvider = null;
        self::$embeddingModel = null;
    }

    public function scoringProvider(string $provider): self
    {
        self::$scoringProvider = $provider;

        return $this;
    }

    public function scoringModel(string $model): self
    {
        self::$scoringModel = $model;

        return $this;
    }

    public function embeddingProvider(string $provider): self
    {
        self::$embeddingProvider = $provider;

        return $this;
    }

    public function embeddingModel(string $model): self
    {
        self::$embeddingModel = $model;

        return $this;
    }
}
