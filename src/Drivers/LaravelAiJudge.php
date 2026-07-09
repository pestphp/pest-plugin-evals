<?php

declare(strict_types=1);

namespace Pest\Evals\Drivers;

use Pest\Evals\Contracts\JudgeDriver;
use RuntimeException;

use function Laravel\Ai\agent;

final class LaravelAiJudge implements JudgeDriver
{
    public readonly string $provider;

    public readonly string $model;

    public function __construct(?string $provider = null, ?string $model = null)
    {
        $this->provider = $provider ?? (getenv('EVAL_SCORING_PROVIDER') ?: 'openai');
        $this->model = $model ?? (getenv('EVAL_SCORING_MODEL') ?: 'gpt-5.4-nano');
    }

    public function generate(string $instructions, string $prompt): string
    {
        if (! function_exists('Laravel\\Ai\\agent')) {
            throw new RuntimeException(
                'The default judge driver requires the [laravel/ai] package. '
                .'Install it with [composer require laravel/ai], or configure a '
                .'custom driver via evals()->judgeUsing(...).'
            );
        }

        return (string) agent(instructions: $instructions)->prompt(
            $prompt,
            provider: $this->provider,
            model: $this->model,
        );
    }
}
