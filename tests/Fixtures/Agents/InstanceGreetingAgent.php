<?php

declare(strict_types=1);

namespace Pest\Evals\Tests\Fixtures\Agents;

use Illuminate\Broadcasting\Channel;
use Laravel\Ai\Approvals\Decisions;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\AgentInput;
use Laravel\Ai\Enums\Lab;
use Laravel\Ai\Messages\UserMessage;
use Laravel\Ai\Responses\AgentResponse;
use Laravel\Ai\Responses\Data\Meta;
use Laravel\Ai\Responses\Data\TextUsage;
use Laravel\Ai\Responses\QueuedAgentResponse;
use Laravel\Ai\Responses\StreamableAgentResponse;
use Pest\Evals\Tests\Fixtures\Support\ContainerGreeting;
use RuntimeException;

final readonly class InstanceGreetingAgent implements Agent
{
    public function __construct(
        private ContainerGreeting $greeting,
    ) {}

    public function instructions(): string
    {
        return "You are a greeting assistant. Always prefix your response with: {$this->greeting->prefix}";
    }

    public function prompt(AgentInput|UserMessage|Decisions|string $prompt, array $attachments = [], Lab|array|string|null $provider = null, ?string $model = null, ?int $timeout = null): AgentResponse
    {
        if (! is_string($prompt)) {
            throw new RuntimeException('Not implemented');
        }

        return new AgentResponse('test', "{$this->greeting->prefix} {$prompt}", new TextUsage(), new Meta());
    }

    public function stream(AgentInput|UserMessage|Decisions|string $prompt, array $attachments = [], Lab|array|string|null $provider = null, ?string $model = null, ?int $timeout = null): StreamableAgentResponse
    {
        throw new RuntimeException('Not implemented');
    }

    public function queue(AgentInput|UserMessage|Decisions|string $prompt, array $attachments = [], Lab|array|string|null $provider = null, ?string $model = null): QueuedAgentResponse
    {
        throw new RuntimeException('Not implemented');
    }

    public function broadcast(AgentInput|UserMessage|Decisions|string $prompt, Channel|array $channels, array $attachments = [], bool $now = false, Lab|array|string|null $provider = null, ?string $model = null): StreamableAgentResponse
    {
        throw new RuntimeException('Not implemented');
    }

    public function broadcastNow(AgentInput|UserMessage|Decisions|string $prompt, Channel|array $channels, array $attachments = [], Lab|array|string|null $provider = null, ?string $model = null): StreamableAgentResponse
    {
        throw new RuntimeException('Not implemented');
    }

    public function broadcastOnQueue(AgentInput|UserMessage|Decisions|string $prompt, Channel|array $channels, array $attachments = [], Lab|array|string|null $provider = null, ?string $model = null): QueuedAgentResponse
    {
        throw new RuntimeException('Not implemented');
    }
}
