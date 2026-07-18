<?php

declare(strict_types=1);

namespace Pest\Evals\Scorers;

use Pest\Evals\Contracts\RequiresJudge;
use Pest\Evals\Support\Judge;

/**
 * @internal
 */
final class LlmJudge implements RequiresJudge, Scorer
{
    public function __construct(
        private readonly string $criteria = '',
    ) {}

    public function score(string $input, string $output, ?string $expected = null): ScorerResult
    {
        return Judge::using(self::class)
            ->instructions('You are an expert AI evaluation judge. You evaluate AI agent responses against specific criteria. Always respond with valid JSON only.')
            ->prompt($this->buildPrompt($input, $output, $expected))
            ->result();
    }

    private function buildPrompt(string $input, string $output, ?string $expected): string
    {
        $prompt = <<<MARKDOWN
        ## Input (what was asked)
        {$input}

        ## Output (what the agent responded)
        {$output}
        MARKDOWN;

        if ($expected !== null) {
            $prompt .= <<<MARKDOWN

            ## Expected Output (reference answer)
            {$expected}
            MARKDOWN;
        }

        return $prompt.<<<MARKDOWN

        ## Evaluation Criteria
        {$this->criteria}

        Evaluate the output against the criteria above. Respond with ONLY a JSON object (no markdown, no code fences):
        {"score": <float between 0.0 and 1.0>, "reasoning": "<brief explanation>"}

        Scoring guide:
        - 1.0: Fully meets all criteria
        - 0.7-0.9: Mostly meets criteria with minor gaps
        - 0.4-0.6: Partially meets criteria
        - 0.1-0.3: Mostly fails to meet criteria
        - 0.0: Completely fails to meet criteria
        MARKDOWN;
    }
}
