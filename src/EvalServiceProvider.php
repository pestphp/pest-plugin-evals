<?php

declare(strict_types=1);

namespace Pest\Evals;

use Illuminate\Support\ServiceProvider;
use Pest\Evals\Commands\EvalMakeCommand;
use Pest\Evals\Commands\ScorerMakeCommand;

final class EvalServiceProvider extends ServiceProvider
{
    #[\Override]
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/Config/eval.php', 'eval');
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/Config/eval.php' => config_path('eval.php'),
            ], 'eval-config');

            $this->publishes([
                __DIR__.'/../stubs/eval.stub' => base_path('stubs/eval.stub'),
                __DIR__.'/../stubs/scorer.stub' => base_path('stubs/scorer.stub'),
            ], 'eval-stubs');

            $this->commands([
                EvalMakeCommand::class,
                ScorerMakeCommand::class,
            ]);
        }
    }
}
