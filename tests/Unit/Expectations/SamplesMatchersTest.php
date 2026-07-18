<?php

declare(strict_types=1);

use Pest\Evals\Eval\Context;
use Pest\Evals\Exceptions\EvalExpectationException;
use Pest\Evals\Plugin;
use PHPUnit\Framework\ExpectationFailedException;

beforeEach(function (): void {
    Context::reset();
    Plugin::resetEvalMode();
    $_SERVER['PEST_EVALS'] = '1';
});

afterEach(function (): void {
    Plugin::resetEvalMode();
});

describe('toContain with multiple needles', function (): void {
    it('asserts every needle against every sample', function (): void {
        expect(fn (string $input): string => 'Paris is the capital of France')
            ->prompt('capital?')
            ->repeat(2)
            ->toContain('Paris', 'France');
    });

    it('fails when any needle is missing from a sample', function (): void {
        expect(fn () => expect(fn (string $input): string => 'Paris is nice')
            ->prompt('capital?')
            ->repeat(2)
            ->toContain('Paris', 'MISSING_NEEDLE'))
            ->toThrow(ExpectationFailedException::class);
    });
});

describe('negated matchers on samples', function (): void {
    it('not->toContain passes when no sample contains the needle', function (): void {
        expect(fn (string $input): string => 'London')
            ->prompt('capital?')
            ->repeat(2)
            ->not->toContain('Paris');
    });

    it('not->toContain fails when any sample contains the needle', function (): void {
        $index = 0;
        $agent = function (string $input) use (&$index): string {
            return ['Paris', 'London'][$index++];
        };

        expect(fn () => expect($agent)
            ->prompt('capital?')
            ->repeat(2)
            ->not->toContain('Paris'))
            ->toThrow(ExpectationFailedException::class);
    });

    it('not->toMatch passes when no sample matches the pattern', function (): void {
        expect(fn (string $input): string => 'no digits here')
            ->prompt('question')
            ->repeat(2)
            ->not->toMatch('/\d+/');
    });

    it('not->toMatch fails when any sample matches the pattern', function (): void {
        $index = 0;
        $agent = function (string $input) use (&$index): string {
            return ['30 days', 'no digits'][$index++];
        };

        expect(fn () => expect($agent)
            ->prompt('question')
            ->repeat(2)
            ->not->toMatch('/\d+/'))
            ->toThrow(ExpectationFailedException::class);
    });

    it('not->toBe passes when no sample equals the expected value', function (): void {
        expect(fn (string $input): string => 'London')
            ->prompt('capital?')
            ->repeat(2)
            ->not->toBe('Paris');
    });

    it('not->toBe fails when any sample equals the expected value', function (): void {
        $index = 0;
        $agent = function (string $input) use (&$index): string {
            return ['Paris', 'London'][$index++];
        };

        expect(fn () => expect($agent)
            ->prompt('capital?')
            ->repeat(2)
            ->not->toBe('Paris'))
            ->toThrow(ExpectationFailedException::class);
    });

    it('not->toBeJson passes when no sample is valid JSON', function (): void {
        expect(fn (string $input): string => 'plain text')
            ->prompt('question')
            ->repeat(2)
            ->not->toBeJson();
    });

    it('not->toBeJson fails when any sample is valid JSON', function (): void {
        $index = 0;
        $agent = function (string $input) use (&$index): string {
            return ['{"ok": true}', 'plain text'][$index++];
        };

        expect(fn () => expect($agent)
            ->prompt('question')
            ->repeat(2)
            ->not->toBeJson())
            ->toThrow(ExpectationFailedException::class);
    });
});

describe('repeat validation', function (): void {
    it('rejects a count below one', function (): void {
        expect(fn () => expect(fn (string $input): string => 'x')
            ->prompt('question')
            ->repeat(0))
            ->toThrow(EvalExpectationException::class, '[repeat()] requires at least 1 sample, [0] given.');
    });

    it('rejects a negative count', function (): void {
        expect(fn () => expect(fn (string $input): string => 'x')
            ->prompt('question')
            ->repeat(-3))
            ->toThrow(EvalExpectationException::class);
    });

    it('allows a count of one', function (): void {
        $calls = 0;

        expect(function (string $input) use (&$calls): string {
            $calls++;

            return 'x';
        })->prompt('question')->repeat(1)->toContain('x');

        expect($calls)->toBe(1);
    });

    it('rejects repeat being called twice', function (): void {
        expect(fn () => expect(fn (string $input): string => 'x')
            ->prompt('question')
            ->repeat(2)
            ->repeat(2))
            ->toThrow(EvalExpectationException::class, '[repeat()] may only be called once per [prompt()].');
    });

    it('still allows repeat after a fresh prompt in the chain', function (): void {
        expect(fn (string $input): string => "value: {$input}")
            ->prompt('alpha')
            ->repeat(2)
            ->toContain('alpha')
            ->prompt('beta')
            ->repeat(2)
            ->toContain('beta');
    });
});
