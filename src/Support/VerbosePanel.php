<?php

declare(strict_types=1);

namespace Pest\Evals\Support;

use Pest\Support\Container;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @internal
 */
final class VerbosePanel
{
    public function render(
        string $scorer,
        float $threshold,
        bool $passed,
        string $input,
        string $output,
        string $reasoning,
        float $score,
    ): void {
        /** @var OutputInterface $console */
        $console = Container::getInstance()->get(OutputInterface::class);

        $icon = $passed ? '<fg=green>✓ PASS</>' : '<fg=red>✗ FAIL</>';
        $percentage = number_format($score * 100);

        $lines = [
            '',
            '  <fg=gray>'.str_repeat('─', 60).'</>',
            "  Assertion: <fg=white>{$scorer}</> (threshold: {$threshold})",
            '',
            "  {$icon}",
            '',
            '  <fg=gray>Input:</>',
            '  <fg=white>"'.mb_strimwidth($input, 0, 120, '...').'"</>',
            '',
            '  <fg=gray>Output:</>',
            '  <fg=white>"'.mb_strimwidth($output, 0, 200, '...').'"</>',
            '',
            '  <fg=gray>Reasoning:</>',
            '  <fg=white>"'.$reasoning.'"</>',
            '',
            "  Score: <fg=white>{$percentage} / 100</>",
            '  <fg=gray>'.str_repeat('─', 60).'</>',
        ];

        foreach ($lines as $line) {
            $console->writeln($line);
        }
    }
}
