<?php

declare(strict_types=1);

namespace Pest\Evals\Tests\Fixtures\Agents;

use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Promptable;

final class SentimentAgent implements Agent
{
    use Promptable;

    public function instructions(): string
    {
        return 'You are a sentiment analysis assistant. Analyze the sentiment of the given text and respond with ONLY one word: "positive", "negative", or "neutral". Do not include any other text.';
    }
}
