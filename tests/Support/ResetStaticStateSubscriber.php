<?php

declare(strict_types=1);

namespace Tests\Support;

use CanvasLMS\Api\AbstractBaseApi;
use PHPUnit\Event\Test\Finished;
use PHPUnit\Event\Test\FinishedSubscriber;

/**
 * Clears the shared HTTP client registry after every test.
 */
final class ResetStaticStateSubscriber implements FinishedSubscriber
{
    public function notify(Finished $event): void
    {
        AbstractBaseApi::resetApiClients();
    }
}
