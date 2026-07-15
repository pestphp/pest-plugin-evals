<?php

declare(strict_types=1);

namespace Pest\Evals;

use Pest\Contracts\Plugins\Bootable;
use Pest\Contracts\Plugins\HandlesArguments;
use Pest\Evals\Eval\Context;
use Pest\Plugins\Concerns\HandleArguments;
use Pest\Plugins\Parallel;
use Pest\Support\Container;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @internal
 */
final class Plugin implements Bootable, HandlesArguments
{
    use HandleArguments;

    private const string ENV_EVAL_MODE = 'PEST_EVALS';

    private static bool $evalMode = false;

    public static function isEvalMode(): bool
    {
        return self::$evalMode
            || ($_SERVER[self::ENV_EVAL_MODE] ?? $_ENV[self::ENV_EVAL_MODE] ?? null) === '1'
            || Parallel::getGlobal(self::ENV_EVAL_MODE) === true;
    }

    public static function isVerbose(): bool
    {
        $output = Container::getInstance()->get(OutputInterface::class);

        return $output instanceof OutputInterface && $output->isVerbose();
    }

    public static function resetEvalMode(): void
    {
        self::$evalMode = false;
        unset($_SERVER[self::ENV_EVAL_MODE], $_ENV[self::ENV_EVAL_MODE]);
        putenv(self::ENV_EVAL_MODE);

        $parallelKey = 'PEST_PARALLEL_GLOBAL_'.self::ENV_EVAL_MODE;
        unset($_SERVER[$parallelKey], $_ENV[$parallelKey]);
        putenv($parallelKey);
    }

    public function boot(): void
    {
        pest()->afterEach(function (): void {
            Context::reset();
        });
    }

    public function handleArguments(array $arguments): array
    {
        if (! $this->hasArgument('--evals', $arguments)) {
            return $arguments;
        }

        self::$evalMode = true;
        $_SERVER[self::ENV_EVAL_MODE] = '1';
        $_ENV[self::ENV_EVAL_MODE] = '1';
        putenv(self::ENV_EVAL_MODE.'=1');

        Parallel::setGlobal(self::ENV_EVAL_MODE, true);

        return $this->popArgument('--evals', $arguments);
    }
}
