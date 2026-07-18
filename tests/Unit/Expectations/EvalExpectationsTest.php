<?php

declare(strict_types=1);

use Illuminate\Container\Container;
use Pest\Evals\Eval\Context;
use Pest\Evals\Plugin;
use Pest\Evals\Tests\Fixtures\Agents\InstanceGreetingAgent;
use Pest\Evals\Tests\Fixtures\Support\ContainerGreeting;
use Pest\Evals\Tests\Fixtures\Support\ContainerResolvedPromptAgent;

beforeEach(function (): void {
    Context::reset();
    Plugin::resetEvalMode();
    $_SERVER['PEST_EVALS'] = '1';
});

afterEach(function (): void {
    Plugin::resetEvalMode();
});

describe('prompt with task closure', function (): void {
    it('returns output that works with native Pest expectations', function (): void {
        expect(fn (string $input): string => "The answer to '{$input}' is 42.")
            ->prompt('What is the meaning of life?')
            ->toContain('42')
            ->toContain('answer');
    });

    it('works with toMatch for regex', function (): void {
        expect(fn (string $input): string => 'Our refund policy allows returns within 30 days.')
            ->prompt('What is your return policy?')
            ->toMatch('/\d+ days/');
    });

    it('works with toBe for exact match', function (): void {
        expect(fn (string $input): string => 'Paris')
            ->prompt('Capital of France?')
            ->toBe('Paris');
    });

    it('works with toBeJson', function (): void {
        expect(fn (string $input): string => '{"refund_window": 30, "currency": "USD"}')
            ->prompt('Return the policy as JSON')
            ->toBeJson();
    });
});

describe('prompt with agent output', function (): void {
    it('supports deterministic checks against the output', function (): void {
        expect(fn (string $input): string => 'We offer full refunds within 30 days of purchase.')
            ->prompt('What is your refund policy?')
            ->toContain('30 days')
            ->toContain('refund')
            ->toMatch('/\d+ days/');
    });

    it('fails when the output does not match', function (): void {
        expect(fn () => expect(fn (string $input): string => 'I do not know')
            ->prompt('What is the capital of France?')
            ->toBe('Paris'))->toThrow(PHPUnit\Framework\ExpectationFailedException::class);
    });
});

describe('samples', function (): void {
    it('asserts each sample independently', function (): void {
        expect(fn (string $input): string => 'Paris')
            ->prompt('What is the capital?')
            ->repeat(3)
            ->toContain('Paris');
    });

    it('fails if any sample does not meet assertion', function (): void {
        $index = 0;
        $agent = function (string $input) use (&$index): string {
            return ['Paris', 'wrong', 'Paris'][$index++];
        };

        expect(fn () => expect($agent)
            ->prompt('What is the capital?')
            ->repeat(3)
            ->toContain('Paris'))->toThrow(PHPUnit\Framework\ExpectationFailedException::class);
    });

    it('runs the closure N times', function (): void {
        $callCount = 0;

        expect(function (string $input) use (&$callCount): string {
            $callCount++;

            return "response {$callCount}";
        })
            ->prompt('test')
            ->repeat(3)
            ->toContain('response');

        expect($callCount)->toBe(3);
    });

    it('applies toBe to every sample', function (): void {
        expect(fn (string $input): string => 'Paris')
            ->prompt('What is the capital?')
            ->repeat(2)
            ->toBe('Paris');
    });
});

describe('Context', function (): void {
    it('binds context to the expectation via prompt', function (): void {
        $expectation = expect(fn (string $input): string => 'output')
            ->prompt('test prompt');

        $context = Context::for($expectation);

        expect($context)->not->toBeNull();
        expect($context->prompt)->toBe('test prompt');
        expect($context->agentName)->toBe('Task');
    });

    it('sets agent name from class basename', function (): void {
        Container::getInstance()->bind(ContainerGreeting::class, fn (): ContainerGreeting => new ContainerGreeting('Hello'));

        $expectation = expect(ContainerResolvedPromptAgent::class)->prompt('World');

        expect(Context::for($expectation)->agentName)->toBe('ContainerResolvedPromptAgent');
    });
});

describe('prompt against a real agent (eval mode)', function (): void {
    it('resolves an agent from the container and runs it', function (): void {
        Container::getInstance()->bind(ContainerGreeting::class, fn (): ContainerGreeting => new ContainerGreeting('Hello'));

        expect(ContainerResolvedPromptAgent::class)
            ->prompt('World')
            ->toBe('Hello World');
    });

    it('runs an agent instance directly without container resolution', function (): void {
        $agent = new InstanceGreetingAgent(new ContainerGreeting('Hello'));

        expect($agent)
            ->prompt('World')
            ->toBe('Hello World');
    });

    it('sets agent name from instance class basename', function (): void {
        $agent = new InstanceGreetingAgent(new ContainerGreeting('Hi'));

        $expectation = expect($agent)->prompt('there');

        expect(Context::for($expectation)->agentName)->toBe('InstanceGreetingAgent');
    });
});

describe('prompt outside eval mode', function (): void {
    beforeEach(fn () => Plugin::resetEvalMode());

    it('skips agent targets', function (): void {
        expect(fn () => expect(InstanceGreetingAgent::class)->prompt('World'))
            ->toThrow(PHPUnit\Framework\SkippedWithMessageException::class);
    });

    it('skips closure targets', function (): void {
        expect(fn () => expect(fn (string $input): string => 'x')->prompt('World'))
            ->toThrow(PHPUnit\Framework\SkippedWithMessageException::class);
    });
});

describe('Context resolveOutputs', function (): void {
    it('returns single output by default', function (): void {
        expect(fn (string $input): string => 'single output')
            ->prompt('test')
            ->toBe('single output');
    });

    it('passes prompt to task closure', function (): void {
        expect(fn (string $input): string => "received: {$input}")
            ->prompt('hello world')
            ->toContain('hello world');
    });
});

it('re-prompts the same agent with a fresh input when chained', function (): void {
    expect(fn (string $input): string => "echo: {$input}")
        ->prompt('turn one')
        ->toContain('turn one')
        ->prompt('turn two')
        ->toContain('turn two')
        ->not->toContain('turn one');
});

it('re-runs the agent on each prompt in the chain', function (): void {
    $calls = 0;
    $agent = function (string $input) use (&$calls): string {
        $calls++;

        return $input;
    };

    expect($agent)
        ->prompt('alpha')
        ->toBe('alpha')
        ->prompt('beta')
        ->toBe('beta');

    expect($calls)->toBe(2);
});

it('keeps re-prompting after a repeat in the chain', function (): void {
    expect(fn (string $input): string => "value: {$input}")
        ->prompt('alpha')
        ->repeat(2)
        ->toContain('alpha')
        ->prompt('beta')
        ->toContain('beta');
});

it('re-prompts an agent instance across turns', function (): void {
    $agent = new InstanceGreetingAgent(new ContainerGreeting('Hello'));

    expect($agent)
        ->prompt('Nuno')
        ->toBe('Hello Nuno')
        ->prompt('World')
        ->toBe('Hello World');
});
