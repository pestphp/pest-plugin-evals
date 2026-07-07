<?php

declare(strict_types=1);

namespace Pest\Evals\Scorers;

use Pest\Evals\Concerns\JudgesWithLlm;

final class Factuality implements Scorer
{
    use JudgesWithLlm;

    public function __construct(
        private ?string $provider = null,
        private ?string $model = null,
    ) {}

    public function score(string $input, string $output, ?string $expected = null): ScorerResult
    {
        if ($expected === null) {
            return new ScorerResult(
                score: 0.0,
                reasoning: 'No reference answer provided for factuality check.',
                scorer: self::class,
            );
        }

        $prompt = $this->buildPrompt($input, $output, $expected);
        $response = $this->judge(
            $prompt,
            'You are an expert factuality evaluator. Compare AI outputs against reference answers for factual consistency. Always respond with valid JSON only.',
        );

        $result = $this->decodeJudgeResponse($response);

        if ($result === null) {
            return new ScorerResult(
                score: 0.0,
                reasoning: "Failed to parse factuality response: {$response}",
                scorer: self::class,
            );
        }

        $category = isset($result['raw']['category']) && is_string($result['raw']['category'])
            ? $result['raw']['category']
            : 'unknown';

        return new ScorerResult(
            score: $result['score'],
            reasoning: "[{$category}] {$result['reasoning']}",
            scorer: self::class,
        );
    }

    private function buildPrompt(string $input, string $output, string $expected): string
    {
        return <<<MARKDOWN
        ## Question/Input
        {$input}

        ## AI Output (to evaluate)
        {$output}

        ## Reference Answer (ground truth)
        {$expected}

        Classify the factual relationship between the AI output and reference answer into one of these categories, then respond with ONLY a JSON object (no markdown, no code fences):

        Categories:
        - "equal": Output contains the same facts as reference (score: 1.0)
        - "approximately_equal": Output is very close, minor wording differences (score: 0.9)
        - "superset": Output contains all reference facts plus additional correct facts (score: 0.8)
        - "subset": Output contains some but not all reference facts (score: 0.6)
        - "disagreement": Output contradicts the reference answer (score: 0.0)

        {"score": <float>, "category": "<category>", "reasoning": "<brief explanation>"}
        MARKDOWN;
    }
}
