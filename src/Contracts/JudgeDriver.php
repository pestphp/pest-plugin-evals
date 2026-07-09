<?php

declare(strict_types=1);

namespace Pest\Evals\Contracts;

interface JudgeDriver
{
    public function generate(string $instructions, string $prompt): string;
}
