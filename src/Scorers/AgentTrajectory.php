<?php

declare(strict_types=1);

namespace Pest\Evals\Scorers;

use Pest\Evals\Concerns\ParsesToolCalls;

final class AgentTrajectory implements Scorer
{
    use ParsesToolCalls;

    /**
     * @param  list<string>  $sequence  Expected tool call sequence (in order)
     */
    public function __construct(
        private array $sequence = [],
        private bool $strictOrder = true,
    ) {
    }

    public function score(string $input, string $output, ?string $expected = null): ScorerResult
    {
        if ($this->sequence === []) {
            return new ScorerResult(
                score: 0.0,
                reasoning: 'No expected tool sequence provided.',
                scorer: self::class,
            );
        }

        $toolCalls = $this->parseToolNamesFromOutput($output);

        if ($toolCalls === null) {
            return new ScorerResult(
                score: 0.0,
                reasoning: 'Could not parse tool calls from output.',
                scorer: self::class,
            );
        }

        if ($this->strictOrder) {
            return $this->scoreStrictOrder($toolCalls);
        }

        return $this->scoreSubset($toolCalls);
    }

    /**
     * @param  list<string>  $toolCalls
     */
    private function scoreStrictOrder(array $toolCalls): ScorerResult
    {
        $sequenceIndex = 0;
        $matched = 0;

        foreach ($toolCalls as $call) {
            if ($sequenceIndex < count($this->sequence) && $call === $this->sequence[$sequenceIndex]) {
                $matched++;
                $sequenceIndex++;
            }
        }

        $score = $matched / count($this->sequence);
        $expectedStr = implode(' -> ', $this->sequence);
        $actual = implode(' -> ', $toolCalls);

        return new ScorerResult(
            score: $score,
            reasoning: $score >= 1.0
                ? "Tool sequence matches: {$expectedStr}"
                : "Expected sequence: {$expectedStr}. Actual: {$actual}. Matched {$matched}/".count($this->sequence).'.',
            scorer: self::class,
        );
    }

    /**
     * @param  list<string>  $toolCalls
     */
    private function scoreSubset(array $toolCalls): ScorerResult
    {
        $matched = [];
        $missing = [];

        foreach ($this->sequence as $expectedTool) {
            if (in_array($expectedTool, $toolCalls, true)) {
                $matched[] = $expectedTool;
            } else {
                $missing[] = $expectedTool;
            }
        }

        $score = count($matched) / count($this->sequence);

        return new ScorerResult(
            score: $score,
            reasoning: $missing === []
                ? 'All expected tools were called.'
                : 'Missing tools: '.implode(', ', $missing).'.',
            scorer: self::class,
        );
    }
}
