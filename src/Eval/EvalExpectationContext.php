<?php

declare(strict_types=1);

namespace Pest\Evals\Eval;

use Closure;
use Illuminate\Container\Container;
use Laravel\Ai\Contracts\Agent;
use RuntimeException;

final class EvalExpectationContext
{
    public static ?self $current = null;

    /**
     * @var (Closure(string): string)|null
     */
    private ?Closure $resolvedTask = null;

    /** @var list<string>|null */
    private ?array $sampleOutputs = null;

    /**
     * @param  list<string>  $fakedResponses
     * @param  list<mixed>  $attachments
     */
    public function __construct(
        public readonly string $prompt,
        public readonly string $agentName,
        public readonly array $fakedResponses = [],
        public readonly array $attachments = [],
    ) {}

    public static function currentPrompt(): string
    {
        return self::$current instanceof self ? self::$current->prompt : '';
    }

    public static function currentAgentName(): string
    {
        return self::$current instanceof self ? self::$current->agentName : 'Direct';
    }

    /**
     * @return list<string>
     */
    public function resolveOutputs(string|Closure|Agent $agent): array
    {
        $this->resolvedTask = $this->resolveTask($agent);

        return [($this->resolvedTask)($this->prompt)];
    }

    /**
     * @return list<string>
     */
    public function resolveAdditionalOutputs(int $count): array
    {
        if (! $this->resolvedTask instanceof Closure) {
            throw new RuntimeException('resolveOutputs() must be called before resolveAdditionalOutputs().');
        }

        $task = $this->resolvedTask;
        $outputs = [];

        for ($i = 0; $i < $count; $i++) {
            $outputs[] = $task($this->prompt);
        }

        return $outputs;
    }

    /**
     * @param  list<string>  $outputs
     */
    public function setSampleOutputs(array $outputs): void
    {
        $this->sampleOutputs = $outputs;
    }

    /**
     * @return list<string>|null
     */
    public function getSampleOutputs(): ?array
    {
        return $this->sampleOutputs;
    }

    /**
     * @return Closure(string): string
     */
    private function resolveTask(string|Closure|Agent $agent): Closure
    {
        if ($agent instanceof Closure) {
            return $agent;
        }

        if ($this->fakedResponses !== []) {
            $responses = $this->fakedResponses;
            $index = 0;

            return function (string $input) use ($responses, &$index): string {
                $response = $responses[$index] ?? $responses[array_key_last($responses)];
                $index++;

                return $response;
            };
        }

        if ($agent instanceof Agent) {
            $attachments = $this->attachments;

            return fn (string $input): string => (string) $agent->prompt($input, $attachments);
        }

        $attachments = $this->attachments;

        return function (string $input) use ($agent, $attachments): string {
            $instance = match (true) {
                class_exists(Container::class) => Container::getInstance()->make($agent),
                default => new $agent(),
            };

            return (string) $instance->prompt($input, $attachments); // @phpstan-ignore method.nonObject, cast.string
        };
    }
}
