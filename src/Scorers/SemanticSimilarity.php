<?php

declare(strict_types=1);

namespace Pest\Evals\Scorers;

use InvalidArgumentException;
use Pest\Evals\Configuration;
use Pest\Evals\Contracts\RequiresEmbeddings;
use RuntimeException;

/**
 * @internal
 */
final class SemanticSimilarity implements RequiresEmbeddings, Scorer
{
    private ?string $expectedText = null;

    /**
     * @var array<int, float>|null
     */
    private ?array $expectedEmbedding = null;

    public function score(string $input, string $output, ?string $expected = null): ScorerResult
    {
        if ($expected === null) {
            throw new InvalidArgumentException('The [SemanticSimilarity] scorer requires an expected output to compare against.');
        }

        if ($this->expectedEmbedding === null || $this->expectedText !== $expected) {
            [$outputEmbedding, $this->expectedEmbedding] = $this->embed([$output, $expected]);
            $this->expectedText = $expected;
        } else {
            [$outputEmbedding] = $this->embed([$output]);
        }

        $similarity = $this->cosineSimilarity($outputEmbedding, $this->expectedEmbedding);
        $score = max(0.0, min(1.0, $similarity));

        return new ScorerResult(
            score: $score,
            reasoning: 'Cosine similarity: '.number_format($score, 4),
            scorer: self::class,
        );
    }

    /**
     * @param  array<int, string>  $inputs
     * @return array<int, array<int, float>>
     */
    private function embed(array $inputs): array
    {
        $embeddings = Configuration::resolvedEmbeddings()->embed($inputs);

        if (count($embeddings) !== count($inputs)) {
            throw new RuntimeException(sprintf(
                'The embeddings driver returned [%d] vectors for [%d] inputs.',
                count($embeddings),
                count($inputs),
            ));
        }

        return array_values($embeddings);
    }

    /**
     * @param  array<int, float>  $a
     * @param  array<int, float>  $b
     */
    private function cosineSimilarity(array $a, array $b): float
    {
        if (count($a) !== count($b)) {
            throw new RuntimeException(sprintf(
                'The embeddings driver returned vectors of different dimensions ([%d] and [%d]).',
                count($a),
                count($b),
            ));
        }

        $dotProduct = 0.0;
        $normA = 0.0;
        $normB = 0.0;

        for ($i = 0, $count = count($a); $i < $count; $i++) {
            $dotProduct += $a[$i] * $b[$i];
            $normA += $a[$i] * $a[$i];
            $normB += $b[$i] * $b[$i];
        }

        $denominator = sqrt($normA) * sqrt($normB);

        if ($denominator === 0.0) {
            return 0.0;
        }

        return $dotProduct / $denominator;
    }
}
