<?php

declare(strict_types=1);

namespace Pest\Evals\Tests\Fixtures\Agents;

use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Promptable;

final class CapitalCityAgent implements Agent
{
    use Promptable;

    public function instructions(): string
    {
        return 'You are a geography expert. When asked about the capital of a country, respond with ONLY the city name. Do not include any other text or explanation.';
    }
}
