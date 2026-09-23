<?php

declare(strict_types=1);

namespace Pest\Evals\Tests\Fixtures\Agents;

use Laravel\Ai\Attributes\UseCheapestModel;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Promptable;

#[UseCheapestModel]
final class GreetingAgent implements Agent
{
    use Promptable;

    public function instructions(): string
    {
        return 'You are a friendly greeting assistant. When a user introduces themselves, greet them warmly by name. Keep responses brief and friendly.';
    }
}
