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

use FastForward\DevTools\Console\Output\OutputCapabilityDetectorInterface;
use FastForward\DevTools\Environment\EnvironmentInterface;
use FastForward\DevTools\Process\ColorPreservingProcessEnvironmentConfigurator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

#[CoversClass(ColorPreservingProcessEnvironmentConfigurator::class)]
final class ColorPreservingProcessEnvironmentConfiguratorTest extends TestCase
{
    use ProphecyTrait;

    private ColorPreservingProcessEnvironmentConfigurator $configurator;

    /**
     * @var ObjectProphecy<EnvironmentInterface>
     */
    private ObjectProphecy $environment;

    /**
     * @var ObjectProphecy<OutputCapabilityDetectorInterface>
     */
    private ObjectProphecy $outputCapabilityDetector;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->environment = $this->prophesize(EnvironmentInterface::class);
        $this->environment->get(Argument::type('string'), Argument::cetera())
            ->willReturn(null);
        $this->outputCapabilityDetector = $this->prophesize(OutputCapabilityDetectorInterface::class);
        $this->outputCapabilityDetector->supportsAnsi(Argument::type(OutputInterface::class))
            ->willReturn(false);
        $this->configurator = new ColorPreservingProcessEnvironmentConfigurator(
            $this->environment->reveal(),
            $this->outputCapabilityDetector->reveal()
        );
    }

    /**
     * @return void
     */
    #[Test]
    public function configureAddsColorEnvironmentForDecoratedOutput(): void
    {
        $process = $this->prophesize(Process::class);
        $output = $this->prophesize(OutputInterface::class);
        $this->outputCapabilityDetector->supportsAnsi($output->reveal())
            ->willReturn(true);
        $process->getEnv()
            ->willReturn([
                'EXISTING_ENV' => 'kept',
            ]);
        $process->setEnv(Argument::that(static fn(array $env): bool => 'kept' === $env['EXISTING_ENV']
            && '1' === $env['FORCE_COLOR']
            && '1' === $env['CLICOLOR_FORCE']))
            ->willReturn($process->reveal())
            ->shouldBeCalledOnce();

        $this->configurator->configure($process->reveal(), $output->reveal());
    }

    /**
     * @return void
     */
    #[Test]
    public function configureDoesNotOverrideNoColorOptOut(): void
    {
        $process = $this->prophesize(Process::class);
        $output = $this->prophesize(OutputInterface::class);
        $this->outputCapabilityDetector->supportsAnsi($output->reveal())
            ->willReturn(true);
        $process->getEnv()
            ->willReturn([
                'NO_COLOR' => '1',
            ]);
        $process->setEnv(Argument::any())
            ->shouldNotBeCalled();

        $this->configurator->configure($process->reveal(), $output->reveal());
    }

    /**
     * @return void
     */
    #[Test]
    public function configureDoesNothingWhenColorIsNotRequested(): void
    {
        $process = $this->prophesize(Process::class);
        $output = $this->prophesize(OutputInterface::class);
        $process->getEnv()
            ->shouldNotBeCalled();
        $process->setEnv(Argument::any())
            ->shouldNotBeCalled();

        $this->configurator->configure($process->reveal(), $output->reveal());
    }

    /**
     * @return void
     */
    #[Test]
    public function configureHonorsParentForceColorForPlainOutput(): void
    {
        $process = $this->prophesize(Process::class);
        $output = $this->prophesize(OutputInterface::class);
        $this->environment->get('FORCE_COLOR', '')
            ->willReturn('1');
        $process->getEnv()
            ->willReturn([]);
        $process->setEnv(Argument::that(static fn(array $env): bool => '1' === $env['FORCE_COLOR']
            && '1' === $env['CLICOLOR_FORCE']))
            ->willReturn($process->reveal())
            ->shouldBeCalledOnce();

        $this->configurator->configure($process->reveal(), $output->reveal());
    }
}
