<?php

declare(strict_types=1);

namespace Pest\Evals\Scorers;

use InvalidArgumentException;
use Pest\Evals\Contracts\RequiresJudge;
use Pest\Evals\Support\Judge;

/**
 * @internal
 */
final class Factuality implements RequiresJudge, Scorer
{
    private const array CATEGORY_SCORES = [
        'equal' => 1.0,
        'approximately_equal' => 0.9,
        'superset' => 0.8,
        'subset' => 0.6,
        'disagreement' => 0.0,
    ];

    public function score(string $input, string $output, ?string $expected = null): ScorerResult
    {
        if ($expected === null) {
            throw new InvalidArgumentException('The [Factuality] scorer requires a reference answer to compare against.');
        }

        $response = Judge::using(self::class)
            ->instructions('You are an expert factuality evaluator. Compare AI outputs against reference answers for factual consistency. Always respond with valid JSON only.')
            ->prompt($this->buildPrompt($input, $output, $expected));

        if ($response->failed()) {
            return $response->result('factuality');
        }

        $category = $response->string('category', 'unknown');

        return new ScorerResult(
            score: self::CATEGORY_SCORES[$category] ?? $response->score(),
            reasoning: "[{$category}] {$response->reasoning()}",
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
