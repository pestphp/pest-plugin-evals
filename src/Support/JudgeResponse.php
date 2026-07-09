<?php

declare(strict_types=1);

namespace Pest\Evals\Support;

use Pest\Evals\Scorers\ScorerResult;

/**
 * @internal
 */
final readonly class JudgeResponse
{
    private float $score;

    private string $reasoning;

    /**
     * @var array<string, mixed>
     */
    private array $decoded;

    private bool $failed;

    public function __construct(
        private string $scorer,
        private string $raw,
    ) {
        $decoded = $this->decode($raw);

        $this->failed = $decoded === null;
        $this->decoded = $decoded ?? [];
        $this->score = $decoded['score'] ?? 0.0;
        $this->reasoning = $decoded['reasoning'] ?? 'No reasoning provided.';
    }

    public function failed(): bool
    {
        return $this->failed;
    }

    public function score(): float
    {
        return $this->score;
    }

    public function reasoning(): string
    {
        return $this->reasoning;
    }

    public function string(string $key, string $default): string
    {
        return isset($this->decoded['raw'][$key]) && is_string($this->decoded['raw'][$key])
            ? $this->decoded['raw'][$key]
            : $default;
    }

    public function result(string $context = 'judge'): ScorerResult
    {
        if ($this->failed) {
            return new ScorerResult(
                score: 0.0,
                reasoning: "Failed to parse {$context} response: {$this->raw}",
                scorer: $this->scorer,
            );
        }

        return new ScorerResult(
            score: $this->score,
            reasoning: $this->reasoning,
            scorer: $this->scorer,
        );
    }

    /**
     * @return array{score: float, reasoning: string, raw: array<string, mixed>}|null
     */
    private function decode(string $response): ?array
    {
        $cleaned = mb_trim($response);
        $cleaned = (string) preg_replace('/^```(?:json)?\s*/m', '', $cleaned);
        $cleaned = (string) preg_replace('/\s*```$/m', '', $cleaned);
        $cleaned = mb_trim($cleaned);

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
