<?php

namespace Paraunit\TestResult;

/**
 * Interface TestResultInterface
 * @package Paraunit\Output\MuteTestResult
 */
interface TestResultInterface
{
    /**
     * @return TestResultFormat
     */
    public function getTestResultFormat();
}
