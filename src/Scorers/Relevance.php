<?php

declare(strict_types=1);

namespace Pest\Evals\Scorers;

use Pest\Evals\Contracts\RequiresJudge;
use Pest\Evals\Support\Judge;

/**
 * @internal
 */
final class Relevance implements RequiresJudge, Scorer
{
    public function score(string $input, string $output, ?string $expected = null): ScorerResult
    {
        return Judge::using(self::class)
            ->instructions('You are an expert relevance evaluator. Determine how relevant AI outputs are to their inputs. Always respond with valid JSON only.')
            ->prompt($this->buildPrompt($input, $output))
            ->result('relevance');
    }

    private function buildPrompt(string $input, string $output): string
    {
        return <<<MARKDOWN
        ## Input (what was asked)
        {$input}

        ## Output (what the agent responded)
        {$output}

        Evaluate the relevance of the output to the input. Consider:
        - Does the output address the question/request?
        - Is the output on-topic?
        - Does the output contain off-topic or irrelevant information?

        Respond with ONLY a JSON object (no markdown, no code fences):
        {"score": <float between 0.0 and 1.0>, "reasoning": "<brief explanation>"}

        Scoring guide:
        - 1.0: Perfectly relevant, directly addresses the input
        - 0.7-0.9: Mostly relevant with minor tangents
        - 0.4-0.6: Partially relevant, some off-topic content
        - 0.1-0.3: Mostly irrelevant
        - 0.0: Completely off-topic
        MARKDOWN;
    }
}
