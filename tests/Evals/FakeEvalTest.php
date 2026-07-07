<?php

declare(strict_types=1);

use Pest\Evals\Tests\Fixtures\Agents\CapitalCityAgent;
use Pest\Evals\Tests\Fixtures\Agents\RefundPolicyAgent;

describe('fake mode', function (): void {
    it('bypasses the real agent and uses the faked response', function (): void {
        expect(CapitalCityAgent::class)
            ->prompt('What is the capital of France?', fake: ['Paris'])
            ->toBe('Paris');
    });

    it('cycles through multiple faked responses with samples', function (): void {
        expect(CapitalCityAgent::class)
            ->prompt('What is the capital?', fake: ['Paris', 'London', 'Berlin'])
            ->repeat(3)
            ->toMatch('/^[A-Z]/');
    });

    it('reuses the last response when samples exceed faked responses', function (): void {
        expect(CapitalCityAgent::class)
            ->prompt('What is the capital?', fake: ['Tokyo'])
            ->repeat(3)
            ->toBe('Tokyo');
    });

    it('works with deterministic checks against faked output', function (): void {
        expect(RefundPolicyAgent::class)
            ->prompt(
                'What is your refund policy?',
                fake: ['We offer full refunds within 30 days of purchase. Items must be in original condition with tags attached.'],
            )
            ->toContain('30 days')
            ->toContain('original condition')
            ->toContain('tags')
            ->toMatch('/\d+ days/');
    });

    it('fails when faked response does not match', function (): void {
        expect(fn () => expect(CapitalCityAgent::class)
            ->prompt('What is the capital of France?', fake: ['I do not know'])
            ->toBe('Paris'))->toThrow(PHPUnit\Framework\ExpectationFailedException::class);
    });

    it('works with json expectations', function (): void {
        expect(RefundPolicyAgent::class)
            ->prompt('Return the policy as JSON', fake: ['{"refund_window": 30, "currency": "USD"}'])
            ->toBeJson();
    });

    it('fails when any sample does not match', function (): void {
        expect(fn () => expect(CapitalCityAgent::class)
            ->prompt('What is the capital?', fake: ['Paris', 'wrong', 'Paris'])
            ->repeat(3)
            ->toContain('Paris'))->toThrow(PHPUnit\Framework\ExpectationFailedException::class);
    });
});
