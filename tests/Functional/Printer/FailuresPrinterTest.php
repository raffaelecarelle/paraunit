<?php

declare(strict_types=1);

namespace Tests\Functional\Printer;

use Paraunit\Configuration\TempFilenameFactory;
use Paraunit\Parser\JSON\LogParser;
use Paraunit\Printer\FailuresPrinter;
use Tests\BaseFunctionalTestCase;

/**
 * Class FailuresPrinterTest
 * @package Tests\Functional\Printer
 */
class FailuresPrinterTest extends BaseFunctionalTestCase
{
    public function testOnEngineEndPrintsInTheRightOrder()
    {
        $this->processAllTheStubLogs();

        /** @var FailuresPrinter $printer */
        $printer = $this->container->get(FailuresPrinter::class);

        $printer->onEngineEnd();

        $output = $this->getConsoleOutput();

        $this->assertNotEmpty($output->getOutput());
        $this->assertNotContains('PASSED output', $output->getOutput(), '', true);
        $this->assertNotContains('SKIPPED output', $output->getOutput(), '', true);
        $this->assertNotContains('INCOMPLETE output', $output->getOutput(), '', true);
        $this->assertNotContains('files with PASSED', $output->getOutput(), '', true);
        $this->assertOutputOrder($output, [
            'Unknown',
            'Abnormal Terminations (fatal Errors, Segfaults) output:',
            'Errors output:',
            'Failures output:',
            'Warnings output:',
            'Risky Outcome output:',
        ]);
    }

    protected function getServiceToBeDeclaredPublic(): array
    {
        return [
            FailuresPrinter::class,
            LogParser::class,
            TempFilenameFactory::class,
        ];
    }
}
