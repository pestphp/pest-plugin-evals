<?php

declare(strict_types=1);

namespace Pest\Evals\Tests;

use Dotenv\Dotenv;
use Laravel\Ai\AiServiceProvider;
use Orchestra\Testbench\TestCase as BaseTestCase;
use Override;

abstract class TestCase extends BaseTestCase
{
    #[Override]
    protected function setUp(): void
    {
        $this->loadPackageEnvironment();

        parent::setUp();
    }

    /**
     * @return array<int, class-string>
     */
    #[Override]
    protected function getPackageProviders($app): array
    {
        return [
            AiServiceProvider::class,
        ];
    }

    private function loadPackageEnvironment(): void
    {
        $envFile = dirname(__DIR__).'/.env';

        if (file_exists($envFile)) {
            Dotenv::createImmutable(dirname(__DIR__))->safeLoad();
        }
    }
}
