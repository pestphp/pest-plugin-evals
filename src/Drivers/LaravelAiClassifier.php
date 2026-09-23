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
                'The classifier judge driver requires the [laravel/ai] package. '
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

        if ($level === null) {
            throw new RuntimeException("The classifier returned no valid level: [{$answer->level()}].");
        }

        if ($answer->probabilities === []) {
            return new Verdict($evaluation->levels[$level], 'Classified without probabilities.', $level);
        }

        $probability = (int) round(max($answer->probabilities) * 100);
        $reasoning = "Classified with {$probability}% probability";

        if ($answer->confidence !== null) {
            $confidence = round($answer->confidence, 2);
            $reasoning .= " and {$confidence} confidence";
        }

        return new Verdict($evaluation->weightedScore($answer->probabilities), "{$reasoning}.", $level);
    }
}
