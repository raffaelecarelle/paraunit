<?php

declare(strict_types=1);

namespace Tests\Functional\Printer;

use Paraunit\Configuration\TempFilenameFactory;
use Paraunit\Parser\JSON\LogParser;
use Paraunit\Printer\FilesRecapPrinter;
use Paraunit\Process\AbstractParaunitProcess;
use Paraunit\TestResult\TestResultContainer;
use Tests\BaseFunctionalTestCase;
use Tests\Stub\StubbedParaunitProcess;

/**
 * Class FilesRecapPrinterTest
 * @package Tests\Functional\Printer
 */
class FilesRecapPrinterTest extends BaseFunctionalTestCase
{
    public function testOnEngineEndPrintsInTheRightOrder()
    {
        $process = new StubbedParaunitProcess();

        $this->processAllTheStubLogs();
        $this->addProcessToContainers($process);

        /** @var FilesRecapPrinter $printer */
        $printer = $this->container->get(FilesRecapPrinter::class);

        $printer->onEngineEnd();

        $output = $this->getConsoleOutput();

        $this->assertNotEmpty($output->getOutput());
        $this->assertNotContains('PASSED output', $output->getOutput(), '', true);
        $this->assertNotContains('SKIPPED output', $output->getOutput(), '', true);
        $this->assertNotContains('INCOMPLETE output', $output->getOutput(), '', true);
        $this->assertNotContains('files with PASSED', $output->getOutput(), '', true);
        $this->assertOutputOrder($output, [
            'files with UNKNOWN',
            'files with COVERAGE NOT FETCHED',
            'files with ERRORS',
            'files with FAILURES',
            'files with WARNING',
            'files with NO TESTS EXECUTED',
            'files with RISKY',
            'files with SKIP',
            'files with INCOMPLETE',
        ]);
    }

    private function addProcessToContainers(AbstractParaunitProcess $process)
    {
        /** @var TestResultContainer $noTestExecuted */
        $noTestExecuted = $this->container->get('paraunit.test_result.no_test_executed_container');
        $noTestExecuted->addProcessToFilenames($process);

        /** @var TestResultContainer $coverageFailure */
        $coverageFailure = $this->container->get('paraunit.test_result.coverage_failure_container');
        $coverageFailure->addProcessToFilenames($process);
    }

    protected function getServiceToBeDeclaredPublic(): array
    {
        return [
            FilesRecapPrinter::class,
            LogParser::class,
            TempFilenameFactory::class,
            'paraunit.test_result.no_test_executed_container',
            'paraunit.test_result.coverage_failure_container',
        ];
    }
}
