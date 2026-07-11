<?php

declare(strict_types=1);

use Pest\Evals\Configuration;
use Pest\Evals\Eval\EvalExpectationContext;
use Pest\Evals\Eval\EvalReport;

beforeEach(function (): void {
    EvalReport::flush();
    EvalExpectationContext::reset();
    Configuration::flush();
});

afterEach(function (): void {
    Configuration::flush();
});

describe('context isolation between interleaved prompt expectations', function (): void {
    it('sends the older expectation input to the judge, not the newer one', function (): void {
        $prompts = [];

        pest()->evals()->judgeUsing(function (string $instructions, string $prompt) use (&$prompts): string {
            $prompts[] = $prompt;

            return '{"score": 1.0, "reasoning": "looks great"}';
        });

        $foo = expect(fn (string $input): string => 'An answer about alpha.')
            ->prompt('QUESTION_ALPHA');

        $bar = expect(fn (string $input): string => 'An answer about beta.')
            ->prompt('QUESTION_BETA');

        $foo->toBeRelevant();

        expect($prompts)->toHaveCount(1);
        expect($prompts[0])
            ->toContain('QUESTION_ALPHA')
            ->toContain('An answer about alpha.')
            ->not->toContain('QUESTION_BETA')
            ->not->toContain('An answer about beta.');

        $bar->toBeRelevant();

        expect($prompts)->toHaveCount(2);
        expect($prompts[1])
            ->toContain('QUESTION_BETA')
            ->not->toContain('QUESTION_ALPHA');
    });

    it('scores the older expectation repeated samples, not the newer one', function (): void {
        $prompts = [];

        pest()->evals()->judgeUsing(function (string $instructions, string $prompt) use (&$prompts): string {
            $prompts[] = $prompt;

            return '{"score": 1.0, "reasoning": "looks great"}';
        });

        $foo = expect('AlphaAgent')
            ->prompt('QUESTION_ALPHA', fake: ['ALPHA_OUTPUT'])
            ->repeat(2);

        $bar = expect('BetaAgent')
            ->prompt('QUESTION_BETA', fake: ['BETA_OUTPUT'])
            ->repeat(2);

        $foo->toBeRelevant();

        expect($prompts)->toHaveCount(2);

        foreach ($prompts as $prompt) {
            expect($prompt)
                ->toContain('QUESTION_ALPHA')
                ->toContain('ALPHA_OUTPUT')
                ->not->toContain('QUESTION_BETA')
                ->not->toContain('BETA_OUTPUT');
        }
    });
});
