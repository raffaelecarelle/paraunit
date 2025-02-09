<?php

declare(strict_types=1);

namespace Tests\Unit\Logs\TestHook;

use Paraunit\Logs\TestHook\PhpDeprecation;
use Paraunit\Logs\ValueObject\LogStatus;
use PHPUnit\Event\Code\IssueTrigger\IssueTrigger;
use PHPUnit\Event\Test\PhpDeprecationTriggered;

/**
 * @template-extends AbstractTestHookTestCase<PhpDeprecation, PhpDeprecationTriggered>
 */
class PhpDeprecationTest extends AbstractTestHookTestCase
{
    protected function createSubscriber(): PhpDeprecation
    {
        return new PhpDeprecation();
    }

    protected function getExpectedStatus(): LogStatus
    {
        return LogStatus::Deprecation;
    }

    protected function createEvent(): PhpDeprecationTriggered
    {
        $args = [
            $this->createTelemetryInfo(),
            $this->createPHPUnitTestMethod(),
            $this->getExpectedMessage(),
            'testFile.php',
            123,
            false,
            false,
            false,
        ];

        if (class_exists(IssueTrigger::class)) {
            $args[] = IssueTrigger::unknown();
        }

        return new PhpDeprecationTriggered(...$args);
    }

    /**
     * @return non-empty-string
     */
    protected function getExpectedMessage(): string
    {
        return 'test message';
    }
}
