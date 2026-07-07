<?php

declare(strict_types=1);

namespace Pest\Evals\Concerns;

use Pest\Evals\Scorers\ScorerResult;

use function Laravel\Ai\agent;

trait JudgesWithLlm
{
    private function judge(string $prompt, string $instructions): string
    {
        /** @var string $provider */
        $provider = $this->provider ?? config('eval.ai.scoring.provider', 'openai');
        /** @var string $model */
        $model = $this->model ?? config('eval.ai.scoring.model', 'gpt-4.1-mini');

        $response = agent(
            instructions: $instructions,
        )->prompt($prompt, provider: $provider, model: $model);

        return (string) $response;
    }

    private function parseJudgeResponse(string $response, string $context = 'judge'): ScorerResult
    {
        $result = $this->decodeJudgeResponse($response);

        if ($result === null) {
            return new ScorerResult(
                score: 0.0,
                reasoning: "Failed to parse {$context} response: {$response}",
                scorer: static::class,
            );
        }

        return new ScorerResult(
            score: $result['score'],
            reasoning: $result['reasoning'],
            scorer: static::class,
        );
    }

    /**
     * @return array{score: float, reasoning: string, raw: array<string, mixed>}|null
     */
    private function decodeJudgeResponse(string $response): ?array
    {
        $cleaned = trim($response);
        $cleaned = (string) preg_replace('/^```(?:json)?\s*/m', '', $cleaned);
        $cleaned = (string) preg_replace('/\s*```$/m', '', $cleaned);
        $cleaned = trim($cleaned);

        /** @var array<string, mixed>|null $decoded */
        $decoded = json_decode($cleaned, true);

        if (! is_array($decoded) || ! isset($decoded['score']) || ! is_numeric($decoded['score'])) {
            return null;
        }

        $reasoning = isset($decoded['reasoning']) && is_string($decoded['reasoning'])
            ? $decoded['reasoning']
            : 'No reasoning provided.';

        return [
            'score' => max(0.0, min(1.0, (float) $decoded['score'])),
            'reasoning' => $reasoning,
            'raw' => $decoded,
        ];
    }
}
