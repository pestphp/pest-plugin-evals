<?php

declare(strict_types=1);

namespace Pest\Evals\Eval;

use Closure;
use Illuminate\Container\Container;
use Laravel\Ai\Contracts\Agent;
use Pest\Evals\Exceptions\EvalExpectationException;
use Pest\Expectation;
use WeakMap;

/**
 * @internal
 */
final class EvalExpectationContext
{
    public static ?self $current = null;

    /**
     * @var WeakMap<object, self>|null
     */
    private static ?WeakMap $contexts = null;

    /**
     * @var (Closure(string): string)|null
     */
    private ?Closure $resolvedTask = null;

    /** @var array<int, string>|null */
    private ?array $sampleOutputs = null;

    /**
     * @param  array<int, mixed>  $attachments
     */
    public function __construct(
        public readonly string $prompt,
        public readonly string $agentName,
        public readonly array $attachments = [],
    ) {}

    /**
     * @template TValue
     *
     * @param  Expectation<TValue>  $expectation
     */
    public static function bind(Expectation $expectation, self $context): void
    {
        self::$contexts ??= new WeakMap;
        self::$contexts[$expectation] = $context;

        self::$current = $context;
    }

    /**
     * @template TValue
     *
     * @param  Expectation<TValue>  $expectation
     */
    public static function for(Expectation $expectation): ?self
    {
        return self::$contexts[$expectation] ?? null;
    }

    public static function reset(): void
    {
        self::$current = null;
        self::$contexts = null;
    }

    /**
     * @return array<int, string>
     */
    public function resolveOutputs(string|Closure|Agent $agent): array
    {
        $this->resolvedTask = $this->resolveTask($agent);

        return [($this->resolvedTask)($this->prompt)];
    }

    /**
     * @return array<int, string>
     */
    public function resolveAdditionalOutputs(int $count): array
    {
        if (! $this->resolvedTask instanceof Closure) {
            throw EvalExpectationException::promptNotCalled();
        }

        $task = $this->resolvedTask;
        $outputs = [];

        for ($i = 0; $i < $count; $i++) {
            $outputs[] = $task($this->prompt);
        }

        return $outputs;
    }

    /**
     * @param  array<int, string>  $outputs
     */
    public function setSampleOutputs(array $outputs): void
    {
        $this->sampleOutputs = $outputs;
    }

    /**
     * @return array<int, string>|null
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
