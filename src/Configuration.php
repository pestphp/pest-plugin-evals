<?php

declare(strict_types=1);

namespace Pest\Evals;

use Closure;
use Pest\Evals\Contracts\EmbeddingsDriver;
use Pest\Evals\Contracts\JudgeDriver;
use Pest\Evals\Drivers\ClosureEmbeddings;
use Pest\Evals\Drivers\ClosureJudge;
use Pest\Evals\Drivers\LaravelAiEmbeddings;
use Pest\Evals\Drivers\LaravelAiJudge;

final class Configuration
{
    private static ?JudgeDriver $judge = null;

    private static ?EmbeddingsDriver $embeddings = null;

    public static function resolvedJudge(): JudgeDriver
    {
        return self::$judge ?? new LaravelAiJudge();
    }

    public static function resolvedEmbeddings(): EmbeddingsDriver
    {
        return self::$embeddings ?? new LaravelAiEmbeddings();
    }

    public static function usesDefaultDrivers(): bool
    {
        return ! self::$judge instanceof JudgeDriver && ! self::$embeddings instanceof EmbeddingsDriver;
    }

    public static function flush(): void
    {
        self::$judge = null;
        self::$embeddings = null;
    }

    public function judgeUsing(JudgeDriver|Closure $judge): self
    {
        self::$judge = $judge instanceof Closure ? new ClosureJudge($judge) : $judge;

        return $this;
    }

    public function embeddingsUsing(EmbeddingsDriver|Closure $embeddings): self
    {
        self::$embeddings = $embeddings instanceof Closure ? new ClosureEmbeddings($embeddings) : $embeddings;

        return $this;
    }
}
