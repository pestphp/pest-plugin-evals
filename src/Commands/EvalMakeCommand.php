<?php

declare(strict_types=1);

namespace Pest\Evals\Commands;

use Illuminate\Console\GeneratorCommand;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputOption;

#[AsCommand(
    name: 'make:eval',
    description: 'Create a new eval test file for an AI agent',
)]
final class EvalMakeCommand extends GeneratorCommand
{
    protected $type = 'Eval';

    protected function getStub(): string
    {
        return file_exists($customPath = $this->laravel->basePath('stubs/eval.stub'))
            ? $customPath
            : __DIR__.'/../../stubs/eval.stub';
    }

    /**
     * @param  string  $name
     */
    #[\Override]
    protected function getPath($name): string
    {
        return base_path('tests/Evals/'.class_basename($name).'EvalTest.php');
    }

    /**
     * @param  string  $name
     */
    #[\Override]
    protected function buildClass($name): string
    {
        $stub = $this->files->get($this->getStub());

        return str_replace('{{ name }}', class_basename($name), $stub);
    }

    /**
     * @return array<int, array<int, string|int>>
     */
    #[\Override]
    protected function getOptions(): array
    {
        return [
            ['force', 'f', InputOption::VALUE_NONE, 'Create the eval even if it already exists'],
        ];
    }
}
