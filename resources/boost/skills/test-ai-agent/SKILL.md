---
name: test-ai-agent
description: "Use when writing or evaluating Pest tests for Laravel AI agents (`Laravel\\Ai\\Contracts\\Agent`): evals in `tests/Evals`, `expect(...)->prompt(...)`, model-response assertions, judge or embedding scorers, tool-call or trajectory checks, custom scorers, or eval providers. Trigger for evals, `--evals`, scorers, or testing an agent's answer; exclude ordinary tests that never call a model."
license: MIT
metadata:
  author: pestphp
---

# Testing Laravel AI Agents

Use `pestphp/pest-plugin-evals` to measure an agent response. An eval is a Pest test in `tests/Evals`: `expect()` accepts an agent class or instance, `->prompt()` runs the agent, and the following expectations grade that response. Class names resolve through the container, so constructor injection works. Pass attachments as `prompt()`'s second argument.

## Build the eval

1. Inspect the agent's instructions, tools, and existing test style. Put the case in `tests/Evals`, group one agent under a `describe()`, and use the project's established `it()` or `test()` form.
2. Write realistic user input for one behaviour, including the failure modes that matter: missing facts, off-topic answers, prompt injection, or unsafe advice.
3. Prompt the agent and choose the cheapest expectation that proves the behaviour. Add `->repeat(3)` through `->repeat(5)` immediately after `->prompt()` when reliability, rather than a single answer, is the property under test.
4. Configure or guard external scoring where needed, then run the eval with its gate enabled.

The eval is complete when its prompt represents the intended user interaction, its assertion directly measures the behaviour, and every required sample passes.

```php
it('answers account questions from the supplied context', function () {
    expect(AccountAgent::class)
        ->prompt('What is the cancellation window?')
        ->repeat(3)
        ->toBeCorrect(expected: 'Customers can cancel within 30 days.');
});
```

`->prompt()` is stateless; another call starts a fresh agent run. With `repeat()`, every later expectation must hold for every sample; `->not->` means no sample may match.

## Choose the expectation

Start deterministic, then use model-assisted scoring only for a property deterministic assertions cannot express.

| Behaviour | Expectation | Scoring calls |
|---|---|---|
| Known text, regex, exact value, or JSON | `toContain`, `toMatch`, `toBe`, `toBeJson` | None |
| Required tool calls; optional arguments use subset matching | `toHaveToolCalls([...])` | None |
| Tool calls in a required order | `toFollowTrajectory([...], strictOrder: bool)` | None |
| Same meaning with variable wording | `toBeSimilar('reference')` | Embeddings |
| Factual consistency with a reference | `toBeCorrect(expected: '...')` | Judge |
| An observable quality described in prose | `toSatisfy('criteria...')` | Judge |
| Topicality or safety | `toBeRelevant()`, `toBeSafe()` | Judge |
| A domain-specific rule | `toPassScorer(new YourScorer)` | Scorer-defined |

Scorers return `0.0`–`1.0`; their default threshold is `0.7`. Set a stricter threshold for safety-critical behaviour and a lower one for acceptable partial answers. `toBeCorrect()` maps classifications to fixed scores (a superset is `0.8`; a subset is `0.6`), so tune its threshold when that boundary is the requirement.

Give `toSatisfy()` a reviewer-ready criterion: name the observable property and its failure boundary. For example: “Answers in two sentences, cites the supplied policy, and contains no joke.”

## Run and configure evals

Evals are skipped until explicitly enabled because they can incur cost and vary between runs. Use one of:

```sh
php artisan test --evals
vendor/bin/pest --evals -v
PEST_EVALS=1 vendor/bin/pest
```

`-v` shows inputs, outputs, reasoning, and scores. Guard `tests/Evals` with a `beforeEach` that skips under eval mode when required provider credentials are unavailable; this keeps an intentional `--evals` run informative in local and CI environments.

Laravel AI provides judging and embeddings. OpenAI is the default when `OPENAI_API_KEY` is set. Configure providers and models per environment with:

- `PEST_EVALS_LARAVEL_SCORING_PROVIDER` and `PEST_EVALS_LARAVEL_SCORING_MODEL`
- `PEST_EVALS_LARAVEL_EMBEDDING_PROVIDER` and `PEST_EVALS_LARAVEL_EMBEDDING_MODEL`

Or configure them in `tests/Pest.php`:

```php
pest()->evals()
    ->judgeUsing(new LaravelAiJudge(provider: 'openai', model: '...'))
    ->embeddingsUsing(new LaravelAiEmbeddings(provider: 'openai', model: '...'));
```

Use a fast, economical judge model appropriate to grading. For no-spend CI smoke coverage, `judgeUsing()` may receive a closure returning `['score' => 1.0, 'reasoning' => 'stubbed']`; it replaces judging while the eval gate remains in effect.

## Custom scorers

Implement `Pest\\Evals\\Scorers\\Scorer::score(string $input, string $output, ?string $expected): ScorerResult`. Mark a scorer that calls a model with `RequiresJudge`, one that creates embeddings with `RequiresEmbeddings`, and one that does both with both markers. These markers keep API-backed scoring behind the eval gate; deterministic scorers remain available in ordinary test runs.

## API lookup

Use `search-docs` for exact signatures and complete examples. Search these headings without the package name: `Evals`, `Writing Your First Eval`, `Prompting`, `Deterministic Expectations`, `Sampling`, `AI-Powered Scorers`, `Custom Scorers`, `Drivers: Laravel AI`, `Running Evals`, and `Verbose Output`.
