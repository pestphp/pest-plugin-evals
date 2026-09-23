<?php

declare(strict_types=1);

namespace Pest\Evals\Drivers;

use Laravel\Ai\Classification;
use Laravel\Ai\Classification\Score;
use Laravel\Ai\Responses\Data\ScoreAnswer;
use Pest\Evals\Contracts\JudgeDriver;
use Pest\Evals\Eval\Evaluation;
use Pest\Evals\Eval\Verdict;
use RuntimeException;

final class LaravelAiClassifier implements JudgeDriver
{
    public readonly ?string $provider;

    public readonly ?string $model;

    public function __construct(?string $provider = null, ?string $model = null)
    {
        $this->provider = $provider ?? (getenv('PEST_EVALS_LARAVEL_CLASSIFICATION_PROVIDER') ?: null);
        $this->model = $model ?? (getenv('PEST_EVALS_LARAVEL_CLASSIFICATION_MODEL') ?: null);
    }

    public function judge(Evaluation $evaluation): Verdict
    {
        if (! class_exists(Classification::class)) {
            throw new RuntimeException(
                'The classification judge requires [laravel/ai] 1.x. '
                .'Install it with [composer require laravel/ai], or configure a '
                .'custom driver via pest()->evals()->judgeUsing(...).'
            );
        }

        /** @var ScoreAnswer $answer */
        $answer = Classification::of($evaluation->state)
            ->question('verdict', new Score($evaluation->question, $evaluation->labels()))
            ->classify($this->provider, $this->model)
            ->answer('verdict');

        $level = $evaluation->labels()[$answer->level()] ?? null;

        if ($answer->probabilities === []) {
            return new Verdict($level === null ? 0.0 : $evaluation->levels[$level], 'Classified without probabilities.', $level);
        }

        $reasoning = sprintf('Classified with %.0f%% probability', max($answer->probabilities) * 100);

        return new Verdict(
            $evaluation->weightedScore($answer->probabilities),
            $answer->confidence === null ? "{$reasoning}." : sprintf('%s and %.2f confidence.', $reasoning, $answer->confidence),
            $level,
        );
    }
}
