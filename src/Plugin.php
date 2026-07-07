<?php

declare(strict_types=1);

namespace Pest\Evals;

use Pest\Contracts\Plugins\AddsOutput;
use Pest\Contracts\Plugins\Bootable;
use Pest\Contracts\Plugins\HandlesArguments;
use Pest\Contracts\Plugins\Terminable;
use Pest\Evals\Eval\EvalExpectationContext;
use Pest\Evals\Eval\EvalReport;
use Pest\Evals\Filters\ExcludesEvalTestCaseMethodFilter;
use Pest\Plugins\Concerns\HandleArguments;
use Pest\Plugins\Parallel;
use Pest\Support\Container;
use Pest\TestSuite;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @internal
 */
final class Plugin implements AddsOutput, Bootable, HandlesArguments, Terminable
{
    use HandleArguments;

    private const string ENV_EVAL_MODE = 'PEST_EVAL_MODE';

    private const string ENV_VERBOSE = 'EVALS_VERBOSE';

    private static bool $evalMode = false;

    private static bool $verbose = false;

    public static function isEvalMode(): bool
    {
        return self::$evalMode
            || ($_SERVER[self::ENV_EVAL_MODE] ?? $_ENV[self::ENV_EVAL_MODE] ?? null) === '1'
            || Parallel::getGlobal(self::ENV_EVAL_MODE) === true;
    }

    public static function isVerbose(): bool
    {
        return self::$verbose
            || ($_SERVER[self::ENV_VERBOSE] ?? $_ENV[self::ENV_VERBOSE] ?? null) === 'true';
    }

    public static function resetEvalMode(): void
    {
        self::$evalMode = false;
        self::$verbose = false;
        unset($_SERVER[self::ENV_EVAL_MODE], $_ENV[self::ENV_EVAL_MODE]);
        putenv(self::ENV_EVAL_MODE);

        $parallelKey = 'PEST_PARALLEL_GLOBAL_'.self::ENV_EVAL_MODE;
        unset($_SERVER[$parallelKey], $_ENV[$parallelKey]);
        putenv($parallelKey);
    }

    public function boot(): void
    {
        TestSuite::getInstance()
            ->tests
            ->addTestCaseMethodFilter(new ExcludesEvalTestCaseMethodFilter());

        pest()->afterEach(function (): void {
            EvalExpectationContext::$current = null;
        });
    }

    public function handleArguments(array $arguments): array
    {
        if (! $this->hasArgument('--eval', $arguments)) {
            return $arguments;
        }

        self::$evalMode = true;
        $_SERVER[self::ENV_EVAL_MODE] = '1';
        $_ENV[self::ENV_EVAL_MODE] = '1';
        putenv(self::ENV_EVAL_MODE.'=1');

        Parallel::setGlobal(self::ENV_EVAL_MODE, true);

        $filtered = $this->popArgument('--eval', $arguments);

        if ($this->hasArgument('--evals-verbose', $filtered)) {
            self::$verbose = true;
            $filtered = $this->popArgument('--evals-verbose', $filtered);
        }

        if ($this->shouldTargetEvalDirectory($filtered)) {
            return $this->pushArgument('tests/Evals', $filtered);
        }

        if (! $this->hasArgument('--group', $filtered)) {
            return $this->pushArgument('--group=eval', $filtered);
        }

        return $filtered;
    }

    public function addOutput(int $exitCode): int
    {
        if (self::isEvalMode()) {
            $report = EvalReport::instance();

            $report->mergeWorkerFiles();

            if ($report->totalEvals() > 0) {
                /** @var OutputInterface $output */
                $output = Container::getInstance()->get(OutputInterface::class);
                $output->writeln($report->renderSummary());
            }

            EvalReport::flush();
            self::resetEvalMode();
        }

        return $exitCode;
    }

    public function terminate(): void
    {
        if (Parallel::isWorker()) {
            EvalReport::instance()->flushToFile();
        }
    }

    /**
     * @param  array<int, string>  $arguments
     */
    private function shouldTargetEvalDirectory(array $arguments): bool
    {
        return is_dir('tests/Evals')
            && ! $this->hasPathArgument($arguments)
            && ! $this->hasArgument('--group', $arguments);
    }

    /**
     * @param  array<int, string>  $arguments
     */
    private function hasPathArgument(array $arguments): bool
    {
        foreach (array_slice($arguments, 1) as $argument) {
            if ($argument !== '' && ! str_starts_with($argument, '-')) {
                return true;
            }
        }

        return false;
    }
}
