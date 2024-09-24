<?php

declare(strict_types=1);

namespace Tests\Functional\Filter;

use Paraunit\Filter\Filter;
use Tests\BaseFunctionalTestCase;

class ExcludeTest extends BaseFunctionalTestCase
{
    protected function setup(): void
    {
        $this->setOption('configuration', $this->getStubPath() . DIRECTORY_SEPARATOR . 'phpunit_with_2_testsuites.xml');
        $this->setOption('exclude-testsuite', 'suite2');

        parent::setup();
    }

    public function testExcludeTestSuites(): void
    {
        /** @var Filter $filter */
        $filter = $this->getService(Filter::class);

        $files = $filter->filterTestFiles();

        $this->assertCount(1, $files);

        $fileExploded = explode('/', $files[0]);

        $this->assertSame('ThreeGreenTestStub.php', end($fileExploded));
    }
}
