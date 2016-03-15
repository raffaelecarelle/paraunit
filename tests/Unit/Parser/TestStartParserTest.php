<?php

namespace Tests\Unit\Parser;


use Paraunit\Parser\JSONLogFetcher;
use Paraunit\Parser\TestStartParser;
use Tests\BaseUnitTestCase;
use Tests\Stub\StubbedParaunitProcess;

/**
 * Class TestStartParserTest
 * @package Unit\Parser
 */
class TestStartParserTest extends BaseUnitTestCase
{
    /**
     * @dataProvider logsProvider
     */
    public function testHandleLogItem($status, $chainInterrupted, $processExpectsTestResult = false)
    {
        $process = new StubbedParaunitProcess();
        $parser = new TestStartParser();
        $log = new \stdClass();
        $log->status = $status;
        $log->test = 'testFunction';

        $return = $parser->handleLogItem($process, $log);

        if ($chainInterrupted) {
            $this->assertInstanceOf('Paraunit\TestResult\TestResultInterface', $return);
        } else {
            $this->assertNull($return);
        }

        if ($processExpectsTestResult) {
            $this->assertTrue($process->isWaitingForTestResult());
        }
    }

    public function logsProvider()
    {
        return array(
            array('testStart', true, true),
            array('suiteStart', true, true),
            array('test', false),
            array('aaaa', false),
            array(JSONLogFetcher::LOG_ENDING_STATUS, false, false),
        );
    }

    public function testHandleLogItemAppendsNoCulpableFunctionForMissingLog()
    {
        $process = new StubbedParaunitProcess();
        $parser = new TestStartParser();
        $log = new \stdClass();
        $log->status = JSONLogFetcher::LOG_ENDING_STATUS;

        $return = $parser->handleLogItem($process, $log);

        $this->assertNull($return);
        $this->assertEquals('UNKNOWN -- log not found', $log->test);
    }

    /**
     * @dataProvider logsProvider
     */
    public function testHandleLogItemAppendsCulpableFunction($status, $chainInterrupted)
    {
        $process = new StubbedParaunitProcess();
        $parser = new TestStartParser();
        $log = new \stdClass();
        $log->status = $status;
        $log->test = 'testFunction';

        $parser->handleLogItem($process, $log);

        if ($chainInterrupted) {
            $log->status = JSONLogFetcher::LOG_ENDING_STATUS;
            $return = $parser->handleLogItem($process, $log);

            $this->assertNull($return);
            $this->assertEquals('testFunction', $log->test);
        }
    }

    /**
     * @dataProvider logsProvider
     */
    public function testHandleLogItemAppendsCulpableFunctionToRightProcess($status, $chainInterrupted)
    {
        $process = new StubbedParaunitProcess();
        $parser = new TestStartParser();
        $log = new \stdClass();
        $log->status = $status;
        $log->test = 'testFunction';

        $parser->handleLogItem(new StubbedParaunitProcess(), $log);

        $log->test = 'culpableFunction';
        $parser->handleLogItem($process, $log);

        if ($chainInterrupted) {
            $log->status = JSONLogFetcher::LOG_ENDING_STATUS;
            $return = $parser->handleLogItem($process, $log);

            $this->assertNull($return);
            $this->assertEquals('culpableFunction', $log->test);
        }
    }
}
