<?php

namespace Tests\Unit\Configuration;

use Paraunit\Configuration\CoverageConfiguration;
use Paraunit\Coverage\Processor\Clover;
use Paraunit\Coverage\Processor\Crap4j;
use Paraunit\Coverage\Processor\Html;
use Paraunit\Coverage\Processor\Php;
use Paraunit\Coverage\Processor\Text;
use Paraunit\Coverage\Processor\TextToConsole;
use Paraunit\Coverage\Processor\Xml;
use Prophecy\Argument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Tests\BaseUnitTestCase;

/**
 * Class ParaunitCoverageTest
 * @package Tests\Unit\Configuration
 */
class CoverageConfigurationTest extends BaseUnitTestCase
{
    public function testBuildContainer()
    {
        $paraunit = new CoverageConfiguration();
        $input = $this->prophesize(InputInterface::class);
        $input->getArgument('stringFilter')
            ->willReturn('text');
        $input->getOption('parallel')
            ->willReturn(10);
        $input->getOption('testsuite')
            ->willReturn('testsuite');
        $input->getOption('configuration')
            ->willReturn($this->getConfigForStubs());
        $input->getOption(Argument::cetera())
            ->willReturn(null);

        $container = $paraunit->buildContainer($input->reveal());

        $this->assertInstanceOf(ContainerBuilder::class, $container);

        $requiredParameters = [
            'paraunit.max_process_count' => 10,
            'paraunit.testsuite' => 'testsuite',
            'paraunit.string_filter' => 'text',
            'paraunit.phpunit_config_filename' => $this->getConfigForStubs(),
        ];

        foreach ($requiredParameters as $parameterName => $expectedValue) {
            $this->assertTrue($container->hasParameter($parameterName), 'Parameter missing: ' . $parameterName);
            $this->assertEquals($expectedValue, $container->getParameter($parameterName));
        }

        $requiredDefinitions = [
            'paraunit.parser.json_log_parser',
            'paraunit.printer.process_printer',
            'paraunit.process.process_factory',
            'paraunit.runner.runner',
            'event_dispatcher',
            'paraunit.test_result.test_result_factory',
            'paraunit.test_result.pass_container',
            'paraunit.test_result.pass_test_result_format',

            'paraunit.coverage.coverage_fetcher',
            'paraunit.coverage.coverage_merger',
            'paraunit.coverage.coverage_result',
            'paraunit.configuration.phpdbg_bin_file',
            'paraunit.printer.coverage_printer',
            'paraunit.configuration.phpunit_config',
        ];

        $servicesIds = $container->getServiceIds();

        foreach ($requiredDefinitions as $definition) {
            $this->assertContains($definition, $servicesIds);
            $container->get($definition); // test instantiation, to prevent misconfigurations
        }

        $this->markTestIncomplete('Awaiting #29 -- paraunit.printer.debug_printer');
    }

    /**
     * @dataProvider cliOptionsProvider
     */
    public function testBuildContainerWithCoverageSettings(string $inputOption, string $processorClass)
    {
        $paraunit = new CoverageConfiguration();
        $input = $this->prophesize(InputInterface::class);
        $options = array(
            'testsuite',
            'configuration',
            'clover',
            'xml',
            'html',
            'text',
            'text-to-console',
            'crap4j',
            'php',
            'ansi',
        );

        foreach ($options as $optionName) {
            $input->getOption($optionName)
                ->willReturn($optionName === $inputOption ? 'someValue' : null);
        }

        $input->getArgument('stringFilter')
            ->willReturn();
        $input->getOption('parallel')
            ->shouldBeCalled()
            ->willReturn(10);

        $container = $paraunit->buildContainer($input->reveal());

        $this->assertInstanceOf(ContainerBuilder::class, $container);

        $coverageResult = $container->get('paraunit.coverage.coverage_result');
        $reflection = new \ReflectionObject($coverageResult);
        $property = $reflection->getProperty('coverageProcessors');
        $property->setAccessible(true);
        $processors = $property->getValue($coverageResult);

        $this->assertCount(1, $processors, 'Wrong count of coverage processors');
        $this->assertInstanceOf($processorClass, $processors[0]);
    }

    public function cliOptionsProvider(): array
    {
        return [
            ['clover', Clover::class],
            ['xml', Xml::class],
            ['html', Html::class],
            ['text', Text::class],
            ['text-to-console', TextToConsole::class],
            ['crap4j', Crap4j::class],
            ['php', Php::class],
        ];
    }

    public function testBuildContainerWithColoredTextToConsoleCoverage()
    {
        $paraunit = new CoverageConfiguration();
        $input = $this->prophesize(InputInterface::class);
        $options = [
            'testsuite',
            'configuration',
            'clover',
            'xml',
            'html',
            'text',
            'crap4j',
            'php',
        ];

        foreach ($options as $optionName) {
            $input->getOption($optionName)
                ->willReturn(null);
        }

        $input->getArgument('stringFilter')
            ->willReturn();
        $input->getOption('parallel')
            ->shouldBeCalled()
            ->willReturn(10);
        $input->getOption('text-to-console')
            ->shouldBeCalled()
            ->willReturn(true);
        $input->getOption('ansi')
            ->shouldBeCalled()
            ->willReturn(true);

        $container = $paraunit->buildContainer($input->reveal());

        $this->assertInstanceOf(ContainerBuilder::class, $container);

        $coverageResult = $container->get('paraunit.coverage.coverage_result');
        $reflection = new \ReflectionObject($coverageResult);
        $property = $reflection->getProperty('coverageProcessors');
        $property->setAccessible(true);
        $processors = $property->getValue($coverageResult);

        $this->assertCount(1, $processors, 'Wrong count of coverage processors');
        $processor = $processors[0];
        $this->assertInstanceOf(TextToConsole::class, $processor);

        $reflection = new \ReflectionObject($processor);
        $property = $reflection->getProperty('showColors');
        $property->setAccessible(true);
        $this->assertTrue($property->getValue($processor));
    }
}
