<?php

declare(strict_types=1);

use Illuminate\Container\Container;
use Pest\Evals\Eval\EvalExpectationContext;
use Pest\Evals\Eval\EvalReport;
use Pest\Evals\Tests\Fixtures\Agents\InstanceGreetingAgent;
use Pest\Evals\Tests\Fixtures\Support\ContainerGreeting;
use Pest\Evals\Tests\Fixtures\Support\ContainerResolvedPromptAgent;

use function Pest\Evals\expectAgent;

beforeEach(function (): void {
    EvalReport::flush();
    EvalExpectationContext::$current = null;
});

describe('expectAgent with task closure', function (): void {
    it('returns output that works with native Pest expectations', function (): void {
        expectAgent(
            fn (string $input): string => "The answer to '{$input}' is 42.",
            'What is the meaning of life?',
        )->toContain('42')
            ->toContain('answer');
    });

    it('works with toMatch for regex', function (): void {
        expectAgent(
            fn (string $input): string => 'Our refund policy allows returns within 30 days.',
            'What is your return policy?',
        )->toMatch('/\d+ days/');
    });

    it('works with toBe for exact match', function (): void {
        expectAgent(
            fn (string $input): string => 'Paris',
            'Capital of France?',
        )->toBe('Paris');
    });

    it('works with toBeJson', function (): void {
        expectAgent(
            fn (string $input): string => '{"refund_window": 30, "currency": "USD"}',
            'Return the policy as JSON',
        )->toBeJson();
    });
});

describe('expectAgent with faked responses', function (): void {
    it('uses faked response instead of real agent', function (): void {
        expectAgent(
            'FakeAgent',
            'What is the capital of France?',
            fake: ['Paris'],
        )->toBe('Paris');
    });

    it('supports deterministic checks against faked output', function (): void {
        expectAgent(
            'FakeAgent',
            'What is your refund policy?',
            fake: ['We offer full refunds within 30 days of purchase.'],
        )->toContain('30 days')
            ->toContain('refund')
            ->toMatch('/\d+ days/');
    });

    it('fails when faked response does not match', function (): void {
        expect(fn () => expectAgent(
            'FakeAgent',
            'What is the capital of France?',
            fake: ['I do not know'],
        )->toBe('Paris'))->toThrow(PHPUnit\Framework\ExpectationFailedException::class);
    });
});

describe('samples', function (): void {
    it('asserts each sample independently', function (): void {
        expectAgent(
            'FakeAgent',
            'What is the capital?',
            fake: ['Paris', 'Paris', 'Paris'],
        )->repeat(3)
            ->toContain('Paris');
    });

    it('fails if any sample does not meet assertion', function (): void {
        expect(function (): void {
            expectAgent(
                'FakeAgent',
                'What is the capital?',
                fake: ['Paris', 'wrong', 'Paris'],
            )->repeat(3)
                ->toContain('Paris');
        })->toThrow(PHPUnit\Framework\ExpectationFailedException::class);
    });

    it('runs the closure N times', function (): void {
        $callCount = 0;

        expectAgent(
            function (string $input) use (&$callCount): string {
                $callCount++;

                return "response {$callCount}";
            },
            'test',
        )->repeat(3)
            ->toContain('response');

        expect($callCount)->toBe(3);
    });

    it('reuses last faked response when samples exceed fakes', function (): void {
        expectAgent(
            'FakeAgent',
            'What is the capital?',
            fake: ['Tokyo'],
        )->repeat(3)
            ->toBe('Tokyo');
    });

    it('repeat is an alias for samples', function (): void {
        expectAgent(
            'FakeAgent',
            'What is the capital?',
            fake: ['Paris', 'Paris'],
        )->repeat(2)
            ->toBe('Paris');
    });
});

describe('EvalExpectationContext', function (): void {
    it('sets current context via expectAgent', function (): void {
        expectAgent(
            fn (string $input): string => 'output',
            'test prompt',
        );

        expect(EvalExpectationContext::$current)->not->toBeNull();
        expect(EvalExpectationContext::$current->prompt)->toBe('test prompt');
        expect(EvalExpectationContext::$current->agentName)->toBe('Task');
    });

    it('sets agent name from class basename', function (): void {
        expectAgent(
            'App\Agents\MyCustomAgent',
            'test prompt',
            fake: ['output'],
        );

        expect(EvalExpectationContext::$current->agentName)->toBe('MyCustomAgent');
    });
});

describe('expectAgent with container resolution', function (): void {
    it('resolves agent from container and runs it', function (): void {
        Container::getInstance()->bind(ContainerGreeting::class, fn (): ContainerGreeting => new ContainerGreeting('Hello'));

        expectAgent(ContainerResolvedPromptAgent::class, 'World')
            ->toBe('Hello World');
    });
});

