<?php

declare(strict_types=1);

namespace Pest\Evals;

use Closure;
use Pest\Evals\Contracts\EmbeddingsDriver;
use Pest\Evals\Contracts\JudgeDriver;
use Pest\Evals\Drivers\ClosureEmbeddings;
use Pest\Evals\Drivers\ClosureJudge;
use Pest\Evals\Drivers\LaravelAiClassifier;
use Pest\Evals\Drivers\LaravelAiEmbeddings;
use Pest\Evals\Drivers\LaravelAiJudge;
use Pest\Evals\Events\Scored;

final class Configuration
{
    private static ?JudgeDriver $judge = null;

    private static ?EmbeddingsDriver $embeddings = null;

    /**
     * @var array<int, Closure(Scored): void>
     */
    private static array $afterScoredCallbacks = [];

    public static function resolvedJudge(): JudgeDriver
    {
        return self::$judge ?? new LaravelAiJudge();
    }

    public static function resolvedEmbeddings(): EmbeddingsDriver
    {
        return self::$embeddings ?? new LaravelAiEmbeddings();
    }

    public static function usesStubbedJudge(): bool
    {
        return self::$judge instanceof ClosureJudge;
    }

    public static function usesDefaultEmbeddings(): bool
    {
        return ! self::$embeddings instanceof EmbeddingsDriver;
    }

    public static function flush(): void
    {
        self::$judge = null;
        self::$embeddings = null;
        self::$afterScoredCallbacks = [];
    }

    /** @internal */
    public static function dispatchScored(Scored $event): void
    {
        foreach (self::$afterScoredCallbacks as $callback) {
            $callback($event);
        }
    }

    public function judgeUsing(JudgeDriver|Closure $judge): self
    {
        self::$judge = $judge instanceof Closure ? new ClosureJudge($judge) : $judge;

        return $this;
    }

    public function classify(?string $provider = null, ?string $model = null): self
    {
        return $this->judgeUsing(new LaravelAiClassifier($provider, $model));
    }

    public function embeddingsUsing(EmbeddingsDriver|Closure $embeddings): self
    {
        self::$embeddings = $embeddings instanceof Closure ? new ClosureEmbeddings($embeddings) : $embeddings;

        return $this;
    }

    /**
     * @param  Closure(Scored): void  $callback
     */
    public function afterScored(Closure $callback): self
    {
        self::$afterScoredCallbacks[] = $callback;

        return $this;
    }
}
