<?php

declare(strict_types=1);

it('can create a scorer class', function (): void {
    $response = $this->artisan('make:scorer', [
        'name' => 'TestScorer',
    ]);

    $response->assertExitCode(0)->run();

    $this->assertFileExists(app_path('Scorers/TestScorer.php'));
});

it('may publish a custom stub', function (): void {
    $this->artisan('vendor:publish', [
        '--tag' => 'eval-stubs',
        '--force' => true,
    ])->assertExitCode(0)->run();

    $this->assertFileExists(base_path('stubs/scorer.stub'));
});
