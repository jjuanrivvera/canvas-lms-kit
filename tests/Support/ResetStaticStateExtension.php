<?php

declare(strict_types=1);

namespace Tests\Support;

use PHPUnit\Runner\Extension\Extension;
use PHPUnit\Runner\Extension\Facade;
use PHPUnit\Runner\Extension\ParameterCollection;
use PHPUnit\TextUI\Configuration\Configuration;

/**
 * Registers the subscriber that clears SDK static state between tests.
 *
 * API classes share a process-global HTTP client registry; without this,
 * a mock set in one test leaks into every later test in the run, making
 * failures depend on test ordering.
 */
final class ResetStaticStateExtension implements Extension
{
    public function bootstrap(Configuration $configuration, Facade $facade, ParameterCollection $parameters): void
    {
        $facade->registerSubscriber(new ResetStaticStateSubscriber());
    }
}
