<?php

declare(strict_types=1);

namespace Pest\Evals\Contracts;

use Pest\Evals\Eval\Evaluation;
use Pest\Evals\Eval\Verdict;

interface JudgeDriver
{
    public function judge(Evaluation $evaluation): Verdict;
}
