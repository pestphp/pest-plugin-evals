<?php

declare(strict_types=1);

namespace Pest\Evals\Tests\Fixtures\Agents;

use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Promptable;

final class RefundPolicyAgent implements Agent
{
    use Promptable;

    public function instructions(): string
    {
        return <<<'INSTRUCTIONS'
        You are a customer support agent for an e-commerce store. Answer questions about the refund policy.

        The refund policy is:
        - Full refunds are available within 30 days of purchase.
        - Items must be in original condition with tags attached.
        - Digital products are non-refundable.
        - Shipping costs are non-refundable.
        - Refunds are processed within 5-7 business days.

        Be helpful, concise, and accurate. Only reference the policy above.
        INSTRUCTIONS;
    }
}
