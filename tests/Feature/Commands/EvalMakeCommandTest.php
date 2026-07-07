<?php

declare(strict_types=1);

it('can create an eval test file', function (): void {
    $response = $this->artisan('make:eval', [
        'name' => 'MyAgent',
    ]);

    $response->assertExitCode(0)->run();

    $this->assertFileExists(base_path('tests/Evals/MyAgentEvalTest.php'));
    $this->assertStringContainsString(
        "->group('eval');",
        (string) file_get_contents(base_path('tests/Evals/MyAgentEvalTest.php')),
    );
});

it('may publish a custom stub', function (): void {
    $this->artisan('vendor:publish', [
        '--tag' => 'eval-stubs',
        '--force' => true,
    ])->assertExitCode(0)->run();

    $this->assertFileExists(base_path('stubs/eval.stub'));
});
