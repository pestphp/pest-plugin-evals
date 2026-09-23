<?php

declare(strict_types=1);

use Pest\Evals\Configuration;
use Pest\Evals\Eval\Context;
use Pest\Evals\Eval\Evaluation;
use Pest\Evals\Plugin;

beforeEach(function (): void {
    Context::reset();
    Configuration::flush();
    Plugin::resetEvalMode();
    $_SERVER['PEST_EVALS'] = '1';
});

afterEach(function (): void {
    Configuration::flush();
    Plugin::resetEvalMode();
});

it('sends the older expectation input to the judge, not the newer one', function (): void {
    $prompts = [];

    pest()->evals()->judgeUsing(function (Evaluation $evaluation) use (&$prompts): float {
        $prompts[] = implode(' ', $evaluation->state);

        return 1.0;
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

    pest()->evals()->judgeUsing(function (Evaluation $evaluation) use (&$prompts): float {
        $prompts[] = implode(' ', $evaluation->state);

        return 1.0;
    });

    $foo = expect(fn (string $input): string => 'ALPHA_OUTPUT')
        ->prompt('QUESTION_ALPHA')
        ->repeat(2);

    $bar = expect(fn (string $input): string => 'BETA_OUTPUT')
        ->prompt('QUESTION_BETA')
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

it('asserts each stored expectation against its own samples, evaluated out of order', function (): void {
    $alpha = expect(fn (string $input): string => 'ALPHA_OUTPUT')
        ->prompt('QUESTION_ALPHA')
        ->repeat(2);

    $beta = expect(fn (string $input): string => 'BETA_OUTPUT')
        ->prompt('QUESTION_BETA')
        ->repeat(3);

    $alpha->toContain('ALPHA_OUTPUT');
    $beta->toContain('BETA_OUTPUT');

    expect(fn () => $alpha->toContain('BETA_OUTPUT'))
        ->toThrow(PHPUnit\Framework\ExpectationFailedException::class);

    expect(fn () => $beta->toContain('ALPHA_OUTPUT'))
        ->toThrow(PHPUnit\Framework\ExpectationFailedException::class);
});

it('routes toBe, toMatch and toBeJson to the matching expectation samples', function (): void {
    $json = expect(fn (string $input): string => '{"ok": true}')
        ->prompt('QUESTION_JSON')
        ->repeat(2);

    $city = expect(fn (string $input): string => 'Paris')
        ->prompt('QUESTION_CITY')
        ->repeat(2);

    $city->toBe('Paris')->toMatch('/^Paris$/');
    $json->toBeJson();

    expect(fn () => $city->toBe('{"ok": true}'))
        ->toThrow(PHPUnit\Framework\ExpectationFailedException::class);
});

it('does not hijack native matchers on plain string expectations sharing a sample value', function (): void {
    expect(fn (string $input): string => 'Paris')
        ->prompt('QUESTION_CITY')
        ->repeat(2)
        ->toContain('Paris');

    expect('Paris')
        ->toContain('Par')
        ->toContain('ris');
});
