<?php

declare(strict_types=1);

namespace Pest\Evals\Support;

use Pest\Evals\Configuration;

/**
 * @internal
 */
final readonly class Judge
{
    private function __construct(
        private string $scorer,
        private string $instructions,
    ) {}

    public static function using(string $scorer): self
    {
        return new self($scorer, '');
    }

    public function instructions(string $instructions): self
    {
        return new self($this->scorer, $instructions);
    }

    public function prompt(string $prompt): JudgeResponse
    {
        $raw = Configuration::resolvedJudge()->generate($this->instructions, $prompt);

        return new JudgeResponse($this->scorer, $raw);
    }
}
