<?php

declare(strict_types=1);

namespace Pest\Evals\Filters;

use Pest\Contracts\TestCaseMethodFilter;
use Pest\Factories\TestCaseMethodFactory;
use Pest\Evals\Plugin;

/**
 * @internal
 */
final readonly class ExcludesEvalTestCaseMethodFilter implements TestCaseMethodFilter
{
    public function accept(TestCaseMethodFactory $factory): bool
    {
        if (Plugin::isEvalMode()) {
            return true;
        }

        return ! str_contains($factory->filename, DIRECTORY_SEPARATOR.'Evals'.DIRECTORY_SEPARATOR);
    }
}