describe('expectAgent with agent instance', function (): void {
    it('runs an agent instance directly without container resolution', function (): void {
        $agent = new InstanceGreetingAgent(new ContainerGreeting('Hello'));

        expectAgent($agent, 'World')
            ->toBe('Hello World');
    });

    it('sets agent name from instance class basename', function (): void {
        $agent = new InstanceGreetingAgent(new ContainerGreeting('Hi'));

        expectAgent($agent, 'there');

        expect(EvalExpectationContext::$current->agentName)->toBe('InstanceGreetingAgent');
    });

    it('uses faked responses over agent instance when fake is provided', function (): void {
        $agent = new InstanceGreetingAgent(new ContainerGreeting('Hello'));

        expectAgent($agent, 'World', fake: ['faked response'])
            ->toBe('faked response');
    });
});

describe('EvalExpectationContext resolveOutputs', function (): void {
    it('returns single output by default', function (): void {
        expectAgent(
            fn (string $input): string => 'single output',
            'test',
        )->toBe('single output');
    });

    it('passes prompt to task closure', function (): void {
        expectAgent(
            fn (string $input): string => "received: {$input}",
            'hello world',
        )->toContain('hello world');
    });
});

describe('EvalReport integration', function (): void {
    it('has no entries when only native Pest expectations used', function (): void {
        expectAgent(
            fn (string $input): string => 'hello',
            'test',
        )->toContain('hello');

        expect(EvalReport::instance()->totalEvals())->toBe(0);
    });
});

describe('EvalReport', function (): void {
    it('tracks scorer results', function (): void {
        $report = EvalReport::instance();

        $report->addScorerResult('TestAgent', 'Relevance', 0.85, 0.7);

        expect($report->totalEvals())->toBe(1);
        expect($report->passedEvals())->toBe(1);
        expect($report->avgScore())->toBe(0.85);
    });

    it('tracks failed scorer results', function (): void {
        $report = EvalReport::instance();

        $report->addScorerResult('TestAgent', 'Safety', 0.3, 0.7);

        expect($report->totalEvals())->toBe(1);
        expect($report->passedEvals())->toBe(0);
        expect($report->avgScore())->toBe(0.3);
    });

    it('calculates average across multiple results', function (): void {
        $report = EvalReport::instance();

        $report->addScorerResult('Agent1', 'Relevance', 0.9, 0.7);
        $report->addScorerResult('Agent2', 'Safety', 0.7, 0.7);
        $report->addScorerResult('Agent3', 'Factuality', 0.4, 0.7);

        expect($report->totalEvals())->toBe(3);
        expect($report->passedEvals())->toBe(2);
        expect($report->avgScore())->toBeGreaterThan(0.66);
        expect($report->avgScore())->toBeLessThan(0.68);
    });

    it('renders empty summary for no entries', function (): void {
        expect(EvalReport::instance()->renderSummary())->toBe('');
    });

    it('renders summary with entries', function (): void {
        $report = EvalReport::instance();
        $report->addScorerResult('TestAgent', 'Relevance', 0.9, 0.7);

        $summary = $report->renderSummary();
        expect($summary)->toContain('1/1 evals passed');
        expect($summary)->toContain('0.90');
    });

    it('flushes correctly', function (): void {
        $report = EvalReport::instance();
        $report->addScorerResult('TestAgent', 'Relevance', 0.9, 0.7);

        EvalReport::flush();

        expect(EvalReport::instance()->totalEvals())->toBe(0);
    });

    it('flushes entries to a temp file and merges them back', function (): void {
        $report = EvalReport::instance();
        $report->addScorerResult('Agent1', 'Relevance', 0.9, 0.7);
        $report->addScorerResult('Agent2', 'Safety', 0.8, 0.7);

        $report->flushToFile();

        EvalReport::flush();
        expect(EvalReport::instance()->totalEvals())->toBe(0);

        EvalReport::instance()->mergeWorkerFiles();

        expect(EvalReport::instance()->totalEvals())->toBe(2);
        expect(EvalReport::instance()->passedEvals())->toBe(2);
    });

    it('does not create a file when entries are empty', function (): void {
        $pattern = sys_get_temp_dir().'/pest_eval_*.json';
        $before = count(glob($pattern) ?: []);

        EvalReport::instance()->flushToFile();

        $after = count(glob($pattern) ?: []);
        expect($after)->toBe($before);
    });

    it('mergeWorkerFiles is a no-op when no files exist', function (): void {
        EvalReport::instance()->mergeWorkerFiles();

        expect(EvalReport::instance()->totalEvals())->toBe(0);
    });
});
