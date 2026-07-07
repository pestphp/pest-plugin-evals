<?php

declare(strict_types=1);

namespace Pest\Evals\Eval;

final class EvalReport
{
    private const string TEMP_FILE_PREFIX = 'pest_eval_';

    private static ?self $instance = null;

    /** @var array<int, array{agent: string, scorer: string, score: float, passed: bool}> */
    private array $entries = [];

    public static function instance(): self
    {
        return self::$instance ??= new self();
    }

    public static function flush(): void
    {
        self::$instance = null;
    }

    public function addScorerResult(string $agent, string $scorer, float $score, float $threshold): void
    {
        $this->entries[] = [
            'agent' => $agent,
            'scorer' => class_basename($scorer),
            'score' => $score,
            'passed' => $score >= $threshold,
        ];
    }

    public function totalEvals(): int
    {
        return count($this->entries);
    }

    public function passedEvals(): int
    {
        return count(array_filter($this->entries, fn (array $entry): bool => $entry['passed']));
    }

    public function avgScore(): float
    {
        if ($this->entries === []) {
            return 0.0;
        }

        return array_sum(array_column($this->entries, 'score')) / count($this->entries);
    }

    public function flushToFile(): void
    {
        if ($this->entries === []) {
            return;
        }

        $token = is_string($_SERVER['UNIQUE_TEST_TOKEN'] ?? null)
            ? $_SERVER['UNIQUE_TEST_TOKEN']
            : uniqid('eval_');
        $path = sys_get_temp_dir().'/'.self::TEMP_FILE_PREFIX.$token.'.json';

        file_put_contents($path, json_encode($this->entries, JSON_THROW_ON_ERROR));
    }

    public function mergeWorkerFiles(): void
    {
        $pattern = sys_get_temp_dir().'/'.self::TEMP_FILE_PREFIX.'*.json';

        foreach (glob($pattern) ?: [] as $file) {
            $decoded = json_decode((string) file_get_contents($file), true);

            if (is_array($decoded)) {
                /** @var array<int, array{agent: string, scorer: string, score: float, passed: bool}> $decoded */
                foreach ($decoded as $entry) {
                    $this->entries[] = $entry;
                }
            }

            unlink($file);
        }
    }

    public function renderSummary(): string
    {
        if ($this->entries === []) {
            return '';
        }

        $passed = $this->passedEvals();
        $total = $this->totalEvals();
        $avgScore = number_format($this->avgScore(), 2);

        $passColor = $passed === $total ? 'green' : 'yellow';

        $lines = [''];
        $lines[] = "<fg={$passColor};options=bold>{$passed}/{$total} evals passed</>  <fg=gray>avg score {$avgScore}</>";
        $lines[] = '';

        return implode("\n", $lines);
    }
}
