<?php

declare(strict_types=1);

namespace Pest\Evals\Drivers;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Responses\StructuredAgentResponse;
use Pest\Evals\Contracts\JudgeDriver;
use Pest\Evals\Eval\Evaluation;
use Pest\Evals\Eval\Verdict;
use RuntimeException;

use function Laravel\Ai\agent;

final class LaravelAiJudge implements JudgeDriver
{
    public readonly string $provider;

    public readonly string $model;

    public function __construct(?string $provider = null, ?string $model = null)
    {
        $this->provider = $provider ?? (getenv('PEST_EVALS_LARAVEL_SCORING_PROVIDER') ?: 'openai');
        $this->model = $model ?? (getenv('PEST_EVALS_LARAVEL_SCORING_MODEL') ?: 'gpt-6-luna');
    }

    public function judge(Evaluation $evaluation): Verdict
    {
        if (! function_exists('Laravel\\Ai\\agent')) {
            throw new RuntimeException(
                'The default judge driver requires the [laravel/ai] package. '
                .'Install it with [composer require laravel/ai], or configure a '
                .'custom driver via pest()->evals()->judgeUsing(...).'
            );
        }

        $labels = $evaluation->labels();
        $levels = implode("\n", array_map(fn (int $index, string $label): string => "{$index}: {$label}", array_keys($labels), $labels));

        $response = agent(
            instructions: "You are an expert evaluator of AI agent responses. {$evaluation->question}\n\nChoose the number of the level, ordered from worst to best, that best describes the output:\n{$levels}",
            schema: fn (JsonSchema $schema): array => [
                'reasoning' => $schema->string()->required(),
                'level' => $schema->integer()->min(0)->max(count($labels) - 1)->required(),
            ],
        )->prompt(
            implode("\n\n", array_map(fn (string $key, string $value): string => '## '.ucfirst($key)."\n{$value}", array_keys($evaluation->state), $evaluation->state)),
            provider: $this->provider,
            model: $this->model,
        );

        $verdict = $response instanceof StructuredAgentResponse ? $response->toArray() : [];
        $level = is_numeric($verdict['level'] ?? null) ? ($labels[(int) $verdict['level']] ?? null) : null;
        $reasoning = is_string($verdict['reasoning'] ?? null) ? $verdict['reasoning'] : 'No reasoning provided.';

        if ($level === null) {
            throw new RuntimeException('The judge returned no valid level: '.json_encode($verdict));
        }

        return new Verdict($evaluation->levels[$level], $reasoning, $level);
    }
}
