<?php

declare(strict_types=1);

namespace Pest\Evals\Commands;

use Illuminate\Console\GeneratorCommand;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputOption;

#[AsCommand(
    name: 'make:scorer',
    description: 'Create a new custom eval scorer class',
)]
final class ScorerMakeCommand extends GeneratorCommand
{
    protected $type = 'Scorer';

    protected function getStub(): string
    {
        return file_exists($customPath = $this->laravel->basePath('stubs/scorer.stub'))
            ? $customPath
            : __DIR__.'/../../stubs/scorer.stub';
    }

    /**
     * @param  string  $rootNamespace
     */
    #[\Override]
    protected function getDefaultNamespace($rootNamespace): string
    {
        return "{$rootNamespace}\\Scorers";
    }

    /**
     * @return array<int, array<int, string|int>>
     */
    #[\Override]
    protected function getOptions(): array
    {
        return [
            ['force', 'f', InputOption::VALUE_NONE, 'Create the scorer even if it already exists'],
        ];
    }
}
