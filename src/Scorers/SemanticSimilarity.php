<?php

declare(strict_types=1);

namespace Pest\Evals\Scorers;

use Laravel\Ai\Embeddings;

final readonly class SemanticSimilarity implements Scorer
{
    public function __construct(
        private ?string $provider = null,
        private ?string $model = null,
    ) {}

    public function score(string $input, string $output, ?string $expected = null): ScorerResult
    {
        if ($expected === null) {
            return new ScorerResult(
                score: 0.0,
                reasoning: 'No expected output provided for semantic similarity comparison.',
                scorer: self::class,
            );
        }

        /** @var string $provider */
        $provider = $this->provider ?? config('eval.ai.embedding.provider', 'openai');
        /** @var string $model */
        $model = $this->model ?? config('eval.ai.embedding.model', 'text-embedding-3-small');

        $response = Embeddings::for([$output, $expected])
            ->generate($provider, $model);

        /** @var list<float> $outputEmbedding */
        $outputEmbedding = $response->embeddings[0];
        /** @var list<float> $expectedEmbedding */
        $expectedEmbedding = $response->embeddings[1];

        $similarity = $this->cosineSimilarity($outputEmbedding, $expectedEmbedding);
        $score = max(0.0, min(1.0, $similarity));

        return new ScorerResult(
            score: $score,
            reasoning: 'Cosine similarity: '.number_format($score, 4),
            scorer: self::class,
        );
    }

    /**
     * @param  list<float>  $a
     * @param  list<float>  $b
     */
    private function cosineSimilarity(array $a, array $b): float
    {
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
