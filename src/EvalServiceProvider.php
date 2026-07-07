<?php

declare(strict_types=1);

namespace Pest\Evals;

use Illuminate\Support\ServiceProvider;

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
        }
    }
}
