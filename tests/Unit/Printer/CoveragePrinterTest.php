<?php

namespace Tests\Unit\Printer;

use Paraunit\Configuration\PHPDbgBinFile;
use Paraunit\Lifecycle\EngineEvent;
use Paraunit\Printer\CoveragePrinter;
use Paraunit\Proxy\XDebugProxy;
use Tests\BaseUnitTestCase;
use Tests\Stub\UnformattedOutputStub;

/**
 * Class CoveragePrinterTest
 * @package Tests\Unit\Printer
 */
class CoveragePrinterTest extends BaseUnitTestCase
{
    public function testOnEngineBeforeStartWithPHPDBGEngine()
    {
        $output = new UnformattedOutputStub();
        $engineEvent = new EngineEvent($output);

        $printer = new CoveragePrinter($this->mockPhpdbgBin(true), $this->mockXdebugLoaded(false));

        $printer->onEngineBeforeStart($engineEvent);

        $this->assertContains('Coverage driver in use: PHPDBG', $output->getOutput());
    }

    public function testOnEngineBeforeStartWithxDebugEngine()
    {
        $output = new UnformattedOutputStub();
        $engineEvent = new EngineEvent($output);

        $printer = new CoveragePrinter($this->mockPhpdbgBin(false), $this->mockXdebugLoaded(true));

        $printer->onEngineBeforeStart($engineEvent);

        $this->assertContains('Coverage driver in use: xDebug', $output->getOutput());
    }

    public function testOnEngineBeforeStartWithWarningForBothEnginesEnabled()
    {
        $output = new UnformattedOutputStub();
        $engineEvent = new EngineEvent($output);

        $printer = new CoveragePrinter($this->mockPhpdbgBin(true), $this->mockXdebugLoaded(true));

        $printer->onEngineBeforeStart($engineEvent);

        $this->assertContains('WARNING', $output->getOutput());
        $this->assertContains('both driver', $output->getOutput());
        $this->assertNotContains('xDebug', $output->getOutput());
    }

    private function mockPhpdbgBin(bool $shouldReturn): PHPDbgBinFile
    {
        $phpdbgBin = $this->prophesize(PHPDbgBinFile::class);
        $phpdbgBin->isAvailable()
            ->willReturn($shouldReturn);

        return $phpdbgBin->reveal();
    }

    private function mockXdebugLoaded(bool $shouldReturn): XDebugProxy
    {
        $xdebug = $this->prophesize(XDebugProxy::class);
        $xdebug->isLoaded()
            ->willReturn($shouldReturn);

        return $xdebug->reveal();
    }
}
