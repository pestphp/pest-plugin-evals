<?php

declare(strict_types=1);

use Pest\Evals\Plugin;

beforeEach(function (): void {
    Plugin::resetEvalMode();
});

afterEach(function (): void {
    Plugin::resetEvalMode();
});

describe('Plugin --evals handling', function (): void {
    it('strips --evals and leaves the remaining arguments untouched', function (): void {
        $plugin = new Plugin();

        expect($plugin->handleArguments(['vendor/bin/pest', '--evals', 'tests/Feature/ExampleTest.php', '--filter=something']))
            ->toBe(['vendor/bin/pest', 'tests/Feature/ExampleTest.php', '--filter=something']);
    });

    it('does not filter, group, or target a directory', function (): void {
        $plugin = new Plugin();

        expect($plugin->handleArguments(['vendor/bin/pest', '--evals']))
            ->toBe(['vendor/bin/pest'])
            ->not->toContain('--group=evals')
            ->not->toContain('tests/Evals');
    });

    it('sets eval mode when --evals is passed', function (): void {
        $plugin = new Plugin();

        $plugin->handleArguments(['vendor/bin/pest', '--evals']);

        expect(Plugin::isEvalMode())->toBeTrue();
    });

    it('does not set eval mode when --evals is not passed', function (): void {
        $plugin = new Plugin();

        $plugin->handleArguments(['vendor/bin/pest']);

        expect(Plugin::isEvalMode())->toBeFalse();
    });

    it('passes arguments through unchanged when --evals is not present', function (): void {
        $plugin = new Plugin();

        expect($plugin->handleArguments(['vendor/bin/pest', '--filter=something']))
            ->toBe(['vendor/bin/pest', '--filter=something']);
    });
});
