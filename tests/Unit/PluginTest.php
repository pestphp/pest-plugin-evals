<?php

declare(strict_types=1);

use Pest\Evals\Eval\EvalReport;
use Pest\Evals\Plugin;

beforeEach(function (): void {
    Plugin::resetEvalMode();
});

describe('Plugin --evals handling', function (): void {
    it('falls back to the eval group when tests/Evals is absent', function (): void {
        withTemporaryWorkingDirectory(function (): void {
            EvalReport::flush();
            $plugin = new Plugin();

            expect($plugin->handleArguments(['vendor/bin/pest', '--evals']))
                ->toBe(['vendor/bin/pest', '--group=evals']);
            expect($plugin->addOutput(0))->toBe(0);
        });
    });

    it('targets tests/Evals when the eval directory exists', function (): void {
        withTemporaryWorkingDirectory(function (string $directory): void {
            mkdir("{$directory}/tests/Evals", 0777, true);

            EvalReport::flush();
            $plugin = new Plugin();

            expect($plugin->handleArguments(['vendor/bin/pest', '--evals']))
                ->toBe(['vendor/bin/pest', 'tests/Evals']);
            expect($plugin->addOutput(0))->toBe(0);
        });
    });

    it('prefers the eval group when explicit paths are provided', function (): void {
        withTemporaryWorkingDirectory(function (string $directory): void {
            mkdir("{$directory}/tests/Evals", 0777, true);

            EvalReport::flush();
            $plugin = new Plugin();

            expect($plugin->handleArguments(['vendor/bin/pest', '--evals', 'tests/Feature/ExampleTest.php']))
                ->toBe(['vendor/bin/pest', 'tests/Feature/ExampleTest.php', '--group=evals']);
            expect($plugin->addOutput(0))->toBe(0);
        });
    });

    it('sets eval mode when --evals is passed', function (): void {
        withTemporaryWorkingDirectory(function (): void {
            $plugin = new Plugin();

            $plugin->handleArguments(['vendor/bin/pest', '--evals']);

            expect(Plugin::isEvalMode())->toBeTrue();
        });
    });

    it('does not set eval mode when --evals is not passed', function (): void {
        withTemporaryWorkingDirectory(function (): void {
            $plugin = new Plugin();

            $plugin->handleArguments(['vendor/bin/pest']);

            expect(Plugin::isEvalMode())->toBeFalse();
        });
    });

    it('passes arguments through unchanged when --evals is not present', function (): void {
        withTemporaryWorkingDirectory(function (): void {
            $plugin = new Plugin();

            expect($plugin->handleArguments(['vendor/bin/pest', '--filter=something']))
                ->toBe(['vendor/bin/pest', '--filter=something']);
        });
    });
});

/**
 * @param  callable(string): void  $callback
 */
function withTemporaryWorkingDirectory(callable $callback): void
{
    $originalDirectory = getcwd();
    $temporaryDirectory = sys_get_temp_dir().'/pest-plugin-evals-'.uniqid('', true);

    mkdir($temporaryDirectory, 0777, true);
    chdir($temporaryDirectory);

    try {
        $callback($temporaryDirectory);
    } finally {
        chdir($originalDirectory);
        Plugin::resetEvalMode();

        if (is_dir("{$temporaryDirectory}/tests/Evals")) {
            rmdir("{$temporaryDirectory}/tests/Evals");
        }

        if (is_dir("{$temporaryDirectory}/tests")) {
            rmdir("{$temporaryDirectory}/tests");
        }

        rmdir($temporaryDirectory);
    }
}
