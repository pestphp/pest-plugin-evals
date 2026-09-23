<?php

declare(strict_types=1);

use Laravel\Ai\Classification;
use Laravel\Ai\Classification\Score;
use Laravel\Ai\Prompts\AgentPrompt;
use Laravel\Ai\Prompts\ClassificationPrompt;
use Laravel\Ai\Responses\Data\ScoreAnswer;
use Laravel\Ai\StructuredAnonymousAgent;
use Pest\Evals\Configuration;
use Pest\Evals\Drivers\LaravelAiClassifier;
use Pest\Evals\Drivers\LaravelAiJudge;
use Pest\Evals\Eval\Evaluation;
use Pest\Evals\Eval\Verdict;
use Pest\Evals\Scorers\Factuality;
use Pest\Evals\Scorers\Relevance;
use Pest\Evals\Tests\TestCase;

uses(TestCase::class);

afterEach(fn () => Configuration::flush());

it('registers the classifier as the judge and keeps the eval gate', function (): void {
    pest()->evals()->classify(provider: 'typesafe', model: 'jev-latest');

    expect(Configuration::resolvedJudge())->toBeInstanceOf(LaravelAiClassifier::class)
        ->and(Configuration::resolvedJudge()->provider)->toBe('typesafe')
        ->and(Configuration::resolvedJudge()->model)->toBe('jev-latest')
        ->and(Configuration::usesDefaultJudge())->toBeTrue();
});

it('keeps the eval gate for an explicit laravel ai judge', function (): void {
    pest()->evals()->judgeUsing(new LaravelAiJudge(model: 'gpt-explicit'));

    expect(Configuration::usesDefaultJudge())->toBeTrue();
});

it('rejects a judge closure that returns neither a score nor a verdict', function (): void {
    pest()->evals()->judgeUsing(fn (Evaluation $evaluation): array => ['score' => 0.2]);

    new Relevance()->score('q', 'a');
})->throws(InvalidArgumentException::class, 'got [array]');

it('is replaced by a later judge stub', function (): void {
    pest()->evals()->classify()->judgeUsing(fn (Evaluation $evaluation): Verdict => new Verdict(1.0, 'stubbed'));

    expect(Configuration::usesDefaultJudge())->toBeFalse()
        ->and(new Relevance()->score('q', 'a')->reasoning)->toBe('stubbed');
});

describe('laravel ai judge', function (): void {
    it('asks for a structured level and scores it', function (): void {
        StructuredAnonymousAgent::fake([['reasoning' => 'On topic.', 'level' => 3]]);

        $result = new Relevance()->score('refund window?', '30 days.');

        expect($result->score)->toBe(0.75)
            ->and($result->reasoning)->toBe('[Mostly relevant with minor tangents] On topic.');

        StructuredAnonymousAgent::assertPrompted(fn (AgentPrompt $prompt): bool => str_contains($prompt->prompt, "## Input\nrefund window?")
            && str_contains($prompt->prompt, "## Output\n30 days.")
            && str_contains($prompt->agent->instructions(), '4: Perfectly relevant, directly addresses the input'));
    });

    it('scores an out-of-range level as zero', function (): void {
        StructuredAnonymousAgent::fake([['reasoning' => 'Odd.', 'level' => 7]]);

        $result = new Relevance()->score('q', 'a');

        expect($result->score)->toBe(0.0)
            ->and($result->reasoning)->toBe('The judge returned no valid level. Odd.');
    });
});

describe('laravel ai classifier', function (): void {
    beforeEach(fn () => pest()->evals()->classify(provider: 'typesafe'));

    it('sends the state, question and levels as a score question', function (): void {
        Classification::fake([['verdict' => new ScoreAnswer(3.8, [0.0, 0.0, 0.0, 0.2, 0.8], [], 0.86)]]);

        $result = new Relevance()->score('question', 'answer');

        expect($result->score)->toEqualWithDelta(0.95, 1e-9)
            ->and($result->reasoning)->toBe('[Perfectly relevant, directly addresses the input] Classified with 80% probability and 0.86 confidence.');

        Classification::assertClassified(fn (ClassificationPrompt $prompt): bool => $prompt->state === ['input' => 'question', 'output' => 'answer']
            && $prompt->questions['verdict'] instanceof Score
            && $prompt->questions['verdict']->levels[0] === 'Completely off-topic'
            && ! str_contains($prompt->questions['verdict']->instructions, 'JSON'));
    });

    it('weights uneven level scores by probability', function (): void {
        Classification::fake([['verdict' => new ScoreAnswer(3.5, [0.0, 0.0, 0.0, 0.5, 0.5], [])]]);

        $result = new Factuality()->score('q', 'Tokyo', 'Tokyo');

        expect($result->score)->toEqualWithDelta(0.95, 1e-9)
            ->and($result->reasoning)->toStartWith('[Approximately equal');
    });

    it('aligns sparse probabilities with their level index', function (): void {
        Classification::fake([['verdict' => new ScoreAnswer(3.8, [3 => 0.2, 4 => 0.8], [], 0.86)]]);

        expect(new Relevance()->score('question', 'answer')->score)->toEqualWithDelta(0.95, 1e-9);
    });

    it('scores the most likely level when the provider returns no probabilities', function (): void {
        Classification::fake([['verdict' => new ScoreAnswer(2.0, [], [])]]);

        expect(new Factuality()->score('q', 'Tokyo, the largest city', 'Tokyo')->score)->toBe(0.8);
    });
});
