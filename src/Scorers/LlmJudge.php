<?php

declare(strict_types=1);

namespace Pest\Evals\Scorers;

use Pest\Evals\Concerns\JudgesWithLlm;

final class LlmJudge implements Scorer
{
    use JudgesWithLlm;

    public function __construct(
        private string $criteria = '',
        private ?string $provider = null,
        private ?string $model = null,
    ) {}

    public function score(string $input, string $output, ?string $expected = null): ScorerResult
    {
        if ($this->criteria === '') {
            return new ScorerResult(
                score: 0.0,
                reasoning: 'No evaluation criteria provided.',
                scorer: self::class,
            );
        }

        $prompt = $this->buildPrompt($input, $output, $expected);
        $response = $this->judge(
            $prompt,
            'You are an expert AI evaluation judge. You evaluate AI agent responses against specific criteria. Always respond with valid JSON only.',
        );

        return $this->parseJudgeResponse($response);
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
