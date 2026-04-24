<?php

declare(strict_types=1);

/**
 * Fast Forward Development Tools for PHP projects.
 *
 * This file is part of fast-forward/dev-tools project.
 *
 * @author   Felipe Sayão Lobato Abreu <github@mentordosnerds.com>
 * @license  https://opensource.org/licenses/MIT MIT License
 *
 * @see      https://github.com/php-fast-forward/
 * @see      https://github.com/php-fast-forward/dev-tools
 * @see      https://github.com/php-fast-forward/dev-tools/issues
 * @see      https://php-fast-forward.github.io/dev-tools/
 * @see      https://datatracker.ietf.org/doc/html/rfc2119
 */

namespace FastForward\DevTools\Tests\Process;

use FastForward\DevTools\Environment\EnvironmentInterface;
use FastForward\DevTools\Php\ExtensionInterface;
use FastForward\DevTools\Process\XdebugDisablingProcessEnvironmentConfigurator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

#[CoversClass(XdebugDisablingProcessEnvironmentConfigurator::class)]
final class XdebugDisablingProcessEnvironmentConfiguratorTest extends TestCase
{
    use ProphecyTrait;

    private XdebugDisablingProcessEnvironmentConfigurator $configurator;

    /**
     * @var ObjectProphecy<EnvironmentInterface>
     */
    private ObjectProphecy $environment;

    /**
     * @var ObjectProphecy<ExtensionInterface>
     */
    private ObjectProphecy $extension;

    /**
     * @var ObjectProphecy<OutputInterface>
     */
    private ObjectProphecy $output;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->environment = $this->prophesize(EnvironmentInterface::class);
        $this->environment->get(Argument::type('string'), Argument::cetera())
            ->willReturn(null);
        $this->extension = $this->prophesize(ExtensionInterface::class);
        $this->extension->isLoaded('xdebug')
            ->willReturn(true);
        $this->extension->isLoaded('pcov')
            ->willReturn(false);
        $this->output = $this->prophesize(OutputInterface::class);
        $this->configurator = new XdebugDisablingProcessEnvironmentConfigurator(
            $this->environment->reveal(),
            $this->extension->reveal()
        );
    }

    /**
     * @return void
     */
    #[Test]
    public function configureDisablesXdebugForNonCoverageProcess(): void
    {
        $process = $this->createProcessMock(
            commandLine: "'composer' 'normalize'",
            env: [
                'EXISTING_ENV' => 'kept',
            ]
        );
        $process->setEnv(Argument::that(static fn(array $env): bool => 'kept' === $env['EXISTING_ENV']
            && 'off' === $env['XDEBUG_MODE']))
            ->willReturn($process->reveal())
            ->shouldBeCalledOnce();

        $this->configurator->configure($process->reveal(), $this->output->reveal());
    }

    /**
     * @return void
     */
    #[Test]
    public function configureDoesNotOverrideProcessXdebugMode(): void
    {
        $process = $this->createProcessMock(
            commandLine: "'composer' 'normalize'",
            env: [
                'XDEBUG_MODE' => 'debug',
            ]
        );
        $process->setEnv(Argument::any())
            ->shouldNotBeCalled();

        $this->configurator->configure($process->reveal(), $this->output->reveal());
    }

    /**
     * @return void
     */
    #[Test]
    public function configureDoesNotDisableXdebugWhenParentAllowsComposerXdebug(): void
    {
        $this->environment->get('COMPOSER_ALLOW_XDEBUG', '')
            ->willReturn('1');
        $process = $this->createProcessMock(commandLine: "'composer' 'normalize'");
        $process->getEnv()
            ->shouldNotBeCalled();
        $process->setEnv(Argument::any())
            ->shouldNotBeCalled();

        $this->configurator->configure($process->reveal(), $this->output->reveal());
    }

    /**
     * @return void
     */
    #[Test]
    public function configureDoesNotDisableXdebugWhenParentAlreadyConfiguredXdebugMode(): void
    {
        $this->environment->get('XDEBUG_MODE')
            ->willReturn('debug');
        $process = $this->createProcessMock(commandLine: "'composer' 'normalize'");
        $process->getEnv()
            ->shouldNotBeCalled();
        $process->setEnv(Argument::any())
            ->shouldNotBeCalled();

        $this->configurator->configure($process->reveal(), $this->output->reveal());
    }

    /**
     * @return void
     */
    #[Test]
    public function configureDoesNotDisableXdebugForCoverageWhenPcovIsUnavailable(): void
    {
        $process = $this->createProcessMock(
            commandLine: "'composer' 'dev-tools' 'tests' '--' '--coverage' '.dev-tools/coverage'"
        );
        $process->getEnv()
            ->shouldNotBeCalled();
        $process->setEnv(Argument::any())
            ->shouldNotBeCalled();

        $this->configurator->configure($process->reveal(), $this->output->reveal());
    }

    /**
     * @return void
     */
    #[Test]
    public function configureDisablesXdebugForCoverageWhenPcovIsAvailable(): void
    {
        $this->extension->isLoaded('pcov')
            ->willReturn(true);
        $process = $this->createProcessMock(
            commandLine: "'vendor/bin/phpunit' '--coverage-html' '.dev-tools/coverage'"
        );
        $process->setEnv(Argument::that(static fn(array $env): bool => 'off' === $env['XDEBUG_MODE']))
            ->willReturn($process->reveal())
            ->shouldBeCalledOnce();

        $this->configurator->configure($process->reveal(), $this->output->reveal());
    }

    /**
     * @return void
     */
    #[Test]
    public function configureDoesNotTreatCoverageSummaryAsCoverageDriverRequirement(): void
    {
        $process = $this->createProcessMock(
            commandLine: "'composer' 'dev-tools' 'tests' '--' '--coverage-summary'"
        );
        $process->setEnv(Argument::that(static fn(array $env): bool => 'off' === $env['XDEBUG_MODE']))
            ->willReturn($process->reveal())
            ->shouldBeCalledOnce();

        $this->configurator->configure($process->reveal(), $this->output->reveal());
    }

    /**
     * @return void
     */
    #[Test]
    public function configureDoesNothingWhenXdebugIsNotLoaded(): void
    {
        $this->extension->isLoaded('xdebug')
            ->willReturn(false);
        $process = $this->createProcessMock(commandLine: "'composer' 'normalize'");
        $process->getEnv()
            ->shouldNotBeCalled();
        $process->setEnv(Argument::any())
            ->shouldNotBeCalled();

        $this->configurator->configure($process->reveal(), $this->output->reveal());
    }

    /**
     * @param string $commandLine
     * @param array<string, string> $env
     *
     * @return ObjectProphecy<Process>
     */
    private function createProcessMock(string $commandLine, array $env = []): ObjectProphecy
    {
        $process = $this->prophesize(Process::class);
        $process->getCommandLine()
            ->willReturn($commandLine);
        $process->getEnv()
            ->willReturn($env);

        return $process;
    }
}
